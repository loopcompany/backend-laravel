<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Field extends Model
{
    use SoftDeletes;
    protected $fillable = [
       'type',
       'title',
       'des',
       'image_path',
       'icon_name',
       'guide',
       'is_required',
       'is_package',
       'is_brand',
       'is_model',
       'has_user_descriptions',
       'border_radius'
    ];

    public function field_details() : HasMany
    {
        return $this->hasMany(FieldDetail::class)    ;
    }

    public function category_field_conditionals() : HasMany
    {
        return $this->hasMany(CategoryFieldConditional::class)    ;
    }

}
