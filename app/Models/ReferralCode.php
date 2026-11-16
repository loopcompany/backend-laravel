<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralCode extends Model
{
    public const CURRENT_POLICY = '20261115';

    public static function eligibleUsers()
    {
        return User::query()->where('is_special',true)->whereIn('account_type',[...User::ORGANIZATION_ACCOUNT_TYPES,'company'])->whereNotNull('code')->whereIn('registration_method',[1,2,3]);
    }

    public function deliveries()
    {
        return $this->hasMany(CodeDelivery::class,'code_id')->where('code_type',self::class);
    }

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SENT = 'sent';
    public const STATUS_USED = 'used';

    protected $fillable = [
        'policy_version', 'commission_mode', 'manual_commission_amount', 'used_order_id', 'issued_by_admin_id',
        'user_id',
        'code',
        'status',
        'multi_use',
        'discount_percent',
        'commission_percent',
        'used_by_user_id',
        'used_at',
    ];

    protected $casts = [
        'manual_commission_amount'=>'integer', 'used_order_id'=>'integer',
        'discount_percent' => 'integer',
        'commission_percent' => 'integer',
        'multi_use' => 'boolean',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
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
        static::saving(function (self $code): void {
            if (!$code->policy_version) return;
            $code->commission_mode ??= 'table';
            if (!in_array($code->commission_mode,['table','manual'],true) || ($code->commission_mode === 'manual' && ($code->manual_commission_amount === null || $code->manual_commission_amount < 0))) throw \Illuminate\Validation\ValidationException::withMessages(['commission_mode'=>'روش و مبلغ پورسانت را معتبر تعیین کنید.']);
            if (!self::eligibleUsers()->whereKey($code->user_id)->exists()) throw \Illuminate\Validation\ValidationException::withMessages(['user_id'=>'کاربر ویژه سازمانی/شرکتی باید شناسه کاربری و روش ثبت‌نام اپ، دسکتاپ یا سایت داشته باشد.']);
            if ($code->exists && $code->getOriginal('used_order_id') && $code->isDirty(['user_id','code','commission_mode','manual_commission_amount','status','multi_use','discount_percent','commission_percent','policy_version','used_order_id'])) throw \Illuminate\Validation\ValidationException::withMessages(['code'=>'کد مصرف‌شده قابل تغییر نیست.']);
            $code->multi_use = false;
            $code->discount_percent = 0;
            $code->commission_percent = 0;
        });
        static::creating(function (self $referralCode): void {
            if (blank($referralCode->code)) {
                $referralCode->code = static::generateUniqueCode();
            }

            $referralCode->code = static::normalize($referralCode->code);
        });
    }
}
