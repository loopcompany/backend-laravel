<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExtraServiceCategory extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'category_id',
        'extra_service_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function extraService()
    {
        return $this->belongsTo(ExtraService::class);
    }
}
