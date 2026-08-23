<?php

namespace App\Filament\Resources\ManpowerRequestResource\Pages;

use App\Filament\Resources\ManpowerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManpowerRequest extends EditRecord
{
    protected static string $resource = ManpowerRequestResource::class;

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
