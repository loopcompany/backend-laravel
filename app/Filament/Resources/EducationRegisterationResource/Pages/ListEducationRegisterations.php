<?php

namespace App\Filament\Resources\EducationRegisterationResource\Pages;

use App\Filament\Resources\EducationRegisterationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEducationRegisterations extends ListRecords
{
    protected static string $resource = EducationRegisterationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
