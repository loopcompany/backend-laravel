<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * تنظیمات کلید/مقدار قابل تغییر از پنل («تنظیمات مالی و تامین کالا»).
 */
class AppSetting extends Model
{
    /** ساعت پایان کار؛ اعتبار استعلام تامین کالا تا این ساعت همان روز است (وقت تهران). */
    public const BUSINESS_END_TIME = 'business_end_time';
    /** درصد پیش‌فرض پیش‌پرداخت تامین کالا. */
    public const PROCUREMENT_PREPAYMENT_PERCENT = 'procurement_prepayment_percent';
    /** درصد پیش‌فرض پیش‌پرداخت محصول اعزام‌شده به لوپ. */
    public const LOOP_PREPAYMENT_PERCENT = 'loop_prepayment_percent';
    /** حداقل مبلغ برداشت پورسانت مأمور خرید به شبا (تومان). */
    public const REFERRAL_MIN_WITHDRAW_AMOUNT = 'referral_min_withdraw_amount';

    public const DEFAULTS = [
        self::BUSINESS_END_TIME => '17:00',
        self::PROCUREMENT_PREPAYMENT_PERCENT => 60,
        self::LOOP_PREPAYMENT_PERCENT => 60,
        self::REFERRAL_MIN_WITHDRAW_AMOUNT => 500000,
    ];

    private const CACHE_KEY = 'app-settings';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $all = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            $all = [];
        }

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function int(string $key): int
    {
        return (int) static::get($key);
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        Cache::forget(self::CACHE_KEY);
    }
}
