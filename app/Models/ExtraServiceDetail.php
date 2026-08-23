<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraServiceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'extra_service_id',
        'title',
        'price',
        'brand',
        'model',
        'warranty',
        'test_duration',
        'barcode'
    ];

    public function extra_service()
    {
        return $this->belongsTo(ExtraService::class);
    }
}
