<?php

namespace App\Forms\Components;

use App\Models\Region;
use Filament\Forms\Components\Field;

/**
 * Map field for picking the districts (مناطق) that make up the service area.
 *
 * The map draws every region that has a border polygon; clicking one toggles it.
 * State is the selected region ids, which the resource saves onto the
 * map_radius_region pivot.
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(function (DistrictPicker $component, $state) {
            // Normalise whatever the relation handed us into a list of ints.
            $component->state(collect($state ?? [])->map(fn ($v) => (int) $v)->values()->all());
        });

        $this->dehydrateStateUsing(
            fn ($state) => collect($state ?? [])->map(fn ($v) => (int) $v)->unique()->values()->all()
        );
    }

    /**
     * The districts to draw, as plain arrays for the view.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDistricts(): array
    {
        return Region::query()
            ->whereNotNull('boundary')
            ->where('is_show', 1)
            ->when($this->cityId, fn ($q) => $q->where('city_id', $this->cityId))
            ->orderByRaw('CAST(code AS UNSIGNED)')
            ->get(['id', 'code', 'title', 'latitude', 'longitude', 'boundary'])
            ->map(fn (Region $r) => [
                'id' => $r->id,
                'code' => $r->code,
                'title' => $r->title,
                'geometry' => json_decode($r->boundary, true),
            ])
            ->all();
    }
}
