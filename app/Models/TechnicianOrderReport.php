<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianOrderReport extends Model
{
    protected $fillable = [
        'technician_id',
        'order_id',
        'name',
        'melicode',
        'product_name',
        'product_brand',
        'product_model',
        'product_color',
        'product_serial_number',
        'asset_label_code',
        'accessories',
        'user_reported_issues',
        'technician_reported_issues',
        'technician_observed_issues',
        'user_requested_services',
        'user_confirmed_at',
        'max_price',
        'min_price',
        'product_password',
    ];

    protected $casts = [
        'user_confirmed_at' => 'datetime',
    ];

    // Relations
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopeConfirmed($query)
    {
        return $query->whereNotNull('user_confirmed_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('user_confirmed_at');
    }

    // Accessors
    public function getIsConfirmedAttribute(): bool
    {
        return !is_null($this->user_confirmed_at);
    }
}
