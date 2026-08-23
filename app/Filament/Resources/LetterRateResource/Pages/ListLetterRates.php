<?php

namespace App\Filament\Resources\LetterRateResource\Pages;

use App\Filament\Resources\LetterRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLetterRates extends ListRecords
{
    protected static string $resource = LetterRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}