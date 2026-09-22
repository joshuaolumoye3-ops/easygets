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
        'max_buy_quantity'        => '5',
        'daily_buy_limit_ngn'     => '500000',
    ];

    public static function all_(): array
    {
        return Cache::remember('giftcard_settings', 300, function () {
            try {
                return array_merge(self::DEFAULTS, self::query()->pluck('value', 'key')->all());
            } catch (\Throwable $e) {
                return self::DEFAULTS;
            }
        });
    }

    public static function get(string $key, $default = null)
    {
        return self::all_()[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function set(string $key, $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget('giftcard_settings');
    }
}