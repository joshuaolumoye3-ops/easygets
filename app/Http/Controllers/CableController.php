<?php

namespace App\Http\Controllers;

use App\Helpers\ClubKonnectHelper;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // ✅ Step 1: Get wallet & check balance
        $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

        if (!$wallet || $wallet->balance < $amount) {
            return back()->with('error', 'Insufficient wallet balance.');
        }

        DB::beginTransaction();

        try {
            // ✅ Step 2: Call ClubKonnect API
            $response = ClubKonnectHelper::buyCableTV(
                $cableTV,
                $package,
                $smartCardNo,
                $phoneNo,
                $requestId,
                $callbackUrl
            );

            // ✅ Step 3: Store transaction
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

            // ✅ Step 4: Deduct balance only if success
            if (isset($response['status']) && $response['status'] === 'SUCCESS') {
                $wallet->decrement('balance', $amount);
                DB::commit();
                return redirect()->back()->with('success', 'Cable TV purchase successful!');
            } else {
                DB::rollBack();
                $errorMessage = $response['message'] ?? 'Purchase failed. Please try again.';
                return redirect()->back()->withInput()->with('error', $errorMessage);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function jamb(Request $request)
{
    $validExamTypes = ['utme', 'de'];
    $request->validate([
        'phone_number' => 'required|string|min:11|max:11',
        'exam_type' => 'sometimes|string|in:' . implode(',', $validExamTypes),
        'amount' => 'required|numeric|min:1000|max:10000'
    ]);
    $phoneNo = $request->input('phone_number');
    $examType = $request->input('exam_type', 'utme');
    $amount = $request->input('amount');
    $requestId = uniqid('jamb_');
    $callbackUrl = null;

    $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

    if (!$wallet || $wallet->balance < $amount) {
        return back()->with('error', 'Insufficient wallet balance.');
    }

    DB::beginTransaction();

    try {

        $response = ClubKonnectHelper::buyJamb(
            $phoneNo,
            $examType,
            $requestId,
            $callbackUrl
        );




        $transaction = Transaction::create([
            'reference' => $requestId,
            'user_id' => auth()->id(),
            'type' => 'jamb',
            'channel' => 'ClubKonnect',
            'provider' => 'JAMB',
            'phone' => $phoneNo,
            'amount' => $amount,
            'status' => ($response['statuscode'] === '200') ? 'success' : 'failed',
            'response' => json_encode($response),
        ]);


        if ($response['statuscode'] === '200') {
            $wallet->decrement('balance', $amount);
            DB::commit();


            $cardDetails = $response['carddetails'] ?? '';
            preg_match('/Serial No:(.*?), pin: (.*)/', $cardDetails, $matches);
            $serial = $matches[1] ?? '';
            $pin = $matches[2] ?? '';

            return redirect()->back()->with([
                'success' => 'JAMB PIN sent to '.$request->phone_number,
                'pin_details' => [
                    'serial' => trim($serial),
                    'pin' => trim($pin),
                    'order_id' => $response['orderid']
                ]
            ]);
        } else {
            DB::rollBack();
            $errorMessage = $response['remark'] ?? 'JAMB purchase failed. Please try again.';
            return redirect()->back()->withInput()->with('error', $errorMessage);
        }

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Something went wrong. Please try again.');
    }
}
}
