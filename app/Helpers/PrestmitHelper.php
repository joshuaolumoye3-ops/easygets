<?php

namespace App\Helpers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrestmitHelper
{
    private array $cfg;

    /** Keyword => domain, for Clearbit logos. Checked in order, first match wins. */
    private const BRAND_DOMAINS = [
        'itunes'        => 'apple.com',
        'apple'         => 'apple.com',
        'amazon'        => 'amazon.com',
        'google play'   => 'google.com',
        'playstation'   => 'playstation.com',
        'psn'           => 'playstation.com',
        'xbox'          => 'xbox.com',
        'steam'         => 'steampowered.com',
        'razer'         => 'razer.com',
        'nike'          => 'nike.com',
        'walmart'       => 'walmart.com',
        'ebay'          => 'ebay.com',
        'sephora'       => 'sephora.com',
        'nordstrom'     => 'nordstrom.com',
        'target'        => 'target.com',
        'best buy'      => 'bestbuy.com',
        'spotify'       => 'spotify.com',
        'netflix'       => 'netflix.com',
        'roblox'        => 'roblox.com',
        'vanilla'       => 'vanillagift.com',
        'macy'          => 'macys.com',
        'home depot'    => 'homedepot.com',
        'google'        => 'google.com',
        'visa'          => 'visa.com',
        'mastercard'    => 'mastercard.com',
        'sears'         => 'sears.com',
        'gamestop'      => 'gamestop.com',
        'starbucks'     => 'starbucks.com',
        'airbnb'        => 'airbnb.com',
        'uber'          => 'uber.com',
    ];

    /** Prestmit country code => flagcdn iso2. */
    private const COUNTRY_FLAGS = [
        'USA'    => 'us',
        'UK'     => 'gb',
        'EURO'   => 'eu',
        'EU'     => 'eu',
        'CANADA' => 'ca',
        'CA'     => 'ca',
        'CAN'    => 'ca',
    ];

    public function __construct()
    {
        $this->cfg = config('services.prestmit', []);
    }

    public function isConfigured(): bool
    {
        return !empty($this->cfg['api_key']) && !empty($this->cfg['api_secret']);
    }

    /**
     * Signed request to Prestmit.
     * Returns: ['ok'=>bool,'status'=>int,'data'=>array,'error'=>?string,'ambiguous'=>bool]
     * "ambiguous" = a mutating call whose outcome is unknown (timeout / 5xx) — never blindly retried or refunded.
     */
    public function request(string $method, string $path, array $fields = [], array $files = []): array
    {
        if (!$this->isConfigured()) {
            return $this->result(false, 0, [], 'Prestmit credentials are not configured.', false);
        }

        $method = strtoupper($method);
        $url    = rtrim($this->cfg['base_url'], '/') . '/' . ltrim($path, '/');
        $hash   = hash_hmac('sha256', $this->cfg['api_key'] . ':' . $this->hashBody($fields), $this->cfg['api_secret']);
        $send   = array_map(fn ($v) => is_scalar($v) ? (string) $v : $v, $fields);

        try {
            $http = Http::withHeaders([
                'API-KEY'  => $this->cfg['api_key'],
                'API-Hash' => $hash,
                'Accept'   => 'application/json',
            ])->timeout((int) ($this->cfg['timeout'] ?? 30));

            if ($method === 'GET') {
                $resp = $http->get($url, $send);
            } else {
                $http = $http->asMultipart();
                foreach ($files as $f) {
                    $http = $http->attach('attachments[]', $f['content'], $f['name']);
                }
                $resp = $http->post($url, $send);
            }
        } catch (ConnectionException $e) {
            Log::warning('Prestmit connection error', ['path' => $path, 'error' => $e->getMessage()]);
            return $this->result(false, 0, [], 'Could not reach Prestmit.', $method !== 'GET');
        } catch (\Throwable $e) {
            Log::error('Prestmit request exception', ['path' => $path, 'error' => $e->getMessage()]);
            return $this->result(false, 0, [], 'Unexpected error contacting Prestmit.', $method !== 'GET');
        }

        $status = $resp->status();
        $body   = $resp->json();
        if (!is_array($body)) {
            $body = [];
        }

        $ok = $resp->successful() && ($body['success'] ?? true) !== false;
        $error = null;
        if (!$ok) {
            $error = $body['message'] ?? $body['details'] ?? $body['error'] ?? 'Request failed (' . $status . ').';
            if (!empty($body['errors']) && is_array($body['errors'])) {
                $first = reset($body['errors']);
                $error = is_array($first) ? ($first[0] ?? $error) : $first;
            }
            Log::warning('Prestmit API error', ['path' => $path, 'status' => $status, 'error' => $error]);
        }

        return $this->result($ok, $status, $body, $error, !$ok && $method !== 'GET' && $status >= 500);
    }

