<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AboutResource\Pages;
use App\Filament\Resources\AboutResource\RelationManagers;
use App\Models\About;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AboutResource extends Resource
{
    use HasFilamentPermissions;
    
    protected static ?string $model = About::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'درباره ما';
    protected static ?string $title = 'مدیریت درباره ما';
    protected static ?string $modelLabel = 'درباره ما';
    protected static ?string $pluralModelLabel  = 'درباره ما';

    protected static function getViewPermission(): string
    {
        return 'view-abouts';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-about';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-about';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-about';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(100)
                    ->label('عنوان'),
                Forms\Components\FileUpload::make('image_path')
                    ->image()
                    ->required()
                    ->directory('about')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048) // حداکثر حجم 2MB
                    ->label('تصویر'),
                Forms\Components\RichEditor::make('des')
                    ->required()
                    ->columnSpanFull()
                    ->label('توضیحات'),
                Forms\Components\Textarea::make('meta')
                    ->columnSpanFull()
                    ->label('متا تگ ها'),
                // Forms\Components\ToggleButtons::make('lang')
                //     ->options([
                //         'fa' => 'فارسی',
                //         'en' => 'انگلیسی'
                //     ])->inline()
                //     ->default('fa')
                //     ->label('زبان'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->label('عنوان'),
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('تصویر'),
                // Tables\Columns\TextColumn::make('lang')
                //     ->searchable()
                //     ->label('زبان'),

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
            'index' => Pages\ListAbouts::route('/'),
            'create' => Pages\CreateAbout::route('/create'),
            'edit' => Pages\EditAbout::route('/{record}/edit'),
        ];
    }


}
