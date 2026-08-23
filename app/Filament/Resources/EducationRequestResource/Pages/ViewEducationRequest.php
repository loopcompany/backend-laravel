<?php

namespace App\Filament\Resources\EducationRequestResource\Pages;

use App\Filament\Resources\EducationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEducationRequest extends ViewRecord
{
    protected static string $resource = EducationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('ویرایش'),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
