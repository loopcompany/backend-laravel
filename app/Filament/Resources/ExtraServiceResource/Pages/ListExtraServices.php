<?php

namespace App\Filament\Resources\ExtraServiceResource\Pages;

use App\Filament\Resources\ExtraServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExtraServices extends ListRecords
{
    protected static string $resource = ExtraServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
