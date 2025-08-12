<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

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
 * Place a bet
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
        'CallBackURL' => $callbackUrl ?? self::getCallbackUrl()
    ];

    $response = Http::get(self::getBaseUrl() . '/APIBettingV1.asp', $params);

    return $response->json();
}
}
