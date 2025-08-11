<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\ClubKonnectHelper;
use App\Models\Transaction;

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

        $response = ClubKonnectHelper::buyAirtime($network, $amount, $number, $requestId);
        
        // Store transaction
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

        if ($response && ($response['statuscode'] ?? null) == '100') {
            return back()->with('success', 'Airtime recharge request sent.');
        } else {
            return back()->with('error', 'Airtime recharge failed: ' . ($response['status'] ?? 'Unknown error'));
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
