<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'latitude',
        'longitude',
        'is_show',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_show' => 'boolean',
    ];

    /**
     * Get the cities for the province.
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
