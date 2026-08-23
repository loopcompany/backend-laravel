<?php

namespace App\Filament\Resources\ArchiveImageResource\Pages;

use App\Filament\Resources\ArchiveImageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewArchiveImage extends ViewRecord
{
    protected static string $resource = ArchiveImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
