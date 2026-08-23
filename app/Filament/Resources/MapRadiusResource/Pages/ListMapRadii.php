<?php

namespace App\Filament\Resources\MapRadiusResource\Pages;

use App\Filament\Resources\MapRadiusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMapRadii extends ListRecords
{
    protected static string $resource = MapRadiusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Create action removed - only edit allowed
        ];
    }
}
