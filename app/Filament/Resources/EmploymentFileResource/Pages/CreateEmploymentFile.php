<?php

namespace App\Filament\Resources\EmploymentFileResource\Pages;

use App\Filament\Resources\EmploymentFileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmploymentFile extends CreateRecord
{
    use HandlesConfidentialSection;

    protected static string $resource = EmploymentFileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->guardConfidential($data) + ['created_by' => auth('admin')->id()];
    }

    protected function afterCreate(): void
    {
        $this->record->logs()->create([
            'admin_id' => auth('admin')->id(),
            'action' => 'created',
            'created_at' => now(),
        ]);
    }
}
