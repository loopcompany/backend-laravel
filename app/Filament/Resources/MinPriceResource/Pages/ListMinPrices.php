<?php

namespace App\Filament\Resources\MinPriceResource\Pages;

use App\Filament\Resources\MinPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMinPrices extends ListRecords
{
    protected static string $resource = MinPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
