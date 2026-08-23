<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncentivePlan extends Model
{
    // وضعیت‌های طرح تشویقی
    const STATUS_PENDING = 0;    // فعال (در انتظار استفاده)
    const STATUS_USED = 1;       // استفاده شده (تکمیل شده)
    const STATUS_EXPIRED = 2;    // منقضی شده

    protected $fillable = [
        'technician_id',
        'description',
        'status',
        'end_at'
    ];

    protected $casts = [
        'end_at' => 'datetime',
        'status' => 'integer',
    ];

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
