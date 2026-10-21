<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * جزئیات فرآیند پنل برای هر سفارش (سند «فرآیند ثبت، بررسی، قیمت‌گذاری و ارجاع سفارش»).
 * مرحله‌ی فعلی روی orders.workflow_stage است تا در لیست و API قابل فیلتر باشد.
 */
class OrderWorkflow extends Model
{
    // مراحل فرآیند (ترتیب همین آرایه، ترتیب استپر پنل است)
    public const STAGE_REVIEW = 'review';
    public const STAGE_QUOTING = 'quoting';
    public const STAGE_ASSIGNMENT = 'assignment';
    public const STAGE_VISIT = 'visit';
    public const STAGE_REQUOTE = 'requote';
    public const STAGE_ONSITE_SERVICE = 'onsite_service';
    public const STAGE_TRANSFER = 'transfer';
    public const STAGE_CLOSED = 'closed';
    public const STAGE_CANCELLED = 'cancelled';

    public const STAGES = [
        self::STAGE_REVIEW => 'بررسی اطلاعات',
        self::STAGE_QUOTING => 'قیمت‌گذاری اولیه و زمان‌بندی',
        self::STAGE_ASSIGNMENT => 'تخصیص تکنسین',
        self::STAGE_VISIT => 'مراجعه و بررسی در محل',
        self::STAGE_REQUOTE => 'قیمت‌گذاری مجدد',
        self::STAGE_ONSITE_SERVICE => 'انجام خدمت در محل',
        self::STAGE_TRANSFER => 'ارجاع دستگاه به شرکت',
        self::STAGE_CLOSED => 'پایان سفارش',
        self::STAGE_CANCELLED => 'لغو شده',
    ];

    /**
     * رنگ هر مرحله؛ در تب‌ها، نشان وضعیت ردیف، نوار کنار ردیف و استپر یکسان است.
     * نام‌ها در AdminPanelProvider به‌عنوان رنگ Filament ثبت شده‌اند.
     */
    public const STAGE_COLORS = [
        self::STAGE_REVIEW => 'amber',
        self::STAGE_QUOTING => 'orange',
        self::STAGE_ASSIGNMENT => 'sky',
        self::STAGE_VISIT => 'blue',
        self::STAGE_REQUOTE => 'pink',
        self::STAGE_ONSITE_SERVICE => 'violet',
        self::STAGE_TRANSFER => 'teal',
        self::STAGE_CLOSED => 'emerald',
        self::STAGE_CANCELLED => 'red',
    ];

    /** رنگ‌های استپر و نوار ردیف: [اصلی، زمینه‌ی روشن، متن تیره] (پالت Tailwind) */
    public const STAGE_HEX = [
        'registered' => ['#64748b', '#f8fafc', '#334155'],
        self::STAGE_REVIEW => ['#f59e0b', '#fffbeb', '#b45309'],
        self::STAGE_QUOTING => ['#f97316', '#fff7ed', '#c2410c'],
        self::STAGE_ASSIGNMENT => ['#0ea5e9', '#f0f9ff', '#0369a1'],
        self::STAGE_VISIT => ['#3b82f6', '#eff6ff', '#1d4ed8'],
        self::STAGE_REQUOTE => ['#ec4899', '#fdf2f8', '#be185d'],
        self::STAGE_ONSITE_SERVICE => ['#8b5cf6', '#f5f3ff', '#6d28d9'],
        self::STAGE_TRANSFER => ['#14b8a6', '#f0fdfa', '#0f766e'],
        self::STAGE_CLOSED => ['#10b981', '#ecfdf5', '#047857'],
        self::STAGE_CANCELLED => ['#ef4444', '#fef2f2', '#b91c1c'],
    ];

    public const STAGE_ICONS = [
        'registered' => 'heroicon-o-inbox-arrow-down',
        self::STAGE_REVIEW => 'heroicon-o-clipboard-document-check',
        self::STAGE_QUOTING => 'heroicon-o-calculator',
        self::STAGE_ASSIGNMENT => 'heroicon-o-user-plus',
        self::STAGE_VISIT => 'heroicon-o-map-pin',
        self::STAGE_REQUOTE => 'heroicon-o-arrow-path',
        self::STAGE_ONSITE_SERVICE => 'heroicon-o-wrench-screwdriver',
        self::STAGE_TRANSFER => 'heroicon-o-building-office',
        self::STAGE_CLOSED => 'heroicon-o-flag',
        self::STAGE_CANCELLED => 'heroicon-o-x-circle',
    ];

    /** ۳-۴ — دسته‌ی درخواست */
    public const SERVICE_TYPES = [
        'onsite_repair' => 'تعمیر دستگاه در محل کاربر',
        'onsite_upgrade' => 'ارتقای دستگاه در محل کاربر',
        'onsite_diagnosis' => 'بررسی و عیب‌یابی اولیه در محل کاربر',
        'pickup_to_loop' => 'دریافت دستگاه و انتقال به شرکت لوپ',
        'combined' => 'ترکیبی: بررسی در محل و احتمال انتقال به شرکت',
    ];

