<?php

namespace App\Support;

use App\Models\Technician;

class TechnicianRestriction
{
    public const TITLE = 'صفحه محدودیت یا غیرفعال شدن';
    public const MESSAGE = 'حساب کاربری شما غیرفعال شده است.';
    public const INSTRUCTION = 'جهت بازگشت به شرایط عادی لازم است ظرف مدت ۲۴ ساعت، به‌صورت حضوری به لوپ مراجعه فرمایید.';

    public const REASONS = [
        'امتیازات و نظرات منفی زیادی نسبت به قبل دارید.',
        'موارد منفی انضباطی زیادی دارید.',
        'مهارت کمتری دارید و می‌بایست تحت آموزش لوپ باشید.',
    ];

    public static function reasonOptions(): array
    {
        return array_combine(self::REASONS, self::REASONS);
    }

    public static function payload(Technician $technician): array
    {
        $reason = trim((string) $technician->limit_access_reason);

        return [
            'title' => self::TITLE,
            'reason' => $reason !== '' ? $reason : 'دسترسی شما توسط مدیریت محدود شده است.',
            'instruction' => self::INSTRUCTION,
            'suspended_at' => $technician->suspended_at?->toISOString(),
        ];
    }

    public static function error(Technician $technician): array
    {
        return [
            'success' => false,
            'message' => self::MESSAGE,
            'error_code' => 'ACCOUNT_DISABLED',
            'restriction' => self::payload($technician),
        ];
    }
}
