<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProcurementItem extends Model
{
    public const CONDITIONS = [
        'new' => 'نو',
        'used' => 'کارکرده',
    ];

    protected $fillable = [
        'order_procurement_id',
        'item_id',
        'title',
        'condition',
        'quantity',
        'unit_price',
        'description',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'integer',
    ];

    public function procurement(): BelongsTo
    {
        return $this->belongsTo(OrderProcurement::class, 'order_procurement_id');
    }

    public function totalPrice(): ?int
    {
        return $this->unit_price === null ? null : $this->unit_price * $this->quantity;
    }
}
