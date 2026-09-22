<?php

namespace App\Forms\Components;

use App\Models\Region;
use Filament\Forms\Components\Field;

/**
 * Map field for picking the districts (مناطق) that make up the service area.
 *
 * The map draws every region that has a border polygon; clicking one toggles it.
 * A district that is split into zones (ServiceZone) is drawn as its zones, each
 * toggled on its own. State is a list of keys, "r:{region id}" for a whole
 * district and "z:{zone id}" for a zone, which EditMapRadius writes onto the
 * map_radius_region and map_radius_service_zone pivots.
 */
class DistrictPicker extends Field
{
    protected string $view = 'filament.components.district-picker';

    protected ?int $cityId = null;

    public function city(int $cityId): static
    {
        $this->cityId = $cityId;

        return $this;
    }

    public static function regionKey(int $id): string
    {
        return "r:{$id}";
    }

    public static function zoneKey(int $id): string
    {
        return "z:{$id}";
    }

    /**
     * Split state keys back into region ids and zone ids.
     *
     * @return array{regions: array<int>, zones: array<int>}
     */
    public static function parseKeys(array $keys): array
    {
        $ids = ['regions' => [], 'zones' => []];

        foreach (array_unique($keys) as $key) {
            if (preg_match('/^([rz]):(\d+)$/', (string) $key, $m)) {
                $ids[$m[1] === 'r' ? 'regions' : 'zones'][] = (int) $m[2];
            }
        }

        return $ids;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(function (DistrictPicker $component, $state) {
            $component->state(collect($state ?? [])->map(fn ($v) => (string) $v)->values()->all());
        });

        $this->dehydrateStateUsing(
            fn ($state) => collect($state ?? [])->map(fn ($v) => (string) $v)->unique()->values()->all()
        );
    }

    /**
     * The areas to draw, as plain arrays for the view.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDistricts(): array
    {
        return Region::query()
            ->whereNotNull('boundary')
            ->where('is_show', 1)
            ->when($this->cityId, fn ($q) => $q->where('city_id', $this->cityId))
            ->with('zones')
            ->orderByRaw('CAST(code AS UNSIGNED)')
            ->get(['id', 'code', 'title', 'latitude', 'longitude', 'boundary'])
            ->flatMap(fn (Region $r) => $r->zones->isEmpty()
                ? [[
                    'key' => self::regionKey($r->id),
                    'title' => $r->title,
                    'geometry' => json_decode($r->boundary, true),
                ]]
                : $r->zones->sortBy('code')->map(fn ($z) => [
                    'key' => self::zoneKey($z->id),
                    'title' => $z->title,
                    'geometry' => $z->geometry(),
                ])->values()->all())
            ->values()
            ->all();
    }
}
