<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Region extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'city_id',
        'code',
        'title',
        'latitude',
        'longitude',
        'boundary',
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

    /**
     * The service areas this district has been selected for.
     */
    public function mapRadii(): BelongsToMany
    {
        return $this->belongsToMany(MapRadius::class, 'map_radius_region')->withTimestamps();
    }

    /**
     * The border polygon as a decoded GeoJSON geometry, or null when unmapped.
     */
    public function geometry(): ?array
    {
        return $this->boundary ? json_decode($this->boundary, true) : null;
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
