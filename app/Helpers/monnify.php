<?php

namespace App\Helpers;

use Exception;

class Monnify
{
    private static function getBaseUrl()
    {
        return config('services.monnify.is_live')
            ? 'https://api.monnify.com'
            : 'https://sandbox.monnify.com';
    }

    private static function getApiKey()
    {
        return config('services.monnify.api_key');
    }

    private static function getSecretKey()
    {
        return config('services.monnify.secret_key');
    }

    private static function getContractCode()
    {
        return config('services.monnify.contract_code');
    }

    public static function generateAccessToken()
    {
        $url = self::getBaseUrl() . '/api/v1/auth/login';
        $authHeader = "Basic " . base64_encode(self::getApiKey() . ":" . self::getSecretKey());

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: $authHeader",
                    'Content-Type: application/json'
                ]
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new Exception("cURL error: " . curl_error($ch));
            }

            curl_close($ch);

            $responseData = json_decode($response, true);

            if (!empty($responseData['requestSuccessful'])) {
                return [
                    'success' => true,
                    'data' => [
                        'accessToken' => $responseData['responseBody']['accessToken'],
                        'expiresIn' => $responseData['responseBody']['expiresIn']
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['responseMessage'] ?? 'Authentication failed'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Request failed: " . $e->getMessage()
            ];
        }
    }

   public static function createVirtualAccount($accountName, $customerEmail, $customerName, $bvn = null, $nin = null, $options = [])
    {
        // Validate at least BVN or NIN is provided
        if (empty($bvn) && empty($nin)) {
            return [
                'success' => false,
                'message' => 'Either BVN or NIN is required'
            ];
        }

        $tokenResponse = self::generateAccessToken();
        if (!$tokenResponse['success']) {
            return [
                'success' => false,
                'message' => 'Failed to generate access token'
            ];
        }

        $url = self::getBaseUrl() . '/api/v2/bank-transfer/reserved-accounts';
        $accessToken = $tokenResponse['data']['accessToken'];

        // Base payload
        $payload = [
            'accountReference' => uniqid('ACC_'),
            'accountName' => $accountName,
            'currencyCode' => 'NGN',
            'contractCode' => self::getContractCode(),
            'customerEmail' => $customerEmail,
            'customerName' => $customerName,
            'getAllAvailableBanks' => true
        ];

        // Add BVN or NIN based on what was provided
        if (!empty($bvn)) {
            $payload['bvn'] = $bvn;
        } elseif (!empty($nin)) {
            $payload['nin'] = $nin;
        }

        // Merge in any extra options (incomeSplitConfig, restrictPaymentSource, etc.)
        if (!empty($options)) {
            $payload = array_merge($payload, $options);
        }

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $accessToken,
                    'Content-Type: application/json'
                ]
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new Exception("cURL error: " . curl_error($ch));
            }

            curl_close($ch);

            $responseData = json_decode($response, true);

            if (!empty($responseData['requestSuccessful'])) {
                return [
                    'success' => true,
                    'data' => $responseData['responseBody']
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['responseMessage'] ?? 'Failed to create virtual account'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Request failed: " . $e->getMessage()
            ];
        }
    }
public static function initializeTransaction($amount, $customerName, $customerEmail)
{
    $tokenResponse = self::generateAccessToken();
    
    if (!$tokenResponse['success']) {
        return [
            'success' => false,
            'message' => 'Failed to generate access token'
        ];
    }

    $url = self::getBaseUrl() . '/api/v1/merchant/transactions/init-transaction';
    $accessToken = $tokenResponse['data']['accessToken'];

    $payload = [
        'amount' => $amount,
        'customerName' => $customerName,
        'customerEmail' => $customerEmail,
        'paymentReference' => 'PAY_' . uniqid(),
        'paymentDescription' => 'Bank Transfer Through OTA',
        'currencyCode' => 'NGN',
        'contractCode' => self::getContractCode(),
        'redirectUrl' => 'http://127.0.0.1:8000/monnifytransferAccount',
        'paymentMethods' => ['CARD', 'ACCOUNT_TRANSFER']
    ];

    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $accessToken,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($responseData['requestSuccessful']) {
            return [
                'success' => true,
                'data' => $responseData['responseBody']
            ];
        }

        return [
            'success' => false,
            'message' => $responseData['responseMessage'] ?? 'Failed to initialize transaction'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Request failed: " . $e->getMessage()
        ];
    }
}

