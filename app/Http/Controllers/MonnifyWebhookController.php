<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MonnifyWebhookController extends Controller
{
    public function handle(Request $request)
    {
        //  Raw payload for signature check
        $rawPayload = $request->getContent();
        $signature  = $request->header('monnify-signature');
        $secretKey  = env('MONNIFY_SECRET_KEY');

        // Verify Monnify signature
        $computedSignature = hash_hmac('sha512', $rawPayload, $secretKey);
        if (!hash_equals($computedSignature, $signature)) {
            Log::warning('Monnify Webhook: Invalid signature', [
                'received' => $signature,
                'computed' => $computedSignature
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        //  Decode JSON safely
        $payload = json_decode($rawPayload, true);
        if (!is_array($payload)) {
            Log::error('Monnify Webhook: Invalid JSON payload');
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        Log::info('Monnify Webhook Received', $payload);

        //  Process only successful transactions
        if (
            strtoupper($payload['eventType'] ?? '') !== 'SUCCESSFUL_TRANSACTION' ||
            empty($payload['eventData']['accountNumber']) ||
            empty($payload['eventData']['amountPaid'])
        ) {
            return response()->json(['ignored' => true], 200);
        }

        $accountNumber = $payload['eventData']['accountNumber'];
        $amount        = (float) $payload['eventData']['amountPaid'];
        $reference     = $payload['eventData']['transactionReference']
            ?? $payload['eventData']['paymentReference']
            ?? Str::uuid()->toString();

        //  Find wallet via virtual account
        $wallet = Wallet::whereHas('virtualAccounts', function ($q) use ($accountNumber) {
            $q->where('account_number', $accountNumber);
        })->first();

        if (!$wallet) {
            Log::error('Monnify Webhook: Wallet not found for account number', ['account_number' => $accountNumber]);
            return response()->json(['error' => 'Wallet not found'], 404);
        }

        // Prevent duplicate transactions
        if (Transaction::where('reference', $reference)->exists()) {
            Log::warning('Monnify Webhook: Duplicate transaction ignored', ['reference' => $reference]);
            return response()->json(['status' => 'duplicate'], 200);
        }

        //  Update wallet & create transaction
        $wallet->increment('balance', $amount);

        Transaction::create([
            'reference' => $reference,
            'user_id'   => $wallet->user_id,
            'type'      => 'deposit',
            'channel'   => 'Monnify',
            'amount'    => $amount,
            'status'    => 'success',
            'response'  => json_encode($payload),
        ]);

        Log::info('Monnify Webhook: Transaction processed successfully', [
            'reference' => $reference,
            'amount'    => $amount,
            'user_id'   => $wallet->user_id
        ]);

        return response()->json(['status' => 'ok'], 200);
    }
}
