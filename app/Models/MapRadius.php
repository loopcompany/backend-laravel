<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * The service area shown on the admin map.
 *
 * Historically this was a circle (centre + radius). It is now defined by the
 * set of districts (مناطق), or parts of districts (ServiceZone), selected on the map; the radius/centre columns are
 * kept only so already-published app builds keep getting a response they
 * understand from /api/locations/radii.
 */
class MapRadius extends Model
{
    protected $fillable = [
        'radius',
        'latitude',
        'longitude',
    ];

    /**
     * The single service-area row, created on first access so the admin map
     * always has something to edit.
     */
    public static function current(): self
    {
        return static::query()->oldest('id')->first()
            ?? static::create(['radius' => null, 'latitude' => 35.6892, 'longitude' => 51.3890]);
    }

    /**
     * The districts that make up the service area.
     */
    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class, 'map_radius_region')->withTimestamps();
    }

    /**
     * Selected parts of the districts that are split into zones (see ServiceZone).
     */
    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(ServiceZone::class, 'map_radius_service_zone')->withTimestamps();
    }
}
