<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نسخه‌ی قیمت سفارش. قیمت ارسال‌شده برای کاربر تغییر نمی‌کند؛ هر تغییر یک نسخه‌ی جدید است (بند ۷-۸).
 */
class OrderQuote extends Model
{
    public const KIND_INITIAL = 'initial';
    public const KIND_REVISED = 'revised';
    public const KIND_FINAL = 'final';

    public const KINDS = [
        self::KIND_INITIAL => 'قیمت اولیه',
        self::KIND_REVISED => 'قیمت اصلاح‌شده',
        self::KIND_FINAL => 'قیمت نهایی',
    ];

    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUPERSEDED = 'superseded';
    public const STATUS_DISCARDED = 'discarded';
    public const STATUS_RECORDED = 'recorded';

    public const STATUSES = [
        self::STATUS_PENDING_REVIEW => 'در انتظار بررسی پنل',
        self::STATUS_SENT => 'ارسال شده — در انتظار تأیید کاربر',
        self::STATUS_ACCEPTED => 'تأیید شده توسط کاربر',
        self::STATUS_REJECTED => 'رد شده توسط کاربر',
        self::STATUS_SUPERSEDED => 'جایگزین شده با نسخه‌ی جدید',
        self::STATUS_DISCARDED => 'رد شده توسط پنل',
        self::STATUS_RECORDED => 'ثبت شده',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING_REVIEW => 'warning',
        self::STATUS_SENT => 'info',
        self::STATUS_ACCEPTED => 'success',
        self::STATUS_REJECTED => 'danger',
        self::STATUS_SUPERSEDED => 'gray',
        self::STATUS_DISCARDED => 'gray',
        self::STATUS_RECORDED => 'success',
    ];

    protected $fillable = [
        'order_id', 'version', 'kind',
        'service_fee', 'parts_fee', 'extra_fee', 'total',
        'is_estimate', 'estimated_duration', 'visit_date', 'visit_time',
        'parts_description', 'change_reason', 'description', 'requires_user_approval',
        'status', 'proposed_by_type', 'proposed_by_id',
        'approved_by_admin_id', 'approved_at', 'sent_at',
        'decided_at', 'decided_by_type', 'decided_by_id', 'decision_note',
    ];

    protected $casts = [
        'service_fee' => 'integer',
        'parts_fee' => 'integer',
        'extra_fee' => 'integer',
        'total' => 'integer',
        'is_estimate' => 'boolean',
        'requires_user_approval' => 'boolean',
        'visit_date' => 'date',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (OrderQuote $quote) {
            $quote->total = (int) $quote->service_fee + (int) $quote->parts_fee + (int) $quote->extra_fee;
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_REVIEW, self::STATUS_SENT], true);
    }

    public function proposerName(): string
    {
        return match ($this->proposed_by_type) {
            'technician' => 'تکنسین: ' . (Technician::find($this->proposed_by_id)?->name ?? '-'),
            default => 'پنل: ' . (Admin::find($this->proposed_by_id)?->name ?? '-'),
        };
    }

    /** شکل قیمت در پاسخ API کاربر / تکنسین */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'kind' => $this->kind,
            'kind_label' => self::KINDS[$this->kind] ?? $this->kind,
            'service_fee' => $this->service_fee,
            'parts_fee' => $this->parts_fee,
            'extra_fee' => $this->extra_fee,
            'total' => $this->total,
            'is_estimate' => $this->is_estimate,
            'estimated_duration' => $this->estimated_duration,
            'visit_date' => $this->visit_date?->toDateString(),
            'visit_time' => $this->visit_time,
            'parts_description' => $this->parts_description,
            'change_reason' => $this->change_reason,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status] ?? $this->status,
            'requires_user_approval' => $this->requires_user_approval,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'decision_note' => $this->decision_note,
        ];
    }
}
