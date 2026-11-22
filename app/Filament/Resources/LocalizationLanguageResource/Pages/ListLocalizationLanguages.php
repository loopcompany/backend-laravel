<?php
namespace App\Filament\Resources\LocalizationLanguageResource\Pages;
class ListLocalizationLanguages extends \Filament\Resources\Pages\ListRecords { protected static string $resource=\App\Filament\Resources\LocalizationLanguageResource::class; protected function getHeaderActions():array{return [\Filament\Actions\CreateAction::make()];} }
