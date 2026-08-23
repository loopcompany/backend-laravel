<?php

namespace App\Filament\Resources\FaultReportResource\Pages;

use App\Filament\Resources\FaultReportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFaultReport extends CreateRecord
{
    protected static string $resource = FaultReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
