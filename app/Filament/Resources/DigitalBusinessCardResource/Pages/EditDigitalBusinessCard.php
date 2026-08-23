<?php

namespace App\Filament\Resources\DigitalBusinessCardResource\Pages;

use App\Filament\Resources\DigitalBusinessCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDigitalBusinessCard extends EditRecord
{
    protected static string $resource = DigitalBusinessCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
