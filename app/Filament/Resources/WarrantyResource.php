<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarrantyResource\RelationManagers;
use App\Filament\Resources\WarrantyResource\Pages;
use App\Models\Access;
use App\Models\AdminAccess;
use App\Models\Warranty;
use App\Models\WarrantyCategory;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WarrantyResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = Warranty::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 11;
    protected static ?string $navigationLabel = 'گارانتی و ضمانت';
    protected static ?string $title = 'گارانتی و ضمانت';
    protected static ?string $modelLabel = 'گارانتی و ضمانت';
    protected static ?string $pluralModelLabel  = 'گارانتی و ضمانت';

    protected static function getViewPermission(): string
    {
        return 'view-warranties';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-warranties';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-warranties';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-warranties';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(180)
                    ->label('عنوان'),
                Forms\Components\Select::make('warranty_category_id')
                        ->label('دسته‌بندی')
                        ->options(WarrantyCategory::pluck('title', 'id'))
                        ->searchable()
                        ->preload()
                        ->placeholder('دسته‌بندی را انتخاب کنید')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->label('عنوان'),
                Tables\Columns\TextColumn::make('category.title')
                    ->searchable()  
                    ->label('دسته بندی'),
                Tables\Columns\TextColumn::make('created_at')
                    ->jalaliDate()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('تاریخ ثبت'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->sortable()
                    ->jalaliDate()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('تاریخ ویرایش'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListWarranties::route('/'),
            'create' => Pages\CreateWarranty::route('/create'),
            'edit' => Pages\EditWarranty::route('/{record}/edit'),
        ];
    }


}
