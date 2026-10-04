<?php

namespace App\Filament\Resources\CooperationRequestResource\Pages;

use App\Filament\Resources\CooperationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCooperationRequests extends ListRecords
{
    protected static string $resource = CooperationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('ثبت درخواست حضوری/تلفنی'),
        ];
    }
}
