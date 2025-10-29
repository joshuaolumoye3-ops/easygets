<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoreThanPanelHelper
{
    private $apiUrl;
    private $apiKey;

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
                
                // Log response structure for debugging
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
     * Calculate order cost (converts from USD to your local currency)
     */
    public function calculateCost($rateUsd, $quantity, $exchangeRate = 1500)
    {
        // Rate is per 1000 units, so divide quantity by 1000
        $costUsd = ($quantity / 1000) * floatval($rateUsd);
        
        // Convert to local currency
        $costLocal = $costUsd * $exchangeRate;
        
        return [
            'cost_usd' => round($costUsd, 2),
            'cost_local' => round($costLocal, 2)
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
    public function mapStatus($apiStatus)
    {
        $apiStatus = trim(strtolower($apiStatus));
        
        $statusMap = [
            'pending' => 'pending',
            'in progress' => 'processing',
            'processing' => 'processing',
            'completed' => 'completed',
            'complete' => 'completed',
            'partial' => 'partial',
            'canceled' => 'failed',
            'cancelled' => 'failed',
            'refunded' => 'refunded',
            'failed' => 'failed',
        ];

        return $statusMap[$apiStatus] ?? 'pending';
    }

    /**
     * Format service data for display
     */
    public function formatServiceForDisplay($service, $exchangeRate = 1500)
    {
        // Calculate cost for 1000 units (standard rate)
        $costs = $this->calculateCost($service['rate'], 1000, $exchangeRate);
        
        return [
            'service_id' => $service['service'],
            'name' => $service['name'],
            'type' => $service['type'],
            'category' => $service['category'],
            'rate_usd' => floatval($service['rate']),
            'rate_local' => $costs['cost_local'], // Cost per 1000 units in local currency
            'min' => intval($service['min']),
            'max' => intval($service['max']),
            'refill' => $service['refill'] ?? false,
            'cancel' => $service['cancel'] ?? false,
            'logo' => $this->getServiceLogo($service['category'], $service['name']),
            'description' => $service['description'] ?? null,
        ];
    }
}