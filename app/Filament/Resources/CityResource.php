<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Filament\Resources\CityResource\RelationManagers;
use App\Models\City;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CityResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = City::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'شهرها';

    protected static ?string $modelLabel = 'شهر';

    protected static ?string $pluralModelLabel = 'شهرها';

    protected static ?string $navigationGroup = 'مدیریت مکان';

    protected static ?int $navigationSort = 2;

    protected static function getViewPermission(): string
    {
        return 'view-cities';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-cities';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-cities';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-cities';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات شهر')
                    ->schema([
                        Forms\Components\Select::make('province_id')
                            ->label('استان')
                            ->relationship('province', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('نام شهر')
                            ->required()
                            ->maxLength(191),
                        Forms\Components\TextInput::make('latitude')
                            ->label('عرض جغرافیایی')
                            ->numeric()
                            ->step(0.000001),
                        Forms\Components\TextInput::make('longitude')
                            ->label('طول جغرافیایی')
                            ->numeric()
                            ->step(0.000001),
                        Forms\Components\Toggle::make('is_show')
                            ->label('نمایش')
                            ->default(true)
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),
                Tables\Columns\TextColumn::make('province.title')
                    ->label('استان')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('نام شهر')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('regions_count')
                    ->label('تعداد مناطق')
                    ->counts('regions')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_show')
                    ->label('نمایش')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاریخ بروزرسانی')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('province_id')
                    ->label('استان')
                    ->relationship('province', 'title')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_show')
                    ->label('نمایش')
                    ->placeholder('همه')
                    ->trueLabel('نمایش داده شود')
                    ->falseLabel('نمایش داده نشود'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
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
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
