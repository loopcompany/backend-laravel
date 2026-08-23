<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryReport extends Model
{
    protected $fillable = [
        'order_id',
        'technician_id',
        'name',
        'melicode',
        'product_info',
        'accessories',
        'label_code',
        'appearance_defect',
        'user_description',
        'technical_description',
        'verification_code',
        'user_verified_at',
    ];

    protected $hidden = [
        'verification_code',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
}
