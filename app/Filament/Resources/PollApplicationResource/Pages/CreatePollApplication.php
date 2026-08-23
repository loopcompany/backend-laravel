<?php

namespace App\Filament\Resources\PollApplicationResource\Pages;

use App\Filament\Resources\PollApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePollApplication extends CreateRecord
{
    protected static string $resource = PollApplicationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}