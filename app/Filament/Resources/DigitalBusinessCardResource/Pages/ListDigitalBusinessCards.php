<?php

namespace App\Filament\Resources\DigitalBusinessCardResource\Pages;

use App\Filament\Resources\DigitalBusinessCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDigitalBusinessCards extends ListRecords
{
    protected static string $resource = DigitalBusinessCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make() ->url(fn (): string => route('admin.cards.create')), 
        ];
    }
}
