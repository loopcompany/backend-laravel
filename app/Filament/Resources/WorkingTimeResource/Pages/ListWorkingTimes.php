<?php

namespace App\Filament\Resources\WorkingTimeResource\Pages;

use App\Filament\Resources\WorkingTimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkingTimes extends ListRecords
{
    protected static string $resource = WorkingTimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
