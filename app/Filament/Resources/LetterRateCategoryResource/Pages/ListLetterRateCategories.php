<?php

namespace App\Filament\Resources\LetterRateCategoryResource\Pages;

use App\Filament\Resources\LetterRateCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLetterRateCategories extends ListRecords
{
    protected static string $resource = LetterRateCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
