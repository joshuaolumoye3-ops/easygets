<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ExpireOtpActivations extends Command
{
    protected $signature = 'activations:expire-otp';
    protected $description = 'Auto-expire OTP activations that have passed their expiry time';

    public function handle()
    {
        $now = Carbon::now();
        $expired = DB::table('transactions')
            ->where('type', 'activation')
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->get();

        foreach ($expired as $activation) {
            DB::transaction(function() use ($activation) {
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update([
                        'status' => 'failed',
                        'updated_at' => now()
                    ]);
                $wallet = DB::table('wallets')->where('user_id', $activation->user_id)->first();
                if ($wallet) {
                    DB::table('wallets')->where('user_id', $activation->user_id)->increment('balance', $activation->amount);
                    DB::table('transactions')->insert([
                        'reference' => 'REF_' . strtoupper(Str::random(10)),
                        'user_id' => $activation->user_id,
                        'type' => 'credit',
                        'channel' => 'Refund',
                        'provider' => $activation->service_name ?? $activation->provider,
                        'amount' => $activation->amount,
                        'status' => 'success',
                        'description' => 'Activation expired refund for ' . $activation->phone,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
        }
        $this->info(count($expired) . ' OTP activations expired and refunded.');
    }
}
