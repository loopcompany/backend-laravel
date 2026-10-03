<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * درخواست حذف حساب. حذف حساب به معنی حذف سوابق سفارش و مالی نیست؛
 * هنگام اجرا فقط اطلاعات شخصی ناشناس می‌شود.
 */
class AccountDeletionRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'در انتظار',
        self::STATUS_APPROVED => 'تأیید شده',
        self::STATUS_DONE => 'انجام شده',
        self::STATUS_CANCELED => 'لغو شده',
        self::STATUS_REJECTED => 'رد شده',
    ];

    public const KIND_INDIVIDUAL = 'individual';
    public const KIND_ORGANIZATION = 'organization';

    protected $fillable = [
        'user_id', 'account_kind', 'status', 'reason', 'phone_snapshot', 'name_snapshot',
        'requested_at', 'scheduled_at', 'executed_at', 'canceled_at', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
        'canceled_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED], true);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status] ?? $this->status,
            'requested_at' => $this->requested_at?->toIso8601String(),
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'executed_at' => $this->executed_at?->toIso8601String(),
            'reason' => $this->reason,
        ];
    }
}
