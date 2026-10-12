<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralWithdrawalResource\Pages;
use App\Models\ReferralWithdrawal;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * صف درخواست‌های برداشت پورسانت مأمور خرید به شبا. تهاترها فوری انجام می‌شوند و این‌جا فقط دیده می‌شوند.
 * رد درخواست، مبلغ را به موجودی قابل برداشت برمی‌گرداند (موجودی از روی دفتر حساب محاسبه می‌شود).
 */
class ReferralWithdrawalResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = ReferralWithdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static ?string $navigationLabel = 'برداشت‌های مأمور خرید';

    protected static ?string $modelLabel = 'درخواست برداشت';

    protected static ?string $pluralModelLabel = 'درخواست‌های برداشت مأمور خرید';

    protected static function getViewPermission(): string
    {
        return 'view-referral-withdrawals';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-referral-withdrawals';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ReferralWithdrawal::whereIn('status', [ReferralWithdrawal::STATUS_PENDING, ReferralWithdrawal::STATUS_APPROVED])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        $open = fn (ReferralWithdrawal $r) => $r->method === ReferralWithdrawal::METHOD_SHEBA
            && in_array($r->status, [ReferralWithdrawal::STATUS_PENDING, ReferralWithdrawal::STATUS_APPROVED], true);

        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('شناسه')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('مأمور خرید')
                    ->formatStateUsing(fn ($state, ReferralWithdrawal $r) => trim($state . ' ' . $r->user?->last_name))
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.phone')->label('موبایل')->searchable(),
                Tables\Columns\TextColumn::make('amount')->label('مبلغ')->formatStateUsing(fn ($state) => number_format($state) . ' تومان')->sortable(),
                Tables\Columns\TextColumn::make('method')->label('روش')->formatStateUsing(fn ($state) => ReferralWithdrawal::METHODS[$state] ?? $state),
                Tables\Columns\TextColumn::make('sheba_number')->label('شبا')->placeholder('-')->copyable(),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')
                    ->formatStateUsing(fn ($state) => ReferralWithdrawal::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        ReferralWithdrawal::STATUS_PAID, ReferralWithdrawal::STATUS_DONE => 'success',
                        ReferralWithdrawal::STATUS_REJECTED => 'danger',
                        ReferralWithdrawal::STATUS_APPROVED => 'info',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('tracking_code')->label('کد پیگیری')->placeholder('-')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ درخواست')->jalaliDateTime('Y/m/d H:i')->sortable(),
                Tables\Columns\TextColumn::make('processed_at')->label('تاریخ پردازش')->jalaliDateTime('Y/m/d H:i')->placeholder('-')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('وضعیت')->options(ReferralWithdrawal::STATUSES),
                Tables\Filters\SelectFilter::make('method')->label('روش')->options(ReferralWithdrawal::METHODS),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('تأیید')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn (ReferralWithdrawal $r) => $r->status === ReferralWithdrawal::STATUS_PENDING && $r->method === ReferralWithdrawal::METHOD_SHEBA && static::canEdit($r))
                    ->requiresConfirmation()
                    ->action(function (ReferralWithdrawal $record) {
                        $record->update(['status' => ReferralWithdrawal::STATUS_APPROVED]);
                        Notification::make()->title('درخواست تأیید شد.')->success()->send();
                    }),
                Tables\Actions\Action::make('pay')
                    ->label('ثبت واریز')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (ReferralWithdrawal $r) => $open($r) && static::canEdit($r))
                    ->form([Forms\Components\TextInput::make('tracking_code')->label('کد پیگیری واریز')->required()->maxLength(100)])
                    ->action(function (ReferralWithdrawal $record, array $data) {
                        $record->update([
                            'status' => ReferralWithdrawal::STATUS_PAID,
                            'tracking_code' => $data['tracking_code'],
                            'processed_at' => now(),
                        ]);
                        Notification::make()->title('واریز ثبت شد.')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('رد')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (ReferralWithdrawal $r) => $open($r) && static::canEdit($r))
                    ->form([Forms\Components\Textarea::make('admin_note')->label('دلیل رد')->required()->rows(2)])
                    ->action(function (ReferralWithdrawal $record, array $data) {
                        $record->update([
                            'status' => ReferralWithdrawal::STATUS_REJECTED,
                            'admin_note' => $data['admin_note'],
                            'processed_at' => now(),
                        ]);
                        Notification::make()->title('درخواست رد شد؛ مبلغ به موجودی مأمور برگشت.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferralWithdrawals::route('/'),
        ];
    }
}
