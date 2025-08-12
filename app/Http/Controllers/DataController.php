<?php

namespace App\Http\Controllers;

use App\Helpers\ClubKonnectHelper;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DataController extends Controller
{
    public function showData()
    {
        $userId = env('CLUBKONNECT_USERID');
        $response = Http::get("https://www.nellobytesystems.com/APIDatabundlePlansV2.asp", [
            'UserID' => $userId
        ]);

        $plans = [];

        if ($response->ok()) {
            $data = $response->json();
            $plans = $data['MOBILE_NETWORK'] ?? [];
        }

        return view('users.data', compact('plans'));
    }
    public function purchase(Request $request)
{
    $request->validate([
        'network'   => 'required|in:mtn,glo,airtel,9mobile',
        'data_plan' => 'required|string',
        'recipient' => 'required|digits:11',
        'amount'    => 'required|numeric|min:1',
    ]);

    $network       = $request->network;
    $dataPlanCode  = $request->data_plan;
    $number        = $request->recipient;
    $amount        = $request->amount;
    $requestId     = uniqid('data_');

    // ClubKonnect network code map
    $networkMap = [
        'mtn'     => '01',
        'glo'     => '02',
        'airtel'  => '03',
        '9mobile' => '04',
    ];

    $networkCode = $networkMap[$network] ?? null;

    if (!$amount || $amount <= 0) {
        return back()->with('error', 'Invalid amount for selected data plan.');
    }

    //  Get wallet & check balance
    $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

    if (!$wallet || $wallet->balance < $amount) {
        return back()->with('error', 'Insufficient wallet balance.');
    }

    DB::beginTransaction();

    try {
        // ✅ Step 2: Call ClubKonnect API
        $response = ClubKonnectHelper::buyDataBundle(
            $networkCode,
            $dataPlanCode,
            $number,
            $requestId
        );

        // ✅ Step 3: Store transaction
        Transaction::create([
            'reference' => $requestId,
            'user_id'   => auth()->id(),
            'type'      => 'data',
            'channel'   => 'ClubKonnect',
            'provider'  => $this->getProviderName($networkCode),
            'phone'     => $number,
            'amount'    => $amount,
            'status'    => ($response['statuscode'] ?? null) == '100' ? 'success' : 'failed',
            'response'  => json_encode($response),
        ]);

        // ✅ Step 4: Deduct balance only if success
        if (($response['statuscode'] ?? null) == '100') {
            $wallet->decrement('balance', $amount);
            DB::commit();
            return back()->with('success', 'Data purchase successful. Order ID: ' . $response['orderid']);
        } else {
            DB::rollBack();
            return back()->with('error', 'Transaction failed: ' . ($response['status'] ?? 'Unknown error'));
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
