<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserChatResource\Pages;
use App\Filament\Resources\UserChatResource\RelationManagers;
use App\Models\Chat;
use App\Models\User;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UserChatResource extends Resource
{
    use HasFilamentPermissions;
    protected static function getViewPermission(): string
    {
        return 'view-user-chats';
    }

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'گفتگوهای کاربران';

    protected static ?string $pluralModelLabel = 'گفتگوهای کاربران';

    protected static ?string $modelLabel = 'گفتگوی کاربر';

    protected static ?string $navigationGroup = 'پشتیبانی و ارتباطات';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('نام کاربر')
                    ->disabled(),
                Forms\Components\TextInput::make('phone')
                    ->label('شماره تلفن')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                // فقط کاربرانی که حداقل یک تیکت دارند
                User::query()
                    ->whereHas('tickets')
                    ->withCount('tickets')
                    ->addSelect([
                        'last_message_at' => \App\Models\UserTicket::select('created_at')
                            ->whereColumn('user_id', 'users.id')
                            ->latest()
                            ->limit(1),
                        'unread_messages_count' => \App\Models\UserTicket::selectRaw('COUNT(*)')
                            ->whereColumn('user_id', 'users.id')
                            ->where('is_read', 0)
                            ->where('role', 'user'), // پیام‌های ارسالی توسط کاربر که خوانده نشده
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('نام کاربر')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('کد کاربری')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('شماره موبایل')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tickets_count')
                    ->label('تعداد تیکت‌ها')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unread_messages_count')
                    ->label('پیام‌های خوانده نشده')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_message_at')
                    ->label('تاریخ و ساعت ثبت')
                    ->formatStateUsing(function ($state) {
                        if (!$state)
                            return '—';
                        return \Morilog\Jalali\Jalalian::fromDateTime($state)->format('Y/m/d H:i');
                    })
                    ->sortable()
                    ->default('—'),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_unread')
                    ->label('دارای تیکت خوانده نشده')
                    ->query(fn(Builder $query) => $query->whereHas('tickets', function ($q) {
                        $q->where('is_read', 0)->where('role', 'user');
                    })),

                Tables\Filters\Filter::make('active_today')
                    ->label('فعال امروز')
                    ->query(fn(Builder $query) => $query->whereHas('tickets', function ($q) {
                        $q->whereDate('created_at', today());
                    })),
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
            RelationManagers\TicketsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserChats::route('/'),
            'view' => Pages\ViewUserChat::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
