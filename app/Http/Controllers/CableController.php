<?php

namespace App\Http\Controllers;

use App\Helpers\ClubKonnectHelper;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CableController extends Controller
{
    public function showCableTV()
    {
        $userId = env('CLUBKONNECT_USERID');
        $response = Http::get("https://www.nellobytesystems.com/APICableTVPackagesV2.asp", [
            'UserID' => $userId
        ]);

        $plans = [];

        if ($response->ok()) {
            $data = $response->json();
            $plans = $data['TV_ID'] ?? []; // Extract DStv, GOtv, Startimes list
        }

        return view('users.cabletv', compact('plans'));
    }
    public function verifySmartcard(Request $request)
    {
        $request->validate([
            'cableTV'     => 'required|string',
            'smartCardNo' => 'required|string'
        ]);

        try {
            $verification = ClubKonnectHelper::verifyCableTVCard(
                $request->cableTV,
                $request->smartCardNo
            );

            if (isset($verification['error']) && $verification['error'] == true) {
                return response()->json([
                    'status'  => 'ERROR',
                    'message' => $verification['message'] ?? 'Verification failed'
                ], 400);
            }

            // Return just the status string instead of full array
            return response()->json([
                'status' => $verification['status'] ?? 'UNKNOWN'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'ERROR',
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
    public function purchase(Request $request)
{
    $request->validate([
        'network'         => 'required|string',
        'package'         => 'required|string',
        'smartcardNumber' => 'required|string',
        'phoneNumber'     => 'required|string',
        'amount'          => 'required|numeric|min:1',
    ]);

    $cableTV     = $request->input('network');
    $package     = $request->input('package');
    $smartCardNo = $request->input('smartcardNumber');
    $phoneNo     = $request->input('phoneNumber');
    $amount      = $request->input('amount');
    $requestId   = uniqid('cable_');
    $callbackUrl = null;

    // Call ClubKonnect API
    $response = ClubKonnectHelper::buyCableTV(
        $cableTV,
        $package,
        $smartCardNo,
        $phoneNo,
        $requestId,
        $callbackUrl
    );

    // Save transaction to DB
    Transaction::create([
        'reference' => $requestId,
        'user_id'   => auth()->id(),
        'type'      => 'cable',
        'channel'   => 'ClubKonnect',
        'provider'  => $cableTV,
        'phone'     => $phoneNo,
        'amount'    => $amount,
        'status'    => (isset($response['status']) && $response['status'] === 'SUCCESS') ? 'success' : 'failed',
        'response'  => json_encode($response),
    ]);

    // Redirect based on response
    if (isset($response['status']) && $response['status'] === 'SUCCESS') {
        return redirect()->back()->with('success', 'Cable TV purchase successful!');
    } else {
        $errorMessage = $response['message'] ?? 'Purchase failed. Please try again.';
        return redirect()->back()->withInput()->with('error', $errorMessage);
    }
}


}
