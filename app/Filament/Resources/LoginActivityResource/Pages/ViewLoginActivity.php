<?php

namespace App\Filament\Resources\LoginActivityResource\Pages;

use App\Filament\Resources\LoginActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLoginActivity extends ViewRecord
{
    protected static string $resource = LoginActivityResource::class;

    protected static ?string $title = 'مشاهده فعالیت ورود/خروج';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف')
                ->visible(fn () => auth('admin')->user()?->can('delete-login-activities') ?? false)
                ->requiresConfirmation()
                ->modalHeading('حذف فعالیت')
                ->modalDescription('آیا از حذف این فعالیت اطمینان دارید؟')
                ->modalSubmitActionLabel('بله، حذف شود')
                ->modalCancelActionLabel('انصراف'),
        ];
    }
}
