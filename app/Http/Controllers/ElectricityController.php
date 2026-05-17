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
        $plans = ClubKonnectHelper::getElectricityDiscos();
        if (!is_array($plans)) {
            $plans = [];
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
           
            // Normalize customer name from different possible response keys
            $customerName = null;
            if (is_array($response)) {
                if (isset($response['customer_name'])) $customerName = $response['customer_name'];
                elseif (isset($response['CustomerName'])) $customerName = $response['CustomerName'];
                elseif (isset($response['customerName'])) $customerName = $response['customerName'];
                elseif (isset($response['Customer_Name'])) $customerName = $response['Customer_Name'];
                else {
                    foreach ($response as $k => $v) {
                        if (strtolower($k) === 'customer_name' || strtolower($k) === 'customername' || strtolower($k) === 'customer') {
                            $customerName = $v;
                            break;
                        }
                    }
                }
            }

            if (!empty($customerName) && strtolower($customerName) !== 'invalid_meterno') {
                return response()->json([
                    'status' => true,
                    'message' => 'Verification successful',
                    'customer_name' => $customerName,
                    'meter_number' => $request->meterNo
                ]);
            }

            $errorMsg = 'Invalid meter number';
            if (is_array($response)) {
                if (isset($response['message'])) $errorMsg = $response['message'];
                elseif (isset($response['status'])) $errorMsg = $response['status'];
            }

            return response()->json([
                'status' => false,
                'message' => $errorMsg
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while verifying the meter number'
            ], 500);
        }
    }

    /**
     * Fetch packages for a given electricity provider
     */
    public function getPackages(Request $request)
    {
        $request->validate([
            'electricCompany' => 'required|string'
        ]);

        try {
            $discos = ClubKonnectHelper::getElectricityDiscos();
            
            if (!is_array($discos) || count($discos) === 0) {
                return response()->json([
                    'status' => false,
                    'packages' => [],
                    'message' => 'No discos found'
                ]);
            }

            $packages = [];
            foreach ($discos as $provider) {
                if (!is_array($provider)) continue;
                
                $providerKey = $provider['PROVIDER_KEY'] ?? $provider['KEY'] ?? null;
                if (!$providerKey || (string)$providerKey !== (string)$request->electricCompany) continue;

                $products = $provider['PRODUCT'] ?? ($provider['products'] ?? []);
                if (!is_array($products)) continue;

                foreach ($products as $product) {
                    if (!is_array($product)) continue;

                    $productType = $product['PRODUCT_TYPE'] ?? $product['PRODUCT_NAME'] ?? $product['type'] ?? 'Meter';
                    $productId = $product['PRODUCT_ID'] ?? $product['PRODUCT_CODE'] ?? $product['PRODUCT_SNO'] ?? $product['ID'] ?? null;
                    
                    $minAmt = $product['MINIMUM_AMOUNT'] ?? $product['MINIMUN_AMOUNT'] ?? $product['MINAMOUNT'] ?? $product['MIN'] ?? null;
                    $maxAmt = $product['MAXIMUM_AMOUNT'] ?? $product['MAXAMOUNT'] ?? $product['MAX'] ?? null;

                    // Normalize to float
                    $minAmt = $minAmt !== null ? floatval(preg_replace('/[^0-9.]/', '', (string)$minAmt)) : null;
                    $maxAmt = $maxAmt !== null ? floatval(preg_replace('/[^0-9.]/', '', (string)$maxAmt)) : null;

                    if (!$minAmt || isnan($minAmt) || $minAmt <= 0) $minAmt = 100;
                    if (!$maxAmt || isnan($maxAmt) || $maxAmt <= 0) $maxAmt = 200000;

                    $packages[] = [
                        'id' => $productId,
                        'type' => $productType,
                        'min' => $minAmt,
                        'max' => $maxAmt,
                    ];
                }

                if (count($packages) > 0) {
                    return response()->json([
                        'status' => true,
                        'packages' => $packages,
                        'message' => 'Packages retrieved'
                    ]);
                }
            }

            return response()->json([
                'status' => false,
                'packages' => [],
                'message' => 'No packages found for this provider'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching electricity packages', [
                'error' => $e->getMessage(),
                'company' => $request->electricCompany
            ]);
            return response()->json([
                'status' => false,
                'packages' => [],
                'message' => 'Failed to fetch packages'
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
            // Server-side: attempt to find package min/max from provider products
            $discos = ClubKonnectHelper::getElectricityDiscos();
            $foundProduct = null;
            if (is_array($discos) && count($discos) > 0) {
                foreach ($discos as $provider) {
                    $products = $provider['PRODUCT'] ?? ($provider['products'] ?? []);
                    if (!is_array($products)) continue;
                    foreach ($products as $prod) {
                        $pid = $prod['PRODUCT_ID'] ?? $prod['PRODUCT_CODE'] ?? $prod['PRODUCT_SNO'] ?? ($prod['ID'] ?? null);
                        if ($pid && (string)$pid === (string)$validated['package']) {
                            $foundProduct = $prod;
                            break 2;
                        }
                    }
                }
            }

            // Extract min/max
            $minFromProd = null;
            $maxFromProd = null;
            if ($foundProduct) {
                $minFromProd = $foundProduct['MINIMUM_AMOUNT'] ?? $foundProduct['MINIMUN_AMOUNT'] ?? $foundProduct['MINAMOUNT'] ?? $foundProduct['MIN'] ?? null;
                $maxFromProd = $foundProduct['MAXIMUM_AMOUNT'] ?? $foundProduct['MAXAMOUNT'] ?? $foundProduct['MAX'] ?? null;
                // normalize
                $minFromProd = $minFromProd !== null ? floatval(preg_replace('/[^0-9.]/', '', (string)$minFromProd)) : null;
                $maxFromProd = $maxFromProd !== null ? floatval(preg_replace('/[^0-9.]/', '', (string)$maxFromProd)) : null;
            }

            // final min/max to enforce
            $minAllowed = $minFromProd ?? floatval($request->input('minAmount', 100));
            $maxAllowed = $maxFromProd ?? floatval($request->input('maxAmount', 200000));

            if ($validated['amount'] < $minAllowed) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => "Minimum amount for this product is ₦".number_format($minAllowed, 2)], 400);
            }
            if ($validated['amount'] > $maxAllowed) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => "Maximum amount for this product is ₦".number_format($maxAllowed, 2)], 400);
            }

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

            // Determine success from various API formats
            $statusCode = $response['statuscode'] ?? $response['StatusCode'] ?? null;
            $statusText = strtoupper($response['status'] ?? $response['Status'] ?? '');
            $accepted = false;

            if ($statusCode) {
                // Nellobyte returns 100 for ORDER_RECEIVED, 200 for completed
                if (in_array((string)$statusCode, ['100', '200'])) {
                    $accepted = true;
                }
            }

            if (!$accepted && $statusText) {
                if (in_array($statusText, ['ORDER_RECEIVED', 'ORDER_COMPLETED', 'SUCCESS'])) $accepted = true;
            }

            if ($accepted) {
                // Deduct amount from wallet
                $wallet->decrement('balance', $validated['amount']);
                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Electricity purchase successful.',
                    'data' => [
                        'token' => $response['metertoken'] ?? ($response['token'] ?? ''),
                        'meter_number' => $validated['meterNumber'],
                        'amount' => $validated['amount'],
                        'company' => $validated['electricCompany'],
                        'transaction_id' => $requestId,
                        'new_balance' => $wallet->fresh()->balance
                    ]
                ]);
            } else {
                DB::rollBack();
                $errorMessage = $response['message'] ?? $response['Status'] ?? $response['status'] ?? 'Electricity purchase failed.';

                return response()->json([
                    'status' => false,
                    'message' => $errorMessage
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Electricity Purchase Error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'meter_number' => $validated['meterNumber'] ?? null,
                'amount' => $validated['amount'] ?? null,
                'company' => $validated['electricCompany'] ?? null,
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
