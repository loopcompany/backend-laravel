<?php

namespace App\Filament\Resources\TechnicianPollResource\Pages;

use App\Filament\Resources\TechnicianPollResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTechnicianPolls extends ListRecords
{
    protected static string $resource = TechnicianPollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // عملیات ایجاد غیرفعال است چون نظرات فقط از طریق API ثبت می‌شوند
        ];
    }
}
