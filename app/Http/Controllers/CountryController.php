<?php

namespace App\Http\Controllers;

use App\Helpers\PinHelper;
use App\Helpers\PricingHelper;
use App\Helpers\SmsBowerHelper;
use App\Models\EsimCountry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Wallet;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Transaction;
use App\Models\Admin;
use Illuminate\Support\Facades\Mail;
use App\Mail\LogsPurchaseAdminNotification;
use App\Helpers\ReferralBalanceHelper;

class CountryController extends Controller
{
    // Provider constants
    const PROVIDER_5SIM    = '5sim';
    const PROVIDER_SMSBUS  = 'smsbus';   // now provider3 (was provider2)
    const PROVIDER_SMSBOWER = 'smsbower'; // new provider2

    private function providerIsAvailable(?Provider $provider): bool
    {
        if (!$provider) {
            return false;
        }

        if ($provider->status !== 'active') {
            return false;
        }

        return $provider->products()->where('is_active', true)->exists();
    }

    // ═══════════════════════════════════════════════════════════
    // SERVICES LIST PAGE (unchanged – shows SMS-Bus services)
    // ═══════════════════════════════════════════════════════════
    public function showServices(Request $request)
    {
        // What the front-end actually sent: 'provider1' | 'provider2' | 'provider3' | null
        $requestedProviderSlug = $this->normalizeProviderSlug($request->input('provider'));

        // Internal identifier used by the branching logic below: '5sim' | 'smsbower' | 'smsbus' | null
        $requestedProvider = $this->providerSlugToInternal($requestedProviderSlug);

        $token = env('SMSBUS_TOKEN');
        $cacheKey = 'smsbus_services';
        $backupCacheKey = 'smsbus_services_backup';

        $products = Cache::get($cacheKey);

        // If a specific provider was requested, we'll load provider-scoped services
        if (!empty($requestedProvider)) {
            if ($requestedProvider === self::PROVIDER_5SIM) {
    // Live catalog from 5sim's own "get products" endpoint.
    $fiveSimCatalog = $this->getFiveSimServiceCatalog();

    $iconMap = $this->getServiceIconMap();
    $products = collect($fiveSimCatalog)->map(function ($s) use ($iconMap) {
        $code = $s['code'];
        $iconUrl = $iconMap[$code] ?? null;
        if (!$iconUrl) {
            $initials = strtoupper(substr($s['name'], 0, 2));
            $colors = ['10b981', '3b82f6', '8b5cf6', 'f59e0b', 'ef4444', '06b6d4'];
            $color = $colors[crc32($code) % count($colors)];
            $iconUrl = "https://ui-avatars.com/api/?name={$initials}&background={$color}&color=ffffff&size=96&format=svg";
        }

        return [
            'id' => 'fs_' . $code,
            'title' => $s['name'],
            'code' => $code,
            'icon_url' => $iconUrl,
            'large_icon_url' => $iconUrl,
        ];
    })->values();

            // Safety net only — if 5sim's API is unreachable, fall back to whatever
            // real (non-pricing-helper) products are curated locally, rather than
            // showing an empty page.
            if ($products->isEmpty()) {
                Log::warning('5sim live catalog empty — falling back to local curated products table');

                $dbProducts = Product::whereHas('provider', function ($q) {
                    $q->where('api_identifier', '5sim');
                })->where('is_active', true)
                ->where('product_code', '!=', 'conversion_rate')
                ->get();

                $products = $dbProducts->map(function ($p) use ($iconMap) {
                    $code = strtolower($p->product_code ?: ($p->name ?? ''));
                    $iconUrl = $iconMap[$code] ?? null;
                    if (!$iconUrl) {
                        $initials = strtoupper(substr($p->name ?? $code, 0, 2));
                        $colors = ['10b981', '3b82f6', '8b5cf6', 'f59e0b', 'ef4444', '06b6d4'];
                        $color = $colors[crc32($code) % count($colors)];
                        $iconUrl = "https://ui-avatars.com/api/?name={$initials}&background={$color}&color=ffffff&size=96&format=svg";
                    }

                    return [
                        'id' => $p->id,
                        'title' => $p->name,
                        'code' => $p->product_code ?? $code,
                        'icon_url' => $iconUrl,
                        'large_icon_url' => $iconUrl,
                    ];
                })->values();
            }
            }elseif ($requestedProvider === self::PROVIDER_SMSBOWER) {
                // Use SmsBower's live "get services" endpoint
                $smsBowerServices = Cache::remember('smsbower_services_list', 3600, function () {
                    return SmsBowerHelper::getServices();
                });

                $iconMap = $this->getServiceIconMap();
                $products = collect($smsBowerServices)->map(function ($s) use ($iconMap) {
                    $code = strtolower($s['code'] ?? ($s['name'] ?? ''));
                    $iconUrl = $iconMap[$code] ?? null;
                    if (!$iconUrl) {
                        $initials = strtoupper(substr($s['name'] ?? $code, 0, 2));
                        $colors = ['10b981', '3b82f6', '8b5cf6', 'f59e0b', 'ef4444', '06b6d4'];
                        $color = $colors[crc32($code) % count($colors)];
                        $iconUrl = "https://ui-avatars.com/api/?name={$initials}&background={$color}&color=ffffff&size=96&format=svg";
                    }
                    return [
                        'id' => 'sb_' . $code,
                        'title' => $s['name'] ?? ucfirst($code),
                        'code' => $code,
                        'icon_url' => $iconUrl,
                        'large_icon_url' => $iconUrl,
                    ];
                })->values();
            } else {
                // provider3 (smsbus) — fall through to the SMS-Bus "list projects" fetch below,
                // which is the correct live endpoint for that provider.
            }
        }

        if (!$products || $products->isEmpty()) {
            try {
                $response = Http::timeout(30)
                    ->connectTimeout(15)
                    ->retry(3, function ($exception, $request) {
                        return 2000;
                    })
                    ->withOptions(['verify' => false])
                    ->get("https://sms-bus.com/api/control/list/projects", [
                        'token' => $token
                    ]);

                if ($response->successful()) {
                    $data = $response->json('data');

                    if (is_array($data) && !empty($data)) {
                        $products = collect($data)->values();
                        Cache::put($cacheKey, $products, now()->addMinutes(30));
                        Cache::put($backupCacheKey, $products, now()->addDays(7));
                    } else {
                        throw new \Exception('Invalid API response data');
                    }
                } else {
                    throw new \Exception('API returned status: ' . $response->status());
                }
            } catch (\Exception $e) {
                Log::error('SMS-Bus API Error', [
                    'error' => $e->getMessage()
                ]);

                $products = Cache::get($backupCacheKey);

                if (!$products || $products->isEmpty()) {
                    $products = $this->getMockServices();
                }
            }
        }

        $iconMap = $this->getServiceIconMap();

        $products = $products->map(function ($product) use ($iconMap) {
            $code = strtolower($product['code']);
            $iconUrl = $iconMap[$code] ?? null;

            if (!$iconUrl) {
                $initials = strtoupper(substr($product['title'], 0, 2));
                $colors = ['10b981', '3b82f6', '8b5cf6', 'f59e0b', 'ef4444', '06b6d4'];
                $color = $colors[crc32($code) % count($colors)];

                $iconUrl = "https://ui-avatars.com/api/?name={$initials}&background={$color}&color=ffffff&size=96&format=svg";
            }

            $product['icon_url'] = $iconUrl;
            $product['large_icon_url'] = $iconUrl;

            return $product;
        });

        // Bring in any SMSBower services that aren't already in the SMS-Bus list,
        // so they show up in the unfiltered "All Services" page and are searchable.
        // Only merge these extras when NO specific provider filter was requested —
        // a provider1/provider2/provider3 filtered view must stay pure to that provider.
        if (empty($requestedProvider) && !empty(env('SMSBOWER_API_KEY'))) {
            $smsBowerServices = Cache::remember('smsbower_services_list', 3600, function () {
                return SmsBowerHelper::getServices();
            });

            $existingCodes = $products
                ->pluck('code')
                ->map(fn($c) => strtolower($c))
                ->flip();

            $extra = collect($smsBowerServices)
                ->filter(fn($s) => isset($s['code']) && !isset($existingCodes[strtolower($s['code'])]))
                ->map(function ($s) use ($iconMap) {
                    $code = strtolower($s['code']);
                    $iconUrl = $iconMap[$code] ?? null;

                    if (!$iconUrl) {
                        $initials = strtoupper(substr($s['name'] ?? $code, 0, 2));
                        $colors = ['10b981', '3b82f6', '8b5cf6', 'f59e0b', 'ef4444', '06b6d4'];
                        $color = $colors[crc32($code) % count($colors)];

                        $iconUrl = "https://ui-avatars.com/api/?name={$initials}&background={$color}&color=ffffff&size=96&format=svg";
                    }

                    return [
                        'id' => 'sb_' . $code,
                        'title' => $s['name'] ?? ucfirst($code),
                        'code' => $code,
                        'icon_url' => $iconUrl,
                        'large_icon_url' => $iconUrl,
                    ];
                })
                ->values();

            $products = $products->concat($extra)->values();
        }

        $popularCodes = ['wa', 'fb', 'tg', 'google', 'ig'];

        $popular = $products->filter(
            fn($p) => in_array(strtolower($p['code']), $popularCodes)
        );

        $search = $request->input('search');
        $isSearching = !empty($search);

        if ($isSearching) {
            $products = $products->filter(function ($p) use ($search) {
                return str_contains(strtolower($p['title']), strtolower($search)) ||
                    str_contains(strtolower($p['code']), strtolower($search));
            });

            $others = $products;
        } else {
            $others = $products->reject(
                fn($p) => in_array(strtolower($p['code']), $popularCodes)
            );
        }

        $perPage = 30;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $paginated = $others->slice($offset, $perPage)->values();
        $hasMorePages = $others->count() > ($page * $perPage);

        if ($request->ajax() || $request->input('ajax') == '1') {
            $html = $this->buildServiceItemsHtml($paginated);

            return response()->json([
                'html' => $html,
                'next_page' => $hasMorePages ? $page + 1 : null,
                'has_more' => $hasMorePages,
                'current_page' => $page,
                'total' => $others->count(),
                'is_searching' => $isSearching,
                'active_provider' => $requestedProviderSlug,
            ]);
        }

        // Check provider availability from database and active products
        $fivesimProvider = Provider::where('api_identifier', '5sim')->first();
        $smsbowerProvider = Provider::where('api_identifier', 'smsbower')->first();
        $smsbusProvider = Provider::where('api_identifier', 'smsbus')->first();

        return view('users.services', [
            'popular' => $isSearching ? collect([]) : $popular,
            'products' => $paginated,
            'nextPage' => $hasMorePages ? 2 : null,
            'isSearching' => $isSearching,
            'hasFivesim' => !empty(env('FIVESIM_TOKEN')) && $this->providerIsAvailable($fivesimProvider),
            'hasSmsBower' => !empty(env('SMSBOWER_API_KEY')) && $this->providerIsAvailable($smsbowerProvider),
            'hasSmsbus' => !empty(env('SMSBUS_TOKEN')) && $this->providerIsAvailable($smsbusProvider),
            'activeProvider' => $requestedProviderSlug,
        ]);
    }

