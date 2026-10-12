<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MaxMind\Db\Reader;
use Throwable;

/**
 * موقعیت تقریبی یک IP از دیتابیس GeoIP محلی (DB-IP lite یا MaxMind GeoLite2، فرمت mmdb).
 * هیچ سرویس بیرونی صدا زده نمی‌شود؛ دیتابیس با `php artisan geoip:update` دانلود می‌شود.
 *
 * خروجی یک رشته‌ی آماده‌ی نمایش است، مثل «تهران، ایران». برای IP خصوصی/رزرو،
 * نبودِ دیتابیس یا پیدا نشدن، null برمی‌گرداند.
 */
class GeoIpLocator
{
    /** استان‌های ایران (نام انگلیسی نرمال‌شده ← نام فارسی). */
    private const IRAN_PROVINCES = [
        'tehran' => 'تهران',
        'alborz' => 'البرز',
        'razavi khorasan' => 'خراسان رضوی',
        'khorasan razavi' => 'خراسان رضوی',
        'north khorasan' => 'خراسان شمالی',
        'south khorasan' => 'خراسان جنوبی',
        'isfahan' => 'اصفهان',
        'esfahan' => 'اصفهان',
        'fars' => 'فارس',
        'east azerbaijan' => 'آذربایجان شرقی',
        'west azerbaijan' => 'آذربایجان غربی',
        'ardabil' => 'اردبیل',
        'khuzestan' => 'خوزستان',
        'mazandaran' => 'مازندران',
        'gilan' => 'گیلان',
        'golestan' => 'گلستان',
        'kerman' => 'کرمان',
        'kermanshah' => 'کرمانشاه',
        'qom' => 'قم',
        'markazi' => 'مرکزی',
        'hamadan' => 'همدان',
        'lorestan' => 'لرستان',
        'zanjan' => 'زنجان',
        'qazvin' => 'قزوین',
        'semnan' => 'سمنان',
        'yazd' => 'یزد',
        'hormozgan' => 'هرمزگان',
        'bushehr' => 'بوشهر',
        'kurdistan' => 'کردستان',
        'kordestan' => 'کردستان',
        'ilam' => 'ایلام',
        'chaharmahal and bakhtiari' => 'چهارمحال و بختیاری',
        'kohgiluyeh and boyer ahmad' => 'کهگیلویه و بویراحمد',
        'kohgiluyeh and boyerahmad' => 'کهگیلویه و بویراحمد',
        'sistan and baluchestan' => 'سیستان و بلوچستان',
    ];

    /** شهرهای پرجمعیت؛ اگر شهر در این فهرست نبود، نام استان نمایش داده می‌شود. */
    private const IRAN_CITIES = [
        'tehran' => 'تهران',
        'mashhad' => 'مشهد',
        'isfahan' => 'اصفهان',
        'esfahan' => 'اصفهان',
        'karaj' => 'کرج',
        'shiraz' => 'شیراز',
        'tabriz' => 'تبریز',
        'qom' => 'قم',
        'ahvaz' => 'اهواز',
        'ahwaz' => 'اهواز',
        'kermanshah' => 'کرمانشاه',
        'urmia' => 'ارومیه',
        'orumiyeh' => 'ارومیه',
        'rasht' => 'رشت',
        'zahedan' => 'زاهدان',
        'kerman' => 'کرمان',
        'hamadan' => 'همدان',
        'yazd' => 'یزد',
        'ardabil' => 'اردبیل',
        'bandar abbas' => 'بندرعباس',
        'arak' => 'اراک',
        'zanjan' => 'زنجان',
        'sanandaj' => 'سنندج',
        'qazvin' => 'قزوین',
        'khorramabad' => 'خرم‌آباد',
        'gorgan' => 'گرگان',
        'sari' => 'ساری',
        'bushehr' => 'بوشهر',
        'birjand' => 'بیرجند',
        'ilam' => 'ایلام',
        'semnan' => 'سمنان',
        'yasuj' => 'یاسوج',
        'shahr e kord' => 'شهرکرد',
        'shahrekord' => 'شهرکرد',
        'bojnurd' => 'بجنورد',
        'kashan' => 'کاشان',
        'babol' => 'بابل',
        'amol' => 'آمل',
        'qods' => 'قدس',
        'eslamshahr' => 'اسلامشهر',
        'islamshahr' => 'اسلامشهر',
        'shahriar' => 'شهریار',
        'pakdasht' => 'پاکدشت',
        'varamin' => 'ورامین',
    ];

    private ?Reader $reader = null;
    private bool $readerFailed = false;

    public static function databasePath(): string
    {
        return (string) config('services.geoip.database', storage_path('app/geoip/dbip-city-lite.mmdb'));
    }

    public function locate(?string $ip): ?string
    {
        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        $reader = $this->reader();
        if (!$reader) {
            return null;
        }

        try {
            $record = $reader->get($ip);
        } catch (Throwable $e) {
            Log::warning('geoip lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);

            return null;
        }

        return is_array($record) ? $this->format($record) : null;
    }

    /** @param array<string, mixed> $record */
    private function format(array $record): ?string
    {
        $countryCode = $record['country']['iso_code'] ?? null;
        $countryFa = $record['country']['names']['fa'] ?? null;
        $countryEn = $record['country']['names']['en'] ?? null;
        $cityEn = $record['city']['names']['en'] ?? null;
        $regionEn = $record['subdivisions'][0]['names']['en'] ?? null;

        if ($countryCode === 'IR') {
            $place = self::IRAN_CITIES[$this->normalize($cityEn)] ?? self::IRAN_PROVINCES[$this->normalize($regionEn)] ?? null;

            return $place ? "{$place}، ایران" : 'ایران';
        }

        if ($countryFa) {
            return $countryFa;
        }

        $parts = array_filter([$cityEn ? preg_replace('/\s*\(.*\)$/', '', $cityEn) : null, $countryEn]);

        return $parts ? implode(', ', $parts) : null;
    }

    /** «Tehrān (District 6)» → «tehran»، «Kohgīlūyeh and Bowyer Aḩmad Province» → … */
    private function normalize(?string $name): string
    {
        if (!$name) {
            return '';
        }

        $name = preg_replace('/\s*\(.*\)$/', '', $name);
        $name = strtolower(Str::ascii($name));
        $name = str_replace(['bowyer', 'boyer-ahmad', 'boir'], ['boyer', 'boyer ahmad', 'boyer'], $name);
        $name = preg_replace('/\b(province|ostan|ostan-e)\b/', '', $name);
        $name = preg_replace('/[^a-z]+/', ' ', $name);

        return trim(preg_replace('/\s+/', ' ', $name));
    }

    private function reader(): ?Reader
    {
        if ($this->reader || $this->readerFailed) {
            return $this->reader;
        }

        $path = self::databasePath();
        if (!is_file($path)) {
            $this->readerFailed = true;

            return null;
        }

        try {
            return $this->reader = new Reader($path);
        } catch (Throwable $e) {
            $this->readerFailed = true;
            Log::warning('geoip database could not be opened', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
