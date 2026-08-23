<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'user_address_id',
        'category_id',
        'technician_id',
        'status',
        'payment_status',
        'must_notify',
        'pakar_price',
        'technician_price',
        'extra_price',
        'discount_price',
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
        'user_in_place_description'
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
    public function user_transaction(): HasOne
    {
        return $this->hasOne(UserTransaction::class);
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
            $discount = max($discountFromUse, $this->discount_price ?? 0);

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