public static function initBankTransfer($transactionReference, $bankCode = null)
{
    $tokenResponse = self::generateAccessToken();
    
    if (!$tokenResponse['success']) {
        return [
            'success' => false,
            'message' => 'Failed to generate access token'
        ];
    }

    $url = self::getBaseUrl() . '/api/v1/merchant/bank-transfer/init-payment';
    $accessToken = $tokenResponse['data']['accessToken'];

    $payload = [
        'transactionReference' => $transactionReference
    ];

    if ($bankCode) {
        $payload['bankCode'] = $bankCode;
    }

    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $accessToken,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("cURL error: " . curl_error($ch));
        }

        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($responseData['requestSuccessful']) {
            return [
                'success' => true,
                'data' => $responseData['responseBody']
            ];
        }

        return [
            'success' => false,
            'message' => $responseData['responseMessage'] ?? 'Failed to initialize bank transfer'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Request failed: " . $e->getMessage()
        ];
    }
}

//method to get transaction status
public static function getTransactionStatus($transactionReference)
{
    $tokenResponse = self::generateAccessToken();
    
    if (!$tokenResponse['success']) {
        return [
            'success' => false,
            'message' => 'Failed to generate access token'
        ];
    }

    $url = self::getBaseUrl() . '/api/v2/transactions/query?transactionReference=' . urlencode($transactionReference);
    $accessToken = $tokenResponse['data']['accessToken'];

    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $accessToken,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($responseData['requestSuccessful']) {
            return [
                'success' => true,
                'data' => $responseData['responseBody']
            ];
        }

        return [
            'success' => false,
            'message' => $responseData['responseMessage'] ?? 'Failed to get transaction status'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Request failed: " . $e->getMessage()
        ];
    }
}

// method to get Reserved Account Details

    public static function getReservedAccountDetails($accountReference)
    {
        $tokenResponse = self::generateAccessToken();
        
        if (!$tokenResponse['success']) {
            return [
                'success' => false,
                'message' => 'Failed to generate access token'
            ];
        }

        $url = self::getBaseUrl() . '/api/v2/bank-transfer/reserved-accounts/' . $accountReference;
        $accessToken = $tokenResponse['data']['accessToken'];

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $accessToken,
                    'Content-Type: application/json'
                ]
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new Exception("cURL error: " . curl_error($ch));
            }

            curl_close($ch);

            $responseData = json_decode($response, true);

            if ($responseData['requestSuccessful']) {
                return [
                    'success' => true,
                    'data' => $responseData['responseBody']
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['responseMessage'] ?? 'Failed to fetch account details'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Request failed: " . $e->getMessage()
            ];
        }
    }

    // Add to existing monnify helper class
    public static function validateWebhookHash($payload, $monnifySignature)
    {
        return hash_equals(
            hash_hmac('sha512', $payload, self::$secretKey),
            $monnifySignature
        );
    }

    public static function processWebhookEvent($eventData)
    {
        switch ($eventData['eventType']) {
            case 'SUCCESSFUL_TRANSACTION':
                return self::processSuccessfulTransaction($eventData['eventData']);
                
            case 'FAILED_TRANSACTION':
                return self::processFailedTransaction($eventData['eventData']);
                
            default:
                return [
                    'success' => false,
                    'message' => 'Unhandled event type'
                ];
        }
    }

    private static function processSuccessfulTransaction($data)
    {
        try {
            return [
                'success' => true,
                'data' => [
                    'reference' => $data['paymentReference'],
                    'amount' => $data['amountPaid'],
                    'status' => 'completed',
                    'payment_method' => $data['paymentMethod'],
                    'currency' => $data['currency'],
                    'customer' => $data['customer']
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
