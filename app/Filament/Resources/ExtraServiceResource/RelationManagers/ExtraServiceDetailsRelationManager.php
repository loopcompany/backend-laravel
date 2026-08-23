<?php

namespace App\Filament\Resources\ExtraServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExtraServiceDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'extra_service_details';
    protected static ?string $navigationLabel = 'لیست قیمت پیشنهادی';
    protected static ?string $title = 'لیست قیمت پیشنهادی';
    protected static ?string $modelLabel = 'لیست قیمت پیشنهادی';
    protected static ?string $pluralModelLabel = 'لیست قیمت پیشنهادی';



    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('عنوان')
                     ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('brand')
                    ->label('برند')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('model')
                    ->label('مدل')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('warranty')
                    ->label('گارانتی')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('test_duration')
                    ->label('مهلت تست')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('barcode')
                    ->label('بارکد')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('price')
                    ->label('قیمت (تومان)')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('عنوان'),
                Tables\Columns\TextColumn::make('price')->label('قیمت')->suffix('تومان'),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ ثبت')->jalaliDate(),
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
