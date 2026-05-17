<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MoreThanPanelHelper
{
    private $apiUrl;
    private $apiKey;
    // Cache to avoid repeated DB queries for provider and products
    private $providerCache = null;
    private $productMapCache = null; // associative array: lower(product_code) => product row

    public function __construct()
    {
        $this->apiUrl = config('services.morethanpanel.api_url', 'https://morethanpanel.com/api/v2');
        $this->apiKey = config('services.morethanpanel.api_key', env('MORETHANPANEL_API_KEY'));
        
        if (empty($this->apiKey)) {
            Log::error('MoreThanPanel API key is not configured');
        }
    }

    /**
     * Make API request to MoreThanPanel
     */
    private function makeRequest($action, $data = [])
    {
        try {
            if (empty($this->apiKey)) {
                Log::error('MoreThanPanel API key missing');
                return null;
            }

            $params = array_merge([
                'key' => $this->apiKey,
                'action' => $action,
            ], $data);

            Log::info('Making MoreThanPanel API request', [
                'action' => $action,
                'params' => array_diff_key($params, ['key' => '']) // Log without API key
            ]);

            $response = Http::asForm()
                ->timeout(30)
                ->retry(2, 100)
                ->post($this->apiUrl, $params);

            if ($response->successful()) {
                $result = $response->json();
                
                // Log response structure for debugging (removed for production)
                Log::info('MoreThanPanel API response received', [
                    'action' => $action,
                    'type' => gettype($result),
                    'is_array' => is_array($result),
                    'has_error' => isset($result['error'])
                ]);
                
                // Check for API error response
                if (is_array($result) && isset($result['error'])) {
                    Log::error('MoreThanPanel API Error', [
                        'action' => $action,
                        'error' => $result['error']
                    ]);
                }
                
                return $result;
            }

            Log::error('MoreThanPanel API HTTP Error', [
                'action' => $action,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('MoreThanPanel API Exception: ' . $e->getMessage(), [
                'action' => $action,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get all available services
     */
    public function getServices()
    {
        $services = $this->makeRequest('services');
        
        // Validate response
        if (!is_array($services)) {
            Log::error('Invalid services response format', [
                'type' => gettype($services),
                'value' => $services
            ]);
            return [];
        }
        
        // Filter out services with invalid data
        $validServices = array_filter($services, function($service) {
            $isValid = isset($service['service']) && 
                       isset($service['name']) && 
                       isset($service['rate']) &&
                       isset($service['min']) &&
                       isset($service['max']) &&
                       isset($service['category']);
            
            if (!$isValid) {
                Log::warning('Filtering out invalid service', ['service' => $service]);
            }
            
            return $isValid;
        });
        
        Log::info('Services filtered', [
            'total' => count($services),
            'valid' => count($validServices)
        ]);
        
        return array_values($validServices); // Re-index array
    }

    /**
     * Place a new order
     */
    public function placeOrder($orderData)
    {
        // Validate required fields
        if (!isset($orderData['service']) || !isset($orderData['link']) || !isset($orderData['quantity'])) {
            Log::error('MTP placeOrder: Missing required fields', ['data' => $orderData]);
            return ['error' => 'Missing required order fields'];
        }

        return $this->makeRequest('add', $orderData);
    }

    /**
     * Get order status
     */
    public function getOrderStatus($orderId)
    {
        if (empty($orderId)) {
            return null;
        }
        
        return $this->makeRequest('status', ['order' => $orderId]);
    }

    /**
     * Get multiple orders status
     */
    public function getMultipleOrdersStatus($orderIds)
    {
        $orders = is_array($orderIds) ? implode(',', $orderIds) : $orderIds;
        return $this->makeRequest('status', ['orders' => $orders]);
    }

    /**
     * Create refill for an order
     */
    public function createRefill($orderId)
    {
        return $this->makeRequest('refill', ['order' => $orderId]);
    }

    /**
     * Create multiple refills
     */
    public function createMultipleRefills($orderIds)
    {
        $orders = is_array($orderIds) ? implode(',', $orderIds) : $orderIds;
        return $this->makeRequest('refill', ['orders' => $orders]);
    }

    /**
     * Get refill status
     */
    public function getRefillStatus($refillId)
    {
        return $this->makeRequest('refill_status', ['refill' => $refillId]);
    }

    /**
     * Get multiple refills status
     */
    public function getMultipleRefillsStatus($refillIds)
    {
        $refills = is_array($refillIds) ? implode(',', $refillIds) : $refillIds;
        return $this->makeRequest('refill_status', ['refills' => $refills]);
    }

    /**
     * Cancel orders
     */
    public function cancelOrders($orderIds)
    {
        $orders = is_array($orderIds) ? implode(',', $orderIds) : $orderIds;
        return $this->makeRequest('cancel', ['orders' => $orders]);
    }

    /**
     * Get account balance
     */
    public function getBalance()
    {
        return $this->makeRequest('balance');
    }

    /**
     * Get product client price by service code (product_code)
     * Returns the per-unit NGN price from the products table
     */
    public function getProductClientPrice($serviceCode)
    {
        try {
            // Delegate to the richer pricing helper
            $pricing = $this->getProductPricing($serviceCode);
            return $pricing['client_price'] ?? null;

        } catch (\Exception $e) {
            Log::error('Error fetching product client price', [
                'service_code' => $serviceCode,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get product pricing and provider percentage by service code.
     * Returns array: ['client_price' => float|null, 'provider_percentage' => int|null, 'provider_id' => int|null]
     */
    public function getProductPricing($serviceCode)
    {
        try {
            // Load provider and product map into cache if not already loaded
            if ($this->providerCache === null || $this->productMapCache === null) {
                $provider = DB::table('providers')
                    ->where('api_identifier', 'morethanpanel')
                    ->orWhere('name', 'Morethanpanel')
                    ->first();

                if (!$provider) {
                    Log::warning('MoreThanPanel provider not found in database');
                    $this->providerCache = null;
                    $this->productMapCache = [];
                    return ['client_price' => null, 'provider_percentage' => null, 'provider_id' => null];
                }

                $this->providerCache = $provider;

                // Load all active products for this provider and map by lowercased product_code
                $products = DB::table('products')
                    ->where('provider_id', $provider->id)
                    ->where('is_active', 1)
                    ->get();

                $map = [];
                foreach ($products as $p) {
                    $code = strtolower($p->product_code ?? '');
                    if ($code !== '') {
                        $map[$code] = $p;
                    }
                }

                $this->productMapCache = $map;
            }

            $provider = $this->providerCache;

            $lookupCode = strtolower($serviceCode ?? '');
            $product = $this->productMapCache[$lookupCode] ?? null;

            if ($product) {
                return [
                    'client_price' => $product->client_price !== null ? floatval($product->client_price) : null,
                    'provider_percentage' => intval($provider->percentage),
                    'provider_id' => $provider->id,
                ];
            }

            // Product not found in map
            Log::info('Product not found for service code: ' . $serviceCode);
            return ['client_price' => null, 'provider_percentage' => intval($provider->percentage), 'provider_id' => $provider->id];

        } catch (\Exception $e) {
            Log::error('Error fetching product pricing', [
                'service_code' => $serviceCode,
                'error' => $e->getMessage()
            ]);
            return ['client_price' => null, 'provider_percentage' => null, 'provider_id' => null];
        }
    }

    /**
     * Calculate order cost using product-level client_price
     * If client_price exists, use it directly; otherwise fall back to API rate with exchange
     */
    public function calculateCost($rateUsd, $quantity, $exchangeRate = 1500, $serviceCode = null)
    {
        // Try to get product-level client_price first
        $clientPricePerUnit = null;
        $providerPercentage = 0;
        
        if (!empty($serviceCode)) {
            $pricing = $this->getProductPricing($serviceCode);
            $clientPricePerUnit = $pricing['client_price'] ?? null;
            $providerPercentage = isset($pricing['provider_percentage']) ? intval($pricing['provider_percentage']) : 0;
        }

        // If we have a product client_price, use it and apply provider percentage as markup
        if ($clientPricePerUnit !== null && $clientPricePerUnit > 0) {
            $markupMultiplier = 1 + ($providerPercentage / 100);
            $finalPerUnit = round($clientPricePerUnit * $markupMultiplier, 2);
            $costLocal = round($finalPerUnit * $quantity, 2);

            // For reference, also calculate what the USD cost would be
            $costUsd = round($costLocal / $exchangeRate, 2);

            return [
                'cost_usd' => $costUsd,
                'cost_local' => $costLocal,
                'cost_local_per_unit' => round($finalPerUnit, 2),
                'percentage_applied' => $providerPercentage,
                'source' => 'product_client_price',
            ];
        }

        // Fallback: Calculate from API rate (for services without product entry)
        $costUsdPerUnit = floatval($rateUsd) / 1000;
        $costUsd = round($costUsdPerUnit * $quantity, 2);
        $costLocalPerUnit = round($costUsdPerUnit * $exchangeRate, 4);
        $costLocal = round($costLocalPerUnit * $quantity, 2);

        return [
            'cost_usd' => $costUsd,
            'cost_local' => $costLocal,
            'cost_local_per_unit' => $costLocalPerUnit,
            'percentage_applied' => 0,
            'source' => 'api_rate_with_exchange',
        ];
    }

    /**
     * Validate service parameters
     */
    public function validateOrderParams($service, $quantity)
    {
        $min = intval($service['min']);
        $max = intval($service['max']);
        
        if ($quantity < $min) {
            return [
                'valid' => false, 
                'message' => "Minimum quantity is " . number_format($min)
            ];
        }

        if ($quantity > $max) {
            return [
                'valid' => false, 
                'message' => "Maximum quantity is " . number_format($max)
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get service logo based on category/type
     */
    public function getServiceLogo($category, $serviceName = '')
    {
        $category = strtolower($category);
        $serviceName = strtolower($serviceName);
        
        // Define logos for common platforms
        $logoMap = [
            'instagram' => '📸',
            'facebook' => '📘',
            'twitter' => '🐦',
            'x.com' => '❌',
            'tiktok' => '🎵',
            'youtube' => '▶️',
            'telegram' => '✈️',
            'linkedin' => '💼',
            'snapchat' => '👻',
            'pinterest' => '📌',
            'spotify' => '🎧',
            'soundcloud' => '🔊',
            'twitch' => '🎮',
            'reddit' => '🤖',
            'discord' => '💬',
            'whatsapp' => '💚',
            'threads' => '🧵',
            'clubhouse' => '🎙️',
        ];

        // Check category or service name for platform
        foreach ($logoMap as $platform => $logo) {
            if (str_contains($category, $platform) || str_contains($serviceName, $platform)) {
                return $logo;
            }
        }

        return '🚀'; // Default logo
    }

    /**
     * Map API status to your local status
     */
    public function mapStatus($apiStatus): string
    {
        $apiStatus = trim(strtolower($apiStatus));

        $statusMap = [
            'pending'     => 'pending',
            'in progress' => 'processing',
            'processing'  => 'processing',
            'completed'   => 'success',    // ← was 'completed', now 'success'
            'complete'    => 'success',
            'partial'     => 'partial',
            'canceled'    => 'failed',
            'cancelled'   => 'failed',
            'refunded'    => 'refunded',
            'failed'      => 'failed',
        ];

        return $statusMap[$apiStatus] ?? 'pending';
    }

    /**
     * Format service data for display
     */
    public function formatServiceForDisplay($service, $exchangeRate = 1500)
    {
        // Calculate cost for 1000 units (standard rate) — pass service id for product-level client_price lookup
        $serviceId = $service['service'] ?? null;
        $costs = $this->calculateCost($service['rate'], 1000, $exchangeRate, $serviceId);

        $category = $service['category'] ?? 'Other';

        return [
            'service_id' => $service['service'],
            'name' => $service['name'],
            'type' => $service['type'] ?? 'General',
            'category' => $category,
            'rate_usd' => floatval($service['rate']),
            'rate_local' => $costs['cost_local'], // Cost per 1000 units in local currency (includes markup)
            'rate_local_per_unit' => $costs['cost_local_per_unit'],
            'client_percentage' => $costs['percentage_applied'],
            'min' => intval($service['min']),
            'max' => intval($service['max']),
            'refill' => $service['refill'] ?? false,
            'cancel' => $service['cancel'] ?? false,
            'logo' => $this->getServiceLogo($category, $service['name']),
            'description' => $service['description'] ?? null,
        ];
    }
}