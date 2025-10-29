<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveService
{
    protected $baseUrl;
    protected $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.flutterwave.base_url');
        $this->secretKey = env('FLW_SECRET_KEY');
        Log::info('Flutterwave Service Initialized', [
            'base_url' => $this->baseUrl,
            'has_secret_key' => !empty($this->secretKey)
        ]);
    }

    public function createVirtualAccount($user_data)
    {
        $user = $user_data['user'];
        $amount = $user_data['amount'] ?? null;
        $payload = [
            'email' => $user->email,
            'is_permanent' => false,
            'tx_ref' => $user_data['tx_ref'] ?? ('easygets-' . uniqid() . '-' . time()),
            'firstname' => $user->first_name,
            'lastname' => $user->last_name ,
            'phonenumber' => $user->phone_number,
            'narration' => ($user->username ?? $user->email),
            'amount' => $amount

        ];
        $payload = array_filter($payload);

        Log::info('Sending Flutterwave Virtual Account Request', [
            'user_id' => $user->id,
            'payload' => $payload
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json'
            ])->timeout(30)->post("{$this->baseUrl}/virtual-account-numbers", $payload);

            $responseData = $response->json();

            Log::info('Flutterwave API Full Response', [
                'status_code' => $response->status(),
                'response' => $responseData
            ]);

            if ($response->successful()) {
                return $responseData;
            }
            Log::error('Flutterwave API Request Failed', [
                'status_code' => $response->status(),
                'response_body' => $responseData,
                'request_payload' => $payload,
                'headers_used' => [
                    'base_url' => $this->baseUrl,
                    'authorization_prefix' => 'Bearer ' . (substr($this->secretKey, 0, 10) . '...')
                ]
            ]);

            return [
                'status' => 'error',
                'message' => $responseData['message'] ?? 'API request failed',
                'error_code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Flutterwave Service Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return [
                'status' => 'error',
                'message' => 'Service exception: ' . $e->getMessage()
            ];
        }
    }
}
