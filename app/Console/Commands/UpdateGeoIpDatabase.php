<?php

namespace App\Console\Commands;

use App\Services\Security\GeoIpLocator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use MaxMind\Db\Reader;
use Throwable;

/**
 * دانلود دیتابیس GeoIP محلی (پیش‌فرض DB-IP City Lite، رایگان و بدون کلید؛ ماهانه به‌روز می‌شود).
 * با --file می‌توان یک فایل mmdb (یا mmdb.gz، مثلاً GeoLite2-City) را مستقیم نصب کرد.
 */
class UpdateGeoIpDatabase extends Command
{
    protected $signature = 'geoip:update {--file= : فایل mmdb یا mmdb.gz آماده} {--url= : آدرس دانلود}';

    protected $description = 'دانلود/نصب دیتابیس GeoIP برای «موقعیت تقریبی» دستگاه‌ها';

    public function handle(): int
    {
        $target = GeoIpLocator::databasePath();
        @mkdir(dirname($target), 0775, true);
        $tmp = $target . '.download';

        try {
            if ($file = $this->option('file')) {
                if (!is_readable($file)) {
                    $this->error("فایل {$file} پیدا نشد.");

                    return self::FAILURE;
                }
                $this->extract($file, $tmp);
            } elseif (!$this->download($tmp . '.gz') || !$this->extract($tmp . '.gz', $tmp)) {
                return self::FAILURE;
            }

            // فایل خراب جایگزین دیتابیس سالم نمی‌شود
            (new Reader($tmp))->close();
            rename($tmp, $target);
        } catch (Throwable $e) {
            $this->error('نصب دیتابیس ناموفق بود: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            @unlink($tmp);
            @unlink($tmp . '.gz');
        }

        $this->info("دیتابیس GeoIP در {$target} نصب شد.");

        return self::SUCCESS;
    }

    private function download(string $path): bool
    {
        $template = $this->option('url') ?: config('services.geoip.download_url');

        // نسخه‌ی ماه جاری ممکن است هنوز منتشر نشده باشد؛ ماه قبل هم امتحان می‌شود
        foreach ([now(), now()->subMonthNoOverflow()] as $month) {
            $url = str_replace(['{Y}', '{m}'], [$month->format('Y'), $month->format('m')], $template);
            $this->line("دانلود {$url}");

            try {
                $response = Http::timeout(600)->sink($path)->get($url);
                if ($response->successful()) {
                    return true;
                }
                $this->warn("HTTP {$response->status()}");
            } catch (Throwable $e) {
                $this->warn($e->getMessage());
            }
        }

        $this->error('دانلود دیتابیس GeoIP ناموفق بود.');

        return false;
    }

    private function extract(string $source, string $target): bool
    {
        $in = str_ends_with($source, '.gz') ? gzopen($source, 'rb') : fopen($source, 'rb');
        $out = fopen($target, 'wb');

        while (!(str_ends_with($source, '.gz') ? gzeof($in) : feof($in))) {
            fwrite($out, str_ends_with($source, '.gz') ? gzread($in, 1 << 20) : fread($in, 1 << 20));
        }

        str_ends_with($source, '.gz') ? gzclose($in) : fclose($in);
        fclose($out);

        return true;
    }
}
