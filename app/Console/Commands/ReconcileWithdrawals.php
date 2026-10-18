<?php

namespace App\Console\Commands;

use App\Models\GiftcardWithdrawal;
use App\Services\Payments\FlutterwavePayoutService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Safety net for missed webhooks: asks Flutterwave about every withdrawal that has been
 * 'processing' for more than 2 minutes. It never sends money — it only reads status.
 */
class ReconcileWithdrawals extends Command
{
    protected $signature = 'giftcards:reconcile-withdrawals {--limit=50}';
    protected $description = 'Sync in-flight gift-card withdrawals with Flutterwave';

    public function handle(FlutterwavePayoutService $payouts): int
    {
        $rows = GiftcardWithdrawal::where('status', 'processing')
            ->where('provider', 'flutterwave')
            ->where('updated_at', '<', now()->subMinutes(2))
            ->orderBy('id')->limit((int) $this->option('limit'))->get();

        foreach ($rows as $w) {
            $res = $payouts->sync($w);
            $this->line("#{$w->id} {$w->reference}: {$res['status']}");

            if ($w->fresh()->status === 'processing' && $w->updated_at->lt(now()->subHour())) {
                Log::warning('Withdrawal has been processing for over an hour', ['withdrawal' => $w->id, 'reference' => $w->reference]);
            }
        }

        return self::SUCCESS;
    }
}