<?php

namespace App\Filament\Resources\MapRadiusResource\Pages;

use App\Filament\Resources\MapRadiusResource;
use App\Forms\Components\DistrictPicker;
use App\Models\ServiceZone;
use App\Services\DistrictLocator;
use Filament\Resources\Pages\EditRecord;

class EditMapRadius extends EditRecord
{
    protected static string $resource = MapRadiusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Delete action removed - only edit allowed
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        $data['regions'] = [
            ...$record->regions()->pluck('regions.id')->map(DistrictPicker::regionKey(...)),
            ...$record->zones()->pluck('service_zones.id')->map(DistrictPicker::zoneKey(...)),
        ];

        return $data;
    }

    /**
     * The district picker is not dehydrated (the selection lives on pivots,
     * not on a column), so sync it here from the raw form state.
     */
    protected function afterSave(): void
    {
        $ids = DistrictPicker::parseKeys($this->data['regions'] ?? []);

        // A split district is served zone by zone, never whole.
        $splitRegionIds = ServiceZone::query()->distinct()->pluck('region_id')->all();

        $this->getRecord()->regions()->sync(array_values(array_diff($ids['regions'], $splitRegionIds)));
        $this->getRecord()->zones()->sync($ids['zones']);

        DistrictLocator::flushCache();
    }
}
