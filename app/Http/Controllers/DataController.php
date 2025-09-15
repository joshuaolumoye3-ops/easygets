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
use Illuminate\Validation\ValidationException;

class DataController extends Controller
{
    public function showData()
    {
        try {
            // Use DemmyPayHelper to get data products with proper authentication
            $response = DemmyPayHelper::getDataProducts();
            
            // Log the full response for debugging
            \Log::info('DemmyPay Full Response:', ['response' => $response]);
            
            $products = $response['data'] ?? [];
            
            \Log::info('DemmyPay Products Count:', ['count' => count($products)]);

            $plans = [];

            // Transform Demmy Global API response to Clubkonnect structure
            $networkMap = [
                'MTN' => 'MTN',
                'GLO' => 'Glo',
                'AIRTEL' => 'Airtel',
                '9MOBILE' => 'm_9mobile'
            ];

            // Group products by network
            $groupedProducts = [];
            foreach ($products as $product) {
                \Log::info('Processing Product:', ['product' => $product]);
                
                $networkName = strtoupper($product['network']['name'] ?? '');
                $mappedNetwork = $networkMap[$networkName] ?? $networkName;
                
                if (!isset($groupedProducts[$mappedNetwork])) {
                    $groupedProducts[$mappedNetwork] = [];
                }
                
                $productName = ($product['name'] ?? 'Unknown') . 
                            ' - ' . ($product['size'] ?? 'N/A') . 
                            ' (' . ($product['validity'] ?? 'N/A') . ')';
                
                $groupedProducts[$mappedNetwork][] = [
                    'PRODUCT_SNO' => (string)($product['id'] ?? ''),
                    'PRODUCT_CODE' => (string)($product['id'] ?? ''),
                    'PRODUCT_ID' => (string)($product['amount'] ?? '0'),
                    'PRODUCT_NAME' => $productName,
                    'PRODUCT_AMOUNT' => (string)($product['amount'] ?? '0')
                ];
            }

            \Log::info('Grouped Products:', ['grouped' => $groupedProducts]);

            // Transform to final Clubkonnect structure
            foreach ($groupedProducts as $network => $networkProducts) {
                $plans[$network] = [
                    [
                        'ID' => $this->getNetworkId($network),
                        'PRODUCT' => $networkProducts
                    ]
                ];
            }

            \Log::info('Final Plans Structure:', ['plans' => $plans]);
            
        } catch (\Exception $e) {
            // Log detailed error information
            \Log::error('DemmyPay API Error Details:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $plans = [];
        }

        $wallet = Wallet::where('user_id', auth()->id())->first();
        return view('users.data', compact('plans', 'wallet'));
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
        // Verify PIN before processing
        PinHelper::verifyPin($validated['pin']);

        $network    = strtolower($validated['network']);
        $productId  = $validated['data_plan'];
        $recipient  = $validated['recipient'];
        $requestId  = 'DATA_' . uniqid();

        // Always fetch amount from Demmy API
        $productsResponse = DemmyPayHelper::getDataProducts();
        $products = $productsResponse['data'] ?? [];
        $product = collect($products)->firstWhere('id', (int)$productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data plan selected.',
                'error_type' => 'validation',
            ], 400);
        }

        $amount = (float) ($product['amount'] ?? 0);
        
        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid amount for selected data plan.',
                'error_type' => 'validation',
            ], 400);
        }

        // Get user wallet
        $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();
        if (!$wallet || $wallet->balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance. Please fund your account.',
                'error_type' => 'insufficient_balance',
            ], 400);
        }

        DB::beginTransaction();

        // Call DemmyPay API
        $response = DemmyPayHelper::buyData($productId, $recipient, $requestId);
        dd($response);
        // Get network name for response
        $networkNames = [
            'mtn' => 'MTN',
            'glo' => 'Glo',
            'airtel' => 'Airtel',
            '9mobile' => '9Mobile'
        ];
        $networkName = $networkNames[$network] ?? ucfirst($network);

        // Save transaction
        $transaction = Transaction::create([
            'reference' => $requestId,
            'user_id'   => auth()->id(),
            'type'      => 'data',
            'channel'   => 'DemmyPay',
            'provider'  => $networkName,
            'phone'     => $recipient,
            'amount'    => $amount,
            'status'    => (isset($response['status']) && $response['status'] == 200) ? 'success' : 'failed',
            'response'  => json_encode($response),
        ]);

        if (isset($response['status']) && $response['status'] == 200) {
            // Deduct wallet
            $wallet->decrement('balance', $amount);
            DB::commit();

            // Format success message with transaction details
            $successMessage = "Your {$networkName} data purchase of ₦" . number_format($amount, 2) . 
                            " ({$product['name']}) to {$recipient} was successful!";

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'data' => [
                    'transaction_reference' => $requestId,
                    'network' => $networkName,
                    'network_code' => $network,
                    'recipient' => $recipient,
                    'package_name' => $product['name'] ?? 'Data Bundle',
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
            
            // Get more specific error message from provider response
            $errorMessage = 'Data purchase failed';
            if (isset($response['detail'])) {
                $errorMessage = $response['detail'];
            } elseif (isset($response['message'])) {
                $errorMessage = $response['message'];
            } elseif (isset($response['error'])) {
                $errorMessage = $response['error'];
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error_code' => $response['status'] ?? 'unknown',
                'provider_response' => $response,
            ], 400);
        }

    } catch (ValidationException $e) {
        DB::rollBack();
        // Handle PIN validation errors specifically
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
        
        // Log the error for debugging
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
            'debug_message' => app()->environment('local') ? $e->getMessage() : null,
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
