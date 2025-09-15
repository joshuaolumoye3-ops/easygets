<?php 

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DemmyPayHelper
{
    protected static $baseUrl;
    protected static $apiKey;
    protected static $passPhrase;
    protected static $accessToken;

    // initialize configs from .env
    public static function init()
    {
        self::$baseUrl    = config('services.demmy.base_url');
        self::$apiKey     = config('services.demmy.api_key');
        self::$passPhrase = config('services.demmy.pass_phrase');
    }

    // authenticate and fetch token
    public static function authenticate()
    {
        self::init();

        Log::info('DemmyPay: Starting authentication...');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(self::$baseUrl . '/api/v1/authentication', [
            'apiKey'     => self::$apiKey,
            'passPhrase' => self::$passPhrase,
        ]);

        Log::info('DemmyPay Auth Response:', ['status' => $response->status(), 'body' => $response->body()]);

        if ($response->successful()) {
            $data = $response->json();
            self::$accessToken = $data['data']['accessToken'] ?? null;
            
            if (self::$accessToken) {
                Log::info('DemmyPay: Authentication successful');
                return self::$accessToken;
            }
        }

        Log::error('DemmyPay Authentication failed:', ['response' => $response->body()]);
        throw new \Exception('DemmyPay Authentication failed: ' . $response->body());
    }

    // internal function to ensure valid token
    protected static function getToken()
    {
        return self::authenticate();
    }

    // fetch DATA products
    public static function getDataProducts()
    {
        $token = self::getToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ])->get(self::$baseUrl . '/api/v1/product', [
            'category' => 'DATA'
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Failed to fetch data products: ' . $response->body());
    }

    // purchase airtime (still supported for backward use)
    public static function buyAirtime($amount, $productId, $recipient, $customerReference)
    {
        $token = self::getToken();

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->post(self::$baseUrl . '/api/v1/purchase', [
            'amount'           => $amount,
            'productId'        => $productId,
            'recipient'        => $recipient,
            'customerReference'=> $customerReference,
        ]);

        return $response->json();
    }

    // purchase data (Demmy Global format: productId + recipient + customerReference)
    public static function buyData($productId, $recipient, $customerReference)
    {
        $token = self::getToken();

        $payload = [
            'productId'        => $productId,
            'recipient'        => $recipient,
            'customerReference'=> $customerReference,
        ];

        Log::info('DemmyPay Data Purchase Request:', $payload);

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->post(self::$baseUrl . '/api/v1/purchase', $payload);

        Log::info('DemmyPay Data Purchase Response:', ['status' => $response->status(), 'body' => $response->body()]);

        return $response->json();
    }
}
