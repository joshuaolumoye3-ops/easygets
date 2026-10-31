<?php 

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class DemmyPayHelper
{
    private const NETWORKS = ['mtn', 'airtel', 'glo', '9mobile'];

    public static function normalizeNigerianPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = '234' . substr($digits, 1);
        }

        return preg_match('/^234[789][01]\d{8}$/', $digits) ? $digits : null;
    }

    public static function buyAirtime(string $network, float $amount, string $phone, string $reference): array
    {
        return self::post('/vend/' . self::network($network) . '/airtime', [
            'phone_number' => self::phone($phone),
            'amount'       => $amount,
            'reference'    => $reference,
        ]);
    }

    public static function buyData(
        string $network,
        string $phone,
        float $amount,
        string $tariffTypeId,
        string $reference
    ): array {
        return self::post('/vend/' . self::network($network) . '/data', [
            'phone_number'   => self::phone($phone),
            'amount'         => $amount,
            'tariff_type_id' => $tariffTypeId,
            'reference'      => $reference,
        ]);
    }

    public static function isSuccessful(array $response): bool
    {
        return (int) ($response['_http_status'] ?? 0) >= 200
            && (int) ($response['_http_status'] ?? 0) < 300
            && strtolower((string) ($response['status'] ?? '')) === 'processed'
            && strtoupper((string) ($response['code'] ?? '')) === 'SUC200';
    }

    public static function getDataProducts(): array
    {
        throw new RuntimeException('AfrikVend catalog response mapping is not available in the supplied API documentation.');
    }

    private static function post(string $path, array $payload): array
    {
        $baseUrl = rtrim((string) config('services.afrikvend.base_url'), '/');
        $apiKey = (string) config('services.afrikvend.api_key');

        if ($baseUrl === '' || $apiKey === '') {
            throw new RuntimeException('AfrikVend API configuration is missing.');
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withHeader('x-api-key', $apiKey)
            ->connectTimeout(5)
            ->timeout(25)
            ->post($baseUrl . $path, $payload);

        $body = $response->json();
        if (!is_array($body)) {
            $body = [];
        }

        if (!$response->successful()) {
            Log::warning('AfrikVend vending request rejected.', [
                'endpoint'    => $path,
                'http_status' => $response->status(),
                'code'        => $body['code'] ?? null,
            ]);
        }

        return $body + ['_http_status' => $response->status()];
    }

    private static function network(string $network): string
    {
        $network = strtolower($network);
        if (!in_array($network, self::NETWORKS, true)) {
            throw new RuntimeException('Unsupported AfrikVend network.');
        }

        return $network;
    }

    private static function phone(string $phone): string
    {
        $normalized = self::normalizeNigerianPhone($phone);
        if (!$normalized) {
            throw new RuntimeException('Invalid Nigerian phone number.');
        }

        return $normalized;
    }
}
