<?php

namespace App\Filament\Resources\OrganizationTermResource\Pages;

use App\Filament\Resources\OrganizationTermResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrganizationTerm extends EditRecord
{
    protected static string $resource = OrganizationTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