    public function showOffersSmsBower(Request $request, string $serviceCode, string $countryId)
    {
        try {
            $exchangeRate = $this->getSmsBowerExchangeRate();
            $offers       = SmsBowerHelper::getOffersForCountry($serviceCode, $countryId, $exchangeRate);

            $countryMeta = Cache::remember('smsbower_country_meta', 3600, fn () => SmsBowerHelper::getCountries());
            $meta        = $countryMeta[$countryId] ?? null;

            $countryName = $request->query('country_name') ?: ($meta['eng'] ?? "Country {$countryId}");
            $countryCode = $request->query('country_code') ?: SmsBowerHelper::countryIdToIso($countryId, $countryName);

            $serviceDetails = $this->getSmsBowerServiceDetails($serviceCode);

            return view('users.offers-smsbower', [
                'offers'      => $offers,
                'serviceCode' => $serviceCode,
                'serviceName' => $serviceDetails['name'],
                'countryId'   => $countryId,
                'countryName' => $countryName,
                'countryCode' => strtolower($countryCode),
            ]);
        } catch (\Exception $e) {
            Log::error('SMSBower offers error', ['service' => $serviceCode, 'country' => $countryId, 'error' => $e->getMessage()]);

            return redirect()->route('services.provider2.countries', ['serviceCode' => $serviceCode])
                ->withErrors('Unable to load number listings. Please try again.');
        }
    }

    // ═══════════════════════════════════════════════════════════
    // PROVIDER 2 — SMSBower countries
    // ═══════════════════════════════════════════════════════════

    /**
     * Show rank selection (Gold/Silver/Bronze/All) for SMSBower (provider2)
     */
    public function showRankSmsBower(string $serviceCode)
    {
        return view('users.rank-smsbower', [
            'serviceCode' => $serviceCode,
        ]);
    }

    /**
     * Show countries available for a service on SMSBower (provider2)
     */
    public function showCountriesSmsBower(Request $request, string $serviceCode)
    {
        try {
            $exchangeRate = $this->getSmsBowerExchangeRate();
            $rank         = $request->input('rank'); // null | 'gold' | 'silver' | 'bronze' | 'all'
            $countries    = SmsBowerHelper::getCountriesForService($serviceCode, $exchangeRate, $rank);

            $search = $request->input('search', '');
            if ($search) {
                $countries = array_values(array_filter($countries, function ($c) use ($search) {
                    return str_contains(strtolower($c['title']), strtolower($search));
                }));
            }

            if ($request->ajax()) {
                return response()->json([
                    'success'       => true,
                    'data'          => $countries,
                    'exchange_rate' => $exchangeRate,
                ]);
            }

            return view('users.countries-smsbower', [
                'countries'    => collect($countries),
                'serviceCode'  => $serviceCode,
                'exchangeRate' => $exchangeRate,
                'rank'         => $rank,
            ]);
        } catch (\Exception $e) {
            Log::error('SMSBower countries error', ['service' => $serviceCode, 'error' => $e->getMessage()]);

            return view('users.countries-smsbower', [
                'countries'    => collect([]),
                'serviceCode'  => $serviceCode,
                'exchangeRate' => $this->getSmsBowerExchangeRate(),
                'rank'         => null,
            ])->withErrors('Unable to load countries. Please try again.');
        }
    }

    /**
     * Prepare purchase for SMSBower (provider2)
     */
    public function preparePurchaseSmsBower(Request $request)
    {
        $request->validate([
            'service'      => 'required|string',
            'country'      => 'required|string',
            'country_name' => 'required|string',
            'country_code' => 'required|string',
            'provider_id'  => 'nullable|string',
        ]);

        $serviceCode  = $request->service;
        $countryId    = $request->country;
        $providerId   = $request->provider_id;
        $exchangeRate = $this->getSmsBowerExchangeRate();

        if ($providerId) {
            // Priced off the exact listing the user picked
            $offers = SmsBowerHelper::getOffersForCountry($serviceCode, $countryId, $exchangeRate);
            $offer  = collect($offers)->firstWhere('provider_id', $providerId);

            if (!$offer) {
                return back()->withErrors('This number listing is no longer available. Please pick another.');
            }

            $costUsd = $offer['cost_usd'];
            $costNgn = $offer['cost'];
            $count   = $offer['count'];
        } else {
            // Fallback: old aggregate-price behavior (kept for backward compatibility)
            $cacheKey  = "smsbower_price_{$serviceCode}_{$countryId}";
            $priceData = Cache::remember($cacheKey, 180, function () use ($serviceCode, $countryId) {
                $prices = SmsBowerHelper::getPrices($serviceCode, $countryId);
                return $prices[$countryId][$serviceCode] ?? null;
            });

            if (!$priceData || empty($priceData['cost'])) {
                return back()->withErrors('No pricing available for this service in this country.');
            }

            $costUsd = (float) $priceData['cost'];
            $costNgn = round($costUsd * $exchangeRate, 2);
            $count   = $priceData['count'] ?? 0;
        }

        if ($costNgn <= 0) {
            return back()->withErrors('Invalid pricing. Please try again.');
        }

        $serviceDetails = $this->getSmsBowerServiceDetails($serviceCode);

        $purchase = [
            'provider'          => self::PROVIDER_SMSBOWER,
            'country_id'        => $countryId,
            'country_name'      => $request->country_name,
            'country_code'      => strtolower($request->country_code),
            'service'           => $serviceCode,
            'service_name'      => $serviceDetails['name'],
            'cost'              => $costNgn,
            'original_cost_usd' => $costUsd,
            'exchange_rate'     => $exchangeRate,
            'total_count'       => $count,
            'provider_id'       => $providerId, // used by processSmsBower to buy this exact listing
            'timestamp'         => time(),
            'session_token'     => bin2hex(random_bytes(16)),
        ];

        session(['purchase' => encrypt($purchase)]);

        Log::info('SMSBower purchase session created', [
            'user_id'    => auth()->id(),
            'service'    => $serviceCode,
            'country_id' => $countryId,
            'provider_id'=> $providerId,
            'usd_cost'   => $costUsd,
            'ngn_cost'   => $costNgn,
        ]);

        return redirect()->route('confirm.payment');
    }

    // ═══════════════════════════════════════════════════════════
    // PROVIDER 3 — SMS-Bus (was provider2)
    // ═══════════════════════════════════════════════════════════

