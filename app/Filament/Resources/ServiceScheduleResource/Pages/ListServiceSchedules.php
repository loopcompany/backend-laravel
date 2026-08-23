<?php

namespace App\Filament\Resources\ServiceScheduleResource\Pages;

use App\Filament\Resources\ServiceScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceSchedules extends ListRecords
{
    protected static string $resource = ServiceScheduleResource::class;

    protected static ?string $title = 'مدیریت گزینه‌های زمان‌بندی سرویس';

    protected function getHeaderActions(): array
    {
        return [
            // دکمه ایجاد غیرفعال شده - فقط ویرایش مجاز است
        ];
    }
}
