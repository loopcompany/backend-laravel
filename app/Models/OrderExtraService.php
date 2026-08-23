<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderExtraService extends Model
{
    protected $fillable = [
        'order_id',
        'extra_service_id',
        'extra_service_detail_id',
        'price',
        'title',
        'brand',
        'model',
        'warranty',
        'test_duration',
        'barcode',
        'number',
        'unit_price'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function extra_service()
    {
        return $this->belongsTo(ExtraService::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function extra_service_detail()
    {
        return $this->belongsTo(ExtraServiceDetail::class);
    }
}
