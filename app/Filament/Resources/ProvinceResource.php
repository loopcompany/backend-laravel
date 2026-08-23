<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProvinceResource\Pages;
use App\Filament\Resources\ProvinceResource\RelationManagers;
use App\Models\Province;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProvinceResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = Province::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'استان‌ها';

    protected static ?string $modelLabel = 'استان';

    protected static ?string $pluralModelLabel = 'استان‌ها';

    protected static ?string $navigationGroup = 'مدیریت مکان';

    protected static ?int $navigationSort = 1;

    protected static function getViewPermission(): string
    {
        return 'view-provinces';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-provinces';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-provinces';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-provinces';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات استان')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('کد استان')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('title')
                            ->label('نام استان')
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
                Tables\Columns\TextColumn::make('code')
                    ->label('کد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('نام استان')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cities_count')
                    ->label('تعداد شهرها')
                    ->counts('cities')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_show')
                    ->label('نمایش')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->jalaliDateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاریخ بروزرسانی')
                    ->jalaliDateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                 
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
            'index' => Pages\ListProvinces::route('/'),
            'create' => Pages\CreateProvince::route('/create'),
            'edit' => Pages\EditProvince::route('/{record}/edit'),
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
