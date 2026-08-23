<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianTransaction extends Model
{
    protected $fillable = [
        'technician_id',
        'order_id',
        'price',
        'commission',
        'referenceId',
        'type',
        'status',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'commission' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
    ];

    /**
     * تراکنش متعلق به چه تکنسینی است
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    /**
     * تراکنش مربوط به چه سفارشی است
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
