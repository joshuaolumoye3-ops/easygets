<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;

class FlutterwaveWebhookController extends Controller
{
    /**
     * Handle incoming Flutterwave webhook
     */
    public function handle(Request $request)
    {
        try {
            // Step 1: Verify webhook signature
            if (!$this->verifyWebhookSignature($request)) {
                Log::warning('Invalid webhook signature', [
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all()
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $payload = $request->all();
            $data = $payload['data'] ?? null;

            if (!$data) {
                Log::error('Webhook payload missing data field', ['payload' => $payload]);
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            Log::info('Flutterwave Webhook Received', [
                'event' => $payload['event'] ?? 'unknown',
                'tx_ref' => $data['tx_ref'] ?? 'N/A',
                'status' => $data['status'] ?? 'N/A',
                'amount' => $data['amount'] ?? 'N/A',
                'account_number' => $data['account_number'] ?? 'N/A',
                'customer_email' => $data['customer']['email'] ?? 'N/A'
            ]);

            // Step 2: Process only successful transactions
            if (!isset($data['status']) || strtolower($data['status']) !== 'successful') {
                Log::info('Webhook ignored - non-successful status', [
                    'status' => $data['status'] ?? 'unknown'
                ]);
                return response()->json(['message' => 'Transaction not successful'], 200);
            }

            // Step 3: Process the payment
            $result = $this->processPayment($data);

            if ($result['success']) {
                return response()->json(['message' => 'Webhook processed successfully'], 200);
            }

            return response()->json(['error' => $result['message']], $result['code']);

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('verif-hash');
        $secretHash = config('services.flutterwave.secret_hash') ?? env('FLW_SECRET_HASH');

        if (!$signature || !$secretHash) {
            Log::warning('Missing signature or secret hash');
            return false;
        }

        return hash_equals($secretHash, $signature);
    }

    /**
     * Process payment and credit wallet
     */
    private function processPayment(array $data): array
    {
        // Validate required fields
        $requiredFields = ['tx_ref', 'amount'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                Log::error("Missing required field: {$field}", ['data' => $data]);
                return [
                    'success' => false,
                    'message' => "Missing required field: {$field}",
                    'code' => 400
                ];
            }
        }

        $txRef = $data['tx_ref'];
        $flwRef = $data['flw_ref'] ?? null;
        $amount = floatval($data['amount']);
        $accountNumber = $data['account_number'] ?? null;

        // Use database transaction for atomicity
        return DB::transaction(function () use ($txRef, $flwRef, $amount, $accountNumber, $data) {
            // Check for duplicate transaction using tx_ref
            $existingTransaction = Transaction::where('reference', $txRef)->first();
            if ($existingTransaction) {
                Log::info('Duplicate transaction prevented', [
                    'reference' => $txRef,
                    'existing_id' => $existingTransaction->id
                ]);
                return [
                    'success' => true,
                    'message' => 'Transaction already processed',
                    'code' => 200
                ];
            }

            // Also check flw_ref to prevent duplicates
            if ($flwRef) {
                $existingFlwRef = Transaction::where('response->flw_ref', $flwRef)->first();
                if ($existingFlwRef) {
                    Log::info('Duplicate FLW reference prevented', [
                        'flw_ref' => $flwRef,
                        'existing_id' => $existingFlwRef->id
                    ]);
                    return [
                        'success' => true,
                        'message' => 'Transaction already processed',
                        'code' => 200
                    ];
                }
            }

            // Extract user identifier from tx_ref or email
            $userId = $this->extractUserIdFromReference($txRef, $data);

            if (!$userId) {
                Log::error('Could not extract user ID from transaction', [
                    'tx_ref' => $txRef,
                    'customer_email' => $data['customer']['email'] ?? 'N/A',
                    'data' => $data
                ]);
                return [
                    'success' => false,
                    'message' => 'Could not identify user',
                    'code' => 404
                ];
            }

            // Find user and wallet
            $user = User::with('wallet')->find($userId);

            if (!$user) {
                Log::error('User not found', [
                    'user_id' => $userId,
                    'tx_ref' => $txRef
                ]);
                return [
                    'success' => false,
                    'message' => 'User not found',
                    'code' => 404
                ];
            }

            $wallet = $user->wallet;

            if (!$wallet) {
                Log::error('Wallet not found for user', [
                    'user_id' => $userId,
                    'tx_ref' => $txRef
                ]);
                return [
                    'success' => false,
                    'message' => 'Wallet not found',
                    'code' => 404
                ];
            }

            // Record transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'reference' => $txRef,
                'status' => 'success',
                'type' => 'payment',
                'channel' => 'Flutterwave',
                'provider' => 'Virtual Account',
                'recipient' => $accountNumber ?? 'N/A',
                'response' => json_encode($data),
            ]);

            // Credit wallet
            $oldBalance = $wallet->balance;
            $wallet->balance += $amount;
            $wallet->save();

            Log::info('Wallet credited successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'old_balance' => $oldBalance,
                'new_balance' => $wallet->balance,
                'reference' => $txRef,
                'flw_ref' => $flwRef
            ]);

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'code' => 200
            ];
        });
    }

    /**
     * Extract user ID from transaction reference or metadata
     */
    private function extractUserIdFromReference(string $txRef, array $data): ?int
    {
        // Method 1: Try to get from tx_ref with format "user_{id}_{timestamp}"
        if (preg_match('/user[_-](\d+)[_-]/', $txRef, $matches)) {
            Log::info('User ID extracted from tx_ref', [
                'tx_ref' => $txRef,
                'user_id' => $matches[1]
            ]);
            return intval($matches[1]);
        }

        // Method 2: Try to get from order_ref
        if (isset($data['order_ref'])) {
            if (preg_match('/user[_-](\d+)[_-]/', $data['order_ref'], $matches)) {
                Log::info('User ID extracted from order_ref', [
                    'order_ref' => $data['order_ref'],
                    'user_id' => $matches[1]
                ]);
                return intval($matches[1]);
            }
        }

        // Method 3: Try to get from meta data
        if (isset($data['meta']['user_id'])) {
            Log::info('User ID extracted from meta', [
                'user_id' => $data['meta']['user_id']
            ]);
            return intval($data['meta']['user_id']);
        }

        // Method 4: Try to get from customer email (MOST RELIABLE FOR YOUR CASE)
        if (isset($data['customer']['email'])) {
            $user = User::where('email', $data['customer']['email'])->first();
            if ($user) {
                Log::info('User ID extracted from customer email', [
                    'email' => $data['customer']['email'],
                    'user_id' => $user->id
                ]);
                return $user->id;
            }
        }

        Log::error('Failed to extract user ID from all methods', [
            'tx_ref' => $txRef,
            'data_keys' => array_keys($data)
        ]);

        return null;
    }
}