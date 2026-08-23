<?php

namespace App\Filament\Resources\TechnicianPollResource\Pages;

use App\Filament\Resources\TechnicianPollResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTechnicianPoll extends ViewRecord
{
    protected static string $resource = TechnicianPollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
