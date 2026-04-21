<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ExpireOtpActivations extends Command
{
    protected $signature   = 'activations:expire-otp';
    protected $description = 'Expire OTP activations past their expires_at time, cancel at provider, and refund users';

    public function handle()
    {
        $now = Carbon::now();

        // Only pick up rows that have a real expiry time set and are still pending
        // Do NOT touch rows without expires_at (those are handled by process-expired)
        $expired = DB::table('transactions')
            ->where('type', 'activation')
            ->where('status', 'pending')
            ->whereNull('sms_code')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->whereNotIn('channel', ['Refund', 'admin'])
            ->get();

        $count    = 0;
        $refunded = 0;

        foreach ($expired as $activation) {
            $count++;
            $provider = $this->normalizeProvider($activation->channel);

            try {
                // Best-effort cancel at provider (don't fail the refund if cancel fails)
                $this->attemptCancel($activation, $provider);

                // Refund inside a transaction
                $ok = $this->refund($activation);
                if ($ok) $refunded++;

            } catch (\Exception $e) {
                Log::error('ExpireOtpActivations: error processing activation', [
                    'activation_id' => $activation->id,
                    'error'         => $e->getMessage(),
                    'trace'         => $e->getTraceAsString(),
                ]);
                $this->error("✗ [ID: {$activation->id}] {$e->getMessage()}");
            }
        }

        $this->info("{$refunded}/{$count} OTP activations expired and refunded.");
        Log::info('ExpireOtpActivations completed', ['total' => $count, 'refunded' => $refunded]);
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function attemptCancel($activation, string $provider): void
    {
        try {
            if ($provider === 'provider1') {
                $token = env('FIVESIM_TOKEN');
                Http::timeout(10)
                    ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
                    ->withOptions(['verify' => false])
                    ->get("https://5sim.net/v1/user/cancel/{$activation->request_id}");

            } elseif ($provider === 'provider2') {
                $token = env('SMSBUS_TOKEN');
                Http::timeout(10)
                    ->withOptions(['verify' => false])
                    ->get('https://sms-bus.com/api/control/cancel', [
                        'token'      => $token,
                        'request_id' => $activation->request_id,
                    ]);
            }
        } catch (\Exception $e) {
            // Log but don't propagate — we still refund even if cancel fails
            Log::warning('ExpireOtpActivations: provider cancel failed (non-fatal)', [
                'activation_id' => $activation->id,
                'provider'      => $provider,
                'error'         => $e->getMessage(),
            ]);
        }
    }

    private function refund($activation): bool
    {
        try {
            DB::transaction(function () use ($activation) {

                // Re-read with lock
                $current = DB::table('transactions')
                    ->where('id', $activation->id)
                    ->lockForUpdate()
                    ->first();

                // Guard: skip if already moved to a terminal state by another job
                if (!$current || $current->status !== 'pending') {
                    Log::info('ExpireOtpActivations: refund skipped — already ' . ($current->status ?? 'gone'), [
                        'activation_id' => $activation->id,
                    ]);
                    return;
                }

                // Check idempotency — no double-refund for the same activation
                $exists = DB::table('transactions')
                    ->where('type', 'credit')
                    ->where('channel', 'Refund')
                    ->where('user_id', $activation->user_id)
                    ->where('amount', $activation->amount)
                    ->whereRaw("JSON_EXTRACT(response, '$.original_activation_id') = ?", [$activation->id])
                    ->exists();

                if ($exists) {
                    // Mark as expired anyway so it won't be picked up again
                    DB::table('transactions')
                        ->where('id', $activation->id)
                        ->update(['status' => 'expired', 'updated_at' => now()]);
                    Log::info('ExpireOtpActivations: duplicate refund skipped', ['activation_id' => $activation->id]);
                    return;
                }

                // Mark activation as expired
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update(['status' => 'expired', 'updated_at' => now()]);

                // Credit wallet
                $wallet = DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    throw new \Exception("Wallet not found for user_id: {$activation->user_id}");
                }

                DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->increment('balance', $activation->amount);

                $newBalance = (float) DB::table('wallets')->where('user_id', $activation->user_id)->value('balance');

                DB::table('transactions')->insert([
                    'reference'    => 'REF_' . strtoupper(Str::random(10)),
                    'user_id'      => $activation->user_id,
                    'type'         => 'credit',
                    'channel'      => 'Refund',
                    'provider'     => $activation->service_name ?? $activation->provider ?? 'System',
                    'phone'        => $activation->phone ?? null,
                    'recipient'    => null,
                    'amount'       => $activation->amount,
                    'status'       => 'success',
                    'description'  => 'Activation expired refund for ' . ($activation->phone ?? $activation->request_id),
                    'response'     => json_encode([
                        'original_activation_id' => $activation->id,
                        'reason'                 => 'expires_at passed',
                    ]),
                    'balance_after' => $newBalance,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                Log::info('ExpireOtpActivations: refund completed', [
                    'activation_id' => $activation->id,
                    'user_id'       => $activation->user_id,
                    'amount'        => $activation->amount,
                    'new_balance'   => $newBalance,
                ]);

                $this->info("✓ [ID: {$activation->id}] Refunded ₦" . number_format($activation->amount, 2));
            });

            return true;

        } catch (\Exception $e) {
            Log::error('ExpireOtpActivations: refund failed', [
                'activation_id' => $activation->id,
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
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