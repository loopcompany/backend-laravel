<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Province;
use App\Models\Region;

class UserCodeHelper
{
    /**
     * کدهای نوع کاربر
     */
    private const TYPE_CODES = [
        'user' => [
            'simple' => '05',
            'special' => '005',
        ],
        'organization' => [
            'simple' => '09',
            'special' => '095',
        ],
        'company' => [
            'simple' => '07',
            'special' => '008',
        ],
    ];

    /**
     * تولید کد یونیک کاربر
     * 
     * فرمت: [provinceCode + regionCode]-[typeCode + randomDigits]
     * مثال: 2118-05327
     * 
     * @param User $user کاربر
     * @param bool|null $isSpecial آیا کاربر ویژه است؟ (null = از روی user->is_special)
     * @return string کد تولید شده
     */
    public static function generateUserCode(User $user, ?bool $isSpecial = null): string
    {
        $maxAttempts = 100;
        $attempt = 0;

        do {
            $code = self::buildCode($user, $isSpecial);
            $attempt++;

            // چک کردن یونیک بودن
            $exists = User::where('code', $code)
                ->where('id', '!=', $user->id ?? 0)
                ->exists();

            if (!$exists) {
                return $code;
            }

        } while ($attempt < $maxAttempts);

        // اگر بعد از 100 بار نتوانست کد یونیک تولید کند، timestamp اضافه می‌کنیم
        return self::buildCode($user, $isSpecial) . substr(time(), -2);
    }

    /**
     * ساخت کد بر اساس اطلاعات کاربر
     * 
     * @param User $user
     * @param bool|null $isSpecial
     * @return string
     */
    private static function buildCode(User $user, ?bool $isSpecial = null): string
    {
        // بخش اول: کد استان + کد منطقه
        $locationCode = self::getLocationCode($user);

        // بخش دوم: کد نوع کاربر + 3 رقم تصادفی
        $typeCode = self::getTypeCode($user, $isSpecial);
        $randomDigits = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);

        return "{$locationCode}-{$typeCode}{$randomDigits}";
    }

    /**
     * دریافت کد موقعیت جغرافیایی (استان + منطقه)
     * 
     * @param User $user
     * @return string مثال: "2118" برای تهران منطقه 18
     */
    private static function getLocationCode(User $user): string
    {
        $provinceCode = '00';
        $regionCode = '00';

        // دریافت کد استان
        if ($user->province_id) {
            $province = Province::find($user->province_id);
            if ($province && $province->code) {
                // حذف صفرهای ابتدایی (مثلاً 021 → 21)
                $provinceCode = ltrim($province->code, '0');
                // اگر خالی شد، از کد اصلی استفاده کن
                if ($provinceCode === '') {
                    $provinceCode = $province->code;
                }
                // حداکثر 2 رقم
                $provinceCode = str_pad(substr($provinceCode, 0, 2), 2, '0', STR_PAD_LEFT);
            }
        }

        // دریافت کد منطقه
        if ($user->region_id) {
            $region = Region::find($user->region_id);
            if ($region && $region->code) {
                // اگر تک رقمی بود، پد کن (مثلاً 9 → 09)
                $regionCode = str_pad($region->code, 2, '0', STR_PAD_LEFT);
            }
        }

        return $provinceCode . $regionCode;
    }

    /**
     * دریافت کد نوع کاربر
     * 
     * @param User $user
     * @param bool|null $isSpecial
     * @return string مثال: "05" برای کاربر عادی
     */
    private static function getTypeCode(User $user, ?bool $isSpecial = null): string
    {
        // اگر isSpecial مشخص نشده، از روی user استفاده کن
        $isSpecial = $isSpecial ?? ($user->is_special ?? false);

        // تعیین نوع کاربر (پیش‌فرض: user)
        $accountType = $user->account_type ?? 'user';

        // اگر نوع کاربر معتبر نیست، پیش‌فرض user
        if (!isset(self::TYPE_CODES[$accountType])) {
            $accountType = 'user';
        }

        // بازگشت کد متناسب
        $codeType = $isSpecial ? 'special' : 'simple';
        return self::TYPE_CODES[$accountType][$codeType];
    }

    /**
     * بررسی معتبر بودن فرمت کد کاربر
     * 
     * @param string $code
     * @return bool
     */
    public static function isValidFormat(string $code): bool
    {
        // فرمت: 4 رقم - 5 یا 6 رقم
        // مثال: 2118-05327 یا 2118-095123
        return (bool) preg_match('/^\d{4}-\d{5,6}$/', $code);
    }

    /**
     * استخراج اطلاعات از کد کاربر
     * 
     * @param string $code
     * @return array|null
     */
    public static function parseCode(string $code): ?array
    {
        if (!self::isValidFormat($code)) {
            return null;
        }

        [$location, $typeAndRandom] = explode('-', $code);

        $provinceCode = substr($location, 0, 2);
        $regionCode = substr($location, 2, 2);

        // تشخیص نوع کاربر
        $userType = null;
        $isSpecial = false;

        foreach (self::TYPE_CODES as $type => $codes) {
            if (str_starts_with($typeAndRandom, $codes['simple'])) {
                $userType = $type;
                $isSpecial = false;
                break;
            }
            if (str_starts_with($typeAndRandom, $codes['special'])) {
                $userType = $type;
                $isSpecial = true;
                break;
            }
        }

        return [
            'province_code' => $provinceCode,
            'region_code' => $regionCode,
            'user_type' => $userType,
            'is_special' => $isSpecial,
            'full_code' => $code,
        ];
    }
}
