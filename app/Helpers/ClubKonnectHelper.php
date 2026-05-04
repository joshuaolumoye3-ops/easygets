<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClubKonnectHelper
{
    private static function getUserId()
    {
        return env('CLUBKONNECT_USERID');
    }

    private static function getApiKey()
    {
        return env('CLUBKONNECT_APIKEY');
    }

    private static function getCallbackUrl()
    {
        return env('CLUBKONNECT_CALLBACK', url('/clubkonnect/callback'));
    }

    private static function getBaseUrl()
    {
        return 'https://www.nellobytesystems.com';
    }

    /**
     * 🔒 Mask sensitive credentials for logging
     */
    private static function maskCredentials($value)
    {
        if (!$value || strlen($value) < 8) {
            return '***';
        }
        
        $visible = substr($value, 0, 4);
        $hidden = str_repeat('*', strlen($value) - 8);
        $end = substr($value, -4);
        
        return "{$visible}{$hidden}{$end}";
    }

    /**
     * 🔒 Log request without exposing credentials
     */
    private static function logRequest($endpoint, $userId, $amount, $customerId = null, $bettingCompany = null)
    {
        Log::info('ClubKonnect API Request', [
            'endpoint' => $endpoint,
            'user_id' => $userId,
            'masked_userid' => self::maskCredentials(self::getUserId()),
            'masked_apikey' => self::maskCredentials(self::getApiKey()),
            'amount' => $amount,
            'betting_company' => $bettingCompany,
            'customer_id_masked' => $customerId ? substr($customerId, 0, 3) . '***' : null,
        ]);
    }

    /**
     * 🔒 Log response safely
     */
    private static function logResponse($endpoint, $statusCode, $status, $reference = null)
    {
        Log::info('ClubKonnect API Response', [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'status' => $status,
            'reference' => $reference,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Get Available Betting Companies
     */
    public static function getBettingCompanies()
    {
        try {
            $endpoint = 'APIBettingCompaniesV2.asp';
            
            Log::debug('Fetching betting companies list');

            $response = Http::withoutVerifying()
                ->timeout(15)
                ->acceptJson()
                ->get('https://www.nellobytesystems.com/' . $endpoint);

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data) && isset($data['BETTING_COMPANY'])) {
                    Log::info('Betting companies fetched successfully', [
                        'count' => count($data['BETTING_COMPANY']),
                    ]);
                    return $data;
                }
            }

            Log::warning('Betting companies endpoint returned empty or invalid data', [
                'status' => $response->status(),
            ]);

            return ['BETTING_COMPANY' => []];
        } catch (\Exception $e) {
            Log::error('Failed to fetch betting companies', [
                'error' => $e->getMessage(),
                'error_type' => class_basename($e),
            ]);
            return ['BETTING_COMPANY' => []];
        }
    }

    /**
     * Place a Bet / Fund Betting Account
     */
    public static function placeBet($bettingCompany, $customerId, $amount, $requestId, $callbackUrl = null)
    {
        $params = [
            'UserID' => self::getUserId(),
            'APIKey' => self::getApiKey(),
            'BettingCompany' => $bettingCompany,
            'CustomerID' => $customerId,
            'Amount' => $amount,
            'RequestID' => $requestId,
            'CallBackURL' => $callbackUrl ?? self::getCallbackUrl(),
        ];

        try {
            $url = self::getBaseUrl() . '/APIBettingV1.asp';
            
            self::logRequest('APIBettingV1.asp', self::getUserId(), $amount, $customerId, $bettingCompany);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get($url, $params);

            $data = json_decode($response->body(), true);

            // If API returns XML or plain text
            if ($data === null && str_contains($response->body(), '<')) {
                try {
                    $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
                    $data = json_decode(json_encode($xml), true);
                } catch (\Exception $e) {
                    Log::error('XML decode failed for betting response', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Normalize
            if (!is_array($data)) {
                Log::error('Betting API returned non-array response', [
                    'response_type' => gettype($response->body()),
                    'status_code' => $response->status(),
                ]);

                return [
                    'status' => false,
                    'statuscode' => '500',
                    'message' => 'Invalid API response format',
                ];
            }

            $statusCode = $data['statuscode'] ?? $data['StatusCode'] ?? '500';
            $status = $data['status'] ?? $data['Status'] ?? 'UNKNOWN';

            self::logResponse('APIBettingV1.asp', $statusCode, $status, $requestId);

            return $data;

        } catch (\Exception $e) {
            Log::error('Betting API Exception', [
                'error' => $e->getMessage(),
                'error_type' => class_basename($e),
                'request_id' => $requestId,
                'betting_company' => $bettingCompany,
            ]);

            return [
                'status' => false,
                'statuscode' => '500',
                'message' => 'API request failed',
            ];
        }
    }

    /**
     * Verify Betting Customer ID
     */
    public static function verifyBettingCustomer($bettingCompany, $customerId)
    {
        $params = [
            'UserID' => self::getUserId(),
            'APIKey' => self::getApiKey(),
            'BettingCompany' => $bettingCompany,
            'CustomerID' => $customerId
        ];

        try {
            $endpoint = 'APIVerifyBettingV1.asp';
            
            self::logRequest($endpoint, self::getUserId(), null, $customerId, $bettingCompany);

            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get(self::getBaseUrl() . '/' . $endpoint, $params);

            $data = $response->json();

            self::logResponse($endpoint, $response->status(), $data['customer_name'] ?? 'Unknown');

            return $data;

        } catch (\Exception $e) {
            Log::error('Customer verification error', [
                'error' => $e->getMessage(),
                'error_type' => class_basename($e),
                'betting_company' => $bettingCompany,
            ]);

            return [
                'customer_name' => 'Error, verification failed'
            ];
        }
    }

    /**
     * Query Transaction by Order ID or Request ID
     */
    public static function queryTransaction($orderId = null, $requestId = null)
    {
        $params = [
            'UserID' => self::getUserId(),
            'APIKey' => self::getApiKey()
        ];

        if ($orderId) {
            $params['OrderID'] = $orderId;
        } elseif ($requestId) {
            $params['RequestID'] = $requestId;
        } else {
            Log::warning('Query transaction called without OrderID or RequestID');
            return ['status' => false, 'message' => 'No query parameter provided'];
        }

        try {
            $endpoint = 'APIQueryV1.asp';
            
            Log::debug('Querying transaction', [
                'has_order_id' => !empty($orderId),
                'has_request_id' => !empty($requestId),
            ]);

            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get(self::getBaseUrl() . '/' . $endpoint, $params);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Query transaction error', [
                'error' => $e->getMessage(),
                'error_type' => class_basename($e),
            ]);

            return [
                'status' => false,
                'message' => 'Query failed',
            ];
        }
    }

    /**
     * Cancel Transaction
     */
    public static function cancelTransaction($orderId)
    {
        if (!$orderId) {
            Log::warning('Cancel transaction called without OrderID');
            return ['status' => false, 'message' => 'OrderID required'];
        }

        $params = [
            'UserID'  => self::getUserId(),
            'APIKey'  => self::getApiKey(),
            'OrderID' => $orderId
        ];

        try {
            $endpoint = 'APICancelV1.asp';
            
            Log::info('Attempting to cancel transaction', [
                'order_id_masked' => substr($orderId, 0, 3) . '***',
            ]);

            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get(self::getBaseUrl() . '/' . $endpoint, $params);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Cancel transaction error', [
                'error' => $e->getMessage(),
                'error_type' => class_basename($e),
            ]);

            return [
                'status' => false,
                'message' => 'Cancel failed',
            ];
        }
    }

    /**
     * Buy Airtime
     */
    public static function buyAirtime($mobileNetwork, $amount, $mobileNumber, $requestId, $callbackUrl = null, $bonusType = null)
    {
        $params = [
            'UserID'        => self::getUserId(),
            'APIKey'        => self::getApiKey(),
            'MobileNetwork' => $mobileNetwork,
            'Amount'        => $amount,
            'MobileNumber'  => $mobileNumber,
            'RequestID'     => $requestId,
            'CallBackURL'   => $callbackUrl ?? self::getCallbackUrl()
        ];

        if ($bonusType) {
            $params['BonusType'] = $bonusType;
        }

        try {
            self::logRequest('APIAirtimeV1.asp', self::getUserId(), $amount);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get(self::getBaseUrl() . '/APIAirtimeV1.asp', $params);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Airtime purchase error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Purchase failed'];
        }
    }

    /**
     * Buy Data Bundle
     */
    public static function buyDataBundle($mobileNetwork, $dataPlan, $mobileNumber, $requestId, $callbackUrl = null)
    {
        $params = [
            'UserID'        => self::getUserId(),
            'APIKey'        => self::getApiKey(),
            'MobileNetwork' => $mobileNetwork,
            'DataPlan'      => $dataPlan,
            'MobileNumber'  => $mobileNumber,
            'RequestID'     => $requestId,
            'CallBackURL'   => $callbackUrl ?? self::getCallbackUrl()
        ];

        try {
            self::logRequest('APIDatabundleV1.asp', self::getUserId(), $dataPlan);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get(self::getBaseUrl() . '/APIDatabundleV1.asp', $params);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Data bundle purchase error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Purchase failed'];
        }
    }

    /**
     * Cable TV Subscription
     */
    public static function buyCableTV($cableTV, $package, $smartCardNo, $phoneNo, $requestId, $callbackUrl = null)
    {
        $params = [
            'UserID'       => self::getUserId(),
            'APIKey'       => self::getApiKey(),
            'CableTV'      => $cableTV,
            'Package'      => $package,
            'SmartCardNo'  => $smartCardNo,
            'PhoneNo'      => $phoneNo,
            'RequestID'    => $requestId,
            'CallBackURL'  => $callbackUrl ?? self::getCallbackUrl()
        ];

        try {
            Log::info('Cable TV subscription initiated', [
                'cable_tv' => $cableTV,
                'package' => $package,
                'request_id' => $requestId,
            ]);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get(self::getBaseUrl() . '/APICableTVV1.asp', $params);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Cable TV subscription error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Subscription failed'];
        }
    }

    /**
     * Verify Cable TV (IUC/SmartCard Number)
     */
    public static function verifyCableTVCard($cableTV, $smartCardNo)
    {
        $params = [
            'UserID'      => self::getUserId(),
            'APIKey'      => self::getApiKey(),
            'CableTV'     => $cableTV,
            'SmartCardNo' => $smartCardNo
        ];

        try {
            Log::debug('Verifying cable TV card', [
                'cable_tv' => $cableTV,
                'smartcard_masked' => substr($smartCardNo, 0, 3) . '***',
            ]);

            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get(self::getBaseUrl() . '/APIVerifyCableTVV1.0.asp', $params);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Cable TV verification error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Verification failed'];
        }
    }

    /**
     * Get All Cable TV Packages
     */
    public static function getCableTVPackages()
    {
        $params = [
            'UserID' => self::getUserId(),
            'APIKey' => self::getApiKey()
        ];

        try {
            Log::debug('Fetching cable TV packages');

            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get(self::getBaseUrl() . '/APICableTVPackagesV2.asp', $params);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Failed to fetch cable TV packages', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buy Electricity Bill
     */
    public static function buyElectricity($electricCompany, $package, $meterNo, $amount, $phoneNo, $requestId, $callbackUrl = null)
    {
        $params = [
            'UserID'          => self::getUserId(),
            'APIKey'          => self::getApiKey(),
            'ElectricCompany' => $electricCompany,
            'MeterType'       => $package,
            'MeterNo'         => $meterNo,
            'Amount'          => $amount,
            'PhoneNo'         => $phoneNo,
            'RequestID'       => $requestId,
            'CallBackURL'     => $callbackUrl ?? self::getCallbackUrl()
        ];

        try {
            self::logRequest('APIElectricityV1.asp', self::getUserId(), $amount);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get(self::getBaseUrl() . '/APIElectricityV1.asp', $params);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Electricity purchase error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Purchase failed'];
        }
    }

    /**
     * Verify Electricity Meter Number
     */
    public static function verifyElectricityMeter($electricCompany, $meterNo)
    {
        $params = [
            'UserID'          => self::getUserId(),
            'APIKey'          => self::getApiKey(),
            'ElectricCompany' => $electricCompany,
            'MeterNo'         => $meterNo
        ];

        try {
            Log::debug('Verifying electricity meter', [
                'electric_company' => $electricCompany,
                'meter_masked' => substr($meterNo, 0, 3) . '***',
            ]);

            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get(self::getBaseUrl() . '/APIVerifyElectricityV1.asp', $params);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Electricity meter verification error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Verification failed'];
        }
    }

    /**
     * Get Available JAMB Packages
     */
    public static function getJambPackages()
    {
        try {
            $endpoint = 'APIJAMBPackagesV2.asp';
            
            Log::debug('Fetching JAMB packages list');

            $response = Http::withoutVerifying()
                ->timeout(15)
                ->get(self::getBaseUrl() . '/' . $endpoint, [
                    'UserID' => self::getUserId(),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('JAMB packages fetched successfully', [
                    'endpoint' => $endpoint,
                    'package_count' => count($data['JAMB_PACKAGE'] ?? []),
                ]);
                return $data;
            }

            Log::warning('JAMB packages endpoint returned error', [
                'status' => $response->status(),
            ]);

            return ['JAMB_PACKAGE' => []];
        } catch (\Exception $e) {
            Log::error('Failed to fetch JAMB packages', [
                'error' => $e->getMessage(),
                'error_type' => class_basename($e),
            ]);
            return ['JAMB_PACKAGE' => []];
        }
    }

    /**
     * Verify JAMB Profile ID
     */
    public static function verifyJambProfileId($examType, $profileId)
    {
        $params = [
            'UserID' => self::getUserId(),
            'APIKey' => self::getApiKey(),
            'ExamType' => $examType,
            'ProfileID' => $profileId
        ];

        try {
            $endpoint = 'APIVerifyJAMBV1.asp';
            
            self::logRequest($endpoint, self::getUserId(), null, $profileId, $examType);

            $response = Http::timeout(15)
                ->withoutVerifying()
                ->get(self::getBaseUrl() . '/' . $endpoint, $params);

            if ($response->successful()) {
                $data = $response->json();
                
                self::logResponse($endpoint, $response->status(), 
                    $data['customer_name'] ?? 'Unknown', 
                    $profileId);

                return $data;
            }

            Log::warning('JAMB verification endpoint error', [
                'status' => $response->status(),
                'profile_id' => substr($profileId, 0, 3) . '***',
            ]);

            return ['customer_name' => 'Error verifying profile'];

        } catch (\Exception $e) {
            Log::error('JAMB Profile Verification Exception', [
                'error' => $e->getMessage(),
                'profile_id' => substr($profileId, 0, 3) . '***',
                'exam_type' => $examType,
            ]);

            return ['customer_name' => 'Verification service unavailable'];
        }
    }

    /**
     * Buy JAMB PIN
     */
    public static function buyJamb($phoneNo, $requestId, $examType = 'utme-mock', $callbackUrl = null)
    {
        $params = [
            'UserID' => self::getUserId(),
            'APIKey' => self::getApiKey(),
            'ExamType' => $examType,
            'PhoneNo' => $phoneNo,
            'RequestID' => $requestId,
            'CallBackURL' => $callbackUrl ?? self::getCallbackUrl(),
        ];

        try {
            $url = self::getBaseUrl() . '/APIJAMBV1.asp';
            
            self::logRequest('APIJAMBV1.asp', self::getUserId(), null, null, null);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get($url, $params);

            $data = $response->json();

            // If API returns XML or plain text
            if ($data === null && str_contains($response->body(), '<')) {
                Log::warning('JAMB API returned XML instead of JSON', [
                    'body' => substr($response->body(), 0, 500),
                ]);
                return ['status' => 'error', 'message' => 'Invalid API response format'];
            }

            // Normalize
            if (!is_array($data)) {
                Log::warning('JAMB API response not array', [
                    'response_type' => gettype($data),
                    'response' => substr((string)$response->body(), 0, 200),
                ]);
                return ['status' => 'error', 'message' => 'Invalid response'];
            }

            $statusCode = $data['statuscode'] ?? $data['StatusCode'] ?? '500';
            $status = $data['status'] ?? $data['Status'] ?? 'UNKNOWN';
            $orderId = $data['orderid'] ?? $data['OrderID'] ?? $requestId;

            self::logResponse('APIJAMBV1.asp', $statusCode, $status, $requestId);

            return $data;

        } catch (\Exception $e) {
            Log::error('JAMB PIN Purchase Exception', [
                'error' => $e->getMessage(),
                'exam_type' => $examType,
                'request_id' => $requestId,
                'phone_masked' => substr($phoneNo, 0, 4) . '****' . substr($phoneNo, -3),
            ]);

            return [
                'statuscode' => '500',
                'status' => 'ERROR',
                'message' => 'Purchase service temporarily unavailable'
            ];
        }
    }
}