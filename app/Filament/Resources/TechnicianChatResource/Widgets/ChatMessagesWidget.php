<?php

namespace App\Filament\Resources\TechnicianChatResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Chat;
use Illuminate\Database\Eloquent\Builder;

class ChatMessagesWidget extends BaseWidget
{
    public ?Chat $record = null;

    protected static ?string $heading = 'پیام‌های گفتگو';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Chat::query()
                    ->where('user_id', $this->record->user_id)
                    ->where('technician_id', $this->record->technician_id)
            )
            ->columns([
                Tables\Columns\TextColumn::make('msg')
                    ->label('پیام')
                    ->searchable()
                    ->wrap()
                    ->grow(),
                
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
            ->actions([
                Tables\Actions\Action::make('mark_as_read')
                    ->label('خوانده شد')
                    ->icon('heroicon-o-check')
                    ->visible(fn ($record) => !$record->is_read)
                    ->action(fn ($record) => $record->update(['is_read' => true])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
