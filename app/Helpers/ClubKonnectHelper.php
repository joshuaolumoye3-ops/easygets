<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClubKonnectHelper
{
    private static function getUserId(): string
    {
        return (string) env('CLUBKONNECT_USERID', '');
    }

    private static function getApiKey(): string
    {
        return (string) env('CLUBKONNECT_APIKEY', '');
    }

    private static function getCallbackUrl(): string
    {
        return (string) env('CLUBKONNECT_CALLBACK', url('/clubkonnect/callback'));
    }

    private static function getBaseUrl(): string
    {
        return 'https://www.nellobytesystems.com';
    }

    /**
     * Mask a credential for safe logging — never log full keys/IDs.
     */
    private static function mask(string $value): string
    {
        $len = strlen($value);
        if ($len < 8) return str_repeat('*', $len);
        return substr($value, 0, 4) . str_repeat('*', $len - 8) . substr($value, -4);
    }

    /**
     * Build base credentials array (UserID + APIKey).
     * Keys come from env only — never from request input.
     */
    private static function credentials(): array
    {
        return [
            'UserID' => self::getUserId(),
            'APIKey' => self::getApiKey(),
        ];
    }

    /**
     * Safely log an outgoing request without exposing credentials.
     */
    private static function logRequest(string $endpoint, array $context = []): void
    {
        Log::info('ClubKonnect →', array_merge([
            'endpoint'         => $endpoint,
            'masked_userid'    => self::mask(self::getUserId()),
            'masked_apikey'    => self::mask(self::getApiKey()),
            'timestamp'        => now()->toDateTimeString(),
        ], $context));
    }

    /**
     * Safely log an API response.
     */
    private static function logResponse(string $endpoint, int $httpStatus, array $context = []): void
    {
        Log::info('ClubKonnect ←', array_merge([
            'endpoint'    => $endpoint,
            'http_status' => $httpStatus,
            'timestamp'   => now()->toDateTimeString(),
        ], $context));
    }

    // -------------------------------------------------------------------------
    // Electricity
    // -------------------------------------------------------------------------

    /**
     * Fetch all electricity discos and their products from ClubKonnect.
     */
    public static function getElectricityDiscos(): array
    {
        $endpoint = 'APIElectricityDiscosV1.asp';

        try {
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get(self::getBaseUrl() . '/' . $endpoint, [
                    'UserID' => self::getUserId(),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['ELECTRIC_COMPANY'] ?? (is_array($data) ? $data : []);
            }

            Log::warning('ClubKonnect: getElectricityDiscos non-success', [
                'http_status' => $response->status(),
            ]);
            return [];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: getElectricityDiscos exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Verify an electricity meter number.
     *
     * The ClubKonnect API REQUIRES MeterType alongside ElectricCompany and MeterNo.
     * See: APIVerifyElectricityV1.asp?...&MeterType=01
     *
     * Returns array with 'customer_name' on success, or error fields on failure.
     */
    public static function verifyElectricityMeter(string $electricCompany, string $meterNo, string $meterType): array
    {
        $endpoint = 'APIVerifyElectricityV1.asp';

        $params = array_merge(self::credentials(), [
            'ElectricCompany' => $electricCompany,
            'MeterNo'         => $meterNo,
            'MeterType'       => $meterType,
        ]);

        try {
            self::logRequest($endpoint, [
                'electric_company' => $electricCompany,
                'meter_type'       => $meterType,
                'meter_masked'     => substr($meterNo, 0, 4) . str_repeat('*', max(0, strlen($meterNo) - 4)),
            ]);

            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get(self::getBaseUrl() . '/' . $endpoint, $params);

            self::logResponse($endpoint, $response->status(), [
                'electric_company' => $electricCompany,
            ]);

            $data = $response->json();
            return is_array($data) ? $data : ['message' => 'Invalid API response'];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: verifyElectricityMeter exception', [
                'error'            => $e->getMessage(),
                'electric_company' => $electricCompany,
                'meter_masked'     => substr($meterNo, 0, 4) . '****',
            ]);
            return ['status' => false, 'message' => 'Verification failed'];
        }
    }

    /**
     * Purchase electricity (pay a bill / top up a meter).
     *
     * @param string $electricCompany  e.g. "01" for EKEDC
     * @param string $package          MeterType: "01" = Prepaid, "02" = Postpaid
     * @param string $meterNo          Customer meter number
     * @param float  $amount           Amount in NGN
     * @param string $phoneNo          Recipient phone for SMS notification
     * @param string $requestId        Your unique request ID (UUID recommended)
     * @param string|null $callbackUrl Optional callback URL override
     */
    public static function buyElectricity(
        string $electricCompany,
        string $package,
        string $meterNo,
        float  $amount,
        string $phoneNo,
        string $requestId,
        ?string $callbackUrl = null
    ): array {
        $endpoint = 'APIElectricityV1.asp';

        $params = array_merge(self::credentials(), [
            'ElectricCompany' => $electricCompany,
            'MeterType'       => $package,           // "01" Prepaid / "02" Postpaid
            'MeterNo'         => $meterNo,
            'Amount'          => $amount,
            'PhoneNo'         => $phoneNo,
            'RequestID'       => $requestId,
            'CallBackURL'     => $callbackUrl ?? self::getCallbackUrl(),
        ]);

        try {
            self::logRequest($endpoint, [
                'electric_company' => $electricCompany,
                'meter_type'       => $package,
                'amount'           => $amount,
                'request_id'       => $requestId,
                'meter_masked'     => substr($meterNo, 0, 4) . '****',
            ]);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get(self::getBaseUrl() . '/' . $endpoint, $params);

            $data = $response->json();

            self::logResponse($endpoint, $response->status(), [
                'statuscode' => $data['statuscode'] ?? $data['StatusCode'] ?? 'n/a',
                'status'     => $data['status'] ?? $data['Status'] ?? 'n/a',
                'request_id' => $requestId,
            ]);

            return is_array($data) ? $data : ['status' => false, 'message' => 'Invalid API response'];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: buyElectricity exception', [
                'error'            => $e->getMessage(),
                'electric_company' => $electricCompany,
                'amount'           => $amount,
                'request_id'       => $requestId,
            ]);
            return ['status' => false, 'message' => 'Purchase failed'];
        }
    }

    // -------------------------------------------------------------------------
    // Cable TV
    // -------------------------------------------------------------------------

    public static function getCableTVPackages(): array
    {
        try {
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get(self::getBaseUrl() . '/APICableTVPackagesV2.asp', self::credentials());

            return $response->successful() ? ($response->json() ?? []) : [];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: getCableTVPackages exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public static function verifyCableTVCard(string $cableTV, string $smartCardNo): array
    {
        $params = array_merge(self::credentials(), [
            'CableTV'     => $cableTV,
            'SmartCardNo' => $smartCardNo,
        ]);

        try {
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get(self::getBaseUrl() . '/APIVerifyCableTVV1.0.asp', $params);

            return $response->json() ?? ['status' => false, 'message' => 'Verification failed'];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: verifyCableTVCard exception', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Verification failed'];
        }
    }

    public static function buyCableTV(
        string $cableTV,
        string $package,
        string $smartCardNo,
        string $phoneNo,
        string $requestId,
        ?string $callbackUrl = null
    ): array {
        $params = array_merge(self::credentials(), [
            'CableTV'     => $cableTV,
            'Package'     => $package,
            'SmartCardNo' => $smartCardNo,
            'PhoneNo'     => $phoneNo,
            'RequestID'   => $requestId,
            'CallBackURL' => $callbackUrl ?? self::getCallbackUrl(),
        ]);

        try {
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get(self::getBaseUrl() . '/APICableTVV1.asp', $params);

            return $response->json() ?? ['status' => false, 'message' => 'Subscription failed'];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: buyCableTV exception', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Subscription failed'];
        }
    }

    // -------------------------------------------------------------------------
    // Airtime
    // -------------------------------------------------------------------------

    public static function buyAirtime(
        string $mobileNetwork,
        float  $amount,
        string $mobileNumber,
        string $requestId,
        ?string $callbackUrl = null,
        ?string $bonusType = null
    ): array {
        $params = array_merge(self::credentials(), [
            'MobileNetwork' => $mobileNetwork,
            'Amount'        => $amount,
            'MobileNumber'  => $mobileNumber,
            'RequestID'     => $requestId,
            'CallBackURL'   => $callbackUrl ?? self::getCallbackUrl(),
        ]);

        if ($bonusType) {
            $params['BonusType'] = $bonusType;
        }

        try {
            self::logRequest('APIAirtimeV1.asp', ['amount' => $amount, 'network' => $mobileNetwork]);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get(self::getBaseUrl() . '/APIAirtimeV1.asp', $params);

            return $response->json() ?? ['status' => false, 'message' => 'Purchase failed'];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: buyAirtime exception', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Purchase failed'];
        }
    }

    // -------------------------------------------------------------------------
    // Data Bundle
    // -------------------------------------------------------------------------

    public static function buyDataBundle(
        string $mobileNetwork,
        string $dataPlan,
        string $mobileNumber,
        string $requestId,
        ?string $callbackUrl = null
    ): array {
        $params = array_merge(self::credentials(), [
            'MobileNetwork' => $mobileNetwork,
            'DataPlan'      => $dataPlan,
            'MobileNumber'  => $mobileNumber,
            'RequestID'     => $requestId,
            'CallBackURL'   => $callbackUrl ?? self::getCallbackUrl(),
        ]);

        try {
            self::logRequest('APIDatabundleV1.asp', ['network' => $mobileNetwork, 'plan' => $dataPlan]);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get(self::getBaseUrl() . '/APIDatabundleV1.asp', $params);

            return $response->json() ?? ['status' => false, 'message' => 'Purchase failed'];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: buyDataBundle exception', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Purchase failed'];
        }
    }

    // -------------------------------------------------------------------------
    // Betting
    // -------------------------------------------------------------------------

    public static function getBettingCompanies(): array
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->acceptJson()
                ->get(self::getBaseUrl() . '/APIBettingCompaniesV2.asp', [
                    'UserID' => self::getUserId(),
                ]);

            if ($response->successful()) {
                $data      = $response->json();
                $companies = $data['BETTING_COMPANY'] ?? $data['SERVICES'] ?? (is_array($data) ? $data : []);
                if (is_object($companies)) $companies = array_values((array) $companies);
                if (is_array($companies) && count($companies) > 0) {
                    return ['BETTING_COMPANY' => $companies];
                }
            }

            return ['BETTING_COMPANY' => []];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: getBettingCompanies exception', ['error' => $e->getMessage()]);
            return ['BETTING_COMPANY' => []];
        }
    }

    public static function verifyBettingCustomer(string $bettingCompany, string $customerId): array
    {
        $params = array_merge(self::credentials(), [
            'BettingCompany' => $bettingCompany,
            'CustomerID'     => $customerId,
        ]);

        try {
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get(self::getBaseUrl() . '/APIVerifyBettingV1.asp', $params);

            return $response->json() ?? ['customer_name' => 'Verification failed'];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: verifyBettingCustomer exception', ['error' => $e->getMessage()]);
            return ['customer_name' => 'Error, verification failed'];
        }
    }

    public static function placeBet(
        string $bettingCompany,
        string $customerId,
        float  $amount,
        string $requestId,
        ?string $callbackUrl = null
    ): array {
        $params = array_merge(self::credentials(), [
            'BettingCompany' => $bettingCompany,
            'CustomerID'     => $customerId,
            'Amount'         => $amount,
            'RequestID'      => $requestId,
            'CallBackURL'    => $callbackUrl ?? self::getCallbackUrl(),
        ]);

        try {
            self::logRequest('APIBettingV1.asp', [
                'betting_company'   => $bettingCompany,
                'amount'            => $amount,
                'customer_masked'   => substr($customerId, 0, 3) . '***',
            ]);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get(self::getBaseUrl() . '/APIBettingV1.asp', $params);

            $data = $response->json();

            // Some endpoints return XML — handle gracefully
            if ($data === null && str_contains($response->body(), '<')) {
                try {
                    $xml  = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
                    $data = json_decode(json_encode($xml), true);
                } catch (\Exception $xmlEx) {
                    Log::error('ClubKonnect: placeBet XML decode failed', ['error' => $xmlEx->getMessage()]);
                }
            }

            return is_array($data) ? $data : ['status' => false, 'statuscode' => '500', 'message' => 'Invalid API response'];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: placeBet exception', [
                'error'          => $e->getMessage(),
                'betting_company'=> $bettingCompany,
                'request_id'     => $requestId,
            ]);
            return ['status' => false, 'statuscode' => '500', 'message' => 'API request failed'];
        }
    }

    // -------------------------------------------------------------------------
    // JAMB
    // -------------------------------------------------------------------------

    public static function getJambPackages(): array
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->get(self::getBaseUrl() . '/APIJAMBPackagesV2.asp', [
                    'UserID' => self::getUserId(),
                ]);

            return $response->successful() ? ($response->json() ?? ['JAMB_PACKAGE' => []]) : ['JAMB_PACKAGE' => []];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: getJambPackages exception', ['error' => $e->getMessage()]);
            return ['JAMB_PACKAGE' => []];
        }
    }

    public static function verifyJambProfileId(string $examType, string $profileId): array
    {
        $params = array_merge(self::credentials(), [
            'ExamType'  => $examType,
            'ProfileID' => $profileId,
        ]);

        try {
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->get(self::getBaseUrl() . '/APIVerifyJAMBV1.asp', $params);

            return $response->successful()
                ? ($response->json() ?? ['customer_name' => 'Error verifying profile'])
                : ['customer_name' => 'Error verifying profile'];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: verifyJambProfileId exception', ['error' => $e->getMessage()]);
            return ['customer_name' => 'Verification service unavailable'];
        }
    }

    public static function buyJamb(
        string $phoneNo,
        string $requestId,
        string $examType = 'utme-mock',
        ?string $callbackUrl = null
    ): array {
        $params = array_merge(self::credentials(), [
            'ExamType'    => $examType,
            'PhoneNo'     => $phoneNo,
            'RequestID'   => $requestId,
            'CallBackURL' => $callbackUrl ?? self::getCallbackUrl(),
        ]);

        try {
            self::logRequest('APIJAMBV1.asp', ['exam_type' => $examType, 'request_id' => $requestId]);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get(self::getBaseUrl() . '/APIJAMBV1.asp', $params);

            $data = $response->json();

            if ($data === null && str_contains($response->body(), '<')) {
                return ['status' => 'error', 'message' => 'Invalid API response format'];
            }

            return is_array($data) ? $data : ['status' => 'error', 'message' => 'Invalid response'];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: buyJamb exception', [
                'error'      => $e->getMessage(),
                'exam_type'  => $examType,
                'request_id' => $requestId,
            ]);
            return ['statuscode' => '500', 'status' => 'ERROR', 'message' => 'Purchase service temporarily unavailable'];
        }
    }

    // -------------------------------------------------------------------------
    // Transaction Utilities
    // -------------------------------------------------------------------------

    public static function queryTransaction(?string $orderId = null, ?string $requestId = null): array
    {
        $params = self::credentials();

        if ($orderId)       $params['OrderID']   = $orderId;
        elseif ($requestId) $params['RequestID'] = $requestId;
        else return ['status' => false, 'message' => 'No query parameter provided'];

        try {
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get(self::getBaseUrl() . '/APIQueryV1.asp', $params);

            return $response->json() ?? ['status' => false, 'message' => 'Query failed'];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: queryTransaction exception', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Query failed'];
        }
    }

    public static function cancelTransaction(string $orderId): array
    {
        $params = array_merge(self::credentials(), ['OrderID' => $orderId]);

        try {
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get(self::getBaseUrl() . '/APICancelV1.asp', $params);

            return $response->json() ?? ['status' => false, 'message' => 'Cancel failed'];

        } catch (\Exception $e) {
            Log::error('ClubKonnect: cancelTransaction exception', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Cancel failed'];
        }
    }
}