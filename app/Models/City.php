<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'province_id',
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
     * Get the province that owns the city.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get the regions for the city.
     */
    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }
}
