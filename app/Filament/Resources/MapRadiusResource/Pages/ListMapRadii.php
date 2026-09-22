<?php

namespace App\Filament\Resources\MapRadiusResource\Pages;

use App\Filament\Resources\MapRadiusResource;
use App\Models\MapRadius;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMapRadii extends ListRecords
{
    protected static string $resource = MapRadiusResource::class;

    /**
     * Old bookmarks and breadcrumbs land here; send them on to the map.
     */
    public function mount(): void
    {
        parent::mount();

        $this->redirect(MapRadiusResource::getUrl('edit', ['record' => MapRadius::current()]));
    }

    protected function getHeaderActions(): array
    {
        return [
            // Create action removed - only edit allowed
        ];
    }
}
