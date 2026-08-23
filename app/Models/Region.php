<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Region extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'city_id',
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
     * Get the city that owns the region.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    public function orders()
    {
        return $this->hasManyThrough(
            Order::class,
            UserAddress::class,
            'region_id',       // Foreign key on UserAddress table
            'user_address_id', // Foreign key on Order table
            'id',              // Local key on Region table
            'id'               // Local key on UserAddress table
        );
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
