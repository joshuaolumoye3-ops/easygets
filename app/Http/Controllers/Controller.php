<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\PinHelper;
use Illuminate\Validation\ValidationException;
use App\Models\Provider;

class Controller
{

    public function dashboard()
    {
        $user = auth()->user();
        $admin_rate_product = Product::select('client_price')
            ->where('product_code', 'morethanpanel')
            ->where('is_active', true)
            ->first();
        $admin_rate = $admin_rate_product ? $admin_rate_product->client_price : null;

        $latestTransactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->take(4)
            ->get();

        // Fetch services from MoreThanPanel (SMM API). Be defensive: the helper
        // may return null, an array, or objects depending on the remote response.
        try {
            $rawServices = smm_api()->services();
        } catch (\Exception $e) {
            Log::error('smm_api()->services() failed', ['error' => $e->getMessage()]);
            $rawServices = [];
        }

        $servicesCollection = collect($rawServices ?: []);

        $categories = $servicesCollection->groupBy(function ($s) {
            // Support both array and object shapes
            if (is_array($s)) return $s['category'] ?? 'other';
            return $s->category ?? 'other';
        })->map(function ($services) {
            return $services->map(function ($service) {
                $svc = is_array($service) ? (object) $service : $service;
                return (object) [
                    'id'   => $svc->service ?? $svc->id ?? null,
                    'name' => $svc->name ?? '',
                    'rate' => isset($svc->rate) ? round($svc->rate * 1.05, 2) : 0,
                    'min'  => (int) ($svc->min ?? 0),
                    'max'  => (int) ($svc->max ?? 0),
                    'desc' => $svc->desc ?? '',
                ];
            })->values();
        });

        // Fetch SMS services for Quick Pick
        $token = env('SMSBUS_TOKEN');
        $cacheKey = 'smsbus_services';

        $smsServices = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($token) {
            try {
                $response = Http::timeout(30)
                    ->withOptions(['verify' => false])
                    ->get("https://sms-bus.com/api/control/list/projects", [
                        'token' => $token
                    ]);

                if ($response->successful()) {
                    $data = $response->json('data');
                    if (is_array($data) && !empty($data)) {
                        return collect($data)->values();
                    }
                }
            } catch (\Exception $e) {
                Log::error('SMS-Bus API Error in dashboard', ['error' => $e->getMessage()]);
            }

            return collect([]);
        });

        $quickPickServices = [
            'facebook' => $smsServices->firstWhere('code', 'fb'),
            'whatsapp' => $smsServices->firstWhere('code', 'wa'),
            'telegram' => $smsServices->firstWhere('code', 'tg'),
            'google'   => $smsServices->firstWhere('code', 'google'),
        ];

        $wallet = $user->wallet()->with('virtualAccounts')->first();

        // ── Provider availability (mirrors CountryController::showServices) ──
        $providerIsAvailable = function (?Provider $provider): bool {
            if (!$provider) return false;
            if ($provider->status !== 'active') return false;
            return $provider->products()->where('is_active', true)->exists();
        };

        $fivesimProvider  = Provider::where('api_identifier', '5sim')->first();
        $smsbowerProvider = Provider::where('api_identifier', 'smsbower')->first();
        $smsbusProvider   = Provider::where('api_identifier', 'smsbus')->first();

        // Config checks (support config() or env fallbacks)
        $fivesimConfigOk = !empty(config('services.5sim.token') ?: env('FIVESIM_TOKEN'));
        $smsbowerConfigOk = !empty(config('services.smsbower.api_key') ?: env('SMSBOWER_API_KEY'));
        $smsbusConfigOk   = !empty(config('services.smsbus.token') ?: env('SMSBUS_TOKEN')) || !empty(env('SMSBUS_TOKEN'));

        $fivesimProviderAvailable  = $providerIsAvailable($fivesimProvider);
        $smsbowerProviderAvailable = $providerIsAvailable($smsbowerProvider);
        $smsbusProviderAvailable   = $providerIsAvailable($smsbusProvider);

        // The provider record is the source of truth for display state in the UI.
        // Config checks are still reported separately for diagnostics, but they must
        // not hide an admin-enabled provider from the home screen modal.
        $hasFivesim  = $fivesimProviderAvailable;
        $hasSmsBower = $smsbowerProviderAvailable;
        $hasSmsbus   = $smsbusProviderAvailable;

