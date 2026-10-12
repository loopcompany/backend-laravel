<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralCommissionResource\Pages;
use App\Models\ReferralCommission;
use App\Traits\HasFilamentPermissions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * پورسانت‌های مأموران خرید (فقط مشاهده؛ وضعیت با وضعیت سفارش خودکار هم‌گام می‌شود).
 */
class ReferralCommissionResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = ReferralCommission::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static ?string $navigationLabel = 'پورسانت‌های مأمور خرید';

    protected static ?string $modelLabel = 'پورسانت';

    protected static ?string $pluralModelLabel = 'پورسانت‌های مأمور خرید';

    protected static function getViewPermission(): string
    {
        return 'view-referral-withdrawals';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
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

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_id')->label('شماره سفارش')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('مأمور خرید')
                    ->formatStateUsing(fn ($state, ReferralCommission $r) => trim($state . ' ' . $r->user?->last_name))
                    ->searchable(),
                Tables\Columns\TextColumn::make('referralCode.code')->label('کد معرف')->searchable(),
                Tables\Columns\TextColumn::make('order_amount')->label('مبلغ مبنا')->formatStateUsing(fn ($state) => number_format($state) . ' تومان'),
                Tables\Columns\TextColumn::make('commission_percent')->label('درصد')->suffix('%'),
                Tables\Columns\TextColumn::make('commission_amount')->label('پورسانت')->formatStateUsing(fn ($state) => number_format($state) . ' تومان')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')
                    ->formatStateUsing(fn ($state) => ReferralCommission::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        ReferralCommission::STATUS_AVAILABLE => 'success',
                        ReferralCommission::STATUS_CANCELED => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ ثبت')->jalaliDateTime('Y/m/d H:i')->sortable(),
                Tables\Columns\TextColumn::make('settled_at')->label('تاریخ تسویه')->jalaliDateTime('Y/m/d H:i')->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('وضعیت')->options(ReferralCommission::STATUSES),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferralCommissions::route('/'),
        ];
    }
}