    public function showCountries(string $serviceId, Request $request = null)
    {
        $request = $request ?? request();
        $token = env('SMSBUS_TOKEN');

        if (!$token) {
            $emptyPaginator = new LengthAwarePaginator([], 0, 20, 1);
            return view('users.countries', [
                'countries' => $emptyPaginator,
                'serviceId' => $serviceId,
            ])->withErrors('Service configuration error. Please contact support.');
        }

        $cacheKey = "smsbus_countries_prices_{$serviceId}";

        try {
            $exchangeRate = $this->getExchangeRate();

            $countries = Cache::remember($cacheKey, 300, function () use ($token, $serviceId) {
                $response = Http::timeout(30)
                    ->get("https://sms-bus.com/api/resource/list/prices", [
                        'projectId' => $serviceId,
                        'token'     => $token,
                    ]);

                if (!$response->successful()) return collect([]);

                $responseData = $response->json();
                if (!isset($responseData['data']) || empty($responseData['data'])) return collect([]);

                return collect($responseData['data'])->map(function ($item) {
                    $countryCode = $item['code'] ?? ($item['country_code'] ?? 'xx');
                    return [
                        'country_id'  => $item['country_id']  ?? ($item['id'] ?? null),
                        'title'       => $item['title']        ?? ($item['country_name'] ?? ($item['name'] ?? 'Unknown')),
                        'code'        => strtolower($countryCode),
                        'project_id'  => $item['project_id']  ?? null,
                        'project_code'=> $item['project_code'] ?? '',
                        'cost_usd'    => $item['cost']   ?? ($item['price'] ?? 0),
                        'cost'        => $item['cost']   ?? ($item['price'] ?? 0),
                        'ws_cost_usd' => $item['ws_cost'] ?? ($item['cost'] ?? ($item['price'] ?? 0)),
                        'ws_cost'     => $item['ws_cost'] ?? ($item['cost'] ?? ($item['price'] ?? 0)),
                        'total_count' => $item['total_count'] ?? ($item['count'] ?? ($item['qty'] ?? 0)),
                        'sort'        => $item['sort'] ?? 999,
                        'flag_url'    => "https://flagcdn.com/w80/" . strtolower($countryCode) . ".png",
                    ];
                })->filter(function ($item) {
                    return !empty($item['country_id']) && $item['title'] !== 'Unknown';
                })->sortBy([['sort','asc'],['total_count','desc']])->values();
            });

            // Apply NGN pricing
            $countries = $countries->map(function ($country) use ($exchangeRate) {
                $country['cost']     = PricingHelper::calculateFinalPrice($country['cost_usd'],    self::PROVIDER_SMSBUS, $exchangeRate);
                $country['ws_cost']  = PricingHelper::calculateFinalPrice($country['ws_cost_usd'], self::PROVIDER_SMSBUS, $exchangeRate);
                $country['exchange_rate'] = $exchangeRate;
                return $country;
            });

            // Search
            $search = $request->get('search', '');
            if ($search) {
                $countries = $countries->filter(function ($c) use ($search) {
                    return str_contains(strtolower($c['title']), strtolower($search)) ||
                           str_contains(strtolower($c['code']), strtolower($search));
                })->values();
            }

            $perPage  = 20;
            $page     = $request->get('page', 1);
            $total    = $countries->count();

            $paginatedCountries = new LengthAwarePaginator(
                $countries->forPage($page, $perPage)->values(),
                $total, $perPage, $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            if ($request->ajax()) {
                return response()->json([
                    'success'        => true,
                    'data'           => $paginatedCountries->items(),
                    'current_page'   => $paginatedCountries->currentPage(),
                    'last_page'      => $paginatedCountries->lastPage(),
                    'total'          => $paginatedCountries->total(),
                    'per_page'       => $paginatedCountries->perPage(),
                    'has_more_pages' => $paginatedCountries->hasMorePages(),
                    'exchange_rate'  => $exchangeRate,
                ]);
            }

            return view('users.countries', [
                'countries'    => $paginatedCountries,
                'serviceId'    => $serviceId,
                'exchangeRate' => $exchangeRate,
            ]);
        } catch (\Exception $e) {
            Log::error('SMS-Bus countries exception', ['serviceId' => $serviceId, 'error' => $e->getMessage()]);
            $emptyPaginator = new LengthAwarePaginator([], 0, 20, 1);

            if ($request->ajax()) {
                return response()->json(['success' => false, 'data' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0], 500);
            }

            return view('users.countries', [
                'countries'    => $emptyPaginator,
                'serviceId'    => $serviceId,
                'exchangeRate' => 1500.0,
            ])->withErrors('An error occurred loading countries.');
        }
    }

    public function getPrices(Request $request, $serviceId, $countryId)
    {
        $token    = env('SMSBUS_TOKEN');
        $response = Http::get("https://sms-bus.com/api/control/list/prices", [
            'token'      => $token,
            'country_id' => $countryId,
        ]);

        if ($response->failed()) {
            return response()->json(['status' => false, 'message' => 'Failed to fetch prices'], 500);
        }

        $data = $response->json();
        if (!isset($data['data'])) {
            return response()->json(['status' => false, 'message' => 'Invalid response from API'], 500);
        }

        $exchangeRate = $this->getExchangeRate();

        $prices = collect($data['data'])
            ->filter(fn ($item) => (int) $item['project_id'] === (int) $serviceId)
            ->map(function ($item) use ($exchangeRate) {
                $costUSD = $item['cost'] ?? 0;
                $costNGN = PricingHelper::calculateFinalPrice($costUSD, self::PROVIDER_SMSBUS, $exchangeRate);
                return [
                    'project_id'   => $item['project_id'],
                    'name'         => ucfirst($item['project_code']),
                    'cost'         => $costNGN,
                    'cost_usd'     => $costUSD,
                    'count'        => $item['total_count'],
                    'country_id'   => $item['country_id'],
                    'country_name' => $item['country_name'] ?? 'Unknown',
                    'country_code' => $item['country_code'] ?? '',
                ];
            })->values()->toArray();

        return response()->json(['status' => true, 'data' => $prices]);
    }

    // ═══════════════════════════════════════════════════════════
    // PROVIDER 1 — 5sim (unchanged)
    // ═══════════════════════════════════════════════════════════

    public function showCountries5sim($serviceCode)
    {
        try {
            $normalizedCode = $this->normalize5simServiceCode($serviceCode);
            $exchangeRate   = $this->get5simExchangeRate();

            $url      = "https://5sim.net/v1/guest/products/any/any/{$normalizedCode}";
            $response = Http::timeout(30)->withOptions(['verify' => false])
                ->get($url, ['single' => 0, 'sort' => 'top']);

            if (!$response->successful()) {
                return view('users.countries-5sim', [
                    'countries'    => collect([]),
                    'serviceCode'  => $serviceCode,
                    'exchangeRate' => $exchangeRate,
                ])->withErrors('Unable to fetch countries from Provider 1. Please try again.');
            }

            $data      = $response->json();
            $countries = [];

            foreach ($data as $countryName => $details) {
                $priceUSD = $details['Price'] ?? 0;
                $priceNGN = PricingHelper::calculateFinalPrice($priceUSD, self::PROVIDER_5SIM, $exchangeRate);

                $countries[] = [
                    'name'      => $countryName,
                    'code'      => $this->getCountryCode($countryName),
                    'qty'       => $details['Qty'] ?? 0,
                    'price_usd' => $priceUSD,
                    'price_ngn' => $priceNGN,
                    'category'  => $details['Category'] ?? 'activation',
                ];
            }

            usort($countries, fn ($a, $b) => $b['qty'] - $a['qty']);
            $countries = collect($countries);

            $search = request()->get('search', '');
            if ($search) {
                $countries = $countries->filter(fn ($c) => str_contains(strtolower($c['name']), strtolower($search)));
            }

            if (request()->ajax()) {
                return response()->json([
                    'data'          => $countries->values(),
                    'success'       => true,
                    'exchange_rate' => $exchangeRate,
                ]);
            }

            return view('users.countries-5sim', [
                'countries'    => $countries,
                'serviceCode'  => $serviceCode,
                'exchangeRate' => $exchangeRate,
            ]);
        } catch (\Exception $e) {
            Log::error('Provider1 countries exception', ['service' => $serviceCode, 'error' => $e->getMessage()]);
            return view('users.countries-5sim', [
                'countries'    => collect([]),
                'serviceCode'  => $serviceCode,
                'exchangeRate' => $this->get5simExchangeRate(),
            ])->withErrors('An error occurred: ' . $e->getMessage());
        }
    }

    public function showOperators5sim($serviceCode, $country)
    {
        try {
            $normalizedCode = $this->normalize5simServiceCode($serviceCode);
            $exchangeRate   = $this->get5simExchangeRate();
            $fivesimProvider = Provider::where('api_identifier', self::PROVIDER_5SIM)->first();

            $response = Http::timeout(30)->withOptions(['verify' => false])
                ->get("https://5sim.net/v1/guest/prices", [
                    'product' => $normalizedCode,
                    'country' => $country,
                ]);

            $data      = $response->successful() ? $response->json() : null;
            $operators = [];

            if ($data && isset($data[$country][$normalizedCode])) {
                foreach ($data[$country][$normalizedCode] as $operatorName => $details) {
                    $costUSD = $details['cost'] ?? 0;
                    $costNGN = PricingHelper::calculateFinalPrice($costUSD, self::PROVIDER_5SIM, $exchangeRate);
                    $operators[] = [
                        'name'         => $operatorName,
                        'display_name' => $this->formatOperatorName($operatorName),
                        'cost_usd'     => $costUSD,
                        'cost_ngn'     => $costNGN,
                        'count'        => $details['count'] ?? 0,
                        'rate'         => $details['rate']  ?? 0,
                        'rate24'       => $details['rate24'] ?? null,
                        'rate72'       => $details['rate72'] ?? null,
                    ];
                }
            }

            if (empty($operators)) {
                foreach ($fivesimProvider->products ?? [] as $dbProduct) {
                    $operators[] = [
                        'name'         => $dbProduct->product_code,
                        'display_name' => $dbProduct->name,
                        'cost_usd'     => $dbProduct->api_price ?? 0,
                        'cost_ngn'     => $dbProduct->client_price,
                        'count'        => 0,
                        'rate'         => 0,
                        'rate24'       => null,
                        'rate72'       => null,
                    ];
                }
            }

            usort($operators, fn ($a, $b) => $b['count'] - $a['count']);

            return view('users.operators-5sim', [
                'operators'    => collect($operators),
                'serviceCode'  => $serviceCode,
                'country'      => $country,
                'exchangeRate' => $exchangeRate,
            ]);
        } catch (\Exception $e) {
            Log::error('Provider1 operators exception', ['service' => $serviceCode, 'country' => $country, 'error' => $e->getMessage()]);
            return view('users.operators-5sim', [
                'operators'    => collect([]),
                'serviceCode'  => $serviceCode,
                'country'      => $country,
                'exchangeRate' => $this->get5simExchangeRate(),
            ])->withErrors('An error occurred: ' . $e->getMessage());
        }
    }

    public function preparePurchase5sim(Request $request)
    {
        $request->validate([
            'service'  => 'required|string',
            'country'  => 'required|string',
            'operator' => 'required|string',
        ]);

        $serviceCode    = $request->service;
        $normalizedCode = $this->normalize5simServiceCode($serviceCode);
        $country        = strtolower($request->country);
        $operator       = strtolower($request->operator);

        $serviceDetails = $this->getServiceDetails($serviceCode, true);
        $countryDetails = $this->getCountryDetails($country, true);

        $cacheKey  = "5sim_price_{$normalizedCode}_{$country}_{$operator}";
        $priceData = Cache::remember($cacheKey, 180, function () use ($normalizedCode, $country, $operator) {
            $response = Http::timeout(20)->withOptions(['verify' => false])
                ->get("https://5sim.net/v1/guest/prices", [
                    'product' => $normalizedCode,
                    'country' => $country,
                ]);
            if ($response->successful()) {
                $data = $response->json();
                return $data[$country][$normalizedCode][$operator] ?? null;
            }
            return null;
        });

        if (!$priceData || !isset($priceData['cost'])) {
            return back()->withErrors('Unable to fetch pricing. Please try again.');
        }

        $exchangeRate = $this->get5simExchangeRate();
        $costNGN      = PricingHelper::calculateFinalPrice($priceData['cost'], self::PROVIDER_5SIM, $exchangeRate);

        $purchase = [
            'provider'             => self::PROVIDER_5SIM,
            'country'              => $country,
            'service'              => $normalizedCode,
            'service_code_display' => $serviceCode,
            'operator'             => $operator,
            'service_name'         => $serviceDetails['name'],
            'country_name'         => $countryDetails['name'],
            'country_code'         => $countryDetails['code'],
            'operator_name'        => $this->formatOperatorName($operator),
            'cost'                 => $costNGN,
            'original_cost_usd'    => $priceData['cost'],
            'exchange_rate'        => $exchangeRate,
            'total_count'          => $priceData['count'] ?? 0,
            'rate'                 => $priceData['rate']  ?? 0,
            'timestamp'            => time(),
            'session_token'        => bin2hex(random_bytes(16)),
        ];

        if ($purchase['cost'] <= 0) {
            return back()->withErrors('Invalid pricing. Please contact support.');
        }

        session(['purchase' => encrypt($purchase)]);

        return redirect()->route('confirm.payment');
    }

    // ═══════════════════════════════════════════════════════════
    // COMMON: confirm + process
    // ═══════════════════════════════════════════════════════════

    public function showConfirm()
    {
        try {
            $encryptedPurchase = session('purchase');
            if (!$encryptedPurchase) {
                return redirect()->route('services.list')->withErrors('No purchase session found. Please start the process again.');
            }

            $purchase = decrypt($encryptedPurchase);

            $requiredFields = ['service', 'cost', 'timestamp', 'provider'];
            foreach ($requiredFields as $field) {
                if (!isset($purchase[$field])) {
                    session()->forget('purchase');
                    return redirect()->route('services.list')->withErrors('Invalid purchase session. Please start again.');
                }
            }

            if ((time() - $purchase['timestamp']) > 1800) {
                session()->forget('purchase');
                return redirect()->route('services.list')->withErrors('Purchase session expired. Please start the process again.');
            }

            $wallet = auth()->user()->wallet;
            if (!$wallet) {
                return redirect()->route('services.list')->withErrors('No wallet found. Please contact support.');
            }

            if ($wallet->balance < $purchase['cost']) {
                return view('users.numberProcess', compact('purchase', 'wallet'))
                    ->withErrors('Insufficient wallet balance. Please top up your account.');
            }

            return view('users.numberProcess', compact('purchase', 'wallet'));
        } catch (\Exception $e) {
            Log::error('Error in showConfirm', ['error' => $e->getMessage()]);
            session()->forget('purchase');
            return redirect()->route('services.list')->withErrors('An error occurred. Please try again.');
        }
    }

    public function process(Request $request)
    {
        try {
            $request->validate([
                'pin'                  => 'required|digits:4',
                'session_token'        => 'required|string',
                'use_referral_balance' => 'sometimes|boolean',
            ]);

            $encryptedPurchase = session('purchase');
            if (!$encryptedPurchase) {
                return redirect()->route('services.list')->withErrors('Session expired. Please start again.');
            }

            $purchase = decrypt($encryptedPurchase);

            if (!isset($purchase['session_token']) || $purchase['session_token'] !== $request->session_token) {
                session()->forget('purchase');
                return redirect()->route('services.list')->withErrors('Invalid session. Please try again.');
            }

            if ((time() - $purchase['timestamp']) > 1800) {
                session()->forget('purchase');
                return redirect()->route('services.list')->withErrors('Session expired. Please start again.');
            }

            /** @var \App\Models\User $user */
            $user   = auth()->user();
            $wallet = $user->wallet;

            if (!$wallet) {
                return back()->withErrors('Wallet not found. Please contact support.');
            }

            if (!PinHelper::verifyPin($request->pin, $user)) {
                return back()->withErrors('Invalid PIN. Please try again.');
            }

            $cost        = (float) $purchase['cost'];
            $useReferral = !empty($request->use_referral_balance) && ReferralBalanceHelper::isEligible($user);

            $split = $useReferral
                ? ReferralBalanceHelper::calculate($user, $wallet, $cost)
                : ['referral_deduction' => 0, 'wallet_deduction' => $cost, 'can_proceed' => true, 'shortfall' => 0];

            if ((float) $wallet->balance < $split['wallet_deduction']) {
                $referralNote = $split['referral_deduction'] > 0
                    ? ' Your referral balance will cover ₦' . number_format($split['referral_deduction'], 2) . ', but the wallet still needs ₦' . number_format($split['wallet_deduction'], 2) . '.'
                    : ' Required: ₦' . number_format($cost, 2) . '.';
                return back()->withErrors('Insufficient balance.' . $referralNote . ' Please top up your wallet.');
            }

            DB::transaction(function () use ($user, $wallet, $cost, &$split) {
                $deducted                     = ReferralBalanceHelper::apply($user, $wallet, $cost);
                $split['referral_deduction']  = $deducted['referral_deduction'];
                $split['wallet_deduction']    = $deducted['wallet_deduction'];
            });

            if ($purchase['provider'] === self::PROVIDER_5SIM) {
                return $this->process5sim($purchase, $split);
            }

            if ($purchase['provider'] === self::PROVIDER_SMSBOWER) {
                return $this->processSmsBower($purchase, $split);
            }

            if ($purchase['provider'] === self::PROVIDER_SMSBUS) {
                return $this->processSmsbus($purchase, $split);
            }

            if (isset($purchase['provider']) && $purchase['provider'] === 'logs') {
                return $this->processLogs($purchase, $split);
            }

            $this->refundPurchase($purchase, $split, 'Unknown provider');
            return back()->withErrors('Unknown provider. Balance refunded.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator->errors()->first());
        } catch (\Exception $e) {
            Log::error('Payment processing error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            if (isset($purchase) && isset($purchase['cost'])) {
                $this->refundPurchase(
                    $purchase,
                    $split ?? ['wallet_deduction' => $purchase['cost'], 'referral_deduction' => 0],
                    'Payment processing exception'
                );
            }
            return back()->withErrors('An error occurred processing payment. Please try again.');
        }
    }

    /**
     * Reverses a failed purchase, crediting BOTH the wallet portion and the
     * referral-balance portion that were originally deducted — restoring the
     * user to exactly the balance they had before the purchase attempt.
     */
    private function refundPurchase(array $purchase, array $split, string $reason): void
    {
        try {
            $user = auth()->user();
            if (!$user) return;

            $wallet = $user->wallet;
            if (!$wallet) return;

            $walletAmount   = (float) ($split['wallet_deduction']   ?? $purchase['cost'] ?? 0);
            $referralAmount = (float) ($split['referral_deduction'] ?? 0);

            if ($walletAmount <= 0 && $referralAmount <= 0) {
                return;
            }

            DB::transaction(function () use ($user, $wallet, $walletAmount, $referralAmount, $reason) {
                if ($walletAmount > 0) {
                    DB::table('wallets')->where('user_id', $user->id)->increment('balance', $walletAmount);
                }

                if ($referralAmount > 0) {
                    if (method_exists(ReferralBalanceHelper::class, 'refund')) {
                        // Preferred: let the helper reverse whatever it deducted from.
                        ReferralBalanceHelper::refund($user, $referralAmount);
                    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('users', 'referral_balance')) {
                        DB::table('users')->where('id', $user->id)->increment('referral_balance', $referralAmount);
                    } else {
                        // Last resort so the user isn't shortchanged — log loudly so it's caught.
                        DB::table('wallets')->where('user_id', $user->id)->increment('balance', $referralAmount);
                        Log::warning('refundPurchase: no referral refund method/column found, credited wallet instead', [
                            'user_id' => $user->id,
                            'amount'  => $referralAmount,
                        ]);
                    }
                }

                DB::table('transactions')->insert([
                    'reference'   => 'REF_' . strtoupper(Str::random(10)),
                    'user_id'     => $user->id,
                    'type'        => 'credit',
                    'channel'     => 'Refund',
                    'provider'    => 'System',
                    'amount'      => $walletAmount + $referralAmount,
                    'status'      => 'success',
                    'description' => $reason . ' — refund [wallet: ₦' . number_format($walletAmount, 2)
                                    . ', referral: ₦' . number_format($referralAmount, 2) . ']',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            });
        } catch (\Exception $e) {
            Log::error('refundPurchase FAILED — manual intervention needed', [
                'user_id'  => auth()->id(),
                'purchase' => $purchase,
                'split'    => $split,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // PRIVATE — provider processors
    // ═══════════════════════════════════════════════════════════

    /**
     * Process SMSBower purchase (new provider2)
     */
    private function processSmsBower(array $purchase, array $split = [])
    {
        if (!env('SMSBOWER_API_KEY')) {
            $this->refundPurchase($purchase, $split, 'SMSBOWER_API_KEY not configured');
            Log::error('SMSBOWER_API_KEY not configured');
            return back()->withErrors('Service configuration error. Balance refunded.');
        }

        try {
            $result = SmsBowerHelper::buyNumber($purchase['service'], $purchase['country_id'], null, $purchase['provider_id'] ?? null);

            if (!$result || empty($result['id']) || empty($result['phone'])) {
                Log::error('SMSBower buyNumber failed or empty response', [
                    'service' => $purchase['service'], 'country_id' => $purchase['country_id'], 'result' => $result,
                ]);
                $this->refundPurchase($purchase, $split, 'SMSBower purchase failed');
                return back()->withErrors('Failed to purchase number. Your balance has been refunded.');
            }

            $activationId = DB::table('transactions')->insertGetId([
                'user_id'      => auth()->id(),
                'reference'    => 'ACT_' . strtoupper(Str::random(10)),
                'request_id'   => (string) $result['id'],
                'phone'        => $result['phone'],
                'service_id'   => $purchase['service'],
                'service_name' => $purchase['service_name'],
                'service_logo' => null,
                'country_id'   => $purchase['country_id'],
                'country_name' => $purchase['country_name'],
                'country_code' => $purchase['country_code'],
                'amount'       => $purchase['cost'],
                'original_cost_usd' => $purchase['original_cost_usd'] ?? null,
                'status'       => 'pending',
                'type'         => 'activation',
                'channel'      => 'provider2',
                'provider'     => 'Provider 2',
                'sms_code'     => null,
                'expires_at'   => now()->addMinutes(20),
                'response'     => json_encode($result),
                'description'  => "Provider 2 activation for {$purchase['service_name']} - {$purchase['country_name']}",
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            session()->forget('purchase');
            return redirect()->route('history')->with('new_activation', $activationId);
        } catch (\Exception $e) {
            Log::error('SMSBower purchase exception', ['error' => $e->getMessage()]);
            $this->refundPurchase($purchase, $split, 'SMSBower purchase exception');
            return back()->withErrors('An error occurred. Your balance has been refunded.');
        }
    }

    private function process5sim(array $purchase, array $split = [])
    {
        $token = env('FIVESIM_TOKEN');
        if (!$token) {
            $this->refundPurchase($purchase, $split, '5sim token missing');
            return back()->withErrors('Service configuration error. Balance refunded.');
        }

        try {
            $url = sprintf(
                'https://5sim.net/v1/user/buy/activation/%s/%s/%s',
                urlencode($purchase['country']),
                urlencode($purchase['operator']),
                urlencode($purchase['service'])
            );

            $response     = Http::timeout(30)
                ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
                ->get($url);
            $responseData = $response->json();

            if ($response->failed()) {
                Log::error('5sim buy failed', [
                    'url' => $url, 'status' => $response->status(), 'body' => $response->body(),
                ]);
                $this->refundPurchase($purchase, $split, '5sim purchase failed');
                $errorMsg = is_array($responseData) && isset($responseData['message'])
                    ? $responseData['message']
                    : substr($response->body(), 0, 250);
                return back()->withErrors('Failed to purchase number. ' . $errorMsg . ' Your balance has been refunded.');
            }

            $data = $responseData ?? [];
            if (!isset($data['id']) || !isset($data['phone'])) {
                Log::error('5sim buy returned unexpected payload', [
                    'url' => $url, 'status' => $response->status(), 'body' => $response->body(),
                ]);
                $this->refundPurchase($purchase, $split, '5sim invalid response');
                return back()->withErrors('Invalid response from service. Your balance has been refunded.');
            }

            $transactionId = DB::table('transactions')->insertGetId([
                'reference'    => 'ACT_' . strtoupper(Str::random(10)),
                'request_id'   => (string) $data['id'],
                'user_id'      => auth()->id(),
                'type'         => 'activation',
                'channel'      => 'provider1',
                'provider'     => 'Provider 1',
                'service_id'   => $purchase['service'],
                'service_name' => $purchase['service_name'],
                'service_logo' => null,
                'country_id'   => null,
                'country_name' => $purchase['country_name'],
                'country_code' => $this->getCountryCodeFrom5simCountry($purchase['country']),
                'operator_name'=> $purchase['operator_name'] ?? null,
                'phone'        => $data['phone'] ?? null,
                'sms_code'     => null,
                'amount'       => $purchase['cost'],
                'original_cost_usd' => $purchase['original_cost_usd'] ?? null,
                'status'       => 'pending',
                'expires_at'   => now()->addMinutes(20),
                'response'     => json_encode($data),
                'description'  => "Provider 1 activation for {$purchase['service_name']} - {$purchase['country_name']}",
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            session()->forget('purchase');
            return redirect()->route('history')->with('new_activation', $transactionId);
        } catch (\Exception $e) {
            Log::error('5sim purchase exception', ['error' => $e->getMessage()]);
            $this->refundPurchase($purchase, $split, '5sim purchase exception');
            return back()->withErrors('An error occurred. Your balance has been refunded.');
        }
    }

    private function processSmsbus(array $purchase, array $split = [])
    {
        $token = env('SMSBUS_TOKEN');
        if (!$token) {
            $this->refundPurchase($purchase, $split, 'SMSBUS token missing');
            return back()->withErrors('Service configuration error. Balance refunded.');
        }

        try {
            $response = Http::timeout(30)->withOptions(['verify' => false])
                ->get("https://sms-bus.com/api/control/get/number", [
                    'project_id' => $purchase['service'],
                    'country_id' => $purchase['country_id'],
                    'token'      => $token,
                    'reuse'      => 0,
                ]);

            $data = $response->json();
            $code = isset($data['code']) ? (int) $data['code'] : null; // loose-cast: avoid string/int mismatch

            if ($response->failed() || $code !== 200) {
                Log::error('SMS-Bus get/number failed', [
                    'status' => $response->status(), 'body' => $response->body(),
                    'project_id' => $purchase['service'], 'country_id' => $purchase['country_id'],
                ]);
                $this->refundPurchase($purchase, $split, 'SMS-Bus purchase failed');
                $errorMessage = $data['message'] ?? 'Failed to purchase number';
                return back()->withErrors($errorMessage . '. Your balance has been refunded.');
            }

            if (!isset($data['data']['request_id']) || !isset($data['data']['number'])) {
                Log::error('SMS-Bus get/number returned unexpected payload', ['body' => $response->body()]);
                $this->refundPurchase($purchase, $split, 'SMS-Bus invalid response');
                return back()->withErrors('Invalid response from service. Your balance has been refunded.');
            }

            $activationId = DB::table('transactions')->insertGetId([
                'user_id'      => auth()->id(),
                'reference'    => 'ACT_' . strtoupper(Str::random(10)),
                'request_id'   => $data['data']['request_id'],
                'phone'        => $data['data']['number'],
                'service_id'   => $purchase['service'],
                'service_name' => $purchase['service_name'],
                'service_logo' => $purchase['service_logo'] ?? null,
                'country_id'   => $purchase['country_id'],
                'country_name' => $purchase['country_name'],
                'country_code' => $purchase['country_code'],
                'amount'       => $purchase['cost'],
                'original_cost_usd' => $purchase['original_cost_usd'] ?? null,
                'status'       => 'pending',
                'type'         => 'activation',
                'channel'      => 'provider3',
                'provider'     => 'Provider 3',
                'sms_code'     => null,
                'expires_at'   => now()->addMinutes(20),
                'response'     => json_encode($data),
                'description'  => "Provider 3 activation for {$purchase['service_name']} - {$purchase['country_name']}",
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            session()->forget('purchase');
            return redirect()->route('history')->with('new_activation', $activationId);
        } catch (\Exception $e) {
            Log::error('SMS-Bus purchase exception', ['error' => $e->getMessage()]);
            $this->refundPurchase($purchase, $split, 'SMS-Bus purchase exception');
            return back()->withErrors('An error occurred. Your balance has been refunded.');
        }
    }

    // ═══════════════════════════════════════════════════════════
    // PREPARE PURCHASE — SMS-Bus (provider3)
    // ═══════════════════════════════════════════════════════════

    public function preparePurchase(Request $request)
    {
        $request->validate([
            'service'      => 'required|integer',
            'country'      => 'required|integer',
            'country_name' => 'required|string',
            'country_code' => 'required|string',
        ]);

        $serviceId   = $request->service;
        $countryId   = $request->country;
        $countryName = $request->country_name;
        $countryCode = strtolower($request->country_code);
        $token       = env('SMSBUS_TOKEN');

        if (!$token) return back()->withErrors('Service configuration error');

        $cacheKey  = "price_data_{$serviceId}_{$countryId}";
        $priceData = Cache::remember($cacheKey, 300, function () use ($token, $countryId, $serviceId) {
            try {
                $response = Http::timeout(30)->get("https://sms-bus.com/api/control/list/prices", [
                    'token'      => $token,
                    'country_id' => $countryId,
                ]);
                if ($response->failed() || !isset($response->json()['data'])) return null;
                return collect($response->json()['data'])
                    ->where('country_id', $countryId)
                    ->where('project_id', $serviceId)
                    ->first();
            } catch (\Exception $e) {
                return null;
            }
        });

        if (!$priceData || !isset($priceData['cost']) || $priceData['cost'] <= 0) {
            return back()->withErrors('No pricing available for this service.');
        }

        $exchangeRate   = $this->getExchangeRate();
        $costNGN        = PricingHelper::calculateFinalPrice($priceData['cost'], self::PROVIDER_SMSBUS, $exchangeRate);
        $serviceDetails = $this->getServiceDetails($serviceId, false);

        $purchase = [
            'provider'           => self::PROVIDER_SMSBUS,
            'country_id'         => $countryId,
            'country_name'       => $countryName,
            'country_code'       => $countryCode,
            'service'            => $priceData['project_id'],
            'service_name'       => $serviceDetails['name'],
            'service_logo'       => $serviceDetails['logo'] ?? null,
            'cost'               => $costNGN,
            'original_cost_usd'  => $priceData['cost'],
            'exchange_rate'      => $exchangeRate,
            'total_count'        => $priceData['total_count'] ?? 0,
            'timestamp'          => time(),
            'session_token'      => bin2hex(random_bytes(16)),
        ];

        if ($purchase['cost'] <= 0) {
            return back()->withErrors('Invalid service pricing. Please contact support.');
        }

        session(['purchase' => encrypt($purchase)]);
        return redirect()->route('confirm.payment');
    }

    // ═══════════════════════════════════════════════════════════
    // LOGS
    // ═══════════════════════════════════════════════════════════

    public function prepareLogPurchase(Request $request, $product)
    {
        try {
            $product = Product::findOrFail($product);
            $cost = $product->client_price ?? $product->api_price ?? 0;
            if ($cost <= 0) return back()->withErrors('Invalid product pricing.');

            $purchase = [
                'provider'           => 'logs',
                'product_id'         => $product->id,
                'product_name'       => $product->name,
                'product_description'=> $product->description ?? null,
                'cost'               => $cost,
                'timestamp'          => time(),
                'session_token'      => bin2hex(random_bytes(16)),
            ];

            session(['purchase' => encrypt($purchase)]);
            return redirect()->route('confirm.payment');
        } catch (\Exception $e) {
            return back()->withErrors('Unable to prepare purchase. Please try again.');
        }
    }

    private function processLogs(array $purchase)
    {
        try {
            $userId  = auth()->id();
            $product = isset($purchase['product_id']) ? Product::find($purchase['product_id']) : null;
            $reference = 'LOGS' . time() . strtoupper(Str::random(8));

            $transaction = Transaction::create([
                'reference'    => $reference,
                'user_id'      => $userId,
                'type'         => 'logs',
                'service_name' => 'Logs Purchase',
                'channel'      => 'web',
                'provider'     => 'logs',
                'phone'        => null,
                'recipient'    => $product->name ?? ($purchase['product_name'] ?? 'Logs'),
                'amount'       => $purchase['cost'] ?? 0,
                'status'       => 'pending',
                'response'     => ['product' => $product ? $product->toArray() : ($purchase['product_name'] ?? null)],
                'description'  => 'Logs purchase awaiting admin fulfillment',
            ]);

            $adminEmails = Admin::whereNotNull('email')->where('email', '<>', '')->pluck('email')->filter()->unique();
            $validAdminEmails = $adminEmails->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))->values()->all();

            if (!empty($validAdminEmails)) {
                foreach ($validAdminEmails as $adminEmail) {
                    try {
                        Mail::to($adminEmail)->send(new LogsPurchaseAdminNotification($transaction, $product, auth()->user()));
                    } catch (\Exception $mailException) {
                        Log::warning('Failed to send admin logs notification', ['error' => $mailException->getMessage()]);
                    }
                }
            }

            session()->forget('purchase');
            return redirect()->route('history')->with('new_activation', $transaction->id);
        } catch (\Exception $e) {
            Log::error('Error processing logs purchase', ['error' => $e->getMessage()]);
            $this->refundUserBalance($purchase['cost'] ?? 0);
            return back()->withErrors('An error occurred. Your balance has been refunded.');
        }
    }

    public function showLogs(Request $request)
    {
        try {
            $logsService  = \App\Models\Service::firstOrCreate(['slug' => 'logs'], ['name' => 'Logs', 'is_active' => true]);
            $logsProvider = \App\Models\Provider::firstOrCreate(['code' => 'logs'], ['name' => 'Logs Service', 'service_id' => $logsService->id, 'api_identifier' => 'logs']);

            $query = Product::where('provider_id', $logsProvider->id)->where('is_active', true)->orderBy('created_at', 'desc');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('country', 'like', "%{$search}%")
                      ->orWhere('country_code', 'like', "%{$search}%");
                });
            }

            $products = $query->paginate(20);
            $products->getCollection()->transform(function ($product) {
                return (object) [
                    'id'           => $product->id,
                    'name'         => $product->name,
                    'description'  => $product->description ?? 'Premium logs access',
                    'price'        => $product->client_price,
                    'country'      => $product->country ?? 'Global',
                    'country_code' => $product->country_code ? strtoupper($product->country_code) : 'GLB',
                ];
            });

            if ($request->ajax()) {
                $html = view('partials.logs-items', ['products' => $products])->render();
                return response()->json(['success' => true, 'html' => $html, 'has_more' => $products->hasMorePages(), 'next_page' => $products->currentPage() + 1]);
            }

            return view('users.logs', compact('products'));
        } catch (\Exception $e) {
            Log::error('Error displaying logs products', ['error' => $e->getMessage()]);
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'An error occurred loading products.'], 500);
            }
            return back()->withErrors('Unable to load logs products. Please try again.');
        }
    }

    public function checkoutLog($id)
    {
        try {
            $logsService  = \App\Models\Service::firstOrCreate(['slug' => 'logs'], ['name' => 'Logs', 'is_active' => true]);
            $logsProvider = \App\Models\Provider::firstOrCreate(['code' => 'logs'], ['name' => 'Logs Service', 'service_id' => $logsService->id, 'api_identifier' => 'logs']);

            $product = Product::where('id', $id)->where('provider_id', $logsProvider->id)->where('is_active', true)->firstOrFail();

            $productData = (object) [
                'id'           => $product->id,
                'name'         => $product->name,
                'description'  => $product->description ?? 'Premium logs access',
                'price'        => $product->client_price,
                'country'      => $product->country ?? 'Global',
                'country_code' => $product->country_code ? strtoupper($product->country_code) : 'GLB',
            ];

            return view('users.logs-checkout', ['product' => $productData]);
        } catch (\Exception $e) {
            return redirect()->route('logs')->withErrors('Product not found or unavailable.');
        }
    }

    public function processLogPurchase(Request $request, $id)
    {
        $request->validate([
            'pin'                  => 'required|array|size:4',
            'pin.*'                => 'required|string|size:1|regex:/^[0-9]$/',
            'quantity'             => 'required|integer|min:1|max:10',
            'use_referral_balance' => 'sometimes|boolean',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $pin  = implode('', $request->pin);

        if (!\Illuminate\Support\Facades\Hash::check($pin, $user->pin)) {
            return response()->json(['success' => false, 'message' => 'Incorrect PIN. Please try again.']);
        }

        $product  = \App\Models\Product::where('is_active', true)->findOrFail($id);
        $quantity = (int) $request->quantity;
        $total    = $product->client_price * $quantity;
        $wallet   = $user->wallet;

        if (!$wallet) {
            return response()->json(['success' => false, 'message' => 'Wallet not found. Please contact support.']);
        }

        $useReferral = !empty($request->use_referral_balance) && ReferralBalanceHelper::isEligible($user);
        $split = $useReferral
            ? ReferralBalanceHelper::calculate($user, $wallet, $total)
            : ['referral_deduction' => 0, 'wallet_deduction' => $total, 'can_proceed' => true, 'shortfall' => 0];

        if ((float) $wallet->balance < $split['wallet_deduction']) {
            $referralNote = $split['referral_deduction'] > 0
                ? ' Your referral balance will cover ₦' . number_format($split['referral_deduction'], 2) . ', but the wallet still needs ₦' . number_format($split['wallet_deduction'], 2) . '.'
                : '';
            return response()->json(['success' => false, 'message' => 'Insufficient wallet balance.' . $referralNote . ' Please fund your wallet.']);
        }

        try {
            DB::beginTransaction();

            $deducted            = ReferralBalanceHelper::apply($user, $wallet, $total);
            $assignedCredentials = [];
            $mode                = $product->mode ?? 'manual';

            if ($mode === 'automatic') {
                $logDetails = is_array($product->log_details)
                    ? $product->log_details
                    : (json_decode($product->log_details ?? '[]', true) ?: []);

                $availableIndexes = [];
                foreach ($logDetails as $index => $detail) {
                    if (empty($detail['sold'])) {
                        $availableIndexes[] = $index;
                        if (count($availableIndexes) >= $quantity) break;
                    }
                }

                foreach ($availableIndexes as $idx) {
                    $assignedCredentials[] = [
                        'username' => $logDetails[$idx]['username'] ?? '',
                        'password' => $logDetails[$idx]['password'] ?? '',
                        'extra'    => $logDetails[$idx]['extra']    ?? '',
                    ];
                }
            }

            $isAutoFulfilled = $mode === 'automatic' && !empty($assignedCredentials);
            $status          = $isAutoFulfilled ? 'success' : 'pending';

            $responsePayload = [
                'product'          => ['id' => $product->id, 'name' => $product->name, 'mode' => $mode, 'country' => $product->country],
                'quantity'         => $quantity,
                'auto_fulfilled'   => $isAutoFulfilled,
                'credentials'      => [],
                'fulfilled_count'  => 0,
                'referral_deducted'=> $deducted['referral_deduction'],
                'wallet_deducted'  => $deducted['wallet_deduction'],
            ];

            if ($isAutoFulfilled) {
                $credentialSets = [];
                foreach ($assignedCredentials as $cred) {
                    $set = [];
                    if (!empty($cred['username'])) $set[] = "Username: {$cred['username']}";
                    if (!empty($cred['password'])) $set[] = "Password: {$cred['password']}";
                    if (!empty($cred['extra']))    $set[] = $cred['extra'];
                    $credentialSets[] = implode("\n", $set);
                }
                $responsePayload['login_details']   = implode("\n\n", $credentialSets);
                $responsePayload['credentials']     = $credentialSets;
                $responsePayload['fulfilled_count'] = count($credentialSets);
            } else {
                $responsePayload['credentials']    = array_fill(0, $quantity, '');
                $responsePayload['fulfilled_count']= 0;
            }

            $transaction = \App\Models\Transaction::create([
                'reference'    => 'LOG-' . strtoupper(\Illuminate\Support\Str::random(12)),
                'user_id'      => $user->id,
                'type'         => 'logs',
                'channel'      => 'Wallet',
                'provider'     => 'Logs',
                'service_name' => $product->name,
                'recipient'    => $product->name,
                'country_name' => $product->country,
                'country_code' => $product->country_code,
                'amount'       => $total,
                'status'       => $status,
                'description'  => $isAutoFulfilled ? 'Logs order auto-fulfilled' : 'Logs order pending admin fulfilment',
                'response'     => $responsePayload,
            ]);

            if ($isAutoFulfilled) {
                $logDetails = is_array($product->log_details) ? $product->log_details : (json_decode($product->log_details ?? '[]', true) ?: []);
                $soldCount  = 0;
                foreach ($logDetails as &$detail) {
                    if (empty($detail['sold']) && $soldCount < count($assignedCredentials)) {
                        $detail['sold']                = true;
                        $detail['sold_at']             = now()->toDateTimeString();
                        $detail['sold_transaction_id'] = $transaction->id;
                        $detail['sold_to_user_id']     = $user->id;
                        $soldCount++;
                    }
                }
                unset($detail);
                $product->log_details = json_encode(array_values($logDetails));
                $product->save();
            }

            DB::commit();

            try {
                $adminEmails = \App\Models\Admin::whereNotNull('email')->where('email', '<>', '')->pluck('email')->filter()->unique();
                $validAdminEmails = $adminEmails->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))->values()->all();
                if (!empty($validAdminEmails)) {
                    foreach ($validAdminEmails as $adminEmail) {
                        try {
                            \Illuminate\Support\Facades\Mail::to($adminEmail)
                                ->send(new \App\Mail\LogsPurchaseAdminNotification($transaction, $product, $user));
                        } catch (\Exception $mailException) {
                            Log::warning('Failed to send admin logs notification', ['error' => $mailException->getMessage()]);
                        }
                    }
                }
            } catch (\Exception $notifyException) {
                Log::error('Admin logs notification error', ['error' => $notifyException->getMessage()]);
            }

            $successMessage = $isAutoFulfilled
                ? 'Purchase successful! Your login details are ready.'
                : 'Order placed successfully! Your details will be delivered shortly.';

            if ($deducted['referral_deduction'] > 0) {
                $successMessage .= ' ₦' . number_format($deducted['referral_deduction'], 2) . ' was paid from your referral balance.';
            }

            return response()->json([
                'success'           => true,
                'message'           => $successMessage,
                'referral_deducted' => $deducted['referral_deduction'],
                'wallet_deducted'   => $deducted['wallet_deduction'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Log purchase error', ['error' => $e->getMessage(), 'product_id' => $id, 'user_id' => $user->id]);
            return response()->json(['success' => false, 'message' => 'Purchase failed. Please try again.']);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // EXCHANGE RATES
    // ═══════════════════════════════════════════════════════════

    private function getExchangeRate(): float
    {
        try {
            // Get SMS-Bus provider (provider_id = 9)
            $smsbusProvider = \App\Models\Provider::where('api_identifier', 'smsbus')->first();
            if ($smsbusProvider) {
                $rateProduct = \App\Models\Product::where('provider_id', $smsbusProvider->id)
                    ->where('product_code', 'conversion_rate')->where('is_active', true)->first();
                if ($rateProduct && $rateProduct->client_price > 0) {
                    return (float) $rateProduct->client_price;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fetching SMS-Bus exchange rate', ['error' => $e->getMessage()]);
        }
        return 1500.0;
    }

    private function get5simExchangeRate(): float
    {
        try {
            // Get 5sim provider (provider_id = 10)
            $fivesimProvider = \App\Models\Provider::where('api_identifier', '5sim')->first();
            if ($fivesimProvider) {
                $rateProduct = \App\Models\Product::where('provider_id', $fivesimProvider->id)
                    ->where('product_code', 'conversion_rate')->where('is_active', true)->first();
                if ($rateProduct && $rateProduct->client_price > 0) {
                    return (float) $rateProduct->client_price;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fetching 5sim exchange rate', ['error' => $e->getMessage()]);
        }
        return 1600.0;
    }

    private function getSmsBowerExchangeRate(): float
    {
        try {
            // Get SMSBower provider (provider_id = 12)
            $smsbowerProvider = \App\Models\Provider::where('api_identifier', 'smsbower')->first();
            if ($smsbowerProvider) {
                $rateProduct = \App\Models\Product::where('provider_id', $smsbowerProvider->id)
                    ->where('product_code', 'conversion_rate')->where('is_active', true)->first();
                if ($rateProduct && $rateProduct->client_price > 0) {
                    return (float) $rateProduct->client_price;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fetching SMSBower exchange rate', ['error' => $e->getMessage()]);
        }
        return 1500.0;
    }

    // ═══════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════

    private function getSmsBowerServiceDetails(string $serviceCode): array
    {
        $cacheKey = "smsbower_service_{$serviceCode}";
        return Cache::remember($cacheKey, 3600, function () use ($serviceCode) {
            $services = SmsBowerHelper::getServices();
            foreach ($services as $svc) {
                if (strtolower($svc['code']) === strtolower($serviceCode)) {
                    return ['name' => $svc['name'], 'code' => $svc['code']];
                }
            }
            return ['name' => ucfirst($serviceCode), 'code' => $serviceCode];
        });
    }

    private function getServiceDetails($serviceIdentifier, $isCode = false): array
    {
        try {
            if ($isCode) {
                $cacheKey    = "smsbus_service_by_code_{$serviceIdentifier}";
                $serviceData = Cache::remember($cacheKey, 300, function () use ($serviceIdentifier) {
                    $token    = env('SMSBUS_TOKEN');
                    $response = Http::timeout(20)->get("https://sms-bus.com/api/control/list/projects", ['token' => $token]);
                    if ($response->successful()) {
                        $data = $response->json('data');
                        if (is_array($data)) {
                            foreach ($data as $service) {
                                if (strtolower($service['code']) === strtolower($serviceIdentifier)) {
                                    return ['name' => $service['title'], 'code' => $service['code'], 'id' => $service['id']];
                                }
                            }
                        }
                    }
                    return null;
                });
                if ($serviceData) return ['name' => $serviceData['name'], 'logo' => null, 'code' => $serviceData['code']];
                return ['name' => ucfirst($serviceIdentifier), 'logo' => null, 'code' => $serviceIdentifier];
            } else {
                $cacheKey    = "smsbus_service_{$serviceIdentifier}";
                $serviceData = Cache::remember($cacheKey, 300, function () use ($serviceIdentifier) {
                    $token    = env('SMSBUS_TOKEN');
                    $response = Http::timeout(20)->get("https://sms-bus.com/api/control/list/projects", ['token' => $token]);
                    if ($response->successful()) {
                        $data = $response->json('data');
                        if (is_array($data)) {
                            foreach ($data as $service) {
                                if ($service['id'] == $serviceIdentifier) {
                                    return ['name' => $service['title'], 'code' => $service['code'], 'id' => $service['id']];
                                }
                            }
                        }
                    }
                    return null;
                });
                if ($serviceData) return ['name' => $serviceData['name'], 'logo' => null, 'code' => $serviceData['code']];
            }
        } catch (\Exception $e) {
            Log::error('Error fetching service details', ['identifier' => $serviceIdentifier, 'error' => $e->getMessage()]);
        }
        return ['name' => 'Unknown Service', 'logo' => null, 'code' => 'unknown'];
    }

    private function getCountryDetails($countryIdentifier, $isName = false): array
    {
        try {
            if ($isName) {
                return ['name' => ucfirst($countryIdentifier), 'code' => $this->getCountryCode($countryIdentifier)];
            } else {
                $token    = env('SMSBUS_TOKEN');
                $cacheKey = "smsbus_country_{$countryIdentifier}";
                $countryData = Cache::remember($cacheKey, 300, function () use ($token, $countryIdentifier) {
                    $response = Http::timeout(20)->get("https://sms-bus.com/api/control/list/prices", [
                        'token'      => $token,
                        'country_id' => $countryIdentifier,
                    ]);
                    if ($response->successful()) {
                        $data = $response->json('data');
                        if (is_array($data) && !empty($data)) {
                            $first = $data[0];
                            return ['name' => $first['country_name'] ?? 'Unknown', 'code' => strtolower($first['country_code'] ?? 'xx')];
                        }
                    }
                    return null;
                });
                if ($countryData) return $countryData;
            }
        } catch (\Exception $e) {
            Log::error('Error fetching country details', ['identifier' => $countryIdentifier, 'error' => $e->getMessage()]);
        }
        return ['name' => 'Unknown Country', 'code' => 'xx'];
    }

    private function getCountryCode($countryName): string
    {
        $normalized = strtolower(str_replace([' ', '-', '_'], '', $countryName));
        $map = [
            'russia'=>'ru','ukraine'=>'ua','kazakhstan'=>'kz','china'=>'cn','philippines'=>'ph',
            'myanmar'=>'mm','indonesia'=>'id','malaysia'=>'my','kenya'=>'ke','tanzania'=>'tz',
            'vietnam'=>'vn','kyrgyzstan'=>'kg','usa'=>'us','israel'=>'il','hongkong'=>'hk',
            'poland'=>'pl','england'=>'gb','nigeria'=>'ng','india'=>'in','egypt'=>'eg',
            'france'=>'fr','germany'=>'de','italy'=>'it','spain'=>'es','ghana'=>'gh',
            'canada'=>'ca','turkey'=>'tr','southafrica'=>'za','brazil'=>'br','mexico'=>'mx',
            'japan'=>'jp','australia'=>'au','singapore'=>'sg','saudiarabia'=>'sa',
            'pakistan'=>'pk','bangladesh'=>'bd','unitedstates'=>'us','unitedkingdom'=>'gb',
        ];
        return $map[$normalized] ?? 'un';
    }

    private function getCountryCodeFrom5simCountry($countryName): string
    {
        return $this->getCountryCode($countryName);
    }

    private function normalize5simServiceCode($serviceCode): string
    {
        $serviceMap = [
            'fb'=>'facebook','wa'=>'whatsapp','tg'=>'telegram','google'=>'google',
            'ig'=>'instagram','twitter'=>'twitter','tiktok'=>'tiktok','snapchat'=>'snapchat',
            'linkedin'=>'linkedin','viber'=>'viber','wechat'=>'wechat','line'=>'line',
            'microsoft'=>'microsoft','yahoo'=>'yahoo','amazon'=>'amazon','netflix'=>'netflix',
        ];
        $lowerCode = strtolower($serviceCode);
        return $serviceMap[$lowerCode] ?? $lowerCode;
    }

    private function formatOperatorName($operatorName): string
    {
        $formatted = str_replace(['_', '-', '.'], ' ', $operatorName);
        $formatted = ucwords(strtolower($formatted));
        return trim(str_ireplace(['Mts','Beeline','Tele2','Megafon','Air','Sms'], ['MTS','Beeline','Tele2','MegaFon','Air','SMS'], $formatted));
    }

    private function refundUserBalance($amount): void
    {
        try {
            $user   = auth()->user();
            $wallet = $user->wallet;
            if ($wallet) {
                DB::transaction(function () use ($wallet, $amount) {
                    $wallet->balance += $amount;
                    $wallet->save();
                    DB::table('transactions')->insert([
                        'reference'   => 'REF_' . strtoupper(Str::random(10)),
                        'user_id'     => $wallet->user_id,
                        'type'        => 'credit',
                        'channel'     => 'Refund',
                        'provider'    => 'System',
                        'amount'      => $amount,
                        'status'      => 'success',
                        'description' => 'Automatic refund due to purchase failure',
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                });
            }
        } catch (\Exception $e) {
            Log::error('Failed to refund balance', ['user_id' => auth()->id(), 'amount' => $amount, 'error' => $e->getMessage()]);
        }
    }

    private function getMockServices()
    {
        return collect([]);
    }

    private function buildServiceItemsHtml($products): string
    {
        $html = '';
        foreach ($products as $product) {
            $iconDisplay = str_contains($product['icon_url'], '.svg')
                ? '<div class="w-6 h-6 service-icon" style="background-image: url(\'' . $product['icon_url'] . '\'); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>'
                : '<img src="' . $product['icon_url'] . '" alt="' . htmlspecialchars($product['title']) . '" class="w-6 h-6 object-contain" loading="lazy" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">
                   <div class="w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold" style="display: none;">' . strtoupper(substr($product['title'], 0, 2)) . '</div>';

            $html .= '<button onclick="handleServiceClick(\'' . $product['id'] . '\', \'' . addslashes($product['title']) . '\', \'' . strtolower($product['code']) . '\')" class="w-full block group">
                <div class="flex items-center space-x-4 p-4 border border-gray-100 rounded-xl hover:border-green-200 hover:bg-green-50/50 transition-all duration-200 group-hover:shadow-sm">
                    <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center overflow-hidden flex-shrink-0 group-hover:bg-white transition-colors duration-200">' . $iconDisplay . '</div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-gray-800 group-hover:text-green-700 transition-colors">' . htmlspecialchars($product['title']) . '</h3>
                        <p class="text-xs text-gray-500 mt-1">' . strtoupper($product['code']) . '</p>
                    </div>
                    <div class="flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-green-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg></div>
                </div></button>';
        }
        return $html;
    }

    private function getServiceIconMap(): array
    {
        return [
            'facebook'  => 'https://cdn-icons-png.flaticon.com/512/733/733547.png',
            'instagram' => 'https://cdn-icons-png.flaticon.com/512/2111/2111463.png',
            'twitter'   => 'https://cdn-icons-png.flaticon.com/512/733/733579.png',
            'tiktok'    => 'https://cdn-icons-png.flaticon.com/512/3046/3046121.png',
            'whatsapp'  => 'https://cdn-icons-png.flaticon.com/512/733/733585.png',
            'telegram'  => 'https://cdn-icons-png.flaticon.com/512/2111/2111646.png',
            'gmail'     => 'https://cdn-icons-png.flaticon.com/512/732/732200.png',
            'youtube'   => 'https://cdn-icons-png.flaticon.com/512/1384/1384060.png',
            'snapchat'  => 'https://cdn-icons-png.flaticon.com/512/2111/2111800.png',
            'amazon'    => 'https://cdn-icons-png.flaticon.com/512/5968/5968253.png',
            'microsoft' => 'https://cdn-icons-png.flaticon.com/512/732/732221.png',
            'yahoo'     => 'https://cdn-icons-png.flaticon.com/512/732/732205.png',
            'netflix'   => 'https://cdn-icons-png.flaticon.com/512/5977/5977590.png',
            'wechat'    => 'https://cdn-icons-png.flaticon.com/512/888/888879.png',
            'paypal'    => 'https://cdn-icons-png.flaticon.com/512/174/174861.png',
            'linkedin'  => 'https://cdn-icons-png.flaticon.com/512/174/174857.png',
            'default'   => 'https://cdn-icons-png.flaticon.com/512/1828/1828884.png',
        ];
    }

    private function fetchCountriesFromAPI(): array { return []; }

/**
 * Normalize the incoming `provider` value (from the UI: provider1/provider2/provider3,
 * or legacy internal names 5sim/smsbower/smsbus) into a canonical UI slug.
 * Returns null when no provider filter was requested.
 */
private function normalizeProviderSlug(?string $provider): ?string
{
    if (empty($provider)) {
        return null;
    }

    $provider = strtolower(trim($provider));

    return match ($provider) {
        'provider1', '5sim'      => 'provider1',
        'provider2', 'smsbower'  => 'provider2',
        'provider3', 'smsbus'    => 'provider3',
        default                  => null,
    };
}

/**
 * Map a UI provider slug (provider1/provider2/provider3) to the internal
 * identifier used throughout the rest of this controller.
 */
private function providerSlugToInternal(?string $slug): ?string
{
    return match ($slug) {
        'provider1' => self::PROVIDER_5SIM,
        'provider2' => self::PROVIDER_SMSBOWER,
        'provider3' => self::PROVIDER_SMSBUS,
        default     => null,
    };
}

/**
 * Live catalog of 5sim services via the guest "products" endpoint
 * (country=any, operator=any). This is what Provider 1's service list
 * should be built from — NOT the local `products` table, which only
 * holds internal pricing rows like "conversion_rate", not real services.
 */
private function getFiveSimServiceCatalog(): array
{
    return Cache::remember('5sim_service_catalog', 3600, function () {
        try {
            $response = Http::timeout(30)
                ->withOptions(['verify' => false])
                ->get('https://5sim.net/v1/guest/products/any/any');

            if (!$response->successful()) {
                Log::error('5sim products catalog fetch failed', ['status' => $response->status()]);
                return [];
            }

            $data = $response->json();
            if (!is_array($data)) {
                return [];
            }

            $catalog = [];
            foreach ($data as $code => $details) {
                if (!is_array($details)) {
                    continue;
                }

                // Only real, currently-buyable activation numbers belong on
                // the "get number" flow — skip rental/hosting categories and
                // anything with zero stock.
                $category = strtolower($details['Category'] ?? 'activation');
                if ($category !== 'activation') {
                    continue;
                }

                $qty = (int) ($details['Qty'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $catalog[] = [
                    'code' => strtolower($code),
                    'name' => ucfirst(str_replace(['_', '-'], ' ', $code)),
                    'qty'  => $qty,
                ];
            }

            return $catalog;
        } catch (\Exception $e) {
            Log::error('5sim products catalog exception', ['error' => $e->getMessage()]);
            return [];
        }
    });
}

}