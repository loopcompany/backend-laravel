<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Partner;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = Partner::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'همکاران (سوابق همکاری)';
    protected static ?string $modelLabel = 'همکار';
    protected static ?string $pluralModelLabel = 'همکاران';

    protected static function getViewPermission(): string
    {
        return 'view-partners';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-partners';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-partners';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-partners';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('نام شرکت یا سازمان')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('website_url')
                    ->label('آدرس سایت')
                    ->helperText('در سایت نمایش داده نمی‌شود؛ با کلیک روی لوگو باز می‌شود.')
                    ->required()
                    ->url()
                    ->placeholder('https://example.com')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('logo')
                    ->label('لوگو')
                    ->helperText('ترجیحاً با زمینه‌ی سفید یا شفاف، حداکثر ۲ مگابایت.')
                    ->required()
                    ->image()
                    ->disk('public')
                    ->directory('partners')
                    ->maxSize(2048),
                Forms\Components\TextInput::make('sort_order')
                    ->label('ترتیب نمایش')
                    ->helperText('عدد کوچک‌تر زودتر نمایش داده می‌شود.')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('نمایش در سایت')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('logo')->disk('public')->label('لوگو'),
                Tables\Columns\TextColumn::make('name')->searchable()->label('نام'),
                Tables\Columns\TextColumn::make('website_url')
                    ->label('سایت')
                    ->url(fn (Partner $record) => $record->website_url, true),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('نمایش در سایت')
                    ->disabled(fn () => !static::canEdit(null)),
                Tables\Columns\TextColumn::make('sort_order')->sortable()->label('ترتیب'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
