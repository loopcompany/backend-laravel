<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTransaction extends Model
{
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
