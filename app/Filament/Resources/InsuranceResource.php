<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InsuranceResource\Pages;
use App\Filament\Resources\InsuranceResource\RelationManagers;
use App\Models\Insurance;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InsuranceResource extends Resource
{
    use HasFilamentPermissions;
    protected static ?string $model = Insurance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'بیمه تأمین اجتماعی';

    protected static ?string $modelLabel = 'بیمه تأمین اجتماعی';

    protected static ?string $pluralModelLabel = 'بیمه تأمین اجتماعی';

    protected static function getCreatePermission(): string
    {
        return 'create-insurance';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-insurance';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-insurance';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('technician_name')
                    ->required()
                    ->label('نام تکنسین / مدیر')
                    ->maxLength(191),
                Forms\Components\TextInput::make('technician_code')
                    ->label('کد تکنسین / مدیر')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('technician_melicode')
                    ->label('شماره ملی تکنسین / مدیر')
                    ->required()
                    ->maxLength(191),
                Forms\Components\DatePicker::make('history_start_at')
                    ->jalali()
                    ->label('سابقه بیمه از تاریخ')
                    ->required(),
                Forms\Components\DatePicker::make('history_end_at')
                    ->jalali()
                    ->label('سابقه بیمه تا تاریخ')
                    ->required(),
                Forms\Components\TextInput::make('duration')
                    ->required()
                    ->label('مدت به سال')
                    ->numeric(),
                Forms\Components\DatePicker::make('loop_start_at')
                    ->jalali()
                    ->label('شروع بیمه در لوپ از تاریخ')
                    ->required(),
                Forms\Components\DatePicker::make('loop_end_at')
                    ->jalali()
                    ->label('پایان بیمه در لوپ تا تاریخ')
                    ->required(),
                Forms\Components\TextInput::make('loop_duration')
                    ->required()
                    ->label('مدت بیمه بیکاری / حوادث در لوپ')
                    ->maxLength(191),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('technician_name')
                    ->label('نام تکنسین / مدیر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('technician_code')
                    ->label('کد تکنسین / مدیر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('technician_melicode')
                    ->label('شماره ملی تکنسین / مدیر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('history_start_at')
                    ->label('سابقه بیمه از تاریخ')
                    ->jalaliDate()
                    ->sortable(),
                Tables\Columns\TextColumn::make('history_end_at')
                    ->label('سابقه بیمه تا تاریخ')
                    ->jalaliDate()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('مدت به سال')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loop_start_at')
                    ->label('شروع بیمه در لوپ از تاریخ')
                    ->jalaliDate()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loop_end_at')
                    ->label('پایان بیمه در لوپ تا تاریخ')
                    ->jalaliDate()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loop_duration')
                    ->label('مدت بیمه بیکاری / حوادث در لوپ')
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
            'index' => Pages\ListInsurances::route('/'),
            'create' => Pages\CreateInsurance::route('/create'),
            'edit' => Pages\EditInsurance::route('/{record}/edit'),
        ];
    }
}
