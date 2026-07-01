<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PricingHelper
{
    /**
     * Calculate the final NGN price for a given provider.
     *
     * @param  float   $apiPriceUsd   The raw USD price from the provider API
     * @param  string  $provider      Provider constant ('smsbower' | 'smsbus' | '5sim')
     * @param  float   $exchangeRate  Already-fetched exchange rate (₦ per $1)
     * @return float   Final price in NGN
     */
    public static function calculateFinalPrice(
        float  $apiPriceUsd,
        string $provider,
        float  $exchangeRate
    ): float {
        // Base conversion
        $baseNgn = $apiPriceUsd * $exchangeRate;

        if ($provider === 'smsbower') {
            // Add optional flat markup stored in the products table
            $markup = self::getSmsBowerMarkup();
            return round($baseNgn + $markup, 2);
        }

        // For smsbus and 5sim the provider's own client_price is used directly
        // when set; this helper is only called for live-API price display.
        return round($baseNgn, 2);
    }

    /**
     * Fetch the admin-configured flat NGN markup for SMSBower.
     * Cached for 10 minutes to avoid repeated DB hits on the country list page.
     */
    public static function getSmsBowerMarkup(): float
    {
        return Cache::remember('smsbower_markup', 600, function () {
            try {
                $provider = \App\Models\Provider::where('api_identifier', 'smsbower')->first();
                if (!$provider) return 0.0;

                $product = \App\Models\Product::where('provider_id', $provider->id)
                    ->where('product_code', 'smsbower-markup')
                    ->where('is_active', true)
                    ->first();

                return $product ? (float) $product->client_price : 0.0;
            } catch (\Exception $e) {
                Log::error('PricingHelper getSmsBowerMarkup error', ['error' => $e->getMessage()]);
                return 0.0;
            }
        });
    }

    /**
     * Fetch the admin-configured USD→NGN rate for SMSBower.
     * Falls back to 1500 if not set.
     */
    public static function getSmsBowerRate(): float
    {
        return Cache::remember('smsbower_rate', 600, function () {
            try {
                $provider = \App\Models\Provider::where('api_identifier', 'smsbower')->first();
                if (!$provider) return 1500.0;

                $product = \App\Models\Product::where('provider_id', $provider->id)
                    ->where('product_code', 'smsbower-rate')
                    ->where('is_active', true)
                    ->first();

                return ($product && $product->client_price > 0) ? (float) $product->client_price : 1500.0;
            } catch (\Exception $e) {
                Log::error('PricingHelper getSmsBowerRate error', ['error' => $e->getMessage()]);
                return 1500.0;
            }
        });
    }
}