<?php

namespace App\Helpers;

class OtpProviderResponseHelper
{
    public static function extractActivationData(mixed $response, string $provider): array
    {
        $provider = strtolower($provider);

        if ($provider === 'smsbower') {
            if (is_string($response)) {
                $body = trim($response);
                if (str_starts_with($body, 'ACCESS_NUMBER:')) {
                    $parts = explode(':', $body);
                    if (count($parts) >= 3) {
                        return [
                            'request_id' => (string) $parts[1],
                            'phone' => (string) $parts[2],
                        ];
                    }
                }
            }

            if (is_array($response)) {
                $id = $response['id'] ?? $response['request_id'] ?? null;
                $phone = $response['phone'] ?? $response['number'] ?? null;
                if ($id && $phone) {
                    return [
                        'request_id' => (string) $id,
                        'phone' => (string) $phone,
                    ];
                }
            }

            return [];
        }

        if ($provider === 'smsbus') {
            $payload = is_array($response) ? $response : [];
            $data = $payload['data'] ?? [];
            if (is_array($data) && !empty($data['request_id']) && !empty($data['number'])) {
                return [
                    'request_id' => (string) $data['request_id'],
                    'phone' => (string) $data['number'],
                ];
            }

            if (is_array($payload) && !empty($payload['request_id']) && !empty($payload['number'])) {
                return [
                    'request_id' => (string) $payload['request_id'],
                    'phone' => (string) $payload['number'],
                ];
            }

            return [];
        }

        if ($provider === '5sim' || $provider === 'provider1') {
            $payload = is_array($response) ? $response : [];
            $data = $payload['data'] ?? $payload;
            if (is_array($data) && !empty($data['id']) && !empty($data['phone'])) {
                return [
                    'request_id' => (string) $data['id'],
                    'phone' => (string) $data['phone'],
                ];
            }

            if (is_array($payload) && !empty($payload['id']) && !empty($payload['phone'])) {
                return [
                    'request_id' => (string) $payload['id'],
                    'phone' => (string) $payload['phone'],
                ];
            }
        }

        return [];
    }
}
