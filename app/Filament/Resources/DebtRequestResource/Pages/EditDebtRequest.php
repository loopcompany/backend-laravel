<?php

namespace App\Filament\Resources\DebtRequestResource\Pages;

use App\Filament\Resources\DebtRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDebtRequest extends EditRecord
{
    protected static string $resource = DebtRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
