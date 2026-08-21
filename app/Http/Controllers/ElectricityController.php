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
        $rawPlans = ClubKonnectHelper::getElectricityDiscos();
        $plans    = $this->normalizePlans($rawPlans);
        $wallet   = Wallet::where('user_id', auth()->id())->first();

        return view('users.electricity', compact('plans', 'wallet'));
    }

    private function normalizePlans(array $rawPlans): array
    {
        if (empty($rawPlans)) {
            return [];
        }

        $normalized = [];

        foreach ($rawPlans as $key => $companyWrapper) {

            if (!is_array($companyWrapper)) {
                continue;
            }

            $company = isset($companyWrapper[0]) ? $companyWrapper[0] : $companyWrapper;

            if (!is_array($company)) {
                continue;
            }

            $code = trim((string) ($company['ID'] ?? ''));
            if ($code === '') {
                continue;
            }

            $name = trim((string) ($company['NAME'] ?? $key));

            $rawProducts = $company['PRODUCT'] ?? $company['PRODUCTS'] ?? [];

            $products = $this->normalizeProducts((array) $rawProducts);

            $normalized[] = [
                'code'     => $code,
                'name'     => $name,
                'products' => $products,
            ];
        }

        return $normalized;
    }

    private function normalizeProducts(array $rawProducts): array
    {
        if (!empty($rawProducts) && isset($rawProducts['PRODUCT_ID'])) {
            $rawProducts = [$rawProducts];
        }

        $products = [];

        foreach ($rawProducts as $product) {
            if (!is_array($product)) {
                continue;
            }

            $id = trim((string) ($product['PRODUCT_ID'] ?? $product['ID'] ?? ''));
            if ($id === '') {
                continue;
            }

            $type = ucfirst(strtolower(
                $product['PRODUCT_TYPE']
                ?? $product['PRODUCT_NAME']
                ?? $product['TYPE']
                ?? 'Meter'
            ));

            $min = (float) ($product['MINAMOUNT'] ?? $product['MIN_AMOUNT'] ?? $product['MINIMUM_AMOUNT'] ?? 100);
            $max = (float) ($product['MAXAMOUNT'] ?? $product['MAX_AMOUNT'] ?? $product['MAXIMUM_AMOUNT'] ?? 200000);

            $products[] = [
                'id'   => $id,
                'type' => $type,
                'min'  => $min > 0 ? $min : 100,
                'max'  => $max > 0 ? $max : 200000,
            ];
        }

        return $products;
    }

    private function extractCompanyCode(array $company): ?string
    {
        $id = trim((string) ($company['ID'] ?? ''));
        if ($id !== '') return $id;

        foreach (['ELECTRIC_COMPANY', 'COMPANY_CODE', 'CODE', 'DISCO_CODE'] as $field) {
            $val = trim((string) ($company[$field] ?? ''));
            if ($val !== '') return $val;
        }

        return null;
    }

    public function verifyElectricityMeter(Request $request)
    {
        $request->validate([
            'electricCompany' => 'required|string',
            'meterNo'         => 'required|string',
            'meterType'       => 'required|string',
        ]);

        try {
            $response = ClubKonnectHelper::verifyElectricityMeter(
                $request->electricCompany,
                $request->meterNo,
                $request->meterType
            );

            $customerName = null;
            if (is_array($response)) {
                foreach (['customer_name', 'CustomerName', 'customerName', 'Customer_Name', 'name'] as $key) {
                    if (!empty($response[$key])) {
                        $customerName = $response[$key];
                        break;
                    }
                }
            }

            if (!empty($customerName) && strtolower($customerName) !== 'invalid_meterno') {
                return response()->json([
                    'status'        => true,
                    'message'       => 'Verification successful',
                    'customer_name' => $customerName,
                    'meter_number'  => $request->meterNo,
                ]);
            }

            $errorMsg = 'Invalid meter number. Please check and try again.';
            if (is_array($response)) {
                if (!empty($response['message'])) {
                    $errorMsg = $response['message'];
                }
            }

            return response()->json([
                'status'  => false,
                'message' => $errorMsg,
            ], 400);

        } catch (\Exception $e) {
            Log::error('Electricity meter verification exception', [
                'user_id'          => auth()->id(),
                'electric_company' => $request->electricCompany,
                'meter_masked'     => substr($request->meterNo, 0, 4) . '****',
                'error'            => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while verifying the meter number. Please try again.',
            ], 500);
        }
    }

    public function getPackages(Request $request)
    {
        $request->validate([
            'electricCompany' => 'required|string',
        ]);

        try {
            $rawPlans = ClubKonnectHelper::getElectricityDiscos();
            $plans    = $this->normalizePlans($rawPlans);

            $match = collect($plans)->firstWhere('code', $request->electricCompany);

            if ($match && !empty($match['products'])) {
                return response()->json([
                    'status'   => true,
                    'packages' => $match['products'],
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
            'package'         => 'required|string',
            'meterNumber'     => 'required|string',
            // Nigerian local format: 0 followed by 10 digits (e.g. 08149659347)
            'phoneNo'         => ['required', 'string', 'regex:/^0\d{10}$/'],
            'amount'          => 'required|numeric|min:100',
            'pin'             => 'required|string|size:4',
        ], [
            'phoneNo.regex' => 'Please enter a valid 11-digit phone number starting with 0.',
        ]);

        try {
            PinHelper::verifyPin($request->pin);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->validator->errors()->first('pin'),
            ], 400);
        }

        $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

        if (!$wallet || $wallet->balance < $validated['amount']) {
            return response()->json([
                'status'  => false,
                'message' => 'Insufficient wallet balance. Your current balance is ₦' . number_format($wallet->balance ?? 0, 2),
            ], 400);
        }

        // Use the phone number the user entered on the form — this is what
        // ClubKonnect requires (PhoneNo) and uses to deliver the token/receipt SMS.
        $phoneNo = $validated['phoneNo'];

        $requestId = Str::uuid()->toString();

        DB::beginTransaction();

        try {
            $rawPlans     = ClubKonnectHelper::getElectricityDiscos();
            $plans        = $this->normalizePlans($rawPlans);
            $providerPlan = collect($plans)->firstWhere('code', $validated['electricCompany']);
            $foundProduct = null;

            if ($providerPlan) {
                $foundProduct = collect($providerPlan['products'])
                    ->firstWhere('id', $validated['package']);
            }

            $minAllowed = $foundProduct['min'] ?? (float) $request->input('minAmount', 100);
            $maxAllowed = $foundProduct['max'] ?? (float) $request->input('maxAmount', 200000);

            if ($minAllowed <= 0) $minAllowed = 100;
            if ($maxAllowed <= 0) $maxAllowed = 200000;

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

            Log::info('Electricity purchase request', [
                'user_id'          => auth()->id(),
                'electric_company' => $validated['electricCompany'],
                'package'          => $validated['package'],
                'meter_masked'     => substr($validated['meterNumber'], 0, 4) . '****',
                'amount'           => $validated['amount'],
                'phone'            => $phoneNo,
                'request_id'       => $requestId,
            ]);

            $response = ClubKonnectHelper::buyElectricity(
                $validated['electricCompany'],
                $validated['package'],
                $validated['meterNumber'],
                $validated['amount'],
                $phoneNo,
                $requestId
            );

            Log::info('Electricity purchase raw response', [
                'user_id'    => auth()->id(),
                'request_id' => $requestId,
                'response'   => $response,
            ]);

            // Guard against ClubKonnect returning a plain error string/status
            // (e.g. MISSING_Electricity, MISSING_MeterType, INVALID_MeterNo) instead of an order object.
            $knownErrorStatuses = [
                'INVALID_CREDENTIALS', 'MISSING_CREDENTIALS', 'MISSING_USERID', 'MISSING_APIKEY',
                'MISSING_ELECTRICITY', 'MISSING_METERTYPE', 'INVALID_METERNO', 'METERTYPE_NOT_AVAILABLE',
            ];

            $statusCode = (string) ($response['statuscode'] ?? $response['StatusCode'] ?? '');
            $statusText = strtoupper((string) ($response['status'] ?? $response['Status'] ?? ''));

            $accepted = !in_array($statusText, $knownErrorStatuses)
                && (
                    in_array($statusCode, ['100', '200'])
                    || in_array($statusText, ['ORDER_RECEIVED', 'ORDER_COMPLETED', 'SUCCESS'])
                );

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
                // Orders accepted with statuscode 100 are still pending on ClubKonnect's
                // side until the callback confirms ORDER_COMPLETED — mark as pending here.
                'status'       => $accepted ? ($statusCode === '100' ? 'pending' : 'success') : 'failed',
                'response'     => json_encode($response),
            ]);

            if ($accepted) {
                $wallet->decrement('balance', $validated['amount']);
                DB::commit();

                return response()->json([
                    'status'  => true,
                    'message' => $statusCode === '100'
                        ? 'Order received and is being processed.'
                        : 'Electricity purchase successful.',
                    'data'    => [
                        'token'          => $response['metertoken'] ?? $response['token'] ?? '',
                        'order_id'       => $response['orderid']    ?? $response['OrderID'] ?? '',
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
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

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

    /**
     * Handle ClubKonnect's async callback for electricity orders.
     * ClubKonnect POSTs (or GETs) here with: orderid, requestid, statuscode, orderstatus, orderremark.
     * This route must be public (no auth, no CSRF) since ClubKonnect's server calls it directly.
     */
    public function handleCallback(Request $request)
    {
        $orderId    = $request->input('orderid');
        $requestId  = $request->input('requestid');
        $statusCode = (string) $request->input('statuscode');
        $orderStatus = strtoupper((string) $request->input('orderstatus'));

        Log::info('ClubKonnect electricity callback received', [
            'orderid'     => $orderId,
            'requestid'   => $requestId,
            'statuscode'  => $statusCode,
            'orderstatus' => $orderStatus,
        ]);

        $transaction = $requestId
            ? Transaction::where('reference', $requestId)->where('type', 'electricity')->first()
            : null;

        if (!$transaction) {
            Log::warning('ClubKonnect electricity callback: no matching transaction', [
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
            // Refund the wallet if the order ultimately failed after we'd already deducted.
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
}