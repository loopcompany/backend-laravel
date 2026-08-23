<?php

namespace App\Filament\Resources\ManpowerRequestResource\Pages;

use App\Filament\Resources\ManpowerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListManpowerRequests extends ListRecords
{
    protected static string $resource = ManpowerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Removed Create button as per requirements
        ];
    }
}
