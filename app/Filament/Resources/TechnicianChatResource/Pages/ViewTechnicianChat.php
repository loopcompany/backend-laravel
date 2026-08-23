<?php

namespace App\Filament\Resources\TechnicianChatResource\Pages;

use App\Filament\Resources\TechnicianChatResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Tables\Table;
use Filament\Tables;
use App\Models\Chat;

class ViewTechnicianChat extends ViewRecord
{
    protected static string $resource = TechnicianChatResource::class;

    protected static ?string $title = 'مشاهده گفتگو';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('اطلاعات گفتگو')
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('نام کاربر'),
                        Components\TextEntry::make('user.code')
                            ->label('کد کاربر'),
                        Components\TextEntry::make('user.phone')
                            ->label('تلفن کاربر'),
                        Components\TextEntry::make('technician.name')
                            ->label('نام تکنسین'),
                        Components\TextEntry::make('technician.referral_code')
                            ->label('کد تکنسین'),
                        Components\TextEntry::make('messages_count')
                            ->label('تعداد پیام‌ها'),
                        Components\TextEntry::make('unread_count')
                            ->label('پیام‌های خوانده نشده'),
                        Components\TextEntry::make('last_message_at')
                            ->label('آخرین پیام')
                            ->formatStateUsing(fn($state) => $state 
                                ? \Morilog\Jalali\Jalalian::fromDateTime($state)->format('Y/m/d H:i')
                                : '—'
                            ),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TechnicianChatResource\Widgets\ChatMessagesWidget::class,
        ];
    }
}
