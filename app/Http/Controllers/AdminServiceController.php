<?php
namespace App\Http\Controllers;

use App\Helpers\DemmyPayHelper;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Provider;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Transaction;
use App\Models\EsimUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AdminServiceController extends Controller
{
    public function index()
    {
        $services = Service::all();
        return view('admin.servicelist', compact('services'));
    }

    public function providers($serviceId)
    {
        $service = Service::findOrFail($serviceId);
        $providers = $service->providers;
        return view('admin.serviceproviders', compact('service', 'providers'));
    }

    public function products($providerId)
    {
        $provider = Provider::findOrFail($providerId);


        // Fetch existing DB products for this provider
        $dbProducts = $provider->products->keyBy('product_code');
        $products = [];

        try {
            if ($provider->api_identifier === 'demmyglobal') {
                // Handle Data Products (Demmy Global)
                $response = DemmyPayHelper::getDataProducts();
                $apiProducts = $response['data'] ?? [];

                foreach ($apiProducts as $apiProduct) {
                    $productCode = (string)($apiProduct['id'] ?? '');
                    $apiPrice    = (float)($apiProduct['amount'] ?? 0);

                    $name = ($apiProduct['name'] ?? 'Unknown') . ' - ' .
                            ($apiProduct['size'] ?? 'N/A') . ' (' .
                            ($apiProduct['validity'] ?? 'N/A') . ')';

                    $dbProduct = $dbProducts->get($productCode);

                    $products[] = (object)[
                        'id'           => $dbProduct->id ?? null,
                        'product_code' => $productCode,
                        'category'     => strtoupper($apiProduct['network']['name'] ?? 'DATA'),
                        'name'         => $name,
                        'api_price'    => $apiPrice,
                        'client_price' => $dbProduct->client_price ?? null,
                        'is_active'    => $dbProduct->is_active ?? true,
                        'updated_at'   => $dbProduct->updated_at ?? now(),
                    ];
                }
            }elseif ($provider->api_identifier === 'clubkonnect-cable') {
                $userId = env('CLUBKONNECT_USERID');
                $url = "https://www.nellobytesystems.com/APICableTVPackagesV2.asp?UserID=" . $userId;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                $responseBody = curl_exec($ch);
                $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error        = curl_error($ch);
                curl_close($ch);

                if ($error) {
                    \Log::error('ClubKonnect Cable cURL Error:', ['error' => $error]);
                } elseif ($httpCode == 200) {
                    $apiResponse = json_decode($responseBody, true);

                    if (json_last_error() === JSON_ERROR_NONE && !empty($apiResponse['TV_ID'])) {
                        foreach ($apiResponse['TV_ID'] as $companyName => $companyArray) {
                            foreach ($companyArray as $companyData) {
                                if (!empty($companyData['PRODUCT']) && is_array($companyData['PRODUCT'])) {
                                    foreach ($companyData['PRODUCT'] as $product) {
                                        $productCode = (string)($product['PACKAGE_ID'] ?? '');
                                        $productName = $product['PACKAGE_NAME'] ?? 'Unknown';

                                        $apiPrice = (float)($product['PACKAGE_AMOUNT'] ?? 0);

                                        $dbProduct = $dbProducts->get($productCode);

                                        $products[] = (object)[
                                            'id'           => $dbProduct->id ?? null,
                                            'product_code' => $productCode,
                                            'category'     => strtoupper($companyName), // e.g. DSTV, GOTV
                                            'name'         => $productName,
                                            'api_price'    => $apiPrice,
                                            'client_price' => $dbProduct->client_price ?? null,
                                            'is_active'    => $dbProduct->is_active ?? true,
                                            'updated_at'   => $dbProduct->updated_at ?? now(),
                                        ];
                                    }
                                }
                            }
                        }
                    } else {
                        \Log::error('ClubKonnect Cable Invalid JSON:', [
                            'response_preview' => substr($responseBody, 0, 500)
                        ]);
                    }
                }
            }elseif ($provider->api_identifier === 'clubkonnect-electricity') {
                // Handle Electricity Products (ClubKonnect) - Using cURL directly
                $userId = env('CLUBKONNECT_USERID');
                $url = "https://www.nellobytesystems.com/APIElectricityDiscosV1.asp?UserID=" . $userId;

                // Use cURL directly as Laravel HTTP client might be blocked
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: application/json, text/plain, */*',
                    'Accept-Language: en-US,en;q=0.9',
                    'Accept-Encoding: gzip, deflate, br',
                    'Connection: keep-alive',
                    'Cache-Control: no-cache',
                ]);

                $responseBody = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error) {
                    \Log::error('ClubKonnect cURL Error:', ['error' => $error]);
                } elseif ($httpCode == 200) {
                    // Log first 500 chars to see what we're getting
                    \Log::info('ClubKonnect Raw Response Preview:', [
                        'response_preview' => substr($responseBody, 0, 500),
                        'length' => strlen($responseBody)
                    ]);

                    // Try to parse as JSON
                    $apiResponse = json_decode($responseBody, true);

                    if (json_last_error() === JSON_ERROR_NONE && !empty($apiResponse['ELECTRIC_COMPANY'])) {
                        // Process the JSON response structure
                        foreach ($apiResponse['ELECTRIC_COMPANY'] as $companyKey => $companyDataArray) {
                            if (!empty($companyDataArray) && is_array($companyDataArray)) {
                                $companyData = $companyDataArray[0];

                                $companyId   = (string)($companyData['ID'] ?? '');
                                $companyName = $companyData['NAME'] ?? str_replace('_', ' ', $companyKey);

                                if (!empty($companyData['PRODUCT']) && is_array($companyData['PRODUCT'])) {
                                    foreach ($companyData['PRODUCT'] as $product) {
                                        $productCode = $companyId . '-' . ($product['PRODUCT_ID'] ?? '');
                                        $productName = $companyName . ' - ' . ucfirst($product['PRODUCT_TYPE'] ?? 'Unknown');

                                        $min = $product['MINAMOUNT'] ?? $product['MINIMUN_AMOUNT'] ?? 0;
                                        $max = $product['MAXAMOUNT'] ?? $product['MAXIMUM_AMOUNT'] ?? 0;

                                        $dbProduct = $dbProducts->get($productCode);

                                        $products[] = (object)[
                                            'id'           => $dbProduct->id ?? null,
                                            'product_code' => $productCode,
                                            'category'     => 'ELECTRICITY',
                                            'name'         => $productName . " (₦" . number_format($min) . " - ₦" . number_format($max) . ")",
                                            'api_price'    => 0,
                                            'client_price' => $dbProduct->client_price ?? null,
                                            'is_active'    => $dbProduct->is_active ?? true,
                                            'updated_at'   => $dbProduct->updated_at ?? now(),
                                        ];
                                    }
                                }
                            }
                        }
                    } else {
                        // If JSON parsing fails, maybe it's HTML error page - extract useful info
                        \Log::error('ClubKonnect API JSON parsing failed:', [
                            'json_error' => json_last_error_msg(),
                            'response_preview' => substr($responseBody, 0, 1000)
                        ]);

                        // Try to extract error message from HTML if present
                        if (strpos($responseBody, 'error') !== false || strpos($responseBody, 'Error') !== false) {
                            preg_match('/<title>(.*?)<\/title>/i', $responseBody, $matches);
                            $pageTitle = $matches[1] ?? 'Unknown error';
                            \Log::error('ClubKonnect API HTML Error:', ['page_title' => $pageTitle]);
                        }
                    }
                } else {
                    \Log::error('ClubKonnect API HTTP Error:', [
                        'http_code' => $httpCode,
                        'response' => substr($responseBody, 0, 500)
                    ]);
                }
            } elseif ($provider->api_identifier === 'smsbus') {
                $token = env('SMSBUS_TOKEN');
                $response = Http::timeout(30)->get("https://sms-bus.com/api/control/list/projects", [
                    'token' => $token,
                ]);
                if ($response->ok()) {
                    $apiServices = $response->json();
                    $exchangeRate = $this->getExchangeRate();
                    foreach ($apiServices as $service) {
                        $productCode = (string)$service['id'];
                        $apiPrice = (float)$service['price'];
                        $name = $service['name'];
                        $dbProduct = $dbProducts->get($productCode);
                        $calculatedClientPrice = $apiPrice * $exchangeRate;
                        $products[] = (object)[
                            'id'           => $dbProduct->id ?? null,
                            'product_code' => $productCode,
                            'category'     => 'OTP',
                            'name'         => $name,
                            'api_price'    => $apiPrice,
                            'client_price' => $dbProduct->client_price ?? $calculatedClientPrice,
                            'is_active'    => $dbProduct->is_active ?? true,
                            'updated_at'   => $dbProduct->updated_at ?? now(),
                        ];
                    }
                } else {
                    // Fallback to DB products if API fails
                    foreach ($provider->products as $dbProduct) {
                        $products[] = (object)[
                            'id'           => $dbProduct->id,
                            'product_code' => $dbProduct->product_code,
                            'category'     => $dbProduct->category ?? 'OTP',
                            'name'         => $dbProduct->name,
                            'api_price'    => $dbProduct->api_price ?? 0,
                            'client_price' => $dbProduct->client_price,
                            'is_active'    => $dbProduct->is_active ?? true,
                            'updated_at'   => $dbProduct->updated_at,
                        ];
                    }
                }
            } elseif ($provider->api_identifier === '5sim') {
                $response = Http::get('https://5sim.net/v1/guest/products');
                if ($response->ok()) {
                    $apiProducts = $response->json();
                    $exchangeRate = $this->get5simExchangeRate();
                    foreach ($apiProducts as $productCode => $productData) {
                        $name = $productData['name'] ?? $productCode;
                        $minPrice = null;
                        if (isset($productData['countries'])) {
                            foreach ($productData['countries'] as $country => $countryData) {
                                if (isset($countryData['price'])) {
                                    $price = (float)$countryData['price'];
                                    if ($minPrice === null || $price < $minPrice) {
                                        $minPrice = $price;
                                    }
                                }
                            }
                        }
                        if ($minPrice !== null) {
                            $apiPrice = $minPrice;
                            $dbProduct = $dbProducts->get($productCode);
                            $calculatedClientPrice = $apiPrice * $exchangeRate;
                            $products[] = (object)[
                                'id'           => $dbProduct->id ?? null,
                                'product_code' => $productCode,
                                'category'     => 'OTP',
                                'name'         => $name,
                                'api_price'    => $apiPrice,
                                'client_price' => $dbProduct->client_price ?? $calculatedClientPrice,
                                'is_active'    => $dbProduct->is_active ?? true,
                                'updated_at'   => $dbProduct->updated_at ?? now(),
                            ];
                        }
                    }
                } else {
                    // Fallback to DB products if API fails
                    foreach ($provider->products as $dbProduct) {
                        $products[] = (object)[
                            'id'           => $dbProduct->id,
                            'product_code' => $dbProduct->product_code,
                            'category'     => $dbProduct->category ?? 'OTP',
                            'name'         => $dbProduct->name,
                            'api_price'    => $dbProduct->api_price ?? 0,
                            'client_price' => $dbProduct->client_price,
                            'is_active'    => $dbProduct->is_active ?? true,
                            'updated_at'   => $dbProduct->updated_at,
                        ];
                    }
                }
            } else {
                // Fallback: show only DB products
                foreach ($provider->products as $dbProduct) {
                    $products[] = (object)[
                        'id'           => $dbProduct->id,
                        'product_code' => $dbProduct->product_code,
                        'category'     => $dbProduct->category ?? 'UNKNOWN',
                        'name'         => $dbProduct->name,
                        'api_price'    => $dbProduct->api_price ?? 0,
                        'client_price' => $dbProduct->client_price,
                        'is_active'    => $dbProduct->is_active ?? true,
                        'updated_at'   => $dbProduct->updated_at,
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::error('Products fetch error:', [
                'provider' => $provider->api_identifier,
                'message'  => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            // On error, fallback to showing DB products
            foreach ($provider->products as $dbProduct) {
                $products[] = (object)[
                    'id'           => $dbProduct->id,
                    'product_code' => $dbProduct->product_code,
                    'category'     => $dbProduct->category ?? 'UNKNOWN',
                    'name'         => $dbProduct->name,
                    'api_price'    => $dbProduct->api_price ?? 0,
                    'client_price' => $dbProduct->client_price,
                    'updated_at'   => $dbProduct->updated_at,
                ];
            }
        }

        return view('admin.servicesprice', compact('provider', 'products'));
    }


    public function saveProducts(Request $request, $providerId)
    {
        $provider = Provider::findOrFail($providerId);

        try {
            if ($provider->api_identifier === 'demmyglobal') {
                // Handle Data Products (Demmy Global)
                $response = DemmyPayHelper::getDataProducts();
                $apiProducts = $response['data'] ?? [];
                $clientPrice = $request->input('client_price');

                foreach ($apiProducts as $apiProduct) {
                    $productCode = (string)($apiProduct['id']);
                    $apiPrice    = (float)($apiProduct['amount']);

                    $productName = ($apiProduct['name'] ?? 'Unknown') .
                                ' - ' . ($apiProduct['size'] ?? 'N/A') .
                                ' (' . ($apiProduct['validity'] ?? 'N/A') . ')';

                    Product::updateOrCreate(
                        [
                            'provider_id'  => $provider->id,
                            'product_code' => $productCode,
                        ],
                        [
                            'name'         => $productName,
                            'category'     => strtoupper($apiProduct['network']['name'] ?? 'DATA'),
                            'api_price'    => $apiPrice,
                            'client_price' => $clientPrice,
                        ]
                    );
                }
            }elseif($provider->api_identifier === 'morethanpanel'){
                $clientPrice = $request->input('client_price');
                Product::updateOrCreate(
                        [
                            'provider_id'  => $provider->id,
                            'product_code' => 'morethanpanel',
                        ],
                        [
                            'name'         => 'MoreThanPanel',
                            'category'     => 'social',
                            'api_price'    => 0,
                            'client_price' => $clientPrice,
                        ]
                    );


            } elseif ($provider->api_identifier === 'clubkonnect-electricity') {
                // Handle Electricity Products
                $userId = env('CLUBKONNECT_USERID');
                $response = Http::get("https://www.nellobytesystems.com/APIElectricityDiscosV1.asp", [
                    'UserID' => $userId
                ]);

                if ($response->ok()) {
                    $apiResponse = $response->json();
                    $clientPrice = $request->input('client_price');

                    foreach ($apiResponse as $discoKey => $discoArray) {
                        foreach ($discoArray as $disco) {
                            $companyId   = (string)($disco['ID'] ?? '');
                            $companyName = $disco['NAME'] ?? 'Unknown Disco';

                            foreach ($disco['PRODUCT'] as $product) {
                                $productCode = $companyId . '-' . ($product['PRODUCT_ID'] ?? '');
                                $productName = $companyName . ' - ' . ucfirst($product['PRODUCT_TYPE'] ?? 'Unknown');

                                $min = $product['MINIMUN_AMOUNT'] ?? $product['MINAMOUNT'] ?? 0;
                                $max = $product['MAXIMUM_AMOUNT'] ?? $product['MAXAMOUNT'] ?? 0;

                                Product::updateOrCreate(
                                    [
                                        'provider_id'  => $provider->id,
                                        'product_code' => $productCode,
                                    ],
                                    [
                                        'name'         => $productName . " (₦{$min} - ₦{$max})",
                                        'category'     => 'ELECTRICITY',
                                        'api_price'    => 0,
                                        'client_price' => $clientPrice,
                                    ]
                                );
                            }
                        }
                    }
                }
            } elseif ($provider->api_identifier === 'clubkonnect-cable') {
                // Handle Cable TV Products
                $userId = env('CLUBKONNECT_USERID');
                $response = Http::get("https://www.nellobytesystems.com/APICableTVPackagesV2.asp", [
                    'UserID' => $userId
                ]);

                if ($response->ok()) {
                    $apiResponse = $response->json();
                    $clientPrice = $request->input('client_price');

                    if (!empty($apiResponse['TV_ID'])) {
                        foreach ($apiResponse['TV_ID'] as $companyName => $companyArray) {
                            foreach ($companyArray as $companyData) {
                                if (!empty($companyData['PRODUCT']) && is_array($companyData['PRODUCT'])) {
                                    foreach ($companyData['PRODUCT'] as $product) {
                                        $productCode = (string)($product['PACKAGE_ID'] ?? '');
                                        $productName = $product['PACKAGE_NAME'] ?? 'Unknown';
                                        $apiPrice    = (float)($product['PACKAGE_AMOUNT'] ?? 0);

                                        Product::updateOrCreate(
                                            [
                                                'provider_id'  => $provider->id,
                                                'product_code' => $productCode,
                                            ],
                                            [
                                                'name'         => $productName,
                                                'category'     => strtoupper($companyName), // e.g. DSTV, GOTV, STARTIMES
                                                'api_price'    => $apiPrice,
                                                'client_price' => $clientPrice,
                                            ]
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }elseif ($provider->api_identifier === 'Admin-otp' || $provider->api_identifier === 'otp') {
            // ✅ Handle OTP Provider Products
            $clientPrice = $request->input('client_price');

            Product::updateOrCreate(
                [
                    'provider_id'  => $provider->id,
                    'product_code' => 'otp-verification',
                ],
                [
                    'name'         => 'OTP Verification Service',
                    'category'     => 'OTP',
                    'api_price'    => 0,
                    'client_price' => $clientPrice,
                    'is_active'    => true,
                ]
            );
        } elseif ($provider->api_identifier === 'smsbus') {
            $clientPrices = $request->input('client_price', []);
            $conversionRate = $request->input('conversion_rate_smsbus', 1500.0);
            
            // Save conversion rate product for SMS-Bus
            Product::updateOrCreate(
                [
                    'provider_id' => $provider->id,
                    'product_code' => 'conversion_rate',
                ],
                [
                    'name' => 'SMS-Bus Conversion Rate (USD to NGN)',
                    'category' => 'CONVERSION_RATE',
                    'api_price' => 1.0,
                    'client_price' => $conversionRate,
                    'is_active' => true,
                ]
            );
            
            // First, update existing products with provided client prices
            foreach ($clientPrices as $productCode => $clientPrice) {
                Product::where('provider_id', $provider->id)
                    ->where('product_code', $productCode)
                    ->update([
                        'client_price' => $clientPrice,
                        'is_active'    => $provider->status === 'active',
                    ]);
            }
            // Then, fetch from API and create/update products
            $token = env('SMSBUS_TOKEN');
            $response = Http::timeout(30)->get("https://sms-bus.com/api/control/list/projects", [
                'token' => $token,
            ]);
            if ($response->ok()) {
                $apiServices = $response->json();
                foreach ($apiServices as $service) {
                    $productCode = (string)$service['id'];
                    $apiPrice = (float)$service['price'];
                    $name = $service['name'];
                    $calculatedClientPrice = $apiPrice * $conversionRate;
                    $clientPrice = $clientPrices[$productCode] ?? $calculatedClientPrice;
                    Product::updateOrCreate(
                        [
                            'provider_id' => $provider->id,
                            'product_code' => $productCode,
                        ],
                        [
                            'name' => $name,
                            'category' => 'OTP',
                            'api_price' => $apiPrice,
                            'client_price' => $clientPrice,
                            'is_active' => true,
                        ]
                    );
                }
            }
        } elseif ($provider->api_identifier === '5sim') {
            $clientPrices = $request->input('client_price', []);
            $conversionRate = $request->input('conversion_rate_5sim', 1600.0);
            
            // Save conversion rate product for 5sim
            Product::updateOrCreate(
                [
                    'provider_id' => $provider->id,
                    'product_code' => 'conversion_rate',
                ],
                [
                    'name' => '5sim Conversion Rate (RUB to NGN)',
                    'category' => 'CONVERSION_RATE',
                    'api_price' => 1.0,
                    'client_price' => $conversionRate,
                    'is_active' => true,
                ]
            );
            
            // First, update existing products with provided client prices
            foreach ($clientPrices as $productCode => $clientPrice) {
                Product::where('provider_id', $provider->id)
                    ->where('product_code', $productCode)
                    ->update([
                        'client_price' => $clientPrice,
                        'is_active'    => $provider->status === 'active',
                    ]);
            }
            // Then, fetch from API and create/update products
            $response = Http::get('https://5sim.net/v1/guest/products');
            if ($response->ok()) {
                $apiProducts = $response->json();
                foreach ($apiProducts as $productCode => $productData) {
                    $name = $productData['name'] ?? $productCode;
                    $minPrice = null;
                    if (isset($productData['countries'])) {
                        foreach ($productData['countries'] as $country => $countryData) {
                            if (isset($countryData['price'])) {
                                $price = (float)$countryData['price'];
                                if ($minPrice === null || $price < $minPrice) {
                                    $minPrice = $price;
                                }
                            }
                        }
                    }
                    if ($minPrice !== null) {
                        $apiPrice = $minPrice;
                        $calculatedClientPrice = $apiPrice * $conversionRate;
                        $clientPrice = $clientPrices[$productCode] ?? $calculatedClientPrice;
                        Product::updateOrCreate(
                            [
                                'provider_id' => $provider->id,
                                'product_code' => $productCode,
                            ],
                            [
                                'name' => $name,
                                'category' => 'OTP',
                                'api_price' => $apiPrice,
                                'client_price' => $clientPrice,
                                'is_active' => true,
                            ]
                        );
                    }
                }
            }
        } elseif ($provider->api_identifier === 'smsbower') {
            $clientPrices = $request->input('client_price', []);
            $conversionRate = $request->input('conversion_rate_smsbower', 1500.0);
            
            // Save conversion rate product for SMSBower
            Product::updateOrCreate(
                [
                    'provider_id' => $provider->id,
                    'product_code' => 'conversion_rate',
                ],
                [
                    'name' => 'SMSBower Conversion Rate (USD to NGN)',
                    'category' => 'CONVERSION_RATE',
                    'api_price' => 1.0,
                    'client_price' => $conversionRate,
                    'is_active' => true,
                ]
            );
            
            // First, update existing products with provided client prices
            foreach ($clientPrices as $productCode => $clientPrice) {
                Product::where('provider_id', $provider->id)
                    ->where('product_code', $productCode)
                    ->update([
                        'client_price' => $clientPrice,
                        'is_active'    => $provider->status === 'active',
                    ]);
            }
            // Then, fetch from API and create/update products
            $services = \App\Helpers\SmsBowerHelper::getServices();
            
            if (!empty($services)) {
                foreach ($services as $service) {
                    $serviceCode = $service['code'] ?? null;
                    $serviceName = $service['name'] ?? 'Unknown Service';
                    
                    if (!$serviceCode) continue;
                    
                    // Get prices for this service across all countries
                    $prices = \App\Helpers\SmsBowerHelper::getPrices($serviceCode);
                    $minPrice = null;
                    
                    if (is_array($prices)) {
                        foreach ($prices as $countryId => $countryPrices) {
                            if (is_array($countryPrices) && isset($countryPrices[$serviceCode])) {
                                $cost = (float)($countryPrices[$serviceCode]['cost'] ?? 0);
                                if ($cost > 0 && ($minPrice === null || $cost < $minPrice)) {
                                    $minPrice = $cost;
                                }
                            }
                        }
                    }
                    
                    if ($minPrice !== null && $minPrice > 0) {
                        $apiPrice = $minPrice;
                        $calculatedClientPrice = $apiPrice * $conversionRate;
                        $clientPrice = $clientPrices[$serviceCode] ?? $calculatedClientPrice;
                        
                        Product::updateOrCreate(
                            [
                                'provider_id' => $provider->id,
                                'product_code' => $serviceCode,
                            ],
                            [
                                'name' => $serviceName,
                                'category' => 'OTP',
                                'api_price' => $apiPrice,
                                'client_price' => $clientPrice,
                                'is_active' => true,
                            ]
                        );
                    }
                }
            }
        }

        return redirect()->back()->with('success', 'All plans saved successfully.');
    } catch (\Exception $e) {
        \Log::error('SaveProducts Error', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString()
        ]);

        return redirect()->back()->with('error', 'Failed to fetch and save products.');
    }
}


    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        // Soft-deactivate instead of hard delete so it can be reactivated later
        $product->is_active = false;
        $product->save();

        return redirect()->back()->with('success', 'Product deactivated successfully. You can reactivate it later.');
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update([
            'client_price' => $request->client_price
        ]);
        return back()->with('success', 'Product price updated.');
    }

    /**
     * Create or reactivate a single product (used when an API product is saved individually)
     */
    public function createProduct(Request $request, $providerId)
    {
        $request->validate([
            'product_code' => 'required|string',
            'name' => 'required|string',
            'api_price' => 'nullable|numeric',
            'client_price' => 'required|numeric',
        ]);

        $provider = Provider::findOrFail($providerId);

        $product = Product::updateOrCreate(
            [
                'provider_id' => $provider->id,
                'product_code' => $request->product_code,
            ],
            [
                'name' => $request->name,
                'category' => $request->category ?? 'UNKNOWN',
                'api_price' => $request->api_price ?? 0,
                'client_price' => $request->client_price,
                'is_active' => true,
            ]
        );

        return redirect()->back()->with('success', 'Product saved/reactivated successfully.');
    }

    protected function getLogsProvider()
    {
        $service = Service::firstOrCreate(
            ['slug' => 'logs'],
            ['name' => 'Logs', 'is_active' => true]
        );

        return Provider::firstOrCreate(
            ['code' => 'logs'],
            ['name' => 'Logs Service', 'service_id' => $service->id, 'api_identifier' => 'logs']
        );
    }

    public function logsIndex()
    {
        $provider = $this->getLogsProvider();
        $products = $provider->products()->orderByDesc('created_at')->get();
        return view('admin.logs', compact('products'));
    }

    public function createLogProduct(Request $request)
    {
        $productId = $request->product_id;
        
        // Different validation based on whether creating or updating
        if ($productId) {
            // Edit existing product - don't require log_details
            $request->validate([
                'name' => 'required|string|max:255',
                'client_price' => 'required|numeric|min:0.01',
                'country' => 'nullable|string|max:80',
                'country_code' => 'nullable|string|max:2',
                'description' => 'nullable|string|max:1000',
                'mode' => 'required|in:automatic,manual',
                'product_id' => 'required|integer',
            ]);
        } else {
            // Create new product - require mode and log_details for automatic
            $request->validate([
                'name' => 'required|string|max:255',
                'client_price' => 'required|numeric|min:0.01',
                'country' => 'nullable|string|max:80',
                'country_code' => 'nullable|string|max:2',
                'description' => 'nullable|string|max:1000',
                'mode' => 'required|in:automatic,manual',
                'log_details' => 'nullable|string',
            ]);
        }

        $provider = $this->getLogsProvider();
        $mode = $request->mode;
        $logDetails = null;

        // Only process log details if provided (for creation or manual mode edits)
        if (!$productId && $mode === 'automatic') {
            $rawDetails = $request->input('log_details');
            $decoded = [];
            if (!empty($rawDetails)) {
                $decoded = json_decode($rawDetails, true);
            }

            if (!is_array($decoded) || empty($decoded)) {
                return redirect()->back()->with('error', 'Automatic logs require at least one log detail.');
            }

            $filtered = array_values(array_filter(array_map(function ($item) {
                return [
                    'username' => trim($item['username'] ?? ''),
                    'password' => trim($item['password'] ?? ''),
                    'extra' => trim($item['extra'] ?? ''),
                    'sold' => false,
                    'sold_at' => null,
                    'sold_transaction_id' => null,
                    'sold_to_user_id' => null,
                ];
            }, $decoded), function ($item) {
                return is_array($item) && (
                    $item['sold'] || trim($item['username']) !== '' || trim($item['password']) !== '' || trim($item['extra']) !== ''
                );
            }));

            if (empty($filtered)) {
                return redirect()->back()->with('error', 'Automatic logs require at least one valid log detail entry.');
            }

            $logDetails = json_encode($filtered);
        }

        $sharedData = [
            'name' => $request->name,
            'category' => $request->country ?: 'Global',
            'client_price' => $request->client_price,
            'country' => $request->country,
            'country_code' => strtoupper($request->country_code),
            'description' => $request->description,
            'mode' => $mode,
        ];
        
        // Only update log_details if provided
        if ($logDetails !== null) {
            $sharedData['log_details'] = $logDetails;
        }

        if ($productId) {
            $product = Product::findOrFail($productId);
            $product->update($sharedData);
            return redirect()->back()->with('success', 'Logs product updated successfully.');
        }

        $productCode = Str::slug($request->name, '-') . '-' . strtoupper(Str::random(4));

        Product::create(array_merge([
            'provider_id' => $provider->id,
            'product_code' => $productCode,
            'api_price' => $request->client_price,
            'is_active' => true,
        ], $sharedData));

        return redirect()->back()->with('success', 'Logs product created successfully.');
    }

    public function updateLogDetails(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if ($product->mode !== 'automatic') {
            return redirect()->back()->with('error', 'Only automatic logs products can save log details.');
        }

        $request->validate([
            'log_details' => 'required|array',
            'log_details.*.username' => 'nullable|string|max:255',
            'log_details.*.password' => 'nullable|string|max:255',
            'log_details.*.extra' => 'nullable|string|max:1000',
            'log_details.*.sold' => 'nullable|boolean',
            'log_details.*.sold_at' => 'nullable|string|max:50',
            'log_details.*.sold_transaction_id' => 'nullable|integer',
            'log_details.*.sold_to_user_id' => 'nullable|integer',
        ]);

        $filtered = [];
        foreach ($request->log_details as $item) {
            $sold = filter_var($item['sold'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $detail = [
                'username' => trim($item['username'] ?? ''),
                'password' => trim($item['password'] ?? ''),
                'extra' => trim($item['extra'] ?? ''),
                'sold' => $sold,
                'sold_at' => $item['sold_at'] ?? null,
                'sold_transaction_id' => $item['sold_transaction_id'] ?? null,
                'sold_to_user_id' => $item['sold_to_user_id'] ?? null,
            ];

            if ($sold || $detail['username'] !== '' || $detail['password'] !== '' || $detail['extra'] !== '') {
                $filtered[] = $detail;
            }
        }

        if (empty($filtered)) {
            return redirect()->back()->with('error', 'Please add at least one log detail entry.');
        }

        $product->update(['log_details' => json_encode(array_values($filtered))]);

        return redirect()->back()->with('success', 'Log details updated successfully.');
    }

    public function logsOrders()
    {
        $transactions = Transaction::where('type', 'logs')
            ->latest()
            ->paginate(15);
        return view('admin.logs-orders', compact('transactions'));
    }

    public function fulfillLogOrder(Request $request, $id)
    {
        $request->validate([
            'login_details' => 'required|array',
            'login_details.*' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $transaction = Transaction::where('type', 'logs')->findOrFail($id);
        $responseData = is_array($transaction->response)
            ? $transaction->response
            : (json_decode($transaction->response ?? '[]', true) ?: []);

        $quantity = max(1, intval($responseData['quantity'] ?? count($responseData['credentials'] ?? []) ?: 1));
        $existingCredentials = is_array($responseData['credentials']) ? array_values($responseData['credentials']) : [];
        $credentials = array_pad($existingCredentials, $quantity, '');

        foreach ($request->login_details as $index => $loginDetails) {
            if (!is_null($loginDetails) && trim($loginDetails) !== '') {
                $credentials[$index] = trim($loginDetails);
            }
        }

        $filledCount = count(array_filter($credentials, function ($value) {
            return trim((string)$value) !== '';
        }));

        if ($filledCount === 0 && count(array_filter($existingCredentials, function ($value) {
            return trim((string)$value) !== '';
        })) === 0) {
            return redirect()->back()->with('error', 'Please provide at least one login detail before submitting.');
        }

        $responseData['credentials'] = $credentials;
        $responseData['fulfilled_count'] = $filledCount;
        $responseData['notes'] = $request->notes;
        $responseData['login_details'] = implode("\n\n", array_filter(array_map(function ($value) {
            return trim((string)$value) !== '' ? trim((string)$value) : null;
        }, $credentials)));

        if ($filledCount === $quantity) {
            $transaction->update([
                'status' => 'success',
                'response' => $responseData,
                'description' => 'Logs order fulfilled by admin',
            ]);

            return redirect()->back()->with('success', 'Logs order fulfilled successfully.');
        }

        $transaction->update([
            'status' => 'pending',
            'response' => $responseData,
            'description' => 'Logs order partially fulfilled - awaiting remaining credentials',
        ]);

        return redirect()->back()->with('success', "Saved ${filledCount} of ${quantity} login detail sets. Order remains pending until all entries are completed.");
    }

    /**
     * Toggle product active status (reactivate/deactivate)
     */
    public function toggleActive($id)
    {
        $product = Product::findOrFail($id);
        $product->is_active = !$product->is_active;
        $product->save();

        $message = $product->is_active ? 'Product reactivated successfully.' : 'Product deactivated successfully.';
        return redirect()->back()->with('success', $message);
    }

    public function toggleProvider($providerId)
    {
        $provider = Provider::findOrFail($providerId);
        $provider->status = $provider->status === 'active' ? 'inactive' : 'active';
        $provider->save();

        $isActive = $provider->status === 'active';

        foreach ($provider->products as $product) {
            $product->is_active = $isActive;
            $product->save();
        }

        $message = $provider->status === 'active' 
            ? $provider->name . ' activated successfully.' 
            : $provider->name . ' deactivated successfully.';
        return redirect()->back()->with('success', $message);
    }

    public function applyMarkup(Request $request, $providerId)
    {
        $provider = Provider::findOrFail($providerId);
        $markup = $request->markup;
        $type = $request->type;

        foreach ($provider->products as $product) {
            $newPrice = $type === 'percent'
                ? $product->api_price + ($product->api_price * $markup / 100)
                : $product->api_price + $markup;

            $product->update(['client_price' => $newPrice]);
        }

        return back()->with('success', 'Markup applied to all plans.');
    }

     /**
     * Display all eSIM transactions
     */
    public function esimindex(Request $request)
    {
        // Get only eSIM transactions using the 'type' column
        $transactions = Transaction::where('type', 'esim')
            ->latest()
            ->paginate(15);
        // dd($transactions); // removed for production
        return view('admin.esim', compact('transactions'));
    }

    /**
     * Show transaction details as JSON (for modal).
     */
    public function show($id)
    {
        $txn = Transaction::with('user', 'esimUpload')->findOrFail($id);
    
        $esimData = null;
    
        if ($txn->esimUpload) {
            $upload = $txn->esimUpload;
    
            // Normalise the images array into full public URLs
            $rawImages = $upload->images;
            if (is_string($rawImages)) {
                $rawImages = json_decode($rawImages, true) ?? [];
            }
            $rawImages = is_array($rawImages) ? $rawImages : [];
    
            $imageUrls = [];
            foreach ($rawImages as $img) {
                if (empty($img)) continue;
    
                // Normalise slashes (DB may have stored with escaped \/)
                $img = str_replace(['\\/', '\\'], ['/', '/'], $img);
                $img = ltrim($img, '/');
    
                if (str_starts_with($img, 'storage/')) {
                    // "storage/esim/filename.png"  →  strip "storage/" prefix
                    $relativePath = substr($img, strlen('storage/'));
                    $imageUrls[] = Storage::disk('public')->url($relativePath);
                } elseif (str_starts_with($img, 'uploads/')) {
                    // legacy "uploads/esim/filename.png"  →  public URL via asset()
                    $imageUrls[] = asset($img);
                } else {
                    // fallback: treat as a storage-disk relative path
                    $imageUrls[] = Storage::disk('public')->url($img);
                }
            }
    
            $esimData = [
                'id'       => $upload->id,
                'number'   => $upload->number,
                'details'  => $upload->details,
                'images'   => $imageUrls,   // always full URLs
            ];
        }
    
        return response()->json([
            'transaction' => [
                'id'        => $txn->id,
                'username'  => $txn->user->username ?? ($txn->user->name ?? 'N/A'),
                'email'     => $txn->user->email ?? 'N/A',
                'amount'    => $txn->amount,
                'status'    => $txn->status,
                'reference' => $txn->reference,
                'recipient' => $txn->recipient,
            ],
            'esim' => $esimData,
        ]);
    }
 
// ---------------------------------------------------------------
// upload() — stores images to storage/app/public/esim only
// ---------------------------------------------------------------
public function upload(Request $request)
{
    $request->validate([
        'transaction_id' => 'required|exists:transactions,id',
        'esim_upload_id' => 'nullable|exists:esim_uploads,id',
        'numbers'        => 'nullable|string',
        'details'        => 'nullable|string',
        'images.*'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
    ]);
 
    try {
        DB::beginTransaction();
 
        $transaction = Transaction::findOrFail($request->transaction_id);
 
        // Find existing upload record
        $esimUpload = null;
        if ($request->esim_upload_id) {
            $esimUpload = EsimUpload::findOrFail($request->esim_upload_id);
        } else {
            $esimUpload = EsimUpload::where('transaction_id', $transaction->id)->first();
        }
 
        $data = [
            'user_id'        => $transaction->user_id,
            'transaction_id' => $transaction->id,
        ];
 
        if ($request->filled('numbers')) {
            $data['number'] = $request->numbers;
        }
        if ($request->filled('details')) {
            $data['details'] = $request->details;
        }
 
        // Merge with any existing images already in DB
        $existingImages = [];
        if ($esimUpload && !empty($esimUpload->images)) {
            $existingImages = is_array($esimUpload->images)
                ? $esimUpload->images
                : (json_decode($esimUpload->images, true) ?? []);
        }
 
        if ($request->hasFile('images')) {
            $newImages = [];
 
            foreach ($request->file('images') as $image) {
                $filename   = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                // Store under storage/app/public/esim/
                Storage::disk('public')->putFileAs('esim', $image, $filename);
                // Save as "storage/esim/filename" — a normalised, storage-relative key
                $newImages[] = 'storage/esim/' . $filename;
            }
 
            $data['images'] = array_merge($existingImages, $newImages);
 
            Log::info('eSIM images saved', [
                'new'   => count($newImages),
                'total' => count($data['images']),
            ]);
        }
 
        if ($esimUpload) {
            $esimUpload->update($data);
            $action = 'updated';
        } else {
            $esimUpload = EsimUpload::create($data);
            $action     = 'created';
        }
 
        // Mark transaction successful
        $transaction->status = 'Success';
        $transaction->save();
 
        DB::commit();
 
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => "eSIM details {$action} successfully!",
                'data'    => ['id' => $esimUpload->id, 'action' => $action],
            ]);
        }
 
        return redirect()->back()->with('success', "eSIM details {$action} successfully!");
 
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('eSIM upload error', [
            'transaction_id' => $request->transaction_id,
            'error'          => $e->getMessage(),
            'trace'          => $e->getTraceAsString(),
        ]);
 
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save eSIM details: ' . $e->getMessage(),
            ], 422);
        }
 
        return redirect()->back()->with('error', 'Failed to save eSIM details: ' . $e->getMessage());
    }
}

    /**
     * Handle admin upload (numbers, details, images).
     */
    /**
 * Upload or Update eSIM details for a transaction
 */
// public function upload(Request $request)
// {
//     $request->validate([
//         'transaction_id' => 'required|exists:transactions,id',
//         'esim_upload_id' => 'nullable|exists:esim_uploads,id',
//         'numbers' => 'nullable|string',
//         'details' => 'nullable|string',
//         'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max per image
//     ]);

//     try {
//         DB::beginTransaction();

//         $transaction = \App\Models\Transaction::findOrFail($request->transaction_id);
        
//         // Check if we're updating or creating
//         $esimUpload = null;
//         if ($request->esim_upload_id) {
//             $esimUpload = \App\Models\EsimUpload::findOrFail($request->esim_upload_id);
//         } else {
//             $esimUpload = \App\Models\EsimUpload::where('transaction_id', $transaction->id)->first();
//         }

//         // Prepare data for update/create
//         $data = [
//             'user_id' => $transaction->user_id,
//             'transaction_id' => $transaction->id,
//         ];

//         // Only update fields that were provided
//         if ($request->filled('numbers')) {
//             $data['number'] = $request->numbers;
//         }

//         if ($request->filled('details')) {
//             $data['details'] = $request->details;
//         }

//         // Handle image uploads
//         $existingImages = [];
//         if ($esimUpload && $esimUpload->images) {
//             // Get existing images
//             $existingImages = is_array($esimUpload->images) 
//                 ? $esimUpload->images 
//                 : json_decode($esimUpload->images, true) ?? [];
//         }

//         if ($request->hasFile('images')) {
//             $newImages = [];
            
//             foreach ($request->file('images') as $image) {
//                 try {
//                     // Generate unique filename
//                     $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    
//                     // Store in storage/app/public/esim/ using the public disk
//                     $storedPath = Storage::disk('public')->putFileAs('esim', $image, $filename);
                    
//                     // Store path as: storage/esim/filename.jpg for retrieval via getImageUrlsAttribute
//                     $newImages[] = 'storage/esim/' . $filename;
                    
//                     Log::info('eSIM Image uploaded successfully', [
//                         'filename' => $filename,
//                         'stored_path' => $storedPath,
//                         'display_path' => 'storage/esim/' . $filename
//                     ]);
//                 } catch (\Exception $e) {
//                     Log::error('Error uploading eSIM image', [
//                         'error' => $e->getMessage(),
//                         'trace' => $e->getTraceAsString()
//                     ]);
//                 }
//             }

//             // Merge existing and new images
//             $allImages = array_merge($existingImages, $newImages);
//             $data['images'] = $allImages;
            
//             Log::info('eSIM Images stored', [
//                 'existing_count' => count($existingImages),
//                 'new_count' => count($newImages),
//                 'total_count' => count($allImages)
//             ]);
//         }

//         // Update or create the eSIM upload record
//         if ($esimUpload) {
//             // Update existing record (only update provided fields)
//             $esimUpload->update($data);
//             $action = 'updated';
//         } else {
//             // Create new record
//             $esimUpload = EsimUpload::create($data);
//             $action = 'created';
//         }

//         // Mark the parent transaction as successful now that eSIM details (and files) are attached
//         try {
//             $transaction->status = 'Success';
//             $transaction->save();
//         } catch (\Exception $e) {
//             Log::error('Failed to update transaction status after eSIM upload', [
//                 'transaction_id' => $transaction->id,
//                 'error' => $e->getMessage()
//             ]);
//         }

//         DB::commit();

//         Log::info("eSIM details {$action}", [
//             'esim_upload_id' => $esimUpload->id,
//             'transaction_id' => $transaction->id,
//             'user_id' => $transaction->user_id,
//             'has_images' => $request->hasFile('images'),
//             'image_count' => $request->hasFile('images') ? count($request->file('images')) : 0
//         ]);

//         // Return response based on request type
//         if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
//             return response()->json([
//                 'success' => true,
//                 'message' => "eSIM details {$action} successfully!",
//                 'data' => [
//                     'id' => $esimUpload->id,
//                     'transaction_id' => $esimUpload->transaction_id,
//                     'action' => $action
//                 ]
//             ]);
//         }

//         return redirect()->back()->with('success', "eSIM details {$action} successfully!");

//     } catch (\Exception $e) {
//         DB::rollBack();
        
//         Log::error('Error uploading eSIM details', [
//             'transaction_id' => $request->transaction_id,
//             'error' => $e->getMessage(),
//             'trace' => $e->getTraceAsString()
//         ]);

//         // Return error response based on request type
//         if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Failed to save eSIM details: ' . $e->getMessage()
//             ], 422);
//         }

//         return redirect()->back()->with('error', 'Failed to save eSIM details: ' . $e->getMessage());
//     }
// }

    private function getExchangeRate(): float
    {
        $cacheKey = 'usd_to_ngn_rate';
        $rate = Cache::get($cacheKey);
        if (!$rate) {
            try {
                $response = Http::timeout(10)->get('https://api.exchangerate-api.com/v4/latest/USD');
                if ($response->ok()) {
                    $data = $response->json();
                    $rate = $data['rates']['NGN'] ?? 1600.0;
                    Cache::put($cacheKey, $rate, now()->addMinutes(60));
                } else {
                    $rate = 1600.0;
                }
            } catch (\Exception $e) {
                Log::error('Exchange rate fetch failed', ['error' => $e->getMessage()]);
                $rate = 1600.0;
            }
        }
        return $rate;
    }

    private function get5simExchangeRate(): float
    {
        $cacheKey = 'rub_to_ngn_rate';
        $rate = Cache::get($cacheKey);
        if (!$rate) {
            try {
                $response = Http::timeout(10)->get('https://api.exchangerate-api.com/v4/latest/RUB');
                if ($response->ok()) {
                    $data = $response->json();
                    $rate = $data['rates']['NGN'] ?? 20.0;
                    Cache::put($cacheKey, $rate, now()->addMinutes(60));
                } else {
                    $rate = 20.0;
                }
            } catch (\Exception $e) {
                Log::error('RUB Exchange rate fetch failed', ['error' => $e->getMessage()]);
                $rate = 20.0;
            }
        }
        return $rate;
    }
}
