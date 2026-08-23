<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DiscountUse extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'discount_code_id'
    ];

    protected $casts = [
        'pakar_price'       => 'integer',
        'technician_price'  => 'integer',
        'discount_amount'   => 'integer',
        'used_at'           => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function discount_code()
    {
        return $this->belongsTo(DiscountCode::class)->withoutGlobalScope(SoftDeletingScope::class);
    }
}
