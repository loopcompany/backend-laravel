<?php

namespace App\Filament\Resources\OrganizationContractRequestResource\Pages;

use App\Filament\Resources\OrganizationContractRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrganizationContractRequests extends ListRecords
{
    protected static string $resource = OrganizationContractRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
