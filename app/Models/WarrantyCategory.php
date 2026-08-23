<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarrantyCategory extends Model
{
    protected $fillable = ['title','description'];

    public function warranties()
    {
        return $this->hasMany(Warranty::class, 'warranty_category_id');
    }
}
