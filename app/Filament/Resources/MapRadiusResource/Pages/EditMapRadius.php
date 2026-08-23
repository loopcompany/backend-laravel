<?php

namespace App\Filament\Resources\MapRadiusResource\Pages;

use App\Filament\Resources\MapRadiusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMapRadius extends EditRecord
{
    protected static string $resource = MapRadiusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Delete action removed - only edit allowed
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
