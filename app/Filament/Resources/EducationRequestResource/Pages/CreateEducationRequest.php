<?php

namespace App\Filament\Resources\EducationRequestResource\Pages;

use App\Filament\Resources\EducationRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEducationRequest extends CreateRecord
{
    protected static string $resource = EducationRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
