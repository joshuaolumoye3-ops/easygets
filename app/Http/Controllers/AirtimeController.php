<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ClubKonnectHelper;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class AirtimeController extends Controller
{
    public function recharge(Request $request)
    {
        $request->validate([
            'mobile_network' => 'required|in:01,02,03,04',
            'mobile_number' => 'required|regex:/^0\d{10}$/',
            'amount' => 'required|numeric|min:50|max:200000',
        ]);

        $network = $request->mobile_network;
        $number = $request->mobile_number;
        $amount = $request->amount;
        $requestId = uniqid('airtime_');

        // ✅ Step 1: Get user wallet & check balance
        $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

        if (!$wallet || $wallet->balance < $amount) {
            return back()->with('error', 'Insufficient wallet balance.');
        }

        DB::beginTransaction();

        try {
            // ✅ Step 2: Call ClubKonnect API
            $response = ClubKonnectHelper::buyAirtime($network, $amount, $number, $requestId);

            // ✅ Step 3: Store transaction
            Transaction::create([
                'reference' => $requestId,
                'user_id' => auth()->id(),
                'type' => 'airtime',
                'channel' => 'ClubKonnect',
                'provider' => $this->getProviderName($network),
                'phone' => $number,
                'amount' => $amount,
                'status' => ($response['statuscode'] ?? null) == '100' ? 'success' : 'failed',
                'response' => json_encode($response),
            ]);

            // ✅ Step 4: Deduct balance only if successful
            if (($response['statuscode'] ?? null) == '100') {
                $wallet->decrement('balance', $amount);
                DB::commit();
                return back()->with('success', 'Airtime recharge request sent.');
            } else {
                DB::rollBack();
                return back()->with('error', 'Airtime recharge failed: ' . ($response['status'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    private function getProviderName($code)
    {
        return match ($code) {
            '01' => 'MTN',
            '02' => 'Glo',
            '03' => 'Airtel',
            '04' => '9mobile',
            default => 'Unknown',
        };
    }
}
