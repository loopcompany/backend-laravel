<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ToolResource\Pages;
use App\Filament\Resources\ToolResource\RelationManagers;
use App\Models\Tool;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ToolResource extends Resource
{
    use HasFilamentPermissions;
    protected static ?string $model = Tool::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'اموال و ابزار محوله';

    protected static ?string $modelLabel = 'اموال و ابزار محوله';

    protected static ?string $pluralModelLabel = 'اموال و ابزار محوله';
    protected static function getCreatePermission(): string
    {
        return 'create-tools';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-tools';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-tools';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('قطعه / ابزار')
                    ->maxLength(191),
                Forms\Components\TextInput::make('num')
                    ->required()
                    ->numeric()
                    ->label('تعداد')
                ,
                Forms\Components\DatePicker::make('delivered_at')
                    ->jalali()
                    ->label('تاریخ تحویل محوله')
                    ->required(),
                Forms\Components\TextInput::make('technician_name')
                    ->required()
                    ->label('نام تکنسین / مدیر')
                    ->maxLength(191),
                Forms\Components\TextInput::make('technician_code')
                    ->required()
                    ->label('کد تکنسین / مدیر')
                    ->maxLength(191),
                Forms\Components\TextInput::make('technician_melicode')
                    ->required()
                    ->label('شماره ملی تکنسین / مدیر')
                    ->maxLength(191),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('قطعه / ابزار')
                    ->searchable(),
                Tables\Columns\TextColumn::make('num')
                    ->label('تعداد')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('تاریخ تحویل محوله')
                    ->jalaliDate()
                    ->sortable(),
                Tables\Columns\TextColumn::make('technician_name')
                    ->label('نام تکنسین / مدیر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('technician_code')
                    ->label('کد تکنسین / مدیر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('technician_melicode')
                    ->label('شماره ملی تکنسین / مدیر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->jalaliDateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->jalaliDateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTools::route('/'),
            'create' => Pages\CreateTool::route('/create'),
            'edit' => Pages\EditTool::route('/{record}/edit'),
        ];
    }
}
