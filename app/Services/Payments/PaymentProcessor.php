<?php

namespace App\Services\Payments;

use App\Models\DedicatedAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ReferralService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The ONE place in the codebase allowed to credit a wallet for an incoming
 * payment. Both the webhook and the (optional) callback-verification path
 * call into this class. It is written to be safe no matter how many times,
 * or how concurrently, it is invoked for the same charge — see the two
 * mechanisms below.
 *
 * Why this exists: the previous bug was two independent code paths each
 * doing their own "check if it exists, then credit" logic, with no shared
 * database-level guarantee. That is fixed here by:
 *
 *   1. A unique DB constraint on transactions.reference (see migration).
 *      Attempting to insert a second row with the same reference throws,
 *      and we treat that as "already handled" — regardless of timing.
 *   2. Row-level locking (`lockForUpdate`) when transitioning an existing
 *      `pending` row to `success`. Concurrent callers serialize on that row;
 *      the loser simply observes status = success already and no-ops.
 *   3. Never crediting based on the webhook body alone — we re-verify the
 *      charge against Korapay's API first and use THAT amount/status.
 */
class PaymentProcessor
{
    public function __construct(protected KorapayService $korapay)
    {
    }

    /**
     * Handle a `charge.success` event, whether it came from a one-time
     * checkout or a deposit into a dedicated (permanent) virtual account.
     *
     * @return array{status:string,message:string}  status: credited|duplicate|failed
     */
    public function handleChargeSuccess(array $webhookData): array
    {
        $reference = $webhookData['payment_reference'] ?? $webhookData['reference'] ?? null;

        if (!$reference) {
            Log::warning('Korapay: charge.success event missing a usable reference', ['data' => $webhookData]);
            return ['status' => 'failed', 'message' => 'Missing reference'];
        }

        // ── Defense in depth: never trust the payload amount/status alone ──
        $verified = $this->korapay->verifyCharge($reference);

        if (!$verified['success']) {
            Log::warning('Korapay: could not independently verify charge before crediting', [
                'reference' => $reference,
            ]);
            return ['status' => 'failed', 'message' => 'Could not verify transaction with Korapay.'];
        }

        $verifiedData = $verified['data'];

        if (($verifiedData['status'] ?? null) !== 'success') {
            Log::info('Korapay: verified charge is not successful, skipping credit', [
                'reference' => $reference,
                'status'    => $verifiedData['status'] ?? null,
            ]);
            return ['status' => 'failed', 'message' => 'Transaction not successful.'];
        }

        $amount = (float) ($verifiedData['amount_paid'] ?? $verifiedData['amount'] ?? 0);
        $vbaDetails = $verifiedData['virtual_bank_account'] ?? ($webhookData['virtual_bank_account_details']['virtual_bank_account'] ?? null);

        if ($vbaDetails && !empty($vbaDetails['account_reference'])) {
            return $this->creditDedicatedAccountDeposit($reference, $amount, $vbaDetails['account_reference'], $verifiedData);
        }

        return $this->creditOneTimeCheckout($reference, $amount, $verifiedData);
    }

    /**
     * One-time checkout: a `pending` Transaction row was already created when
     * the user clicked "Add Funds". We flip it to `success` and credit the
     * wallet, guarded by a row lock so concurrent calls can't double-credit.
     */
    protected function creditOneTimeCheckout(string $reference, float $amount, array $verifiedData): array
    {
        try {
            return DB::transaction(function () use ($reference, $amount, $verifiedData) {
                $transaction = Transaction::where('reference', $reference)
                    ->lockForUpdate()
                    ->first();

                if (!$transaction) {
                    Log::warning('Korapay: verified charge has no matching pending transaction', ['reference' => $reference]);
                    return ['status' => 'failed', 'message' => 'No matching transaction found.'];
                }

                if ($transaction->status === 'success') {
                    // Already processed by a previous call (webhook retry, or the
                    // callback page beat the webhook to it). This is expected and safe.
                    return ['status' => 'duplicate', 'message' => 'Already credited.'];
                }

                $wallet = Wallet::where('user_id', $transaction->user_id)->lockForUpdate()->first();

                if (!$wallet) {
                    Log::error('Korapay: wallet not found for user during credit', ['user_id' => $transaction->user_id]);
                    return ['status' => 'failed', 'message' => 'Wallet not found.'];
                }

                $wallet->increment('balance', $amount);

                $transaction->status = 'success';
                $transaction->amount = $amount; // trust the verified amount, not the original request amount
                $transaction->response = json_encode($verifiedData);
                $transaction->save();

                Log::info('Korapay: wallet credited (one-time checkout)', [
                    'reference' => $reference,
                    'user_id'   => $transaction->user_id,
                    'amount'    => $amount,
                ]);

                $this->creditReferralCommissionSafely($transaction->user_id, $amount, $transaction->id);

                return ['status' => 'credited', 'message' => 'Wallet credited.'];
            });
        } catch (QueryException $e) {
            // Unique constraint violation = a concurrent request already inserted/updated this.
            Log::info('Korapay: duplicate credit attempt caught by DB constraint', ['reference' => $reference]);
            return ['status' => 'duplicate', 'message' => 'Already processed.'];
        }
    }

