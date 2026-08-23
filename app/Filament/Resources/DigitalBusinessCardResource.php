<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DigitalBusinessCardResource\Pages;
use App\Filament\Resources\DigitalBusinessCardResource\RelationManagers;
use App\Filament\Resources\DigitalBusinessCardResource\RelationManagers\DigitalBusinessCardBlocksRelationManager;
use App\Filament\Resources\DigitalBusinessCardResource\RelationManagers\FaqBlocksRelationManager;
use App\Filament\Resources\DigitalBusinessCardResource\RelationManagers\GalleryBlocksRelationManager;
use App\Filament\Resources\DigitalBusinessCardResource\RelationManagers\LinkBlocksRelationManager;
use App\Filament\Resources\DigitalBusinessCardResource\RelationManagers\MapBlocksRelationManager;
use App\Filament\Resources\DigitalBusinessCardResource\RelationManagers\SocialBlocksRelationManager;
use App\Filament\Resources\DigitalBusinessCardResource\RelationManagers\TextBlocksRelationManager;
use App\Models\DigitalBusinessCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DigitalBusinessCardResource extends Resource
{
    protected static ?string $model = DigitalBusinessCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'کارت ویزیت‌ها';

    protected static ?string $modelLabel = 'کارت ویزیت';

    protected static ?string $pluralModelLabel = 'کارت ویزیت‌ها';

    protected static ?string $navigationGroup = 'مدیریت کارت ویزیت';

    protected static ?int $navigationSort = 4;
    protected static function getViewPermission(): string
    {
        return 'view-digital-business-cards';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-digital-business-cards';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-digital-business-cards';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-digital-business-cards';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->label('عنوان کارت ویزیت')
                    ->maxLength(191),
                Forms\Components\FileUpload::make('image_background')
                    ->image()
                    ->label('تصویر پس زمینه')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image_background')->label('تصویر پس زمینه'),
                Tables\Columns\TextColumn::make('public_link')
                    ->label('لینک عمومی')
                    ->state(fn($record) => route('public.cards.show', ['slug' => $record->slug]))
                    ->copyable()
                    ->copyMessage('لینک با موفقیت کپی شد')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('created_at')
                    ->jalaliDateTime()
                    ->label('تاریخ ایجاد')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->jalaliDateTime()
                    ->label('تاریخ بروزرسانی')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->url(fn($record): string => route('admin.cards.editor', ['card' => $record->id])),
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
            DigitalBusinessCardBlocksRelationManager::class,
            TextBlocksRelationManager::class,
            LinkBlocksRelationManager::class,
            SocialBlocksRelationManager::class,
            MapBlocksRelationManager::class,
            FaqBlocksRelationManager::class,
            GalleryBlocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDigitalBusinessCards::route('/'),
            'create' => Pages\CreateDigitalBusinessCard::route('/create'),
            'edit' => Pages\EditDigitalBusinessCard::route('/{record}/edit'),
        ];
    }
}
