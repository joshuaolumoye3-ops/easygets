<?php

namespace App\Http\Controllers;

use App\Helpers\ClubKonnectHelper;
use App\Models\Transaction;
use Illuminate\Http\Request;
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
            '9mobile' => '03',
            'airtel'  => '04',
        ];

        $networkCode = $networkMap[$network] ?? null;

        // Sanity check: reject zero or null amounts
        if (!$amount || $amount <= 0) {
            return back()->with('error', 'Invalid amount for selected data plan.');
        }

        // Call ClubKonnect
        $response = ClubKonnectHelper::buyDataBundle(
            $networkCode,
            $dataPlanCode,
            $number,
            $requestId
        );

        // Save transaction
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

        if (($response['statuscode'] ?? null) == '100') {
            return back()->with('success', 'Data purchase initiated successfully. Order ID: ' . $response['orderid']);
        } else {
            return back()->with('error', 'Transaction failed: ' . ($response['status'] ?? 'Unknown error'));
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
