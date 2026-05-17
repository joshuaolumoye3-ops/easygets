<?php

namespace App\Http\Controllers;

use App\Helpers\ClubKonnectHelper;
use App\Models\Wallet;
use Illuminate\Http\Request;
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

    /**
     * Verify meter number.
     * MeterType is now REQUIRED because the ClubKonnect API needs it.
     * The frontend must select meter type BEFORE triggering verification.
     */
    public function verifyElectricityMeter(Request $request)
    {
        $request->validate([
            'electricCompany' => 'required|string',
            'meterNo'         => 'required|string',
            'meterType'       => 'required|string', // e.g. "01" for Prepaid, "02" for Postpaid
        ]);

        try {
            $response = ClubKonnectHelper::verifyElectricityMeter(
                $request->electricCompany,
                $request->meterNo,
                $request->meterType   // <-- now forwarded to the API
            );

            // Normalize customer name from various possible response keys
            $customerName = null;
            if (is_array($response)) {
                foreach (['customer_name', 'CustomerName', 'customerName', 'Customer_Name', 'name'] as $key) {
                    if (!empty($response[$key])) {
                        $customerName = $response[$key];
                        break;
                    }
                }
            }

            // ClubKonnect returns "INVALID_METERNO" as the customer name on failure
            if (!empty($customerName) && strtolower($customerName) !== 'invalid_meterno') {
                return response()->json([
                    'status'        => true,
                    'message'       => 'Verification successful',
                    'customer_name' => $customerName,
                    'meter_number'  => $request->meterNo,
                ]);
            }

            // Extract a meaningful error message
            $errorMsg = 'Invalid meter number. Please check and try again.';
            if (is_array($response)) {
                if (!empty($response['message'])) $errorMsg = $response['message'];
                elseif (!empty($response['status']) && strtolower($response['status']) !== 'invalid_meterno') {
                    $errorMsg = $response['status'];
                }
            }

            return response()->json([
                'status'  => false,
                'message' => $errorMsg,
            ], 400);

        } catch (\Exception $e) {
            Log::error('Electricity meter verification exception', [
                'user_id'         => auth()->id(),
                'electric_company'=> $request->electricCompany,
                'meter_masked'    => substr($request->meterNo, 0, 4) . '****',
                'error'           => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while verifying the meter number. Please try again.',
            ], 500);
        }
    }

    /**
     * Fetch packages for a given electricity provider (helper for any AJAX needs).
     */
    public function getPackages(Request $request)
    {
        $request->validate([
            'electricCompany' => 'required|string',
        ]);

        try {
            $discos = ClubKonnectHelper::getElectricityDiscos();

            if (!is_array($discos) || count($discos) === 0) {
                return response()->json([
                    'status'   => false,
                    'packages' => [],
                    'message'  => 'No providers found. Please try again later.',
                ]);
            }

            $packages = [];
            foreach ($discos as $provider) {
                if (!is_array($provider)) continue;

                // Match by provider key or ELECTRIC_COMPANY field
                $providerKey = $provider['ELECTRIC_COMPANY'] ?? $provider['PROVIDER_KEY'] ?? $provider['KEY'] ?? null;
                if ((string)$providerKey !== (string)$request->electricCompany) continue;

                $products = $provider['PRODUCT'] ?? $provider['products'] ?? [];
                if (!is_array($products)) continue;

                foreach ($products as $product) {
                    if (!is_array($product)) continue;

                    $productType = $product['PRODUCT_TYPE'] ?? $product['PRODUCT_NAME'] ?? $product['type'] ?? 'Meter';
                    $productId   = $product['PRODUCT_ID'] ?? $product['PRODUCT_CODE'] ?? $product['PRODUCT_SNO'] ?? $product['ID'] ?? null;

                    $minAmt = $product['MINIMUM_AMOUNT'] ?? $product['MINIMUN_AMOUNT'] ?? $product['MINAMOUNT'] ?? $product['MIN_AMOUNT'] ?? null;
                    $maxAmt = $product['MAXIMUM_AMOUNT'] ?? $product['MAXAMOUNT'] ?? $product['MAX_AMOUNT'] ?? $product['MAX'] ?? null;

                    $minAmt = $minAmt !== null ? (float) preg_replace('/[^0-9.]/', '', (string)$minAmt) : 100;
                    $maxAmt = $maxAmt !== null ? (float) preg_replace('/[^0-9.]/', '', (string)$maxAmt) : 200000;

                    if (!$minAmt || $minAmt <= 0) $minAmt = 100;
                    if (!$maxAmt || $maxAmt <= 0) $maxAmt = 200000;

                    $packages[] = [
                        'id'   => $productId,
                        'type' => $productType,
                        'min'  => $minAmt,
                        'max'  => $maxAmt,
                    ];
                }

                break; // found provider, stop searching
            }

            if (count($packages) > 0) {
                return response()->json([
                    'status'   => true,
                    'packages' => $packages,
                    'message'  => 'Packages retrieved',
                ]);
            }

            return response()->json([
                'status'   => false,
                'packages' => [],
                'message'  => 'No packages found for this provider.',
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching electricity packages', [
                'error'   => $e->getMessage(),
                'company' => $request->electricCompany,
            ]);

            return response()->json([
                'status'   => false,
                'packages' => [],
                'message'  => 'Failed to fetch packages. Please try again.',
            ], 500);
        }
    }

    public function purchase(Request $request)
    {
        $validated = $request->validate([
            'electricCompany' => 'required|string',
            'package'         => 'required|string', // meter type code e.g. "01"
            'meterNumber'     => 'required|string',
            'amount'          => 'required|numeric|min:100',
            'pin'             => 'required|string|size:4',
        ]);

        // Verify transaction PIN before anything else
        try {
            PinHelper::verifyPin($request->pin);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->validator->errors()->first('pin'),
            ], 400);
        }

        // Lock wallet row to prevent race conditions
        $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

        if (!$wallet || $wallet->balance < $validated['amount']) {
            return response()->json([
                'status'  => false,
                'message' => 'Insufficient wallet balance. Your current balance is ₦' . number_format($wallet->balance ?? 0, 2),
            ], 400);
        }

        $requestId = Str::uuid()->toString();
        $phoneNo   = auth()->user()->phone ?? '';

        DB::beginTransaction();

        try {
            // Server-side min/max validation from provider product data
            $discos      = ClubKonnectHelper::getElectricityDiscos();
            $foundProduct = null;

            if (is_array($discos)) {
                foreach ($discos as $provider) {
                    $products = $provider['PRODUCT'] ?? $provider['products'] ?? [];
                    if (!is_array($products)) continue;
                    foreach ($products as $prod) {
                        $pid = $prod['PRODUCT_ID'] ?? $prod['PRODUCT_CODE'] ?? $prod['PRODUCT_SNO'] ?? $prod['ID'] ?? null;
                        if ($pid && (string)$pid === (string)$validated['package']) {
                            $foundProduct = $prod;
                            break 2;
                        }
                    }
                }
            }

            $minAllowed = 100;
            $maxAllowed = 200000;

            if ($foundProduct) {
                $rawMin = $foundProduct['MINIMUM_AMOUNT'] ?? $foundProduct['MINIMUN_AMOUNT'] ?? $foundProduct['MINAMOUNT'] ?? $foundProduct['MIN_AMOUNT'] ?? null;
                $rawMax = $foundProduct['MAXIMUM_AMOUNT'] ?? $foundProduct['MAXAMOUNT'] ?? $foundProduct['MAX_AMOUNT'] ?? $foundProduct['MAX'] ?? null;

                if ($rawMin !== null) {
                    $parsed = (float) preg_replace('/[^0-9.]/', '', (string)$rawMin);
                    if ($parsed > 0) $minAllowed = $parsed;
                }
                if ($rawMax !== null) {
                    $parsed = (float) preg_replace('/[^0-9.]/', '', (string)$rawMax);
                    if ($parsed > 0) $maxAllowed = $parsed;
                }
            }

            // Fallback to client-submitted limits only if product not found server-side
            if (!$foundProduct) {
                $clientMin = (float) $request->input('minAmount', 100);
                $clientMax = (float) $request->input('maxAmount', 200000);
                if ($clientMin > 0) $minAllowed = $clientMin;
                if ($clientMax > 0) $maxAllowed = $clientMax;
            }

            if ($validated['amount'] < $minAllowed) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Minimum amount for this meter type is ₦' . number_format($minAllowed, 2),
                ], 400);
            }

            if ($validated['amount'] > $maxAllowed) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Maximum amount for this meter type is ₦' . number_format($maxAllowed, 2),
                ], 400);
            }

            // Call ClubKonnect API
            $response = ClubKonnectHelper::buyElectricity(
                $validated['electricCompany'],
                $validated['package'],      // MeterType (e.g. "01")
                $validated['meterNumber'],
                $validated['amount'],
                $phoneNo,
                $requestId
            );

            // Determine success/failure
            // ClubKonnect uses statuscode: 100 = ORDER_RECEIVED, 200 = ORDER_COMPLETED
            $statusCode = (string) ($response['statuscode'] ?? $response['StatusCode'] ?? '');
            $statusText = strtoupper($response['status'] ?? $response['Status'] ?? '');
            $accepted   = in_array($statusCode, ['100', '200'])
                || in_array($statusText, ['ORDER_RECEIVED', 'ORDER_COMPLETED', 'SUCCESS']);

            // Record transaction regardless of outcome for reconciliation
            Transaction::create([
                'reference'    => $requestId,
                'user_id'      => auth()->id(),
                'type'         => 'electricity',
                'channel'      => 'ClubKonnect',
                'provider'     => $validated['electricCompany'],
                'phone'        => $phoneNo,
                'amount'       => $validated['amount'],
                'meter_number' => $validated['meterNumber'],
                'package'      => $validated['package'],
                'status'       => $accepted ? 'success' : 'failed',
                'response'     => json_encode($response),
            ]);

            if ($accepted) {
                $wallet->decrement('balance', $validated['amount']);
                DB::commit();

                return response()->json([
                    'status'  => true,
                    'message' => 'Electricity purchase successful.',
                    'data'    => [
                        'token'          => $response['metertoken'] ?? $response['token'] ?? '',
                        'order_id'       => $response['orderid'] ?? $response['OrderID'] ?? '',
                        'meter_number'   => $validated['meterNumber'],
                        'amount'         => $validated['amount'],
                        'company'        => $validated['electricCompany'],
                        'transaction_id' => $requestId,
                        'new_balance'    => $wallet->fresh()->balance,
                    ],
                ]);
            }

            DB::rollBack();

            $errorMessage = $response['message']
                ?? $response['Message']
                ?? $response['Status']
                ?? $response['status']
                ?? 'Electricity purchase failed. Please try again.';

            return response()->json([
                'status'  => false,
                'message' => $errorMessage,
            ], 400);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Electricity purchase exception', [
                'user_id'      => auth()->id(),
                'meter_masked' => isset($validated['meterNumber']) ? substr($validated['meterNumber'], 0, 4) . '****' : null,
                'amount'       => $validated['amount'] ?? null,
                'company'      => $validated['electricCompany'] ?? null,
                'error'        => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * Render success partial for AJAX display.
     */
    public function renderSuccessPartial(Request $request)
    {
        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'details' => 'sometimes|array',
        ]);

        return view('partials.success', [
            'title'   => $data['title'],
            'message' => $data['message'],
            'details' => $data['details'] ?? [],
        ])->render();
    }
}