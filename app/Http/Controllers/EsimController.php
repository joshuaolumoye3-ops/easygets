<?php

namespace App\Http\Controllers;

use App\Helpers\PinHelper;
use App\Helpers\ReferralBalanceHelper;
use App\Mail\EsimPurchaseAdminNotification;
use App\Models\Admin;
use App\Models\Transaction;
use App\Models\EsimCountry;
use App\Models\EsimPackage;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EsimController extends Controller
{
    public function index()
    {
        $countries = EsimCountry::where('is_active', true)
            ->where('base_price', '>', 0)
            ->whereHas('packages', fn ($q) => $q->where('is_active', true))
            ->withCount(['packages' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('country_name')
            ->get()
            ->map(fn ($country) => [
                'id'                     => $country->id,
                'code'                   => $country->country_code,
                'title'                  => $country->country_name,
                'price'                  => $country->base_price,
                'flag_url'               => $country->flag_url,
                'packages_count'         => $country->packages_count,
                'local_number_available' => (bool) $country->local_number_available,
            ])
            ->toArray();

        return view('users.esim', compact('countries'));
    }

    public function plan($code)
    {
        $country = EsimCountry::where('country_code', strtoupper($code))
            ->where('is_active', true)
            ->first();

        if (!$country) {
            abort(404, 'Country not found');
        }

        $packages = $country->packages()
            ->where('is_active', true)
            ->orderBy('validity_days')
            ->orderBy('price')
            ->get()
            ->map(fn ($package) => [
                'id'         => $package->id,
                'title'      => $package->plan,
                'validity'   => $package->validity_days . ' days',
                'price'      => number_format($package->price, 2),
                'badge'      => '',
                'badgeColor' => '',
            ])
            ->toArray();

        if (empty($packages)) {
            return back()->with('error', 'No packages available for this country yet.');
        }

        $countryData = [
            'id'                     => $country->id,
            'code'                   => $country->country_code,
            'title'                  => $country->country_name,
            'flag_url'               => $country->flag_url,
            'description'            => $country->description,
            'local_number_available' => (bool) $country->local_number_available,
        ];

        $features = [
            'network' => '4G LTE / 5G',
            'speed'   => 'High speed (depends on local provider)',
            'phone'   => $country->local_number_available ? 'Local phone number available' : 'No local phone number',
            'topup'   => 'Not supported',
        ];

        return view('users.esim-plan', [
            'country'  => $countryData,
            'packages' => $packages,
            'features' => $features,
        ]);
    }

    public function checkout($code, $packageId)
    {
        $country = EsimCountry::where('country_code', strtoupper($code))
            ->where('is_active', true)
            ->first();

        if (!$country) {
            abort(404, 'Country not found');
        }

        $package = EsimPackage::where('id', $packageId)
            ->where('esim_country_id', $country->id)
            ->where('is_active', true)
            ->first();

        if (!$package) {
            abort(404, 'Package not found');
        }

        $countryData = [
            'id'          => $country->id,
            'code'        => $country->country_code,
            'title'       => $country->country_name,
            'flag_url'    => $country->flag_url,
            'description' => $country->description,
        ];

        $packageData = [
            'id'       => $package->id,
            'title'    => $package->plan,
            'validity' => $package->validity_days . ' days',
            'price'    => number_format($package->price, 2),
        ];

        $wallet = Wallet::where('user_id', auth()->id())->first();

        return view('users.esim-checkout', [
            'country' => $countryData,
            'package' => $packageData,
            'wallet'  => $wallet,
        ]);
    }

    /**
     * Process eSIM purchase with optional referral balance deduction.
     */
    public function store(Request $request, string $countryCode, string $packageId)
    {
        // ── Validate PIN format ───────────────────────────────────────────────
        try {
            $request->validate([
                'pin'                  => 'required|array|size:4',
                'pin.*'                => 'required|numeric|digits:1',
                'use_referral_balance' => 'sometimes|boolean',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid PIN format. Please enter 4 digits.',
            ], 422);
        }

        // ── Verify PIN value ──────────────────────────────────────────────────
        $inputPin = implode('', $request->pin);

        try {
            PinHelper::verifyPin($inputPin);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first('pin') ?? 'Invalid PIN',
            ], 422);
        } catch (\Exception $e) {
            Log::error('PIN verification failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'PIN verification failed. Please try again.',
            ], 400);
        }

        // ── Load country & package ────────────────────────────────────────────
        $country = EsimCountry::where('country_code', strtoupper($countryCode))
            ->where('is_active', true)
            ->first();

        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found or inactive',
            ], 404);
        }

        $package = EsimPackage::where('id', $packageId)
            ->where('esim_country_id', $country->id)
            ->where('is_active', true)
            ->first();

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found or inactive',
            ], 404);
        }

        /** @var \App\Models\User $user */
        $user   = auth()->user();
        $userId = $user->id;
        $amount = (float) $package->price;

        try {
            DB::beginTransaction();

            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

            if (!$wallet) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found. Please contact support.',
                ], 400);
            }

            // ── Referral balance split ────────────────────────────────────────
            $useReferral = !empty($request->use_referral_balance)
                && ReferralBalanceHelper::isEligible($user);

            if ($useReferral) {
                $split = ReferralBalanceHelper::calculate($user, $wallet, $amount);
            } else {
                $split = [
                    'referral_deduction' => 0,
                    'wallet_deduction'   => $amount,
                    'can_proceed'        => true,
                    'shortfall'          => 0,
                ];
            }

            // ── Balance check ─────────────────────────────────────────────────
            if ((float) $wallet->balance < $split['wallet_deduction']) {
                DB::rollBack();

                $referralNote = $split['referral_deduction'] > 0
                    ? ' Your referral balance will cover ₦' . number_format($split['referral_deduction'], 2)
                      . ', but the wallet still needs ₦' . number_format($split['wallet_deduction'], 2) . '.'
                    : ' Required: ₦' . number_format($amount, 2) . '.';

                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance.' . $referralNote
                        . ' Please fund your wallet.',
                ], 400);
            }

            // ── Create transaction record ─────────────────────────────────────
            $reference = 'ESIM' . time() . strtoupper(Str::random(10));

            $transaction = Transaction::create([
                'reference' => $reference,
                'user_id'   => $userId,
                'type'      => 'esim',
                'title'     => 'eSIM Purchase',
                'channel'   => 'web',
                'provider'  => $country->country_name,
                'phone'     => null,
                'recipient' => $country->country_name . ' - ' . $package->plan,
                'amount'    => $amount,
                'status'    => 'pending',
                'response'  => json_encode([
                    'country'            => $country->country_name,
                    'package'            => $package->plan,
                    'validity'           => $package->validity_days . ' days',
                    'referral_deducted'  => $split['referral_deduction'],
                    'wallet_deducted'    => $split['wallet_deduction'],
                ]),
            ]);

            // ── Deduct balances (referral first, then wallet) ─────────────────
            $deducted = ReferralBalanceHelper::apply($user, $wallet, $amount);

            DB::commit();

            Log::info('eSIM purchase successful', [
                'transaction_id'     => $transaction->id,
                'reference'          => $reference,
                'referral_deducted'  => $deducted['referral_deduction'],
                'wallet_deducted'    => $deducted['wallet_deduction'],
            ]);

            // ── Notify admins (outside transaction — failure is non-fatal) ────
            $adminEmails = Admin::whereNotNull('email')
                ->where('email', '<>', '')
                ->pluck('email')
                ->filter()
                ->unique();

            $validAdminEmails = $adminEmails
                ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
                ->values()
                ->all();

            if (!empty($validAdminEmails)) {
                foreach ($validAdminEmails as $adminEmail) {
                    try {
                        Mail::to($adminEmail)->send(
                            new EsimPurchaseAdminNotification($transaction, $country, $package, $user)
                        );
                    } catch (\Exception $mailException) {
                        Log::warning('Failed to send admin eSIM notification', [
                            'admin_email' => $adminEmail,
                            'error'       => $mailException->getMessage(),
                        ]);
                    }
                }
            }

            // ── Build success message ─────────────────────────────────────────
            $successMessage = 'eSIM purchase successful! Check your transactions for details.';
            if ($deducted['referral_deduction'] > 0) {
                $successMessage .= ' ₦' . number_format($deducted['referral_deduction'], 2)
                    . ' was paid from your referral balance.';
            }

            return response()->json([
                'success'        => true,
                'message'        => $successMessage,
                'transaction_id' => $transaction->id,
                'data'           => [
                    'transaction_reference' => $reference,
                    'country'               => $country->country_name,
                    'package'               => $package->plan,
                    'validity'              => $package->validity_days . ' days',
                    'amount'                => $amount,
                    'formatted_amount'      => '₦' . number_format($amount, 2),
                    'referral_deducted'     => $deducted['referral_deduction'],   // ← NEW
                    'wallet_deducted'       => $deducted['wallet_deduction'],     // ← NEW
                    'new_balance'           => $wallet->fresh()->balance,
                    'formatted_balance'     => '₦' . number_format($wallet->fresh()->balance, 2),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('eSIM purchase error', [
                'user_id'      => $userId,
                'country_code' => $countryCode,
                'package_id'   => $packageId,
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again.',
            ], 500);
        }
    }
}