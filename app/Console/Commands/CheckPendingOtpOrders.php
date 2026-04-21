<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CheckPendingOtpOrders extends Command
{
    protected $signature = 'otp:check-pending-orders';
    protected $description = 'Poll pending OTP/activation orders from ALL providers and update SMS codes / statuses';

    public function handle()
    {
        $pending = Transaction::whereIn('type', ['otp', 'activation'])
            ->whereIn('status', ['pending', 'processing'])
            ->whereNotNull('request_id')
            ->whereNull('sms_code')
            ->whereNotIn('channel', ['Refund', 'admin'])
            ->get();

        $checked  = 0;
        $updated  = 0;
        $refunded = 0;

        foreach ($pending as $txn) {
            $checked++;
            $provider = $this->normalizeProvider($txn->channel);

            try {
                $result = $provider === 'provider1'
                    ? $this->check5sim($txn)
                    : $this->checkSmsBus($txn);

                if ($result['updated'])  $updated++;
                if ($result['refunded']) $refunded++;

            } catch (\Exception $e) {
                Log::error('OTP poll failed', [
                    'txn_id'     => $txn->id,
                    'provider'   => $provider,
                    'error'      => $e->getMessage(),
                    'request_id' => $txn->request_id,
                ]);
                $this->error("✗ [ID: {$txn->id}] Error: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->line('<fg=cyan>═══════════════════════════════════════</>');
        $this->line('<fg=cyan>🔍 OTP Checker Report</>');
        $this->line("  Checked:  <fg=white>{$checked}</> pending orders");
        $this->line("  Updated:  <fg=green>{$updated}</> orders with new data");
        $this->line("  Refunded: <fg=yellow>{$refunded}</> auto-refunded");
        $this->line("  Pending:  <fg=yellow>" . ($checked - $updated) . '</> still waiting');
        $this->line('<fg=cyan>═══════════════════════════════════════</>');
        $this->newLine();
    }

    private function check5sim(Transaction $txn): array
    {
        $token    = env('FIVESIM_TOKEN');
        $response = Http::timeout(15)
            ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
            ->withOptions(['verify' => false])
            ->get("https://5sim.net/v1/user/check/{$txn->request_id}");

        if (!$response->ok()) {
            $this->line("⚠ [ID: {$txn->id}] 5sim API error: {$response->status()}");
            return ['updated' => false, 'refunded' => false];
        }

        $data      = $response->json();
        $apiStatus = $data['status'] ?? 'UNKNOWN';

        // SMS received
        if (!empty($data['sms']) && is_array($data['sms'])) {
            foreach ($data['sms'] as $sms) {
                if (!empty($sms['code'])) {
                    $txn->sms_code = $sms['code'];
                    $txn->status   = 'completed';
                    $txn->save();
                    try {
                        Http::timeout(10)
                            ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
                            ->withOptions(['verify' => false])
                            ->get("https://5sim.net/v1/user/finish/{$txn->request_id}");
                    } catch (\Exception $e) {
                        Log::warning('Failed to finish 5sim order', ['error' => $e->getMessage()]);
                    }
                    $this->info("✓ [ID: {$txn->id}] SMS: {$sms['code']}");
                    return ['updated' => true, 'refunded' => false];
                }
            }
        }

        // Terminal states — refund
        if (in_array($apiStatus, ['CANCELED', 'TIMEOUT'], true)) {
            $ok = $this->autoRefund($txn, 'expired', "5sim {$apiStatus} auto-refund [activation #{$txn->id}]");
            $this->line("↩ [ID: {$txn->id}] {$apiStatus} — refund " . ($ok ? 'OK ✓' : 'FAILED ✗'));
            return ['updated' => true, 'refunded' => $ok];
        }

        if ($apiStatus === 'FINISHED') {
            $txn->status = 'completed';
            $txn->save();
            $this->line("✓ [ID: {$txn->id}] FINISHED");
            return ['updated' => true, 'refunded' => false];
        }

        $this->line("⧖ [ID: {$txn->id}] Waiting — 5sim: {$apiStatus}");
        return ['updated' => false, 'refunded' => false];
    }

    private function checkSmsBus(Transaction $txn): array
    {
        $token    = env('SMSBUS_TOKEN');
        $response = Http::timeout(15)
            ->withOptions(['verify' => false])
            ->get('https://sms-bus.com/api/control/get/sms', [
                'token'      => $token,
                'request_id' => $txn->request_id,
            ]);

        if (!$response->ok()) {
            $this->line("⚠ [ID: {$txn->id}] SMS-Bus API error: {$response->status()}");
            return ['updated' => false, 'refunded' => false];
        }

        $data = $response->json();
        $code = $data['code'] ?? null;

        if ($code === 200 && !empty($data['data'])) {
            $txn->sms_code = $data['data'];
            $txn->status   = 'completed';
            $txn->save();
            $this->info("✓ [ID: {$txn->id}] SMS: {$data['data']}");
            return ['updated' => true, 'refunded' => false];
        }

        if ($code === 50101) {
            $this->line("⧖ [ID: {$txn->id}] SMS-Bus: waiting");
            return ['updated' => false, 'refunded' => false];
        }

        if ($code === 50102) {
            $ok = $this->autoRefund($txn, 'expired', "SMS-Bus expired auto-refund [activation #{$txn->id}]");
            $this->line("↩ [ID: {$txn->id}] SMS-Bus expired — refund " . ($ok ? 'OK ✓' : 'FAILED ✗'));
            return ['updated' => true, 'refunded' => $ok];
        }

        $this->line("⧖ [ID: {$txn->id}] SMS-Bus unknown code: {$code}");
        return ['updated' => false, 'refunded' => false];
    }

    private function autoRefund(Transaction $txn, string $status, string $description): bool
    {
        try {
            DB::transaction(function () use ($txn, $status, $description) {

                // Re-read with row lock
                $current = DB::table('transactions')
                    ->where('id', $txn->id)
                    ->lockForUpdate()
                    ->first();

                if (!$current || !in_array($current->status, ['pending', 'processing'], true)) {
                    Log::info("autoRefund skipped — status is already [{$current->status}]", ['txn_id' => $txn->id]);
                    return;
                }

                // Idempotency: look for existing refund by activation ID in description
                // (avoids JSON_EXTRACT which can fail if response column has non-JSON data)
                $alreadyRefunded = DB::table('transactions')
                    ->where('type', 'credit')
                    ->where('channel', 'Refund')
                    ->where('user_id', $txn->user_id)
                    ->where('amount', $txn->amount)
                    ->where('description', 'like', '%[activation #' . $txn->id . ']%')
                    ->exists();

                if ($alreadyRefunded) {
                    Log::info('autoRefund: duplicate detected, marking status only', ['txn_id' => $txn->id]);
                    DB::table('transactions')
                        ->where('id', $txn->id)
                        ->update(['status' => $status, 'updated_at' => now()]);
                    return;
                }

                // Update activation status
                DB::table('transactions')
                    ->where('id', $txn->id)
                    ->update(['status' => $status, 'updated_at' => now()]);

                // Lock wallet
                $wallet = DB::table('wallets')
                    ->where('user_id', $txn->user_id)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    throw new \Exception("Wallet not found for user_id: {$txn->user_id}");
                }

                // Credit wallet
                DB::table('wallets')
                    ->where('user_id', $txn->user_id)
                    ->increment('balance', $txn->amount);

                // Build refund record using only guaranteed columns
                $refundData = [
                    'reference'   => 'REF_' . strtoupper(Str::random(10)),
                    'user_id'     => $txn->user_id,
                    'type'        => 'credit',
                    'channel'     => 'Refund',
                    'amount'      => $txn->amount,
                    'status'      => 'success',
                    'description' => $description,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];

                // Safely add optional columns
                $this->addIfColumnExists($refundData, 'provider',      $txn->service_name ?? $txn->provider ?? 'System');
                $this->addIfColumnExists($refundData, 'phone',         $txn->phone ?? null);
                $this->addIfColumnExists($refundData, 'recipient',     null);
                $this->addIfColumnExists($refundData, 'service_name',  $txn->service_name ?? null);
                $this->addIfColumnExists($refundData, 'balance_after', (float) DB::table('wallets')->where('user_id', $txn->user_id)->value('balance'));
                $this->addIfColumnExists($refundData, 'response',      json_encode(['original_activation_id' => $txn->id, 'reason' => 'auto-refund']));

                DB::table('transactions')->insert($refundData);

                Log::info('✓ autoRefund completed', [
                    'txn_id'    => $txn->id,
                    'user_id'   => $txn->user_id,
                    'amount'    => $txn->amount,
                    'reference' => $refundData['reference'],
                ]);
            });

            return true;

        } catch (\Exception $e) {
            Log::error('✗ autoRefund FAILED', [
                'txn_id' => $txn->id,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /** Add a key=>value to $data only if the column exists in the transactions table */
    private function addIfColumnExists(array &$data, string $column, $value): void
    {
        static $columns = null;
        if ($columns === null) {
            $columns = DB::getSchemaBuilder()->getColumnListing('transactions');
        }
        if (in_array($column, $columns, true)) {
            $data[$column] = $value;
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