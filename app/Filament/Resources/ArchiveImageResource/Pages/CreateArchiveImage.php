<?php

namespace App\Filament\Resources\ArchiveImageResource\Pages;

use App\Filament\Resources\ArchiveImageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateArchiveImage extends CreateRecord
{
    protected static string $resource = ArchiveImageResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تصویر با موفقیت به آرشیو اضافه شد';
    }
}
