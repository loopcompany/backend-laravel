<?php

namespace App\Filament\Resources\TechnicianChatResource\Pages;

use App\Filament\Resources\TechnicianChatResource;
use Filament\Resources\Pages\ListRecords;

class ListTechnicianChats extends ListRecords
{
    protected static string $resource = TechnicianChatResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
    
}
