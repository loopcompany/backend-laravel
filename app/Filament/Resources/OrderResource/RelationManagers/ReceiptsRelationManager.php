<?php
namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class ReceiptsRelationManager extends RelationManager
{
    protected static string $relationship = 'receipts';
    protected static ?string $title = 'دریافت‌ها و تسویه';

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return \App\Filament\Resources\OrderReceiptResource::canViewAny();
    }
    public function table(Table $table): Table
    {
        return \App\Filament\Resources\OrderReceiptResource::table($table);
    }
}
