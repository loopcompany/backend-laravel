<?php

namespace App\Filament\Resources\FaultReportResource\Pages;

use App\Filament\Resources\FaultReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFaultReport extends EditRecord
{
    protected static string $resource = FaultReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('مشاهده'),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
