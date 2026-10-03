<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Morilog\Jalali\Jalalian;

/**
 * سقف سال تولد برای ثبت‌نام تکنسین.
 *
 * فقط متولدین «سال جاری شمسی منهای ۱۸» و قبل از آن مجاز به ثبت‌نام هستند و این سقف
 * هر سال در اول فروردین یک سال جلو می‌رود (۱۴۰۵ ← ۱۳۸۷، ۱۴۰۶ ← ۱۳۸۸).
 *
 * تاریخ هم به‌صورت شمسی (1387/05/12 یا 1387-05-12) و هم میلادی (2008-08-02) پذیرفته می‌شود.
 */
class TechnicianBirthYear implements ValidationRule
{
    public const MIN_AGE_YEARS = 18;

    public static function maxAllowedJalaliYear(): int
    {
        return Jalalian::now()->getYear() - self::MIN_AGE_YEARS;
    }

    /**
     * سال تولد شمسی را از رشته‌ی تاریخ استخراج می‌کند؛ در صورت نامعتبر بودن null برمی‌گرداند.
     */
    public static function jalaliYearOf(?string $date): ?int
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        $normalized = strtr(trim($date), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        if (!preg_match('/^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})/', $normalized, $m)) {
            return null;
        }

        [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];

        // سال‌های زیر ۱۷۰۰ شمسی فرض می‌شوند
        if ($year < 1700) {
            return ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) ? $year : null;
        }

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return Jalalian::fromDateTime(sprintf('%04d-%02d-%02d', $year, $month, $day))->getYear();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $year = self::jalaliYearOf((string) $value);

        if ($year === null) {
            $fail('تاریخ تولد نامعتبر است.');
            return;
        }

        $maxYear = self::maxAllowedJalaliYear();

        if ($year > $maxYear) {
            $fail("ثبت‌نام تکنسین فقط برای متولدین سال {$maxYear} و قبل از آن امکان‌پذیر است.");
        }
    }
}
