<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegionResource\Pages;
use App\Filament\Resources\RegionResource\RelationManagers;
use App\Filament\Resources\RegionResource\RelationManagers\UsersRelationManager;
use App\Models\Region;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RegionResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = Region::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'مناطق';

    protected static ?string $modelLabel = 'منطقه';

    protected static ?string $pluralModelLabel = 'مناطق';

    protected static ?string $navigationGroup = 'مدیریت مکان';

    protected static ?int $navigationSort = 3;

    protected static function getViewPermission(): string
    {
        return 'view-regions';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-regions';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-regions';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-regions';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات منطقه')
                    ->schema([
                        Forms\Components\Select::make('city_id')
                            ->label('شهر')
                            ->relationship('city', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('code')
                            ->label('کد منطقه')
                            ->numeric(),
                        Forms\Components\TextInput::make('title')
                            ->label('نام منطقه')
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
                Tables\Columns\TextColumn::make('city.province.title')
                    ->label('استان')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.title')
                    ->label('شهر')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('نام منطقه')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('تعداد سفارش‌ها')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state == 0 => 'gray',
                        $state <= 10 => 'success',
                        $state <= 50 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('تعداد کاربران')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state <= 10 => 'success',
                        $state <= 50 => 'warning',
                        default => 'danger',
                    }),
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
                Tables\Filters\SelectFilter::make('city_id')
                    ->label('شهر')
                    ->relationship('city', 'title')
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
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegions::route('/'),
            'create' => Pages\CreateRegion::route('/create'),
            'edit' => Pages\EditRegion::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withCount(['users', 'orders']);
    }


}
