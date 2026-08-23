<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarrantyCategoryResource\Pages;
use App\Filament\Resources\WarrantyCategoryResource\RelationManagers;
use App\Models\WarrantyCategory;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WarrantyCategoryResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = WarrantyCategory::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 11;
    protected static ?string $navigationLabel = 'دسته بندی گارانتی';
    protected static ?string $title = 'دسته بندی گارانتی';
    protected static ?string $modelLabel = 'دسته بندی گارانتی';
    protected static ?string $pluralModelLabel = 'دسته بندی گارانتی';
    protected static function getViewPermission(): string
    {
        return 'view-warranty-categories';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-warranty-categories';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-warranty-categories';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-warranty-categories';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->label('عنوان')
                    ->maxLength(191),
                Forms\Components\Textarea::make('description')
                    ->label('توضیحات')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->jalaliDateTime()
                    ->sortable()
                    ->label('تاریخ ایجاد')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->jalaliDateTime()
                    ->sortable()
                    ->label('تاریخ به‌روزرسانی')
                    ->toggleable(isToggledHiddenByDefault: true),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarrantyCategories::route('/'),
            'create' => Pages\CreateWarrantyCategory::route('/create'),
            'edit' => Pages\EditWarrantyCategory::route('/{record}/edit'),
        ];
    }
}
