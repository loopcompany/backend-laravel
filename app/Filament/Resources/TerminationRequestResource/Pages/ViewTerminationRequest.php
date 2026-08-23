<?php

namespace App\Filament\Resources\TerminationRequestResource\Pages;

use App\Filament\Resources\TerminationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTerminationRequest extends ViewRecord
{
    protected static string $resource = TerminationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
