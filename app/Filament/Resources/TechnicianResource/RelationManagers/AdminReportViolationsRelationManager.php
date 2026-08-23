<?php

namespace App\Filament\Resources\TechnicianResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdminReportViolationsRelationManager extends RelationManager
{
    protected static string $relationship = 'adminReportViolations';

    protected static ?string $title = 'گزارش تخلفات';
    protected static ?string $modelLabel = 'گزارش تخلف';
    protected static ?string $pluralModelLabel = 'گزارش تخلفات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('عنوان')
                    ->required()
                    ->maxLength(1000)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->label('شرح تخلف')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull()
                    ->helperText('توضیحات کامل در مورد تخلف تکنسین'),

                Forms\Components\Toggle::make('can_reply')
                    ->label('امکان پاسخ‌دهی')
                    ->helperText('آیا تکنسین می‌تواند به این گزارش پاسخ دهد؟')
                    ->default(true)
                    ->onColor('success')
                    ->offColor('danger')
                    ->inline(false)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('technician_response')
                    ->label('پاسخ تکنسین')
                    ->rows(4)
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn ($record) => $record?->technician_response)
                    ->columnSpanFull()
                    ->helperText('پاسخ تکنسین به این گزارش (فقط خواندنی)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }
                        return null;
                    }),

                Tables\Columns\IconColumn::make('can_reply')
                    ->label('قابل پاسخ')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('has_response')
                    ->label('پاسخ داده شده')
                    ->state(fn ($record) => !empty($record->technician_response))
                    ->boolean()
                    ->trueIcon('heroicon-o-chat-bubble-left-right')
                    ->falseIcon('heroicon-o-chat-bubble-left')
                    ->trueColor('info')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین بروزرسانی')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('can_reply')
                    ->label('قابلیت پاسخ')
                    ->placeholder('همه')
                    ->trueLabel('قابل پاسخ')
                    ->falseLabel('غیرقابل پاسخ'),

                Tables\Filters\TernaryFilter::make('has_response')
                    ->label('وضعیت پاسخ')
                    ->placeholder('همه')
                    ->trueLabel('پاسخ داده شده')
                    ->falseLabel('بدون پاسخ')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('technician_response')->where('technician_response', '!=', ''),
                        false: fn (Builder $query) => $query->where(function ($q) {
                            $q->whereNull('technician_response')->orWhere('technician_response', '');
                        }),
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('ثبت گزارش تخلف جدید')
                    ->modalHeading('ثبت گزارش تخلف')
                    ->modalDescription('ثبت گزارش تخلف برای این تکنسین')
                    ->modalButton('ثبت گزارش')
                    ->successNotificationTitle('گزارش تخلف با موفقیت ثبت شد')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Convert toggle to integer (0 or 1)
                        $data['can_reply'] = $data['can_reply'] ? 1 : 0;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('مشاهده'),

                Tables\Actions\EditAction::make()
                    ->label('ویرایش')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Convert toggle to integer (0 or 1)
                        $data['can_reply'] = $data['can_reply'] ? 1 : 0;
                        return $data;
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->modalHeading('حذف گزارش تخلف')
                    ->modalDescription('آیا از حذف این گزارش تخلف اطمینان دارید؟')
                    ->successNotificationTitle('گزارش تخلف حذف شد'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('حذف گزارش‌های انتخاب شده')
                        ->modalDescription('آیا از حذف گزارش‌های تخلف انتخاب شده اطمینان دارید؟')
                        ->successNotificationTitle('گزارش‌های تخلف حذف شدند'),
                ]),
            ])
            ->emptyStateHeading('هیچ گزارش تخلفی ثبت نشده')
            ->emptyStateDescription('برای ثبت گزارش تخلف از دکمه بالا استفاده کنید.')
            ->emptyStateIcon('heroicon-o-exclamation-triangle');
    }
}
