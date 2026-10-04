<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Technician;

/**
 * اطلاعاتی از تکنسین که مجاز است به مشتری (اپ کاربر/سازمانی) برگردد.
 *
 * - فقط فیلدهای همین فهرست سریالایز می‌شوند (کد ملی، اطلاعات بانکی، آدرس منزل، کیف پول و ... هرگز).
 * - شماره تماس تکنسین فقط تا وقتی سفارش جاری است (status 0 یا 1) فرستاده می‌شود؛
 *   بعد از انجام یا لغو سفارش، کلید phone اصلاً در پاسخ نمی‌آید.
 */
class CustomerFacingTechnician
{
    public const VISIBLE = [
        'id',
        'name',
        'phone',
        'technician_type',
        'referral_code',
        'profile_photo_path',
        'created_at',
        'is_online',
        'average_rating',
        'completed_orders_count',
    ];

    public const ACTIVE_ORDER_STATUSES = [0, 1];

    public static function isActiveOrderStatus(mixed $status): bool
    {
        return $status !== null && in_array((int) $status, self::ACTIVE_ORDER_STATUSES, true);
    }

    public static function prepare(?Technician $technician, bool $withPhone): ?Technician
    {
        if ($technician === null) {
            return null;
        }

        $visible = $withPhone ? self::VISIBLE : array_values(array_diff(self::VISIBLE, ['phone']));

        return $technician->setVisible($visible);
    }

    /** تکنسینِ بارگذاری‌شده‌ی سفارش را برای ارسال به مشتری آماده می‌کند. */
    public static function forOrder(Order $order): Order
    {
        if ($order->relationLoaded('technician')) {
            self::prepare($order->technician, self::isActiveOrderStatus($order->status));
        }

        return $order;
    }
}
