<?php

namespace App\Filament\Resources\OrganizationContractRequestResource\Pages;

use App\Filament\Resources\OrganizationContractRequestResource;
use App\Helpers\Helper;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditOrganizationContractRequest extends EditRecord
{
    protected static string $resource = OrganizationContractRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $oldNeedDocs = $this->record->need_docs ?? '';
        $newNeedDocs = $data['need_docs'] ?? '';

        if (($oldNeedDocs !== $newNeedDocs) && !empty($newNeedDocs) && !is_null($newNeedDocs)) {
            Log::info('595134 sent', ['phone' => $this->record->user?->phone, 'organization_name' => $this->record->organization?->organization_name]);
            Helper::send_sms($this->record->user?->phone, 595134, ['NAME'], [$this->record->organization?->organization_name]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // بررسی تغییر وضعیت به "انجام شده" (2)
        $this->record->refresh();
        // اگر uploaded_by_admin_at خالی باشه، الان ست کن
        if (!$this->record->uploaded_by_admin_at && !is_null($this->record->contract_file_path)) {

            if ($this->record->user?->phone && $this->record->organization?->organization_name) {
                Log::info('283705 sent', ['phone' => $this->record->user?->phone, 'organization_name' => $this->record->organization?->organization_name]);

                Helper::send_sms($this->record->user?->phone, 283705, ['NAME'], [$this->record->organization?->organization_name]);
            }
            $this->record->update(['uploaded_by_admin_at' => now()]);
        }

    }

}
