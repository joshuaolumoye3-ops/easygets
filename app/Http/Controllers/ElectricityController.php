<?php

namespace App\Http\Controllers;

use App\Helpers\ClubKonnectHelper;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Helpers\PinHelper;
use App\Models\Transaction;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $wallet = Wallet::where('user_id', auth()->id())->first();

        return view('users.electricity', compact('plans', 'wallet'));
    }

    public function verifyElectricityMeter(Request $request)
    {
        $request->validate([
            'electricCompany' => 'required|string',
            'meterNo' => 'required|string'
        ]);

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
        // Validate request including PIN
        $validated = $request->validate([
            'electricCompany' => 'required|string',
            'package'         => 'required|string', // meter type
            'meterNumber'     => 'required|string',
            'amount'          => 'required|numeric|min:100',
            'pin'             => 'required|string|size:4'
        ]);

        // Verify PIN first before processing
        try {
            PinHelper::verifyPin($request->pin);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->validator->errors()->first('pin')
            ], 400);
        }

        // Get wallet with lock for update to prevent race conditions
        $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

        // Check wallet balance
        if (!$wallet || $wallet->balance < $validated['amount']) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient wallet balance. Your current balance is ₦' . number_format($wallet->balance ?? 0, 2)
            ], 400);
        }

        // Generate unique request ID
        $requestId = Str::uuid()->toString();

        // Use authenticated user phone or fallback
        $phoneNo = auth()->user()->phone ?? $request->input('phone');

        DB::beginTransaction();

        try {
            // Call helper
            $response = ClubKonnectHelper::buyElectricity(
                $validated['electricCompany'],
                $validated['package'],
                $validated['meterNumber'],
                $validated['amount'],
                $phoneNo,
                $requestId
            );

            // Create transaction record
            $transaction = Transaction::create([
                'reference' => $requestId,
                'user_id' => auth()->id(),
                'type' => 'electricity',
                'channel' => 'ClubKonnect',
                'provider' => $validated['electricCompany'],
                'phone' => $phoneNo,
                'amount' => $validated['amount'],
                'meter_number' => $validated['meterNumber'],
                'package' => $validated['package'],
                'status' => (isset($response['status']) && $response['status'] === 'success') ? 'success' : 'failed',
                'response' => json_encode($response),
            ]);

            // Check API response
            if (isset($response['status']) && $response['status'] === 'success') {
                // Deduct amount from wallet
                $wallet->decrement('balance', $validated['amount']);
                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Electricity purchase successful.',
                    'data' => [
                        'token' => $response['token'] ?? '',
                        'meter_number' => $validated['meterNumber'],
                        'amount' => $validated['amount'],
                        'company' => $validated['electricCompany'],
                        'transaction_id' => $requestId,
                        'new_balance' => $wallet->fresh()->balance
                    ]
                ]);

            } else {
                DB::rollBack();
                $errorMessage = $response['message'] ?? 'Electricity purchase failed.';
                
                return response()->json([
                    'status' => false,
                    'message' => $errorMessage
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Electricity Purchase Error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'meter_number' => $validated['meterNumber'],
                'amount' => $validated['amount'],
                'company' => $validated['electricCompany'],
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.'
            ], 500);
        }
    }

    /**
     * Render success partial for AJAX requests
     */
    public function renderSuccessPartial(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'details' => 'sometimes|array'
        ]);

        // Render the partial component with details
        return view('partials.success', [
            'title' => $data['title'],
            'message' => $data['message'],
            'details' => $data['details'] ?? []
        ])->render();
    }
}