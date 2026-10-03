<?php

namespace App\Filament\Resources\EmploymentFileResource\Pages;

use App\Filament\Resources\EmploymentFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmploymentFiles extends ListRecords
{
    protected static string $resource = EmploymentFileResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
