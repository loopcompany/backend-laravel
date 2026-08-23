<?php

namespace App\Filament\Resources\LoginActivityResource\Pages;

use App\Filament\Resources\LoginActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoginActivities extends ListRecords
{
    protected static string $resource = LoginActivityResource::class;

    protected static ?string $title = 'فعالیت‌های ورود/خروج';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('بروزرسانی')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->dispatch('$refresh')),
        ];
    }
}
