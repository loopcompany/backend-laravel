<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateContract extends CreateRecord
{
    protected static string $resource = ContractResource::class;

    /**
     * تنظیم خودکار created_by قبل از ذخیره
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        return $data;
    }

    /**
     * پیام موفقیت فارسی
     */
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'قرارداد با موفقیت ثبت شد';
    }

    /**
     * ریدایرکت به لیست بعد از ذخیره
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
