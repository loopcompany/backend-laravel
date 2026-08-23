<?php

namespace App\Filament\Resources\WarrantyCategoryResource\Pages;

use App\Filament\Resources\WarrantyCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWarrantyCategory extends EditRecord
{
    protected static string $resource = WarrantyCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
