<?php

namespace App\Filament\Resources\EducationRequestResource\Pages;

use App\Filament\Resources\EducationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEducationRequest extends EditRecord
{
    protected static string $resource = EducationRequestResource::class;

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
