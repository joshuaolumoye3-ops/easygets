<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ClubKonnectHelper;
use App\Helpers\DemmyPayHelper;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AirtimeController extends Controller
{
    // public function recharge(Request $request)
    // {
    //     $request->validate([
    //         'mobile_network' => 'required|in:01,02,03,04',
    //         'mobile_number' => 'required|regex:/^0\d{10}$/',
    //         'amount' => 'required|numeric|min:50|max:200000',
    //     ]);

    //     $network = $request->mobile_network;
    //     $number = $request->mobile_number;
    //     $amount = $request->amount;
    //     $requestId = uniqid('airtime_');

    //     // ✅ Step 1: Get user wallet & check balance
    //     $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

    //     if (!$wallet || $wallet->balance < $amount) {
    //         return back()->with('error', 'Insufficient wallet balance.');
    //     }

    //     DB::beginTransaction();

    //     try {
    //         // ✅ Step 2: Call ClubKonnect API
    //         $response = ClubKonnectHelper::buyAirtime($network, $amount, $number, $requestId);

    //         // ✅ Step 3: Store transaction
    //         Transaction::create([
    //             'reference' => $requestId,
    //             'user_id' => auth()->id(),
    //             'type' => 'airtime',
    //             'channel' => 'ClubKonnect',
    //             'provider' => $this->getProviderName($network),
    //             'phone' => $number,
    //             'amount' => $amount,
    //             'status' => ($response['statuscode'] ?? null) == '100' ? 'success' : 'failed',
    //             'response' => json_encode($response),
    //         ]);

    //         // ✅ Step 4: Deduct balance only if successful
    //         if (($response['statuscode'] ?? null) == '100') {
    //             $wallet->decrement('balance', $amount);
    //             DB::commit();
    //             return back()->with('success', 'Airtime recharge request sent.');
    //         } else {
    //             DB::rollBack();
    //             return back()->with('error', 'Airtime recharge failed: ' . ($response['status'] ?? 'Unknown error'));
    //         }

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Something went wrong. Please try again.');
    //     }
    // }

     /**
     * Map network codes to DemmyPay product IDs for airtime
     */
     private function getAirtimeProductId($code)
    {
        return match ($code) {
            '01' => 661, // MTN Airtime
            '02' => 702, // Glo Airtime
            '03' => 703, // Airtel Airtime (replace with actual ID)
            '04' => 704, // 9mobile Airtime (replace with actual ID)
            default => null,
        };
    }

    /**
     * Handle airtime purchase
     */
    public function recharge(Request $request)
    {
        $validated = $request->validate([
            'amount'    => 'required|numeric|min:50|max:50000',
            'network'   => 'required|string|in:01,02,03,04',
            'recipient' => 'required|string|min:8|max:15',
        ]);

        try {
            // --- sanitize input ---
            $amount    = (int) $validated['amount']; // cast to int
            $network   = trim($validated['network']); // remove whitespace
            $recipient = preg_replace('/\D/', '', $validated['recipient']); // digits only

            // validate phone number again after cleaning
            if (strlen($recipient) < 8 || strlen($recipient) > 15) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid recipient phone number',
                ], 400);
            }

            // Get productId
            $productId = $this->getAirtimeProductId($network);
            if (!$productId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid network code provided',
                ], 400);
            }

            // generate unique reference
            $customerReference = 'AIRTIME_' . Str::uuid();

            // call helper
            $response = DemmyPayHelper::buyAirtime(
                $amount,
                $productId,
                $recipient,
                $customerReference
            );
            dd($response);
            if (isset($response['status']) && $response['status'] == 200) {
                return response()->json([
                    'success' => true,
                    'message' => 'Airtime purchase successful',
                    'data'    => $response['data'],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response['detail'] ?? 'Airtime purchase failed',
                'response'=> $response
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
