<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\FlutterwaveHelper;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VirtualAccountController extends Controller
{
    /**
     * Initialize payment checkout
     */
    public function createAccount(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100'
        ]);

        try {
            $user = User::findOrFail(auth()->id());
            $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

            // Generate unique transaction reference
            $txRef = 'FLW_' . $user->id . '_' . time() . '_' . uniqid();

            $user_data = [
                'user' => $user,
                'amount' => $request->amount,
                'tx_ref' => $txRef
            ];

            Log::info('Initiating Flutterwave Payment', [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'tx_ref' => $txRef
            ]);

            $response = FlutterwaveHelper::initializePayment($user_data);

            if (isset($response['status']) && $response['status'] === 'success') {
                // Store payment reference in session
                session([
                    'payment_reference' => $txRef,
                    'payment_amount' => $request->amount,
                    'payment_initiated_at' => now()
                ]);

                // Create pending transaction record
                Transaction::create([
                    'reference' => $txRef,
                    'user_id' => $user->id,
                    'type' => 'credit',
                    'channel' => 'Flutterwave',
                    'provider' => 'Flutterwave Checkout',
                    'amount' => $request->amount,
                    'status' => 'pending',
                    'description' => 'Wallet funding via Flutterwave Checkout'
                ]);

                // Redirect to Flutterwave payment page
                return redirect($response['payment_link']);
            }

            $errorMessage = $response['message'] ?? 'Failed to initialize payment';
            Log::error('Payment initialization failed', [
                'user_id' => $user->id,
                'error' => $errorMessage
            ]);

            return redirect()->back()->with('error', $errorMessage);

        } catch (\Exception $e) {
            Log::error('Payment initialization exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'System error: ' . $e->getMessage());
        }
    }

    /**
     * Handle payment callback
     */
    public function handleCallback(Request $request)
    {
        $status = $request->query('status');
        $txRef = $request->query('tx_ref');
        $transactionId = $request->query('transaction_id');
        // dd($request); // removed for production

        Log::info('Flutterwave Payment Callback', [
            'status' => $status,
            'tx_ref' => $txRef,
            'transaction_id' => $transactionId,
            'all_params' => $request->all()
        ]);

        // Check if payment was cancelled or failed
        if ($status === 'cancelled') {
            // Update transaction status to failed
            Transaction::where('reference', $txRef)
                ->where('status', 'pending')
                ->update(['status' => 'failed', 'response' => json_encode(['reason' => 'Payment cancelled by user'])]);

            return redirect()->route('dashboard')
                ->with('error', 'Payment was cancelled.');
        }

        if ($status !== 'completed' || !$transactionId) {
            // Update transaction status to failed
            Transaction::where('reference', $txRef)
                ->where('status', 'pending')
                ->update(['status' => 'failed', 'response' => json_encode(['reason' => 'Payment not completed'])]);

            return redirect()->route('dashboard')
                ->with('error', 'Payment was not completed. Please try again.');
        }

        // Verify transaction with Flutterwave
        $verification = FlutterwaveHelper::verifyTransaction($transactionId);

        Log::info('Flutterwave Verification Response', [
            'transaction_id' => $transactionId,
            'verification' => $verification
        ]);

        if (
            isset($verification['status']) &&
            $verification['status'] === 'success' &&
            isset($verification['data']['status']) &&
            $verification['data']['status'] === 'successful'
        ) {
            // Process the payment and credit wallet
            return $this->processSuccessfulPayment($verification['data'], $txRef);
        }

        Log::warning('Payment verification failed', [
            'transaction_id' => $transactionId,
            'tx_ref' => $txRef,
            'verification' => $verification
        ]);

        // Update transaction status to failed
        Transaction::where('reference', $txRef)
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'response' => json_encode($verification)
            ]);

        return redirect()->route('dashboard')
            ->with('error', 'Payment verification failed. Please contact support if money was deducted.');
    }

    /**
     * Process successful payment and credit wallet
     */
    protected function processSuccessfulPayment($data, $txRef)
    {
        try {
            DB::beginTransaction();

            // Find the pending transaction
            $transaction = Transaction::where('reference', $txRef)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                Log::error('Transaction not found or already processed', [
                    'tx_ref' => $txRef,
                    'data' => $data
                ]);
                DB::rollBack();
                return redirect()->route('dashboard')
                    ->with('error', 'Transaction record not found. Please contact support.');
            }

            // Check if already processed
            if ($transaction->status === 'success') {
                Log::warning('Transaction already processed', [
                    'tx_ref' => $txRef,
                    'transaction_id' => $transaction->id
                ]);
                DB::rollBack();
                return redirect()->route('dashboard')
                    ->with('info', 'Payment already processed.');
            }

            // Get user's wallet
            $wallet = Wallet::where('user_id', $transaction->user_id)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                // Create wallet if it doesn't exist
                $wallet = Wallet::create([
                    'user_id' => $transaction->user_id,
                    'balance' => 0
                ]);
            }

            $amount = $data['amount'];
            $chargedAmount = $data['charged_amount'] ?? $amount;

            // Verify amount matches
            if (abs($transaction->amount - $amount) > 0.01) {
                Log::error('Amount mismatch', [
                    'tx_ref' => $txRef,
                    'expected' => $transaction->amount,
                    'received' => $amount
                ]);
                DB::rollBack();
                return redirect()->route('dashboard')
                    ->with('error', 'Payment amount mismatch. Please contact support.');
            }

            // Credit wallet
            $oldBalance = $wallet->balance;
            $wallet->balance += $amount;
            $wallet->save();

            // Update transaction
            $transaction->status = 'success';
            $transaction->response = json_encode([
                'flutterwave_id' => $data['id'],
                'flw_ref' => $data['flw_ref'] ?? null,
                'tx_ref' => $data['tx_ref'],
                'amount' => $amount,
                'charged_amount' => $chargedAmount,
                'currency' => $data['currency'] ?? 'NGN',
                'payment_type' => $data['payment_type'] ?? null,
                'status' => $data['status'],
                'old_balance' => $oldBalance,
                'new_balance' => $wallet->balance,
                'processed_at' => now()->toDateTimeString()
            ]);
            $transaction->save();

            // Clear session
            session()->forget(['payment_reference', 'payment_amount', 'payment_initiated_at']);

            DB::commit();

            Log::info('Wallet credited successfully', [
                'tx_ref' => $txRef,
                'user_id' => $transaction->user_id,
                'amount' => $amount,
                'old_balance' => $oldBalance,
                'new_balance' => $wallet->balance
            ]);

            return redirect()->route('dashboard')
                ->with('success', "Payment successful! ₦" . number_format($amount, 2) . " has been added to your wallet.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Payment processing failed', [
                'tx_ref' => $txRef,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Payment processing failed. Please contact support.');
        }
    }    /**
     * Clear session data
     */
    public function clearSession(Request $request)
    {
        $request->session()->forget(['payment_reference', 'payment_amount', 'payment_initiated_at']);
        return response()->json(['success' => true]);
    }
}