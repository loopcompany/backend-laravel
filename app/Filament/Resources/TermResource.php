<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TermResource\RelationManagers;
use App\Filament\Resources\TermResource\Pages;
use App\Models\Access;
use App\Models\AdminAccess;
use App\Models\Term;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TermResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = Term::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationLabel = 'قوانین و مقررات';
    protected static ?string $title = 'قوانین و مقررات';
    protected static ?string $modelLabel = 'قوانین و مقررات';
    protected static ?string $pluralModelLabel  = 'قوانین و مقررات';

    protected static function getViewPermission(): string
    {
        return 'view-terms';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-terms';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-terms';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-terms';
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
                // Forms\Components\ToggleButtons::make('lang')
                //     ->options([
                //         'fa' => 'ÙØ§Ø±Ø³ÛŒ',
                //         'en' => 'Ø§Ù†Ú¯Ù„ÛŒØ³ÛŒ'
                //     ])
                //     ->inline()
                //     ->default('fa')
                //     ->label('Ø²Ø¨Ø§Ù†'),
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
                //         'fa' => 'ÙØ§Ø±Ø³ÛŒ',
                //         'en' => 'Ø§Ù†Ú¯Ù„ÛŒØ³ÛŒ'
                //     ])
                //     ->selectablePlaceholder(false)
                //     ->searchable()
                //     ->label('Ø²Ø¨Ø§Ù†'),
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
            'index' => Pages\ListTerms::route('/'),
            'create' => Pages\CreateTerm::route('/create'),
            'edit' => Pages\EditTerm::route('/{record}/edit'),
        ];
    }


}

