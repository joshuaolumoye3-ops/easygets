<?php
 
namespace App\Http\Controllers;
 
use App\Helpers\PinHelper;
use App\Helpers\PricingHelper;
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
    const PROVIDER_5SIM = '5sim';
    const PROVIDER_SMSBUS = 'smsbus';

public function showServices(Request $request)
{
    $token = env('SMSBUS_TOKEN');
    $cacheKey = 'smsbus_services';
    $backupCacheKey = 'smsbus_services_backup';
    
    $products = Cache::get($cacheKey);
    
    if (!$products || $products->isEmpty()) {
        try {
            $response = Http::timeout(30)
                ->connectTimeout(15)
                ->retry(3, function ($exception, $request) {
                    Log::info('Retrying SMS-Bus API call', ['attempt' => $request->attempt ?? 1]);
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
                    Log::info('SMS-Bus API call successful', ['count' => $products->count()]);
                } else {
                    throw new \Exception('Invalid API response data');
                }
            } else {
                throw new \Exception('API returned status: ' . $response->status());
            }
            
        } catch (\Exception $e) {
            Log::error('SMS-Bus API Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $products = Cache::get($backupCacheKey);
            
            if (!$products || $products->isEmpty()) {
                $products = $this->getMockServices();
                Log::warning('Using mock data due to API failure');
            } else {
                Log::info('Using backup cache data', ['count' => $products->count()]);
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

    $popularCodes = ['wa', 'fb', 'tg', 'google', 'ig'];
    $popular = $products->filter(fn($p) => in_array(strtolower($p['code']), $popularCodes));

    $search = $request->input('search');
    $isSearching = !empty($search);
    
    if ($isSearching) {
        $products = $products->filter(function($p) use ($search) {
            return str_contains(strtolower($p['title']), strtolower($search)) ||
                   str_contains(strtolower($p['code']), strtolower($search));
        });
        $others = $products; // All filtered results
    } else {
        $others = $products->reject(fn($p) => in_array(strtolower($p['code']), $popularCodes));
    }

    $perPage = 30;
    $page = $request->input('page', 1);
    $offset = ($page - 1) * $perPage;
    
    $paginated = $others->slice($offset, $perPage)->values();
    $hasMorePages = $others->count() > ($page * $perPage);

    // Handle AJAX requests (both infinite scroll and search)
    if ($request->ajax() || $request->input('ajax') == '1') {
        $html = $this->buildServiceItemsHtml($paginated);
        
        Log::info('AJAX request processed', [
            'page' => $page,
            'is_searching' => $isSearching,
            'search_term' => $search,
            'items_returned' => $paginated->count(),
            'has_more' => $hasMorePages,
            'html_length' => strlen($html)
        ]);
        
        return response()->json([
            'html' => $html,
            'next_page' => $hasMorePages ? $page + 1 : null,
            'has_more' => $hasMorePages,
            'current_page' => $page,
            'total' => $others->count(),
            'is_searching' => $isSearching
        ]);
    }

    return view('users.services', [
        'popular'   => $isSearching ? collect([]) : $popular,
        'products'  => $paginated,
        'nextPage'  => $hasMorePages ? 2 : null,
        'isSearching' => $isSearching,
        // Whether Provider 1 (5sim) is available (requires FIVESIM_TOKEN)
        'hasFivesim' => !empty(env('FIVESIM_TOKEN'))
    ]);
}

// Helper method to build service items HTML
private function buildServiceItemsHtml($products)
{
    $html = '';
    
    foreach ($products as $product) {
        $iconDisplay = '';
        if (str_contains($product['icon_url'], '.svg')) {
            $iconDisplay = '<div class="w-6 h-6 service-icon" style="background-image: url(\'' . $product['icon_url'] . '\'); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>';
        } else {
            $iconDisplay = '<img src="' . $product['icon_url'] . '" alt="' . htmlspecialchars($product['title']) . '" class="w-6 h-6 object-contain" loading="lazy" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">
                <div class="w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold" style="display: none;">' . strtoupper(substr($product['title'], 0, 2)) . '</div>';
        }
        
        $html .= '<button onclick="showProviderModal(\'' . $product['id'] . '\', \'' . addslashes($product['title']) . '\', \'' . strtolower($product['code']) . '\')" class="w-full block group">
            <div class="flex items-center space-x-4 p-4 border border-gray-100 rounded-xl hover:border-green-200 hover:bg-green-50/50 transition-all duration-200 group-hover:shadow-sm">
                <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center overflow-hidden flex-shrink-0 group-hover:bg-white transition-colors duration-200">
                    ' . $iconDisplay . '
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-gray-800 group-hover:text-green-700 transition-colors">
                        ' . htmlspecialchars($product['title']) . '
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        ' . strtoupper($product['code']) . '
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-green-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </div>
        </button>';
    }
    
    return $html;
}

private function getServiceIconMap()
{
    return [
        'facebook' => 'https://cdn-icons-png.flaticon.com/512/733/733547.png',
        'instagram' => 'https://cdn-icons-png.flaticon.com/512/2111/2111463.png',
        'twitter' => 'https://cdn-icons-png.flaticon.com/512/733/733579.png',
        'tiktok' => 'https://cdn-icons-png.flaticon.com/512/3046/3046121.png',
        'whatsapp' => 'https://cdn-icons-png.flaticon.com/512/733/733585.png',
        'telegram' => 'https://cdn-icons-png.flaticon.com/512/2111/2111646.png',
        'gmail' => 'https://cdn-icons-png.flaticon.com/512/732/732200.png',
        'youtube' => 'https://cdn-icons-png.flaticon.com/512/1384/1384060.png',
        'snapchat' => 'https://cdn-icons-png.flaticon.com/512/2111/2111800.png',
        'amazon' => 'https://cdn-icons-png.flaticon.com/512/5968/5968253.png',
        'microsoft' => 'https://cdn-icons-png.flaticon.com/512/732/732221.png',
        'yahoo' => 'https://cdn-icons-png.flaticon.com/512/732/732205.png',
        'netflix' => 'https://cdn-icons-png.flaticon.com/512/5977/5977590.png',
        'wechat' => 'https://cdn-icons-png.flaticon.com/512/888/888879.png',
        'paypal' => 'https://cdn-icons-png.flaticon.com/512/174/174861.png',
        'linkedin' => 'https://cdn-icons-png.flaticon.com/512/174/174857.png',
        'default' => 'https://cdn-icons-png.flaticon.com/512/1828/1828884.png'
    ];
}


    private function fetchCountriesFromAPI()
{
    try {
        $token = env('SMSBUS_TOKEN');
        
        if (!$token) {
            Log::error('Provider2_TOKEN not configured');
            throw new \Exception('Provider2_TOKEN not configured in .env file');
        }

        Log::info('Fetching countries from Provider2 API...');

        $response = Http::timeout(30)->get("https://sms-bus.com/api/control/list/countries", [
            'token' => $token,
        ]);

        Log::info('API Response Status: ' . $response->status());

        if ($response->failed()) {
            Log::error('Failed to fetch countries from Provider2', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('API request failed with status: ' . $response->status());
        }

        $responseData = $response->json();
        
        Log::info('API Response Data Structure', [
            'has_data_key' => isset($responseData['data']),
            'data_is_array' => isset($responseData['data']) && is_array($responseData['data']),
            'data_count' => isset($responseData['data']) ? count($responseData['data']) : 0,
            'response_keys' => array_keys($responseData)
        ]);

        if (!isset($responseData['data']) || empty($responseData['data'])) {
            Log::error('No data returned from Provider2 countries', [
                'response' => $responseData
            ]);
            throw new \Exception('No country data in API response');
        }

        $countries = collect($responseData['data'])
            ->map(function ($country) {
                $countryCode = strtoupper($country['country_code'] ?? $country['iso2'] ?? '');
                
                return [
                    'country_name' => $country['country_name'] ?? $country['name'] ?? 'Unknown',
                    'country_code' => $countryCode,
                    'flag_url'     => $country['flag_url'] ?? "https://flagcdn.com/w320/" . strtolower($countryCode) . ".png",
                    'base_price'   => 0,
                    'is_active'    => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            })
            ->filter(function($country) {
                return !empty($country['country_code']) && 
                       $country['country_name'] !== 'Unknown' &&
                       strlen($country['country_code']) == 2;
            })
            ->unique('country_code')
            ->values()
            ->toArray();

        if (empty($countries)) {
            Log::warning('No valid countries found after filtering API response');
            throw new \Exception('No valid countries after filtering');
        }

        Log::info('Filtered countries count: ' . count($countries));

        // Insert in chunks to avoid memory issues
        $inserted = 0;
        foreach (array_chunk($countries, 50) as $chunk) {
            foreach ($chunk as $country) {
                EsimCountry::updateOrCreate(
                    ['country_code' => $country['country_code']],
                    $country
                );
                $inserted++;
            }
        }

        Log::info('Successfully fetched and stored eSIM countries', [
            'total_countries' => count($countries),
            'inserted' => $inserted
        ]);

        return [
            'success' => true,
            'count' => $inserted
        ];
        
    } catch (\Exception $e) {
        Log::error('Error fetching eSIM countries from API', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * ✅ Get Exchange Rate or Conversion Price
 */
private function getExchangeRate(): float
    {
        try {
            // Find the OTP provider
            $otpProvider = \App\Models\Provider::where('api_identifier', 'Admin-otp')
                ->orWhere('api_identifier', 'otp')
                ->first();

            if ($otpProvider) {
                // Find the OTP product linked to this provider
                $otpProduct = \App\Models\Product::where('provider_id', $otpProvider->id)
                    ->where('product_code', 'otp-verification')
                    ->where('is_active', true)
                    ->first();

                if ($otpProduct && $otpProduct->client_price > 0) {
                    Log::info('Exchange rate from OTP product', [
                        'rate' => $otpProduct->client_price
                    ]);
                    return (float) $otpProduct->client_price;
                }
            }

            Log::warning('OTP product not found, using default exchange rate');
        } catch (\Exception $e) {
            Log::error('Error fetching exchange rate', [
                'error' => $e->getMessage()
            ]);
        }

        // ✅ Default fallback rate (₦1500 per $1)
        return 1500.0;
    }


    // ==================== 5SIM.NET METHODS ====================
    
    // Step 1: Get countries for a service (5sim)
    public function showCountries5sim($serviceCode)
{
    try {
        // Normalize service code for 5sim API
        $normalizedCode = $this->normalize5simServiceCode($serviceCode);
        
        Log::info('Provider1 showCountries called', [
            'original_code' => $serviceCode,
            'normalized_code' => $normalizedCode
        ]);
        
        // Get exchange rate for 5sim
        $exchangeRate = $this->get5simExchangeRate();
        
        $url = "https://5sim.net/v1/guest/products/any/any/{$normalizedCode}";
        Log::info('Provider1 API URL', ['url' => $url]);
        
        $response = Http::timeout(30)
            ->withOptions(['verify' => false])
            ->get($url, [
                'single' => 0,
                'sort' => 'top'
            ]);

        Log::info('Provider1 API Response', [
            'status' => $response->status(),
            'successful' => $response->successful(),
        ]);

        if (!$response->successful()) {
            Log::error('Provider1 countries API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'service_code' => $normalizedCode
            ]);
            
            return view('users.countries-5sim', [
                'countries' => collect([]),
                'serviceCode' => $serviceCode,
                'exchangeRate' => $exchangeRate
            ])->withErrors('Unable to fetch countries from Provider1. Please try again.');
        }

        $data = $response->json();
        Log::info('Provider1 parsed data', [
            'count' => count($data),
            'sample_keys' => array_slice(array_keys($data), 0, 5)
        ]);
        
        $countries = [];
        
        foreach ($data as $countryName => $details) {
            $priceUSD = $details['Price'] ?? 0;
            // Apply client price multiplier for 5sim
            $priceNGN = PricingHelper::calculateFinalPrice($priceUSD, self::PROVIDER_5SIM, $exchangeRate);
            
            $countries[] = [
                'name' => $countryName,
                'code' => $this->getCountryCode($countryName),
                'qty' => $details['Qty'] ?? 0,
                'price_usd' => $priceUSD,
                'price_ngn' => $priceNGN,
                'category' => $details['Category'] ?? 'activation'
            ];
        }
        
        Log::info('Provider1 countries processed', ['total' => count($countries)]);
        
        // Sort by quantity (descending)
        usort($countries, function($a, $b) {
            return $b['qty'] - $a['qty'];
        });
        
        $countries = collect($countries);
        
        $search = request()->get('search', '');
        
        if ($search) {
            $countries = $countries->filter(function($country) use ($search) {
                return str_contains(strtolower($country['name']), strtolower($search));
            });
        }

        if (request()->ajax()) {
            return response()->json([
                'data' => $countries->values(),
                'success' => true,
                'exchange_rate' => $exchangeRate
            ]);
        }

        return view('users.countries-5sim', [
            'countries' => $countries,
            'serviceCode' => $serviceCode,
            'exchangeRate' => $exchangeRate
        ]);
        
    } catch (\Exception $e) {
        Log::error('Provider1 countries exception', [
            'service' => $serviceCode,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return view('users.countries-5sim', [
            'countries' => collect([]),
            'serviceCode' => $serviceCode,
            'exchangeRate' => $this->get5simExchangeRate()
        ])->withErrors('An error occurred: ' . $e->getMessage());
    }
}

private function normalize5simServiceCode($serviceCode)
{
    // Map common service codes to 5sim format
    $serviceMap = [
        'fb' => 'facebook',
        'wa' => 'whatsapp',
        'tg' => 'telegram',
        'google' => 'google',
        'ig' => 'instagram',
        'twitter' => 'twitter',
        'tiktok' => 'tiktok',
        'snapchat' => 'snapchat',
        'linkedin' => 'linkedin',
        'viber' => 'viber',
        'wechat' => 'wechat',
        'line' => 'line',
        'microsoft' => 'microsoft',
        'yahoo' => 'yahoo',
        'amazon' => 'amazon',
        'netflix' => 'netflix',
    ];

    $lowerCode = strtolower($serviceCode);
    
    // Return mapped code if exists, otherwise return original (lowercase)
    return $serviceMap[$lowerCode] ?? $lowerCode;
}

    // Step 2: Get operators for a country and service (5sim)
    public function showOperators5sim($serviceCode, $country)
{
    try {
        // Normalize service code for 5sim API
        $normalizedCode = $this->normalize5simServiceCode($serviceCode);
        
        Log::info('Provider1 showOperators called', [
            'original_code' => $serviceCode,
            'normalized_code' => $normalizedCode,
            'country' => $country
        ]);
        
        // Get exchange rate for 5sim
        $exchangeRate = $this->get5simExchangeRate();
        
        // Get 5sim provider
        $fivesimProvider = Provider::where('api_identifier', self::PROVIDER_5SIM)->first();
        
        $url = "https://5sim.net/v1/guest/prices";
        
        $response = Http::timeout(30)
            ->withOptions(['verify' => false])
            ->get($url, [
                'product' => $normalizedCode,
                'country' => $country
            ]);

        Log::info('Provider1 operators API response', [
            'status' => $response->status(),
            'url' => $url,
            'params' => ['product' => $normalizedCode, 'country' => $country],
        ]);

        if (!$response->successful()) {
            Log::error('Provider1 operators API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'service_code' => $normalizedCode
            ]);
            
            // Don't return, fall back to DB products
        }

        $data = $response->successful() ? $response->json() : null;
        $operators = [];
        
        if ($data && isset($data[$country][$normalizedCode])) {
            foreach ($data[$country][$normalizedCode] as $operatorName => $details) {
                $costUSD = $details['cost'] ?? 0;
                
                // Apply client price multiplier for 5sim
                $costNGN = PricingHelper::calculateFinalPrice($costUSD, self::PROVIDER_5SIM, $exchangeRate);
                
                $operators[] = [
                    'name' => $operatorName,
                    'display_name' => $this->formatOperatorName($operatorName),
                    'cost_usd' => $costUSD,
                    'cost_ngn' => $costNGN,
                    'count' => $details['count'] ?? 0,
                    'rate' => $details['rate'] ?? 0,
                    'rate24' => $details['rate24'] ?? null,
                    'rate72' => $details['rate72'] ?? null,
                ];
            }
            
            Log::info('Provider1 operators found', ['count' => count($operators)]);
        } else {
            Log::warning('Provider1 no operators in response structure or API failed', [
                'country' => $country,
                'service' => $normalizedCode,
                'api_successful' => $response->successful(),
                'available_services' => $data && isset($data[$country]) ? array_keys($data[$country]) : []
            ]);
        }
        
        // Fallback to DB products if no operators from API
        if (empty($operators)) {
            foreach ($fivesimProvider->products ?? [] as $dbProduct) {
                $operators[] = [
                    'name' => $dbProduct->product_code,
                    'display_name' => $dbProduct->name,
                    'cost_usd' => $dbProduct->api_price ?? 0,
                    'cost_ngn' => $dbProduct->client_price,
                    'count' => 0,
                    'rate' => 0,
                    'rate24' => null,
                    'rate72' => null,
                ];
            }
        }
        
        // Sort by count (descending)
        usort($operators, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return view('users.operators-5sim', [
            'operators' => collect($operators),
            'serviceCode' => $serviceCode,
            'country' => $country,
            'exchangeRate' => $exchangeRate
        ]);
        
    } catch (\Exception $e) {
        Log::error('Provider1 operators exception', [
            'service' => $serviceCode,
            'country' => $country,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return view('users.operators-5sim', [
            'operators' => collect([]),
            'serviceCode' => $serviceCode,
            'country' => $country,
            'exchangeRate' => $this->get5simExchangeRate()
        ])->withErrors('An error occurred: ' . $e->getMessage());
    }
}


    private function formatOperatorName($operatorName)
    {
        // Replace underscores, hyphens, and dots with spaces
        $formatted = str_replace(['_', '-', '.'], ' ', $operatorName);

        // Convert to lowercase and capitalize words
        $formatted = ucwords(strtolower($formatted));

        // Common replacements for better display
        $formatted = str_ireplace([
            'Mts', 'Beeline', 'Tele2', 'Megafon', 'Air', 'Sms'
        ], [
            'MTS', 'Beeline', 'Tele2', 'MegaFon', 'Air', 'SMS'
        ], $formatted);

        return trim($formatted);
    }


    // Prepare purchase for 5sim
    public function preparePurchase5sim(Request $request)
{
    $request->validate([
        'service' => 'required|string',
        'country' => 'required|string',
        'operator' => 'required|string',
    ]);

    $serviceCode = $request->service;
    $normalizedCode = $this->normalize5simServiceCode($serviceCode);
    // Ensure country/operator are in expected format for 5sim (lowercase)
    $country = strtolower($request->country);
    $operator = strtolower($request->operator);

    Log::info('Provider1 preparePurchase', [
        'original_code' => $serviceCode,
        'normalized_code' => $normalizedCode,
        'country' => $country,
        'operator' => $operator
    ]);

    // Get service details (use original code for display)
    $serviceDetails = $this->getServiceDetails($serviceCode, true);
    
    // Get country details
    $countryDetails = $this->getCountryDetails($country, true);

    // Fetch current price using normalized code
    $cacheKey = "5sim_price_{$normalizedCode}_{$country}_{$operator}";
    $priceData = Cache::remember($cacheKey, 180, function () use ($normalizedCode, $country, $operator) {
        try {
            $response = Http::timeout(20)
                ->withOptions(['verify' => false])
                ->get("https://5sim.net/v1/guest/prices", [
                    'product' => $normalizedCode,
                    'country' => $country
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Provider1 price check response', [
                    'has_country' => isset($data[$country]),
                    'has_service' => isset($data[$country][$normalizedCode]),
                    'has_operator' => isset($data[$country][$normalizedCode][$operator])
                ]);
                
                if (isset($data[$country][$normalizedCode][$operator])) {
                    return $data[$country][$normalizedCode][$operator];
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Provider1 price fetch error', [
                'error' => $e->getMessage(),
                'service' => $normalizedCode
            ]);
            return null;
        }
    });

    if (!$priceData || !isset($priceData['cost'])) {
        Log::error('Provider1 no price data', [
            'service' => $normalizedCode,
            'country' => $country,
            'operator' => $operator
        ]);
        return back()->withErrors('Unable to fetch pricing. Please try again.');
    }

    // Get 5sim exchange rate (from Otp-others)
    $exchangeRate = $this->get5simExchangeRate();

    // Apply client price multiplier for 5sim
    $costNGN = PricingHelper::calculateFinalPrice($priceData['cost'], self::PROVIDER_5SIM, $exchangeRate);

    $purchase = [
        'provider' => self::PROVIDER_5SIM,
        'country' => $country,
        'service' => $normalizedCode, // Store normalized code for API calls
        'service_code_display' => $serviceCode, // Store original for display
        'operator' => $operator,
        'service_name' => $serviceDetails['name'],
        'country_name' => $countryDetails['name'],
        'country_code' => $countryDetails['code'],
        'operator_name' => $this->formatOperatorName($operator),
        'cost' => $costNGN,
        'original_cost_usd' => $priceData['cost'],
        'exchange_rate' => $exchangeRate,
        'total_count' => $priceData['count'] ?? 0,
        'rate' => $priceData['rate'] ?? 0,
        'timestamp' => time(),
        'session_token' => bin2hex(random_bytes(16)),
    ];

    if ($purchase['cost'] <= 0) {
        return back()->withErrors('Invalid pricing. Please contact support.');
    }

    session(['purchase' => encrypt($purchase)]);

    Log::info('Provider1 purchase session created', [
        'user_id' => auth()->id(),
        'service' => $normalizedCode,
        'service_name' => $serviceDetails['name'],
        'country' => $country,
        'country_name' => $countryDetails['name'],
        'operator' => $operator,
        'exchange_rate' => $exchangeRate,
        'usd_cost' => $priceData['cost'],
        'ngn_cost' => $purchase['cost']
    ]);

    return redirect()->route('confirm.payment');
}

    private function getCountryCode($countryName)
{
    // Normalize the name (remove spaces, hyphens, and lowercase)
    $normalized = strtolower(str_replace([' ', '-', '_'], '', $countryName));

    // Common country name → ISO2 code mappings
    $map = [
        'russia' => 'ru', 'ukraine' => 'ua', 'kazakhstan' => 'kz',
        'china' => 'cn', 'philippines' => 'ph', 'myanmar' => 'mm',
        'indonesia' => 'id', 'malaysia' => 'my', 'kenya' => 'ke',
        'tanzania' => 'tz', 'vietnam' => 'vn', 'kyrgyzstan' => 'kg',
        'usa' => 'us', 'israel' => 'il', 'hongkong' => 'hk',
        'poland' => 'pl', 'england' => 'gb', 'nigeria' => 'ng',
        'india' => 'in', 'egypt' => 'eg', 'france' => 'fr',
        'germany' => 'de', 'italy' => 'it', 'spain' => 'es',
        'ghana' => 'gh', 'canada' => 'ca', 'turkey' => 'tr',
        'southafrica' => 'za', 'brazil' => 'br', 'mexico' => 'mx',
        'japan' => 'jp', 'australia' => 'au', 'singapore' => 'sg',
        'saudiarabia' => 'sa', 'pakistan' => 'pk', 'bangladesh' => 'bd'
    ];

    // Return the matching code or 'un' (unknown) if not found
    return $map[$normalized] ?? 'un';
}

    private function getCountryCodeFrom5simCountry($countryName)
    {
        // Map 5sim country names to ISO codes
        $countryMap = [
            'russia' => 'ru',
            'ukraine' => 'ua',
            'kazakhstan' => 'kz',
            'china' => 'cn',
            'philippines' => 'ph',
            'myanmar' => 'mm',
            'indonesia' => 'id',
            'malaysia' => 'my',
            'kenya' => 'ke',
            'tanzania' => 'tz',
            'vietnam' => 'vn',
            'kyrgyzstan' => 'kg',
            'usa' => 'us',
            'israel' => 'il',
            'hongkong' => 'hk',
            'poland' => 'pl',
            'england' => 'gb',
            'madagascar' => 'mg',
            'congo' => 'cd',
            'nigeria' => 'ng',
            'macau' => 'mo',
            'egypt' => 'eg',
            'india' => 'in',
            'ireland' => 'ie',
            'cambodia' => 'kh',
            'laos' => 'la',
            'haiti' => 'ht',
            'ivorycoast' => 'ci',
            'gambia' => 'gm',
            'serbia' => 'rs',
            'yemen' => 'ye',
            'southafrica' => 'za',
            'romania' => 'ro',
            'colombia' => 'co',
            'estonia' => 'ee',
            'azerbaijan' => 'az',
            'canada' => 'ca',
            'morocco' => 'ma',
            'ghana' => 'gh',
            'argentina' => 'ar',
            'uzbekistan' => 'uz',
            'cameroon' => 'cm',
            'chad' => 'td',
            'germany' => 'de',
            'lithuania' => 'lt',
            'croatia' => 'hr',
            'sweden' => 'se',
            'iraq' => 'iq',
            'netherlands' => 'nl',
            'latvia' => 'lv',
            'austria' => 'at',
            'belarus' => 'by',
            'thailand' => 'th',
            'saudiarabia' => 'sa',
            'mexico' => 'mx',
            'taiwan' => 'tw',
            'spain' => 'es',
            'iran' => 'ir',
            'algeria' => 'dz',
            'slovenia' => 'si',
            'bangladesh' => 'bd',
            'senegal' => 'sn',
            'turkey' => 'tr',
            'czechrepublic' => 'cz',
            'srilanka' => 'lk',
            'peru' => 'pe',
            'pakistan' => 'pk',
            'newzealand' => 'nz',
            'guinea' => 'gn',
            'mali' => 'ml',
            'venezuela' => 've',
            'ethiopia' => 'et',
            'mongolia' => 'mn',
            'brazil' => 'br',
            'afghanistan' => 'af',
            'uganda' => 'ug',
            'angola' => 'ao',
            'cyprus' => 'cy',
            'france' => 'fr',
            'papua' => 'pg',
            'mozambique' => 'mz',
            'nepal' => 'np',
            'belgium' => 'be',
            'bulgaria' => 'bg',
            'hungary' => 'hu',
            'moldova' => 'md',
            'italy' => 'it',
            'paraguay' => 'py',
            'honduras' => 'hn',
            'tunisia' => 'tn',
            'nicaragua' => 'ni',
            'timorleste' => 'tl',
            'bolivia' => 'bo',
            'costarica' => 'cr',
            'guatemala' => 'gt',
            'uae' => 'ae',
            'zimbabwe' => 'zw',
            'puertorico' => 'pr',
            'sudan' => 'sd',
            'togo' => 'tg',
            'kuwait' => 'kw',
            'elsalvador' => 'sv',
            'libyan' => 'ly',
            'jamaica' => 'jm',
            'trinidad' => 'tt',
            'ecuador' => 'ec',
            'swaziland' => 'sz',
            'oman' => 'om',
            'bosniaand' => 'ba',
            'dominicanrepublic' => 'do',
            'syria' => 'sy',
            'antigua' => 'ag',
            'armenia' => 'am',
            'seychelles' => 'sc',
            'newcaledonia' => 'nc',
            'solomon' => 'sb',
            'panama' => 'pa',
            'mauritius' => 'mu',
            'singapore' => 'sg',
            'jordan' => 'jo',
            'luxembourg' => 'lu',
            'grenada' => 'gd',
            'barbados' => 'bb',
            'fiji' => 'fj',
            'burundi' => 'bi',
            'japan' => 'jp',
            'maldives' => 'mv',
            'guyana' => 'gy',
            'georgia' => 'ge',
            'bahamas' => 'bs',
            'albania' => 'al',
            'uruguay' => 'uy',
            'mauritania' => 'mr',
            'bhutan' => 'bt',
            'macao' => 'mo',
            'lesotho' => 'ls',
            'sierra' => 'sl',
            'benin' => 'bj',
            'liberia' => 'lr',
            'rwanda' => 'rw',
            'suriname' => 'sr',
            'zambia' => 'zm',
            'malawi' => 'mw',
            'djibouti' => 'dj',
            'belize' => 'bz',
            'fiji' => 'fj',
            'guineabissau' => 'gw',
            'portugal' => 'pt',
            'switzerland' => 'ch',
            'greece' => 'gr',
            'chile' => 'cl',
            'denmark' => 'dk',
            'finland' => 'fi',
            'norway' => 'no',
            'australia' => 'au',
            'guinea-bissau' => 'gw',
        ];

        $normalizedName = strtolower(str_replace([' ', '-'], '', $countryName));
        return $countryMap[$normalizedName] ?? 'un';
    }

    private function process5sim($purchase)
    {
        $token = env('FIVESIM_TOKEN');

        if (!$token) {
            $this->refundUserBalance($purchase['cost']);
            Log::error('FIVESIM_TOKEN not configured');
            return back()->withErrors('Service configuration error. Balance refunded.');
        }

        try {
            Log::info('5sim purchase attempt', [
                'user_id' => auth()->id(),
                'service' => $purchase['service'],
                'country' => $purchase['country'],
                'operator' => $purchase['operator']
            ]);

            // Make purchase request to 5sim
            // Build URL with proper encoding
            $url = sprintf(
                'https://5sim.net/v1/user/buy/activation/%s/%s/%s',
                urlencode($purchase['country']),
                urlencode($purchase['operator']),
                urlencode($purchase['service'])
            );

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json'
                ])
                ->get($url);

            $responseData = $response->json();

            Log::info('5sim API response', [
                'status' => $response->status(),
                'json' => $responseData,
                'body' => $response->body(),
                'url' => $url
            ]);

            if ($response->failed()) {
                $this->refundUserBalance($purchase['cost']);

                Log::error('5sim purchase failed', [
                    'status' => $response->status(),
                    'json' => $responseData,
                    'body' => $response->body(),
                    'url' => $url
                ]);

                $errorMsg = 'Failed to purchase number. ';
                if (is_array($responseData) && isset($responseData['message'])) {
                    $errorMsg .= $responseData['message'];
                } elseif (is_string($response->body())) {
                    $errorMsg .= substr($response->body(), 0, 250);
                }

                return back()->withErrors($errorMsg . ' Your balance has been refunded.');
            }

            $data = $responseData ?? [];

            // Validate response data
            if (!isset($data['id']) || !isset($data['phone'])) {
                $this->refundUserBalance($purchase['cost']);
                Log::error('5sim invalid response structure', ['data' => $data]);
                return back()->withErrors('Invalid response from service. Your balance has been refunded.');
            }

            // Create transaction record
            $transactionId = DB::table('transactions')->insertGetId([
                'reference' => 'ACT_' . strtoupper(Str::random(10)),
                'request_id' => (string)$data['id'],
                'user_id' => auth()->id(),
                'type' => 'activation',
                'channel' => 'provider1',
                'provider' => 'Provider 1',
                'service_id' => $purchase['service'],
                'service_name' => $purchase['service_name'],
                'service_logo' => null, // 5sim doesn't provide logos
                'country_id' => null, // 5sim uses country names, not IDs
                'country_name' => $purchase['country_name'],
                'country_code' => $this->getCountryCodeFrom5simCountry($purchase['country']),
                'operator_name' => $purchase['operator_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'sms_code' => null,
                'amount' => $purchase['cost'],
                'original_cost_usd' => $purchase['original_cost_usd'] ?? null,
                'status' => 'pending',
                'expires_at' => now()->addMinutes(20),
                'response' => json_encode($data),
                'description' => "Provider 1 activation for {$purchase['service_name']} - {$purchase['country_name']}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Successful Provider1 purchase', [
                'user_id' => auth()->id(),
                'transaction_id' => $transactionId,
                'order_id' => $data['id'],
                'phone' => $data['phone']
            ]);

            session()->forget('purchase');

            // Redirect to history page with activation ID
            return redirect()->route('history')->with('new_activation', $transactionId);

        } catch (\Exception $e) {
            $this->refundUserBalance($purchase['cost']);
            Log::error('Provider1 purchase exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors('An error occurred. Your balance has been refunded.');
        }
    }

    private function get5simExchangeRate(): float
{
    try {
        // Find the OTP provider
        $otpProvider = \App\Models\Provider::where('api_identifier', 'Admin-otp')
            ->orWhere('api_identifier', 'otp')
            ->first();

        if ($otpProvider) {
            // Find the Otp-others product (specifically for 5sim conversion)
            $otpOthersProduct = \App\Models\Product::where('provider_id', $otpProvider->id)
                ->where('product_code', 'Otp-others')
                ->where('is_active', true)
                ->first();

            if ($otpOthersProduct && $otpOthersProduct->client_price > 0) {
                Log::info('5sim exchange rate from Otp-others product', [
                    'rate' => $otpOthersProduct->client_price
                ]);
                return (float) $otpOthersProduct->client_price;
            }
        }

        Log::warning('Otp-others product not found, using default 5sim exchange rate');
    } catch (\Exception $e) {
        Log::error('Error fetching 5sim exchange rate', [
            'error' => $e->getMessage()
        ]);
    }

    // Default fallback rate (₦1600 per $1 for 5sim)
    return 1600.0;
}

    private function fetchSms5sim($activation)
    {
        $token = env('FIVESIM_TOKEN');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Service token not configured'
            ], 500);
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json'
                ])
                ->get("https://5sim.net/v1/user/check/{$activation->request_id}");

            if ($response->failed()) {
                Log::error('Provider1 check failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return response()->json([
                    'success' => false,
                    'status' => 'pending',
                    'message' => 'Service temporarily unavailable'
                ]);
            }

            $data = $response->json();

            Log::info('Provider1 SMS check', [
                'request_id' => $activation->request_id,
                'status' => $data['status'] ?? 'unknown',
                'has_sms' => isset($data['sms']) && !empty($data['sms'])
            ]);

            // Check if SMS received
            if (isset($data['sms']) && !empty($data['sms'])) {
                // SMS can be array or single object
                $smsData = is_array($data['sms']) ? $data['sms'][0] : $data['sms'];
                $smsText = is_array($smsData) ? ($smsData['text'] ?? $smsData['code'] ?? '') : $smsData;
                
                // Extract code from SMS text
                $code = $this->extractCode($smsText);

                if ($code) {
                    DB::table('transactions')
                        ->where('id', $activation->id)
                        ->update([
                            'sms_code' => $code,
                            'status' => 'completed',
                            'updated_at' => now()
                        ]);

                    // Finish the activation on 5sim
                    try {
                        Http::timeout(10)
                            ->withHeaders([
                                'Authorization' => "Bearer {$token}",
                                'Accept' => 'application/json'
                            ])
                            ->get("https://5sim.net/v1/user/finish/{$activation->request_id}");
                    } catch (\Exception $e) {
                        Log::warning('Failed to finish Provider1 activation', ['error' => $e->getMessage()]);
                    }

                    return response()->json([
                        'success' => true,
                        'sms_code' => $code,
                        'status' => 'completed'
                    ]);
                }
            }

            // Check activation status
            $status = $data['status'] ?? 'PENDING';
            
            if ($status === 'FINISHED') {
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update([
                        'status' => 'completed',
                        'updated_at' => now()
                    ]);

                return response()->json([
                    'success' => false,
                    'status' => 'completed',
                    'message' => 'Activation finished'
                ]);
            } elseif ($status === 'CANCELED' || $status === 'TIMEOUT') {
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update([
                        'status' => 'expired',
                        'updated_at' => now()
                    ]);

                return response()->json([
                    'success' => false,
                    'status' => 'expired',
                    'message' => 'Activation expired'
                ]);
            }

            // Still waiting
            return response()->json([
                'success' => false,
                'status' => 'pending',
                'message' => 'Waiting for SMS'
            ]);

        } catch (\Exception $e) {
            Log::error('Provider1 SMS fetch error', [
                'error' => $e->getMessage(),
                'request_id' => $activation->request_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching SMS',
                'status' => 'pending'
            ], 500);
        }
    }

    private function cancel5sim($activation)
    {
        $token = env('FIVESIM_TOKEN');

        if (!$token) {
            return [
                'success' => false,
                'message' => 'Service token not configured'
            ];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json'
                ])
                ->get("https://5sim.net/v1/user/cancel/{$activation->request_id}");

            if ($response->successful()) {
                Log::info('Provider1 activation cancelled', [
                    'request_id' => $activation->request_id
                ]);
                
                return ['success' => true];
            }

            $data = $response->json();
            Log::error('Provider1 cancel failed', [
                'status' => $response->status(),
                'response' => $data
            ]);

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to cancel activation'
            ];

        } catch (\Exception $e) {
            Log::error('Provider1 cancel error', [
                'error' => $e->getMessage(),
                'request_id' => $activation->request_id
            ]);

            return [
                'success' => false,
                'message' => 'Error cancelling request'
            ];
        }
    }

    private function cancelSmsBus($activation)
    {
        $token = env('SMSBUS_TOKEN');

        if (!$token) {
            Log::error('Provider2 token not configured for cancellation');
            return [
                'success' => false,
                'message' => 'Service token not configured'
            ];
        }

        try {
            Log::info('Provider2 cancellation request', [
                'request_id' => $activation->request_id,
                'activation_id' => $activation->id
            ]);

            $response = Http::timeout(15)
                ->withOptions(['verify' => false])
                ->get('https://sms-bus.com/api/control/cancel', [
                    'token' => $token,
                    'request_id' => $activation->request_id
                ]);

            Log::info('Provider2 cancel response received', [
                'status' => $response->status(),
                'successful' => $response->successful()
            ]);

            // Check if HTTP request failed
            if ($response->failed()) {
                Log::error('Provider2 cancel HTTP failed', [
                    'http_status' => $response->status(),
                    'body' => $response->body(),
                    'request_id' => $activation->request_id
                ]);

                return [
                    'success' => false,
                    'message' => 'Service temporarily unavailable'
                ];
            }

            $data = $response->json();

            // Success: cancellation completed
            if (isset($data['code']) && $data['code'] === 200) {
                Log::info('Provider2 cancellation successful', [
                    'request_id' => $activation->request_id,
                    'activation_id' => $activation->id
                ]);

                return ['success' => true];
            }

            // Error: already closed
            if (isset($data['code']) && $data['code'] === 50103) {
                Log::info('Provider2 request already closed', [
                    'request_id' => $activation->request_id,
                    'message' => $data['message'] ?? 'Already closed'
                ]);

                return [
                    'success' => false,
                    'message' => 'Request already closed - cannot cancel'
                ];
            }

            // Other API errors
            Log::warning('Provider2 cancel unexpected response', [
                'code' => $data['code'] ?? null,
                'message' => $data['message'] ?? null,
                'request_id' => $activation->request_id,
                'full_response' => $data
            ]);

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to cancel activation'
            ];

        } catch (\Exception $e) {
            Log::error('Provider2 cancel exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $activation->request_id
            ]);

            return [
                'success' => false,
                'message' => 'Error cancelling request'
            ];
        }
    }

    // ==================== SMS-BUS METHODS (UNCHANGED) ====================

    public function showCountries($serviceId)
{
    $token = env('SMSBUS_TOKEN');
    
    if (!$token) {
        Log::error('Provider2_TOKEN not configured');
        $emptyPaginator = new LengthAwarePaginator([], 0, 20, 1);
        return view('users.countries', [
            'countries' => $emptyPaginator,
            'serviceId' => $serviceId
        ])->withErrors('Service configuration error. Please contact support.');
    }

    $cacheKey = "smsbus_countries_prices_{$serviceId}";
    
    try {
        // Get exchange rate
        $exchangeRate = $this->getExchangeRate();
        
        // Get SMS-Bus provider and product
        $smsbusProvider = Provider::where('api_identifier', self::PROVIDER_SMSBUS)->first();
        $product = $smsbusProvider ? Product::where('provider_id', $smsbusProvider->id)->where('product_code', $serviceId)->first() : null;
        
        // Fetch countries with prices from the API
        $countries = Cache::remember($cacheKey, 300, function () use ($token, $serviceId) {
            Log::info('Fetching Provider2 countries with prices', ['serviceId' => $serviceId]);
            
            $response = Http::timeout(30)
                ->get("https://sms-bus.com/api/resource/list/prices", [
                    'projectId' => $serviceId,
                    'token' => $token
                ]);

            Log::info('Provider2 countries API response', [
                'status' => $response->status(),
                'successful' => $response->successful()
            ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch Provider2 countries', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return collect([]);
            }

            $responseData = $response->json();
            
            Log::info('Provider2 API response structure', [
                'has_data' => isset($responseData['data']),
                'data_type' => isset($responseData['data']) ? gettype($responseData['data']) : 'null',
                'data_count' => isset($responseData['data']) ? count($responseData['data']) : 0,
                'first_item_sample' => isset($responseData['data'][0]) ? $responseData['data'][0] : null,
                'response_keys' => array_keys($responseData)
            ]);
            
            if (!isset($responseData['data']) || empty($responseData['data'])) {
                Log::warning('No countries data in Provider2 response', [
                    'full_response' => $responseData
                ]);
                return collect([]);
            }

            // Transform the data
            $countries = collect($responseData['data'])->map(function ($item) {
                $countryId = $item['country_id'] ?? ($item['id'] ?? null);
                $title = $item['title'] ?? ($item['country_name'] ?? ($item['name'] ?? 'Unknown'));
                $code = $item['code'] ?? ($item['country_code'] ?? 'xx');
                
                return [
                    'country_id' => $countryId,
                    'title' => $title,
                    'code' => isset($code) ? strtolower($code) : 'xx',
                    'project_id' => $item['project_id'] ?? null,
                    'project_code' => $item['project_code'] ?? '',
                    'cost_usd' => $item['cost'] ?? ($item['price'] ?? 0), // Store original USD price
                    'cost' => $item['cost'] ?? ($item['price'] ?? 0), // Will be converted to NGN later
                    'ws_cost_usd' => $item['ws_cost'] ?? ($item['cost'] ?? ($item['price'] ?? 0)),
                    'ws_cost' => $item['ws_cost'] ?? ($item['cost'] ?? ($item['price'] ?? 0)),
                    'total_count' => $item['total_count'] ?? ($item['count'] ?? ($item['qty'] ?? 0)),
                    'sort' => $item['sort'] ?? ($item['order'] ?? 999),
                    'flag_url' => isset($code) && $code !== 'xx'
                        ? "https://flagcdn.com/w80/" . strtolower($code) . ".png"
                        : "https://ui-avatars.com/api/?name=" . urlencode(substr($title, 0, 2)) . "&background=cccccc&color=ffffff&size=80"
                ];
            })->filter(function ($item) {
                return !empty($item['country_id']) && !empty($item['title']) && $item['title'] !== 'Unknown';
            });

            // Sort by sort order, then by total count
            $countries = $countries->sortBy([
                ['sort', 'asc'],
                ['total_count', 'desc']
            ])->values();

            Log::info('Provider2 countries processed', [
                'total_count' => $countries->count(),
                'first_country' => $countries->first(),
                'has_valid_data' => $countries->isNotEmpty()
            ]);

            return $countries;
        });

        // Apply client price multiplier for smsbus
        $countries = $countries->map(function ($country) use ($exchangeRate) {
            $country['cost'] = PricingHelper::calculateFinalPrice($country['cost_usd'], self::PROVIDER_SMSBUS, $exchangeRate);
            $country['ws_cost'] = PricingHelper::calculateFinalPrice($country['ws_cost_usd'], self::PROVIDER_SMSBUS, $exchangeRate);
            $country['exchange_rate'] = $exchangeRate;
            return $country;
        });

        // Handle search
        $search = request()->get('search', '');
        if ($search) {
            $countries = $countries->filter(function($country) use ($search) {
                return str_contains(strtolower($country['title']), strtolower($search)) ||
                    str_contains(strtolower($country['code']), strtolower($search));
            })->values();
        }

        // Check if countries is empty after filtering
        if ($countries->isEmpty()) {
            Log::warning('No countries available after filtering', [
                'service_id' => $serviceId,
                'search' => $search,
                'cache_had_data' => Cache::has($cacheKey)
            ]);
        }

        $perPage = 20;
        $page = request()->get('page', 1);
        $total = $countries->count();

        // Create paginator for both AJAX and regular requests
        $paginatedCountries = new LengthAwarePaginator(
            $countries->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Handle AJAX requests for infinite scroll
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $paginatedCountries->items(),
                'current_page' => $paginatedCountries->currentPage(),
                'last_page' => $paginatedCountries->lastPage(),
                'total' => $paginatedCountries->total(),
                'per_page' => $paginatedCountries->perPage(),
                'has_more_pages' => $paginatedCountries->hasMorePages(),
                'exchange_rate' => $exchangeRate
            ]);
        }

        // For initial page load
        return view('users.countries', [
            'countries' => $paginatedCountries,
            'serviceId' => $serviceId,
            'exchangeRate' => $exchangeRate
        ]);

    } catch (\Exception $e) {
        Log::error('Provider2 countries exception', [
            'serviceId' => $serviceId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        $emptyPaginator = new LengthAwarePaginator([], 0, 20, 1);

        if (request()->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred loading countries: ' . $e->getMessage(),
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0
            ], 500);
        }

        return view('users.countries', [
            'countries' => $emptyPaginator,
            'serviceId' => $serviceId,
            'exchangeRate' => 1500.0
        ])->withErrors('An error occurred loading countries: ' . $e->getMessage());
    }
}

    public function getPrices(Request $request, $serviceId, $countryId)
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

        $exchangeRate = $this->getExchangeRate();

        $prices = collect($data['data'])
            ->filter(function ($item) use ($serviceId) {
                return (int) $item['project_id'] === (int) $serviceId;
            })
            ->map(function ($item) use ($exchangeRate) {
                $costUSD = $item['cost'] ?? 0;
                // Apply client price multiplier for smsbus
                $costNGN = PricingHelper::calculateFinalPrice($costUSD, self::PROVIDER_SMSBUS, $exchangeRate);
                
                return [
                    'project_id' => $item['project_id'],
                    'name'       => ucfirst($item['project_code']),
                    'cost'       => $costNGN,
                    'cost_usd'   => $costUSD,
                    'count'      => $item['total_count'],
                    'country_id' => $item['country_id'],
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

    /**
 * ✅ Get service details by ID
 */
private function getServiceDetails($serviceIdentifier, $isCode = false): array
    {
        try {
            if ($isCode) {
                // For 5sim - search by code
                $cacheKey = "smsbus_service_by_code_{$serviceIdentifier}";
                $serviceData = Cache::remember($cacheKey, 300, function () use ($serviceIdentifier) {
                    $token = env('SMSBUS_TOKEN');
                    $response = Http::timeout(20)->get("https://sms-bus.com/api/control/list/projects", [
                        'token' => $token
                    ]);

                    if ($response->successful()) {
                        $data = $response->json('data');
                        if (is_array($data)) {
                            foreach ($data as $service) {
                                if (strtolower($service['code']) === strtolower($serviceIdentifier)) {
                                    return [
                                        'name' => $service['title'],
                                        'code' => $service['code'],
                                        'id' => $service['id']
                                    ];
                                }
                            }
                        }
                    }
                    return null;
                });

                if ($serviceData) {
                    return [
                        'name' => $serviceData['name'],
                        'logo' => null,
                        'code' => $serviceData['code']
                    ];
                }

                // Fallback to formatted code
                return [
                    'name' => ucfirst($serviceIdentifier),
                    'logo' => null,
                    'code' => $serviceIdentifier
                ];
            } else {
                // For SMS-Bus - search by ID
                $cacheKey = "smsbus_service_{$serviceIdentifier}";
                $serviceData = Cache::remember($cacheKey, 300, function () use ($serviceIdentifier) {
                    $token = env('SMSBUS_TOKEN');
                    $response = Http::timeout(20)->get("https://sms-bus.com/api/control/list/projects", [
                        'token' => $token
                    ]);

                    if ($response->successful()) {
                        $data = $response->json('data');
                        if (is_array($data)) {
                            foreach ($data as $service) {
                                if ($service['id'] == $serviceIdentifier) {
                                    return [
                                        'name' => $service['title'],
                                        'code' => $service['code'],
                                        'id' => $service['id']
                                    ];
                                }
                            }
                        }
                    }
                    return null;
                });

                if ($serviceData) {
                    return [
                        'name' => $serviceData['name'],
                        'logo' => null,
                        'code' => $serviceData['code']
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fetching service details', [
                'identifier' => $serviceIdentifier,
                'error' => $e->getMessage()
            ]);
        }

        // ✅ Fallback
        return [
            'name' => 'Unknown Service',
            'logo' => null,
            'code' => 'unknown'
        ];
    }

    /**
     * ✅ IMPROVED: Get country name from API or cache
     */
    private function getCountryDetails($countryIdentifier, $isName = false): array
    {
        try {
            if ($isName) {
                // For 5sim - already have the country name
                $countryCode = $this->getCountryCode($countryIdentifier);
                return [
                    'name' => ucfirst($countryIdentifier),
                    'code' => $countryCode
                ];
            } else {
                // For SMS-Bus - fetch from API by country_id
                $token = env('SMSBUS_TOKEN');
                $cacheKey = "smsbus_country_{$countryIdentifier}";
                
                $countryData = Cache::remember($cacheKey, 300, function () use ($token, $countryIdentifier) {
                    $response = Http::timeout(20)->get("https://sms-bus.com/api/control/list/prices", [
                        'token' => $token,
                        'country_id' => $countryIdentifier
                    ]);

                    if ($response->successful()) {
                        $data = $response->json('data');
                        if (is_array($data) && !empty($data)) {
                            $first = $data[0];
                            return [
                                'name' => $first['country_name'] ?? 'Unknown',
                                'code' => strtolower($first['country_code'] ?? 'xx')
                            ];
                        }
                    }
                    return null;
                });

                if ($countryData) {
                    return $countryData;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fetching country details', [
                'identifier' => $countryIdentifier,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'name' => 'Unknown Country',
            'code' => 'xx'
        ];
    }

    public function preparePurchase(Request $request)
    {
        $request->validate([
            'service' => 'required|integer',
            'country' => 'required|integer',
            'country_name' => 'required|string',
            'country_code' => 'required|string',
        ]);

        $serviceId = $request->service;
        $countryId = $request->country;
        $countryName = $request->country_name;
        $countryCode = strtolower($request->country_code);
        $token = env('SMSBUS_TOKEN');

        if (!$token) {
            Log::error('Provider2_TOKEN not configured');
            return back()->withErrors('Service configuration error');
        }

        $cacheKey = "price_data_{$serviceId}_{$countryId}";
        $priceData = Cache::remember($cacheKey, 300, function () use ($token, $countryId, $serviceId) {
            try {
                $response = Http::timeout(30)->get("https://sms-bus.com/api/control/list/prices", [
                    'token' => $token,
                    'country_id' => $countryId,
                ]);

                if ($response->failed() || !isset($response->json()['data'])) {
                    return null;
                }

                $responseData = $response->json();
                return collect($responseData['data'])
                    ->where('country_id', $countryId)
                    ->where('project_id', $serviceId)
                    ->first();
            } catch (\Exception $e) {
                Log::error('Error fetching price data', ['error' => $e->getMessage()]);
                return null;
            }
        });

        if (!$priceData || !isset($priceData['cost']) || $priceData['cost'] <= 0) {
            return back()->withErrors('No pricing available for this service.');
        }

        // Get exchange rate for fallback (if needed)
        $exchangeRate = $this->getExchangeRate();

        // Apply client price multiplier for smsbus
        $costNGN = PricingHelper::calculateFinalPrice($priceData['cost'], self::PROVIDER_SMSBUS, $exchangeRate);

        // ✅ Get service details
        $serviceDetails = $this->getServiceDetails($serviceId, false);
        
        // ✅ Use the country details passed from the form (from the countries list)
        // This ensures we have the correct country name and code that was displayed to the user
        $countryDetails = [
            'name' => $countryName,
            'code' => $countryCode
        ];

        // Production: debug log removed
        Log::info('Country details in preparePurchase', [
            'country_id' => $countryId,
            'country_name' => $countryName,
            'country_code' => $countryCode,
            'from_price_data' => [
                'name' => $priceData['country_name'] ?? 'N/A',
                'code' => $priceData['country_code'] ?? 'N/A'
            ]
        ]);

        $purchase = [
            'provider'        => self::PROVIDER_SMSBUS,
            'country_id'      => $countryId,
            'country_name'    => $countryDetails['name'],
            'country_code'    => $countryDetails['code'],
            'service'         => $priceData['project_id'],
            'service_name'    => $serviceDetails['name'],
            'service_logo'    => $serviceDetails['logo'] ?? null,
            'cost'            => $costNGN,
            'original_cost_usd' => $priceData['cost'],
            'exchange_rate'   => $exchangeRate,
            'total_count'     => $priceData['total_count'] ?? 0,
            'timestamp'       => time(),
            'session_token'   => bin2hex(random_bytes(16)),
        ];

        if ($purchase['cost'] <= 0) {
            return back()->withErrors('Invalid service pricing. Please contact support.');
        }

        session(['purchase' => encrypt($purchase)]);

        Log::info('Provider2 purchase session created', [
            'user_id' => auth()->id(),
            'service' => $serviceId,
            'service_name' => $serviceDetails['name'],
            'country' => $countryId,
            'country_name' => $countryDetails['name'],
            'country_code' => $countryDetails['code'],
            'exchange_rate' => $exchangeRate,
            'usd_cost' => $priceData['cost'],
            'ngn_cost' => $purchase['cost']
        ]);

        return redirect()->route('confirm.payment');
    }

    public function prepareLogPurchase(Request $request, $product)
    {
        try {
            $product = Product::findOrFail($product);

            $cost = $product->client_price ?? $product->api_price ?? 0;
            if ($cost <= 0) {
                return back()->withErrors('Invalid product pricing.');
            }

            $purchase = [
                'provider' => 'logs',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_description' => $product->description ?? null,
                'cost' => $cost,
                'timestamp' => time(),
                'session_token' => bin2hex(random_bytes(16)),
            ];

            session(['purchase' => encrypt($purchase)]);

            Log::info('Logs purchase session created', [
                'user_id' => auth()->id(),
                'product_id' => $product->id,
                'product_name' => $product->name,
                'cost' => $cost,
            ]);

            return redirect()->route('confirm.payment');
        } catch (\Exception $e) {
            Log::error('Error preparing logs purchase', ['error' => $e->getMessage()]);
            return back()->withErrors('Unable to prepare purchase. Please try again.');
        }
    }


    private function processSmsbus($purchase)
{
    $token = env('SMSBUS_TOKEN');

    if (!$token) {
        $this->refundUserBalance($purchase['cost']);
        Log::error('Provider2_TOKEN not configured');
        return back()->withErrors('Service configuration error. Balance refunded.');
    }

    try {
        Log::info('Provider2 purchase attempt', [
            'user_id' => auth()->id(),
            'project_id' => $purchase['service'],
            'country_id' => $purchase['country_id'],
            'cost' => $purchase['cost']
        ]);

        $response = Http::timeout(30)
            ->withOptions(['verify' => false])
            ->get("https://sms-bus.com/api/control/get/number", [
                'project_id' => $purchase['service'],
                'country_id' => $purchase['country_id'],
                'token' => $token,
                'reuse' => false
            ]);

        Log::info('Provider2 API response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if ($response->failed()) {
            $this->refundUserBalance($purchase['cost']);
            
            Log::error('Provider2 purchase failed', [
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);
            
            return back()->withErrors('Failed to purchase number. Your balance has been refunded.');
        }

        $data = $response->json();

        // Check for successful response
        if (!isset($data['code']) || $data['code'] !== 200) {
            $this->refundUserBalance($purchase['cost']);
            
            $errorMessage = $data['message'] ?? 'Failed to purchase number';
            
            Log::error('Provider2 purchase unsuccessful', [
                'error' => $errorMessage,
                'data' => $data
            ]);
            
            return back()->withErrors($errorMessage . '. Your balance has been refunded.');
        }

        // Verify we have the required data
        if (!isset($data['data']['request_id']) || !isset($data['data']['number'])) {
            $this->refundUserBalance($purchase['cost']);
            
            Log::error('Provider2 invalid response structure', ['data' => $data]);
            
            return back()->withErrors('Invalid response from service. Your balance has been refunded.');
        }

        // Create activation record in transactions table
        $activationId = DB::table('transactions')->insertGetId([
            'user_id' => auth()->id(),
            'reference' => 'ACT_' . strtoupper(Str::random(10)),
            'request_id' => $data['data']['request_id'],
            'phone' => $data['data']['number'],
            'service_id' => $purchase['service'],
            'service_name' => $purchase['service_name'],
            'service_logo' => $purchase['service_logo'] ?? null,
            'country_id' => $purchase['country_id'],
            'country_name' => $purchase['country_name'],
            'country_code' => $purchase['country_code'],
            'amount' => $purchase['cost'],
            'original_cost_usd' => $purchase['original_cost_usd'] ?? null,
            'status' => 'pending',
            'type' => 'activation',
            'channel' => 'provider2',
            'provider' => 'Provider 2',
            'sms_code' => null,
            'expires_at' => now()->addMinutes(20),
            'response' => json_encode($data),
            'description' => "Provider 2 activation for {$purchase['service_name']} - {$purchase['country_name']}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Successful Provider2 purchase', [
            'user_id' => auth()->id(),
            'activation_id' => $activationId,
            'request_id' => $data['data']['request_id'],
            'phone' => $data['data']['number']
        ]);

        session()->forget('purchase');

        // Redirect to history page with activation ID
        return redirect()->route('history')->with('new_activation', $activationId);

    } catch (\Exception $e) {
        $this->refundUserBalance($purchase['cost']);
        
        Log::error('Provider2 purchase exception', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return back()->withErrors('An error occurred. Your balance has been refunded.');
    }
}

    // ==================== COMMON METHODS ====================

   public function showConfirm()
    {
        try {
            $encryptedPurchase = session('purchase');
            
            if (!$encryptedPurchase) {
                Log::warning('No purchase session found', ['user_id' => auth()->id()]);
                return redirect()->route('services.list')->withErrors('No purchase session found. Please start the process again.');
            }

            $purchase = decrypt($encryptedPurchase);

            $requiredFields = ['service', 'cost', 'timestamp', 'provider'];
            foreach ($requiredFields as $field) {
                if (!isset($purchase[$field])) {
                    Log::error('Invalid purchase session - missing field', ['missing_field' => $field]);
                    session()->forget('purchase');
                    return redirect()->route('services.list')->withErrors('Invalid purchase session. Please start again.');
                }
            }

            if ((time() - $purchase['timestamp']) > 1800) {
                Log::info('Purchase session expired', ['user_id' => auth()->id()]);
                session()->forget('purchase');
                return redirect()->route('services.list')->withErrors('Purchase session expired. Please start the process again.');
            }

            $wallet = auth()->user()->wallet;

            if (!$wallet) {
                Log::warning('User wallet not found', ['user_id' => auth()->id()]);
                return redirect()->route('services.list')->withErrors('No wallet found. Please contact support.');
            }

            if ($wallet->balance < $purchase['cost']) {
                return view('users.numberProcess', compact('purchase', 'wallet'))
                    ->withErrors('Insufficient wallet balance. Please top up your account.');
            }

            Log::info('Purchase confirmation page loaded', [
                'user_id' => auth()->id(),
                'service_name' => $purchase['service_name'] ?? 'Unknown',
                'country_name' => $purchase['country_name'] ?? 'Unknown',
                'cost' => $purchase['cost'],
                'exchange_rate' => $purchase['exchange_rate'] ?? 'N/A'
            ]);

            return view('users.numberProcess', compact('purchase', 'wallet'));

        } catch (\Exception $e) {
            Log::error('Error in showConfirm', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->forget('purchase');
            return redirect()->route('services.list')->withErrors('An error occurred. Please try again.');
        }
    }

    /**
     * Process the payment and purchase number
     */
    public function process(Request $request)
    {
        try {
            // ── Validate PIN + session token + optional referral flag ─────────────
            $request->validate([
                'pin'                  => 'required|digits:4',
                'session_token'        => 'required|string',
                'use_referral_balance' => 'sometimes|boolean',
            ]);
    
            // ── Recover purchase session ──────────────────────────────────────────
            $encryptedPurchase = session('purchase');
    
            if (!$encryptedPurchase) {
                Log::warning('No purchase session found during payment', ['user_id' => auth()->id()]);
                return redirect()->route('services.list')->withErrors('Session expired. Please start again.');
            }
    
            $purchase = decrypt($encryptedPurchase);
    
            // ── Verify session token ──────────────────────────────────────────────
            if (!isset($purchase['session_token']) || $purchase['session_token'] !== $request->session_token) {
                Log::warning('Session token mismatch', ['user_id' => auth()->id()]);
                session()->forget('purchase');
                return redirect()->route('services.list')->withErrors('Invalid session. Please try again.');
            }
    
            // ── Check session timeout (30 minutes) ────────────────────────────────
            if ((time() - $purchase['timestamp']) > 1800) {
                Log::info('Purchase session timeout', ['user_id' => auth()->id()]);
                session()->forget('purchase');
                return redirect()->route('services.list')->withErrors('Session expired. Please start again.');
            }
    
            /** @var \App\Models\User $user */
            $user   = auth()->user();
            $wallet = $user->wallet;
    
            if (!$wallet) {
                Log::error('User wallet not found', ['user_id' => $user->id]);
                return back()->withErrors('Wallet not found. Please contact support.');
            }
    
            // ── Verify PIN ────────────────────────────────────────────────────────
            if (!PinHelper::verifyPin($request->pin, $user)) {
                Log::warning('Invalid PIN attempt', ['user_id' => $user->id]);
                return back()->withErrors('Invalid PIN. Please try again.');
            }
    
            $cost = (float) $purchase['cost'];
    
            // ── Referral balance split ────────────────────────────────────────────
            $useReferral = !empty($request->use_referral_balance)
                && ReferralBalanceHelper::isEligible($user);
    
            if ($useReferral) {
                $split = ReferralBalanceHelper::calculate($user, $wallet, $cost);
            } else {
                $split = [
                    'referral_deduction' => 0,
                    'wallet_deduction'   => $cost,
                    'can_proceed'        => true,
                    'shortfall'          => 0,
                ];
            }
    
            // ── Balance check ─────────────────────────────────────────────────────
            if ((float) $wallet->balance < $split['wallet_deduction']) {
                Log::warning('Insufficient balance', [
                    'user_id'          => $user->id,
                    'required'         => $cost,
                    'wallet_needed'    => $split['wallet_deduction'],
                    'referral_covered' => $split['referral_deduction'],
                    'available'        => $wallet->balance,
                ]);
    
                $referralNote = $split['referral_deduction'] > 0
                    ? ' Your referral balance will cover ₦' . number_format($split['referral_deduction'], 2)
                    . ', but the wallet still needs ₦' . number_format($split['wallet_deduction'], 2) . '.'
                    : ' Required: ₦' . number_format($cost, 2) . '.';
    
                return back()->withErrors('Insufficient balance.' . $referralNote . ' Please top up your wallet.');
            }
    
            // ── Deduct balances inside a transaction ──────────────────────────────
            DB::transaction(function () use ($user, $wallet, $cost, $useReferral, &$split) {
                // Re-run apply() so the fresh DB read inside it guards against races.
                // We only reach here when the pre-flight check above passed, so the
                // amounts in $split are correct — but apply() re-reads and re-calculates
                // for safety.
                $deducted = ReferralBalanceHelper::apply($user, $wallet, $cost);
    
                // Overwrite $split with the actual deducted amounts for logging below.
                $split['referral_deduction'] = $deducted['referral_deduction'];
                $split['wallet_deduction']   = $deducted['wallet_deduction'];
    
                Log::info('Balances deducted for OTP purchase', [
                    'user_id'            => $user->id,
                    'total_cost'         => $cost,
                    'referral_deducted'  => $deducted['referral_deduction'],
                    'wallet_deducted'    => $deducted['wallet_deduction'],
                    'new_wallet_balance' => $wallet->fresh()->balance,
                ]);
            });
    
            // ── Dispatch to provider ──────────────────────────────────────────────
            if ($purchase['provider'] === self::PROVIDER_5SIM) {
                return $this->process5sim($purchase);
            }
    
            if ($purchase['provider'] === self::PROVIDER_SMSBUS) {
                return $this->processSmsbus($purchase);
            }
    
            if (isset($purchase['provider']) && $purchase['provider'] === 'logs') {
                return $this->processLogs($purchase);
            }
    
            // Unknown provider — refund
            $this->refundUserBalance($cost);
            return back()->withErrors('Unknown provider. Balance refunded.');
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator->errors()->first());
        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
    
            // Attempt refund if we know the cost
            if (isset($purchase['cost'])) {
                $this->refundUserBalance((float) $purchase['cost']);
            }
    
            return back()->withErrors('An error occurred processing payment. Please try again.');
        }
    }

    /**
     * Refund user balance
     */
    private function refundUserBalance($amount)
    {
        try {
            $user = auth()->user();
            $wallet = $user->wallet;

            if ($wallet) {
                DB::transaction(function () use ($wallet, $amount) {
                    $wallet->balance += $amount;
                    $wallet->save();

                    // Create refund transaction record
                    DB::table('transactions')->insert([
                        'reference' => 'REF_' . strtoupper(Str::random(10)),
                        'user_id' => $wallet->user_id,
                        'type' => 'credit',
                        'channel' => 'Refund',
                        'provider' => 'System',
                        'amount' => $amount,
                        'status' => 'success',
                        'description' => 'Automatic refund due to purchase failure',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });

                Log::info('User balance refunded', [
                    'user_id' => $wallet->user_id,
                    'amount' => $amount,
                    'new_balance' => $wallet->balance
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to refund balance', [
                'user_id' => auth()->id(),
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process logs product purchase (creates transaction and notifies admins)
     */
    private function processLogs($purchase)
    {
        try {
            $userId = auth()->id();
            $product = isset($purchase['product_id']) ? Product::find($purchase['product_id']) : null;

            $reference = 'LOGS' . time() . strtoupper(Str::random(8));

            $transaction = Transaction::create([
                'reference' => $reference,
                'user_id' => $userId,
                'type' => 'logs',
                'service_name' => 'Logs Purchase',
                'channel' => 'web',
                'provider' => 'logs',
                'phone' => null,
                'recipient' => $product->name ?? ($purchase['product_name'] ?? 'Logs'),
                'amount' => $purchase['cost'] ?? 0,
                'status' => 'pending',
                'response' => ['product' => $product ? $product->toArray() : ($purchase['product_name'] ?? null)],
                'description' => 'Logs purchase awaiting admin fulfillment',
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
                foreach ($validAdminEmails as $adminEmail) {
                    try {
                        Mail::to($adminEmail)->send(new LogsPurchaseAdminNotification($transaction, $product, auth()->user()));
                    } catch (\Exception $mailException) {
                        Log::warning('Failed to send admin logs notification', [
                            'transaction_id' => $transaction->id,
                            'admin_email' => $adminEmail,
                            'error' => $mailException->getMessage(),
                        ]);
                    }
                }
            } else {
                Log::warning('No valid admin emails found for logs notification', [
                    'transaction_id' => $transaction->id,
                    'admin_emails' => $adminEmails->values()->all(),
                ]);
            }

            session()->forget('purchase');

            return redirect()->route('history')->with('new_activation', $transaction->id);
        } catch (\Exception $e) {
            Log::error('Error processing logs purchase', ['error' => $e->getMessage()]);
            $this->refundUserBalance($purchase['cost'] ?? 0);
            return back()->withErrors('An error occurred. Your balance has been refunded.');
        }
    }

    /**
     * Extract verification code from SMS text
     */
    private function extractCode($smsText)
    {
        if (empty($smsText)) {
            return null;
        }

        // Convert to string if it's an array or object
        if (is_array($smsText) || is_object($smsText)) {
            $smsText = json_encode($smsText);
        }

        // Try different patterns to extract code
        $patterns = [
            '/\b(\d{4,8})\b/',                    // 4-8 digit codes
            '/code[:\s]+(\d{4,8})/i',             // "code: 1234" or "code 1234"
            '/verification[:\s]+(\d{4,8})/i',     // "verification: 1234"
            '/otp[:\s]+(\d{4,8})/i',              // "OTP: 1234"
            '/pin[:\s]+(\d{4,8})/i',              // "PIN: 1234"
            '/is[:\s]+(\d{4,8})/i',               // "is: 1234"
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $smsText, $matches)) {
                return $matches[1];
            }
        }

        // If no pattern matches, try to find the first 4-8 digit sequence
        if (preg_match('/(\d{4,8})/', $smsText, $matches)) {
            return $matches[1];
        }

        return null;
    }

     /**
     * Display logs products page with pagination and search
     */
    public function showLogs(Request $request)
    {
        try {
            $logsService = \App\Models\Service::firstOrCreate(
                ['slug' => 'logs'],
                ['name' => 'Logs', 'is_active' => true]
            );

            $logsProvider = \App\Models\Provider::firstOrCreate(
                ['code' => 'logs'],
                ['name' => 'Logs Service', 'service_id' => $logsService->id, 'api_identifier' => 'logs']
            );

            $query = Product::where('provider_id', $logsProvider->id)
                ->where('is_active', true)
                ->orderBy('created_at', 'desc');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('country_code', 'like', "%{$search}%");
                });
            }

            $products = $query->paginate(20);

            $products->getCollection()->transform(function ($product) {
                return (object)[
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

                return response()->json([
                    'success'   => true,
                    'html'      => $html,
                    'has_more'  => $products->hasMorePages(),
                    'next_page' => $products->currentPage() + 1,
                ]);
            }

            return view('users.logs', compact('products'));

        } catch (\Exception $e) {
            Log::error('Error displaying logs products', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred loading products.'
                ], 500);
            }

            return back()->withErrors('Unable to load logs products. Please try again.');
        }
    }

    public function checkoutLog($id)
    {
        try {
            $logsService = \App\Models\Service::firstOrCreate(
                ['slug' => 'logs'],
                ['name' => 'Logs', 'is_active' => true]
            );

            $logsProvider = \App\Models\Provider::firstOrCreate(
                ['code' => 'logs'],
                ['name' => 'Logs Service', 'service_id' => $logsService->id, 'api_identifier' => 'logs']
            );

            $product = Product::where('id', $id)
                ->where('provider_id', $logsProvider->id)
                ->where('is_active', true)
                ->firstOrFail();

            $productData = (object)[
                'id'           => $product->id,
                'name'         => $product->name,
                'description'  => $product->description ?? 'Premium logs access',
                'price'        => $product->client_price,
                'country'      => $product->country ?? 'Global',
                'country_code' => $product->country_code ? strtoupper($product->country_code) : 'GLB',
            ];

            return view('users.logs-checkout', ['product' => $productData]);

        } catch (\Exception $e) {
            Log::error('Error loading logs checkout', [
                'product_id' => $id,
                'error'      => $e->getMessage()
            ]);

            return redirect()->route('logs')->withErrors('Product not found or unavailable.');
        }
    }

    public function processLogPurchase(Request $request, $id)
    {
        $request->validate([
            'pin'                  => 'required|array|size:4',
            'pin.*'                => 'required|string|size:1|regex:/^[0-9]$/',
            'quantity'             => 'required|integer|min:1|max:10',
            'use_referral_balance' => 'sometimes|boolean',   // ← NEW
        ]);
    
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $pin  = implode('', $request->pin);
    
        // ── Verify PIN ────────────────────────────────────────────────────────────
        if (!\Illuminate\Support\Facades\Hash::check($pin, $user->pin)) {
            return response()->json(['success' => false, 'message' => 'Incorrect PIN. Please try again.']);
        }
    
        $product  = \App\Models\Product::where('is_active', true)->findOrFail($id);
        $quantity = (int) $request->quantity;
        $total    = $product->client_price * $quantity;
    
        $wallet = $user->wallet;
        if (!$wallet) {
            return response()->json(['success' => false, 'message' => 'Wallet not found. Please contact support.']);
        }
    
        // ── Referral balance split ────────────────────────────────────────────────
        $useReferral = !empty($request->use_referral_balance)
            && ReferralBalanceHelper::isEligible($user);
    
        if ($useReferral) {
            $split = ReferralBalanceHelper::calculate($user, $wallet, $total);
        } else {
            $split = [
                'referral_deduction' => 0,
                'wallet_deduction'   => $total,
                'can_proceed'        => true,
                'shortfall'          => 0,
            ];
        }
    
        // ── Balance check (wallet portion only) ───────────────────────────────────
        if ((float) $wallet->balance < $split['wallet_deduction']) {
            $referralNote = $split['referral_deduction'] > 0
                ? ' Your referral balance will cover ₦' . number_format($split['referral_deduction'], 2)
                . ', but the wallet still needs ₦' . number_format($split['wallet_deduction'], 2) . '.'
                : '';
    
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance.' . $referralNote . ' Please fund your wallet.',
            ]);
        }
    
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
    
            // ── Deduct balances (referral first, then wallet) ─────────────────────
            $deducted = ReferralBalanceHelper::apply($user, $wallet, $total);
    
            $assignedCredentials = [];
            $mode                = $product->mode ?? 'manual';
    
            // ── AUTO-ASSIGN credentials for automatic products ────────────────────
            if ($mode === 'automatic') {
                $logDetails = is_array($product->log_details)
                    ? $product->log_details
                    : (json_decode($product->log_details ?? '[]', true) ?: []);
    
                $availableIndexes = [];
                foreach ($logDetails as $index => $detail) {
                    if (empty($detail['sold'])) {
                        $availableIndexes[] = $index;
                        if (count($availableIndexes) >= $quantity) {
                            break;
                        }
                    }
                }
    
                if (!empty($availableIndexes)) {
                    foreach ($availableIndexes as $idx) {
                        $assignedCredentials[] = [
                            'username' => $logDetails[$idx]['username'] ?? '',
                            'password' => $logDetails[$idx]['password'] ?? '',
                            'extra'    => $logDetails[$idx]['extra']    ?? '',
                        ];
                    }
                }
                // If availableIndexes is empty → treat as manual (pending)
            }
    
            // ── Build response payload ────────────────────────────────────────────
            $isAutoFulfilled = $mode === 'automatic' && !empty($assignedCredentials);
            $status          = $isAutoFulfilled ? 'success' : 'pending';
    
            $responsePayload = [
                'product' => [
                    'id'      => $product->id,
                    'name'    => $product->name,
                    'mode'    => $mode,
                    'country' => $product->country,
                ],
                'quantity'           => $quantity,
                'auto_fulfilled'     => $isAutoFulfilled,
                'credentials'        => [],
                'fulfilled_count'    => 0,
                'referral_deducted'  => $deducted['referral_deduction'],  // ← NEW (audit trail)
                'wallet_deducted'    => $deducted['wallet_deduction'],    // ← NEW (audit trail)
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
                $responsePayload['credentials']     = array_fill(0, $quantity, '');
                $responsePayload['fulfilled_count'] = 0;
            }
    
            // ── Create transaction ────────────────────────────────────────────────
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
                'description'  => $isAutoFulfilled
                    ? 'Logs order auto-fulfilled'
                    : 'Logs order pending admin fulfilment',
                'response'     => $responsePayload,
            ]);
    
            // ── Mark credentials as sold on the product ───────────────────────────
            if ($isAutoFulfilled) {
                $logDetails = is_array($product->log_details)
                    ? $product->log_details
                    : (json_decode($product->log_details ?? '[]', true) ?: []);
    
                $soldCount = 0;
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
    
            \Illuminate\Support\Facades\DB::commit();
    
            // ── Notify admins (outside transaction — email failure won't rollback) ─
            try {
                $adminEmails = \App\Models\Admin::whereNotNull('email')
                    ->where('email', '<>', '')
                    ->pluck('email')
                    ->filter()
                    ->unique();
    
                $validAdminEmails = $adminEmails
                    ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
                    ->values()
                    ->all();
    
                if (!empty($validAdminEmails)) {
                    foreach ($validAdminEmails as $adminEmail) {
                        try {
                            \Illuminate\Support\Facades\Mail::to($adminEmail)
                                ->send(new \App\Mail\LogsPurchaseAdminNotification(
                                    $transaction,
                                    $product,
                                    $user
                                ));
                        } catch (\Exception $mailException) {
                            \Illuminate\Support\Facades\Log::warning('Failed to send admin logs notification', [
                                'transaction_id' => $transaction->id,
                                'admin_email'    => $adminEmail,
                                'error'          => $mailException->getMessage(),
                            ]);
                        }
                    }
                }
            } catch (\Exception $notifyException) {
                \Illuminate\Support\Facades\Log::error('Admin logs notification error', [
                    'transaction_id' => $transaction->id,
                    'error'          => $notifyException->getMessage(),
                ]);
            }
    
            // ── Build success message ─────────────────────────────────────────────
            $successMessage = $isAutoFulfilled
                ? 'Purchase successful! Your login details are ready.'
                : 'Order placed successfully! Your details will be delivered shortly.';
    
            if ($deducted['referral_deduction'] > 0) {
                $successMessage .= ' ₦' . number_format($deducted['referral_deduction'], 2)
                    . ' was paid from your referral balance.';
            }
    
            return response()->json([
                'success'           => true,
                'message'           => $successMessage,
                'referral_deducted' => $deducted['referral_deduction'],  // ← NEW
                'wallet_deducted'   => $deducted['wallet_deduction'],    // ← NEW
            ]);
    
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Log purchase error', [
                'error'      => $e->getMessage(),
                'product_id' => $id,
                'user_id'    => $user->id,
            ]);
    
            return response()->json(['success' => false, 'message' => 'Purchase failed. Please try again.']);
        }
    }

}