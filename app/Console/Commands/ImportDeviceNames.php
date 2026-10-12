<?php

namespace App\Console\Commands;

use App\Models\DeviceMarketingName;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * ورود فهرست رسمی دستگاه‌های Google Play (ستون‌های Retail Branding, Marketing Name, Device, Model).
 * فایل UTF-16 است. دانلود مستقیم از سرورهای داخل ایران 403 می‌دهد؛ در این حالت فایل را جای دیگری
 * دانلود کنید و با --file بدهید. هر اجرا کل جدول را جایگزین می‌کند.
 */
class ImportDeviceNames extends Command
{
    protected $signature = 'devices:import-names {--file= : مسیر فایل supported_devices.csv} {--url= : آدرس دانلود (پیش‌فرض فهرست Google Play)}';

    protected $description = 'ورود نام تجاری دستگاه‌ها (مثل SM-A225F → Galaxy A22) برای امنیت حساب';

    public function handle(): int
    {
        $raw = $this->readSource();
        if ($raw === null) {
            return self::FAILURE;
        }

        $rows = $this->parse($raw);
        if ($rows === []) {
            $this->error('هیچ ردیف معتبری در فایل پیدا نشد.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($rows) {
            DeviceMarketingName::query()->delete();

            foreach (array_chunk($rows, 1000) as $chunk) {
                DeviceMarketingName::insert($chunk);
            }
        });

        $this->info(count($rows) . ' مدل دستگاه وارد شد.');

        return self::SUCCESS;
    }

    private function readSource(): ?string
    {
        if ($file = $this->option('file')) {
            if (!is_readable($file)) {
                $this->error("فایل {$file} پیدا نشد.");

                return null;
            }

            return file_get_contents($file);
        }

        $url = $this->option('url') ?: config('services.device_names.csv_url');

        try {
            $response = Http::timeout(120)->get($url);
        } catch (Throwable $e) {
            $this->error('دانلود ناموفق بود: ' . $e->getMessage());

            return null;
        }

        if (!$response->successful()) {
            $this->error("دانلود ناموفق بود (HTTP {$response->status()}). فایل را دستی دانلود کنید و با --file بدهید.");

            return null;
        }

        return $response->body();
    }

    /** @return list<array{model: string, brand: ?string, marketing_name: string}> */
    private function parse(string $raw): array
    {
        if (str_starts_with($raw, "\xFF\xFE") || str_starts_with($raw, "\xFE\xFF") || substr_count(substr($raw, 0, 200), "\0") > 20) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-16');
        }
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

        $lines = preg_split('/\r\n|\n|\r/', $raw);
        $header = array_map(fn ($h) => strtolower(trim($h)), str_getcsv(array_shift($lines) ?? ''));
        $brandIdx = array_search('retail branding', $header, true);
        $nameIdx = array_search('marketing name', $header, true);
        $modelIdx = array_search('model', $header, true);

        if ($nameIdx === false || $modelIdx === false) {
            $this->error('ستون‌های «Marketing Name» و «Model» در فایل نیست.');

            return [];
        }

        $rows = [];
        $seen = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cols = str_getcsv($line);
            $model = trim($cols[$modelIdx] ?? '');
            $name = trim($cols[$nameIdx] ?? '');

            // برای هر مدل اولین ردیف کافی است
            $key = strtolower($model);
            if ($model === '' || $name === '' || isset($seen[$key]) || mb_strlen($model) > 100) {
                continue;
            }
            $seen[$key] = true;

            $rows[] = [
                'model' => $model,
                'brand' => $brandIdx !== false ? (mb_substr(trim($cols[$brandIdx] ?? ''), 0, 100) ?: null) : null,
                'marketing_name' => mb_substr($name, 0, 150),
            ];
        }

        return $rows;
    }
}
