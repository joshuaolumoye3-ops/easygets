<?php

namespace App\Http\Controllers;

use App\Helpers\PinHelper;
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
        // Get active countries from database that have:
        // 1. A base_price greater than 0
        // 2. At least one active package
        $dbCountries = EsimCountry::where('is_active', true)
            ->where('base_price', '>', 0)
            ->whereHas('packages', function($query) {
                $query->where('is_active', true);
            })
            ->withCount(['packages' => function($query) {
                $query->where('is_active', true);
            }])
            ->orderBy('country_name')
            ->get()
            ->map(function($country) {
                return [
                    'id'    => $country->id,
                    'code'  => $country->country_code,
                    'title' => $country->country_name,
                    'price' => $country->base_price,
                    'flag_url' => $country->flag_url,
                    'packages_count' => $country->packages_count,
                    'local_number_available' => (bool) $country->local_number_available,
                ];
            })
            ->toArray();

        // If no countries in DB, show empty state
        $countries = $dbCountries;

        return view('users.esim', compact('countries'));
    }

    public function plan($code)
    {
        // Find country by code from database
        $country = EsimCountry::where('country_code', strtoupper($code))
            ->where('is_active', true)
            ->first();

        if (!$country) {
            abort(404, 'Country not found');
        }

        // Get packages for this country
        $packages = $country->packages()
            ->where('is_active', true)
            ->orderBy('validity_days')
            ->orderBy('price')
            ->get()
            ->map(function($package) {
                return [
                    'id' => $package->id,
                    'title' => $package->plan,
                    'validity' => $package->validity_days . ' days',
                    'price' => number_format($package->price, 2),
                    'badge' => '',
                    'badgeColor' => '',
                ];
            })
            ->toArray();

        // If no packages, show message
        if (empty($packages)) {
            return back()->with('error', 'No packages available for this country yet.');
        }

        // Format country data for view
        $countryData = [
            'id' => $country->id,
            'code' => $country->country_code,
            'title' => $country->country_name,
            'flag_url' => $country->flag_url,
            'local_number_available' => (bool) $country->local_number_available,
        ];

        $features = [
            'network' => '4G LTE / 5G',
            'speed'   => 'High speed (depends on local provider)',
            'phone'   => $country->local_number_available ? 'Local phone number available' : 'No local phone number',
            'topup'   => 'Not supported',
        ];

        return view('users.esim-plan', [
            'country' => $countryData,
            'packages' => $packages,
            'features' => $features
        ]);
    }

    public function checkout($code, $packageId)
    {
        // Get country from database
        $country = EsimCountry::where('country_code', strtoupper($code))
            ->where('is_active', true)
            ->first();

        if (!$country) {
            abort(404, 'Country not found');
        }

        // Get specific package
        $package = EsimPackage::where('id', $packageId)
            ->where('esim_country_id', $country->id)
            ->where('is_active', true)
            ->first();

        if (!$package) {
            abort(404, 'Package not found');
        }

        // Format data for view
        $countryData = [
            'id' => $country->id,
            'code' => $country->country_code,
            'title' => $country->country_name,
            'flag_url' => $country->flag_url,
        ];

        $packageData = [
            'id' => $package->id,
            'title' => $package->plan,
            'validity' => $package->validity_days . ' days',
            'price' => number_format($package->price, 2),
        ];

        return view('users.esim-checkout', [
            'country' => $countryData,
            'package' => $packageData
        ]);
    }

    public function store(Request $request, string $countryCode, string $packageId)
    {
        try {
            $request->validate([
                'pin' => 'required|array|size:4',
                'pin.*' => 'required|numeric|digits:1',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid PIN format. Please enter 4 digits.',
            ], 422);
        }

        // Combine PIN array into one string
        $inputPin = implode('', $request->pin);

        try {
            // Verify PIN
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

        // Fetch active country
        $country = EsimCountry::where('country_code', strtoupper($countryCode))
            ->where('is_active', true)
            ->first();

        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found or inactive',
            ], 404);
        }

        // Fetch active package
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

        $userId = auth()->id();
        $amount = (float) $package->price;

        try {
            DB::beginTransaction();

            // Lock wallet to prevent race condition
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

            if (!$wallet) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found. Please contact support.',
                ], 400);
            }

            if ($wallet->balance < $amount) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. You need ₦' . number_format($amount, 2) . ' but have ₦' . number_format($wallet->balance, 2),
                ], 400);
            }

            // Generate shorter unique transaction reference (max 30 chars)
            $reference = 'ESIM' . time() . strtoupper(Str::random(10));

            // Create transaction record
            $transaction = Transaction::create([
                'reference' => $reference,
                'user_id' => $userId,
                'type' => 'esim',
                'title' => 'eSIM Purchase',
                'channel' => 'web',
                'provider' => $country->country_name,
                'phone' => null,
                'recipient' => $country->country_name . ' - ' . $package->plan,
                'amount' => $amount,
                'status' => 'pending',
                'response' => json_encode([
                    'country' => $country->country_name,
                    'package' => $package->plan,
                    'validity' => $package->validity_days . ' days',
                ]),
            ]);

            // Deduct wallet balance
            $wallet->decrement('balance', $amount);

            DB::commit();

            Log::info('eSIM purchase successful', [
                'transaction_id' => $transaction->id,
                'reference' => $reference,
            ]);

            // Notify admins
            $adminEmails = Admin::whereNotNull('email')
                ->where('email', '<>', '')
                ->pluck('email')
                ->filter()
                ->unique();

            $validAdminEmails = $adminEmails->filter(function ($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            })->values()->all();

            if (!empty($validAdminEmails)) {
                // Send to each admin separately so one bad address doesn't stop others
                foreach ($validAdminEmails as $adminEmail) {
                    try {
                        Mail::to($adminEmail)->send(new EsimPurchaseAdminNotification($transaction, $country, $package, auth()->user()));
                    } catch (\Exception $mailException) {
                        Log::warning('Failed to send admin eSIM notification to admin', [
                            'transaction_id' => $transaction->id,
                            'admin_email' => $adminEmail,
                            'error' => $mailException->getMessage(),
                        ]);
                    }
                }
            } else {
                Log::warning('No valid admin emails found for eSIM notification', [
                    'transaction_id' => $transaction->id,
                    'admin_emails' => $adminEmails->values()->all(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'eSIM purchase successful! Check your transactions for details.',
                'transaction_id' => $transaction->id,
                'data' => [
                    'transaction_reference' => $reference,
                    'country' => $country->country_name,
                    'package' => $package->plan,
                    'validity' => $package->validity_days . ' days',
                    'amount' => $amount,
                    'formatted_amount' => '₦' . number_format($amount, 2),
                    'new_balance' => $wallet->fresh()->balance,
                    'formatted_balance' => '₦' . number_format($wallet->fresh()->balance, 2),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('eSIM purchase error', [
                'user_id' => $userId,
                'country_code' => $countryCode,
                'package_id' => $packageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again.',
            ], 500);
        }
    }
}