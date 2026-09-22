<?php

namespace App\Console\Commands;

use App\Models\GiftcardOrder;
use App\Services\GiftcardSettlement;
use Illuminate\Console\Command;

class GiftcardsReconcile extends Command
{
    protected $signature = 'giftcards:reconcile';
    protected $description = 'Re-check unsettled gift card orders against Prestmit (safety net for missed webhooks).';

    public function handle(GiftcardSettlement $settle): int
    {
        $orders = GiftcardOrder::whereNull('settled_at')
            ->where('created_at', '<=', now()->subMinutes(2))
            ->orderBy('id')->limit(50)->get();

        foreach ($orders as $o) {
            $r = $settle->syncOrder($o);
            $this->line("#{$o->id} {$o->reference}: " . (($r['ok'] ?? false) ? ($r['status'] ?? 'ok') : ($r['error'] ?? 'error')));
        }
        return self::SUCCESS;
    }
}