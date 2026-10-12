<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Order extends Model
{
    public const PAYMENT_METHODS = [
        'cash' => 'نقدی',
        'card_to_card' => 'کارت به کارت',
        'sheba' => 'شبا',
        'bank_transfer' => 'پرداخت درون‌بانکی',
        'in_app' => 'پرداخت درون برنامه لوپ',
        'unpaid' => 'پرداخت نشده',
        'remaining' => 'مانده',
    ];

    public const PAYMENT_CHANNELS = [
        'app' => 'اپلیکیشن',
        'site' => 'سایت',
        'in_person' => 'حضوری',
    ];

    protected $fillable = [
        'user_id',
        'user_address_id',
        'category_id',
        'technician_id',
        'status',
        'payment_status',
        'payment_method',
        'payment_channel',
        'remaining_amount',
        'must_notify',
        'pakar_price',
        'technician_price',
        'extra_price',
        'discount_price',
        'referral_code_id',
        'referral_discount_percent',
        'referral_commission_percent',
        'promo_code_id',
        'promo_discount_percent',
        'set_off_at',
        'arrived_at',
        'started_at',
        'finished_at',
        'is_urgent',
        'des',
        'technician_des',
        'date',
        'time',
        'female_count',
        'male_count',
        'unspecified_count',
        'is_fixed',
        'image_path',
        'is_technician_verified',
        'send_to_loop',
        'shipment_status',
        'shipment_status_descriptions',
        'duration',
        'loop_description',
        'loop_cost_estimate',
        'user_cancellation_reason',
        'user_cancellation_date',
        'user_accept_date',
        'user_initial_accept',
        'user_return_followup_description',
        'return_date',
        'return_time',
        'returned_at',
        'user_final_description',
        'is_time_changed',
        'technician_cancel_reason',
        // Service Schedule fields (for organizations)
        'service_schedule_type',
        'service_schedule_long_duration',
        'service_schedule_long_date',
        'service_schedule_long_time',
        'service_schedule_long_file',
        'service_schedule_short_date',
        'service_schedule_short_time',
        'service_schedule_short_file',
        'emergency_help_at',
        'emergency_help',
        'technician_opinion',
        'prepayment',
        'prepayment_payment_status',
        'platform',
        'done_in_place',
        'technician_in_place_description',
        'admin_in_place_description',
        'user_in_place_description',
        // تامین کالا (اپ سازمانی)
        'procurement_items',
        'delivery_mode',
        'technician_gender',
        // مشخصات تحویل کالا / محصول از لوپ به کاربر
        'delivery_receiver_type',
        'delivery_receiver_name',
        'delivery_receiver_mobile',
        'delivery_receiver_national_id',
        'delivery_method',
        'delivery_status',
        'delivered_at',
    ];

    public const DELIVERY_RECEIVER_TYPES = [
        'self' => 'خود کاربر',
        'representative' => 'نماینده / شخص دیگر',
    ];

    public const DELIVERY_METHODS = [
        'loop_technician' => 'توسط تکنسین لوپ',
        'user' => 'خود کاربر / تحویل حضوری',
        'courier' => 'پیک حمل و نقل',
    ];

    public const DELIVERY_STATUSES = [
        'pending' => 'در انتظار',
        'ready' => 'آماده تحویل',
        'shipped' => 'ارسال شد',
        'delivered' => 'تحویل شد',
    ];

    /** بازه‌های «ساعت اعزام محصول / تکنسین» در پنل. */
    public const DISPATCH_TIME_SLOTS = [
        '10 - 12' => '۱۰ الی ۱۲',
        '12 - 14' => '۱۲ الی ۱۴',
        '14 - 16' => '۱۴ الی ۱۶',
        '16 - 20' => '۱۶ الی ۲۰',
    ];

    protected $casts = [
        'set_off_at' => 'datetime',
        'arrived_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'send_to_loop' => 'datetime',
        'done_in_place' => 'datetime',
        'user_cancellation_date' => 'datetime',
        'user_accept_date' => 'datetime',
        'user_initial_accept' => 'datetime',
        'returned_at' => 'datetime',
        'return_date' => 'date',
        'is_technician_verified' => 'integer',
        'service_schedule_long_date' => 'date',
        'service_schedule_short_date' => 'date',
        'emergency_help_at' => 'datetime',
        'referral_discount_percent' => 'integer',
        'promo_discount_percent' => 'integer',
        'procurement_items' => 'array',
        'delivery_mode' => 'array',
        'delivered_at' => 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'set_off_at' => 'datetime',
            'arrived_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'done_in_place' => 'datetime',
        ];
    }



    protected static function booted(): void
    {
        static::saving(function (Order $order) {
            // محصول اعزام‌شده به لوپ بدون درصد پیش‌پرداخت تأیید نمی‌شود؛ پیش‌فرض از تنظیمات پنل (۶۰٪)
            if ($order->send_to_loop !== null && $order->prepayment === null) {
                $order->prepayment = AppSetting::int(AppSetting::LOOP_PREPAYMENT_PERCENT);
            }
        });

        static::saved(function (Order $order) {
            // پورسانت مأمور خرید با وضعیت سفارش هم‌گام می‌شود (pending / available / canceled)
            if ($order->referral_code_id && $order->wasChanged(['status', 'payment_status', 'technician_price', 'extra_price', 'pakar_price'])) {
                app(\App\Services\ReferralAgentService::class)->syncForOrder($order);
            }
        });
    }

    public function delivery_reports(): HasOne
    {
        return $this->hasOne(DeliveryReport::class);
    }
    public function technician_order_report(): HasOne
    {
        return $this->hasOne(TechnicianOrderReport::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function user_address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'user_address_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }
    public function order_galleries(): HasMany
    {
        return $this->hasMany(OrderGallery::class);
    }

    public function extra_services(): HasMany
    {
        return $this->hasMany(OrderExtraService::class);
    }

    public function technicianReviews(): HasMany
    {
        return $this->hasMany(TechnicianReview::class);
    }

    public function discountUse(): HasOne
    {
        return $this->hasOne(DiscountUse::class);
    }

    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function promoCodeUsage(): HasOne
    {
        return $this->hasOne(PromoCodeUsage::class);
    }
    public function user_transaction(): HasOne
    {
        return $this->hasOne(UserTransaction::class);
    }

    /** استعلام تامین کالا (فقط سفارش سازمانی/شرکتی با اقلام تامین کالا). */
    public function procurement(): HasOne
    {
        return $this->hasOne(OrderProcurement::class);
    }

    public function referralCommission(): HasOne
    {
        return $this->hasOne(ReferralCommission::class);
    }

    /** کالا / محصول از لوپ به کاربر برمی‌گردد (اعزام به لوپ یا تامین کالا). */
    public function returnsGoodsToUser(): bool
    {
        return $this->send_to_loop !== null
            || $this->delivery_receiver_type !== null
            || ($this->relationLoaded('procurement') ? $this->procurement !== null : $this->procurement()->exists());
    }

    /** شکل `delivery` در پاسخ جزئیات / لیست سفارش (سند اپ کاربر، بخش ۱۰). */
    public function deliveryPayload(): ?array
    {
        if (!$this->returnsGoodsToUser()) {
            return null;
        }

        $isSelf = $this->delivery_receiver_type === 'self';
        // بدون setRelation، تا user در خروجی JSON سفارش ظاهر نشود
        $user = $isSelf ? ($this->relationLoaded('user') ? $this->user : User::find($this->user_id)) : null;

        return [
            'receiver_type' => $this->delivery_receiver_type,
            'receiver_name' => $isSelf
                ? (trim(($user?->name ?? '') . ' ' . ($user?->last_name ?? '')) ?: null)
                : $this->delivery_receiver_name,
            'receiver_mobile' => $isSelf ? $user?->phone : $this->delivery_receiver_mobile,
            'receiver_national_id' => $isSelf ? null : $this->delivery_receiver_national_id,
            'method' => $this->delivery_method,
            'status' => $this->delivery_status ?? 'pending',
            'delivered_at' => $this->delivered_at?->toIso8601String(),
        ];
    }

    /**
     * فیلدهای `procurement` و `delivery` برای پاسخ‌های مشتری. رابطه‌ی procurement از خروجی خام
     * حذف می‌شود تا شکل API جایگزین آن شود.
     */
    public function customerExtras(): array
    {
        $procurement = $this->relationLoaded('procurement') ? $this->procurement : $this->procurement()->with('items')->first();

        return [
            'procurement' => $procurement?->toApiArray(),
            'delivery' => $this->deliveryPayload(),
        ];
    }

    /**
     * محاسبه قیمت نهایی قابل پرداخت
     * 
     * @param bool $withPlatform آیا قیمت پلتفرم هم اضافه بشه
     * @return float
     */
    public function payment_price(bool $withPlatform = true): float
    {
        $min_price = MinPrice::orderByDesc('id')->first();
        $price = 0;

        // قیمت تکنسین

        if (!is_null($this->prepayment) && $this->prepayment > 0 && $this->prepayment_payment_status == 0 && !is_null($this->loop_cost_estimate) && $this->loop_cost_estimate > 0) {
            $price = $this->loop_cost_estimate * $this->prepayment / 100;
        } else {
            if ($this->technician_price) {
                $price += $this->technician_price;
            }

            // قیمت خدمات اضافی
            if ($this->extra_price) {
                $price += $this->extra_price;
            }
            $discountFromUse = $this->calculateDiscountFromUse();

            // استفاده از بیشترین تخفیف
            $storedPromoDiscount = $this->promoCodeUsage?->discount_amount;
            $promoDiscount = $storedPromoDiscount === null
                ? $this->promoDiscountAmount($price)
                : 0;

            $discount = max($discountFromUse, $this->discount_price ?? 0)
                + $promoDiscount
                + $this->referralDiscountAmount($price);

            // کسر تخفیف
            if ($discount) {
                $price -= $discount;
            }

            if (!is_null($this->prepayment) && $this->prepayment > 0 && $this->prepayment_payment_status == 1 && !is_null($this->loop_cost_estimate) && $this->loop_cost_estimate > 0) {
                $price -= ($this->loop_cost_estimate * $this->prepayment / 100);
            }
            if ($price < $min_price?->price) {
                $price += 200000;
            }
        }


        // محاسبه تخفیف از discount_uses

        return max(0, $price); // حداقل صفر
    }

    /**
     * محاسبه تخفیف از discount_uses
     */
    public function referralDiscountAmount(?float $totalPrice = null): float
    {
        if (!$this->referral_discount_percent) {
            return 0;
        }

        $totalPrice ??= ($this->technician_price ?? $this->pakar_price ?? 0) + ($this->extra_price ?? 0);

        return ($totalPrice * $this->referral_discount_percent) / 100;
    }

    /**
     * روش پرداخت؛ برای سفارش‌های قبل از این فیلد از روی وضعیت پرداخت محاسبه می‌شود
     * (پرداخت‌های قبلی همه از درگاه یا کیف پول بوده‌اند).
     */
    protected function paymentMethod(): Attribute
    {
        return Attribute::get(function ($value) {
            if ($value) {
                return $value;
            }

            return (int) $this->payment_status === 1 || (int) $this->prepayment_payment_status === 1
                ? 'in_app'
                : 'unpaid';
        });
    }

    protected function paymentChannel(): Attribute
    {
        return Attribute::get(function ($value) {
            if ($value) {
                return $value;
            }

            if ((int) $this->payment_status !== 1 && (int) $this->prepayment_payment_status !== 1) {
                return null;
            }

            return in_array($this->platform, ['web', 'site'], true) ? 'site' : 'app';
        });
    }

    /** مانده‌ی قابل پرداخت؛ فقط وقتی بخشی از مبلغ (مثلاً پیش‌پرداخت) پرداخت شده است. */
    protected function remainingAmount(): Attribute
    {
        return Attribute::get(function ($value) {
            if ($value !== null) {
                return (int) $value > 0 ? (int) $value : null;
            }

            if ((int) $this->prepayment_payment_status === 1 && (int) $this->payment_status !== 1) {
                $remaining = (int) round($this->payment_price());

                return $remaining > 0 ? $remaining : null;
            }

            return null;
        });
    }

    /** ثبت پرداخت موفق درون برنامه (درگاه یا کیف پول). */
    public function markPaidInApp(): void
    {
        $this->payment_method = 'in_app';
        $this->payment_channel = in_array($this->platform, ['web', 'site'], true) ? 'site' : 'app';
        // پس از پیش‌پرداخت، مانده از روی قیمت نهایی محاسبه می‌شود
        $this->remaining_amount = null;
    }

    /**
     * پورسانت صاحب کد معرف: درصد ثبت‌شده روی سفارش، از مبلغ خدمات پس از کسر تخفیف همان کد.
     */
    public function referralCommissionAmount(): float
    {
        if (!$this->referral_commission_percent) {
            return 0;
        }

        $base = ($this->technician_price ?? $this->pakar_price ?? 0) + ($this->extra_price ?? 0);
        $base -= $this->referralDiscountAmount($base);

        return round(max(0, $base) * $this->referral_commission_percent / 100);
    }

    public function promoDiscountAmount(?float $totalPrice = null): float
    {
        if (!$this->promo_discount_percent) {
            return 0;
        }

        // After payment, keep using the exact amount that was recorded at payment time.
        // This prevents recalculating the promo discount on top of discount_price.
        $storedAmount = $this->promoCodeUsage?->discount_amount;
        if ($storedAmount !== null) {
            return (float) $storedAmount;
        }

        $totalPrice ??= ($this->technician_price ?? $this->pakar_price ?? 0) + ($this->extra_price ?? 0);

        return ($totalPrice * $this->promo_discount_percent) / 100;
    }

    protected function calculateDiscountFromUse(): float
    {
        if (!$this->relationLoaded('discountUse')) {
            $this->load('discountUse.discount_code');
        }

        $discountUse = $this->discountUse;

        if (!$discountUse || !$discountUse->discount_code) {
            return 0;
        }

        $discountCode = $discountUse->discount_code;

        // مبلغ نهایی (قیمت پایه + خدمات اضافی)
        $basePrice = $this->technician_price ? $this->technician_price : $this->pakar_price ?? 0;
        $totalPrice = $basePrice + ($this->extra_price ?? 0);

        // محاسبه تخفیف بر اساس درصد روی مبلغ نهایی
        $discountAmount = ($totalPrice * $discountCode->discount_percent) / 100;

        // اعمال سقف تخفیف
        if ($discountCode->club->max_price && $discountAmount > $discountCode->club->max_price) {
            $discountAmount = $discountCode->club->max_price;
        }



        return $discountAmount;
    }

    public function region()
    {
        return $this->hasOneThrough(
            Region::class,
            UserAddress::class,
            'id',              // Foreign key on UserAddress table
            'id',              // Foreign key on Region table
            'user_address_id', // Local key on Order table
            'region_id'        // Local key on UserAddress table
        );
    }

    public function userChats()
    {
        return $this->hasMany(Chat::class, 'user_id', 'user_id');
    }

    
}
