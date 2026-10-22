<?php

namespace App\Console\Commands;

use App\Models\GiftcardWithdrawal;
use App\Services\Payments\FlutterwavePayoutService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Safety net for missed after-response dispatches and webhooks. Fresh untouched requests
 * may be submitted once; in-flight or ambiguous requests are only read and reconciled.
 */
class ReconcileWithdrawals extends Command
{
    protected $signature = 'giftcards:reconcile-withdrawals {--limit=50}';
    protected $description = 'Submit missed fresh payouts and reconcile in-flight Flutterwave transfers';

    public function handle(FlutterwavePayoutService $payouts): int
    {
        if (config('services.flutterwave.payout_auto', true)) {
            $fresh = GiftcardWithdrawal::where('status', 'pending')
                ->where('provider', 'flutterwave')
                ->where('payout_attempts', 0)
                ->whereNull('failure_reason')
                ->where('created_at', '<', now()->subMinute())
                ->where('created_at', '>', now()->subMinutes(15))
                ->orderBy('id')->limit((int) $this->option('limit'))->get();

            foreach ($fresh as $withdrawal) {
                try {
                    $result = $payouts->process($withdrawal->id);
                    $this->line("#{$withdrawal->id} {$withdrawal->reference}: {$result['status']}");
                } catch (\Throwable $e) {
                    Log::error('Fresh gift-card payout dispatch failed', [
                        'withdrawal_id' => $withdrawal->id,
                        'reference' => $withdrawal->reference,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $rows = GiftcardWithdrawal::where('status', 'processing')
            ->where('provider', 'flutterwave')
            ->where('updated_at', '<', now()->subMinutes(2))
            ->orderBy('id')->limit((int) $this->option('limit'))->get();

        foreach ($rows as $w) {
            try {
                $res = $payouts->sync($w);
                $this->line("#{$w->id} {$w->reference}: {$res['status']}");
            } catch (\Throwable $e) {
                Log::error('Gift-card withdrawal reconciliation failed', [
                    'withdrawal_id' => $w->id,
                    'reference' => $w->reference,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($w->fresh()->status === 'processing' && $w->created_at->lt(now()->subHour())) {
                Log::warning('Withdrawal has been processing for over an hour', ['withdrawal' => $w->id, 'reference' => $w->reference]);
            }
        }

        return self::SUCCESS;
    }
}