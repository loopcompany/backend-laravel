<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialResource\Pages;
use App\Filament\Resources\SocialResource\RelationManagers;
use App\Models\Access;
use App\Models\AdminAccess;
use App\Models\Social;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SocialResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = Social::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'سوشال مدیا';
    protected static ?string $title = 'محیط های مجازی';
    protected static ?string $modelLabel = 'سوشال';
    protected static ?string $pluralModelLabel  = 'محیط های مجازی';

    protected static function getViewPermission(): string
    {
        return 'view-socials';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-socials';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-socials';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-socials';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->label('عنوان')
                    ->maxLength(255),
                Forms\Components\TextInput::make('link')
                    ->required()
                    ->label('لینک')
                    ->maxLength(255),
                Forms\Components\TextInput::make('value')
                    ->required()
                    ->label('مقدار')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('icon')
                    ->required()
                    ->label('آیکون')
                    ->directory('socials')
                    ->acceptedFileTypes(['image/*']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('id'),
                Tables\Columns\TextColumn::make('title')->searchable()->label('عنوان'),
                Tables\Columns\TextColumn::make('link')->searchable()->label('لینک'),
                Tables\Columns\TextColumn::make('value')->searchable()->label('مقدار'),
                // Tables\Columns\ToggleColumn::make('float')->searchable()->label('Ù†Ù…Ø§ÛŒØ´ Ø¯Ø± ÙÙ„ÙˆØª Ø¨Ø§ØªÙ†'),
                Tables\Columns\ImageColumn::make('icon')->searchable()->label('آیکون'),
                Tables\Columns\TextColumn::make('created_at')->jalaliDate()->sortable()->label('تاریخ ایجاد'),
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
            'index' => Pages\ListSocials::route('/'),
            'create' => Pages\CreateSocial::route('/create'),
            'edit' => Pages\EditSocial::route('/{record}/edit'),
        ];
    }
}

