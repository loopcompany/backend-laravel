<?php

namespace App\Filament\Resources\EmploymentFileResource\Pages;

use App\Filament\Resources\EmploymentFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEmploymentFile extends ViewRecord
{
    use HandlesConfidentialSection;

    protected static string $resource = EmploymentFileResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillConfidential($data);
    }
}
