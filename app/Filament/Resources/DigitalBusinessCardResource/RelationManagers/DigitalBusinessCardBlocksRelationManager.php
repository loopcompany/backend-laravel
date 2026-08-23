<?php

namespace App\Filament\Resources\DigitalBusinessCardResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DigitalBusinessCardBlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'digital_business_card_blocks';
    protected static ?string $title = 'بلاک های کارت ویزیت';
    protected static ?string $modelLabel = 'بلاک های کارت ویزیت';
    protected static ?string $pluralModelLabel  = 'بلاک های کارت ویزیت';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->label('عنوان')
                    ->maxLength(191),
                Forms\Components\ColorPicker::make('color')->required()->label('رنگ متن عنوان')
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('عنوان'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
