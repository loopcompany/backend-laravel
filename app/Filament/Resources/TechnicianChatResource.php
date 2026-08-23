<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TechnicianChatResource\Pages;
use App\Filament\Resources\TechnicianChatResource\RelationManagers;
use App\Models\Chat;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TechnicianChatResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = Chat::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $navigationLabel = 'گفتگوهای تکنسین‌ها';
    protected static ?string $pluralModelLabel = 'گفتگوهای تکنسین‌ها';
    protected static ?string $modelLabel = 'گفتگوی تکنسین';
    protected static ?string $navigationGroup = 'پشتیبانی و ارتباطات';
    protected static ?int $navigationSort = 2;

    protected static function getViewPermission(): string
    {
        return 'view-technician-chats';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('technician.name')
                    ->label('نام تکنسین')
                    ->disabled(),
                Forms\Components\TextInput::make('user.name')
                    ->label('نام کاربر')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Chat::query()
                    ->select([
                        'user_id',
                        'technician_id',
                        DB::raw('MAX(id) as id'),
                        DB::raw('COUNT(*) as messages_count'),
                        DB::raw('MAX(created_at) as last_message_at'),
                        DB::raw('SUM(CASE WHEN is_read = 0 AND is_user = 1 THEN 1 ELSE 0 END) as unread_count')
                    ])
                    ->groupBy('user_id', 'technician_id')
                    ->with(['user', 'technician'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('technician.name')
                    ->label('نام تکنسین')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('technician.phone')
                    ->label('شماره تکنسین')
                    ->searchable(),

                Tables\Columns\TextColumn::make('technician.referral_code')
                    ->label('کد پرسنلی')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('نام کاربر')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.code')
                    ->label('کد کاربری')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.phone')
                    ->label('شماره کاربر')
                    ->searchable(),

                Tables\Columns\TextColumn::make('messages_count')
                    ->label('تعداد پیام‌ها')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unread_count')
                    ->label('پیام‌های خوانده نشده')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_message_at')
                    ->label('آخرین پیام')
                    ->formatStateUsing(function ($state) {
                        if (!$state)
                            return '—';
                        return \Morilog\Jalali\Jalalian::fromDateTime($state)->format('Y/m/d H:i');
                    })
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_unread')
                    ->label('دارای پیام خوانده نشده')
                    ->query(fn(Builder $query) => $query->having('unread_count', '>', 0)),

                Tables\Filters\Filter::make('active_today')
                    ->label('فعال امروز')
                    ->query(fn(Builder $query) => $query->havingRaw('DATE(MAX(created_at)) = ?', [today()->toDateString()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('مشاهده پیام‌ها'),
            ])
            ->defaultSort('last_message_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [ 
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTechnicianChats::route('/'),
            'view' => Pages\ViewTechnicianChat::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
