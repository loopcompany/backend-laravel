<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope; 

class UserChatsRelationManager extends RelationManager
{
    protected static string $relationship = 'userChats';
    protected static ?string $title = 'پیام‌های کاربر';
    protected static ?string $modelLabel = 'پیام‌های کاربر';
    protected static ?string $pluralModelLabel = 'پیام‌های کاربر';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('msg')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('msg')
            
            ->columns([
                Tables\Columns\TextColumn::make('msg')
                ->label('متن پیام')  
                ->wrap()
                ,
                Tables\Columns\TextColumn::make('technician.name')
                ->label('تکنسین')  
                ,
                Tables\Columns\TextColumn::make('technician.referral_code')
                ->label('کدتکنسین')  
                ,
                Tables\Columns\TextColumn::make('user.name')
                ->label('نام کاربر')  
                ,
                Tables\Columns\TextColumn::make('user.code')
                ->label('کد کاربر')  
                ,
                Tables\Columns\IconColumn::make('is_user')
                    ->label('فرستنده')
                    ->boolean()
                    ->trueIcon('heroicon-o-user')
                    ->falseIcon('heroicon-o-wrench-screwdriver')
                    ->trueColor('info')
                    ->falseColor('success')
                    ->tooltip(fn ($state) => $state ? 'کاربر' : 'تکنسین'),
                
                Tables\Columns\IconColumn::make('is_read')
                    ->label('وضعیت')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('زمان')
                    ->formatStateUsing(fn($state) => \Morilog\Jalali\Jalalian::fromDateTime($state)->format('Y/m/d H:i'))
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
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
