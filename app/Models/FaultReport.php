<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaultReport extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'product_name',
        'ordered_at',
        'delivered_at',
        'technician_code',
        'paid_price',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * دریافت تکنسین بر اساس کد ارجاع
     */
    public function getTechnicianAttribute()
    {
        if (empty($this->technician_code)) {
            return null;
        }
        
        return Technician::where('referral_code', $this->technician_code)->first();
    }

    /**
     * دریافت نام تکنسین
     */
    public function getTechnicianNameAttribute(): string
    {
        $technician = $this->technician;
        
        if (!$technician) {
            return empty($this->technician_code) ? 'کد تکنسین ثبت نشده' : 'تکنسین یافت نشد';
        }
        
        return $technician->name ?? 'نام ثبت نشده';
    }

    /**
     * دریافت شماره تلفن تکنسین
     */
    public function getTechnicianPhoneAttribute(): string
    {
        $technician = $this->technician;
        
        if (!$technician) {
            return '-';
        }
        
        return $technician->phone ?? 'شماره ثبت نشده';
    }
}
