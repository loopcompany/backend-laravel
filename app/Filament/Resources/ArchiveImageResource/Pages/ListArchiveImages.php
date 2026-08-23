<?php

namespace App\Filament\Resources\ArchiveImageResource\Pages;

use App\Filament\Resources\ArchiveImageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArchiveImages extends ListRecords
{
    protected static string $resource = ArchiveImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('افزودن تصویر جدید'),
        ];
    }
}
