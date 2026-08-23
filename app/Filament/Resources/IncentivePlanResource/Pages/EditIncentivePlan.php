<?php

namespace App\Filament\Resources\IncentivePlanResource\Pages;

use App\Filament\Resources\IncentivePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIncentivePlan extends EditRecord
{
    protected static string $resource = IncentivePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'طرح تشویقی با موفقیت ویرایش شد';
    }
}

