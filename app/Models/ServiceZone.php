<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Part of a district (منطقه) that is only partly inside the service area.
 *
 * A district with zones is selected zone by zone on the admin map; the zones of
 * one district tile it exactly (see database/data/build-tehran-district-zones.py).
 */
class ServiceZone extends Model
{
    protected $fillable = [
        'region_id',
        'code',
        'title',
        'boundary',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function mapRadii(): BelongsToMany
    {
        return $this->belongsToMany(MapRadius::class, 'map_radius_service_zone')->withTimestamps();
    }

    /**
     * The border polygon as a decoded GeoJSON geometry.
     */
    public function geometry(): ?array
    {
        return $this->boundary ? json_decode($this->boundary, true) : null;
    }
}
