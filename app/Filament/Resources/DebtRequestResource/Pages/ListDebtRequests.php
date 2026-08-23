<?php

namespace App\Filament\Resources\DebtRequestResource\Pages;

use App\Filament\Resources\DebtRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListDebtRequests extends ListRecords
{
    protected static string $resource = DebtRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // درخواست‌ها فقط از طریق API تکنسین‌ها ایجاد می‌شوند
        ];
    }
}
