<?php

namespace App\Filament\Resources\CooperationRequestResource\RelationManagers;

use App\Models\CooperationRequestLog;
use App\Support\Careers\CareerOptions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * ۱۰. سوابق و تاریخچه‌ی گزینش: تاریخ و ساعت، اقدام‌کننده، نوع اقدام، تغییر وضعیت، نتیجه، توضیحات، پیوست‌ها.
 */
class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'سوابق و تاریخچه گزینش';

    protected static ?string $modelLabel = 'اقدام';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('result')->label('نتیجه اقدام')->maxLength(255),
            Forms\Components\Textarea::make('note')->label('توضیحات')->rows(3)->required()->columnSpanFull(),
            Forms\Components\FileUpload::make('attachments')
                ->label('پیوست‌ها و مستندات')
                ->multiple()
                ->disk('local')
                ->directory(fn () => 'cooperation-requests/' . $this->getOwnerRecord()->id . '/logs')
                ->maxSize(10240)
                ->downloadable()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ و ساعت')->jalaliDateTime('Y/m/d H:i'),
                Tables\Columns\TextColumn::make('admin.name')->label('اقدام‌کننده')->placeholder('متقاضی / سیستم'),
                Tables\Columns\TextColumn::make('action')->label('نوع اقدام')->formatStateUsing(fn ($state) => CooperationRequestLog::ACTIONS[$state] ?? $state),
                Tables\Columns\TextColumn::make('to_status')->label('تغییر وضعیت')
                    ->formatStateUsing(fn ($state, CooperationRequestLog $record) => $state
                        ? ($record->from_status ? (CareerOptions::STATUSES[$record->from_status] ?? $record->from_status) . ' ← ' : '') . (CareerOptions::STATUSES[$state] ?? $state)
                        : null)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('result')->label('نتیجه')->placeholder('-')->wrap(),
                Tables\Columns\TextColumn::make('note')->label('توضیحات')->placeholder('-')->wrap()->limit(80),
                Tables\Columns\TextColumn::make('attachments')->label('پیوست')
                    ->getStateUsing(fn (CooperationRequestLog $record) => count($record->attachments ?? []) ?: null)
                    ->formatStateUsing(fn ($state) => $state . ' فایل')
                    ->placeholder('-'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('ثبت اقدام / یادداشت')
                    ->visible(fn () => auth('admin')->user()?->can('edit-cooperation-requests'))
                    ->mutateFormDataUsing(fn (array $data) => $data + [
                        'admin_id' => auth('admin')->id(),
                        'action' => 'note',
                        'created_at' => now(),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
