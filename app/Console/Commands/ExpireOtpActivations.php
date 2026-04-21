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
        $expired = DB::table('transactions')
            ->where('type', 'activation')
            ->where('status', 'pending')
            ->whereNull('sms_code')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->whereNotIn('channel', ['Refund', 'admin'])
            ->get();

        $count = 0; $refunded = 0;

        foreach ($expired as $activation) {
            $count++;
            $provider = $this->normalizeProvider($activation->channel);

            try {
                $this->cancelAtProvider($activation, $provider);
                $ok = $this->doRefund($activation);
                if ($ok) {
                    $refunded++;
                    $this->info("✓ [ID: {$activation->id}] Refunded ₦" . number_format($activation->amount, 2));
                } else {
                    $this->error("✗ [ID: {$activation->id}] Refund FAILED — check laravel.log");
                }
            } catch (\Exception $e) {
                Log::error('ExpireOtpActivations error', ['id' => $activation->id, 'error' => $e->getMessage()]);
                $this->error("✗ [ID: {$activation->id}] {$e->getMessage()}");
            }
        }

        $this->info("{$refunded}/{$count} activations expired and refunded.");
    }

    private function cancelAtProvider($activation, string $provider): void
    {
        try {
            if ($provider === 'provider1') {
                $token = env('FIVESIM_TOKEN');
                Http::timeout(10)
                    ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
                    ->withOptions(['verify' => false])
                    ->get("https://5sim.net/v1/user/cancel/{$activation->request_id}");
            } else {
                $token = env('SMSBUS_TOKEN');
                Http::timeout(10)
                    ->withOptions(['verify' => false])
                    ->get('https://sms-bus.com/api/control/cancel', [
                        'token'      => $token,
                        'request_id' => $activation->request_id,
                    ]);
            }
        } catch (\Exception $e) {
            Log::warning('cancelAtProvider failed (non-fatal)', ['id' => $activation->id, 'error' => $e->getMessage()]);
        }
    }

    private function doRefund($activation): bool
    {
        try {
            DB::transaction(function () use ($activation) {

                $current = DB::table('transactions')
                    ->where('id', $activation->id)
                    ->lockForUpdate()
                    ->first();

                if (!$current || $current->status !== 'pending') {
                    Log::info("ExpireOtp: refund skipped — [{$current->status}]", ['id' => $activation->id]);
                    return;
                }

                // Idempotency
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
                        ->update(['status' => 'expired', 'updated_at' => now()]);
                    return;
                }

                // Mark expired
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update(['status' => 'expired', 'updated_at' => now()]);

                // Lock + credit wallet
                $wallet = DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) throw new \Exception("Wallet not found for user {$activation->user_id}");

                DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->increment('balance', $activation->amount);

                $row = [
                    'reference'   => 'REF_' . strtoupper(Str::random(10)),
                    'user_id'     => $activation->user_id,
                    'type'        => 'credit',
                    'channel'     => 'Refund',
                    'amount'      => $activation->amount,
                    'status'      => 'success',
                    'description' => 'Activation expired refund [activation #' . $activation->id . ']',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];

                $cols = DB::getSchemaBuilder()->getColumnListing('transactions');
                if (in_array('provider',      $cols)) $row['provider']      = $activation->service_name ?? $activation->provider ?? 'System';
                if (in_array('phone',         $cols)) $row['phone']         = $activation->phone ?? null;
                if (in_array('recipient',     $cols)) $row['recipient']     = null;
                if (in_array('service_name',  $cols)) $row['service_name']  = $activation->service_name ?? null;
                if (in_array('balance_after', $cols)) $row['balance_after'] = (float) DB::table('wallets')->where('user_id', $activation->user_id)->value('balance');
                if (in_array('response',      $cols)) $row['response']      = json_encode(['original_activation_id' => $activation->id, 'reason' => 'expires_at passed']);

                DB::table('transactions')->insert($row);

                Log::info('✓ ExpireOtp refund OK', [
                    'id'        => $activation->id,
                    'user_id'   => $activation->user_id,
                    'amount'    => $activation->amount,
                    'reference' => $row['reference'],
                ]);
            });

            return true;

        } catch (\Exception $e) {
            Log::error('✗ ExpireOtp refund FAILED', ['id' => $activation->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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