        return view('users.home', compact(
            'latestTransactions', 'wallet', 'categories', 'admin_rate', 'quickPickServices',
            'hasFivesim', 'hasSmsBower', 'hasSmsbus',
            'fivesimConfigOk', 'fivesimProviderAvailable',
            'smsbowerConfigOk', 'smsbowerProviderAvailable',
            'smsbusConfigOk', 'smsbusProviderAvailable'
        ));
    }

    public function transaction()
    {
        $user = Auth::user();

        // Get transactions and group by date (Y-m-d)
        $transactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->get()
            ->groupBy(fn ($txn) => Carbon::parse($txn->created_at)->format('d M, Y'));

        return view('users.transaction', compact('transactions'));
    }

    public function profile()
    {
        $user = auth()->user();

        $wallet = $user->wallet()->with('virtualAccounts')->first();

        return view('users.profile', compact('wallet'));
    }

    public function updatePin(Request $request)
    {
        $user = auth()->user();

        if ($user->pin) {
            // User already has a PIN: require previous_pin + new pin (confirmed)
            $request->validate([
                'previous_pin' => ['required', 'digits:4'],
                'pin' => ['required', 'digits:4', 'confirmed'],
            ]);

            // Verify previous pin
            try {
                PinHelper::verifyPin($request->previous_pin, $user);
            } catch (ValidationException $e) {
                $errors = $e->errors();
                $message = $errors['pin'][0] ?? 'The current PIN is incorrect.';
                return back()->withErrors(['previous_pin' => $message])->withInput();
            }
        } else {
            // No existing PIN – allow creation
            $request->validate([
                'pin' => ['required', 'digits:4', 'confirmed'],
            ]);
        }

        $user->pin = Hash::make($request->pin); // hash new pin
        $user->save();

        return redirect()->route('profile')
                         ->with('success', 'Your PIN has been updated successfully.');
    }

    /**
     * AJAX: return provider config and connectivity status for modal retry.
     */
    public function providerStatus(Request $request)
    {
        try {
            $fivesimConfigOk = !empty(config('services.5sim.token') ?: env('FIVESIM_TOKEN'));
            $smsbowerConfigOk = !empty(config('services.smsbower.api_key') ?: env('SMSBOWER_API_KEY'));
            $smsbusConfigOk   = !empty(config('services.smsbus.token') ?: env('SMSBUS_TOKEN')) || !empty(env('SMSBUS_TOKEN'));

            $fivesimProvider  = Provider::where('api_identifier', '5sim')->first();
            $smsbowerProvider = Provider::where('api_identifier', 'smsbower')->first();
            $smsbusProvider   = Provider::where('api_identifier', 'smsbus')->first();

            $providerIsAvailable = function (?Provider $provider): bool {
                if (!$provider) return false;
                if ($provider->status !== 'active') return false;
                return $provider->products()->where('is_active', true)->exists();
            };

            $fivesimProviderAvailable  = $providerIsAvailable($fivesimProvider);
            $smsbowerProviderAvailable = $providerIsAvailable($smsbowerProvider);
            $smsbusProviderAvailable   = $providerIsAvailable($smsbusProvider);

            // Lightweight connectivity probes
            $fivesimProbe = false; $fivesimProbeMsg = null;
            if ($fivesimConfigOk) {
                try {
                    $token = config('services.5sim.token') ?: env('FIVESIM_TOKEN');
                    $resp = Http::withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
                                ->timeout(10)->get('https://5sim.net/v1/user/profile');
                    $fivesimProbe = $resp->successful();
                } catch (\Exception $e) { $fivesimProbeMsg = $e->getMessage(); }
            }

            $smsbowerProbe = false; $smsbowerProbeMsg = null;
            if ($smsbowerConfigOk) {
                try {
                    $services = \App\Helpers\SmsBowerHelper::getServices();
                    $smsbowerProbe = !empty($services);
                } catch (\Exception $e) { $smsbowerProbeMsg = $e->getMessage(); }
            }

            $smsbusProbe = false; $smsbusProbeMsg = null;
            if ($smsbusConfigOk) {
                try {
                    $token = config('services.smsbus.token') ?: env('SMSBUS_TOKEN');
                    $resp = Http::timeout(10)->get('https://sms-bus.com/api/control/list/projects', ['token' => $token]);
                    $smsbusProbe = $resp->successful() && !empty($resp->json('data'));
                } catch (\Exception $e) { $smsbusProbeMsg = $e->getMessage(); }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'fivesim' => [
                        'config_ok' => $fivesimConfigOk,
                        'provider_available' => $fivesimProviderAvailable,
                        'probe_ok' => $fivesimProbe,
                        'probe_msg' => $fivesimProbeMsg,
                    ],
                    'smsbower' => [
                        'config_ok' => $smsbowerConfigOk,
                        'provider_available' => $smsbowerProviderAvailable,
                        'probe_ok' => $smsbowerProbe,
                        'probe_msg' => $smsbowerProbeMsg,
                    ],
                    'smsbus' => [
                        'config_ok' => $smsbusConfigOk,
                        'provider_available' => $smsbusProviderAvailable,
                        'probe_ok' => $smsbusProbe,
                        'probe_msg' => $smsbusProbeMsg,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


}
