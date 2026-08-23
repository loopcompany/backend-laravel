<?php

namespace App\Filament\Resources\UserChatResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ChatsRelationManager extends RelationManager
{
    protected static string $relationship = 'chats';

    protected static ?string $title = 'پیام‌های کاربر';

    protected static ?string $recordTitleAttribute = 'msg';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('technician_id')
                    ->label('تکنسین')
                    ->relationship('technician', 'name')
                    ->required()
                    ->searchable(),
                
                Forms\Components\Textarea::make('msg')
                    ->label('پیام')
                    ->required()
                    ->rows(3),
                
                Forms\Components\Toggle::make('is_user')
                    ->label('ارسال شده توسط کاربر')
                    ->default(true),
                
                Forms\Components\Toggle::make('is_read')
                    ->label('خوانده شده')
                    ->default(false),
                
                Forms\Components\Toggle::make('is_closed')
                    ->label('بسته شده')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('technician.name')
                    ->label('تکنسین')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('msg')
                    ->label('پیام')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),
                
                Tables\Columns\IconColumn::make('is_user')
                    ->label('ارسال‌کننده')
                    ->boolean()
                    ->trueIcon('heroicon-o-user')
                    ->falseIcon('heroicon-o-wrench-screwdriver')
                    ->trueColor('info')
                    ->falseColor('success')
                    ->tooltip(fn ($state) => $state ? 'کاربر' : 'تکنسین'),
                
                Tables\Columns\IconColumn::make('is_read')
                    ->label('وضعیت')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($state) => $state ? 'خوانده شده' : 'خوانده نشده'),
                
                Tables\Columns\IconColumn::make('is_closed')
                    ->label('بسته')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ارسال')
                    ->formatStateUsing(function ($state) {
                        return \Morilog\Jalali\Jalalian::fromDateTime($state)->format('Y/m/d H:i');
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_user')
                    ->label('ارسال‌کننده')
                    ->options([
                        1 => 'کاربر',
                        0 => 'تکنسین',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('خوانده شده')
                    ->placeholder('همه')
                    ->trueLabel('خوانده شده')
                    ->falseLabel('خوانده نشده'),
                
                Tables\Filters\TernaryFilter::make('is_closed')
                    ->label('بسته شده')
                    ->placeholder('همه')
                    ->trueLabel('بسته شده')
                    ->falseLabel('باز'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('پیام جدید'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('mark_as_read')
                    ->label('علامت به عنوان خوانده شده')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_read)
                    ->action(fn ($record) => $record->update(['is_read' => true]))
                    ->requiresConfirmation(false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('mark_as_read')
                        ->label('علامت به عنوان خوانده شده')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_read' => true])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
