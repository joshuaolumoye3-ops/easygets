<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FiveSmmHelper
{
    private $apiUrl;
    private $apiKey;
    private $providerCache = null;
    private $productMapCache = null;

    public function __construct()
    {
        $this->apiUrl = config('services.fivesmm.api_url', 'https://5smm.com/api/v2');
        $this->apiKey = config('services.fivesmm.api_key', env('FIVESMM_API_KEY'));

        if (empty($this->apiKey)) {
            Log::error('5SMM API key is not configured');
        }
    }

    private function makeRequest($action, $data = [])
    {
        try {
            if (empty($this->apiKey)) {
                Log::error('5SMM API key missing');
                return null;
            }

            $params = array_merge(['key' => $this->apiKey, 'action' => $action], $data);

            $response = Http::asForm()->timeout(30)->retry(2, 100)->post($this->apiUrl, $params);

            if ($response->successful()) {
                $result = $response->json();
                if (is_array($result) && isset($result['error'])) {
                    Log::error('5SMM API Error', ['action' => $action, 'error' => $result['error']]);
                }
                return $result;
            }

            Log::error('5SMM API HTTP Error', [
                'action' => $action,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('5SMM API Exception: ' . $e->getMessage(), ['action' => $action, 'data' => $data]);
            return null;
        }
    }

    public function getServices()
    {
        $services = $this->makeRequest('services');

        if (!is_array($services)) {
            Log::error('Invalid 5SMM services response', ['type' => gettype($services)]);
            return [];
        }

        $valid = array_filter($services, function ($s) {
            return isset($s['service'], $s['name'], $s['rate'], $s['min'], $s['max'], $s['category']);
        });

        return array_values($valid);
    }

    public function placeOrder($orderData)
    {
        if (!isset($orderData['service'], $orderData['link'], $orderData['quantity'])) {
            Log::error('5SMM placeOrder: Missing required fields', ['data' => $orderData]);
            return ['error' => 'Missing required order fields'];
        }

        return $this->makeRequest('add', $orderData);
    }

    public function getOrderStatus($orderId)
    {
        if (empty($orderId)) return null;
        return $this->makeRequest('status', ['order' => $orderId]);
    }

    public function createRefill($orderId)
    {
        return $this->makeRequest('refill', ['order' => $orderId]);
    }

    public function cancelOrders($orderIds)
    {
        $orders = is_array($orderIds) ? implode(',', $orderIds) : $orderIds;
        return $this->makeRequest('cancel', ['orders' => $orders]);
    }

    public function getBalance()
    {
        return $this->makeRequest('balance');
    }

    public function getProductPricing($serviceCode)
    {
        try {
            if ($this->providerCache === null || $this->productMapCache === null) {
                $provider = DB::table('providers')->where('api_identifier', 'fivesmm')->first();

                if (!$provider) {
                    $this->providerCache   = null;
                    $this->productMapCache = [];
                    return ['client_price' => null, 'provider_percentage' => null, 'provider_id' => null];
                }

                $this->providerCache = $provider;

                $products = DB::table('products')
                    ->where('provider_id', $provider->id)
                    ->where('is_active', 1)
                    ->get();

                $map = [];
                foreach ($products as $p) {
                    $code = strtolower($p->product_code ?? '');
                    if ($code !== '') $map[$code] = $p;
                }
                $this->productMapCache = $map;
            }

            $provider   = $this->providerCache;
            $lookupCode = strtolower($serviceCode ?? '');
            $product    = $this->productMapCache[$lookupCode] ?? null;

            if ($product) {
                return [
                    'client_price'        => $product->client_price !== null ? floatval($product->client_price) : null,
                    'provider_percentage' => intval($provider->percentage),
                    'provider_id'         => $provider->id,
                ];
            }

            return ['client_price' => null, 'provider_percentage' => intval($provider->percentage), 'provider_id' => $provider->id];
        } catch (\Exception $e) {
            Log::error('5SMM getProductPricing error', ['service_code' => $serviceCode, 'error' => $e->getMessage()]);
            return ['client_price' => null, 'provider_percentage' => null, 'provider_id' => null];
        }
    }

    public function calculateCost($rateUsd, $quantity, $exchangeRate = 1500, $serviceCode = null, $adminPercentage = 0)
    {
        $clientPricePerUnit = null;
        $providerPercentage = 0;

        if (!empty($serviceCode)) {
            $pricing            = $this->getProductPricing($serviceCode);
            $clientPricePerUnit = $pricing['client_price'] ?? null;
            $providerPercentage = isset($pricing['provider_percentage']) ? intval($pricing['provider_percentage']) : 0;
        }

        if ($clientPricePerUnit !== null && $clientPricePerUnit > 0) {
            $finalPerUnit = round($clientPricePerUnit * (1 + $providerPercentage / 100), 2);
            $costLocal    = round($finalPerUnit * $quantity, 2);

            if ($adminPercentage > 0) {
                $mult                = 1 + ($adminPercentage / 100);
                $finalPerUnit        = round($finalPerUnit * $mult, 2);
                $costLocal           = round($costLocal * $mult, 2);
                $providerPercentage += $adminPercentage;
            }

            return [
                'cost_usd'            => round($costLocal / $exchangeRate, 2),
                'cost_local'          => $costLocal,
                'cost_local_per_unit' => round($finalPerUnit, 2),
                'percentage_applied'  => $providerPercentage,
                'source'              => 'product_client_price',
            ];
        }

        $costUsdPerUnit   = floatval($rateUsd) / 1000;
        $costUsd          = round($costUsdPerUnit * $quantity, 2);
        $costLocalPerUnit = round($costUsdPerUnit * $exchangeRate, 4);
        $costLocal        = round($costLocalPerUnit * $quantity, 2);

        if ($adminPercentage > 0) {
            $mult             = 1 + ($adminPercentage / 100);
            $costLocalPerUnit = round($costLocalPerUnit * $mult, 4);
            $costLocal        = round($costLocal * $mult, 2);
            $costUsd          = round($costLocal / $exchangeRate, 2);
        }

        return [
            'cost_usd'            => $costUsd,
            'cost_local'          => $costLocal,
            'cost_local_per_unit' => $costLocalPerUnit,
            'percentage_applied'  => $adminPercentage,
            'source'              => $adminPercentage > 0 ? 'api_rate_with_exchange_with_admin_markup' : 'api_rate_with_exchange',
        ];
    }

    public function validateOrderParams($service, $quantity)
    {
        $min = intval($service['min']);
        $max = intval($service['max']);

        if ($quantity < $min) return ['valid' => false, 'message' => 'Minimum quantity is ' . number_format($min)];
        if ($quantity > $max) return ['valid' => false, 'message' => 'Maximum quantity is ' . number_format($max)];

        return ['valid' => true];
    }

    public function getServiceLogo($category, $serviceName = '')
    {
        $category    = strtolower($category);
        $serviceName = strtolower($serviceName);

        $logoMap = [
            'instagram' => '📸', 'facebook' => '📘', 'twitter' => '🐦', 'x.com' => '❌',
            'tiktok' => '🎵', 'youtube' => '▶️', 'telegram' => '✈️', 'linkedin' => '💼',
            'snapchat' => '👻', 'pinterest' => '📌', 'spotify' => '🎧', 'soundcloud' => '🔊',
            'twitch' => '🎮', 'reddit' => '🤖', 'discord' => '💬', 'whatsapp' => '💚',
            'threads' => '🧵', 'clubhouse' => '🎙️',
        ];

        foreach ($logoMap as $platform => $logo) {
            if (str_contains($category, $platform) || str_contains($serviceName, $platform)) return $logo;
        }

        return '🚀';
    }

    public function mapStatus($apiStatus): string
    {
        $apiStatus = trim(strtolower($apiStatus));

        $statusMap = [
            'pending'     => 'pending',
            'in progress' => 'processing',
            'processing'  => 'processing',
            'completed'   => 'success',
            'complete'    => 'success',
            'partial'     => 'partial',
            'canceled'    => 'failed',
            'cancelled'   => 'failed',
            'refunded'    => 'refunded',
            'failed'      => 'failed',
        ];

        return $statusMap[$apiStatus] ?? 'pending';
    }

    public function formatServiceForDisplay($service, $exchangeRate = 1500)
    {
        $serviceId = $service['service'] ?? null;
        $costs     = $this->calculateCost($service['rate'], 1000, $exchangeRate, $serviceId);
        $category  = $service['category'] ?? 'Other';

        return [
            'service_id'          => $service['service'],
            'name'                => $service['name'],
            'type'                => $service['type'] ?? 'General',
            'category'            => $category,
            'rate_usd'            => floatval($service['rate']),
            'rate_local'          => $costs['cost_local'],
            'rate_local_per_unit' => $costs['cost_local_per_unit'],
            'client_percentage'   => $costs['percentage_applied'],
            'min'                 => intval($service['min']),
            'max'                 => intval($service['max']),
            'refill'              => $service['refill'] ?? false,
            'cancel'              => $service['cancel'] ?? false,
            'logo'                => $this->getServiceLogo($category, $service['name']),
            'description'         => $service['description'] ?? null,
        ];
    }
}