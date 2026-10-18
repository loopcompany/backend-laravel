<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * تشخیص نسخه‌ی مناسب نصب‌کننده‌ی ویندوز برای بازدیدکننده (حدس اولیه‌ی سمت سرور).
 *
 * کروم/اج از نسخه‌ی ۱۰۷ به بعد User-Agent را روی «Windows NT 10.0; Win64; x64» ثابت می‌کنند،
 * پس اگر مرورگر Client Hints بفرستد (Sec-CH-UA-Platform-Version / Sec-CH-UA-Bitness) همان‌ها ملاک است
 * و User-Agent فقط برای مرورگرهای بدون Client Hints (مثل فایرفاکس) استفاده می‌شود.
 * اسکریپت صفحه همین منطق را با navigator.userAgentData دوباره و دقیق‌تر اجرا می‌کند.
 */
class WindowsBuildDetector
{
    /** هدرهای Client Hints که برای تشخیص لازم است (در پاسخ صفحه با Accept-CH درخواست می‌شوند) */
    public const CLIENT_HINTS = 'Sec-CH-UA-Platform, Sec-CH-UA-Platform-Version, Sec-CH-UA-Bitness, Sec-CH-UA-Arch';

    /**
     * @return array{is_windows: bool, family: string, bits: string, key: string}
     *         family: win7 (ویندوز ۷/۸/۸.۱) | win10 (ویندوز ۱۰/۱۱) ، bits: 64 | 32
     */
    public static function detect(Request $request): array
    {
        $ua = (string) $request->userAgent();
        $platform = trim((string) $request->header('Sec-CH-UA-Platform'), '" ');
        $platformVersion = trim((string) $request->header('Sec-CH-UA-Platform-Version'), '" ');
        $bitness = trim((string) $request->header('Sec-CH-UA-Bitness'), '" ');

        $isWindows = $platform !== '' ? strcasecmp($platform, 'Windows') === 0 : str_contains($ua, 'Windows NT');

        // ویندوز ۷/۸/۸.۱ در Client Hints نسخه‌ی پلتفرم «0.x» دارند؛ ویندوز ۱۰ از 1 تا 10 و ویندوز ۱۱ از 13 به بعد
        if ($platformVersion !== '') {
            $family = (int) explode('.', $platformVersion)[0] === 0 ? 'win7' : 'win10';
        } else {
            $family = preg_match('/Windows NT 6\.[0-3]/', $ua) ? 'win7' : 'win10';
        }

        if ($bitness !== '') {
            $bits = $bitness === '32' ? '32' : '64';
        } else {
            // بدون نشانه‌ی ۶۴ بیتی در User-Agent، سیستم ۳۲ بیتی فرض می‌شود (مثل «Windows NT 6.1» در فایرفاکس ۳۲ بیتی)
            $bits = ! $isWindows || preg_match('/Win64|WOW64|x64|amd64|arm64|aarch64/i', $ua) ? '64' : '32';
        }

        return [
            'is_windows' => $isWindows,
            'family' => $family,
            'bits' => $bits,
            'key' => $family.'-'.$bits,
        ];
    }
}
