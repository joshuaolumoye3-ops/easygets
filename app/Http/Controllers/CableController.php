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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CableController extends Controller
{
    public function showCableTV()
    {
        $providerId = 3; // Cable TV provider

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
                            'ID' => $id, // lowercase ClubKonnect code — this is what MUST be sent as CableTV
                            'PRODUCT' => []
                        ]
                    ];
                }

                $structured[$network][0]['PRODUCT'][] = [
                    'PACKAGE_ID'                => $product->product_code,
                    'PACKAGE_NAME'               => $product->name,
                    'PACKAGE_AMOUNT'             => $product->client_price,
                    'PRODUCT_DISCOUNT_AMOUNT'    => $product->discount_amount ?? null,
                    'PRODUCT_DISCOUNT'           => $product->discount ?? null,
                    'MINAMOUNT'                  => $product->min_amount ?? 100,
                    'MAXAMOUNT'                  => $product->max_amount ?? 200000,
                ];
            }
        }

        $plans = $structured;
        $wallet = Wallet::where('user_id', auth()->id())->first();

        return view('users.cabletv', compact('plans', 'wallet'));
    }

    /**
     * Verify smartcard/IUC number.
     * ClubKonnect returns {"customer_name": "..."} on success or
     * {"customer_name": "INVALID_SMARTCARDNO"} on failure — there is no
     * separate "status"/"message" pair on this endpoint.
     */
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

            $customerName = null;
            if (is_array($verification)) {
                foreach (['customer_name', 'CustomerName', 'customerName', 'Customer_Name', 'name'] as $key) {
                    if (!empty($verification[$key])) {
                        $customerName = $verification[$key];
                        break;
                    }
                }
            }

            if (!empty($customerName) && !str_contains(strtoupper($customerName), 'INVALID')) {
                return response()->json([
                    'status'        => true,
                    'message'       => 'Verification successful',
                    'customer_name' => $customerName,
                ]);
            }

            $errorMsg = 'Invalid smartcard/IUC number. Please check and try again.';
            if (is_array($verification) && !empty($verification['message'])) {
                $errorMsg = $verification['message'];
            }

            return response()->json([
                'status'  => false,
                'message' => $errorMsg,
            ], 400);

        } catch (\Exception $e) {
            Log::error('Cable Smartcard Verification Error', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'cableTV' => $request->cableTV,
                'card'    => $request->smartCardNo,
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'We could not verify the smartcard at the moment. Please check your details or try again later.'
            ], 500);
        }
    }

    public function purchase(Request $request)
    {
        $data = $request->isJson() ? $request->json()->all() : $request->all();

        $validator = Validator::make($data, [
            'network'         => 'required|string',
            'package'         => 'required|string', // product_code
            'smartcardNumber' => 'required|string',
            // Nigerian local format: 0 followed by 10 digits (e.g. 08149659347)
            'phoneNumber'     => ['required', 'string', 'regex:/^0\d{10}$/'],
            'amount'          => 'required|numeric|min:1',
            'pin'             => 'required|string',
        ], [
            'phoneNumber.regex' => 'Please enter a valid 11-digit phone number starting with 0.',
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

        $cableTV     = $data['network'];       // lowercase ClubKonnect code, e.g. "dstv"
        $package     = $data['package'];       // product_code
        $smartCardNo = $data['smartcardNumber'];
        $phoneNo     = $data['phoneNumber'];
        $requestId   = (string) Str::uuid();

        // Step 1: Get product from DB (always trusted, never from client amount)
        $product = Product::where('product_code', $package)
            ->where('provider_id', 3) // CableTV provider_id
            ->first();

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid package selected.'
            ], 400);
        }

        $amount = (float) $product->client_price;

        // Step 2: Get wallet & check balance
        $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

        if (!$wallet || $wallet->balance < $amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient wallet balance.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            Log::info('Cable TV purchase request', [
                'user_id'      => auth()->id(),
                'cableTV'      => $cableTV,
                'package'      => $package,
                'card_masked'  => substr($smartCardNo, 0, 4) . '****',
                'amount'       => $amount,
                'phone'        => $phoneNo,
                'request_id'   => $requestId,
            ]);

            // Step 3: Call ClubKonnect API
            $response = ClubKonnectHelper::buyCableTV(
                $cableTV,
                $package,
                $smartCardNo,
                $phoneNo,
                $requestId
            );

            Log::info('Cable TV purchase raw response', [
                'user_id'    => auth()->id(),
                'request_id' => $requestId,
                'response'   => $response,
            ]);

            // Guard against ClubKonnect returning a plain error string/status
            // instead of an order object (e.g. MISSING_CABLETV, INVALID_SMARTCARDNO).
            $knownErrorStatuses = [
                'INVALID_CREDENTIALS', 'MISSING_CREDENTIALS', 'MISSING_USERID', 'MISSING_APIKEY',
                'MISSING_CABLETV', 'MISSING_PACKAGE', 'INVALID_SMARTCARDNO', 'PACKAGE_NOT_AVAILABLE',
            ];

            $statusCode = (string) ($response['statuscode'] ?? $response['StatusCode'] ?? '');
            $statusText = strtoupper((string) ($response['status'] ?? $response['Status'] ?? ''));

            // ClubKonnect never returns the literal "SUCCESS" here — an accepted
            // order comes back as statuscode 100 / status ORDER_RECEIVED.
            $accepted = !in_array($statusText, $knownErrorStatuses)
                && (
                    in_array($statusCode, ['100', '200'])
                    || in_array($statusText, ['ORDER_RECEIVED', 'ORDER_COMPLETED', 'SUCCESS'])
                );

            // Step 4: Store transaction
            $transaction = Transaction::create([
                'reference' => $requestId,
                'user_id'   => auth()->id(),
                'type'      => 'cable',
                'channel'   => 'ClubKonnect',
                'provider'  => $cableTV,
                'phone'     => $phoneNo,
                'amount'    => $amount,
                // Orders accepted with statuscode 100 are still pending on ClubKonnect's
                // side until the callback confirms ORDER_COMPLETED.
                'status'    => $accepted ? ($statusCode === '100' ? 'pending' : 'success') : 'failed',
                'response'  => json_encode($response),
            ]);

            // Step 5: Deduct balance only if accepted
            if ($accepted) {
                $wallet->decrement('balance', $amount);
                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => $statusCode === '100'
                        ? 'Order received and is being processed.'
                        : 'Cable TV purchase successful!',
                    'data' => [
                        'reference'      => $requestId,
                        'order_id'       => $response['orderid'] ?? $response['OrderID'] ?? '',
                        'package_name'   => $product->name,
                        'amount'         => $amount,
                        'new_balance'    => $wallet->fresh()->balance,
                        'transaction_id' => $transaction->id
                    ]
                ]);
            }

            DB::rollBack();
            $errorMessage = $response['message']
                ?? $response['Message']
                ?? $response['status']
                ?? $response['Status']
                ?? 'Purchase failed. Please try again.';

            return response()->json([
                'status' => false,
                'message' => $errorMessage
            ], 400);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Cable TV purchase exception', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.',
                'error'   => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Handle ClubKonnect's async callback for cable TV orders.
     * Must be public (no auth, no CSRF) — ClubKonnect's server calls it directly.
     */
    public function handleCallback(Request $request)
    {
        $orderId     = $request->input('orderid');
        $requestId   = $request->input('requestid');
        $statusCode  = (string) $request->input('statuscode');
        $orderStatus = strtoupper((string) $request->input('orderstatus'));

        Log::info('ClubKonnect cable TV callback received', [
            'orderid'     => $orderId,
            'requestid'   => $requestId,
            'statuscode'  => $statusCode,
            'orderstatus' => $orderStatus,
        ]);

        $transaction = $requestId
            ? Transaction::where('reference', $requestId)->where('type', 'cable')->first()
            : null;

        if (!$transaction) {
            Log::warning('ClubKonnect cable TV callback: no matching transaction', [
                'requestid' => $requestId,
                'orderid'   => $orderId,
            ]);
            return response()->json(['status' => 'ignored'], 200);
        }

        $isCompleted = in_array($statusCode, ['200']) || $orderStatus === 'ORDER_COMPLETED';
        $isFailed    = in_array($orderStatus, ['ORDER_FAILED', 'ORDER_CANCELED', 'ORDER_CANCELLED']);

        if ($isCompleted) {
            $transaction->update(['status' => 'success']);
        } elseif ($isFailed) {
            DB::transaction(function () use ($transaction) {
                if ($transaction->status !== 'failed') {
                    $wallet = Wallet::where('user_id', $transaction->user_id)->lockForUpdate()->first();
                    if ($wallet) {
                        $wallet->increment('balance', $transaction->amount);
                    }
                }
                $transaction->update(['status' => 'failed']);
            });
        }

        return response()->json(['status' => 'received'], 200);
    }

    public function jambshow()
    {
        $wallet = Wallet::where('user_id', auth()->id())->first();
        return view('users.jambPin', compact('wallet'));
    }

    public function jamb(Request $request)
    {
        $validExamTypes = ['utme', 'de'];

        $request->validate([
            'phone_number' => 'required|string|min:11|max:11',
            'exam_type' => 'sometimes|string|in:' . implode(',', $validExamTypes),
            'amount' => 'required|numeric|min:1000|max:10000',
            'pin' => 'required|string'
        ]);

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

        $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

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
                $wallet->decrement('balance', $amount);
                DB::commit();

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