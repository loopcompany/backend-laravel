<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MapRadiusResource\Pages;
use App\Filament\Resources\MapRadiusResource\RelationManagers;
use App\Models\MapRadius;
use App\Forms\Components\MapPicker;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MapRadiusResource extends Resource
{
    use HasFilamentPermissions;
    protected static ?string $model = MapRadius::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $modelLabel = 'شعاع نقشه';

    protected static ?string $pluralModelLabel = 'شعاع نقشه';

    protected static ?string $navigationLabel = 'شعاع نقشه';

    public static function canCreate(): bool
    {
        return false;
    }
    protected static function getViewPermission(): string
    {
        return 'view-map';
    }

    protected static function getCreatePermission(): string
    {
        return false;
    }

    protected static function getEditPermission(): string
    {
        return 'edit-map';
    }

    protected static function getDeletePermission(): string
    {
        return false;
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('radius')
                    ->label('شعاع (متر)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(1)
                    ->suffix('متر')
                    ->lazy(),
                MapPicker::make('map')
                    ->label('موقعیت مکانی')
                    ->columnSpanFull()
                    ->dehydrated(false),
                Forms\Components\Hidden::make('latitude')
                    ->required()
                    ->default(35.6892),
                Forms\Components\Hidden::make('longitude')
                    ->required()
                    ->default(51.3890),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('radius')
                    ->label('شعاع')
                    ->suffix(' متر')
                    ->sortable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->label('عرض جغرافیایی')
                    ->sortable(),
                Tables\Columns\TextColumn::make('longitude')
                    ->label('طول جغرافیایی')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین بروزرسانی')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Removed delete bulk action
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMapRadii::route('/'),
            'edit' => Pages\EditMapRadius::route('/{record}/edit'),
        ];
    }
}
