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

        $getServiceIcon = function($code) {
            return $this->getServiceIconMap()[$code] ?? null;
        };

        $iconMap = $getServiceIcon('');
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
        if ($search) {
            $products = $products->filter(function($p) use ($search) {
                return str_contains(strtolower($p['title']), strtolower($search)) ||
                       str_contains(strtolower($p['code']), strtolower($search));
            });
        }

        $others = $search ? $products : $products->reject(fn($p) => in_array(strtolower($p['code']), $popularCodes));

        $perPage = 30;
        $page = $request->input('page', 1);
        $paginated = $others->forPage($page, $perPage)->values();

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

    // 5sim.net: Get countries for a service
    public function showCountries5sim($serviceId)
    {
        $token = env('FIVESIM_TOKEN');
        $cacheKey = "5sim_countries_{$serviceId}";
        
        $countries = Cache::remember($cacheKey, 600, function () use ($token, $serviceId) {
            try {
                // Fetch prices to get available countries for this service
                $response = Http::timeout(20)
                    ->withHeaders([
                        'Authorization' => "Bearer {$token}",
                        'Accept' => 'application/json'
                    ])
                    ->get("https://5sim.net/v1/guest/prices");

                if ($response->successful()) {
                    $data = $response->json();
                    $countries = [];
                    
                    // Extract countries that have this service
                    foreach ($data as $countryName => $services) {
                        if (isset($services[$serviceId])) {
                            $countries[] = [
                                'name' => $countryName,
                                'code' => $this->getCountryCode($countryName),
                                'service' => $serviceId,
                                'operators' => array_keys($services[$serviceId])
                            ];
                        }
                    }
                    
                    return collect($countries);
                }
                
                return collect([]);
            } catch (\Exception $e) {
                Log::error('5sim countries fetch error', ['error' => $e->getMessage()]);
                return collect([]);
            }
        });

        $perPage = 20;
        $page = request()->get('page', 1);
        $search = request()->get('search', '');
        
        if ($search) {
            $countries = $countries->filter(function($country) use ($search) {
                return str_contains(strtolower($country['name']), strtolower($search));
            });
        }

        $paginatedCountries = new LengthAwarePaginator(
            $countries->forPage($page, $perPage),
            $countries->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        if (request()->ajax()) {
            return response()->json([
                'data' => $countries->forPage($page, $perPage)->values(),
                'last_page' => $paginatedCountries->lastPage()
            ]);
        }

        return view('users.countries-5sim', [
            'countries' => $paginatedCountries,
            'serviceId' => $serviceId
        ]);
    }

    // 5sim.net: Get operators for a country
    public function showOperators5sim($serviceId, $country)
    {
        $token = env('FIVESIM_TOKEN');
        $cacheKey = "5sim_operators_{$serviceId}_{$country}";
        
        $operators = Cache::remember($cacheKey, 600, function () use ($token, $serviceId, $country) {
            try {
                $response = Http::timeout(20)
                    ->withHeaders([
                        'Authorization' => "Bearer {$token}",
                        'Accept' => 'application/json'
                    ])
                    ->get("https://5sim.net/v1/guest/prices", [
                        'country' => $country,
                        'product' => $serviceId
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $operators = [];
                    
                    if (isset($data[$country][$serviceId])) {
                        foreach ($data[$country][$serviceId] as $operator => $details) {
                            $operators[] = [
                                'name' => $operator,
                                'cost' => $details['cost'],
                                'count' => $details['count'],
                                'rate' => $details['rate'] ?? null
                            ];
                        }
                    }
                    
                    return collect($operators);
                }
                
                return collect([]);
            } catch (\Exception $e) {
                Log::error('5sim operators fetch error', ['error' => $e->getMessage()]);
                return collect([]);
            }
        });

        return view('users.operators-5sim', [
            'operators' => $operators,
            'serviceId' => $serviceId,
            'country' => $country
        ]);
    }

    // SMS-Bus: Existing implementation
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

    // Get prices for SMS-Bus
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

    // Prepare purchase for 5sim
    public function preparePurchase5sim(Request $request)
    {
        $request->validate([
            'service' => 'required|string',
            'country' => 'required|string',
            'operator' => 'required|string',
        ]);

        $serviceId = $request->service;
        $country = $request->country;
        $operator = $request->operator;
        $token = env('FIVESIM_TOKEN');

        if (!$token) {
            Log::error('FIVESIM_TOKEN not configured');
            return back()->withErrors('Service configuration error');
        }

        $cacheKey = "5sim_price_{$serviceId}_{$country}_{$operator}";
        $priceData = Cache::remember($cacheKey, 300, function () use ($token, $serviceId, $country, $operator) {
            try {
                $response = Http::timeout(20)
                    ->withHeaders([
                        'Authorization' => "Bearer {$token}",
                        'Accept' => 'application/json'
                    ])
                    ->get("https://5sim.net/v1/guest/prices", [
                        'country' => $country,
                        'product' => $serviceId
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data[$country][$serviceId][$operator])) {
                        return $data[$country][$serviceId][$operator];
                    }
                }
                
                return null;
            } catch (\Exception $e) {
                Log::error('5sim price fetch error', ['error' => $e->getMessage()]);
                return null;
            }
        });

        if (!$priceData || !isset($priceData['cost'])) {
            return back()->withErrors('No pricing available for this service.');
        }

        $usdToNgn = $this->getExchangeRate();

        $purchase = [
            'provider' => self::PROVIDER_5SIM,
            'country' => $country,
            'service' => $serviceId,
            'operator' => $operator,
            'service_name' => ucfirst($serviceId),
            'country_name' => ucfirst($country),
            'operator_name' => ucfirst($operator),
            'cost' => round($priceData['cost'] * $usdToNgn, 2),
            'original_cost_usd' => $priceData['cost'],
            'exchange_rate' => $usdToNgn,
            'total_count' => $priceData['count'] ?? 0,
            'rate' => $priceData['rate'] ?? null,
            'timestamp' => time(),
            'session_token' => bin2hex(random_bytes(16)),
        ];

        if ($purchase['cost'] <= 0) {
            return back()->withErrors('Invalid service pricing. Please contact support.');
        }

        session(['purchase' => encrypt($purchase)]);

        Log::info('5sim purchase session created', [
            'user_id' => auth()->id(),
            'service' => $serviceId,
            'country' => $country,
            'operator' => $operator
        ]);

        return redirect()->route('confirm.payment');
    }

    // Prepare purchase for SMS-Bus (existing)
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
                
                $matchedData = collect($responseData['data'])
                    ->where('country_id', $countryId)
                    ->where('project_id', $serviceId)
                    ->first();

                return $matchedData;
                
            } catch (\Exception $e) {
                Log::error('Error fetching price data', ['error' => $e->getMessage()]);
                return null;
            }
        });

        if (!$priceData || !isset($priceData['cost']) || $priceData['cost'] <= 0) {
            return back()->withErrors('No pricing available for this service.');
        }

        $usdToNgn = $this->getExchangeRate();
        $serviceDetails = $this->getServiceDetails($serviceId);

        $purchase = [
            'provider' => self::PROVIDER_SMSBUS,
            'country_id' => $countryId,
            'country_name' => $priceData['country_name'] ?? 'Unknown',
            'country_code' => strtolower($priceData['country_code'] ?? ''),
            'service' => $priceData['project_id'],
            'service_name' => $serviceDetails['name'] ?? ucfirst($priceData['project_code'] ?? 'Unknown Service'),
            'service_logo' => $serviceDetails['logo'] ?? null,
            'cost' => round($priceData['cost'] * $usdToNgn, 2),
            'original_cost_usd' => $priceData['cost'],
            'exchange_rate' => $usdToNgn,
            'total_count' => $priceData['total_count'] ?? 0,
            'timestamp' => time(),
            'session_token' => bin2hex(random_bytes(16)),
        ];

        if ($purchase['cost'] <= 0) {
            return back()->withErrors('Invalid service pricing. Please contact support.');
        }

        session(['purchase' => encrypt($purchase)]);

        Log::info('SMS-Bus purchase session created', [
            'user_id' => auth()->id(),
            'service' => $serviceId,
            'country' => $countryId
        ]);

        return redirect()->route('confirm.payment');
    }

    public function showConfirm()
    {
        try {
            $encryptedPurchase = session('purchase');
            
            if (!$encryptedPurchase) {
                Log::warning('No purchase session found', ['user_id' => auth()->id()]);
                return redirect()->route('services.index')->withErrors('No purchase session found. Please start the process again.');
            }

            $purchase = decrypt($encryptedPurchase);

            $requiredFields = ['service', 'cost', 'timestamp', 'provider'];
            foreach ($requiredFields as $field) {
                if (!isset($purchase[$field])) {
                    Log::error('Invalid purchase session - missing field', ['missing_field' => $field]);
                    session()->forget('purchase');
                    return redirect()->route('services.index')->withErrors('Invalid purchase session. Please start again.');
                }
            }

            if ((time() - $purchase['timestamp']) > 1800) {
                Log::info('Purchase session expired', ['user_id' => auth()->id()]);
                session()->forget('purchase');
                return redirect()->route('services.index')->withErrors('Purchase session expired. Please start the process again.');
            }

            $user = auth()->user();
            if ($user && isset($user->balance) && $user->balance < $purchase['cost']) {
                return view('users.numberProcess', compact('purchase'))
                    ->withErrors('Insufficient balance. Please top up your account.');
            }

            return view('users.numberProcess', compact('purchase'));
            
        } catch (\Exception $e) {
            Log::error('Error in showConfirm', ['error' => $e->getMessage()]);
            session()->forget('purchase');
            return redirect()->route('services.index')->withErrors('An error occurred. Please try again.');
        }
    }

    public function process(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:4',
            'session_token' => 'sometimes|string'
        ]);

        try {
            $encryptedPurchase = session('purchase');
            
            if (!$encryptedPurchase) {
                return back()->withErrors('No purchase session found. Please start again.');
            }

            $purchase = decrypt($encryptedPurchase);

            if ((time() - $purchase['timestamp']) > 1800) {
                session()->forget('purchase');
                return back()->withErrors('Purchase session expired. Please start again.');
            }

            if ($request->has('session_token') && 
                isset($purchase['session_token']) && 
                $request->session_token !== $purchase['session_token']) {
                return back()->withErrors('Invalid session. Please try again.');
            }

            if (!PinHelper::verifyPin($request->pin)) {
                return back()->withErrors('Invalid PIN. Please try again.')->withInput();
            }

            if (!$this->checkUserBalance($purchase['cost'])) {
                return back()->withErrors('Insufficient balance. Please top up your account.');
            }

            if (!$this->deductUserBalance($purchase['cost'])) {
                return back()->withErrors('Failed to process payment. Please try again.');
            }

            // Route to appropriate provider
            if ($purchase['provider'] === self::PROVIDER_5SIM) {
                return $this->process5sim($purchase);
            } else {
                return $this->processSmsbus($purchase);
            }

        } catch (\Exception $e) {
            Log::error('Purchase processing error', ['error' => $e->getMessage()]);
            
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
            return back()->withErrors('An error occurred processing your purchase.');
        }
    }

    private function process5sim($purchase)
    {
        $token = env('FIVESIM_TOKEN');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json'
                ])
                ->get("https://5sim.net/v1/user/buy/activation/{$purchase['country']}/{$purchase['operator']}/{$purchase['service']}");

            if ($response->failed()) {
                $this->refundUserBalance($purchase['cost']);
                
                Log::error('5sim number purchase failed', [
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
                
                return back()->withErrors('Failed to purchase number. Your balance has been refunded.');
            }

            $data = $response->json();

            Log::info('Successful 5sim purchase', [
                'user_id' => auth()->id(),
                'service' => $purchase['service'],
                'country' => $purchase['country']
            ]);

            session()->forget('purchase');

            return view('users.purchase-success', [
                'data' => $data,
                'purchase' => $purchase
            ]);

        } catch (\Exception $e) {
            $this->refundUserBalance($purchase['cost']);
            Log::error('5sim purchase error', ['error' => $e->getMessage()]);
            return back()->withErrors('An error occurred. Your balance has been refunded.');
        }
    }

    private function processSmsbus($purchase)
    {
        $token = env('SMSBUS_TOKEN');

        try {
            $response = Http::timeout(30)->get("https://sms-bus.com/api/control/get/number", [
                'token' => $token,
                'country_id' => $purchase['country_id'],
                'project_id' => $purchase['service'],
            ]);

            if ($response->failed()) {
                $this->refundUserBalance($purchase['cost']);
                
                Log::error('SMS-Bus purchase failed', [
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
                
                return back()->withErrors('Failed to purchase number. Your balance has been refunded.');
            }

            $data = $response->json();

            if (!isset($data['status']) || $data['status'] !== true) {
                $this->refundUserBalance($purchase['cost']);
                
                $errorMessage = isset($data['message']) ? $data['message'] : 'Failed to purchase number';
                return back()->withErrors($errorMessage . '. Your balance has been refunded.');
            }

            Log::info('Successful SMS-Bus purchase', [
                'user_id' => auth()->id(),
                'service' => $purchase['service']
            ]);

            session()->forget('purchase');

            return view('users.purchase-success', [
                'data' => $data,
                'purchase' => $purchase
            ]);

        } catch (\Exception $e) {
            $this->refundUserBalance($purchase['cost']);
            Log::error('SMS-Bus purchase error', ['error' => $e->getMessage()]);
            return back()->withErrors('An error occurred. Your balance has been refunded.');
        }
    }

    // Helper methods
    private function getExchangeRate()
    {
        try {
            $response = Http::timeout(10)->get('https://api.exchangerate.host/latest', [
                'base' => 'USD',
                'symbols' => 'NGN'
            ]);

            if ($response->ok() && isset($response->json()['rates']['NGN'])) {
                return $response->json()['rates']['NGN'];
            }

            return 1500;
        } catch (\Exception $e) {
            Log::error('Exchange rate fetch failed', ['error' => $e->getMessage()]);
            return 1500;
        }
    }

    private function getServiceDetails($serviceId)
    {
        $token = env('SMSBUS_TOKEN');
        if (!$token) {
            return ['name' => 'Unknown Service', 'logo' => null];
        }

        $cacheKey = "service_details_all";

        $services = Cache::remember($cacheKey, 600, function () use ($token) {
            try {
                $response = Http::timeout(20)->get("https://sms-bus.com/api/control/list/projects", [
                    'token' => $token
                ]);

                if ($response->failed() || !isset($response->json()['data'])) {
                    return [];
                }

                return $response->json()['data'];
            } catch (\Exception $e) {
                return [];
            }
        });

        if (isset($services[$serviceId])) {
            return [
                'name' => $services[$serviceId]['title'],
                'logo' => "/images/services/" . strtolower($services[$serviceId]['code']) . ".png"
            ];
        }

        return ['name' => 'Unknown Service', 'logo' => null];
    }

    private function checkUserBalance($amount)
    {
        $user = auth()->user();
        return $user && $user->balance >= $amount;
    }

    private function deductUserBalance($amount)
    {
        try {
            $user = auth()->user();
            
            if (!$user || $user->balance < $amount) {
                return false;
            }

            DB::transaction(function () use ($user, $amount) {
                $user->decrement('balance', $amount);
                
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
            Log::error('Failed to deduct balance', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function refundUserBalance($amount)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return false;
            }

            DB::transaction(function () use ($user, $amount) {
                $user->increment('balance', $amount);
                
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
            Log::error('Failed to refund balance', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function getCountryCode($countryName)
    {
        $codes = [
            'russia' => 'ru', 'ukraine' => 'ua', 'kazakhstan' => 'kz',
            'england' => 'gb', 'usa' => 'us', 'india' => 'in',
            'indonesia' => 'id', 'poland' => 'pl', 'nigeria' => 'ng',
            'egypt' => 'eg', 'mexico' => 'mx', 'vietnam' => 'vn',
        ];
        
        return $codes[strtolower($countryName)] ?? 'un';
    }

    private function getMockServices()
    {
        return collect([
            ['id' => 1, 'code' => 'WA', 'title' => 'WhatsApp'],
            ['id' => 2, 'code' => 'FB', 'title' => 'Facebook'],
            ['id' => 3, 'code' => 'TG', 'title' => 'Telegram'],
            ['id' => 4, 'code' => 'GOOGLE', 'title' => 'Google'],
            ['id' => 5, 'code' => 'IG', 'title' => 'Instagram'],
        ]);
    }

    private function getServiceIconMap()
    {
        return [
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
            'pp' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/paypal.svg',
            'tk' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/tiktok.svg',
            'uber' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/uber.svg',
        ];
    }
}