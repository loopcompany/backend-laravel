<?php

namespace App\Filament\Resources\SupplementaryInsuranceResource\Pages;

use App\Filament\Resources\SupplementaryInsuranceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplementaryInsurance extends EditRecord
{
    protected static string $resource = SupplementaryInsuranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
