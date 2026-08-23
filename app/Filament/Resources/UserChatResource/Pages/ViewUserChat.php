<?php

namespace App\Filament\Resources\UserChatResource\Pages;

use App\Filament\Resources\UserChatResource;
use App\Models\UserTicket;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;

class ViewUserChat extends ViewRecord
{
    protected static string $resource = UserChatResource::class;

    // Enable relation managers to be displayed as tabs
    protected static bool $hasCombinedRelationManagerTabsWithContent = true;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_message')
                ->label('ارسال پیام جدید')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->form([
                    Forms\Components\Textarea::make('message')
                        ->label('پیام')
                        ->required()
                        ->rows(5)
                        ->maxLength(5000)
                        ->placeholder('پیام خود را اینجا بنویسید...')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    UserTicket::create([
                        'user_id' => $this->record->id,
                        'message' => $data['message'],
                        'role' => 'admin',
                        'is_read' => 0, // User hasn't read it yet
                    ]);

                    Notification::make()
                        ->title('پیام با موفقیت ارسال شد')
                        ->success()
                        ->send();
                        
                    // Refresh the page to show new message
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('اطلاعات کاربر')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('نام')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('شماره تلفن'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('ایمیل')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('tickets_count')
                            ->label('تعداد تیکت‌ها')
                            ->state(fn ($record) => $record->tickets()->count())
                            ->badge()
                            ->color('info'),
                    ])
                    ->columns(2),
            ]);
    }
}
