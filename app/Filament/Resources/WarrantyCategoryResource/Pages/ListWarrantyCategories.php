<?php

namespace App\Filament\Resources\WarrantyCategoryResource\Pages;

use App\Filament\Resources\WarrantyCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWarrantyCategories extends ListRecords
{
    protected static string $resource = WarrantyCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
