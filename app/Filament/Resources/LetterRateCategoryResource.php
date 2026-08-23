<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LetterRateCategoryResource\Pages;
use App\Filament\Resources\LetterRateCategoryResource\RelationManagers;
use App\Models\LetterRateCategory;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LetterRateCategoryResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = LetterRateCategory::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?int $navigationSort = 11;
    protected static ?string $navigationLabel = 'دسته‌بندی نرخ نامه';
    protected static ?string $title = 'دسته‌بندی نرخ نامه';
    protected static ?string $modelLabel = 'دسته‌بندی';
    protected static ?string $pluralModelLabel = 'دسته‌بندی‌ها';

    protected static function getViewPermission(): string
    {
        return 'view-letter-rate-categories';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-letter-rate-categories';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-letter-rate-categories';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-letter-rate-categories';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات دسته‌بندی')->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->label('عنوان دسته‌بندی')
                        ->placeholder('مثال: خدمات پایه، خدمات ویژه، ...')
                        ->columnSpanFull(),
                ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('letter_rates_count')
                    ->counts('letterRates')
                    ->label('تعداد نرخ‌ها')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین بروزرسانی')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLetterRateCategories::route('/'),
            'create' => Pages\CreateLetterRateCategory::route('/create'),
            'edit' => Pages\EditLetterRateCategory::route('/{record}/edit'),
        ];
    }
}
