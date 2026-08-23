<?php

namespace App\Filament\Resources\DebtRequestResource\Pages;

use App\Filament\Resources\DebtRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDebtRequest extends ViewRecord
{
    protected static string $resource = DebtRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
