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
                'id'       => $package->id,
                'title'    => $package->plan,
                'validity' => $package->validity_days . ' days',
                'price'    => number_format($package->price, 2),
                'badge'    => '',
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
            'description'            => $country->description,          // ← pass through
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
            'description' => $country->description,          // ← pass through
        ];

        $packageData = [
            'id'       => $package->id,
            'title'    => $package->plan,
            'validity' => $package->validity_days . ' days',
            'price'    => number_format($package->price, 2),
        ];

        return view('users.esim-checkout', [
            'country' => $countryData,
            'package' => $packageData,
        ]);
    }

    public function store(Request $request, string $countryCode, string $packageId)
    {
        try {
            $request->validate([
                'pin'   => 'required|array|size:4',
                'pin.*' => 'required|numeric|digits:1',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid PIN format. Please enter 4 digits.'], 422);
        }

        $inputPin = implode('', $request->pin);

        try {
            PinHelper::verifyPin($inputPin);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first('pin') ?? 'Invalid PIN'], 422);
        } catch (\Exception $e) {
            Log::error('PIN verification failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'PIN verification failed. Please try again.'], 400);
        }

        $country = EsimCountry::where('country_code', strtoupper($countryCode))
            ->where('is_active', true)
            ->first();

        if (!$country) {
            return response()->json(['success' => false, 'message' => 'Country not found or inactive'], 404);
        }

        $package = EsimPackage::where('id', $packageId)
            ->where('esim_country_id', $country->id)
            ->where('is_active', true)
            ->first();

        if (!$package) {
            return response()->json(['success' => false, 'message' => 'Package not found or inactive'], 404);
        }

        $userId = auth()->id();
        $amount = (float) $package->price;

        try {
            DB::beginTransaction();

            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

            if (!$wallet) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Wallet not found. Please contact support.'], 400);
            }

            if ($wallet->balance < $amount) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. You need ₦' . number_format($amount, 2) . ' but have ₦' . number_format($wallet->balance, 2),
                ], 400);
            }

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
                    'country'  => $country->country_name,
                    'package'  => $package->plan,
                    'validity' => $package->validity_days . ' days',
                ]),
            ]);

            $wallet->decrement('balance', $amount);

            DB::commit();

            Log::info('eSIM purchase successful', ['transaction_id' => $transaction->id, 'reference' => $reference]);

            // Notify admins
            $adminEmails = Admin::whereNotNull('email')->where('email', '<>', '')->pluck('email')->filter()->unique();
            $validAdminEmails = $adminEmails->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))->values()->all();

            if (!empty($validAdminEmails)) {
                foreach ($validAdminEmails as $adminEmail) {
                    try {
                        Mail::to($adminEmail)->send(new EsimPurchaseAdminNotification($transaction, $country, $package, auth()->user()));
                    } catch (\Exception $mailException) {
                        Log::warning('Failed to send admin eSIM notification', ['admin_email' => $adminEmail, 'error' => $mailException->getMessage()]);
                    }
                }
            }

            return response()->json([
                'success'        => true,
                'message'        => 'eSIM purchase successful! Check your transactions for details.',
                'transaction_id' => $transaction->id,
                'data'           => [
                    'transaction_reference' => $reference,
                    'country'               => $country->country_name,
                    'package'               => $package->plan,
                    'validity'              => $package->validity_days . ' days',
                    'amount'                => $amount,
                    'formatted_amount'      => '₦' . number_format($amount, 2),
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
            return response()->json(['success' => false, 'message' => 'An error occurred while processing your request. Please try again.'], 500);
        }
    }
}