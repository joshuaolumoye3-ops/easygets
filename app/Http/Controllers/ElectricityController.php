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

    // -------------------------------------------------------------------------
    // Normalize the raw ClubKonnect response into a consistent shape:
    //   [ { code, name, products: [ { id, type, min, max } ] } ]
    // The "code" is the numeric ElectricCompany value the API requires (e.g. "07").
    // -------------------------------------------------------------------------
    private function normalizePlans(array $rawPlans): array
    {
        if (empty($rawPlans)) {
            return [];
        }

        // Log a sample so we can inspect field names during development
        $sample = array_slice($rawPlans, 0, 1);
        Log::info('ClubKonnect electricity discos raw sample', ['sample' => $sample]);

        $normalized = [];
        $isIndexed  = array_is_list($rawPlans);

        $entries = $isIndexed
            ? array_map(fn ($c) => [null, $c], $rawPlans)
            : array_map(null, array_keys($rawPlans), array_values($rawPlans));

        foreach ($entries as [$stringKey, $company]) {
            if (!is_array($company)) {
                continue;
            }

            $code = $this->extractCompanyCode($company) ?? $stringKey;
            if (!$code) {
                continue;
            }

            $name = $company['NAME']
                ?? $company['COMPANY_NAME']
                ?? $company['DISCO_NAME']
                ?? $stringKey
                ?? 'Unknown Provider';

            $rawProducts = $company['PRODUCT']
                ?? $company['PRODUCTS']
                ?? $company['products']
                ?? [];

            $products = $this->normalizeProducts((array) $rawProducts);

            $normalized[] = [
                'code'     => (string) $code,
                'name'     => (string) $name,
                'products' => $products,
            ];
        }

        return $normalized;
    }

    private function normalizeProducts(array $rawProducts): array
    {
        $products = [];

        foreach ($rawProducts as $product) {
            if (!is_array($product)) {
                continue;
            }

            $id = $product['PRODUCT_ID']
                ?? $product['PRODUCT_CODE']
                ?? $product['PRODUCT_SNO']
                ?? $product['ID']
                ?? null;

            if ($id === null || $id === '') {
                continue;
            }

            $type = $product['PRODUCT_TYPE']
                ?? $product['PRODUCT_TYPE_NAME']
                ?? $product['PRODUCT_NAME']
                ?? $product['PRODUCT_DESCRIPTION']
                ?? 'Meter';

            $rawMin = $product['MINIMUM_AMOUNT']
                ?? $product['MINIMUN_AMOUNT']
                ?? $product['MINAMOUNT']
                ?? $product['MIN_AMOUNT']
                ?? null;

            $rawMax = $product['MAXIMUM_AMOUNT']
                ?? $product['MAXAMOUNT']
                ?? $product['MAX_AMOUNT']
                ?? $product['MAX']
                ?? null;

            $min = $rawMin !== null
                ? (float) preg_replace('/[^0-9.]/', '', (string) $rawMin)
                : 0;

            $max = $rawMax !== null
                ? (float) preg_replace('/[^0-9.]/', '', (string) $rawMax)
                : 0;

            $products[] = [
                'id'   => (string) $id,
                'type' => (string) $type,
                'min'  => $min > 0 ? $min : 100,
                'max'  => $max > 0 ? $max : 200000,
            ];
        }

        return $products;
    }

    /**
     * Extract the numeric API code for an electricity company.
     *
     * ClubKonnect expects a numeric string like "07" for the ElectricCompany
     * parameter. The response may place this in various field names; we try
     * known candidates before falling back to the ELECTRIC_COMPANY string.
     */
    private function extractCompanyCode(array $company): ?string
    {
        // V2 API: ELECTRIC_COMPANY holds the numeric code (e.g. "07")
        $ec = trim((string) ($company['ELECTRIC_COMPANY'] ?? ''));
        if ($ec !== '') {
            return $ec;
        }

        // Fallback: check other known numeric-code field names
        $numericCandidates = [
            'COMPANY_CODE',
            'DISCO_CODE',
            'CODE',
            'ELECTRIC_CODE',
            'COMPANY_NO',
            'DISCO_NO',
            'PROVIDER_CODE',
            'PROVIDER_ID',
        ];

        foreach ($numericCandidates as $field) {
            $val = trim((string) ($company[$field] ?? ''));
            if ($val !== '') {
                return $val;
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Verify meter number — MeterType is required by the ClubKonnect API.
    // -------------------------------------------------------------------------
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

    /**
     * Fetch packages for a given electricity provider (AJAX helper).
     * Now that the blade loads packages from normalized plan data directly,
     * this endpoint is mainly used for server-side amount validation.
     */
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
            'amount'          => 'required|numeric|min:100',
            'pin'             => 'required|string|size:4',
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

        $requestId = Str::uuid()->toString();
        $phoneNo   = auth()->user()->phone ?? '';

        DB::beginTransaction();

        try {
            // Server-side min/max validation from normalized plan data
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

            $response = ClubKonnectHelper::buyElectricity(
                $validated['electricCompany'],  // numeric code e.g. "07"
                $validated['package'],          // MeterType e.g. "01"
                $validated['meterNumber'],
                $validated['amount'],
                $phoneNo,
                $requestId
            );

            $statusCode = (string) ($response['statuscode'] ?? $response['StatusCode'] ?? '');
            $statusText = strtoupper($response['status'] ?? $response['Status'] ?? '');
            $accepted   = in_array($statusCode, ['100', '200'])
                || in_array($statusText, ['ORDER_RECEIVED', 'ORDER_COMPLETED', 'SUCCESS']);

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