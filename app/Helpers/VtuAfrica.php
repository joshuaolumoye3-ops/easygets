<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VtuAfrica
{

    public static function fundBetAccount(string $service, string $userId, float $amount, string $reference, ?string $phone = null): array
    {
        $baseUrl = self::getBaseUrl();
        $apiKey = self::getApiKey();

        $params = [
            'apikey' => $apiKey,
            'service' => $service,
            'userid' => $userId,
            'amount' => $amount,
            'ref' => $reference,
        ];

        if ($phone) {
            $params['phone'] = $phone;
        }

        try {
            $response = Http::timeout(30)->get($baseUrl, $params);
            $responseData = $response->json();

            self::logTransaction($params, $responseData);

            return [
                'success' => $response->successful() && ($responseData['code'] ?? 0) == 101,
                'data' => $responseData,
                'http_status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('VTU Africa API Error: ' . $e->getMessage(), [
                'params' => $params,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'error' => 'API request failed: ' . $e->getMessage(),
                'http_status' => 500,
            ];
        }
    }


    protected static function getBaseUrl(): string
    {
        return 'https://vtuafrica.com.ng/portal/api/betpay';
    }

    protected static function getApiKey(): string
    {
        return config('services.vtuafrica.api_key');
    }


    protected static function logTransaction(array $request, ?array $response): void
    {
        $logData = [
            'request' => $request,
            'response' => $response,
            'service' => $request['service'] ?? null,
            'amount' => $request['amount'] ?? null,
            'reference' => $request['ref'] ?? null,
        ];

        if (($response['code'] ?? 0) == 101) {
            Log::info('VTU Africa Successful Transaction', $logData);
        } else {
            Log::error('VTU Africa Failed Transaction', $logData);
        }
    }


    public static function getSupportedServices(): array
    {
        return [
            'Bet9ja' => 'Bet9ja',
            'BetKing' => 'BetKing',
            '1XBet' => '1XBet',
            'NairaBet' => 'NairaBet',
            'BetBiga' => 'BetBiga',
            'MerryBet' => 'MerryBet',
            'SportyBet' => 'SportyBet',
            'NaijaBet' => 'NaijaBet',
            'Betway' => 'Betway',
            'BangBet' => 'BangBet',
            'MelBet' => 'MelBet',
            'LiveScoreBet' => 'LiveScoreBet',
            'Naira-Million' => 'Naira Million',
            'CloudBet' => 'CloudBet',
            'Paripesa' => 'Paripesa',
            'MylottoHub' => 'MyLotto Hub',
        ];
    }


    public static function isValidService(string $service): bool
    {
        return array_key_exists($service, self::getSupportedServices());
    }
}
