<?php

namespace App\Filament\Resources\ReportViolationResource\Pages;

use App\Filament\Resources\ReportViolationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReportViolations extends ListRecords
{
    protected static string $resource = ReportViolationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