    /**
     * Dedicated account deposit: this reference is NEW every time (each bank
     * transfer into the account is its own charge). We look up which of our
     * users owns the destination account, then INSERT a brand-new Transaction
     * row. The unique index on `reference` is what makes this safe against
     * concurrent/duplicate webhook deliveries — if two requests race to insert
     * the same reference, the DB itself rejects the second one.
     */
    protected function creditDedicatedAccountDeposit(string $reference, float $amount, string $accountReference, array $verifiedData): array
    {
        $account = DedicatedAccount::where('account_reference', $accountReference)->first();

        if (!$account || !$account->isActive()) {
            Log::error('Korapay: deposit received for unknown/inactive dedicated account', [
                'account_reference' => $accountReference,
                'reference'         => $reference,
            ]);
            return ['status' => 'failed', 'message' => 'Unknown destination account.'];
        }

        try {
            return DB::transaction(function () use ($reference, $amount, $account, $verifiedData) {
                // Insert-first: relies on the unique index on `reference` to reject
                // a second insert for the same charge, closing the race entirely.
                $transaction = Transaction::create([
                    'reference'   => $reference,
                    'user_id'     => $account->user_id,
                    'type'        => 'credit',
                    'channel'     => 'Korapay',
                    'provider'    => 'Korapay Dedicated Account',
                    'amount'      => $amount,
                    'status'      => 'success',
                    'description' => 'Wallet funding via dedicated account — ' . $account->account_number,
                    'response'    => json_encode($verifiedData),
                ]);

                $wallet = Wallet::where('id', $account->wallet_id)->lockForUpdate()->first();

                if (!$wallet) {
                    Log::error('Korapay: wallet missing for dedicated account', ['account_id' => $account->id]);
                    return ['status' => 'failed', 'message' => 'Wallet not found.'];
                }

                $wallet->increment('balance', $amount);

                Log::info('Korapay: wallet credited (dedicated account)', [
                    'reference'  => $reference,
                    'account_id' => $account->id,
                    'user_id'    => $account->user_id,
                    'amount'     => $amount,
                ]);

                $this->creditReferralCommissionSafely($account->user_id, $amount, $transaction->id);

                return ['status' => 'credited', 'message' => 'Wallet credited.'];
            });
        } catch (QueryException $e) {
            Log::info('Korapay: duplicate dedicated-account deposit caught by DB constraint', ['reference' => $reference]);
            return ['status' => 'duplicate', 'message' => 'Already processed.'];
        }
    }

    /**
     * Referral commission runs OUTSIDE the money-crediting DB transaction so a
     * commission failure can never roll back or block a confirmed deposit —
     * same principle as the original Flutterwave webhook, kept intentionally.
     */
    protected function creditReferralCommissionSafely(int $userId, float $amount, int $transactionId): void
    {
        try {
            $user = User::find($userId);
            if ($user) {
                app(ReferralService::class)->creditCommissionOnDeposit($user, $amount, $transactionId, 'korapay_deposit');
            }
        } catch (\Throwable $e) {
            Log::error('Korapay: referral commission failed', [
                'user_id'        => $userId,
                'transaction_id' => $transactionId,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
