<?php

namespace App\Services;

use App\Helpers\PrestmitHelper;
use App\Models\GiftcardOrder;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The ONLY place where money moves for gift cards after the initial debit.
 * Every mutation is inside a transaction that locks the order row and bails out
 * if `settled_at` is set, so webhooks / polling / cron can run in any order, any number of times.
 */
class GiftcardSettlement
{
    public function __construct(private PrestmitHelper $prestmit)
    {
    }

    /** Re-fetch the trade from Prestmit (source of truth) and apply it. */
    public function syncOrder(GiftcardOrder $order): array
    {
        $order->refresh();
        if ($order->settled_at) {
            return ['ok' => true, 'status' => $order->status];
        }

        $ref   = $order->provider_reference;
        $path  = $order->type === 'buy' ? 'giftcard-trade/buy/history' : 'giftcard-trade/sell/history';
        $query = $ref ? ['referenceOrID' => $ref] : ['uniqueIdentifier' => $order->reference];

        $r = $this->prestmit->request('GET', $path, $query);
        $order->forceFill(['last_synced_at' => now()])->save();
        if (!$r['ok']) {
            return ['ok' => false, 'error' => $r['error']];
        }

        $trade = $this->pickTrade($r['data'], $ref, $order->reference);
        if (!$trade) {
            // Never created on Prestmit's side — only give up after a long grace period.
            if (!$ref && $order->created_at->lt(now()->subMinutes(60))) {
                return $order->type === 'buy'
                    ? $this->failBuy($order, 'Order was not created with the provider.')
                    : $this->rejectSell($order, 'Submission was not received by the provider.', 'failed');
            }
            return ['ok' => false, 'error' => 'not_found'];
        }

        return $this->applyStatus($order, strtoupper((string) ($trade['status'] ?? 'PENDING')), $trade);
    }

    public function applyStatus(GiftcardOrder $order, string $status, array $trade = []): array
    {
        $order->refresh();
        if ($order->settled_at) {
            return ['ok' => true, 'status' => $order->status];
        }
        $updates = ['provider_status' => $status, 'last_synced_at' => now()];
        if (!$order->provider_reference && !empty($trade['reference'])) {
            $updates['provider_reference'] = $trade['reference'];
        }
        $order->forceFill($updates)->save();

        return $order->type === 'buy' ? $this->applyBuy($order, $status, $trade) : $this->applySell($order, $status, $trade);
    }

    // ── BUY ──────────────────────────────────────────────────────────────

    private function applyBuy(GiftcardOrder $order, string $status, array $trade): array
    {
        if ($status === 'COMPLETED') {
            $codes = $this->collectCodes($order, $trade);
            if (empty($codes)) {
                $order->forceFill(['status' => 'processing', 'needs_review' => true])->save();
                return ['ok' => false, 'error' => 'codes_pending'];
            }
            return DB::transaction(function () use ($order, $codes) {
                $o = GiftcardOrder::lockForUpdate()->find($order->id);
                if ($o->settled_at) {
                    return ['ok' => true, 'status' => $o->status];
                }
                $o->forceFill([
                    'codes' => $codes, 'status' => 'completed', 'needs_review' => false, 'settled_at' => now(),
                ])->save();
                Transaction::where('reference', $o->reference)->update(['status' => 'success']);
                return ['ok' => true, 'status' => 'completed'];
            });
        }

        if (in_array($status, ['FAILED', 'REFUNDED', 'REJECTED', 'CANCELLED', 'CANCELED'], true)) {
            return $this->failBuy($order, $trade['rejectReason'] ?? $trade['rejectionReason'] ?? 'Purchase could not be completed.', $status === 'REFUNDED' ? 'refunded' : 'failed');
        }

        $order->forceFill(['status' => 'processing'])->save();
        return ['ok' => true, 'status' => 'processing'];
    }

    /** Refund the user's wallet exactly once and close the order. */
    public function failBuy(GiftcardOrder $order, string $reason, string $finalStatus = 'failed'): array
    {
        return DB::transaction(function () use ($order, $reason, $finalStatus) {
            $o = GiftcardOrder::lockForUpdate()->find($order->id);
            if ($o->settled_at) {
                return ['ok' => true, 'status' => $o->status];
            }

            if ($o->wallet_debited) {
                $wallet = Wallet::where('user_id', $o->user_id)->lockForUpdate()->first();
                if ($wallet) {
                    $wallet->balance = round((float) $wallet->balance + (float) $o->total_ngn, 2);
                    $wallet->save();
                    Transaction::create([
                        'reference'     => 'REF_' . strtoupper(Str::random(10)),
                        'user_id'       => $o->user_id,
                        'type'          => 'credit',
                        'channel'       => 'Refund',
                        'provider'      => 'Giftcard',
                        'amount'        => $o->total_ngn,
                        'status'        => 'success',
                        'description'   => 'Gift card purchase refund — ' . $o->title,
                        'balance_after' => $wallet->balance,
                    ]);
                } else {
                    Log::critical('Giftcard refund: wallet missing', ['order' => $o->id]);
                }
            }

            $o->forceFill([
                'status' => $finalStatus, 'reject_reason' => Str::limit(strip_tags($reason), 480),
                'needs_review' => false, 'settled_at' => now(),
            ])->save();
            Transaction::where('reference', $o->reference)->update(['status' => 'failed']);

            return ['ok' => true, 'status' => $finalStatus];
        });
    }

