<?php

namespace App\Filament\Resources\LetterRateCategoryResource\Pages;

use App\Filament\Resources\LetterRateCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLetterRateCategory extends EditRecord
{
    protected static string $resource = LetterRateCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
