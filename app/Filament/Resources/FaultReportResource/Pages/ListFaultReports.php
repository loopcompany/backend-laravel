<?php

namespace App\Filament\Resources\FaultReportResource\Pages;

use App\Filament\Resources\FaultReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaultReports extends ListRecords
{
    protected static string $resource = FaultReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
 
        ];
    }
}