    private function result(bool $ok, int $status, array $data, ?string $error, bool $ambiguous): array
    {
        return compact('ok', 'status', 'data', 'error', 'ambiguous');
    }

    /** Body string used for API-Hash. Attachments are never part of it. */
    private function hashBody(array $fields): string
    {
        if (empty($fields)) {
            return (string) ($this->cfg['empty_hash_body'] ?? '[]');
        }
        if (empty($this->cfg['hash_numeric'])) {
            $fields = array_map(fn ($v) => is_scalar($v) ? (string) $v : $v, $fields);
        }
        return json_encode($fields);
    }

    public function verifyWebhookSignature(string $raw, ?string $signature): bool
    {
        $secret = $this->cfg['webhook_secret'] ?? '';
        if ($secret === '' || !$signature) {
            return false;
        }
        return hash_equals(base64_encode(hash_hmac('sha256', $raw, $secret, true)), trim($signature));
    }

    // ── Catalogs (cached) ────────────────────────────────────────────────

    /** @return array{categories:array,cards:array}|null */
    public function sellCatalog(): ?array
    {
        return Cache::remember('prestmit:sell_catalog', 120, function () {
            $r = $this->request('GET', 'giftcard-trade/sell/rate-calculator-data');
            if (!$r['ok']) {
                return null;
            }
            $d = $r['data']['data'] ?? $r['data'];
            return [
                'categories' => $d['giftCardCategories'] ?? [],
                'cards'      => $d['sellableGiftcards'] ?? [],
            ];
        });
    }

    /** Normalised list of every buyable card, or null if Prestmit is unreachable. */
    public function buyCatalog(): ?array
    {
        return Cache::remember('prestmit:buy_catalog', 600, function () {
            $all = [];
            for ($page = 1; $page <= 20; $page++) {
                $r = $this->request('GET', 'giftcard-trade/buy/fetch-buyable-giftcards', ['perPage' => 25, 'page' => $page]);
                if (!$r['ok']) {
                    if ($page === 1) {
                        return null;
                    }
                    break;
                }
                foreach (($r['data']['data'] ?? []) as $c) {
                    if (!is_array($c) || empty($c['sku'])) {
                        continue;
                    }
                    $regionCode = strtolower((string) ($c['regions'][0]['code'] ?? ''));
                    $all[] = [
                        'sku'         => (int) $c['sku'],
                        'title'       => (string) ($c['title'] ?? 'Gift card'),
                        'description' => $this->cleanHtml($c['description']['content'] ?? ''),
                        'min'         => (float) ($c['minPrice'] ?? 0),
                        'max'         => (float) ($c['maxPrice'] ?? 0),
                        'currency'    => strtoupper($c['currency']['code'] ?? 'USD'),
                        'symbol'      => $c['currency']['symbol'] ?? '$',
                        'pre_order'   => (bool) ($c['preOrder'] ?? false),
                        'categories'  => array_values(array_filter(array_map(fn ($x) => $x['name'] ?? null, $c['categories'] ?? []))),
                        'regions'     => array_values(array_filter(array_map(fn ($x) => $x['name'] ?? null, $c['regions'] ?? []))),
                        'image'       => $c['imageUrl'] ?? null,
                        'logo'        => $c['imageUrl'] ?: $this->brandLogo($c['title'] ?? ''),
                        'flag'        => $regionCode !== '' ? $regionCode : null,
                    ];
                }
                if ($page >= (int) ($r['data']['meta']['last_page'] ?? 1)) {
                    break;
                }
            }
            return $all;
        });
    }

    public function cleanHtml($html): string
    {
        $html = strip_tags((string) $html, '<p><br><ul><ol><li><strong><b><em><i>');
        return preg_replace('/<(\w+)[^>]*>/', '<$1>', $html);
    }

    /** Best-effort brand logo (Clearbit CDN) from a card/category name. Client falls back to an emoji on error. */
    public function brandLogo(string $name): ?string
    {
        $lower = strtolower($name);
        foreach (self::BRAND_DOMAINS as $needle => $domain) {
            if (str_contains($lower, $needle)) {
                return 'https://logo.clearbit.com/' . $domain . '?size=128';
            }
        }
        return null;
    }

    /** flagcdn.com iso2 code for a Prestmit country code (USA, UK, EURO, CANADA, OTHERS…), or null. */
    public function countryFlag(?string $countryCode): ?string
    {
        $code = strtoupper(trim((string) $countryCode));
        return self::COUNTRY_FLAGS[$code] ?? null;
    }

    public function serviceAvailability(): array
    {
        return Cache::remember('prestmit:availability', 60, fn () => $this->request('GET', 'general/service-availability'));
    }
}