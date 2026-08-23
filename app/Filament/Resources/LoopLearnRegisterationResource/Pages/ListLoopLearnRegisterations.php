<?php

namespace App\Filament\Resources\LoopLearnRegisterationResource\Pages;

use App\Filament\Resources\LoopLearnRegisterationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoopLearnRegisterations extends ListRecords
{
    protected static string $resource = LoopLearnRegisterationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
