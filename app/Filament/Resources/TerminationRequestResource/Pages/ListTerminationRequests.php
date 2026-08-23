<?php

namespace App\Filament\Resources\TerminationRequestResource\Pages;

use App\Filament\Resources\TerminationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTerminationRequests extends ListRecords
{
    protected static string $resource = TerminationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Removed Create button as per requirements
        ];
    }
}
