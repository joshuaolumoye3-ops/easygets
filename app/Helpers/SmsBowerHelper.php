<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SmsBowerHelper
{
    const BASE_URL = 'https://smsbower.page/stubs/handler_api.php';

    protected static function apiKey(): string
    {
        return env('SMSBOWER_API_KEY', '');
    }

    // ──────────────────────────────────────────────
    // Get account balance
    // ──────────────────────────────────────────────
    public static function getBalance(): ?float
    {
        try {
            $response = Http::timeout(15)->get(self::BASE_URL, [
                'api_key' => self::apiKey(),
                'action'  => 'getBalance',
            ]);

            if ($response->successful()) {
                $body = trim($response->body());
                if (str_starts_with($body, 'ACCESS_BALANCE:')) {
                    return (float) str_replace('ACCESS_BALANCE:', '', $body);
                }
            }
        } catch (\Exception $e) {
            Log::error('SmsBower getBalance error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    // ──────────────────────────────────────────────
    // Get list of services
    // ──────────────────────────────────────────────
    public static function getServices(): array
    {
        try {
            $response = Http::timeout(30)->get(self::BASE_URL, [
                'api_key' => self::apiKey(),
                'action'  => 'getServicesList',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['services']) && is_array($data['services'])) {
                    return $data['services'];
                }
            }
        } catch (\Exception $e) {
            Log::error('SmsBower getServices error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    // ──────────────────────────────────────────────
    // Get list of countries
    // ──────────────────────────────────────────────
    public static function getCountries(): array
    {
        try {
            $response = Http::timeout(30)->get(self::BASE_URL, [
                'api_key' => self::apiKey(),
                'action'  => 'getCountries',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data)) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            Log::error('SmsBower getCountries error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    // ──────────────────────────────────────────────
    // Get prices for a service + country
    // Returns: { countryId: { serviceCode: { cost, count } } }
    // ──────────────────────────────────────────────
    public static function getPrices(?string $service = null, ?string $country = null): array
    {
        $params = [
            'api_key' => self::apiKey(),
            'action'  => 'getPrices',
        ];
        if ($service) $params['service'] = $service;
        if ($country !== null) $params['country'] = $country;

        try {
            $response = Http::timeout(30)->get(self::BASE_URL, $params);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data)) return $data;
            }
        } catch (\Exception $e) {
            Log::error('SmsBower getPrices error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    // ──────────────────────────────────────────────
    // Buy a number (getNumber)
    // Returns: ['id' => ..., 'phone' => ...] or null
    // ──────────────────────────────────────────────
    public static function buyNumber(string $service, string $country, ?float $maxPrice = null): ?array
    {
        $params = [
            'api_key' => self::apiKey(),
            'action'  => 'getNumber',
            'service' => $service,
            'country' => $country,
        ];
        if ($maxPrice !== null) {
            $params['maxPrice'] = $maxPrice;
        }

        try {
            $response = Http::timeout(30)->get(self::BASE_URL, $params);

            if ($response->successful()) {
                $body = trim($response->body());
                Log::info('SmsBower buyNumber response', ['body' => $body]);

                if (str_starts_with($body, 'ACCESS_NUMBER:')) {
                    // Format: ACCESS_NUMBER:$activationId:$phoneNumber
                    $parts = explode(':', $body);
                    if (count($parts) >= 3) {
                        return [
                            'id'    => $parts[1],
                            'phone' => $parts[2],
                        ];
                    }
                }

                Log::warning('SmsBower buyNumber unexpected response', ['body' => $body]);
            }
        } catch (\Exception $e) {
            Log::error('SmsBower buyNumber error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    // ──────────────────────────────────────────────
    // Get SMS status / code
    // Returns one of: STATUS_WAIT_CODE | STATUS_WAIT_RETRY:$code | STATUS_CANCEL | STATUS_OK:$code
    // ──────────────────────────────────────────────
    public static function getStatus(string $activationId): string
    {
        try {
            $response = Http::timeout(15)->get(self::BASE_URL, [
                'api_key' => self::apiKey(),
                'action'  => 'getStatus',
                'id'      => $activationId,
            ]);

            if ($response->successful()) {
                return trim($response->body());
            }
        } catch (\Exception $e) {
            Log::error('SmsBower getStatus error', ['activation_id' => $activationId, 'error' => $e->getMessage()]);
        }

        return 'STATUS_WAIT_CODE';
    }

    // ──────────────────────────────────────────────
    // Change activation status
    // 1=ready, 3=request another, 6=complete, 8=cancel
    // ──────────────────────────────────────────────
    public static function setStatus(string $activationId, int $status): string
    {
        try {
            $response = Http::timeout(15)->get(self::BASE_URL, [
                'api_key' => self::apiKey(),
                'action'  => 'setStatus',
                'id'      => $activationId,
                'status'  => $status,
            ]);

            if ($response->successful()) {
                return trim($response->body());
            }
        } catch (\Exception $e) {
            Log::error('SmsBower setStatus error', ['activation_id' => $activationId, 'error' => $e->getMessage()]);
        }

        return 'ERROR';
    }

    // ──────────────────────────────────────────────
    // Cancel an activation (setStatus = 8)
    // ──────────────────────────────────────────────
    public static function cancelActivation(string $activationId): bool
    {
        $result = self::setStatus($activationId, 8);
        return $result === 'ACCESS_CANCEL';
    }

    // ──────────────────────────────────────────────
    // Finish an activation (setStatus = 6)
    // ──────────────────────────────────────────────
    public static function finishActivation(string $activationId): bool
    {
        $result = self::setStatus($activationId, 6);
        return $result === 'ACCESS_ACTIVATION';
    }

    // ──────────────────────────────────────────────
    // Parse SMS code out of a getStatus response
    // Returns null if no code yet
    // ──────────────────────────────────────────────
    public static function parseSmsCode(string $statusResponse): ?string
    {
        if (str_starts_with($statusResponse, 'STATUS_OK:')) {
            return trim(str_replace('STATUS_OK:', '', $statusResponse));
        }

        if (str_starts_with($statusResponse, 'STATUS_WAIT_RETRY:')) {
            return trim(str_replace('STATUS_WAIT_RETRY:', '', $statusResponse));
        }

        return null;
    }

    // ──────────────────────────────────────────────
    // Get countries with prices for a specific service
    // Used by the countries listing page
    // ──────────────────────────────────────────────
    public static function getCountriesForService(string $serviceCode, float $exchangeRate): array
    {
        $cacheKey = "smsbower_countries_{$serviceCode}";

        $raw = Cache::remember($cacheKey, 300, function () use ($serviceCode) {
            return self::getPrices($serviceCode);
        });

        // Fetch country list for names
        $countryMeta = Cache::remember('smsbower_country_meta', 3600, function () {
            return self::getCountries();
        });

        $countries = [];

        foreach ($raw as $countryId => $services) {
            if (!isset($services[$serviceCode])) continue;

            $svcData    = $services[$serviceCode];
            $costUsd    = (float) ($svcData['cost']  ?? 0);
            $count      = (int)   ($svcData['count'] ?? 0);

            if ($count <= 0 || $costUsd <= 0) continue;

            // Resolve country name + ISO code
            $meta = $countryMeta[$countryId] ?? null;
            $name = $meta['eng'] ?? "Country {$countryId}";

            // Build an ISO2 code from the name (best-effort)
            $isoCode = self::countryIdToIso((string) $countryId, $name);

            $costNgn = round($costUsd * $exchangeRate, 2);

            $countries[] = [
                'country_id'  => (string) $countryId,
                'title'       => $name,
                'code'        => $isoCode,
                'cost_usd'    => $costUsd,
                'cost'        => $costNgn,
                'total_count' => $count,
                'flag_url'    => "https://flagcdn.com/w80/{$isoCode}.png",
            ];
        }

        usort($countries, fn ($a, $b) => $b['total_count'] <=> $a['total_count']);

        return $countries;
    }

    // ──────────────────────────────────────────────
    // Helper: map SMSBower country numeric ID → ISO2
    // SMSBower uses numeric country IDs (same as 5sim / smsactivate standard)
    // ──────────────────────────────────────────────
    public static function countryIdToIso(string $id, string $engName = ''): string
    {
        // Common mappings (SMSBower uses smsactivate-compatible numeric IDs)
        $map = [
            '0'  => 'ru', '1'  => 'ua', '2'  => 'kz', '3'  => 'cn',
            '4'  => 'ph', '5'  => 'mm', '6'  => 'id', '7'  => 'my',
            '8'  => 'ke', '9'  => 'tz', '10' => 'vn', '11' => 'kg',
            '12' => 'us', '13' => 'il', '14' => 'hk', '15' => 'pl',
            '16' => 'gb', '17' => 'mg', '18' => 'cd', '19' => 'ng',
            '20' => 'mo', '21' => 'eg', '22' => 'in', '23' => 'ie',
            '24' => 'kh', '25' => 'la', '26' => 'ht', '27' => 'ci',
            '28' => 'gm', '29' => 'rs', '30' => 'ye', '31' => 'za',
            '32' => 'ro', '33' => 'co', '34' => 'ee', '35' => 'az',
            '36' => 'ca', '37' => 'ma', '38' => 'gh', '39' => 'ar',
            '40' => 'uz', '41' => 'cm', '42' => 'td', '43' => 'de',
            '44' => 'lt', '45' => 'hr', '46' => 'se', '47' => 'iq',
            '48' => 'nl', '49' => 'lv', '50' => 'at', '51' => 'by',
            '52' => 'th', '53' => 'sa', '54' => 'mx', '55' => 'tw',
            '56' => 'es', '57' => 'ir', '58' => 'dz', '59' => 'si',
            '60' => 'bd', '61' => 'sn', '62' => 'tr', '63' => 'cz',
            '64' => 'lk', '65' => 'pe', '66' => 'pk', '67' => 'nz',
            '68' => 'gn', '69' => 'ml', '70' => 've', '71' => 'et',
            '72' => 'mn', '73' => 'br', '74' => 'af', '75' => 'ug',
            '76' => 'ao', '77' => 'cy', '78' => 'fr', '79' => 'pg',
            '80' => 'mz', '81' => 'np', '82' => 'be', '83' => 'bg',
            '84' => 'hu', '85' => 'md', '86' => 'it', '87' => 'py',
            '88' => 'hn', '89' => 'tn', '90' => 'ni', '91' => 'tl',
            '92' => 'bo', '93' => 'cr', '94' => 'gt', '95' => 'ae',
            '96' => 'zw', '97' => 'pr', '98' => 'sd', '99' => 'tg',
            '100'=> 'kw', '101'=> 'sv', '102'=> 'ly', '103'=> 'jm',
            '104'=> 'tt', '105'=> 'ec', '106'=> 'sz', '107'=> 'om',
            '108'=> 'ba', '109'=> 'do', '110'=> 'sy', '111'=> 'ag',
            '112'=> 'am', '113'=> 'sc', '114'=> 'nc', '115'=> 'sb',
            '116'=> 'pa', '117'=> 'mu', '118'=> 'sg', '119'=> 'jo',
            '120'=> 'lu', '121'=> 'gd', '122'=> 'bb', '123'=> 'fj',
            '124'=> 'bi', '125'=> 'jp', '126'=> 'mv', '127'=> 'gy',
            '128'=> 'ge', '129'=> 'bs', '130'=> 'al', '131'=> 'uy',
            '132'=> 'mr', '133'=> 'bt', '134'=> 'ls', '135'=> 'sl',
            '136'=> 'bj', '137'=> 'lr', '138'=> 'rw', '139'=> 'sr',
            '140'=> 'zm', '141'=> 'mw', '142'=> 'dj', '143'=> 'bz',
            '144'=> 'gw', '145'=> 'pt', '146'=> 'ch', '147'=> 'gr',
            '148'=> 'cl', '149'=> 'dk', '150'=> 'fi', '151'=> 'no',
            '152'=> 'au',
        ];

        if (isset($map[$id])) {
            return $map[$id];
        }

        // Fallback: try to derive from English name
        if ($engName) {
            $norm = strtolower(str_replace([' ', '-'], '', $engName));
            $nameMap = [
                'russia'=>'ru','ukraine'=>'ua','china'=>'cn','usa'=>'us',
                'unitedstates'=>'us','unitedkingdom'=>'gb','england'=>'gb',
                'germany'=>'de','france'=>'fr','india'=>'in','nigeria'=>'ng',
                'brazil'=>'br','mexico'=>'mx','canada'=>'ca','australia'=>'au',
                'southafrica'=>'za','turkey'=>'tr','pakistan'=>'pk',
                'indonesia'=>'id','philippines'=>'ph','vietnam'=>'vn',
                'thailand'=>'th','malaysia'=>'my','singapore'=>'sg',
                'japan'=>'jp','southkorea'=>'kr','egypt'=>'eg','ghana'=>'gh',
                'kenya'=>'ke','ethiopia'=>'et','tanzania'=>'tz','uganda'=>'ug',
            ];
            return $nameMap[$norm] ?? 'un';
        }

        return 'un';
    }
}