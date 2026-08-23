<?php

namespace App\Filament\Resources\LoginActivityResource\Pages;

use App\Filament\Resources\LoginActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoginActivity extends EditRecord
{
    protected static string $resource = LoginActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
