<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\User;

class FlutterwaveWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('🔔 Flutterwave Webhook Hit', [
            'headers'     => $request->headers->all(),
            'raw_body'    => $request->getContent(),
            'parsed_body' => $request->all(),
        ]);

        // ── Verify webhook signature ──────────────────────────────────
        $signature  = $request->header('verif-hash');
        $secretHash = env('FLW_SECRET_HASH');

        if (!$secretHash) {
            Log::error('FLW_SECRET_HASH not set in .env');
            return response('Server Misconfigured', 500);
        }

        if (!$signature || $signature !== $secretHash) {
            Log::warning('⚠️ Invalid or Missing Webhook Signature', [
                'received' => $signature,
                'expected' => $secretHash,
            ]);
            return response('Invalid signature', 401);
        }

        Log::info('✅ Webhook Signature Verified');

        $payload = $request->all();

        if (($payload['event'] ?? null) === 'charge.completed') {
            return $this->handleChargeCompleted($payload);
        }

        Log::info('Webhook ignored: Unsupported event', [
            'event' => $payload['event'] ?? null,
        ]);

        return response('Ignored', 200);
    }

    /**
     * Handle successful charge completion.
     */
    protected function handleChargeCompleted(array $payload)
    {
        $data = $payload['data'] ?? [];

        if (($data['status'] ?? '') !== 'successful') {
            Log::info('Payment not successful', ['status' => $data['status'] ?? 'unknown']);
            return response('Payment not successful', 200);
        }

        $txRef         = $data['tx_ref']  ?? null;
        $amount        = (float) ($data['amount'] ?? 0);
        $transactionId = $data['id']      ?? null;

        if (!$txRef || !$transactionId) {
            Log::error('Missing required fields in webhook', $data);
            return response('Invalid data', 400);
        }

        // ── Variables we need after the transaction ───────────────────
        $creditedUserId    = null;
        $creditedAmount    = 0.0;
        $creditedTxnId     = null;

        try {
            DB::beginTransaction();

            // Guard: duplicate transaction
            if (Transaction::where('reference', $txRef)->exists()) {
                Log::warning('⚠️ Duplicate transaction ignored', ['tx_ref' => $txRef]);
                DB::commit();
                return response('Duplicate transaction', 200);
            }

            // Resolve user
            $userId = $this->extractUserIdFromRef($txRef, $data);

            if (!$userId) {
                Log::error('Could not extract user_id from transaction', [
                    'tx_ref' => $txRef,
                    'data'   => $data,
                ]);
                DB::rollBack();
                return response('User not found', 404);
            }

            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

            if (!$wallet) {
                Log::error('Wallet not found for user', ['user_id' => $userId]);
                DB::rollBack();
                return response('Wallet not found', 404);
            }

            $oldBalance = $wallet->balance;
            $wallet->increment('balance', $amount);

            $transaction = Transaction::create([
                'reference'   => $txRef,
                'user_id'     => $userId,
                'type'        => 'credit',
                'channel'     => 'Flutterwave',
                'provider'    => 'Flutterwave Checkout',
                'amount'      => $amount,
                'status'      => 'success',
                'description' => 'Wallet funding via Flutterwave — ₦' . number_format($amount, 2),
                'response'    => json_encode($data),
            ]);

            // Capture for use after commit
            $creditedUserId = $userId;
            $creditedAmount = $amount;
            $creditedTxnId  = $transaction->id;

            DB::commit();

            Log::info('💰 Payment Processed Successfully', [
                'tx_ref'      => $txRef,
                'user_id'     => $userId,
                'amount'      => $amount,
                'old_balance' => $oldBalance,
                'new_balance' => $wallet->fresh()->balance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Payment Processing Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Internal server error', 500);
        }

        // ── Referral commission ───────────────────────────────────────
        // Runs OUTSIDE the DB transaction so a commission failure can
        // never roll back or block the confirmed deposit.
        if ($creditedUserId && $creditedAmount > 0) {
            try {
                $user = User::find($creditedUserId);

                if ($user) {
                    app(ReferralService::class)->creditCommissionOnDeposit(
                        $user,
                        $creditedAmount,
                        $creditedTxnId,
                        'flutterwave_deposit'
                    );
                }
            } catch (\Exception $e) {
                // Log but never let this block the 200 response to Flutterwave
                Log::error('Flutterwave referral commission failed', [
                    'user_id' => $creditedUserId,
                    'amount'  => $creditedAmount,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return response('OK', 200);
    }

    /**
     * Extract user ID from transaction reference or metadata.
     */
    protected function extractUserIdFromRef(string $txRef, array $data): ?int
    {
        // Format: FLW_{user_id}_{timestamp}_{unique}
        if (preg_match('/^FLW_(\d+)_/', $txRef, $matches)) {
            return (int) $matches[1];
        }

        if (isset($data['meta']['user_id'])) {
            return (int) $data['meta']['user_id'];
        }

        if (isset($data['customer']['email'])) {
            $user = User::where('email', $data['customer']['email'])->first();
            return $user?->id;
        }

        return null;
    }
}