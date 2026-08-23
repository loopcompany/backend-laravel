<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';
    
    protected static ?string $title = 'آدرس‌های کاربر';
    
    protected static ?string $modelLabel = 'آدرس';
    
    protected static ?string $pluralModelLabel = 'آدرس‌ها';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات آدرس')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان آدرس')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('fname')
                            ->label('نام')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('lname')
                            ->label('نام خانوادگی')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('telephone')
                            ->label('تلفن ثابت')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mobile')
                            ->label('موبایل')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->label('شهر')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('region')
                            ->label('منطقه')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('latitude')
                            ->label('عرض جغرافیایی')
                            ->numeric()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('longitude')
                            ->label('طول جغرافیایی')
                            ->numeric()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('address')
                            ->label('آدرس کامل')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fname')
                    ->label('نام')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lname')
                    ->label('نام خانوادگی')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->label('موبایل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('شهر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('region')
                    ->label('منطقه')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->jalaliDate()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('city')
                    ->label('شهر')
                    ->options(function () {
                        return \App\Models\UserAddress::whereNotNull('city')
                            ->distinct()
                            ->pluck('city', 'city')
                            ->toArray();
                    }),
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