<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MinPriceResource\Pages;
use App\Filament\Resources\MinPriceResource\RelationManagers;
use App\Models\MinPrice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MinPriceResource extends Resource
{
    protected static ?string $model = MinPrice::class;
     protected static ?string $navigationLabel = 'حداقل مبلغ اتحادیه';

    protected static ?string $modelLabel = 'حداقل مبلغ اتحادیه';

    protected static ?string $pluralModelLabel = 'حداقل مبلغ اتحادیه';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static function getViewPermission(): string
    {
        return 'view-min-price';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-min-price';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->label('حداقل مبلغ سفارش برای محاسبه هزینه ایاب ذهاب')
                    ->numeric()
                    ->suffix('تومان')
                    ->default(1200000),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('price')
                    ->label('مبلغ فعلی')
                    ->numeric()
                    ->suffix('تومان')
                    ->searchable(), 
                Tables\Columns\TextColumn::make('updated_at')
                    ->jalaliDateTime()
                    ->sortable() 
                    ->label('تاریخ آخرین بروزرسانی'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListMinPrices::route('/'),
            'create' => Pages\CreateMinPrice::route('/create'),
            'edit' => Pages\EditMinPrice::route('/{record}/edit'),
        ];
    }
}
