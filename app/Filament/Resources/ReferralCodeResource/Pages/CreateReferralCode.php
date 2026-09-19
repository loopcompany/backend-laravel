<?php

namespace App\Filament\Resources\ReferralCodeResource\Pages;

use App\Filament\Resources\ReferralCodeResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateReferralCode extends CreateRecord
{
    protected static string $resource = ReferralCodeResource::class;

    protected function afterCreate(): void
    {
        $this->record->load('user');

        Notification::make()
            ->title('کد معرف ایجاد شد')
            ->body("کد {$this->record->code} برای کاربر {$this->record->user?->phone} ایجاد شد.")
            ->success()
            ->send();
    }
}
