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
    $provider = Provider::with('products')
        ->where('api_identifier', 'demmyglobal')
        ->first();
    
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
            'network'              => 'required|string|in:mtn,glo,airtel,9mobile,MTN,GLO,AIRTEL,9MOBILE',
            'data_plan'            => 'required|string',
            'recipient'            => 'required|digits:11',
            'pin'                  => 'required|string|size:4',
            'use_referral_balance' => 'sometimes|boolean',
        ]);

        $network   = null;
        $productId = null;
        $recipient = null;
        $amount    = null;
        $product   = null;
        $wallet    = null;

        try {
            DB::beginTransaction();

            // ── Verify PIN ────────────────────────────────────────────────────────
            try {
                PinHelper::verifyPin($validated['pin']);
            } catch (ValidationException $e) {
                DB::rollBack();
                $pinError = $e->validator->errors()->first('pin') ?? 'Invalid PIN. Please try again.';

                \Log::warning('Data purchase - Invalid PIN', ['user_id' => auth()->id()]);

                return response()->json([
                    'success'    => false,
                    'title'      => 'Invalid PIN',
                    'message'    => $pinError,
                    'error_type' => 'validation',
                    'suggestion' => 'Please enter your 4-digit transaction PIN.',
                    'details'    => [],
                ], 422);
            }

            $network   = strtolower($validated['network']);
            $productId = $validated['data_plan'];
            $recipient = DemmyPayHelper::normalizeNigerianPhone($validated['recipient']);
            if (!$recipient) {
                DB::rollBack();
                return response()->json([
                    'success'    => false,
                    'title'      => 'Invalid Number',
                    'message'    => 'Please enter a valid Nigerian phone number.',
                    'error_type' => 'validation',
                ], 422);
            }
            $requestId = 'DATA_' . Str::uuid();

            // ── Fetch product ─────────────────────────────────────────────────────
            $provider = Provider::where('api_identifier', 'demmyglobal')->first();
            $product = Product::where('provider_id', $provider?->id)
                ->where('product_code', $productId)
                ->where('is_active', 1)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                DB::rollBack();
                return response()->json([
                    'success'    => false,
                    'title'      => 'Package Not Found',
                    'message'    => 'The selected data package is no longer available. Please select another package.',
                    'error_type' => 'validation',
                    'details'    => [],
                ], 400);
            }

            $amount = (float) ($product->client_price ?? 0);
            $providerAmount = (float) ($product->api_price ?? 0);
            if ($amount <= 0 || $providerAmount <= 0) {
                DB::rollBack();
                return response()->json([
                    'success'    => false,
                    'title'      => 'Invalid Amount',
                    'message'    => 'Unable to process this data package at this time. The pricing information is incorrect.',
                    'error_type' => 'system_error',
                    'details'    => [],
                ], 400);
            }

            $productNetwork = strtolower((string) ($product->category ?? '') . ' ' . $product->name);
            $productNetwork = preg_match('/(^|[^a-z0-9])(?:9mobile|etisalat)([^a-z0-9]|$)/i', $productNetwork)
                ? '9mobile'
                : (preg_match('/(^|[^a-z0-9])airtel([^a-z0-9]|$)/i', $productNetwork)
                    ? 'airtel'
                    : (preg_match('/(^|[^a-z0-9])glo([^a-z0-9]|$)/i', $productNetwork)
                        ? 'glo'
                        : (preg_match('/(^|[^a-z0-9])mtn([^a-z0-9]|$)/i', $productNetwork) ? 'mtn' : null)));

            if ($productNetwork !== $network) {
                DB::rollBack();
                return response()->json([
                    'success'    => false,
                    'title'      => 'Invalid Package',
                    'message'    => 'The selected package does not match the chosen network.',
                    'error_type' => 'validation',
                ], 422);
            }

            // ── Load user and wallet ──────────────────────────────────────────────
            /** @var \App\Models\User $user */
            $user   = \Illuminate\Support\Facades\Auth::user();
            $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet) {
                DB::rollBack();
                return response()->json([
                    'success'    => false,
                    'title'      => 'Wallet Error',
                    'message'    => 'Your wallet could not be accessed. Please contact support.',
                    'error_type' => 'system_error',
                    'details'    => [],
                ], 500);
            }

            // ── Referral balance split ────────────────────────────────────────────
            $useReferral = !empty($validated['use_referral_balance'])
                && \App\Helpers\ReferralBalanceHelper::isEligible($user);

            if ($useReferral) {
                $split = \App\Helpers\ReferralBalanceHelper::calculate($user, $wallet, $amount);
            } else {
                $split = [
                    'referral_deduction' => 0,
                    'wallet_deduction'   => $amount,
                    'can_proceed'        => true,
                    'shortfall'          => 0,
                ];
            }

            // ── Balance check ─────────────────────────────────────────────────────
            if ((float) $wallet->balance < $split['wallet_deduction']) {
                DB::rollBack();

                $shortfall = round($split['wallet_deduction'] - $wallet->balance, 2);

                \Log::info('Data purchase - Insufficient balance', [
                    'user_id'          => $user->id,
                    'balance'          => $wallet->balance,
                    'required_amount'  => $amount,
                    'wallet_needed'    => $split['wallet_deduction'],
                    'referral_covers'  => $split['referral_deduction'],
                    'shortfall'        => $shortfall,
                ]);

                return response()->json([
                    'success'    => false,
                    'title'      => 'Insufficient Balance',
                    'message'    => 'Your wallet balance is not enough for this purchase.'
                        . ($split['referral_deduction'] > 0
                            ? ' Your referral balance will cover ₦' . number_format($split['referral_deduction'], 2) . ','
                            . ' but your wallet still needs ₦' . number_format($split['wallet_deduction'], 2) . '.'
                            : ''),
                    'error_type' => 'insufficient_balance',
                    'details'    => [
                        'Total Required'       => '₦' . number_format($amount, 2),
                        'Covered by Referral'  => '₦' . number_format($split['referral_deduction'], 2),
                        'Required from Wallet' => '₦' . number_format($split['wallet_deduction'], 2),
                        'Wallet Balance'       => '₦' . number_format($wallet->balance, 2),
                        'Shortfall'            => '₦' . number_format($shortfall, 2),
                    ],
                    'suggestion' => 'Please fund your wallet with at least ₦' . number_format($shortfall, 2) . ' to complete this purchase.',
                ], 400);
            }

            // ── Call API ──────────────────────────────────────────────────────────
            $response = DemmyPayHelper::buyData(
                $network,
                $recipient,
                $providerAmount,
                (string) $product->product_code,
                $requestId
            );

            $networkNames = [
                'mtn'     => 'MTN',
                'glo'     => 'Glo',
                'airtel'  => 'Airtel',
                '9mobile' => '9Mobile',
            ];
            $networkName  = $networkNames[$network] ?? ucfirst($network);
            $isApiSuccess = DemmyPayHelper::isSuccessful($response);

            if ($isApiSuccess) {
                // ── Deduct balances (referral first, then wallet) ─────────────────
                $deducted = \App\Helpers\ReferralBalanceHelper::apply($user, $wallet, $amount);

                $transaction = Transaction::create([
                    'reference' => substr($requestId, 0, 100),
                    'user_id'   => $user->id,
                    'type'      => 'data',
                    'channel'   => 'AfrikVend',
                    'provider'  => $networkName,
                    'phone'     => $recipient,
                    'recipient' => $recipient,
                    'amount'    => $amount,
                    'status'    => 'success',
                    'response'  => substr(json_encode(array_merge($response, [
                        'referral_deducted' => $deducted['referral_deduction'],
                        'wallet_deducted'   => $deducted['wallet_deduction'],
                    ]), JSON_UNESCAPED_SLASHES), 0, 65535),
                ]);

                DB::commit();

                $successMessage = "Your {$networkName} data bundle ({$product->name}) has been successfully purchased and delivered to {$recipient}!";
                if ($deducted['referral_deduction'] > 0) {
                    $successMessage .= ' ₦' . number_format($deducted['referral_deduction'], 2)
                        . ' was paid from your referral balance.';
                }

                \Log::info('Data purchase - Success', [
                    'user_id'        => $user->id,
                    'transaction_id' => $transaction->id,
                    'network'        => $networkName,
                    'amount'         => $amount,
                    'referral_used'  => $deducted['referral_deduction'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'data'    => [
                        'transaction_reference' => $response['txRef'] ?? $requestId,
                        'network'               => $networkName,
                        'recipient'             => $recipient,
                        'package_name'          => $product->name ?? 'Data Bundle',
                        'amount'                => $amount,
                        'formatted_amount'      => '₦' . number_format($amount, 2),
                        'referral_deducted'     => $deducted['referral_deduction'],
                        'wallet_deducted'       => $deducted['wallet_deduction'],
                        'new_balance'           => $wallet->fresh()->balance,
                        'formatted_balance'     => '₦' . number_format($wallet->fresh()->balance, 2),
                        'transaction_time'      => now()->format('Y-m-d H:i:s'),
                        'transaction_id'        => $transaction->id,
                    ],
                ]);

            } else {
                // ── API failed — do NOT deduct anything ───────────────────────────
                DB::rollBack();

                $errorMessage      = $response['detail'] ?? $response['message'] ?? $response['error'] ?? null;
                $isValidationError = false;

                if ($errorMessage) {
                    foreach (['invalid', 'not found', 'does not exist', 'incorrect', 'invalid phone'] as $kw) {
                        if (stripos($errorMessage, $kw) !== false) {
                            $isValidationError = true;
                            break;
                        }
                    }
                }

                \Log::warning('Data purchase - API failed', [
                    'user_id'      => $user->id,
                    'network'      => $networkName,
                    'recipient'    => $recipient,
                    'product_id'   => $product->id,
                    'amount'       => $amount,
                    'api_response' => $response,
                ]);

                // Audit record — no wallet deduction
                Transaction::create([
                    'reference' => substr($requestId, 0, 100),
                    'user_id'   => $user->id,
                    'type'      => 'data',
                    'channel'   => 'AfrikVend',
                    'provider'  => $networkName,
                    'phone'     => $recipient,
                    'recipient' => $recipient,
                    'amount'    => $amount,
                    'status'    => 'failed',
                    'response'  => substr(json_encode($response, JSON_UNESCAPED_SLASHES), 0, 65535),
                ]);

                if ($isValidationError) {
                    return response()->json([
                        'success'    => false,
                        'title'      => 'Invalid Details',
                        'message'    => 'The phone number or network details you provided may be incorrect. Please verify and try again.',
                        'error_type' => 'validation',
                        'details'    => [
                            'Network'      => $networkName,
                            'Phone Number' => $recipient,
                            'Package'      => $product->name,
                        ],
                        'suggestion' => 'Please check that the phone number is correct for the selected network.',
                    ], 400);
                }

                return response()->json([
                    'success'    => false,
                    'title'      => 'Service Temporarily Unavailable',
                    'message'    => "We're experiencing technical difficulties with the data service right now. Your funds have not been deducted. Please try again in a few moments.",
                    'error_type' => 'service_error',
                    'details'    => [
                        'Network' => $networkName,
                        'Package' => $product->name,
                        'Amount'  => '₦' . number_format($amount, 2),
                    ],
                    'suggestion' => 'If the problem persists, please contact our support team.',
                ], 503);
            }

        } catch (ValidationException $e) {
            DB::rollBack();
            \Log::warning('Data purchase - Validation error', ['user_id' => auth()->id(), 'errors' => $e->validator->errors()->all()]);
            return response()->json([
                'success'    => false,
                'title'      => 'Invalid Information',
                'message'    => 'Please check all the details you provided and try again.',
                'error_type' => 'validation',
                'details'    => [],
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Data purchase - Unexpected error', [
                'user_id'   => auth()->id(),
                'error'     => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'network'   => $network   ?? 'unknown',
                'recipient' => $recipient ?? 'unknown',
                'amount'    => $amount    ?? 'unknown',
            ]);
            return response()->json([
                'success'    => false,
                'title'      => 'Something Went Wrong',
                'message'    => 'We encountered an unexpected error while processing your request. Your funds have not been deducted.',
                'error_type' => 'system_error',
                'details'    => [],
                'suggestion' => 'Please try again later. If you continue to experience issues, please contact our support team.',
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
