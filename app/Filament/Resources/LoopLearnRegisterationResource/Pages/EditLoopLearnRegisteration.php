<?php

namespace App\Filament\Resources\LoopLearnRegisterationResource\Pages;

use App\Filament\Resources\LoopLearnRegisterationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoopLearnRegisteration extends EditRecord
{
    protected static string $resource = LoopLearnRegisterationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
