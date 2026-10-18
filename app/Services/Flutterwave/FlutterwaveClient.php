<?php

namespace App\Services\Flutterwave;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin, defensive wrapper around the Flutterwave v3 REST API.
 *
 * Every method returns:
 *   ['ok' => bool, 'http' => int, 'message' => string, 'data' => mixed, 'ambiguous' => bool]
 *
 * `ambiguous` is TRUE only for state-changing calls (create account / transfer) where the
 * outcome is unknown (timeout, connection drop, 5xx). Callers MUST NOT blindly retry those.
 *
 * Security: request bodies, BVN/NIN, the secret key and full account numbers are never logged.
 */
class FlutterwaveClient
{
    private string $baseUrl;
    private string $secret;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.flutterwave.base_url', 'https://api.flutterwave.com/v3'), '/');
        $this->secret  = trim((string) config('services.flutterwave.secret_key'));
    }

    // ── Virtual accounts ─────────────────────────────────────────────────

    public function createStaticVirtualAccount(array $payload): array
    {
        $payload['is_permanent'] = true;
        return $this->request('POST', '/virtual-account-numbers', $payload, mutating: true, timeout: 30);
    }

    // ── Collections ──────────────────────────────────────────────────────

    public function verifyTransaction(int|string $id): array
    {
        return $this->request('GET', '/transactions/' . (int) $id . '/verify');
    }

    // ── Payouts ──────────────────────────────────────────────────────────

    /** @return array<int, array{code:string,name:string}>|null */
    public function banks(): ?array
    {
        $cached = Cache::get('flw:banks:ng');
        if (is_array($cached) && $cached) {
            return $cached;
        }

        $r = $this->request('GET', '/banks/NG');
        if (!$r['ok'] || !is_array($r['data'])) {
            return null;
        }

        $banks = collect($r['data'])
            ->filter(fn ($b) => !empty($b['code']) && !empty($b['name']))
            ->map(fn ($b) => ['code' => (string) $b['code'], 'name' => (string) $b['name']])
            ->unique('code')->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        Cache::put('flw:banks:ng', $banks, now()->addHours(12));
        return $banks;
    }

    public function resolveAccount(string $accountNumber, string $bankCode): array
    {
        return $this->request('POST', '/accounts/resolve', [
            'account_number' => $accountNumber,
            'account_bank'   => $bankCode,
        ]);
    }

    /** @return array{ok:bool, available:?float, message:string} */
    public function balance(string $currency = 'NGN'): array
    {
        $r = $this->request('GET', '/balances/' . strtoupper($currency));
        if (!$r['ok'] || !is_array($r['data'])) {
            return ['ok' => false, 'available' => null, 'message' => $r['message']];
        }
        $row = isset($r['data'][0]) && is_array($r['data'][0]) ? $r['data'][0] : $r['data'];
        return ['ok' => true, 'available' => (float) ($row['available_balance'] ?? 0), 'message' => 'OK'];
    }

    public function transfer(array $payload): array
    {
        return $this->request('POST', '/transfers', $payload, mutating: true, timeout: 30);
    }

    public function getTransfer(int|string $id): array
    {
        return $this->request('GET', '/transfers/' . (int) $id);
    }

    public function findTransfersByReference(string $reference): array
    {
        return $this->request('GET', '/transfers', ['reference' => $reference]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public static function maskAccount(string $number): string
    {
        $len = strlen($number);
        return $len <= 4 ? str_repeat('*', $len) : str_repeat('*', $len - 4) . substr($number, -4);
    }

    private function request(string $method, string $path, array $data = [], bool $mutating = false, int $timeout = 20): array
    {
        if ($this->secret === '') {
            Log::critical('Flutterwave secret key is not configured (services.flutterwave.secret_key).');
            return $this->result(false, 0, 'Payment provider is not configured.');
        }

        try {
            $http = Http::withToken($this->secret)->acceptJson()->asJson()
                ->connectTimeout(8)->timeout($timeout);

            // Safe-to-repeat calls get a couple of quick retries. State-changing calls never do.
            if (!$mutating) {
                $http = $http->retry(2, 300, fn ($e) => $e instanceof ConnectionException, throw: false);
            }

            $response = $method === 'GET'
                ? $http->get($this->baseUrl . $path, $data)
                : $http->post($this->baseUrl . $path, $data);
        } catch (ConnectionException $e) {
            Log::warning('Flutterwave connection error', ['path' => $path, 'type' => class_basename($e)]);
            return $this->result(false, 0, 'Could not reach the payment provider.', null, $mutating);
        } catch (\Throwable $e) {
            Log::error('Flutterwave client exception', ['path' => $path, 'type' => class_basename($e)]);
            return $this->result(false, 0, 'Unexpected payment provider error.', null, $mutating);
        }

        $body    = is_array($response->json()) ? $response->json() : [];
        $http    = $response->status();
        $ok      = $response->successful() && (($body['status'] ?? '') === 'success');
        $message = (string) ($body['message'] ?? ($ok ? 'OK' : 'Request failed'));

        if (!$ok) {
            Log::warning('Flutterwave API error', ['path' => $path, 'http' => $http, 'message' => $message]);
        }

        // A 5xx on a state-changing call means we cannot know whether it was applied.
        $ambiguous = $mutating && !$ok && $http >= 500;

        return $this->result($ok, $http, $message, $body['data'] ?? null, $ambiguous);
    }

    private function result(bool $ok, int $http, string $message, mixed $data = null, bool $ambiguous = false): array
    {
        return ['ok' => $ok, 'http' => $http, 'message' => $message, 'data' => $data, 'ambiguous' => $ambiguous];
    }
}