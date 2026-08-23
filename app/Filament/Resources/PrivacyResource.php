<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrivacyResource\Pages;
use App\Filament\Resources\PrivacyResource\RelationManagers;
use App\Models\Access;
use App\Models\AdminAccess;
use App\Models\Privacy;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PrivacyResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = Privacy::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationLabel = 'حریم خصوصی';
    protected static ?string $title = 'حریم خصوصی';
    protected static ?string $modelLabel = 'حریم خصوصی';
    protected static ?string $pluralModelLabel  = 'حریم خصوصی';

    protected static function getViewPermission(): string
    {
        return 'view-privacy';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-privacy';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-privacy';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-privacy';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(180)
                    ->label('عنوان')
                    ->columnSpan('full'),
                Forms\Components\RichEditor::make('description')
                    ->required()
                    ->columnSpanFull()
                    ->label('توضیحات'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->label('عنوان'),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->words(10)
                    ->html()
                    ->label('توضیحات'),
                // Tables\Columns\SelectColumn::make('lang')
                //     ->options([
                //         'fa' => 'فارسی',
                //         'en' => 'انگلیسی'
                //     ])
                //     ->selectablePlaceholder(false)
                //     ->searchable()
                //     ->label('زبان'),
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
            'index' => Pages\ListPrivacies::route('/'),
            'create' => Pages\CreatePrivacy::route('/create'),
            'edit' => Pages\EditPrivacy::route('/{record}/edit'),
        ];
    }


}