    private function collectCodes(GiftcardOrder $order, array $trade): array
    {
        $codes = [];
        if ($order->provider_reference) {
            $r = $this->prestmit->request('GET', 'giftcard-trade/buy/fetch-codes/' . $order->provider_reference);
            if ($r['ok']) {
                $list = isset($r['data'][0]) ? $r['data'] : ($r['data']['data'] ?? []);
                foreach ($list as $c) {
                    if (!is_array($c)) {
                        continue;
                    }
                    $codes[] = [
                        'cardNumber' => (string) ($c['cardNumber'] ?? ''),
                        'pinCode'    => (string) ($c['pinCode'] ?? ''),
                        'claimUrl'   => (string) ($c['claimUrl'] ?? ''),
                        'expireDate' => (string) ($c['expireDate'] ?? ''),
                    ];
                }
            }
        }
        if (empty($codes) && !empty($trade['cardCode'])) {
            $codes[] = ['cardNumber' => (string) $trade['cardCode'], 'pinCode' => (string) ($trade['cardPin'] ?? ''), 'claimUrl' => '', 'expireDate' => ''];
        }
        return array_values(array_filter($codes, fn ($c) => $c['cardNumber'] !== '' || $c['claimUrl'] !== '' || $c['pinCode'] !== ''));
    }

    // ── SELL ─────────────────────────────────────────────────────────────

    private function applySell(GiftcardOrder $order, string $status, array $trade): array
    {
        if ($status === 'COMPLETED') {
            return DB::transaction(function () use ($order, $trade) {
                $o = GiftcardOrder::lockForUpdate()->find($order->id);
                if ($o->settled_at) {
                    return ['ok' => true, 'status' => $o->status];
                }

                $quoted     = (float) $o->total_ngn;
                $actual     = (float) ($trade['totalAmount'] ?? $o->provider_amount_ngn ?? 0);
                $commission = (float) ($o->pricing['commission_percent'] ?? 0);
                $derived    = $actual > 0 ? floor($actual * (1 - $commission / 100) * 100) / 100 : $quoted;
                $payout     = round(min($quoted, $derived > 0 ? $derived : $quoted), 2);

                if ($payout > 0) {
                    DB::table('users')->where('id', $o->user_id)->lockForUpdate()->first();
                    DB::table('users')->where('id', $o->user_id)->increment('withdrawable_balance', $payout);
                }

                $o->forceFill([
                    'status' => 'completed', 'payout_ngn' => $payout,
                    'provider_amount_ngn' => $actual ?: $o->provider_amount_ngn,
                    'needs_review' => false, 'settled_at' => now(),
                ])->save();
                return ['ok' => true, 'status' => 'completed'];
            });
        }

        if (in_array($status, ['REJECTED', 'FAILED', 'CANCELLED', 'CANCELED'], true)) {
            return $this->rejectSell($order, $trade['rejectionReason'] ?? $trade['rejectReason'] ?? 'Card was not accepted.', 'rejected');
        }

        $order->forceFill(['status' => 'pending'])->save();
        return ['ok' => true, 'status' => 'pending'];
    }

    public function rejectSell(GiftcardOrder $order, string $reason, string $finalStatus = 'rejected'): array
    {
        return DB::transaction(function () use ($order, $reason, $finalStatus) {
            $o = GiftcardOrder::lockForUpdate()->find($order->id);
            if ($o->settled_at) {
                return ['ok' => true, 'status' => $o->status];
            }
            $o->forceFill([
                'status' => $finalStatus, 'reject_reason' => Str::limit(strip_tags($reason), 480),
                'needs_review' => false, 'settled_at' => now(),
            ])->save();
            return ['ok' => true, 'status' => $finalStatus];
        });
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function pickTrade(array $body, ?string $ref, string $unique): ?array
    {
        $items = $body['data'] ?? $body['trades'] ?? $body['history'] ?? $body;
        if (isset($items['data']) && is_array($items['data'])) {
            $items = $items['data'];
        }
        if (isset($items['reference'])) {
            $items = [$items];
        }
        if (!is_array($items)) {
            return null;
        }
        $items = array_values(array_filter($items, 'is_array'));
        foreach ($items as $t) {
            if (($ref && ($t['reference'] ?? null) === $ref) || (($t['uniqueIdentifier'] ?? null) === $unique)) {
                return $t;
            }
        }
        return (!$ref && count($items) === 1) ? $items[0] : null;
    }
}