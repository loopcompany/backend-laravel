<?php

namespace App\Filament\Resources\CategoryFieldResource\Pages;

use App\Filament\Resources\CategoryFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategoryFields extends ListRecords
{
    protected static string $resource = CategoryFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }


}
