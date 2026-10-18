<?php

namespace App\Services\Payments;

use App\Models\DedicatedAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Flutterwave\FlutterwaveClient;
use App\Services\ReferralService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Credits wallets for bank transfers received on a user's Flutterwave dedicated account.
 *
 * The webhook body is NEVER trusted for money: we re-fetch the transaction from Flutterwave
 * and check id, status, currency and amount before touching a wallet. The credit itself is
 * idempotent (unique reference + row lock), so duplicate/replayed webhooks are harmless.
 */
class FlutterwaveInflowProcessor
{
    public function __construct(private FlutterwaveClient $flw)
    {
    }

    /**
     * @param  array $hook  The `data` object of a `charge.completed` webhook.
     * @return array{status:string}  credited|held|duplicate|unmatched|rejected|ignored
     * @throws \RuntimeException when Flutterwave cannot be reached (webhook should be retried)
     */
    public function handleChargeCompleted(array $hook): array
    {
        $id = $hook['id'] ?? null;
        if (!$id || strtolower((string) ($hook['status'] ?? '')) !== 'successful') {
            return ['status' => 'ignored'];
        }

        // 1) Authoritative re-check with Flutterwave.
        $v = $this->flw->verifyTransaction($id);
        if (!$v['ok'] || !is_array($v['data'])) {
            if ($v['http'] === 404) {
                Log::warning('Flutterwave inflow: transaction not found on verify', ['id' => $id]);
                return ['status' => 'rejected'];
            }
            throw new \RuntimeException('Flutterwave verification unavailable.');
        }

        $d = $v['data'];
        $amount = round((float) ($d['amount'] ?? 0), 2);

        if ((string) ($d['id'] ?? '') !== (string) $id
            || strtolower((string) ($d['status'] ?? '')) !== 'successful'
            || strtoupper((string) ($d['currency'] ?? '')) !== 'NGN'
            || $amount <= 0) {
            Log::warning('Flutterwave inflow: verification mismatch', ['id' => $id]);
            return ['status' => 'rejected'];
        }

        // 2) Which user does this belong to?
        $account = $this->matchAccount($d);
        if (!$account) {
            Log::critical('Flutterwave inflow: money received but no dedicated account matched', [
                'id' => $id, 'flw_ref' => $d['flw_ref'] ?? null, 'tx_ref' => $d['tx_ref'] ?? null, 'amount' => $amount,
            ]);
            return ['status' => 'unmatched'];
        }

        // 3) Credit (or hold, if an admin has deactivated the account) — exactly once.
        $reference = 'FLWVA_' . $id;
        $isActive  = strtolower((string) $account->status) === 'active';

        try {
            $result = DB::transaction(function () use ($account, $reference, $amount, $d, $isActive) {
                if (Transaction::where('reference', $reference)->lockForUpdate()->exists()) {
                    return ['status' => 'duplicate'];
                }

                $wallet = Wallet::where('user_id', $account->user_id)->lockForUpdate()->first()
                    ?? Wallet::create(['user_id' => $account->user_id, 'balance' => 0]);

                if ($isActive) {
                    $wallet->increment('balance', $amount);
                    $wallet->refresh();
                }

                $txn = Transaction::create([
                    'reference'     => $reference,
                    'user_id'       => $account->user_id,
                    'type'          => 'credit',
                    'channel'       => 'Flutterwave',
                    'provider'      => 'Flutterwave Dedicated Account',
                    'amount'        => $amount,
                    'status'        => $isActive ? 'success' : 'pending',
                    'description'   => $isActive
                        ? 'Wallet funding via dedicated account — ₦' . number_format($amount, 2)
                        : 'HELD: deposit to a deactivated dedicated account — needs admin review',
                    'response'      => json_encode([
                        'flw_id'         => $d['id'] ?? null,
                        'flw_ref'        => $d['flw_ref'] ?? null,
                        'tx_ref'         => $d['tx_ref'] ?? null,
                        'amount'         => $amount,
                        'charged_amount' => $d['charged_amount'] ?? null,
                        'app_fee'        => $d['app_fee'] ?? null,
                        'payment_type'   => $d['payment_type'] ?? null,
                        'verified_at'    => now()->toDateTimeString(),
                    ]),
                    'balance_after' => $wallet->balance,
                ]);

                return [
                    'status' => $isActive ? 'credited' : 'held',
                    'txn_id' => $txn->id,
                ];
            });
        } catch (QueryException $e) {
            // Unique-reference race with a concurrent identical webhook.
            if ((string) $e->getCode() === '23000') {
                return ['status' => 'duplicate'];
            }
            throw $e;
        }

        if ($result['status'] === 'held') {
            Log::critical('Flutterwave inflow: deposit HELD (dedicated account not active)', [
                'user_id' => $account->user_id, 'reference' => $reference, 'amount' => $amount,
            ]);
        }

        // 4) Referral commission — outside the DB transaction so it can never block a confirmed deposit.
        if ($result['status'] === 'credited') {
            try {
                $user = User::find($account->user_id);
                if ($user) {
                    app(ReferralService::class)->creditCommissionOnDeposit(
                        $user, $amount, $result['txn_id'], 'flutterwave_deposit'
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Flutterwave referral commission failed', [
                    'user_id' => $account->user_id, 'error' => $e->getMessage(),
                ]);
            }
        }

        return ['status' => $result['status']];
    }

    /**
     * Primary match: the `tx_ref` we set when the account was created (Flutterwave echoes it back).
     * Fallback: the verified customer e-mail, accepted ONLY if it identifies exactly one active account.
     */
    private function matchAccount(array $verified): ?DedicatedAccount
    {
        $txRef = (string) ($verified['tx_ref'] ?? '');

        if ($txRef !== '') {
            $account = DedicatedAccount::where('provider', 'flutterwave')
                ->where('account_reference', $txRef)->first();
            if ($account) {
                return $account;
            }
        }

        $email = Str::lower(trim((string) ($verified['customer']['email'] ?? '')));
        if ($email === '') {
            return null;
        }

        $userIds = User::whereRaw('LOWER(email) = ?', [$email])->pluck('id');
        $matches = DedicatedAccount::where('provider', 'flutterwave')
            ->where('status', 'active')->whereIn('user_id', $userIds)->get();

        if ($matches->count() === 1) {
            Log::warning('Flutterwave inflow matched by customer e-mail fallback', ['tx_ref' => $txRef]);
            return $matches->first();
        }

        return null;
    }
}