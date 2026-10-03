<?php

namespace App\Filament\Resources\EmploymentFileResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * تاریخچه‌ی تغییرات پرونده (فقط نام بخش‌های تغییرکرده؛ مقادیر محرمانه ثبت نمی‌شوند).
 */
class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'تاریخچه تغییرات';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ و ساعت')->jalaliDateTime('Y/m/d H:i'),
                Tables\Columns\TextColumn::make('admin.name')->label('کاربر')->placeholder('-'),
                Tables\Columns\TextColumn::make('action')->label('اقدام')
                    ->formatStateUsing(fn ($state) => ['created' => 'ایجاد پرونده', 'updated' => 'ویرایش'][$state] ?? $state),
                Tables\Columns\TextColumn::make('changed_fields')->label('بخش‌های تغییرکرده')
                    ->getStateUsing(fn ($record) => implode('، ', $record->changed_fields ?? []) ?: '-')
                    ->wrap(),
            ]);
    }
}
