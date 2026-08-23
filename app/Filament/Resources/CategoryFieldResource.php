<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryFieldResource\Pages;
use App\Filament\Resources\CategoryFieldResource\RelationManagers;
use App\Filament\Resources\CategoryFieldResource\RelationManagers\CategoryFieldConditionalsRelationManager;
use App\Models\CategoryField;
use App\Models\Field;
use App\Models\FieldDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class CategoryFieldResource extends Resource
{
    protected static ?string $model = CategoryField::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Placeholder::make('field_display')
                    ->label('فیلد انتخاب‌شده')
                    ->content(function ($get) {
                        $fieldId = $get('field_id');
                        $field = \App\Models\Field::find($fieldId);
                        return $field ? $field->title.' - راهنما:'.$field->guide : 'فیلدی انتخاب نشده است';
                    }),

                    
                

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
            CategoryFieldConditionalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            // 'index' => Pages\ListCategoryFields::route('/'),
            // 'create' => Pages\CreateCategoryField::route('/create'),
            'edit' => Pages\EditCategoryField::route('/{record}/edit'),
        ];
    }


    protected function getTitle(): string
    {
        return 'ویرایش مرحله فرم';
    }

    public static function canViewAny(): bool
    {
        return auth('admin')->user()?->can('view-category-fields') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return auth('admin')->user()?->can('edit-category-fields') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth('admin')->user()?->can('delete-category-fields') ?? false;
    }
}
