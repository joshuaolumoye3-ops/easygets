<?php

namespace App\Http\Controllers;

use App\Helpers\ClubKonnectHelper;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Helpers\DemmyPayHelper;
use App\Helpers\PinHelper;
use App\Models\Product;
use App\Models\Provider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DataController extends Controller
{
public function showData()
{
    $providerId = 1;

    // Get provider and its products
    $provider = Provider::with('products')->find($providerId);
    
    if (!$provider) {
        return response()->json(['error' => 'Provider not found'], 404);
    }

    $structured = [];

    foreach ($provider->products as $product) {
        // Detect network name from product name
        $network = null;
        if (stripos($product->name, 'MTN') !== false) {
            $network = 'MTN';
            $id = '01';
        } elseif (stripos($product->name, 'GLO') !== false) {
            $network = 'Glo';
            $id = '02';
        } elseif (stripos($product->name, 'Airtel') !== false) {
            $network = 'Airtel';
            $id = '03';
        } elseif (stripos($product->name, '9mobile') !== false || stripos($product->name, 'Etisalat') !== false) {
            $network = 'm_9mobile';
            $id = '04';
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

            // Detect plan type
            $type = $this->detectPlanType($product->name);

            $structured[$network][0]['PRODUCT'][] = [
                'PRODUCT_SNO'   => $product->product_sno,
                'PRODUCT_CODE'  => $product->product_code,
                'PRODUCT_ID'    => $product->product_id,
                'PRODUCT_NAME'  => $product->name,
                'PRODUCT_AMOUNT'=> $product->client_price,
                'PRODUCT_TYPE'  => $type,
            ];
        }
    }
    $plans = $structured;
    $wallet = Wallet::where('user_id', auth()->id())->first();

    return view('users.data', compact('plans', 'wallet'));
}

/**
 * Detect plan type from product name
 */
private function detectPlanType($productName)
{
    $name = strtolower($productName);
    
    if (strpos($name, '365 day') !== false || strpos($name, '12 months') !== false) {
        return 'Yearly';
    } elseif (strpos($name, '120 day') !== false || strpos($name, '4 months') !== false) {
        return 'Quarterly';
    } elseif (strpos($name, '90 day') !== false || strpos($name, '3 months') !== false) {
        return 'Quarterly';
    } elseif (strpos($name, '60 day') !== false) {
        return 'Bi-Monthly';
    } elseif (strpos($name, '30 day') !== false || strpos($name, 'monthly') !== false) {
        return 'Monthly';
    } elseif (strpos($name, '7 day') !== false || strpos($name, 'weekly') !== false) {
        return 'Weekly';
    } elseif (strpos($name, '3 day') !== false) {
        return '3-Day';
    } elseif (strpos($name, '2 day') !== false) {
        return '2-Day';
    } elseif (strpos($name, '1 day') !== false || strpos($name, 'daily') !== false) {
        return 'Daily';
    }
    
    return 'Other';
}

    /**
     * Get network ID mapping for compatibility
     */
    private function getNetworkId($network)
    {
        $networkIds = [
            'MTN' => '01',
            'Glo' => '02',
            'Airtel' => '03',
            'm_9mobile' => '04'
        ];
        
        return $networkIds[$network] ?? '00';
    }

    public function purchase(Request $request)
    {
        $validated = $request->validate([
            'network'   => 'required|string|in:mtn,glo,airtel,9mobile,MTN,GLO,AIRTEL,9MOBILE',
            'data_plan' => 'required|string',
            'recipient' => 'required|digits:11',
            'pin'       => 'required|string|size:4',
        ]);

        $network    = null;
        $productId  = null;
        $recipient  = null;
        $amount     = null;
        $product    = null;
        $wallet     = null;

        try {
            // ✅ Initialize transaction variables
            DB::beginTransaction();

            // ✅ Verify user PIN first
            try {
                PinHelper::verifyPin($validated['pin']);
            } catch (ValidationException $e) {
                DB::rollBack();
                $pinError = $e->validator->errors()->first('pin') ?? 'Invalid PIN. Please try again.';
                
                \Log::warning('Data purchase - Invalid PIN', [
                    'user_id' => auth()->id(),
                    'recipient' => $validated['recipient']
                ]);
                
                return response()->json([
                    'success' => false,
                    'title' => 'Invalid PIN',
                    'message' => $pinError,
                    'error_type' => 'validation',
                    'suggestion' => 'Please enter your 4-digit transaction PIN.',
                    'details' => []
                ], 422);
            }

            $network    = strtolower($validated['network']);
            $productId  = $validated['data_plan'];
            $recipient  = $validated['recipient'];

            // ✅ Generate reference (max 100 chars)
            $requestId  = 'DATA_' . Str::uuid();

            // ✅ Fetch product from DB (not from API)
            $product = Product::where('product_code', $productId)
                ->where('is_active', 1)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                DB::rollBack();
                
                \Log::warning('Data purchase - Product not found', [
                    'user_id' => auth()->id(),
                    'product_code' => $productId
                ]);
                
                return response()->json([
                    'success' => false,
                    'title' => 'Package Not Found',
                    'message' => 'The selected data package is no longer available. Please select another package.',
                    'error_type' => 'validation',
                    'details' => []
                ], 400);
            }

            $amount = (float) ($product->client_price ?? 0);
            if ($amount <= 0) {
                DB::rollBack();
                
                \Log::error('Data purchase - Invalid product price', [
                    'user_id' => auth()->id(),
                    'product_id' => $product->id,
                    'price' => $amount
                ]);
                
                return response()->json([
                    'success' => false,
                    'title' => 'Invalid Amount',
                    'message' => 'Unable to process this data package at this time. The pricing information is incorrect. Please try again later.',
                    'error_type' => 'system_error',
                    'details' => []
                ], 400);
            }

            // ✅ Fetch and lock user wallet
            $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();
            if (!$wallet) {
                DB::rollBack();
                
                \Log::error('Data purchase - Wallet not found', [
                    'user_id' => auth()->id()
                ]);
                
                return response()->json([
                    'success' => false,
                    'title' => 'Wallet Error',
                    'message' => 'Your wallet could not be accessed. Please contact support.',
                    'error_type' => 'system_error',
                    'details' => []
                ], 500);
            }

            if ($wallet->balance < $amount) {
                DB::rollBack();
                
                $shortfall = $amount - $wallet->balance;
                
                \Log::info('Data purchase - Insufficient balance', [
                    'user_id' => auth()->id(),
                    'balance' => $wallet->balance,
                    'required_amount' => $amount,
                    'shortfall' => $shortfall
                ]);
                
                return response()->json([
                    'success' => false,
                    'title' => 'Insufficient Balance',
                    'message' => 'Your wallet balance is not enough for this purchase.',
                    'error_type' => 'insufficient_balance',
                    'details' => [
                        'Required' => '₦' . number_format($amount, 2),
                        'Available Balance' => '₦' . number_format($wallet->balance, 2),
                        'Shortfall' => '₦' . number_format($shortfall, 2)
                    ],
                    'suggestion' => 'Please fund your wallet with at least ₦' . number_format($shortfall, 2) . ' to complete this purchase.'
                ], 400);
            }

            // ✅ Call DemmyPay data purchase API
            $response = DemmyPayHelper::buyData($product->product_code, $recipient, $requestId);

            $networkNames = [
                'mtn' => 'MTN',
                'glo' => 'Glo',
                'airtel' => 'Airtel',
                '9mobile' => '9Mobile'
            ];
            $networkName = $networkNames[$network] ?? ucfirst($network);

            // ✅ Check API response - Handle both successful and failed responses
            $isApiSuccess = isset($response['status']) && (int)$response['status'] === 200;

            if ($isApiSuccess) {
                // ✅ API was successful - Deduct wallet
                $wallet->decrement('balance', $amount);

                // ✅ Save transaction record with success status
                $transaction = Transaction::create([
                    'reference' => substr($requestId, 0, 100),
                    'user_id'   => auth()->id(),
                    'type'      => 'data',
                    'channel'   => 'DemmyPay',
                    'provider'  => $networkName,
                    'phone'     => $recipient,
                    'recipient' => $recipient,
                    'amount'    => $amount,
                    'status'    => 'success',
                    'response'  => substr(json_encode($response, JSON_UNESCAPED_SLASHES), 0, 65535),
                ]);

                DB::commit();

                $successMessage = "Your {$networkName} data bundle ({$product->name}) has been successfully purchased and delivered to {$recipient}!";

                \Log::info('Data purchase - Success', [
                    'user_id' => auth()->id(),
                    'transaction_id' => $transaction->id,
                    'network' => $networkName,
                    'amount' => $amount,
                    'recipient' => $recipient
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'data' => [
                        'transaction_reference' => $requestId,
                        'network' => $networkName,
                        'recipient' => $recipient,
                        'package_name' => $product->name ?? 'Data Bundle',
                        'amount' => $amount,
                        'formatted_amount' => '₦' . number_format($amount, 2),
                        'new_balance' => $wallet->fresh()->balance,
                        'formatted_balance' => '₦' . number_format($wallet->fresh()->balance, 2),
                        'transaction_time' => now()->format('Y-m-d H:i:s'),
                        'transaction_id' => $transaction->id,
                    ],
                ]);
            } else {
                // ✅ API Failed - DO NOT deduct wallet
                DB::rollBack();

                // Determine error message based on API response
                $errorMessage = null;
                $errorDetails = [];

                // Try to extract specific error information
                if (!empty($response['detail'])) {
                    $errorMessage = $response['detail'];
                } elseif (!empty($response['message'])) {
                    $errorMessage = $response['message'];
                } elseif (!empty($response['error'])) {
                    $errorMessage = $response['error'];
                }

                // Check if it's a user validation error or API/system error
                $isValidationError = false;
                if ($errorMessage) {
                    $validationKeywords = ['invalid', 'not found', 'does not exist', 'incorrect', 'invalid phone'];
                    foreach ($validationKeywords as $keyword) {
                        if (stripos($errorMessage, $keyword) !== false) {
                            $isValidationError = true;
                            break;
                        }
                    }
                }

                // Log the failure
                \Log::warning('Data purchase - API failed', [
                    'user_id' => auth()->id(),
                    'network' => $networkName,
                    'recipient' => $recipient,
                    'product_id' => $product->id,
                    'amount' => $amount,
                    'api_response' => $response,
                    'api_status' => $response['status'] ?? 'unknown'
                ]);

                // Save transaction record with failed status (for audit trail)
                $failedTransaction = Transaction::create([
                    'reference' => substr($requestId, 0, 100),
                    'user_id'   => auth()->id(),
                    'type'      => 'data',
                    'channel'   => 'DemmyPay',
                    'provider'  => $networkName,
                    'phone'     => $recipient,
                    'recipient' => $recipient,
                    'amount'    => $amount,
                    'status'    => 'failed',
                    'response'  => substr(json_encode($response, JSON_UNESCAPED_SLASHES), 0, 65535),
                ]);

                // Return appropriate error message
                if ($isValidationError) {
                    // User validation error
                    return response()->json([
                        'success' => false,
                        'title' => 'Invalid Details',
                        'message' => 'The phone number or network details you provided may be incorrect. Please verify and try again.',
                        'error_type' => 'validation',
                        'details' => [
                            'Network' => $networkName,
                            'Phone Number' => $recipient,
                            'Package' => $product->name
                        ],
                        'suggestion' => 'Please check that the phone number is correct for the selected network.'
                    ], 400);
                } else {
                    // API/System error - show user-friendly message
                    return response()->json([
                        'success' => false,
                        'title' => 'Service Temporarily Unavailable',
                        'message' => 'We\'re experiencing technical difficulties with the data service right now. Your funds have not been deducted. Please try again in a few moments.',
                        'error_type' => 'service_error',
                        'details' => [
                            'Network' => $networkName,
                            'Package' => $product->name,
                            'Amount' => '₦' . number_format($amount, 2)
                        ],
                        'suggestion' => 'If the problem persists, please contact our support team. We apologize for the inconvenience.'
                    ], 503);
                }
            }

        } catch (ValidationException $e) {
            DB::rollBack();
            
            \Log::warning('Data purchase - Validation error', [
                'user_id' => auth()->id(),
                'errors' => $e->validator->errors()->all()
            ]);
            
            return response()->json([
                'success' => false,
                'title' => 'Invalid Information',
                'message' => 'Please check all the details you provided and try again.',
                'error_type' => 'validation',
                'details' => []
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            // Log the unexpected error
            \Log::error('Data purchase - Unexpected error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'network' => $network ?? 'unknown',
                'recipient' => $recipient ?? 'unknown',
                'amount' => $amount ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);

            // Return generic error message to user
            return response()->json([
                'success' => false,
                'title' => 'Something Went Wrong',
                'message' => 'We encountered an unexpected error while processing your request. Your funds have not been deducted.',
                'error_type' => 'system_error',
                'details' => [],
                'suggestion' => 'Please try again later. If you continue to experience issues, please contact our support team.'
            ], 500);
        }
    }

/**
 * Render success partial for AJAX requests (if not already in controller)
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
