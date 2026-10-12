<?php

namespace App\Services\Security;

use App\Models\DeviceMarketingName;
use App\Support\KnownDeviceNames;
use Throwable;

/**
 * نام تجاری دستگاه از روی کد مدل، مثل SM-A225F → «Galaxy A22» (بدون برند؛ اپ برند را خودش اضافه می‌کند).
 * iOS لازم ندارد چون device_model خودش نام تجاری است («iPhone 13»).
 */
class DeviceNameResolver
{
    public function resolve(?string $brand, ?string $model, ?string $platform = null): ?string
    {
        $model = trim((string) $model);

        if ($model === '' || $platform === 'ios' || $platform === 'web' || str_starts_with(strtolower($model), 'iphone')) {
            return null;
        }

        // «SM-A225F/DSN» و «sm-a225f» هر دو به یک مدل می‌رسند
        $candidates = array_unique([$model, preg_replace('#/.*$#', '', $model)]);

        foreach ($candidates as $candidate) {
            $name = $this->lookup($candidate) ?? KnownDeviceNames::find($candidate);

            if ($name !== null) {
                return $this->clean($name, $brand, $candidate);
            }
        }

        return null;
    }

    private function lookup(string $model): ?string
    {
        try {
            return DeviceMarketingName::query()
                ->whereRaw('LOWER(model) = ?', [strtolower($model)])
                ->orderBy('id')
                ->value('marketing_name');
        } catch (Throwable) {
            return null; // جدول هنوز migrate نشده
        }
    }

    private function clean(string $name, ?string $brand, string $model): ?string
    {
        $name = trim($name);
        $brand = trim((string) $brand);

        if ($brand !== '' && stripos($name, $brand . ' ') === 0) {
            $name = trim(substr($name, strlen($brand)));
        }

        if ($name === '' || strcasecmp($name, $model) === 0) {
            return null;
        }

        return mb_substr($name, 0, 100);
    }
}
