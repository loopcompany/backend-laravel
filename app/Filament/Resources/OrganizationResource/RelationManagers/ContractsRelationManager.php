<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use App\Models\OrganizationContract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'contracts';
    protected static ?string $title = 'قراردادهای سازمان';
    protected static ?string $modelLabel = 'قرارداد';

    public function form(Form $form): Form
    {
        // فرم فقط برای مشاهده - بدون ویرایش
        return $form
            ->schema([
                Forms\Components\TextInput::make('status')
                    ->label('وضعیت')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'در انتظار',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده',
                        default => 'نامشخص',
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),

                Tables\Columns\TextColumn::make('rejection_reason')
                    ->label('دلیل رد')
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('uploaded_at')
                    ->label('تاریخ آپلود')
                    ->jalaliDateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('تاریخ بررسی')
                    ->jalaliDateTime()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending' => 'در انتظار',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده',
                    ]),
            ])
            ->defaultSort('uploaded_at', 'desc')
            ->headerActions([
                // حذف دکمه ایجاد - فقط کاربر می‌تواند آپلود کند
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('دانلود')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (OrganizationContract $record) => $record->contract_url)
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('approve')
                    ->label('تایید')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (OrganizationContract $record) => $record->isPending())
                    ->action(function (OrganizationContract $record) {
                        $record->update([
                            'status' => OrganizationContract::STATUS_APPROVED,
                            'reviewed_at' => now(),
                            'reviewed_by' => \Illuminate\Support\Facades\Auth::id(),
                            'rejection_reason' => null,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('قرارداد تایید شد')
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('رد')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (OrganizationContract $record) => $record->isPending())
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('دلیل رد')
                            ->required()
                            ->rows(4)
                            ->helperText('لطفاً دلیل رد قرارداد را وارد کنید.'),
                    ])
                    ->action(function (OrganizationContract $record, array $data) {
                        $record->update([
                            'status' => OrganizationContract::STATUS_REJECTED,
                            'reviewed_at' => now(),
                            'reviewed_by' => \Illuminate\Support\Facades\Auth::id(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        Notification::make()
                            ->warning()
                            ->title('قرارداد رد شد')
                            ->send();
                    }),
            ])
            ->bulkActions([
                // حذف bulk actions
            ]);
    }
}
