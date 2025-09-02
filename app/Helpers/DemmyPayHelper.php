<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class DemmyPayHelper
{
    protected static $baseUrl;
    protected static $apiKey;
    protected static $passPhrase;
    protected static $accessToken;

    // initialize configs from .env
    public static function init()
    {
        self::$baseUrl = config('services.demmy.base_url');
        self::$apiKey = config('services.demmy.api_key');
        self::$passPhrase = config('services.demmy.pass_phrase');
    }

    // authenticate and fetch token
    public static function authenticate()
    {
        self::init();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(self::$baseUrl . '/api/v1/authentication', [
            'apiKey' => self::$apiKey,
            'passPhrase' => self::$passPhrase,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            self::$accessToken = $data['data']['accessToken'];
            return self::$accessToken;
        }

        throw new \Exception('DemmyPay Authentication failed: ' . $response->body());
    }

    // internal function to ensure valid token
    protected static function getToken()
    {
        if (!self::$accessToken) {
            return self::authenticate();
        }
        return self::$accessToken;
    }

    // purchase airtime
    public static function buyAirtime($amount, $productId, $recipient, $customerReference)
    {
        $token = self::getToken();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->post(self::$baseUrl . '/api/v1/purchase', [
            'amount' => $amount,
            'productId' => $productId,
            'recipient' => $recipient,
            'customerReference' => $customerReference,
        ]);

        return $response->json();
    }

    // purchase data
    public static function buyData($productId, $recipient, $customerReference)
    {
        $token = self::getToken();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->post(self::$baseUrl . '/api/v1/purchase', [
            'productId' => $productId,
            'recipient' => $recipient,
            'customerReference' => $customerReference,
        ]);

        return $response->json();
    }
}
