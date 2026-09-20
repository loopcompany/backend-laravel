<?php

namespace App\Services;

use App\Models\MapRadius;
use App\Models\Region;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves a coordinate to the district (منطقه) that contains it, and answers
 * whether that coordinate falls inside the configured service area.
 *
 * Borders are stored as GeoJSON text on `regions.boundary`, so the test is done
 * here in PHP rather than with ST_Contains. Across Tehran's 22 districts
 * (~1500 vertices) a warm lookup costs ~5 µs, and it keeps one code path working
 * on both MySQL and the SQLite connection used by the test suite.
 */
class DistrictLocator
{
    private const CACHE_KEY = 'districts.with_boundary';
    private const CACHE_TTL = 3600;

    /**
     * Per-instance memo. Without it every locate() would deserialise the whole
     * polygon set back out of the cache store, which dwarfs the actual maths.
     */
    private ?Collection $districts = null;

    /**
     * All regions that have a border polygon, as lightweight arrays.
     */
    public function districts(): Collection
    {
        return $this->districts ??= Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Region::query()
                ->whereNotNull('boundary')
                ->where('is_show', 1)
                ->get(['id', 'city_id', 'code', 'title', 'latitude', 'longitude', 'boundary'])
                ->map(fn (Region $r) => [
                    'id' => $r->id,
                    'city_id' => $r->city_id,
                    'code' => $r->code,
                    'title' => $r->title,
                    'latitude' => $r->latitude,
                    'longitude' => $r->longitude,
                    'geometry' => $geometry = json_decode($r->boundary, true),
                    'bbox' => $this->boundingBox($geometry),
                ])
                ->values();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The district containing the point, or null if none does.
     */
    public function locate(float $latitude, float $longitude): ?array
    {
        foreach ($this->districts() as $district) {
            // Cheap rejection first: only ~1 of 22 bounding boxes can match.
            [$minLng, $minLat, $maxLng, $maxLat] = $district['bbox'];

            if ($longitude < $minLng || $longitude > $maxLng
                || $latitude < $minLat || $latitude > $maxLat) {
                continue;
            }

            if ($this->geometryContains($district['geometry'], $latitude, $longitude)) {
                return $district;
            }
        }

        return null;
    }

    /**
     * Region ids that make up the configured service area.
     *
     * @return array<int>
     */
    public function serviceAreaRegionIds(): array
    {
        $area = MapRadius::with('regions:id')->first();

        return $area ? $area->regions->pluck('id')->all() : [];
    }

    /**
     * Is the point inside one of the districts the admin selected?
     */
    public function isCovered(float $latitude, float $longitude): bool
    {
        $district = $this->locate($latitude, $longitude);

        return $district !== null
            && in_array($district['id'], $this->serviceAreaRegionIds(), true);
    }

    /**
     * [minLng, minLat, maxLng, maxLat] covering a GeoJSON geometry.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    private function boundingBox(?array $geometry): array
    {
        if (! $geometry) {
            return [0.0, 0.0, 0.0, 0.0];
        }

        $polygons = $geometry['type'] === 'MultiPolygon'
            ? $geometry['coordinates']
            : [$geometry['coordinates']];

        $lngs = $lats = [];

        foreach ($polygons as $polygon) {
            foreach ($polygon[0] as [$lng, $lat]) {
                $lngs[] = $lng;
                $lats[] = $lat;
            }
        }

        return [min($lngs), min($lats), max($lngs), max($lats)];
    }

    /**
     * Point-in-polygon for a GeoJSON Polygon or MultiPolygon.
     */
    private function geometryContains(?array $geometry, float $lat, float $lng): bool
    {
        if (! $geometry || ! isset($geometry['type'], $geometry['coordinates'])) {
            return false;
        }

        $polygons = $geometry['type'] === 'MultiPolygon'
            ? $geometry['coordinates']
            : [$geometry['coordinates']];

        foreach ($polygons as $polygon) {
            // coordinates[0] is the outer ring, the rest are holes
            if (! $this->ringContains($polygon[0], $lat, $lng)) {
                continue;
            }

            foreach (array_slice($polygon, 1) as $hole) {
                if ($this->ringContains($hole, $lat, $lng)) {
                    continue 2;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Ray casting. Ring vertices are GeoJSON order: [longitude, latitude].
     */
    private function ringContains(array $ring, float $lat, float $lng): bool
    {
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];

            if (($yi > $lat) !== ($yj > $lat)
                && $lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
