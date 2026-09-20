<?php

namespace App\Filament\Resources\MapRadiusResource\Pages;

use App\Filament\Resources\MapRadiusResource;
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
        $data['regions'] = $this->getRecord()->regions()->pluck('regions.id')->all();

        return $data;
    }

    /**
     * The district picker is not dehydrated (the selection lives on a pivot,
     * not on a column), so sync it here from the raw form state.
     */
    protected function afterSave(): void
    {
        $ids = collect($this->data['regions'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->getRecord()->regions()->sync($ids);

        DistrictLocator::flushCache();
    }
}
