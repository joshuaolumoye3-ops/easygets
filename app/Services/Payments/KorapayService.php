<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Thin wrapper around the Korapay Merchant API.
 *
 * Docs used to build this (verified against Korapay's own reference, not memory):
 *  - Checkout Redirect:            https://developers.korapay.com/docs/checkout-redirect
 *  - Webhooks & signature check:   https://developers.korapay.com/docs/webhooks
 *  - Virtual Bank Accounts (NGN):  https://developers.korapay.com/docs/virtual-bank-accounts-ngn
 *
 * SECURITY NOTES:
 *  - The secret key is only ever used server-side and only ever sent as a
 *    Bearer token over HTTPS. It must never be exposed to the browser.
 *  - Every response is logged, but sensitive fields (BVN/NIN) are masked
 *    before logging — see maskSensitive().
 */
class KorapayService
{
    protected string $baseUrl;
    protected string $secretKey;
    protected string $publicKey;

    public function __construct()
    {
        $this->baseUrl   = rtrim(config('services.korapay.base_url', 'https://api.korapay.com/merchant/api/v1'), '/');
        $this->secretKey = (string) config('services.korapay.secret_key');
        $this->publicKey = (string) config('services.korapay.public_key');

        if (empty($this->secretKey)) {
            Log::error('Korapay secret key is not configured.');
        }
    }

    protected function client()
    {
        return Http::withToken($this->secretKey)
            ->acceptJson()
            ->timeout(30);
    }

    /**
     * Initialize a one-time hosted checkout (Checkout Redirect flow).
     *
     * @param  string $reference   Our own unique reference (also becomes transactions.reference)
     */
    public function initializeCharge(array $data): array
    {
        $payload = [
            'amount'           => $data['amount'],
            'currency'         => $data['currency'] ?? 'NGN',
            'reference'        => $data['reference'],
            'redirect_url'     => $data['redirect_url'],
            'notification_url' => $data['notification_url'],
            'narration'        => $data['narration'] ?? 'Wallet funding',
            'customer'         => [
                'email' => $data['customer']['email'],
                'name'  => $data['customer']['name'],
            ],
            // Merchant absorbs the fee by default; flip to false to pass it to the customer.
            'merchant_bears_cost' => $data['merchant_bears_cost'] ?? true,
        ];

        Log::info('Korapay: initializing charge', ['reference' => $payload['reference'], 'amount' => $payload['amount']]);

        try {
            $response = $this->client()->post("{$this->baseUrl}/charges/initialize", $payload);
            $json = $response->json();

            Log::info('Korapay: initialize charge response', [
                'reference' => $payload['reference'],
                'status'    => $response->status(),
                'ok'        => $json['status'] ?? null,
            ]);

            if ($response->successful() && ($json['status'] ?? false) === true) {
                return [
                    'success'      => true,
                    'checkout_url' => $json['data']['checkout_url'] ?? null,
                    'reference'    => $json['data']['reference'] ?? $payload['reference'],
                ];
            }

            return [
                'success' => false,
                'message' => $json['message'] ?? 'Failed to initialize Korapay charge.',
            ];
        } catch (\Throwable $e) {
            Log::error('Korapay: initializeCharge exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Payment service unavailable. Please try again.'];
        }
    }

    /**
     * Server-to-server verification of a charge. This is the SOURCE OF TRUTH
     * used before crediting any wallet — never trust the webhook body alone.
     */
    public function verifyCharge(string $reference): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/charges/{$reference}");
            $json = $response->json();

            if ($response->successful() && ($json['status'] ?? false) === true) {
                return ['success' => true, 'data' => $json['data']];
            }

            Log::warning('Korapay: verifyCharge non-success response', [
                'reference' => $reference,
                'status'    => $response->status(),
                'body'      => $json,
            ]);

            return ['success' => false, 'message' => $json['message'] ?? 'Unable to verify transaction.'];
        } catch (\Throwable $e) {
            Log::error('Korapay: verifyCharge exception', ['reference' => $reference, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Payment service unavailable.'];
        }
    }

    /**
     * Create a permanent (dedicated) NGN virtual bank account for a customer.
     *
     * Per Korapay's current docs, `kyc.bvn` is REQUIRED and `kyc.nin` is optional
     * (it is supplementary, not a substitute for BVN). If your compliance team
     * confirms NIN-only is now accepted, adjust the validation in
     * DedicatedAccountController accordingly — don't assume, check with Korapay
     * support since this is a KYC/regulatory detail that can change.
     */
    public function createVirtualAccount(array $data): array
    {
        $payload = [
            'account_name'      => $data['account_name'],
            'account_reference' => $data['account_reference'],
            'permanent'         => true,
            'bank_code'         => $data['bank_code'] ?? config('services.korapay.default_bank_code', '035'),
            'customer'          => [
                'name'  => $data['customer']['name'],
                'email' => $data['customer']['email'],
            ],
            'kyc' => array_filter([
                'bvn' => $data['bvn'] ?? null,
                'nin' => $data['nin'] ?? null,
            ]),
        ];

        Log::info('Korapay: creating virtual account', [
            'account_reference' => $payload['account_reference'],
            'bank_code'          => $payload['bank_code'],
            // never log bvn/nin values themselves
            'has_bvn'            => isset($payload['kyc']['bvn']),
            'has_nin'            => isset($payload['kyc']['nin']),
        ]);

        try {
            $response = $this->client()->post("{$this->baseUrl}/virtual-bank-account", $payload);
            $json = $response->json();

            if ($response->successful() && ($json['status'] ?? false) === true) {
                return ['success' => true, 'data' => $json['data']];
            }

            Log::warning('Korapay: createVirtualAccount failed', [
                'account_reference' => $payload['account_reference'],
                'status'            => $response->status(),
                'message'           => $json['message'] ?? null,
            ]);

            return ['success' => false, 'message' => $json['message'] ?? 'Failed to create virtual account.'];
        } catch (\Throwable $e) {
            Log::error('Korapay: createVirtualAccount exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Payment service unavailable. Please try again.'];
        }
    }

    public function getVirtualAccount(string $accountReference): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/virtual-bank-account/{$accountReference}");
            $json = $response->json();

            if ($response->successful() && ($json['status'] ?? false) === true) {
                return ['success' => true, 'data' => $json['data']];
            }

            return ['success' => false, 'message' => $json['message'] ?? 'Unable to retrieve account.'];
        } catch (\Throwable $e) {
            Log::error('Korapay: getVirtualAccount exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Payment service unavailable.'];
        }
    }

    /**
     * Verify the `x-korapay-signature` header.
     *
     * Per Korapay's docs, the signature is an HMAC-SHA256 of ONLY the `data`
     * object from the payload (not the whole envelope), signed with the
     * secret key. We re-encode with JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
     * so PHP's json_encode output matches JavaScript's JSON.stringify byte-for-byte
     * (PHP escapes "/" and non-ASCII by default; JS does not) — a common source of
     * false-negative signature mismatches if left at defaults.
     *
     * This check is a first line of defense. It is NOT the only defense — see
     * PaymentProcessor, which independently re-verifies the charge via the API
     * before crediting anything.
     */
    public function verifySignature(array $dataObject, ?string $signatureHeader): bool
    {
        if (empty($signatureHeader) || empty($this->secretKey)) {
            return false;
        }

        $encoded = json_encode($dataObject, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $expected = hash_hmac('sha256', $encoded, $this->secretKey);

        return hash_equals($expected, $signatureHeader);
    }
}
