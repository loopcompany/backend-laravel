<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExtraServiceResource\Pages;
use App\Filament\Resources\ExtraServiceResource\RelationManagers;
use App\Models\Access;
use App\Models\AdminAccess;
use App\Models\ExtraService;
use App\Models\Category;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\ExtraServiceResource\RelationManagers\ExtraServiceDetailsRelationManager;
use App\Filament\Resources\ExtraServiceResource\RelationManagers\CategoriesRelationManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExtraServiceResource extends Resource
{
    use HasFilamentPermissions;
    protected static ?string $model = ExtraService::class;

    protected static ?string $navigationGroup = 'مدیریت دسته‌بندی و فرمساز';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationLabel = 'قطعات و هزینه‌ها';
    protected static ?string $title = 'قطعات و هزینه‌ها';
    protected static ?string $modelLabel = 'قطعات و هزینه‌ها';
    protected static ?string $pluralModelLabel = 'قطعات و هزینه‌ها';

    protected static function getViewPermission(): string
    {
        return 'view-extra-services';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-extra-services';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-extra-services';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-extra-services';
    }




    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Forms\Components\Select::make('category_id')
                //     ->label('دسته‌بندی')
                //     ->options(
                //         function () {
                //             return Category::where('has_subcategory', 0)->pluck('title', 'id');
                //         }
                //     )
                //     ->searchable()
                //     ->required(),

                Forms\Components\TextInput::make('title')
                    ->label('عنوان')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('des')
                    ->label('توضیحات')
                    ->required(),

                // Forms\Components\TextInput::make('recommended_price')
                //     ->label('قیمت پیشنهادی')
                //     ->numeric()
                //     ->default(0)
                //     ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('شناسه')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('عنوان')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('categories.title')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('دسته‌بندی‌ها')
                    ->badge()
                    ->separator(','),

                Tables\Columns\TextColumn::make('created_at')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('تاریخ ایجاد')
                    ->jalaliDate()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            ExtraServiceDetailsRelationManager::class,
            CategoriesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExtraServices::route('/'),
            'create' => Pages\CreateExtraService::route('/create'),
            'edit' => Pages\EditExtraService::route('/{record}/edit'),
        ];
    }

}
