<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkingTimeResource\Pages;
use App\Filament\Resources\WorkingTimeResource\RelationManagers;
use App\Models\WorkingTime;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkingTimeResource extends Resource
{
    protected static ?string $model = WorkingTime::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'ساعت کاری لوپ';

    protected static ?string $modelLabel = 'ساعت کاری لوپ';

    protected static ?string $pluralModelLabel = 'ساعت کاری لوپ';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TimePicker::make('start_at')
                    ->label('ساعت شروع کار')
                    ->required(),
                Forms\Components\TimePicker::make('end_at')
                    ->label('ساعت پایان کار')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('start_at')->label('ساعت شروع کار'),
                Tables\Columns\TextColumn::make('end_at')->label('ساعت پایان کار'), 
                Tables\Columns\TextColumn::make('updated_at')
                    ->jalaliDateTime()
                    ->sortable()
                    ->label('تاریخ آخرین بروزرسانی')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListWorkingTimes::route('/'),
            'create' => Pages\CreateWorkingTime::route('/create'),
            'edit' => Pages\EditWorkingTime::route('/{record}/edit'),
        ];
    }
}
