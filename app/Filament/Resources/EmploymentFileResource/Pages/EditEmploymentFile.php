<?php

namespace App\Filament\Resources\EmploymentFileResource\Pages;

use App\Filament\Resources\EmploymentFileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmploymentFile extends EditRecord
{
    use HandlesConfidentialSection;

    protected static string $resource = EmploymentFileResource::class;

    private array $before = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillConfidential($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->guardConfidential($data);
    }

    protected function beforeSave(): void
    {
        $this->before = $this->snapshot($this->record);
    }

    protected function afterSave(): void
    {
        $this->logChanges($this->record, $this->before);
    }
}
