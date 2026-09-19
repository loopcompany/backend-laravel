<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralCode extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SENT = 'sent';
    public const STATUS_USED = 'used';

    protected $fillable = [
        'user_id',
        'code',
        'status',
        'discount_percent',
        'used_by_user_id',
        'used_at',
    ];

    protected $casts = [
        'discount_percent' => 'integer',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'فعال',
            self::STATUS_SENT => 'فرستاده شده',
            self::STATUS_USED => 'استفاده شده',
        ];
    }

    public static function usableStatuses(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_SENT];
    }

    public static function normalize(?string $code): string
    {
        return strtoupper(trim((string) $code));
    }

    public static function generateUniqueCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = '';
            for ($index = 0; $index < 6; $index++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $code = 'LOOP-' . $suffix;
        } while (static::where('code', $code)->exists());

        return $code;
    }

    protected static function booted(): void
    {
        static::creating(function (self $referralCode): void {
            if (blank($referralCode->code)) {
                $referralCode->code = static::generateUniqueCode();
            }

            $referralCode->code = static::normalize($referralCode->code);
        });
    }
}
