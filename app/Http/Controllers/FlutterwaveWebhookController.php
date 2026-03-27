<?php

namespace App\Http\Controllers;

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
            'headers' => $request->headers->all(),
            'raw_body' => $request->getContent(),
            'parsed_body' => $request->all()
        ]);

        // Verify webhook signature
        $signature = $request->header('verif-hash');
        $secretHash = env('FLW_SECRET_HASH');

        if (!$secretHash) {
            Log::error('FLW_SECRET_HASH not set in .env');
            return response('Server Misconfigured', 500);
        }

        if (!$signature || $signature !== $secretHash) {
            Log::warning('⚠️ Invalid or Missing Webhook Signature', [
                'received' => $signature,
                'expected' => $secretHash
            ]);
            return response('Invalid signature', 401);
        }

        Log::info('✅ Webhook Signature Verified');

        $payload = $request->all();

        // Handle charge.completed event (standard checkout)
        if (($payload['event'] ?? null) === 'charge.completed') {
            return $this->handleChargeCompleted($payload);
        }

        Log::info('Webhook ignored: Unsupported event', [
            'event' => $payload['event'] ?? null
        ]);

        return response('Ignored', 200);
    }

    /**
     * Handle successful charge completion
     */
    protected function handleChargeCompleted($payload)
    {
        $data = $payload['data'] ?? [];

        // Verify payment was successful
        if (($data['status'] ?? '') !== 'successful') {
            Log::info('Payment not successful', ['status' => $data['status'] ?? 'unknown']);
            return response('Payment not successful', 200);
        }

        $txRef = $data['tx_ref'] ?? null;
        $amount = $data['amount'] ?? 0;
        $transactionId = $data['id'] ?? null;

        if (!$txRef || !$transactionId) {
            Log::error('Missing required fields in webhook', $data);
            return response('Invalid data', 400);
        }

        try {
            DB::beginTransaction();

            // Check for duplicate transaction
            $existingTransaction = Transaction::where('reference', $txRef)->first();
            if ($existingTransaction) {
                Log::warning('⚠️ Duplicate transaction ignored', ['tx_ref' => $txRef]);
                DB::commit();
                return response('Duplicate transaction', 200);
            }

            // Extract user_id from tx_ref (format: FLW_{user_id}_{timestamp}_{unique})
            $userId = $this->extractUserIdFromRef($txRef, $data);

            if (!$userId) {
                Log::error('Could not extract user_id from transaction', [
                    'tx_ref' => $txRef,
                    'data' => $data
                ]);
                DB::rollBack();
                return response('User not found', 404);
            }

            $wallet = Wallet::where('user_id', $userId)->first();

            if (!$wallet) {
                Log::error('Wallet not found for user', ['user_id' => $userId]);
                DB::rollBack();
                return response('Wallet not found', 404);
            }

            // Credit wallet
            $oldBalance = $wallet->balance;
            $wallet->increment('balance', $amount);

            // Create transaction record
            Transaction::create([
                'reference' => $txRef,
                'user_id' => $userId,
                'type' => 'credit',
                'channel' => 'Flutterwave',
                'provider' => 'Flutterwave Checkout',
                'amount' => $amount,
                'status' => 'success',
                'description' => "Wallet funding via Flutterwave - ₦" . number_format($amount, 2),
                'response' => json_encode($data)
            ]);

            DB::commit();

            Log::info('💰 Payment Processed Successfully', [
                'tx_ref' => $txRef,
                'user_id' => $userId,
                'amount' => $amount,
                'old_balance' => $oldBalance,
                'new_balance' => $wallet->balance
            ]);

            return response('OK', 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Payment Processing Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Internal server error', 500);
        }
    }

    /**
     * Extract user ID from transaction reference or metadata
     */
    protected function extractUserIdFromRef($txRef, $data)
    {
        // Try to extract from tx_ref format: FLW_{user_id}_{timestamp}_{unique}
        if (preg_match('/^FLW_(\d+)_/', $txRef, $matches)) {
            return (int) $matches[1];
        }

        // Try to get from meta data
        if (isset($data['meta']['user_id'])) {
            return (int) $data['meta']['user_id'];
        }

        // Try to find user by email
        if (isset($data['customer']['email'])) {
            $user = User::where('email', $data['customer']['email'])->first();
            return $user ? $user->id : null;
        }

        return null;
    }
}