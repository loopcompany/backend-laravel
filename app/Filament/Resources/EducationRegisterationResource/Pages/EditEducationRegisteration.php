<?php

namespace App\Filament\Resources\EducationRegisterationResource\Pages;

use App\Filament\Resources\EducationRegisterationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEducationRegisteration extends EditRecord
{
    protected static string $resource = EducationRegisterationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
