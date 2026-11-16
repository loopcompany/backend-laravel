<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncentivePlan extends Model
{
    public const BENEFITS = ['legacy'=>'توضیحی قدیمی (نیازمند تکمیل)', 'wallet'=>'افزایش کیف پول', 'allowance'=>'کمک هزینه', 'reward'=>'پاداش', 'purchase_discount'=>'تخفیف خرید از مکان خاص', 'company_item'=>'دریافت اقلام از شرکت'];

    // وضعیت‌های طرح تشویقی
    const STATUS_PENDING = 0;    // فعال (در انتظار استفاده)
    const STATUS_USED = 1;       // استفاده شده (تکمیل شده)
    const STATUS_EXPIRED = 2;    // منقضی شده

    protected $fillable = [
        'benefit_type', 'amount', 'benefit_code', 'benefit_location', 'requested_at', 'redeemed_at', 'fulfilled_by_admin_id', 'technician_transaction_id',
        'technician_id',
        'description',
        'status',
        'end_at'
    ];

    protected $casts = [
        'end_at' => 'datetime', 'amount'=>'integer', 'requested_at'=>'datetime', 'redeemed_at'=>'datetime',
        'status' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $plan) {
            if ($plan->requested_at || $plan->redeemed_at || (int)$plan->status===self::STATUS_USED) throw \Illuminate\Validation\ValidationException::withMessages(['incentive'=>'سابقه مزیت درخواست‌شده یا مصرف‌شده قابل حذف نیست.']);
        });
        static::saving(function (self $plan) {
            if (!array_key_exists($plan->benefit_type ?? 'legacy',self::BENEFITS)) throw \Illuminate\Validation\ValidationException::withMessages(['benefit_type'=>'نوع مزیت نامعتبر است.']);
            if (in_array($plan->benefit_type,['wallet','allowance','reward'],true) && (int)$plan->amount < 1) throw \Illuminate\Validation\ValidationException::withMessages(['amount'=>'مبلغ مزیت باید مثبت باشد.']);
            if ($plan->benefit_type !== 'legacy' && (int)$plan->status === self::STATUS_USED && !$plan->redeemed_at) throw \Illuminate\Validation\ValidationException::withMessages(['status'=>'مزیت باید از مسیر استفاده مصرف شود.']);
        });
        static::updating(function (self $plan) {
            if ($plan->getOriginal('requested_at') && $plan->isDirty(['technician_id','benefit_type','amount','benefit_code','benefit_location'])) throw \Illuminate\Validation\ValidationException::withMessages(['amount'=>'مزیت درخواست‌شده قابل واگذاری یا تغییر نیست.']);
            if ($plan->getOriginal('redeemed_at') && $plan->isDirty(['technician_id','benefit_type','amount','status','redeemed_at','technician_transaction_id'])) throw \Illuminate\Validation\ValidationException::withMessages(['amount'=>'مزیت مصرف‌شده قابل تغییر نیست.']);
        });
    }

    /**
     * رابطه با تکنسین
     */
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    /**
     * دریافت برچسب وضعیت به فارسی
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Active',
            self::STATUS_USED => 'Used',
            self::STATUS_EXPIRED => 'Expired',
            default => 'Unknown',
        };
    }

    /**
     * دریافت رنگ badge وضعیت
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'success',
            self::STATUS_USED => 'info',
            self::STATUS_EXPIRED => 'danger',
            default => 'gray',
        };
    }

    /**
     * دریافت لیست تمام وضعیت‌ها برای Select
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'فعال (در انتظار استفاده)',
            self::STATUS_USED => 'استفاده شده (تکمیل شده)',
        ];
    }

    /**
     * Scope برای فیلتر کردن بر اساس وضعیت
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUsed($query)
    {
        return $query->where('status', self::STATUS_USED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }
}
