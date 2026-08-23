<?php

namespace App\Filament\Resources\AdminAuthLogResource\Pages;

use App\Filament\Resources\AdminAuthLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdminAuthLog extends EditRecord
{
    protected static string $resource = AdminAuthLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
