<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralCommissionResource\Pages;
use App\Models\ReferralCommission;
use App\Traits\HasFilamentPermissions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * پورسانت‌های مأموران خرید و تأیید برداشت پس از تسویهٔ کامل سفارش.
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
                Tables\Columns\TextColumn::make('order_amount')->label('مبلغ مبنا')->formatStateUsing(fn ($state) => number_format($state) . ' تومان'),
                Tables\Columns\TextColumn::make('commission_amount')->label('پورسانت')->formatStateUsing(fn ($state) => number_format($state) . ' تومان')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')
                    ->formatStateUsing(fn ($state) => ReferralCommission::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        ReferralCommission::STATUS_AVAILABLE => 'success',
                        ReferralCommission::STATUS_CANCELED => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('calculation_note')->label('مبنای محاسبه')->wrap(),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ ثبت')->jalaliDateTime('Y/m/d H:i')->sortable(),
                Tables\Columns\TextColumn::make('settled_at')->label('تاریخ تسویه')->jalaliDateTime('Y/m/d H:i')->placeholder('-'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve_withdrawal')->label('تأیید تسویهٔ پورسانت')
                    ->visible(fn (ReferralCommission $record) => $record->status === ReferralCommission::STATUS_PENDING
                        && $record->order?->settled_at && auth('admin')->user()?->is_active
                        && (auth('admin')->user()->isSuperAdmin() || auth('admin')->user()->can('edit-referral-withdrawals')))
                    ->requiresConfirmation()
                    ->action(fn (ReferralCommission $record) => app(\App\Services\ReferralAgentService::class)->approveForWithdrawal($record, auth('admin')->user())),
                Tables\Actions\Action::make('manual')->label('تعیین پورسانت دستی')
                    ->visible(fn ($record)=>!$record->settled_at && auth('admin')->user()?->can('edit-referral-withdrawals'))
                    ->authorize(fn()=>auth('admin')->user()?->can('edit-referral-withdrawals') ?? false)
                    ->form([\Filament\Forms\Components\TextInput::make('amount')->label('پورسانت نهایی (تومان)')->numeric()->integer()->minValue(0)->required()])
                    ->action(function ($record,$data) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record,$data) {
                            \App\Models\User::lockForUpdate()->findOrFail($record->user_id);
                            $record=$record->fresh();
                            abort_if($record->settled_at,422);
                            // Preserve the consumed code; manual settlement is an audited commission override.
                            $record->forceFill(['commission_amount'=>(int)$data['amount'],'calculation_note'=>'مبلغ دستی مدیریت'])->save();
                            app(\App\Services\ReferralAgentService::class)->settleManual($record,(int)$data['amount']);
                        });
                    }),
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
