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
     * Get Available Betting Companies
     */
    public static function getBettingCompanies()
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->acceptJson()
                ->get('https://www.nellobytesystems.com/APIBettingCompaniesV2.asp');

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data) && isset($data['BETTING_COMPANY'])) {
                    return $data; // ✅ return array, not JsonResponse
                }
            }

            return ['BETTING_COMPANY' => []];
        } catch (\Exception $e) {
            Log::error('Failed to fetch betting companies: ' . $e->getMessage());
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

            $response = Http::timeout(30)->get($url, $params);

            \Log::info('Betting API Request', ['url' => $url, 'params' => $params]);
            \Log::info('Betting API Response', ['response' => $response->body()]);

            // Decode safely
            $data = json_decode($response->body(), true);

            // If API returns XML or plain text (sometimes ClubKonnect does this)
            if ($data === null) {
                // Try XML decode fallback
                if (str_contains($response->body(), '<')) {
                    try {
                        $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
                        $data = json_decode(json_encode($xml), true);
                    } catch (\Exception $e) {
                        \Log::error('XML decode failed: ' . $e->getMessage());
                    }
                }
            }

            // Normalize
            if (!is_array($data)) {
                return [
                    'status' => false,
                    'statuscode' => '500',
                    'message' => 'Invalid API response',
                    'raw' => $response->body(),
                ];
            }

            return $data;
        } catch (\Exception $e) {
            \Log::error('Betting API Exception: ' . $e->getMessage(), ['params' => $params]);
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
            $response = Http::timeout(15)->get(self::getBaseUrl() . '/APIVerifyBettingV1.asp', $params);
            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Customer verification error: ' . $e->getMessage());
            return [
                'customer_name' => 'Error, verification failed'
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

        $response = Http::get(self::getBaseUrl() . '/APIAirtimeV1.asp', $params);
        return $response->json();
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

        $response = Http::get(self::getBaseUrl() . '/APIDatabundleV1.asp', $params);
        return $response->json();
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

        $response = Http::get(self::getBaseUrl() . '/APICableTVV1.asp', $params);
        return $response->json();
    }

    /**
     * Verify IUC/SmartCard Number
     */
    public static function verifyCableTVCard($cableTV, $smartCardNo)
    {
        $params = [
            'UserID'      => self::getUserId(),
            'APIKey'      => self::getApiKey(),
            'CableTV'     => $cableTV,
            'SmartCardNo' => $smartCardNo
        ];

        $response = Http::get(self::getBaseUrl() . '/APIVerifyCableTVV1.0.asp', $params);
        return $response->json();
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

        $response = Http::get(self::getBaseUrl() . '/APICableTVPackagesV2.asp', $params);
        return $response->json();
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
        }

        $response = Http::get(self::getBaseUrl() . '/APIQueryV1.asp', $params);
        return $response->json();
    }

    /**
     * Cancel Transaction
     */
    public static function cancelTransaction($orderId)
    {
        $params = [
            'UserID'  => self::getUserId(),
            'APIKey'  => self::getApiKey(),
            'OrderID' => $orderId
        ];

        $response = Http::get(self::getBaseUrl() . '/APICancelV1.asp', $params);
        return $response->json();
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

        $response = Http::get(self::getBaseUrl() . '/APIElectricityV1.asp', $params);
        return $response->json();
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

        $response = Http::get(self::getBaseUrl() . '/APIVerifyElectricityV1.asp', $params);
        return $response->json();
    }

    /**
     * Buy JAMB PIN
     */
    public static function buyJamb($phoneNo, $requestId, $examType = 'utme', $callbackUrl = null)
    {
        $params = [
            'UserID' => self::getUserId(),
            'APIKey' => self::getApiKey(),
            'ExamType' => $examType,
            'PhoneNo' => $phoneNo,
            'RequestID' => $requestId,
            'CallBackURL' => $callbackUrl ?? self::getCallbackUrl()
        ];

        $response = Http::get(self::getBaseUrl() . '/APIJAMBV1.asp', $params);
        return $response->json();
    }
}