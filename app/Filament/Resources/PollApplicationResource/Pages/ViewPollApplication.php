<?php

namespace App\Filament\Resources\PollApplicationResource\Pages;

use App\Filament\Resources\PollApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPollApplication extends ViewRecord
{
    protected static string $resource = PollApplicationResource::class;

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
                Infolists\Components\Section::make('اطلاعات کاربر')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('نام کاربر'),
                        Infolists\Components\TextEntry::make('user.phone')
                            ->label('شماره تلفن'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('ایمیل'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('تاریخ ثبت نظرسنجی')
                            ->formatStateUsing(fn ($state) => safe_jalali_datetime($state)),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('امتیازات')
                    ->schema([
                        Infolists\Components\TextEntry::make('app_rate')
                            ->label('امتیاز اپلیکیشن')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'خوب' => 'success',
                                'متوسط' => 'warning',
                                'ضعیف' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('tech_rate')
                            ->label('امتیاز تکنسین')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'خوب' => 'success',
                                'متوسط' => 'warning',
                                'ضعیف' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('support_rate')
                            ->label('امتیاز پشتیبانی')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'خوب' => 'success',
                                'متوسط' => 'warning',
                                'ضعیف' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('نظرات و توضیحات')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('توضیحات کاربر')
                            ->placeholder('بدون توضیحات')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (): bool => !empty($this->record->description)),

                Infolists\Components\Section::make('آمار کلی امتیاز')
                    ->schema([
                        Infolists\Components\TextEntry::make('overall_score')
                            ->label('میانگین امتیازات')
                            ->state(function () {
                                $scores = [
                                    'خوب' => 3,
                                    'متوسط' => 2,
                                    'ضعیف' => 1,
                                ];
                                
                                $appScore = $scores[$this->record->app_rate] ?? 0;
                                $techScore = $scores[$this->record->tech_rate] ?? 0;
                                $supportScore = $scores[$this->record->support_rate] ?? 0;
                                
                                $average = ($appScore + $techScore + $supportScore) / 3;
                                
                                if ($average >= 2.5) return 'خوب';
                                if ($average >= 1.5) return 'متوسط';
                                return 'ضعیف';
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'خوب' => 'success',
                                'متوسط' => 'warning',
                                'ضعیف' => 'danger',
                                default => 'gray',
                            }),
                    ]),
            ]);
    }
}