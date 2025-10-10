<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\VirtualAccount;

class FlutterwaveWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify Flutterwave signature
        $signature = $request->header('verif-hash');
        if (!$signature || $signature !== env('FLW_SECRET_HASH')) {
            Log::warning('Invalid webhook signature received');
            return response('Invalid signature', 401);
        }

        $data = $request->input('data');

        Log::info('Flutterwave Webhook Received', $data);

        // Process only successful transactions
        if (isset($data['status']) && $data['status'] === 'successful') {
            $accountNumber = $data['account_number'] ?? null;

            if (!$accountNumber) {
                Log::warning('No account number in Flutterwave webhook payload');
                return response('Missing account number', 400);
            }

            // Find virtual account by account number
            $virtualAccount = VirtualAccount::where('account_number', $accountNumber)->first();

            if (!$virtualAccount) {
                Log::error("No matching virtual account found for account number: {$accountNumber}");
                return response('Account not found', 404);
            }

            // Get linked wallet and user
            $wallet = $virtualAccount->wallet;
            $user = $wallet->user;

            // Prevent duplicate transactions
            $existingTransaction = Transaction::where('reference', $data['tx_ref'])->first();
            if ($existingTransaction) {
                Log::info("Duplicate transaction ignored: {$data['tx_ref']}");
                return response('Duplicate transaction', 200);
            }

            // Record transaction
            Transaction::create([
                'user_id' => $user->id,
                'amount' => $data['amount'],
                'reference' => $data['tx_ref'],
                'status' => 'success',
                'type' => 'credit',
                'channel' => 'Flutterwave',
                'recipient' => $accountNumber,
                'response' => json_encode($data),
            ]);

            // Credit wallet
            $wallet->balance += $data['amount'];
            $wallet->save();

            Log::info("Wallet credited for {$user->email} with ₦{$data['amount']} | New balance: ₦{$wallet->balance}");
        }

        return response('OK', 200);
    }
}
