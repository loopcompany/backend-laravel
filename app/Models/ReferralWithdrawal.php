<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * برداشت پورسانت مأمور خرید: به شبا (صف پنل) یا تهاتر با کیف پول لوپ (فوری).
 */
class ReferralWithdrawal extends Model
{
    public const METHOD_SHEBA = 'sheba';
    public const METHOD_OFFSET = 'offset';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_DONE = 'done';
    public const STATUS_REJECTED = 'rejected';

    public const METHODS = [
        self::METHOD_SHEBA => 'واریز به شبا',
        self::METHOD_OFFSET => 'تهاتر با کیف پول لوپ',
    ];

    public const STATUSES = [
        self::STATUS_PENDING => 'در انتظار بررسی',
        self::STATUS_APPROVED => 'تأیید شده (در انتظار واریز)',
        self::STATUS_PAID => 'واریز شد',
        self::STATUS_DONE => 'انجام شد',
        self::STATUS_REJECTED => 'رد شده',
    ];

    protected $fillable = [
        'user_id',
        'amount',
        'method',
        'status',
        'sheba_number',
        'tracking_code',
        'admin_note',
        'user_transaction_id',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'method' => $this->method,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status] ?? $this->status,
            'sheba_number' => $this->sheba_number,
            'tracking_code' => $this->tracking_code,
            'admin_note' => $this->admin_note,
            'created_at' => $this->created_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
        ];
    }
}
