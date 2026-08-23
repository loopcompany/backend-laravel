<?php

namespace App\Filament\Resources\FieldDetailResource\Pages;

use App\Filament\Resources\FieldDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFieldDetails extends ListRecords
{
    protected static string $resource = FieldDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
