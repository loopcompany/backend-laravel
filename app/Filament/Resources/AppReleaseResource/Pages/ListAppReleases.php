<?php
namespace App\Filament\Resources\AppReleaseResource\Pages;
class ListAppReleases extends \Filament\Resources\Pages\ListRecords
{
    protected static string $resource = \App\Filament\Resources\AppReleaseResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
