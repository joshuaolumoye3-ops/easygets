<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveService
{
    protected $baseUrl;
    protected $secretKey;
    protected $publicKey;

    public function __construct()
    {
        $this->baseUrl = config('services.flutterwave.base_url');
        $this->secretKey = trim(env('FLW_SECRET_KEY', config('services.flutterwave.secret_key')));
        $this->publicKey = trim(env('FLW_PUBLIC_KEY', config('services.flutterwave.public_key')));

        Log::info('Flutterwave Service Initialized', [
            'base_url' => $this->baseUrl,
            'has_secret_key' => !empty($this->secretKey),
            'secret_key_length' => strlen($this->secretKey),
            'has_public_key' => !empty($this->publicKey)
        ]);
    }

    /**
     * Initialize payment checkout
     */
    public function initializePayment($user_data)
    {
        $user = $user_data['user'];
        $amount = $user_data['amount'];
        $txRef = $user_data['tx_ref'];

        if (!$this->secretKey) {
            Log::error('Flutterwave Secret Key is empty', [
                'user_id' => $user->id
            ]);
            return [
                'status' => 'error',
                'message' => 'Server configuration error: Missing Flutterwave secret key'
            ];
        }

        $payload = [
            'tx_ref' => $txRef,
            'amount' => $amount,
            'currency' => 'NGN',
            'redirect_url' => route('flutterwave.callback'),
            'customer' => [
                'email' => $user->email,
                'phonenumber' => $user->phone_number,
                'name' => "{$user->first_name} {$user->last_name}"
            ],
            'customizations' => [
                'title' => 'Wallet Funding',
                'description' => "Add ₦" . number_format($amount, 2) . " to wallet",
                'logo' => asset('images/logo.png')
            ],
            'meta' => [
                'user_id' => $user->id,
                'wallet_id' => $user->wallet->id ?? null
            ]
        ];

        Log::info('Initializing Flutterwave Payment', [
            'user_id' => $user->id,
            'amount' => $amount,
            'tx_ref' => $txRef
        ]);

        try {
            $authHeader = 'Bearer ' . $this->secretKey;
            
            Log::info('Flutterwave Auth Header Debug', [
                'header_length' => strlen($authHeader),
                'starts_with_bearer' => strpos($authHeader, 'Bearer ') === 0,
                'secret_key_length' => strlen($this->secretKey)
            ]);

            $response = Http::withHeaders([
                'Authorization' => $authHeader,
                'Content-Type' => 'application/json'
            ])->timeout(30)->post("{$this->baseUrl}/payments", $payload);

            $responseData = $response->json();

            Log::info('Flutterwave Payment Initialization Response', [
                'status_code' => $response->status(),
                'response' => $responseData
            ]);

            if ($response->successful() && isset($responseData['status']) && $responseData['status'] === 'success') {
                return [
                    'status' => 'success',
                    'data' => $responseData['data'],
                    'payment_link' => $responseData['data']['link']
                ];
            }

            Log::error('Flutterwave Payment Initialization Failed', [
                'status_code' => $response->status(),
                'response_body' => $responseData
            ]);

            return [
                'status' => 'error',
                'message' => $responseData['message'] ?? 'Payment initialization failed'
            ];

        } catch (\Exception $e) {
            Log::error('Flutterwave Payment Initialization Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'message' => 'Service exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify transaction
     */
    public function verifyTransaction($transactionId)
    {
        Log::info('Verifying Flutterwave Transaction', [
            'transaction_id' => $transactionId
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json'
            ])->get("{$this->baseUrl}/transactions/{$transactionId}/verify");

            $responseData = $response->json();

            Log::info('Transaction Verification Response', [
                'transaction_id' => $transactionId,
                'response' => $responseData
            ]);

            return $responseData;

        } catch (\Exception $e) {
            Log::error('Transaction Verification Failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}