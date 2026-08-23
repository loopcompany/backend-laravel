<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warranty extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'warranty_category_id'
    ];

    public function category()
    {
        return $this->belongsTo(WarrantyCategory::class, 'warranty_category_id');
    }
}
