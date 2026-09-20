<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * The service area shown on the admin map.
 *
 * Historically this was a circle (centre + radius). It is now defined by the
 * set of districts (مناطق) selected on the map; the radius/centre columns are
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
     * The districts that make up the service area.
     */
    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class, 'map_radius_region')->withTimestamps();
    }
}
