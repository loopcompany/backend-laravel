<?php

namespace App\Filament\Resources\ReportViolationResource\Pages;

use App\Filament\Resources\ReportViolationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReportViolation extends EditRecord
{
    protected static string $resource = ReportViolationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
