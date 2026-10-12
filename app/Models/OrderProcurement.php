<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * استعلام قیمت «تامین تجهیزات / کالا» یک سفارش سازمانی (پیش‌رسید → پیش‌پرداخت → آماده‌سازی → باقی‌مانده → ارسال).
 */
class OrderProcurement extends Model
{
    public const STATUS_AWAITING_QUOTE = 'awaiting_quote';
    public const STATUS_QUOTED = 'quoted';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';

    public const STATUSES = [
        self::STATUS_AWAITING_QUOTE => 'منتظر اعلام قیمت',
        self::STATUS_QUOTED => 'قیمت اعلام شده',
        self::STATUS_ACCEPTED => 'تأیید کاربر (منتظر پیش‌پرداخت)',
        self::STATUS_REJECTED => 'رد شده توسط کاربر',
        self::STATUS_EXPIRED => 'منقضی شده',
        self::STATUS_PREPARING => 'در حال آماده‌سازی',
        self::STATUS_READY => 'کالا آماده است (منتظر باقی‌مانده)',
        self::STATUS_SHIPPED => 'ارسال شد',
        self::STATUS_DELIVERED => 'تحویل شد',
    ];

    protected $fillable = [
        'order_id',
        'status',
        'total_amount',
        'quoted_at',
        'valid_until',
        'note',
        'prepayment_percent',
        'prepayment_amount',
        'prepayment_paid_at',
        'remaining_amount',
        'remaining_paid_at',
        'accepted_at',
        'rejected_at',
        'reject_reason',
        'ready_at',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'prepayment_percent' => 'integer',
        'prepayment_amount' => 'integer',
        'remaining_amount' => 'integer',
        'quoted_at' => 'datetime',
        'valid_until' => 'datetime',
        'prepayment_paid_at' => 'datetime',
        'remaining_paid_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'ready_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderProcurementItem::class)->orderBy('id');
    }

    /** «قیمت اعلام شده» پس از valid_until خودبه‌خود «منقضی» است. */
    public function effectiveStatus(): string
    {
        if ($this->status === self::STATUS_QUOTED && $this->valid_until && $this->valid_until->isPast()) {
            return self::STATUS_EXPIRED;
        }

        return $this->status;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->effectiveStatus()] ?? $this->effectiveStatus();
    }

    /** مبالغ قطعی از جمع اقلام و درصد پیش‌پرداخت (تا وقتی پیش‌پرداخت انجام نشده). */
    public function recalculate(): void
    {
        $this->loadMissing('items');

        $this->total_amount = (int) $this->items->sum(fn (OrderProcurementItem $item) => $item->totalPrice() ?? 0);

        if ($this->prepayment_paid_at === null) {
            $this->prepayment_amount = (int) round($this->total_amount * $this->prepayment_percent / 100);
        }

        $this->remaining_amount = max(0, $this->total_amount - (int) $this->prepayment_amount);
    }

    /** شکل پاسخ `procurement` در جزئیات و لیست سفارش‌ها (سند اپ کاربر بخش ۹-۳). */
    public function toApiArray(): array
    {
        $this->loadMissing('items');

        return [
            'status' => $this->effectiveStatus(),
            'status_label' => $this->statusLabel(),
            'items' => $this->items->map(fn (OrderProcurementItem $item) => [
                'id' => $item->id,
                'item_id' => $item->item_id,
                'title' => $item->title,
                'condition' => $item->condition,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->totalPrice(),
                'description' => $item->description,
            ])->values()->all(),
            'total_amount' => $this->total_amount,
            'quoted_at' => $this->quoted_at?->toIso8601String(),
            'valid_until' => $this->valid_until?->toIso8601String(),
            'note' => $this->note,
            'payment' => [
                'prepayment_percent' => $this->prepayment_percent,
                'prepayment_amount' => $this->prepayment_amount,
                'prepayment_paid' => $this->prepayment_paid_at !== null,
                'prepayment_paid_at' => $this->prepayment_paid_at?->toIso8601String(),
                'remaining_amount' => $this->remaining_amount,
                'remaining_paid' => $this->remaining_paid_at !== null,
                'remaining_paid_at' => $this->remaining_paid_at?->toIso8601String(),
            ],
        ];
    }
}
