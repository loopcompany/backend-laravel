<?php

namespace App\Filament\Resources\UserChatResource\Pages;

use App\Filament\Resources\UserChatResource;
use Filament\Resources\Pages\ListRecords;

class ListUserChats extends ListRecords
{
    protected static string $resource = UserChatResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
