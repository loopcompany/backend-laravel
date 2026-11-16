<?php
namespace App\Filament\Resources\PermissionResource\Pages;
class ListPermissions extends \Filament\Resources\Pages\ListRecords {
    protected static string $resource=\App\Filament\Resources\PermissionResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