    /** ۳-۶ — نوع کاربر برای شرایط قیمت‌گذاری */
    public const CUSTOMER_TYPES = [
        'individual' => 'کاربر عادی',
        'organization' => 'کاربر سازمانی',
        'company' => 'کاربر شرکتی',
    ];

    public const REVIEW_STATUSES = [
        'pending' => 'در انتظار بررسی',
        'incomplete' => 'ناقص — بازگشت برای تکمیل',
        'approved' => 'تأیید شده',
    ];

    public const ASSIGNMENT_STATUSES = [
        'pending' => 'در انتظار تأیید تکنسین',
        'accepted' => 'تأیید شده توسط تکنسین',
        'rejected' => 'رد شده توسط تکنسین',
    ];

    /** ۶-۴ — نتیجه‌ی بررسی اولیه‌ی تکنسین در محل */
    public const VISIT_OUTCOMES = [
        'serviceable_onsite' => 'انجام خدمت در محل امکان‌پذیر است',
        'needs_requote' => 'نیاز به تغییر قیمت یا افزودن خدمات و قطعات',
        'transfer_to_loop' => 'انجام در محل ممکن نیست؛ انتقال دستگاه به شرکت',
        'user_declined' => 'کاربر با ادامه‌ی خدمت موافقت نمی‌کند',
        'mission_failed' => 'مأموریت ناموفق (عدم حضور کاربر، مغایرت اطلاعات یا سایر موانع)',
    ];

    /** ۹-۲ — روش انتقال دستگاه */
    public const TRANSFER_METHODS = [
        'technician' => 'دریافت توسط تکنسین میدانی و انتقال به شرکت',
        'courier' => 'پیک / حمل‌ونقل',
        'user' => 'تحویل توسط خود کاربر به شرکت',
    ];

    /** ۱۰-۱ — علت لغو */
    public const CANCEL_REASONS = [
        'user_request' => 'درخواست لغو از سوی کاربر',
        'price_rejected' => 'رد قیمت اولیه یا قیمت اصلاح‌شده',
        'not_feasible' => 'عدم امکان انجام خدمت',
        'parts_unavailable' => 'عدم امکان تأمین قطعه یا اجرای خدمت در شرایط توافق‌شده',
        'user_absent' => 'عدم حضور کاربر یا مانع مؤثر در انجام مأموریت',
        'other' => 'سایر شرایط مطابق ضوابط شرکت یا توافق طرفین',
    ];

    /** ۱۱-۱ — نتیجه‌ی مراحل سفارش */
    public const FIELD_RESULTS = [
        'done_onsite' => 'خدمت در محل انجام شد',
        'transferred_to_loop' => 'دستگاه به شرکت لوپ ارجاع شد',
        'cancelled_by_user' => 'سفارش با درخواست کاربر لغو شد',
        'cancelled_not_feasible' => 'سفارش به دلیل عدم امکان انجام خدمت لغو شد',
        'failed_needs_recoordination' => 'مأموریت ناموفق بود و نیازمند هماهنگی مجدد است',
    ];

    protected $fillable = [
        'order_id',
        'service_type', 'customer_type', 'requester_name', 'requester_phone', 'contract_reference',
        'review_status', 'review_note', 'reviewed_at', 'reviewed_by', 'route_to_loop_reason',
        'assignment_status', 'assigned_at', 'assignment_responded_at', 'assignment_note', 'assignment_reject_reason',
        'visit_outcome', 'visit_outcome_note', 'visit_outcome_at',
        'service_actions', 'service_parts_used', 'service_result',
        'transfer_method', 'transfer_reason', 'transfer_device_condition', 'transfer_accessories',
        'transfer_courier_info', 'transfer_started_at', 'transfer_received_by', 'transfer_received_at',
        'transfer_receipt_number', 'transfer_custody_note',
        'cancel_reason_type', 'cancel_reason', 'cancel_financial_note', 'device_return_required',
        'field_result', 'closure_note', 'field_closed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'assigned_at' => 'datetime',
        'assignment_responded_at' => 'datetime',
        'visit_outcome_at' => 'datetime',
        'transfer_started_at' => 'datetime',
        'transfer_received_at' => 'datetime',
        'field_closed_at' => 'datetime',
        'device_return_required' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public static function stageLabel(?string $stage): string
    {
        return self::STAGES[$stage] ?? 'نامشخص';
    }

    /** نوع کاربر پیش‌فرض از نوع حساب (۳-۶) */
    public static function customerTypeFromAccount(?string $accountType): string
    {
        return match ($accountType) {
            'company' => 'company',
            'organization', 'g_organization', 's_g_organization' => 'organization',
            default => 'individual',
        };
    }
}
