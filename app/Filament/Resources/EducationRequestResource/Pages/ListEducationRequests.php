<?php

namespace App\Filament\Resources\EducationRequestResource\Pages;

use App\Filament\Resources\EducationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEducationRequests extends ListRecords
{
    protected static string $resource = EducationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // درخواست‌ها فقط از طریق API تکنسین‌ها ایجاد می‌شوند
        ];
    }
}
