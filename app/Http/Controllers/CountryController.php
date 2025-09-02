<?php

namespace App\Http\Controllers;

use App\Helpers\PinHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CountryController extends Controller
{

public function showServices(Request $request)
{
    $token = env('SMSBUS_TOKEN');

    // Check if we have a fresh cache first
    $cacheKey = 'smsbus_services';
    $backupCacheKey = 'smsbus_services_backup';
    
    $products = Cache::get($cacheKey);
    
    if (!$products || $products->isEmpty()) {
        try {
            // Try to fetch from API with better configuration
            $response = Http::timeout(30)
                ->connectTimeout(15)
                ->retry(3, function ($exception, $request) {
                    // Log retry attempts
                    \Log::info('Retrying SMS-Bus API call', ['attempt' => $request->attempt ?? 1]);
                    return 2000; // Wait 2 seconds between retries
                })
                ->withOptions([
                    'verify' => false, // Skip SSL verification if needed
                    'curl' => [
                        CURLOPT_DNS_CACHE_TIMEOUT => 300,
                        CURLOPT_FRESH_CONNECT => false,
                    ]
                ])
                ->get("https://sms-bus.com/api/control/list/projects", [
                    'token' => $token
                ]);

            if ($response->successful()) {
                $data = $response->json('data');
                if (is_array($data) && !empty($data)) {
                    $products = collect($data)->values();
                    
                    // Cache successful response
                    Cache::put($cacheKey, $products, now()->addMinutes(30));
                    Cache::put($backupCacheKey, $products, now()->addDays(7)); // Long-term backup
                    
                    \Log::info('SMS-Bus API call successful', ['count' => $products->count()]);
                } else {
                    throw new \Exception('Invalid API response data');
                }
            } else {
                throw new \Exception('API returned status: ' . $response->status());
            }
            
        } catch (\Exception $e) {
            \Log::error('SMS-Bus API Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Try to use backup cache
            $products = Cache::get($backupCacheKey);
            
            if (!$products || $products->isEmpty()) {
                // If no backup, use mock data for development
                $products = collect([
                    ['id' => 1, 'code' => 'WA', 'title' => 'WhatsApp'],
                    ['id' => 2, 'code' => 'FB', 'title' => 'Facebook'],
                    ['id' => 3, 'code' => 'TG', 'title' => 'Telegram'],
                    ['id' => 4, 'code' => 'GOOGLE', 'title' => 'Google'],
                    ['id' => 5, 'code' => 'IG', 'title' => 'Instagram'],
                    ['id' => 6, 'code' => 'TK', 'title' => 'TikTok'],
                    ['id' => 7, 'code' => 'PP', 'title' => 'PayPal'],
                    ['id' => 8, 'code' => 'UB', 'title' => 'Uber'],
                    ['id' => 9, 'code' => 'NETFLIX', 'title' => 'Netflix'],
                    ['id' => 10, 'code' => 'SPOTIFY', 'title' => 'Spotify'],
                    ['id' => 11, 'code' => 'LINKEDIN', 'title' => 'LinkedIn'],
                    ['id' => 12, 'code' => 'DISCORD', 'title' => 'Discord'],
                    ['id' => 13, 'code' => 'MICROSOFT', 'title' => 'Microsoft'],
                    ['id' => 14, 'code' => 'YAHOO', 'title' => 'Yahoo'],
                    ['id' => 15, 'code' => 'VB', 'title' => 'Viber'],
                    ['id' => 16, 'code' => 'KAKAO', 'title' => 'KakaoTalk'],
                    ['id' => 17, 'code' => 'COINBASE', 'title' => 'Coinbase'],
                    ['id' => 18, 'code' => 'EBAY', 'title' => 'eBay'],
                    ['id' => 19, 'code' => 'ALIEXPRESS', 'title' => 'AliExpress'],
                    ['id' => 20, 'code' => 'IMO', 'title' => 'imo']
                ]);
                
                \Log::warning('Using mock data due to API failure');
            } else {
                \Log::info('Using backup cache data', ['count' => $products->count()]);
            }
        }
    }

    // Helper function to get service icon (cached separately)
    $getServiceIcon = function($code) {
        return Cache::remember('service_icons_map_v2', now()->addDays(1), function() {
            return [
                // Popular services
                'wa' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/whatsapp.svg',
                'fb' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/facebook.svg',
                'tg' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/telegram.svg',
                'google' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/google.svg',
                'ig' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/instagram.svg',
                'youtube' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/youtube.svg',
                'twitter' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/twitter.svg',
                'discord' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/discord.svg',
                'netflix' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/netflix.svg',
                'spotify' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/spotify.svg',
                'linkedin' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/linkedin.svg',
                
                // Payment services
                'pp' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/paypal.svg',
                'paypal' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/paypal.svg',
                'coinbase' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/coinbase.svg',
                'wise' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/wise.svg',
                'skrill' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/skrill.svg',
                
                // Social & Communication
                'tk' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/tiktok.svg',
                'tiktok' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/tiktok.svg',
                'vb' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/viber.svg',
                'viber' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/viber.svg',
                'kakao' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/kakaotalk.svg',
                'snapchat' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/snapchat.svg',
                'groupme' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/groupme.svg',
                'imo' => 'https://logo.clearbit.com/imo.im',
                
                // Dating apps
                'hinge' => 'https://logo.clearbit.com/hinge.co',
                'omiai' => 'https://ui-avatars.com/api/?name=OM&background=ff6b6b&color=ffffff&size=96&format=svg',
                'zoosk' => 'https://logo.clearbit.com/zoosk.com',
                'okcupid' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/okcupid.svg',
                
                // Tech companies
                'microsoft' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/microsoft.svg',
                'azure' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/microsoftazure.svg',
                'yahoo' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/yahoo.svg',
                'aol' => 'https://logo.clearbit.com/aol.com',
                
                // E-commerce
                'ub' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/uber.svg',
                'uber' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/uber.svg',
                'ebay' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/ebay.svg',
                'aliexpress' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/aliexpress.svg',
                'vinted' => 'https://logo.clearbit.com/vinted.com',
                'depop' => 'https://logo.clearbit.com/depop.com',
                'wish' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/wish.svg',
                'eneba' => 'https://logo.clearbit.com/eneba.com',
                
                // Gaming
                'geegee' => 'https://ui-avatars.com/api/?name=GG&background=6366f1&color=ffffff&size=96&format=svg',
                'taptap' => 'https://ui-avatars.com/api/?name=TT&background=10b981&color=ffffff&size=96&format=svg',
                'taito' => 'https://ui-avatars.com/api/?name=TT&background=8b5cf6&color=ffffff&size=96&format=svg',
                'bz' => 'https://logo.clearbit.com/blizzard.com',
                'blizzard' => 'https://logo.clearbit.com/blizzard.com',
                'nc' => 'https://logo.clearbit.com/ncsoft.com',
                'ncsoft' => 'https://logo.clearbit.com/ncsoft.com',
                
                // Others
                'bot' => 'https://ui-avatars.com/api/?name=🤖&background=374151&color=ffffff&size=96&format=svg',
                'af' => 'https://logo.clearbit.com/affirm.com',
                'affirm' => 'https://logo.clearbit.com/affirm.com',
                'curtsy' => 'https://logo.clearbit.com/curtsy.com',
                'wild' => 'https://ui-avatars.com/api/?name=WD&background=ef4444&color=ffffff&size=96&format=svg',
                'betfair' => 'https://logo.clearbit.com/betfair.com',
                'superbet' => 'https://ui-avatars.com/api/?name=SB&background=dc2626&color=ffffff&size=96&format=svg',
                'aarp' => 'https://logo.clearbit.com/aarp.org',
                'tamtam' => 'https://ui-avatars.com/api/?name=TT&background=3b82f6&color=ffffff&size=96&format=svg',
                'go2bank' => 'https://ui-avatars.com/api/?name=G2&background=059669&color=ffffff&size=96&format=svg',
                'appen' => 'https://logo.clearbit.com/appen.com',
                'goalry' => 'https://ui-avatars.com/api/?name=GL&background=f59e0b&color=ffffff&size=96&format=svg',
                'fiverr' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/fiverr.svg',
                'tixcraft' => 'https://ui-avatars.com/api/?name=TX&background=7c3aed&color=ffffff&size=96&format=svg',
                'xianyu' => 'https://ui-avatars.com/api/?name=XY&background=f97316&color=ffffff&size=96&format=svg',
                'ininal' => 'https://ui-avatars.com/api/?name=IN&background=06b6d4&color=ffffff&size=96&format=svg',
                'oldubil' => 'https://ui-avatars.com/api/?name=OL&background=84cc16&color=ffffff&size=96&format=svg',
                'paypay' => 'https://ui-avatars.com/api/?name=PP&background=dc2626&color=ffffff&size=96&format=svg',
                
                // Email & Communication
                'getemail' => 'https://ui-avatars.com/api/?name=EM&background=3b82f6&color=ffffff&size=96&format=svg',
                'gamemail' => 'https://ui-avatars.com/api/?name=GM&background=6366f1&color=ffffff&size=96&format=svg',
                'rocketreach' => 'https://logo.clearbit.com/rocketreach.co',
                
                // Fitness & Lifestyle
                'wondercise' => 'https://ui-avatars.com/api/?name=WC&background=10b981&color=ffffff&size=96&format=svg',
                'asiamiles' => 'https://ui-avatars.com/api/?name=AM&background=0ea5e9&color=ffffff&size=96&format=svg',
                
                // Social Media (Additional)
                'red' => 'https://ui-avatars.com/api/?name=红&background=dc2626&color=ffffff&size=96&format=svg',
                'rednote' => 'https://ui-avatars.com/api/?name=红&background=dc2626&color=ffffff&size=96&format=svg',
            ];
        });
    };

    // Add icon URLs to products
    $iconMap = $getServiceIcon('');
    $products = $products->map(function ($product) use ($iconMap) {
        $code = strtolower($product['code']);
        $iconUrl = $iconMap[$code] ?? null;
        
        // If no specific icon found, create a placeholder with service initials
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

    $popularCodes = ['wa', 'fb', 'tg', 'google', 'ig'];
    $popular = $products->filter(fn($p) => in_array(strtolower($p['code']), $popularCodes));

    // Apply search if provided
    $search = $request->input('search');
    if ($search) {
        $products = $products->filter(function($p) use ($search) {
            return str_contains(strtolower($p['title']), strtolower($search)) ||
                   str_contains(strtolower($p['code']), strtolower($search));
        });
    }

    // Exclude popular from main list when not searching
    $others = $search ? $products : $products->reject(fn($p) => in_array(strtolower($p['code']), $popularCodes));

    // Paginate
    $perPage = 30;
    $page = $request->input('page', 1);
    $paginated = $others->forPage($page, $perPage)->values();

    // AJAX response for infinite scroll
    if ($request->ajax()) {
        return response()->json([
            'html' => view('users.partials.service_items', ['products' => $paginated])->render(),
            'next_page' => ($others->count() > $page * $perPage) ? $page + 1 : null,
        ]);
    }

    return view('users.services', [
        'popular'   => $popular,
        'products'  => $paginated,
        'nextPage'  => ($others->count() > $perPage) ? 2 : null
    ]);
}




public function showCountries($serviceId)
{
    $token = env('SMSBUS_TOKEN');

    $response = Http::get("https://sms-bus.com/api/control/list/countries", [
        'token' => $token
    ]);
    
    $countries = collect();
    if ($response->successful()) {
        $countries = collect($response->json('data'))->values();
    }

    // Pagination setup
    $perPage = 20;
    $page = request()->get('page', 1);
    $total = $countries->count();

    $paginatedCountries = new LengthAwarePaginator(
        $countries->forPage($page, $perPage),
        $total,
        $perPage,
        $page,
        ['path' => request()->url(), 'query' => request()->query()]
    );

    return view('users.countries', [
        'countries' => $paginatedCountries,
        'serviceId' => $serviceId
    ]);
}

// Updated getPrices method
function getPrices(Request $request, $serviceId, $countryId)
{
    $token = env('SMSBUS_TOKEN');

    $response = Http::get("https://sms-bus.com/api/control/list/prices", [
        'token'      => $token,
        'country_id' => $countryId,
    ]);

    if ($response->failed()) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to fetch prices',
        ], 500);
    }

    $data = $response->json();

    if (!isset($data['data'])) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid response from API',
        ], 500);
    }

    // Filter and map prices for the selected service
    $prices = collect($data['data'])
        ->filter(function ($item) use ($serviceId) {
            return (int) $item['project_id'] === (int) $serviceId;
        })
        ->map(function ($item) {
            return [
                'project_id' => $item['project_id'],
                'name'       => ucfirst($item['project_code']),
                'cost'       => $item['cost'],
                'count'      => $item['total_count'],
                'country_id' => $item['country_id'], // Add country_id to response
                'country_name' => $item['country_name'] ?? 'Unknown',
                'country_code' => $item['country_code'] ?? '',
            ];
        })
        ->values()
        ->toArray();

    return response()->json([
        'status' => true,
        'data'   => $prices,
    ]);
}

