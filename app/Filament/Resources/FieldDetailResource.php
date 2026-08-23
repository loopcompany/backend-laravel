<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FieldDetailResource\Pages;
use App\Filament\Resources\FieldDetailResource\RelationManagers;
use App\Filament\Resources\FieldDetailResource\RelationManagers\FieldChartsRelationManager;
use App\Models\FieldDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FieldDetailResource extends Resource
{
    protected static ?string $model = FieldDetail::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FieldChartsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            // 'index' => Pages\ListFieldDetails::route('/'),
            // 'create' => Pages\CreateFieldDetail::route('/create'),
            'edit' => Pages\EditFieldDetail::route('/{record}/edit'),
        ];
    }



    public static function canCreate(): bool
    {
        return false;
    }
}
