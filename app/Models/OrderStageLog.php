<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تاریخچه‌ی سفارش: هر تغییر مرحله / وضعیت با مسئول، زمان و علت (بند ۱۲-۴). حذف نمی‌شود (۱۲-۶).
 */
class OrderStageLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTOR_TYPES = [
        'admin' => 'پنل',
        'user' => 'کاربر',
        'technician' => 'تکنسین',
        'system' => 'سیستم',
    ];

    public const ACTIONS = [
        'created' => 'ثبت سفارش',
        'classified' => 'تعیین دسته‌ی درخواست و نوع کاربر',
        'review_incomplete' => 'بازگشت برای تکمیل اطلاعات',
        'review_approved' => 'تأیید اطلاعات سفارش',
        'routed_to_loop' => 'هدایت مستقیم به ارجاع دستگاه',
        'quote_created' => 'ثبت قیمت',
        'quote_sent' => 'ارسال قیمت برای کاربر',
        'quote_discarded' => 'رد پیشنهاد قیمت توسط پنل',
        'quote_accepted' => 'تأیید قیمت',
        'quote_rejected' => 'رد قیمت',
        'requote_proposed' => 'پیشنهاد قیمت جدید',
        'requote_requested' => 'نیاز به قیمت‌گذاری مجدد',
        'requote_resolved' => 'تعیین مسیر پس از رد قیمت',
        'technician_assigned' => 'تخصیص تکنسین',
        'technician_accepted' => 'تأیید مأموریت توسط تکنسین',
        'technician_rejected' => 'عدم امکان انجام مأموریت',
        'set_off' => 'حرکت تکنسین',
        'arrived' => 'رسیدن تکنسین',
        'visit_outcome' => 'نتیجه‌ی بررسی در محل',
        'service_started' => 'شروع خدمت در محل',
        'service_completed' => 'پایان خدمت در محل',
        'transfer_requested' => 'تصمیم به ارجاع دستگاه',
        'transfer_started' => 'ارجاع دستگاه به شرکت',
        'transfer_received' => 'تحویل دستگاه در شرکت',
        'final_price' => 'ثبت قیمت نهایی',
        'file_closed' => 'مختومه شدن پرونده',
        'cancelled' => 'لغو سفارش',
        'device_returned' => 'عودت دستگاه',
        'status_changed' => 'تغییر وضعیت',
    ];

    protected $fillable = [
        'order_id', 'from_stage', 'to_stage', 'action',
        'actor_type', 'actor_id', 'actor_name', 'note', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
