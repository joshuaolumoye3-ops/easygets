<?php

namespace App\Http\Controllers;

use App\Helpers\ClubKonnectHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ElectricityController extends Controller
{
    public function showElectricityPlans()
    {
        $userId = env('CLUBKONNECT_USERID');

        $response = Http::get("https://www.nellobytesystems.com/APIElectricityDiscosV1.asp", [
            'UserID' => $userId
        ]);

        $plans = [];

        if ($response->ok()) {
            $data = $response->json();
            $plans = $data['ELECTRIC_COMPANY'] ?? [];
        }

        return view('users.electricity', compact('plans'));
    }

    public function verifyElectricityMeter(Request $request)
    {
        $request->validate([
            'electricCompany' => 'required|string',
            'meterNo' => 'required|string'
        ]);
        dd($request->all());
        try {
            $response = ClubKonnectHelper::verifyElectricityMeter(
                $request->electricCompany,
                $request->meterNo
            );
           
            // If the API returns a recognizable success flag or customer name
            if (!empty($response['CustomerName'])) {
                return response()->json([
                    'status' => true,
                    'message' => 'Verification successful',
                    'customer_name' => $response['CustomerName'],
                    'meter_number' => $request->meterNo
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => $response['message'] ?? 'Invalid meter number'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while verifying the meter number'
            ], 500);
        }
    }

    public function purchase(Request $request)
    {
        // validate request
        $validated = $request->validate([
            'electricCompany' => 'required|string',
            'package'         => 'required|string', // meter type
            'meterNumber'     => 'required|string',
            'amount'          => 'required|numeric|min:100',
        ]);
        // generate unique request ID
        $requestId = Str::uuid()->toString();

        // use authenticated user phone or fallback
        $phoneNo = auth()->user()->phone ?? $request->input('phone');

        // call helper
        $response = ClubKonnectHelper::buyElectricity(
            $validated['electricCompany'],
            $validated['package'],
            $validated['meterNumber'],
            $validated['amount'],
            $phoneNo,
            $requestId
        );
        dd($response);
        // check API response
        if (isset($response['status']) && $response['status'] === 'success') {
            return back()->with('success', 'Electricity purchase successful. Token: ' . ($response['token'] ?? ''));
        } else {
            return back()->with('error', $response['message'] ?? 'Electricity purchase failed.');
        }
    }
}
