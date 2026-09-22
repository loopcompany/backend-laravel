<?php

namespace App\Services;

use App\Models\MapRadius;
use App\Models\Region;
use App\Models\ServiceZone;
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
    private const ZONES_CACHE_KEY = 'districts.zones';
    private const CACHE_TTL = 3600;

    /**
     * Per-instance memo. Without it every locate() would deserialise the whole
     * polygon set back out of the cache store, which dwarfs the actual maths.
     */
    private ?Collection $districts = null;

    private ?Collection $zones = null;

    private ?MapRadius $serviceArea = null;

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

    /**
     * Zones of the districts that are split, keyed by region id.
     */
    public function zones(): Collection
    {
        return $this->zones ??= Cache::remember(self::ZONES_CACHE_KEY, self::CACHE_TTL, function () {
            return ServiceZone::query()
                ->get(['id', 'region_id', 'code', 'title', 'boundary'])
                ->map(fn (ServiceZone $z) => [
                    'id' => $z->id,
                    'region_id' => $z->region_id,
                    'code' => $z->code,
                    'title' => $z->title,
                    'geometry' => $geometry = json_decode($z->boundary, true),
                    'bbox' => $this->boundingBox($geometry),
                ])
                ->groupBy('region_id');
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::ZONES_CACHE_KEY);
    }

    /**
     * The district containing the point, or null if none does.
     */
    public function locate(float $latitude, float $longitude): ?array
    {
        return $this->firstContaining($this->districts(), $latitude, $longitude);
    }

    /**
     * The zone of a split district containing the point, or null if the
     * district is not split (or the point is not in it).
     */
    public function locateZone(array $district, float $latitude, float $longitude): ?array
    {
        return $this->firstContaining($this->zones()->get($district['id'], collect()), $latitude, $longitude);
    }

    private function firstContaining(iterable $shapes, float $latitude, float $longitude): ?array
    {
        foreach ($shapes as $shape) {
            // Cheap rejection first: only ~1 of 22 bounding boxes can match.
            [$minLng, $minLat, $maxLng, $maxLat] = $shape['bbox'];

            if ($longitude < $minLng || $longitude > $maxLng
                || $latitude < $minLat || $latitude > $maxLat) {
                continue;
            }

            if ($this->geometryContains($shape['geometry'], $latitude, $longitude)) {
                return $shape;
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
        return $this->serviceArea()?->regions->pluck('id')->all() ?? [];
    }

    /**
     * Zone ids (parts of split districts) that are in the service area.
     *
     * @return array<int>
     */
    public function serviceAreaZoneIds(): array
    {
        return $this->serviceArea()?->zones->pluck('id')->all() ?? [];
    }

    private function serviceArea(): ?MapRadius
    {
        return $this->serviceArea ??= MapRadius::with(['regions:id', 'zones:id'])->oldest('id')->first();
    }

    /**
     * Where the point is and whether it is served.
     *
     * A district that is split into zones is served zone by zone; any other
     * district is served when it is selected whole.
     *
     * @return array{covered: bool, region: ?array, zone: ?array}
     */
    public function coverage(float $latitude, float $longitude): array
    {
        $district = $this->locate($latitude, $longitude);

        if ($district === null) {
            return ['covered' => false, 'region' => null, 'zone' => null];
        }

        if ($this->zones()->has($district['id'])) {
            $zone = $this->locateZone($district, $latitude, $longitude);

            return [
                'covered' => $zone !== null && in_array($zone['id'], $this->serviceAreaZoneIds(), true),
                'region' => $district,
                'zone' => $zone,
            ];
        }

        return [
            'covered' => in_array($district['id'], $this->serviceAreaRegionIds(), true),
            'region' => $district,
            'zone' => null,
        ];
    }

    /**
     * Is the point inside the area the admin selected?
     */
    public function isCovered(float $latitude, float $longitude): bool
    {
        return $this->coverage($latitude, $longitude)['covered'];
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
