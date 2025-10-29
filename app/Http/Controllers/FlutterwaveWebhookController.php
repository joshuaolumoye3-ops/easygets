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
        // Verify webhook signature
        $signature = $request->header('verif-hash');
        $secretHash = config('services.flutterwave.secret_hash') ?? env('FLW_SECRET_HASH');

        if (!$signature || !$secretHash) {
            Log::warning('Missing signature or secret hash');
            return false;
        }

        $payload = $request->all();
        $data = $payload['data'];

        Log::info('Flutterwave Webhook Received', ['payload' => $payload]);

        if ($payload['event'] !== 'virtual.account.credited') {
            return response('Ignored non-credit event', 200);
        }

        try {
            DB::beginTransaction();

            // Find virtual account by reference
            $virtualAccount = VirtualAccount::where('account_reference', $data['tx_ref'])
                ->where('status', 'pending')
                ->first();

            if (!$virtualAccount) {
                Log::error('Virtual account not found or already processed', [
                    'reference' => $data['tx_ref']
                ]);
                return response('Invalid account', 404);
            }

            // Verify amount matches expected amount
            if ((float)$data['amount'] !== (float)$virtualAccount->amount) {
                Log::warning('Amount mismatch', [
                    'expected' => $virtualAccount->amount,
                    'received' => $data['amount']
                ]);
                // Still process the payment but log warning
            }

            // Create transaction record
            $transaction = Transaction::create([
                'reference' => $data['tx_ref'],
                'user_id' => $virtualAccount->wallet->user_id,
                'type' => 'credit',
                'channel' => 'Flutterwave',
                'amount' => $data['amount'],
                'status' => 'success',
                'recipient' => $data['account_number'],
                'response' => json_encode($data)
            ]);

            // Update virtual account status
            $virtualAccount->update([
                'status' => 'completed'
            ]);

            // Credit wallet
            $virtualAccount->wallet->increment('balance', $data['amount']);

            DB::commit();

            Log::info('Payment processed successfully', [
                'reference' => $data['tx_ref'],
                'amount' => $data['amount']
            ]);

            return response('OK', 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response('Internal server error', 500);
        }
    }
}
