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

        try {
            // ✅ Verify user PIN first
            PinHelper::verifyPin($validated['pin']);

            $network    = strtolower($validated['network']);
            $productId  = $validated['data_plan'];
            $recipient  = $validated['recipient'];

            // ✅ Generate reference (max 100 chars)
            $requestId  = 'DATA_' . Str::uuid();

            // ✅ Fetch product from DB (not from API)
            $product = Product::where('product_code', $productId)
                ->where('is_active', 1)
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid data plan selected.',
                    'error_type' => 'validation',
                ], 400);
            }

            $amount = (float) ($product->client_price ?? 0);
            if ($amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid amount for selected data plan.',
                    'error_type' => 'validation',
                ], 400);
            }

            // ✅ Fetch and lock user wallet
            $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();
            if (!$wallet || $wallet->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance. Please fund your account.',
                    'error_type' => 'insufficient_balance',
                ], 400);
            }

            DB::beginTransaction();

            // ✅ Call DemmyPay data purchase API
            $response = DemmyPayHelper::buyData($product->product_code, $recipient, $requestId);

            $networkNames = [
                'mtn' => 'MTN',
                'glo' => 'Glo',
                'airtel' => 'Airtel',
                '9mobile' => '9Mobile'
            ];
            $networkName = $networkNames[$network] ?? ucfirst($network);

            // ✅ Save transaction record
            $transaction = Transaction::create([
                'reference' => substr($requestId, 0, 100),
                'user_id'   => auth()->id(),
                'type'      => 'data',
                'channel'   => 'DemmyPay',
                'provider'  => $networkName,
                'phone'     => $recipient,
                'recipient' => $recipient,
                'amount'    => $amount,
                'status'    => isset($response['status']) && $response['status'] == 200 ? 'success' : 'failed',
                'response'  => substr(json_encode($response, JSON_UNESCAPED_SLASHES), 0, 65535), // safely truncated
            ]);

            if (isset($response['status']) && (int)$response['status'] === 200) {
                // ✅ Deduct wallet on success
                $wallet->decrement('balance', $amount);
                DB::commit();

                $successMessage = "Your {$networkName} data purchase of ₦" . number_format($amount, 2) .
                                " ({$product->name}) to {$recipient} was successful!";

                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'data' => [
                        'transaction_reference' => $requestId,
                        'network' => $networkName,
                        'network_code' => $network,
                        'recipient' => $recipient,
                        'package_name' => $product->name ?? 'Data Bundle',
                        'amount' => $amount,
                        'formatted_amount' => '₦' . number_format($amount, 2),
                        'new_balance' => $wallet->fresh()->balance,
                        'formatted_balance' => '₦' . number_format($wallet->fresh()->balance, 2),
                        'transaction_time' => now()->format('Y-m-d H:i:s'),
                        'provider_response' => $response['data'] ?? null,
                    ],
                    'transaction_id' => $transaction->id,
                ]);
            } else {
                DB::rollBack();

                $errorMessage = $response['detail'] 
                    ?? $response['message'] 
                    ?? $response['error'] 
                    ?? 'Data purchase failed';

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error_code' => $response['status'] ?? 'unknown',
                    'provider_response' => $response,
                ], 400);
            }

        } catch (ValidationException $e) {
            DB::rollBack();
            $pinError = $e->validator->errors()->first('pin');
            
            if ($pinError) {
                return response()->json([
                    'success' => false,
                    'message' => $pinError,
                    'error_type' => 'pin_validation',
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'error_type' => 'validation',
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Data purchase error', [
                'user_id' => auth()->id(),
                'amount' => $amount ?? null,
                'recipient' => $recipient ?? null,
                'network' => $network ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again.',
                'error_type' => 'system_error',
                // 'debug_message' => app()->environment('local') ? $e->getMessage() : null, // removed for production
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
