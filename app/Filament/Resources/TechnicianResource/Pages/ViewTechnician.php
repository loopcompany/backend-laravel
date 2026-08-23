<?php

namespace App\Filament\Resources\TechnicianResource\Pages;

use App\Filament\Resources\TechnicianResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewTechnician extends ViewRecord
{
    protected static string $resource = TechnicianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('اطلاعات شخصی')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('نام'),
                        Infolists\Components\TextEntry::make('referral_code')
                            ->label('کد پرسنلی'),
                        Infolists\Components\TextEntry::make('melicode')
                            ->label('کد ملی'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('تلفن'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('ایمیل'),
                        Infolists\Components\TextEntry::make('birth_date')
                            ->label('تاریخ تولد')
                            ->formatStateUsing(fn ($state) => $state),
                        Infolists\Components\TextEntry::make('home_address')
                            ->label('آدرس')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('وضعیت تایید')
                    ->schema([
                        Infolists\Components\TextEntry::make('approval_status_label')
                            ->label('وضعیت تایید')
                            ->badge()
                            ->color(fn (string $state): string => match ($this->record->approval_status) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('approved_at')
                            ->label('زمان تایید')
                            ->formatStateUsing(fn ($state) => safe_jalali_datetime($state)),
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label('علت رد')
                            ->visible(fn (): bool => $this->record->isRejected())
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('تخصص‌ها')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('expertises')
                            ->label('تخصص‌ها')
                            ->schema([
                                Infolists\Components\TextEntry::make('title')
                                    ->label('عنوان'),
                            ])
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('مهارت‌ها')
                    ->schema([
                        Infolists\Components\TextEntry::make('software_skill')
                            ->label('مهارت نرم‌افزاری'),
                        Infolists\Components\TextEntry::make('hardware_skill')
                            ->label('مهارت سخت‌افزاری'),
                        Infolists\Components\TextEntry::make('software_weakness')
                            ->label('نقاط ضعف نرم‌افزاری'),
                        Infolists\Components\TextEntry::make('hardware_weakness')
                            ->label('نقاط ضعف سخت‌افزاری'),
                    ])
                    ->columns(2),
            ]);
    }
}