<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * پورسانت مأمور خرید از یک سفارش ثبت‌شده با کد معرف او.
 * pending (تا تسویه‌ی کامل) → available | canceled
 */
class ReferralCommission extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_OFFSET = 'offset';
    public const STATUS_CANCELED = 'canceled';

    public const STATUSES = [
        self::STATUS_PENDING => 'در انتظار تسویه‌ی سفارش',
        self::STATUS_AVAILABLE => 'قابل برداشت',
        self::STATUS_WITHDRAWN => 'برداشت شده',
        self::STATUS_OFFSET => 'تهاتر شده',
        self::STATUS_CANCELED => 'لغو شده',
    ];

    protected $fillable = [
        'user_id',
        'referral_code_id',
        'order_id',
        'order_amount',
        'commission_percent',
        'commission_amount',
        'status',
        'settled_at',
    ];

    protected $casts = [
        'order_amount' => 'integer',
        'commission_percent' => 'integer',
        'commission_amount' => 'integer',
        'settled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_amount' => $this->order_amount,
            'commission_percent' => $this->commission_percent,
            'commission_amount' => $this->commission_amount,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status] ?? $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'settled_at' => $this->settled_at?->toIso8601String(),
        ];
    }
}
