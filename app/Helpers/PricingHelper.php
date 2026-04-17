<?php

namespace App\Helpers;

use App\Models\Provider;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class PricingHelper
{
    /**
     * Get the client price multiplier for a provider
     * 
     * @param string $providerIdentifier - The provider's api_identifier (e.g., '5sim', 'smsbus')
     * @return float|null - The client price multiplier, or null if not found
     */
    public static function getClientPriceMultiplier($providerIdentifier)
    {
        try {
            $provider = Provider::where('api_identifier', $providerIdentifier)->first();
            
            if (!$provider) {
                Log::warning('PricingHelper: Provider not found', [
                    'provider_identifier' => $providerIdentifier
                ]);
                return null;
            }

            // Get the first active product for this provider (contains the client price multiplier)
            $product = Product::where('provider_id', $provider->id)
                ->where('is_active', 1)
                ->first();

            if (!$product || !$product->client_price) {
                Log::warning('PricingHelper: No active product with client_price found', [
                    'provider_id' => $provider->id,
                    'provider_identifier' => $providerIdentifier
                ]);
                return null;
            }

            return $product->client_price;
        } catch (\Exception $e) {
            Log::error('PricingHelper: Error getting client price multiplier', [
                'provider_identifier' => $providerIdentifier,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get the client price for a specific product code
     * 
     * @param string $providerIdentifier
     * @param string $productCode
     * @return float|null
     */
    public static function getProductClientPrice($providerIdentifier, $productCode)
    {
        try {
            $provider = Provider::where('api_identifier', $providerIdentifier)->first();
            
            if (!$provider) {
                return null;
            }

            $product = Product::where('provider_id', $provider->id)
                ->where('product_code', $productCode)
                ->where('is_active', 1)
                ->first();

            return $product ? $product->client_price : null;
        } catch (\Exception $e) {
            Log::error('PricingHelper: Error getting product client price', [
                'provider_identifier' => $providerIdentifier,
                'product_code' => $productCode,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Apply pricing multiplier to a price value
     * 
     * @param float $apiPrice - The original API price (USD)
     * @param float $clientPriceMultiplier - The client price multiplier
     * @return float - The final price
     */
    public static function applyMultiplier($apiPrice, $clientPriceMultiplier)
    {
        if (!$clientPriceMultiplier || $clientPriceMultiplier <= 0) {
            return $apiPrice;
        }

        return round($apiPrice * $clientPriceMultiplier, 2);
    }

    /**
     * Calculate final price with exchange rate and/or client multiplier
     * 
     * @param float $apiPrice - The original API price
     * @param string $providerIdentifier - The provider identifier
     * @param float|null $exchangeRate - Optional exchange rate (if prices are in USD)
     * @return float - The final price in NGN
     */
    public static function calculateFinalPrice($apiPrice, $providerIdentifier, $exchangeRate = null)
    {
        try {
            $clientMultiplier = self::getClientPriceMultiplier($providerIdentifier);

            if (!$clientMultiplier) {
                // Fallback to exchange rate if available
                if ($exchangeRate) {
                    return round($apiPrice * $exchangeRate, 2);
                }
                return $apiPrice;
            }

            // Apply the client multiplier
            return self::applyMultiplier($apiPrice, $clientMultiplier);
        } catch (\Exception $e) {
            Log::error('PricingHelper: Error calculating final price', [
                'api_price' => $apiPrice,
                'provider_identifier' => $providerIdentifier,
                'error' => $e->getMessage()
            ]);
            return $apiPrice;
        }
    }

    /**
     * Apply pricing to a collection of items
     * Expects items to have 'cost_usd' or 'price' key
     * 
     * @param \Illuminate\Support\Collection $items
     * @param string $providerIdentifier
     * @param float|null $exchangeRate
     * @param string $priceKey - The key for the original price (default: 'cost_usd')
     * @param string $outputKey - The key for the final price (default: 'cost_ngn')
     * @return \Illuminate\Support\Collection
     */
    public static function applyPricingToCollection($items, $providerIdentifier, $exchangeRate = null, $priceKey = 'cost_usd', $outputKey = 'cost_ngn')
    {
        try {
            $clientMultiplier = self::getClientPriceMultiplier($providerIdentifier);

            return $items->map(function ($item) use ($clientMultiplier, $exchangeRate, $priceKey, $outputKey) {
                $apiPrice = $item[$priceKey] ?? $item['price'] ?? 0;

                if ($clientMultiplier && $clientMultiplier > 0) {
                    // Use client multiplier if available
                    $item[$outputKey] = round($apiPrice * $clientMultiplier, 2);
                } elseif ($exchangeRate && $exchangeRate > 0) {
                    // Fallback to exchange rate
                    $item[$outputKey] = round($apiPrice * $exchangeRate, 2);
                } else {
                    // No conversion
                    $item[$outputKey] = $apiPrice;
                }

                return $item;
            });
        } catch (\Exception $e) {
            Log::error('PricingHelper: Error applying pricing to collection', [
                'provider_identifier' => $providerIdentifier,
                'error' => $e->getMessage()
            ]);
            return $items;
        }
    }

    /**
     * Format price for display
     * 
     * @param float $price
     * @param string $currency
     * @return string
     */
    public static function formatPrice($price, $currency = '₦')
    {
        return $currency . number_format($price, 2);
    }
}
