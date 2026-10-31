<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GiftcardSetting extends Model
{
    protected $table = 'giftcard_settings';
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'buy_usd_rate'            => '1500',
        'buy_markup_percent'      => '5',
        'sell_commission_percent' => '3',
        'min_withdrawal'          => '1000',
        'withdrawal_fee_up_to_5000' => '0',
        'withdrawal_fee_5001_to_50000' => '0',
        'withdrawal_fee_over_50000' => '0',
        'max_buy_quantity'        => '5',
        'daily_buy_limit_ngn'     => '500000',
    ];

    public static function all_(): array
    {
        return Cache::remember('giftcard_settings', 300, function () {
            try {
                $stored = self::query()->pluck('value', 'key')->all();
                $settings = array_merge(self::DEFAULTS, $stored);

                // Preserve a previously configured flat fee until admins set tier fees.
                $tierKeys = [
                    'withdrawal_fee_up_to_5000',
                    'withdrawal_fee_5001_to_50000',
                    'withdrawal_fee_over_50000',
                ];
                if (!array_intersect($tierKeys, array_keys($stored)) && array_key_exists('withdrawal_fee_flat', $stored)) {
                    foreach ($tierKeys as $key) {
                        $settings[$key] = $stored['withdrawal_fee_flat'];
                    }
                }

                return $settings;
            } catch (\Throwable $e) {
                return self::DEFAULTS;
            }
        });
    }

    public static function get(string $key, $default = null)
    {
        return self::all_()[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function withdrawalFeeTiers(): array
    {
        return [
            'up_to_5000' => (float) self::get('withdrawal_fee_up_to_5000', 0),
            '5001_to_50000' => (float) self::get('withdrawal_fee_5001_to_50000', 0),
            'over_50000' => (float) self::get('withdrawal_fee_over_50000', 0),
        ];
    }

    public static function withdrawalFeeForAmount(float $amount, ?array $tiers = null): float
    {
        $tiers ??= self::withdrawalFeeTiers();

        $fee = match (true) {
            $amount <= 5000 => $tiers['up_to_5000'] ?? 0,
            $amount <= 50000 => $tiers['5001_to_50000'] ?? 0,
            default => $tiers['over_50000'] ?? 0,
        };

        return round(max(0, (float) $fee), 2);
    }

    public static function set(string $key, $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget('giftcard_settings');
    }
}