<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ProcessExpiredActivations extends Command
{
    protected $signature = 'activations:process-expired
                            {--limit=100 : Maximum activations to process per run}
                            {--timeout=3 : Minutes before auto-cancelling a pending activation}';

    protected $description = 'Auto-cancel and refund pending activations after 3 minutes with no SMS';

    private $lockTimeout = 180;

    public function handle()
    {
        $limit          = (int) $this->option('limit');
        $timeoutMinutes = (int) $this->option('timeout');

        $lockKey = 'activations:process-expired-lock';
        if (Cache::has($lockKey)) {
            $this->warn('Previous process still running. Skipping...');
            return 0;
        }
        Cache::put($lockKey, true, $this->lockTimeout);

        try {
            $threshold = Carbon::now()->subMinutes($timeoutMinutes);

            $pending = DB::table('transactions')
                ->where('type', 'activation')
                ->where('status', 'pending')
                ->whereNotNull('request_id')
                ->whereNull('sms_code')
                ->where('created_at', '<', $threshold)
                ->whereNotIn('channel', ['Refund', 'admin'])
                ->take($limit)
                ->get();

            $this->info("Found " . count($pending) . " pending activations older than {$timeoutMinutes} min");

            $processed = 0; $refunded = 0; $failed = 0;

            foreach ($pending as $activation) {
                usleep(500000); // 0.5s between API calls
                try {
                    $result = $this->processOne($activation);
                    if ($result['success'])  $processed++;
                    if ($result['refunded']) $refunded++;
                    if (!$result['success']) $failed++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::error('processExpired: error', ['id' => $activation->id, 'error' => $e->getMessage()]);
                    $this->error("✗ [ID: {$activation->id}] {$e->getMessage()}");
                }
            }

            $this->info("✓ Done: {$processed} processed, {$refunded} refunded, {$failed} failed");
            return 0;

        } finally {
            Cache::forget($lockKey);
        }
    }

    private function processOne($activation): array
    {
        $provider = $this->normalizeProvider($activation->channel);
        $this->info("[{$provider}] ID: {$activation->id} | request_id: {$activation->request_id}");

        // Check if SMS already arrived on provider side
        $apiStatus = $this->checkStatus($activation, $provider);

        if ($apiStatus['sms_received']) {
            $this->info("  → SMS already received — marking completed, no refund");
            DB::table('transactions')
                ->where('id', $activation->id)
                ->where('status', 'pending')
                ->update(['status' => 'completed', 'updated_at' => now()]);
            return ['success' => true, 'refunded' => false];
        }

        if (!$apiStatus['valid']) {
            // Already expired on provider side
            $this->info("  → Provider says number expired");
            $ok = $this->doRefund($activation, 'expired', "Auto-expired: no SMS after {$this->option('timeout')}min [activation #{$activation->id}]");
            $this->line("  " . ($ok ? '✓ Refunded' : '✗ Refund FAILED'));
            return ['success' => true, 'refunded' => $ok];
        }

        // Still active — cancel then refund
        $this->info("  → Cancelling at provider...");
        $cancel = $this->cancelAt($activation, $provider);

        if ($cancel['success']) {
            $ok = $this->doRefund($activation, 'expired', "Auto-cancelled: no SMS after {$this->option('timeout')}min [activation #{$activation->id}]");
            $this->line("  " . ($ok ? '✓ Cancelled + Refunded' : '✗ Cancelled but Refund FAILED'));
            return ['success' => true, 'refunded' => $ok];
        }

        $this->warn("  ✗ Cancel failed: " . ($cancel['reason'] ?? 'unknown'));
        return ['success' => false, 'refunded' => false];
    }

    private function checkStatus($activation, string $provider): array
    {
        try {
            return $provider === 'provider1'
                ? $this->checkStatus5sim($activation)
                : $this->checkStatusSmsBus($activation);
        } catch (\Exception $e) {
            Log::warning('checkStatus failed — assuming valid', ['id' => $activation->id, 'error' => $e->getMessage()]);
            return ['valid' => true, 'sms_received' => false];
        }
    }

    private function checkStatus5sim($activation): array
    {
        $token    = env('FIVESIM_TOKEN');
        $response = Http::timeout(15)
            ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
            ->withOptions(['verify' => false])
            ->get("https://5sim.net/v1/user/check/{$activation->request_id}");

        if (!$response->successful()) {
            return ['valid' => true, 'sms_received' => false];
        }

        $data = $response->json();

        if (!empty($data['sms']) && is_array($data['sms'])) {
            foreach ($data['sms'] as $sms) {
                if (!empty($sms['code'])) {
                    DB::table('transactions')
                        ->where('id', $activation->id)
                        ->where('status', 'pending')
                        ->update(['sms_code' => $sms['code'], 'status' => 'completed', 'updated_at' => now()]);
                    return ['valid' => true, 'sms_received' => true];
                }
            }
        }

        $apiStatus = $data['status'] ?? 'UNKNOWN';
        $isExpired = in_array($apiStatus, ['CANCELED', 'TIMEOUT', 'FINISHED'], true);
        return ['valid' => !$isExpired, 'sms_received' => false];
    }

    private function checkStatusSmsBus($activation): array
    {
        $token    = env('SMSBUS_TOKEN');
        $response = Http::timeout(15)
            ->withOptions(['verify' => false])
            ->get('https://sms-bus.com/api/control/get/sms', [
                'token'      => $token,
                'request_id' => $activation->request_id,
            ]);

        if (!$response->successful()) {
            return ['valid' => true, 'sms_received' => false];
        }

        $data = $response->json();
        $code = $data['code'] ?? null;

        if ($code === 200 && !empty($data['data'])) {
            DB::table('transactions')
                ->where('id', $activation->id)
                ->where('status', 'pending')
                ->update(['sms_code' => $data['data'], 'status' => 'completed', 'updated_at' => now()]);
            return ['valid' => true, 'sms_received' => true];
        }

        return ['valid' => $code !== 50102, 'sms_received' => false];
    }

    private function cancelAt($activation, string $provider): array
    {
        try {
            if ($provider === 'provider1') {
                $token    = env('FIVESIM_TOKEN');
                $response = Http::timeout(15)
                    ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
                    ->withOptions(['verify' => false])
                    ->get("https://5sim.net/v1/user/cancel/{$activation->request_id}");
                return ['success' => $response->successful()];
            }

            $token    = env('SMSBUS_TOKEN');
            $response = Http::timeout(15)
                ->withOptions(['verify' => false])
                ->get('https://sms-bus.com/api/control/cancel', [
                    'token'      => $token,
                    'request_id' => $activation->request_id,
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'reason' => "HTTP {$response->status()}"];
            }

            $code = $response->json()['code'] ?? null;
            // 200 = cancelled, 50103 = already closed (still treat as success)
            return ['success' => in_array($code, [200, 50103], true), 'reason' => $response->json()['message'] ?? ''];

        } catch (\Exception $e) {
            return ['success' => false, 'reason' => $e->getMessage()];
        }
    }

    private function doRefund($activation, string $status, string $description): bool
    {
        try {
            DB::transaction(function () use ($activation, $status, $description) {

                $current = DB::table('transactions')
                    ->where('id', $activation->id)
                    ->lockForUpdate()
                    ->first();

                if (!$current || $current->status !== 'pending') {
                    Log::info("doRefund skipped — status is [{$current->status}]", ['id' => $activation->id]);
                    return;
                }

                // Idempotency via description tag
                $exists = DB::table('transactions')
                    ->where('type', 'credit')
                    ->where('channel', 'Refund')
                    ->where('user_id', $activation->user_id)
                    ->where('amount', $activation->amount)
                    ->where('description', 'like', '%[activation #' . $activation->id . ']%')
                    ->exists();

                if ($exists) {
                    DB::table('transactions')
                        ->where('id', $activation->id)
                        ->update(['status' => $status, 'updated_at' => now()]);
                    return;
                }

                // Mark expired
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update(['status' => $status, 'updated_at' => now()]);

                // Lock wallet
                $wallet = DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) throw new \Exception("Wallet not found for user {$activation->user_id}");

                // Credit
                DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->increment('balance', $activation->amount);

                // Build insert with only safe columns
                $row = [
                    'reference'   => 'REF_' . strtoupper(Str::random(10)),
                    'user_id'     => $activation->user_id,
                    'type'        => 'credit',
                    'channel'     => 'Refund',
                    'amount'      => $activation->amount,
                    'status'      => 'success',
                    'description' => $description,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];

                $cols = DB::getSchemaBuilder()->getColumnListing('transactions');
                if (in_array('provider',      $cols)) $row['provider']      = $activation->service_name ?? $activation->provider ?? 'System';
                if (in_array('phone',         $cols)) $row['phone']         = $activation->phone ?? null;
                if (in_array('recipient',     $cols)) $row['recipient']     = null;
                if (in_array('service_name',  $cols)) $row['service_name']  = $activation->service_name ?? null;
                if (in_array('balance_after', $cols)) $row['balance_after'] = (float) DB::table('wallets')->where('user_id', $activation->user_id)->value('balance');
                if (in_array('response',      $cols)) $row['response']      = json_encode(['original_activation_id' => $activation->id]);

                DB::table('transactions')->insert($row);

                Log::info('✓ doRefund OK', [
                    'id'        => $activation->id,
                    'user_id'   => $activation->user_id,
                    'amount'    => $activation->amount,
                    'reference' => $row['reference'],
                ]);
            });

            return true;

        } catch (\Exception $e) {
            Log::error('✗ doRefund FAILED', ['id' => $activation->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return false;
        }
    }

    private function normalizeProvider(?string $channel): string
    {
        $p = strtolower($channel ?? 'provider2');
        if (in_array($p, ['sms-bus', 'smsbus'], true)) return 'provider2';
        if ($p === '5sim')                               return 'provider1';
        return $p;
    }
}