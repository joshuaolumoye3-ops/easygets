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
        // Fetch all pending activation/otp rows that have a request_id
        // Exclude rows that are already terminal so we don't hammer the APIs pointlessly
        $pending = Transaction::whereIn('type', ['otp', 'activation'])
            ->whereIn('status', ['pending', 'processing'])
            ->whereNotNull('request_id')
            ->whereNull('sms_code')          // already has a code → no need to re-poll
            ->whereNotIn('channel', ['Refund', 'admin'])
            ->get();

        $checked  = 0;
        $updated  = 0;
        $refunded = 0;

        foreach ($pending as $txn) {
            $checked++;
            $provider = $this->normalizeProvider($txn->channel);

            try {
                if ($provider === 'provider1') {
                    $result = $this->check5sim($txn);
                } else {
                    $result = $this->checkSmsBus($txn);
                }

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

    // ──────────────────────────────────────────────────────────────────────────
    // 5SIM POLLER
    // ──────────────────────────────────────────────────────────────────────────

    private function check5sim(Transaction $txn): array
    {
        $token = env('FIVESIM_TOKEN');

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

        // ── SMS code received ──
        if (!empty($data['sms']) && is_array($data['sms'])) {
            foreach ($data['sms'] as $sms) {
                if (!empty($sms['code'])) {
                    $code = $sms['code'];

                    $txn->sms_code = $code;
                    $txn->status   = 'completed';
                    $txn->save();

                    // Try to finish the order on the provider side
                    try {
                        Http::timeout(10)
                            ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
                            ->withOptions(['verify' => false])
                            ->get("https://5sim.net/v1/user/finish/{$txn->request_id}");
                    } catch (\Exception $e) {
                        Log::warning('Failed to finish 5sim order', ['error' => $e->getMessage()]);
                    }

                    $this->info("✓ [ID: {$txn->id}] SMS Code: <fg=green>{$code}</> | Status: {$apiStatus}");
                    Log::info("OTP received for txn {$txn->id}", ['code' => $code, 'api_status' => $apiStatus]);
                    return ['updated' => true, 'refunded' => false];
                }
            }
        }

        // ── Terminal states without an SMS code — refund ──
        if (in_array($apiStatus, ['CANCELED', 'TIMEOUT'], true)) {
            $refunded = $this->autoRefund($txn, 'expired', "5sim activation {$apiStatus} — auto-refunded");
            $label    = $apiStatus === 'CANCELED' ? 'CANCELED' : 'TIMEOUT';
            $this->error("✓ [ID: {$txn->id}] Status: <fg=red>{$label}</> — refund " . ($refunded ? 'OK' : 'FAILED'));
            return ['updated' => true, 'refunded' => $refunded];
        }

        if ($apiStatus === 'FINISHED') {
            // Finished without code → mark completed (no refund; the provider delivered)
            $txn->status = 'completed';
            $txn->save();
            $this->comment("✓ [ID: {$txn->id}] Status: <fg=yellow>FINISHED</> (no code)");
            return ['updated' => true, 'refunded' => false];
        }

        // Still pending
        $this->line("⧖ [ID: {$txn->id}] Still waiting — 5sim status: {$apiStatus}");
        return ['updated' => false, 'refunded' => false];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SMS-BUS POLLER
    // ──────────────────────────────────────────────────────────────────────────

    private function checkSmsBus(Transaction $txn): array
    {
        $token = env('SMSBUS_TOKEN');

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

        // ── SMS received ──
        if ($code === 200 && !empty($data['data'])) {
            $smsCode = $data['data'];

            $txn->sms_code = $smsCode;
            $txn->status   = 'completed';
            $txn->save();

            $this->info("✓ [ID: {$txn->id}] SMS Code: <fg=green>{$smsCode}</>");
            Log::info("SMS-Bus OTP received for txn {$txn->id}", ['code' => $smsCode]);
            return ['updated' => true, 'refunded' => false];
        }

        // ── Still waiting ──
        if ($code === 50101) {
            $this->line("⧖ [ID: {$txn->id}] SMS-Bus: still waiting for SMS");
            return ['updated' => false, 'refunded' => false];
        }

        // ── Number expired/released ──
        if ($code === 50102) {
            $refunded = $this->autoRefund($txn, 'expired', 'SMS-Bus number expired/released — auto-refunded');
            $this->error("✓ [ID: {$txn->id}] SMS-Bus: number expired — refund " . ($refunded ? 'OK' : 'FAILED'));
            return ['updated' => true, 'refunded' => $refunded];
        }

        $this->line("⧖ [ID: {$txn->id}] SMS-Bus unexpected code: {$code}");
        return ['updated' => false, 'refunded' => false];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SHARED AUTO-REFUND
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Mark a transaction as $status and credit the user's wallet.
     * Safe to call multiple times — guarded by a status check inside the transaction.
     */
    private function autoRefund(Transaction $txn, string $status, string $description): bool
    {
        try {
            DB::transaction(function () use ($txn, $status, $description) {

                // Re-read with lock to prevent race with other jobs/requests
                $current = DB::table('transactions')
                    ->where('id', $txn->id)
                    ->lockForUpdate()
                    ->first();

                // Only refund if still in a pending/processing state
                if (!$current || !in_array($current->status, ['pending', 'processing'], true)) {
                    Log::info("autoRefund skipped — status already {$current->status}", ['txn_id' => $txn->id]);
                    return;
                }

                // Check for an existing refund in the last 10 minutes (idempotency)
                $exists = DB::table('transactions')
                    ->where('type', 'credit')
                    ->where('channel', 'Refund')
                    ->where('user_id', $txn->user_id)
                    ->where('amount', $txn->amount)
                    ->where('created_at', '>=', Carbon::now()->subMinutes(10))
                    ->whereRaw("JSON_EXTRACT(response, '$.original_activation_id') = ?", [$txn->id])
                    ->exists();

                if ($exists) {
                    Log::info('autoRefund: duplicate detected, skipping', ['txn_id' => $txn->id]);
                    return;
                }

                // Update activation status
                DB::table('transactions')
                    ->where('id', $txn->id)
                    ->update(['status' => $status, 'updated_at' => now()]);

                // Credit wallet
                $wallet = DB::table('wallets')
                    ->where('user_id', $txn->user_id)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    throw new \Exception("Wallet not found for user_id: {$txn->user_id}");
                }

                DB::table('wallets')
                    ->where('user_id', $txn->user_id)
                    ->increment('balance', $txn->amount);

                $newBalance = (float) DB::table('wallets')->where('user_id', $txn->user_id)->value('balance');

                // Record the refund credit transaction
                DB::table('transactions')->insert([
                    'reference'    => 'REF_' . strtoupper(Str::random(10)),
                    'user_id'      => $txn->user_id,
                    'type'         => 'credit',
                    'channel'      => 'Refund',
                    'provider'     => $txn->service_name ?? $txn->provider ?? 'System',
                    'phone'        => $txn->phone ?? null,
                    'recipient'    => null,
                    'amount'       => $txn->amount,
                    'status'       => 'success',
                    'description'  => $description,
                    'response'     => json_encode([
                        'original_activation_id' => $txn->id,
                        'reason'                 => 'OTP poller auto-refund',
                    ]),
                    'balance_after' => $newBalance,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                Log::info('autoRefund completed', [
                    'txn_id'       => $txn->id,
                    'user_id'      => $txn->user_id,
                    'amount'       => $txn->amount,
                    'status'       => $status,
                    'new_balance'  => $newBalance,
                ]);
            });

            return true;

        } catch (\Exception $e) {
            Log::error('autoRefund failed', [
                'txn_id' => $txn->id,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);
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