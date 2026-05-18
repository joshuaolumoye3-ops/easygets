<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Helpers\MoreThanPanelHelper;

class CheckPendingSocialOrders extends Command
{
    protected $signature = 'social:check-pending-orders
                            {--limit=50 : Maximum orders to process per run}';

    protected $description = 'Poll MoreThanPanel for pending social boost orders and update their statuses';

    protected $mtpHelper;

    public function __construct()
    {
        parent::__construct();
        $this->mtpHelper = new MoreThanPanelHelper();
    }

    public function handle()
    {
        $lockKey = 'social:check-pending-orders-lock';

        if (Cache::has($lockKey)) {
            $this->warn('Previous process still running. Skipping...');
            return 0;
        }

        Cache::put($lockKey, true, 180); // 3-minute lock

        try {
            $limit = (int) $this->option('limit');

            $pending = DB::table('transactions')
                ->where('type', 'social_boost')
                ->whereIn('status', ['pending', 'processing'])
                ->whereNotNull('request_id')
                ->orderBy('created_at', 'asc')
                ->take($limit)
                ->get();

            $this->info("Found " . count($pending) . " pending social boost order(s).");

            $checked  = 0;
            $updated  = 0;
            $failed   = 0;

            foreach ($pending as $transaction) {
                $checked++;

                try {
                    $result = $this->processOne($transaction);

                    if ($result['updated']) $updated++;
                    if ($result['failed'])  $failed++;

                    // Small delay to avoid hammering the API
                    usleep(300000); // 300ms

                } catch (\Exception $e) {
                    $failed++;
                    Log::error('social:check-pending-orders — error processing order', [
                        'transaction_id' => $transaction->id,
                        'request_id'     => $transaction->request_id,
                        'error'          => $e->getMessage(),
                    ]);
                    $this->error("✗ [ID: {$transaction->id}] {$e->getMessage()}");
                }
            }

            $this->newLine();
            $this->line('<fg=cyan>═══════════════════════════════════════</>');
            $this->line('<fg=cyan>🚀 Social Boost Order Checker Report</>');
            $this->line("  Checked: <fg=white>{$checked}</>");
            $this->line("  Updated: <fg=green>{$updated}</>");
            $this->line("  Failed:  <fg=red>{$failed}</>");
            $this->line("  Pending: <fg=yellow>" . ($checked - $updated - $failed) . '</> still in progress');
            $this->line('<fg=cyan>═══════════════════════════════════════</>');
            $this->newLine();

            return 0;

        } finally {
            Cache::forget($lockKey);
        }
    }

    private function processOne($transaction): array
    {
        $statusResponse = $this->mtpHelper->getOrderStatus($transaction->request_id);

        if (!$statusResponse) {
            Log::warning('social:check-pending-orders — empty API response', [
                'transaction_id' => $transaction->id,
                'request_id'     => $transaction->request_id,
            ]);
            return ['updated' => false, 'failed' => false];
        }

        if (isset($statusResponse['error'])) {
            Log::error('social:check-pending-orders — API error', [
                'transaction_id' => $transaction->id,
                'request_id'     => $transaction->request_id,
                'error'          => $statusResponse['error'],
            ]);
            return ['updated' => false, 'failed' => true];
        }

        $mtpStatus  = $statusResponse['status'] ?? 'Pending';
        $localStatus = $this->mtpHelper->mapStatus($mtpStatus);

        // Normalise: treat "completed" as "success" to match the rest of the app
        if ($localStatus === 'completed') {
            $localStatus = 'success';
        }

        // No change — nothing to write
        if ($localStatus === $transaction->status) {
            $this->line("⧖ [ID: {$transaction->id}] No change — {$mtpStatus}");
            return ['updated' => false, 'failed' => false];
        }

        // Merge new API data into the existing response JSON
        $existingResponse = json_decode($transaction->response, true) ?? [];
        $mergedResponse   = array_merge($existingResponse, [
            'mtp_status'   => $mtpStatus,
            'start_count'  => $statusResponse['start_count'] ?? $existingResponse['start_count'] ?? null,
            'remains'      => $statusResponse['remains']     ?? $existingResponse['remains']     ?? null,
            'charge'       => $statusResponse['charge']      ?? null,
            'last_checked' => now()->toIso8601String(),
        ]);

        DB::table('transactions')
            ->where('id', $transaction->id)
            ->update([
                'status'     => $localStatus,
                'response'   => json_encode($mergedResponse),
                'updated_at' => now(),
            ]);

        $icon = in_array($localStatus, ['success', 'partial']) ? '✓' : '↩';
        $this->info("{$icon} [ID: {$transaction->id}] {$transaction->status} → {$localStatus} (MTP: {$mtpStatus})");

        Log::info('social:check-pending-orders — status updated', [
            'transaction_id' => $transaction->id,
            'request_id'     => $transaction->request_id,
            'old_status'     => $transaction->status,
            'new_status'     => $localStatus,
            'mtp_status'     => $mtpStatus,
        ]);

        return ['updated' => true, 'failed' => false];
    }
}