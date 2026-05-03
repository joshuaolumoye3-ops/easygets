<?php

namespace App\Http\Controllers;

use App\Helpers\ClubKonnectHelper;
use App\Helpers\PinHelper;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CableController extends Controller
{
public function showCableTV()
{
    $providerId = 3; // Cable TV provider

    // Get provider and its products
    $provider = Provider::with('products')->find($providerId);
    
    if (!$provider) {
        return response()->json(['error' => 'Provider not found'], 404);
    }

    $structured = [];
    
    foreach ($provider->products as $product) {
        $network = null;
        $id = null;

        if (stripos($product->name, 'DStv') !== false) {
            $network = 'DStv';
            $id = 'dstv';
        } elseif (stripos($product->name, 'GOtv') !== false) {
            $network = 'GOtv';
            $id = 'gotv';
        } elseif (stripos($product->name, 'Startimes') !== false) {
            $network = 'Startimes';
            $id = 'startimes';
        } elseif (
            stripos($product->name, 'Showmax') !== false || 
            stripos($product->name, 'Show Max') !== false || 
            stripos($product->name, 'Show-Max') !== false
        ) {
            $network = 'Showmax';
            $id = 'showmax';
        }

        if ($network) {
            if (!isset($structured[$network])) {
                $structured[$network] = [
                    [
                        'ID' => $id,
                        'PRODUCT' => []
                    ]
                ];
            }

            $structured[$network][0]['PRODUCT'][] = [
                'PACKAGE_ID'                => $product->product_code,  // or unique identifier
                'PACKAGE_NAME'              => $product->name,
                'PACKAGE_AMOUNT'            => $product->client_price, // ✅ client price
                'PRODUCT_DISCOUNT_AMOUNT'   => $product->discount_amount ?? null,
                'PRODUCT_DISCOUNT'          => $product->discount ?? null,
                'MINAMOUNT'                 => $product->min_amount ?? 100,
                'MAXAMOUNT'                 => $product->max_amount ?? 200000,
            ];
        }
    }
    $plans = $structured;

    // ✅ Get logged-in user's wallet
    $wallet = Wallet::where('user_id', auth()->id())->first();

    return view('users.cabletv', compact('plans', 'wallet'));
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

        // Handle provider response errors
        if (isset($verification['error']) && $verification['error'] == true) {
            return response()->json([
                'status'  => 'ERROR',
                'message' => $verification['message'] ?? 'Verification failed. Please try again.'
            ], 400);
        }

        // Success response
        return response()->json([
            'status'  => $verification['status'] ?? 'UNKNOWN',
            'message' => $verification['message'] ?? 'Verification completed successfully'
        ]);

    } catch (\Exception $e) {
        // Log actual error for developers
        \Log::error('Cable Smartcard Verification Error', [
            'error'   => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
            'cableTV' => $request->cableTV,
            'card'    => $request->smartCardNo,
        ]);

        // Return friendly message to user
        return response()->json([
            'status'  => 'ERROR',
            'message' => 'We could not verify the smartcard at the moment. Please check your details or try again later.'
        ], 500);
    }
}

