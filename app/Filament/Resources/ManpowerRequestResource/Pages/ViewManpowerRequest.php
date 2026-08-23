<?php

namespace App\Filament\Resources\ManpowerRequestResource\Pages;

use App\Filament\Resources\ManpowerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewManpowerRequest extends ViewRecord
{
    protected static string $resource = ManpowerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
