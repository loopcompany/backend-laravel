<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\FieldServiceVisit;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FieldServiceVisitsRelationManager extends RelationManager
{
    protected static string $relationship = 'fieldServiceVisits';
    protected static ?string $title = 'گزارش‌های روزانهٔ تکنسین میدانی';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('بازدید'),
                Tables\Columns\TextColumn::make('technician.name')->label('تکنسین'),
                Tables\Columns\TextColumn::make('started_at')->label('ورود')->jalaliDateTime(),
                Tables\Columns\TextColumn::make('finished_at')->label('خروج')->jalaliDateTime(),
                Tables\Columns\TextColumn::make('status')->label('وضعیت گزارش')->badge(),
                Tables\Columns\TextColumn::make('outcome')->label('نتیجه'),
            ])
            ->actions([Tables\Actions\ViewAction::make()->label('جزئیات')]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('حضور و وضعیت محل')->schema([
                Infolists\Components\TextEntry::make('technician.name')->label('تکنسین'),
                Infolists\Components\TextEntry::make('started_at')->label('ورود')->jalaliDateTime(),
                Infolists\Components\TextEntry::make('finished_at')->label('خروج')->jalaliDateTime(),
                Infolists\Components\TextEntry::make('site_condition')->label('وضعیت هنگام مراجعه'),
                Infolists\Components\TextEntry::make('problem_description')->label('شرح مشکل'),
                Infolists\Components\TextEntry::make('latitude')->label('عرض جغرافیایی'),
                Infolists\Components\TextEntry::make('longitude')->label('طول جغرافیایی'),
            ])->columns(2),
            Infolists\Components\Section::make('اقدامات و ادامهٔ کار')->schema([
                Infolists\Components\TextEntry::make('actions')
                    ->label('اقدامات انجام‌شده')
                    ->state(fn (FieldServiceVisit $record): string => implode('، ', $record->actions ?? [])),
                Infolists\Components\TextEntry::make('has_remaining_work')
                    ->label('کار باقیمانده')
                    ->formatStateUsing(fn ($state): string => $state ? 'بله' : 'خیر'),
                Infolists\Components\TextEntry::make('remaining_action')->label('اقدام باقیمانده'),
                Infolists\Components\TextEntry::make('continuation_owner')->label('مسئول ادامه'),
                Infolists\Components\TextEntry::make('continuation_note')->label('توضیح ادامه'),
                Infolists\Components\TextEntry::make('outcome')->label('نتیجه'),
            ])->columns(2),
            Infolists\Components\Section::make('تأیید سازمان')->schema([
                Infolists\Components\TextEntry::make('status')->label('وضعیت گزارش'),
                Infolists\Components\TextEntry::make('reviewer.name')->label('بررسی‌کننده'),
                Infolists\Components\TextEntry::make('reviewed_at')->label('زمان بررسی')->jalaliDateTime(),
                Infolists\Components\TextEntry::make('review_note')->label('توضیح سازمان'),
            ])->columns(2),
            Infolists\Components\RepeatableEntry::make('photos')->label('عکس‌ها')->schema([
                Infolists\Components\TextEntry::make('kind')->label('نوع'),
                Infolists\Components\ImageEntry::make('url')->label('عکس')->height(150),
            ])->columns(2),
            Infolists\Components\RepeatableEntry::make('reviews')->label('تاریخچهٔ بررسی')->schema([
                Infolists\Components\TextEntry::make('decision')->label('تصمیم'),
                Infolists\Components\TextEntry::make('note')->label('توضیح'),
                Infolists\Components\TextEntry::make('reviewer.name')->label('بررسی‌کننده'),
                Infolists\Components\TextEntry::make('created_at')->label('زمان')->jalaliDateTime(),
            ])->columns(2),
        ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
