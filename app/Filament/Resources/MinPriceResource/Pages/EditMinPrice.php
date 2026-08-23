<?php

namespace App\Filament\Resources\MinPriceResource\Pages;

use App\Filament\Resources\MinPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMinPrice extends EditRecord
{
    protected static string $resource = MinPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
