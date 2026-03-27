<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Helpers\OtpHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Helpers\PinHelper;

class OtpController extends Controller
{
    // Proxy to 5sim check order
    public function check($orderId)
    {

        // Allow polling for any valid transaction by orderId (no auth required)
        $txn = \App\Models\Transaction::where('request_id', $orderId)->first();
        if (!$txn) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $resp = OtpHelper::checkOrder($orderId);
        $data = $resp->json();
        $status = $resp->status();

        // Try to get OTP code and status for display
        $otpCode = null;
        $smsText = null;
        if (!empty($data['sms']) && is_array($data['sms'])) {
            foreach ($data['sms'] as $sms) {
                if (!empty($sms['code'])) {
                    $otpCode = $sms['code'];
                    $smsText = $sms['text'] ?? null;
                    break;
                }
            }
        }
        // Also get status
        $orderStatus = $data['status'] ?? null;

        // Add to response for frontend display
        $data['otp_code'] = $otpCode;
        $data['sms_text'] = $smsText;
        $data['order_status'] = $orderStatus;

        return response()->json($data, $status);
    }

    // Cancel order and refund
    public function cancel($orderId)
    {
        // Allow cancel for any valid transaction by orderId (no auth required)
        $txn = Transaction::where('request_id', $orderId)->first();
        if (!$txn) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }
        if ($txn->status === 'canceled' || $txn->status === 'refunded') {
            return response()->json(['success' => false, 'message' => 'Already canceled/refunded.'], 400);
        }
        $resp = OtpHelper::cancelOrder($orderId);
        if ($resp->ok() && in_array($resp['status'], ['CANCELED', 'TIMEOUT'])) {
            DB::transaction(function() use ($txn) {
                $txn->status = 'refunded';
                $txn->save();
                $wallet = Wallet::where('user_id', $txn->user_id)->lockForUpdate()->first();
                if ($wallet) {
                    $wallet->balance += $txn->amount;
                    $wallet->save();
                }
            });
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => $resp->json('message') ?? 'Failed to cancel.'], 400);
    }

    // Re-buy number (validate PIN, charge user, call 5sim reuse)
    public function rebuy(Request $request, $product, $number)
    {
        $user = Auth::user();
        $pin = $request->input('pin');
        try {
            PinHelper::verifyPin($pin, $user);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
        $resp = OtpHelper::rebuyNumber($product, $number);
        if ($resp->ok() && isset($resp['price'])) {
            $price = $resp['price'];
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if ($wallet->balance < $price) {
                return response()->json(['success' => false, 'message' => 'Insufficient balance.'], 400);
            }
            DB::transaction(function() use ($wallet, $price, $user, $resp) {
                $wallet->balance -= $price;
                $wallet->save();
                Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'esim',
                    'amount' => $price,
                    'status' => 'pending',
                    'reference' => 'REB-' . uniqid(),
                    'order_id' => $resp['id'] ?? null,
                    'phone' => $resp['phone'] ?? null,
                    'product' => $resp['product'] ?? null,
                    'expires' => $resp['expires'] ?? null,
                ]);
            });
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => $resp->json('message') ?? 'Failed to re-buy.'], 400);
    }
}