public function purchase(Request $request)
{
    // Handle both JSON and FormData requests
    $data = $request->isJson() ? $request->json()->all() : $request->all();

    $validator = Validator::make($data, [
        'network'         => 'required|string',
        'package'         => 'required|string', // this is product_code or id
        'smartcardNumber' => 'required|string',
        'phoneNumber'     => 'required|string',
        'amount'          => 'required|numeric|min:1',
        'pin'             => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        PinHelper::verifyPin($data['pin']);
    } catch (ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid PIN',
            'errors' => $e->errors()
        ], 422);
    }

    $cableTV     = $data['network'];
    $package     = $data['package']; // Product code
    $smartCardNo = $data['smartcardNumber'];
    $phoneNo     = $data['phoneNumber'];
    $requestId   = uniqid('cable_');
    $callbackUrl = null;

    // ✅ Step 1: Get product from DB (always trusted)
    $product = Product::where('product_code', $package)
        ->where('provider_id', 3) // CableTV provider_id
        ->first();

    if (!$product) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid package selected.'
        ], 400);
    }

    $amount = (float) $product->client_price; // ✅ Use client_price from DB

    // ✅ Step 2: Get wallet & check balance
    $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

    if (!$wallet || $wallet->balance < $amount) {
        return response()->json([
            'status' => false,
            'message' => 'Insufficient wallet balance.'
        ], 400);
    }

    DB::beginTransaction();

    try {
        // ✅ Step 3: Call ClubKonnect API
        $response = ClubKonnectHelper::buyCableTV(
            $cableTV,
            $package,
            $smartCardNo,
            $phoneNo,
            $requestId,
            $callbackUrl
        );
        // dd($response); // removed for production

        // ✅ Step 4: Store transaction
        $transaction = Transaction::create([
            'reference' => $requestId,
            'user_id'   => auth()->id(),
            'type'      => 'cable',
            'channel'   => 'ClubKonnect',
            'provider'  => $cableTV,
            'phone'     => $phoneNo,
            'amount'    => $amount, // ✅ Save DB price, not request price
            'status'    => (isset($response['status']) && $response['status'] === 'SUCCESS') ? 'success' : 'failed',
            'response'  => json_encode($response),
        ]);

        // ✅ Step 5: Deduct balance only if success
        if (isset($response['status']) && $response['status'] === 'SUCCESS') {
            $wallet->decrement('balance', $amount);
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Cable TV purchase successful!',
                'data' => [
                    'reference'     => $requestId,
                    'package_name'  => $product->name,
                    'amount'        => $amount,
                    'new_balance'   => $wallet->fresh()->balance,
                    'transaction_id'=> $transaction->id
                ]
            ]);
        } else {
            DB::rollBack();
            $errorMessage = $response['message'] ?? 'Purchase failed. Please try again.';

            return response()->json([
                'status' => false,
                'message' => $errorMessage
            ], 400);
        }

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong. Please try again.',
            'error'   => app()->environment('local') ? $e->getMessage() : null
        ], 500);
    }
}


    public function jambshow() // or whatever method renders your betting view
    {
        $wallet = Wallet::where('user_id', auth()->id())->first();
        return view('users.jambPin', compact('wallet'));
    }

    public function jamb(Request $request)
    {
        $validExamTypes = ['utme', 'de'];
        
        // Validate input including PIN
        $request->validate([
            'phone_number' => 'required|string|min:11|max:11',
            'exam_type' => 'sometimes|string|in:' . implode(',', $validExamTypes),
            'amount' => 'required|numeric|min:1000|max:10000',
            'pin' => 'required|string'
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

        $phoneNo = $request->input('phone_number');
        $examType = $request->input('exam_type', 'utme');
        $amount = $request->input('amount');
        $requestId = uniqid('jamb_');
        $callbackUrl = null;

        // Get wallet with lock for update to prevent race conditions
        $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

        // Check wallet balance
        if (!$wallet || $wallet->balance < $amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient wallet balance. Your current balance is ₦' . number_format($wallet->balance ?? 0, 2)
            ], 400);
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
                // Deduct amount from wallet
                $wallet->decrement('balance', $amount);
                DB::commit();

                // Extract PIN details from response
                $cardDetails = $response['carddetails'] ?? '';
                preg_match('/Serial No:(.*?), pin: (.*)/', $cardDetails, $matches);
                $serial = $matches[1] ?? '';
                $pin = $matches[2] ?? '';

                return response()->json([
                    'status' => true,
                    'message' => 'JAMB e-PIN purchased successfully and sent to ' . $phoneNo,
                    'data' => [
                        'serial' => trim($serial),
                        'pin' => trim($pin),
                        'order_id' => $response['orderid'] ?? '',
                        'phone' => $phoneNo,
                        'amount' => $amount,
                        'new_balance' => $wallet->fresh()->balance
                    ]
                ]);

            } else {
                DB::rollBack();
                $errorMessage = $response['remark'] ?? 'JAMB purchase failed. Please try again.';
                
                return response()->json([
                    'status' => false,
                    'message' => $errorMessage
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('JAMB Purchase Error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'phone' => $phoneNo,
                'amount' => $amount,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.'
            ], 500);
        }
    }
}
