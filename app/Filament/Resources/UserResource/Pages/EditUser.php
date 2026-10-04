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
        // بارگذاری همه‌ی اطلاعات سازمان برای نمایش در فرم؛ فیلدی که این‌جا پر نشود
        // هنگام ذخیره با مقدار خالی بازنویسی می‌شود (قبلاً نام تجاری، نماینده و سابقه پاک می‌شدند).
        if ($this->record->organization) {
            $organization = $this->record->organization;
            $data['organization'] = $organization->only($organization->getFillable());
            $data['organization']['is_suspended'] = $organization->suspended_at !== null;
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
            $organizationData = $data['organization'];
            $isSuspended = (bool) ($organizationData['is_suspended'] ?? false);
            unset($organizationData['is_suspended']);

            $current = $this->record->organization;
            $organizationData['suspended_at'] = $isSuspended ? ($current?->suspended_at ?? now()) : null;
            if (!$isSuspended) {
                $organizationData['suspension_reason'] = null;
            }

            $this->record->organization()->updateOrCreate(
                ['user_id' => $this->record->id],
                $organizationData
            );
        } elseif ($this->record->account_type === 'individual' && $this->record->organization) {
            // اگر نوع حساب دیگه سازمانی نیست، اطلاعات سازمان رو حذف کن
            $this->record->organization()->delete();
        }
    }
}
