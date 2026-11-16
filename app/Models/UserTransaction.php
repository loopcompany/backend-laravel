<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTransaction extends Model
{
    protected $hidden = ['accounting_review_note', 'accounting_reviewed_by'];

    public function requiresAccountingReview(): bool
    {
        return (int) $this->status === 100 && (in_array((int) $this->type, [1, 2], true)
            || in_array($this->payment_method, ['card_to_card', 'sheba', 'bank_transfer'], true));
    }

    protected static function booted(): void
    {
        static::saving(function (self $transaction) {
            if ($transaction->requiresAccountingReview() && (!$transaction->accounting_status
                || (!$transaction->wasRecentlyCreated && $transaction->exists && $transaction->isDirty(['price','user_id','order_id','referenceId','payment_method','status'])))) {
                $transaction->accounting_status = 'pending';
                $transaction->accounting_reviewed_by = null;
                $transaction->accounting_reviewed_at = null;
                $transaction->accounting_review_note = null;
            }
        });
    }
    protected $fillable = [
        'user_id',
        'price',
        'referenceId',
        'type',
        'status',
        'description',
        'linking_url',
        'order_id',
        'payment_method',
        'payment_channel',
        'order_payment_kind',
    ];

    /** نوع ۴: پرداخت خارج از برنامه (نقدی، کارت‌به‌کارت، ...) که ادمین ثبت می‌کند */
    public const TYPE_OFFLINE_PAYMENT = 4;

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** تراکنش‌های قبل از این فیلد همه درون برنامه (درگاه/کیف پول) بوده‌اند. */
    protected function paymentMethod(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(fn ($value) => $value ?: 'in_app');
    }

    protected function paymentChannel(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(fn ($value) => $value ?: 'app');
    }
}
