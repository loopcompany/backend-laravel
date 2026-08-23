<?php

namespace App\Filament\Resources\TerminationRequestResource\Pages;

use App\Filament\Resources\TerminationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTerminationRequest extends EditRecord
{
    protected static string $resource = TerminationRequestResource::class;

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
