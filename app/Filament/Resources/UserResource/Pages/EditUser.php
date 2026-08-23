<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // بارگذاری اطلاعات سازمان برای نمایش در فرم
        if ($this->record->organization) {
            $data['organization'] = [
                'organization_name' => $this->record->organization->organization_name,
                'organization_code' => $this->record->organization->organization_code,
                'organization_phone' => $this->record->organization->organization_phone,
                'organization_address' => $this->record->organization->organization_address,
                'manager_full_name' => $this->record->organization->manager_full_name,
                'manager_national_code' => $this->record->organization->manager_national_code,
                'profile_image' => $this->record->organization->profile_image,
                'profile_status' => $this->record->organization->profile_status,
                'profile_approved_at' => $this->record->organization->profile_approved_at,
                'profile_rejection_reason' => $this->record->organization->profile_rejection_reason,
                'contract_status' => $this->record->organization->contract_status,
                'contract_approved_at' => $this->record->organization->contract_approved_at,
                'contract_rejection_reason' => $this->record->organization->contract_rejection_reason,
            ];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // حذف داده‌های organization از داده اصلی user
        if (isset($data['organization'])) {
            unset($data['organization']);
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        // ذخیره یا به‌روزرسانی اطلاعات سازمان
        $data = $this->form->getState();
        
        if ($this->record->account_type !== 'individual' && isset($data['organization'])) {
            $this->record->organization()->updateOrCreate(
                ['user_id' => $this->record->id],
                $data['organization']
            );
        } elseif ($this->record->account_type === 'individual' && $this->record->organization) {
            // اگر نوع حساب دیگه سازمانی نیست، اطلاعات سازمان رو حذف کن
            $this->record->organization()->delete();
        }
    }
}