private function getExchangeRate()
{
    try {
        // Example: fetch from an external API like exchangerate.host
        $response = Http::timeout(10)->get('https://api.exchangerate.host/latest', [
            'base' => 'USD',
            'symbols' => 'NGN'
        ]);

        if ($response->ok() && isset($response->json()['rates']['NGN'])) {
            return $response->json()['rates']['NGN'];
        }

        // fallback exchange rate if API fails
        return 1500; 
    } catch (\Exception $e) {
        Log::error('Exchange rate fetch failed', ['error' => $e->getMessage()]);
        return 1500; // fallback
    }
}

private function getServiceDetails($serviceId)
{
    $token = env('SMSBUS_TOKEN');
    if (!$token) {
        Log::error('SMSBUS_TOKEN not configured');
        return ['name' => 'Unknown Service', 'logo' => null];
    }

    // Cache for 10 minutes to avoid hitting API every time
    $cacheKey = "service_details_all";

    $services = Cache::remember($cacheKey, 600, function () use ($token) {
        try {
            $response = Http::timeout(20)->get("https://sms-bus.com/api/control/list/projects", [
                'token' => $token
            ]);

            if ($response->failed() || !isset($response->json()['data'])) {
                Log::error('Failed fetching service details', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            return $response->json()['data'];
        } catch (\Exception $e) {
            Log::error('Error fetching service details', ['error' => $e->getMessage()]);
            return [];
        }
    });

    if (isset($services[$serviceId])) {
        return [
            'name' => $services[$serviceId]['title'],
            'logo' => "/images/services/" . strtolower($services[$serviceId]['code']) . ".png" // optional local logos
        ];
    }

    return ['name' => 'Unknown Service', 'logo' => null];
}


// Updated preparePurchase method
public function preparePurchase(Request $request)
{
    $request->validate([
        'service' => 'required|integer',
        'country' => 'required|integer',
    ]);

    $serviceId = $request->service;
    $countryId = $request->country;
    $token = env('SMSBUS_TOKEN');

    if (!$token) {
        Log::error('SMSBUS_TOKEN not configured');
        return back()->withErrors('Service configuration error');
    }

    // Fetch price info securely with caching
    $cacheKey = "price_data_{$serviceId}_{$countryId}";
    $priceData = Cache::remember($cacheKey, 300, function () use ($token, $countryId, $serviceId) {
        try {
            $response = Http::timeout(30)->get("https://sms-bus.com/api/control/list/prices", [
                'token' => $token,
                'country_id' => $countryId,
            ]);

            if ($response->failed() || !isset($response->json()['data'])) {
                Log::error('SMS-Bus API failed in preparePurchase', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'country_id' => $countryId
                ]);
                return null;
            }

            $responseData = $response->json();
            
            // Find the price data that matches both country and service
            $matchedData = collect($responseData['data'])
                ->where('country_id', $countryId)
                ->where('project_id', $serviceId)
                ->first();

            if (!$matchedData) {
                Log::warning('No matching price data found', [
                    'country_id' => $countryId,
                    'service_id' => $serviceId,
                    'available_data' => collect($responseData['data'])->pluck('country_id', 'project_id')->toArray()
                ]);
            }

            return $matchedData;
            
        } catch (\Exception $e) {
            Log::error('Error fetching price data', [
                'error' => $e->getMessage(),
                'country_id' => $countryId,
                'service_id' => $serviceId
            ]);
            return null;
        }
    });

    if (!$priceData) {
        return back()->withErrors('No pricing available for this service in the selected country. Please try a different country or service.');
    }

    // Validate that we have essential pricing data
    if (!isset($priceData['cost']) || $priceData['cost'] <= 0) {
        Log::warning('Invalid price data', ['price_data' => $priceData]);
        return back()->withErrors('Invalid pricing data. Please try again.');
    }

    // Get exchange rate with fallback
    $usdToNgn = $this->getExchangeRate();

    // Get service details
    $serviceDetails = $this->getServiceDetails($serviceId);

    $purchase = [
        'country_id' => $countryId,
        'country_name' => $priceData['country_name'] ?? 'Unknown',
        'country_code' => strtolower($priceData['country_code'] ?? ''),
        'service' => $priceData['project_id'],
        'service_name' => $serviceDetails['name'] ?? ucfirst($priceData['project_code'] ?? 'Unknown Service'),
        'service_logo' => $serviceDetails['logo'] ?? null,
        'cost' => round($priceData['cost'] * $usdToNgn, 2),
        'original_cost_usd' => $priceData['cost'],
        'exchange_rate' => $usdToNgn,
        'total_count' => $priceData['total_count'] ?? 0, // Add available count
        'flag' => isset($priceData['country_code']) && $priceData['country_code']
            ? "https://flagcdn.com/w40/" . strtolower($priceData['country_code']) . ".png" 
            : "https://via.placeholder.com/40?text=" . substr($priceData['country_name'] ?? 'UK', 0, 2),
        'timestamp' => time(),
        'session_token' => bin2hex(random_bytes(16)),
    ];

    // Additional validation before storing
    if ($purchase['cost'] <= 0) {
        return back()->withErrors('Invalid service pricing. Please contact support.');
    }

    // Store in session with encryption
    session(['purchase' => encrypt($purchase)]);

    Log::info('Purchase session created', [
        'user_id' => auth()->id(),
        'service' => $serviceId,
        'country' => $countryId,
        'cost_ngn' => $purchase['cost'],
        'cost_usd' => $purchase['original_cost_usd']
    ]);

    return redirect()->route('confirm.payment');
}

// Enhanced showConfirm method with better error handling
public function showConfirm()
{
    try {
        $encryptedPurchase = session('purchase');
        
        if (!$encryptedPurchase) {
            Log::warning('No purchase session found', ['user_id' => auth()->id()]);
            return redirect()->route('services.index')->withErrors('No purchase session found. Please start the process again.');
        }

        $purchase = decrypt($encryptedPurchase);

        // Validate required fields
        $requiredFields = ['country_id', 'service', 'cost', 'timestamp'];
        foreach ($requiredFields as $field) {
            if (!isset($purchase[$field])) {
                Log::error('Invalid purchase session - missing field', [
                    'missing_field' => $field,
                    'user_id' => auth()->id()
                ]);
                session()->forget('purchase');
                return redirect()->route('services.index')->withErrors('Invalid purchase session. Please start again.');
            }
        }

        // Validate session age (30 minutes max)
        if ((time() - $purchase['timestamp']) > 1800) {
            Log::info('Purchase session expired', [
                'user_id' => auth()->id(),
                'session_age' => time() - $purchase['timestamp']
            ]);
            session()->forget('purchase');
            return redirect()->route('services.index')->withErrors('Purchase session expired. Please start the process again.');
        }

        // Check if user has sufficient balance
        $user = auth()->user();
        if ($user && isset($user->balance) && $user->balance < $purchase['cost']) {
            return view('users.numberProcess', compact('purchase'))
                ->withErrors('Insufficient balance. Please top up your account.');
        }

        return view('users.numberProcess', compact('purchase'));
        
    } catch (\Exception $e) {
    Log::error('Error in showConfirm', [
        'error' => $e->getMessage(),
        'user_id' => auth()->id()
    ]);

    session()->forget('purchase');

    return redirect()
        ->route('confirm.payment')
        ->withErrors(['pin' => 'Invalid transactional Pin. Please try again.']);
}

}

// Enhanced process method with better error handling and logging
public function process(Request $request)
{
    $request->validate([
        'pin' => 'required|digits:4',
        'session_token' => 'sometimes|string'
    ]);

    try {
        $encryptedPurchase = session('purchase');
        
        if (!$encryptedPurchase) {
            Log::warning('No purchase session in process', ['user_id' => auth()->id()]);
            return back()->withErrors('No purchase session found. Please start again.');
        }

        $purchase = decrypt($encryptedPurchase);

        // Security validations
        if ((time() - $purchase['timestamp']) > 1800) {
            session()->forget('purchase');
            Log::info('Purchase session expired during process', ['user_id' => auth()->id()]);
            return back()->withErrors('Purchase session expired. Please start again.');
        }

        // Validate session token if provided
        if ($request->has('session_token') && 
            isset($purchase['session_token']) && 
            $request->session_token !== $purchase['session_token']) {
            Log::warning('Session token mismatch', ['user_id' => auth()->id()]);
            return back()->withErrors('Invalid session. Please try again.');
        }

        // 🔹 Global PIN Validation
        if (!PinHelper::verifyPin($request->pin)) {
            Log::warning('Invalid PIN attempt', ['user_id' => auth()->id()]);
            return back()->withErrors('Invalid PIN. Please try again.')->withInput();
        }

        $token = env('SMSBUS_TOKEN');
        if (!$token) {
            Log::error('SMSBUS_TOKEN not configured');
            return back()->withErrors('Service configuration error');
        }

        // Check balance before proceeding
        if (!$this->checkUserBalance($purchase['cost'])) {
            Log::warning('Insufficient balance', [
                'user_id' => auth()->id(),
                'required' => $purchase['cost']
            ]);
            return back()->withErrors('Insufficient balance. Please top up your account.');
        }

        // Deduct user balance first
        if (!$this->deductUserBalance($purchase['cost'])) {
            return back()->withErrors('Failed to process payment. Please try again.');
        }

        // Make the API request to purchase number
        $response = Http::timeout(30)->get("https://sms-bus.com/api/control/get/number", [
            'token' => $token,
            'country_id' => $purchase['country_id'],
            'project_id' => $purchase['service'],
        ]);

        if ($response->failed()) {
            // Refund user balance on failure
            $this->refundUserBalance($purchase['cost']);
            
            Log::error('SMS-Bus number purchase API failed', [
                'user_id' => auth()->id(),
                'response_status' => $response->status(),
                'response_body' => $response->body(),
                'country_id' => $purchase['country_id'],
                'service_id' => $purchase['service']
            ]);
            
            return back()->withErrors('Failed to purchase number. Your balance has been refunded. Please try again.');
        }

        $data = $response->json();

        // Validate API response
        if (!isset($data['status']) || $data['status'] !== true) {
            // Refund user balance on API error
            $this->refundUserBalance($purchase['cost']);
            
            Log::error('SMS-Bus API returned error', [
                'user_id' => auth()->id(),
                'api_response' => $data
            ]);
            
            $errorMessage = isset($data['message']) ? $data['message'] : 'Failed to purchase number';
            return back()->withErrors($errorMessage . '. Your balance has been refunded.');
        }

        // Log successful purchase
        Log::info('Successful number purchase', [
            'user_id' => auth()->id(),
            'service_id' => $purchase['service'],
            'country_id' => $purchase['country_id'],
            'cost_ngn' => $purchase['cost'],
            'cost_usd' => $purchase['original_cost_usd'],
            'api_response' => $data
        ]);

        // Clear session after success
        session()->forget('purchase');

        return view('users.purchase-success', [
            'data' => $data,
            'purchase' => $purchase
        ]);

    } catch (\Exception $e) {
        Log::error('Purchase processing error', [
            'error' => $e->getMessage(),
            'user_id' => auth()->id(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Try to refund if we can decrypt the purchase data
        try {
            if (isset($encryptedPurchase)) {
                $purchase = decrypt($encryptedPurchase);
                if (isset($purchase['cost'])) {
                    $this->refundUserBalance($purchase['cost']);
                }
            }
        } catch (\Exception $refundError) {
            Log::error('Failed to refund after error', ['error' => $refundError->getMessage()]);
        }
        
        session()->forget('purchase');
        return back()->withErrors('An error occurred processing your purchase. Please contact support if your balance was deducted.');
    }
}

// Add this helper method to check balance before deduction
private function checkUserBalance($amount)
{
    $user = auth()->user();
    return $user && $user->balance >= $amount;
}

// Enhanced deductUserBalance with transaction logging
private function deductUserBalance($amount)
{
    try {
        $user = auth()->user();
        
        if (!$user || $user->balance < $amount) {
            return false;
        }

        // Use database transaction for consistency
        DB::transaction(function () use ($user, $amount) {
            $user->decrement('balance', $amount);
            
            // Create transaction record (adjust table name as needed)
            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $amount,
                'description' => 'SMS Number Purchase',
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
        
        return true;
    } catch (\Exception $e) {
        Log::error('Failed to deduct user balance', [
            'user_id' => auth()->id(),
            'amount' => $amount,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

// Enhanced refundUserBalance with transaction logging
private function refundUserBalance($amount)
{
    try {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        DB::transaction(function () use ($user, $amount) {
            $user->increment('balance', $amount);
            
            // Create refund transaction record
            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $amount,
                'description' => 'SMS Purchase Refund',
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
        
        return true;
    } catch (\Exception $e) {
        Log::error('Failed to refund user balance', [
            'user_id' => auth()->id(),
            'amount' => $amount,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}
}
