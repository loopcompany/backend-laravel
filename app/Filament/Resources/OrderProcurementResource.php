<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderProcurementResource\Pages;
use App\Models\OrderProcurement;
use App\Models\OrderProcurementItem;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Morilog\Jalali\Jalalian;

/**
 * صف «استعلام تامین کالا»: اعلام قیمت اقلام (معتبر تا پایان ساعات کاری)، پیش‌پرداخت، آماده‌سازی و ارسال.
 */
class OrderProcurementResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = OrderProcurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'استعلام تامین کالا';

    protected static ?string $modelLabel = 'استعلام تامین کالا';

    protected static ?string $pluralModelLabel = 'استعلام‌های تامین کالا';

    protected static ?int $navigationSort = 3;

    protected static function getViewPermission(): string
    {
        return 'view-procurements';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-procurements';
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
        $count = OrderProcurement::where('status', OrderProcurement::STATUS_AWAITING_QUOTE)->count();

        return $count > 0 ? (string) $count : null;
    }

    /** پس از پیش‌پرداخت، اقلام و مبالغ قفل می‌شوند. */
    public static function isLocked(?OrderProcurement $record): bool
    {
        return $record?->prepayment_paid_at !== null
            || in_array($record?->status, [OrderProcurement::STATUS_REJECTED, OrderProcurement::STATUS_ACCEPTED], true);
    }

    public static function form(Form $form): Form
    {
        $jalali = fn ($date) => $date ? Jalalian::forge($date)->format('Y/m/d H:i') : '-';

        return $form->schema([
            Forms\Components\Section::make('سفارش')
                ->schema([
                    Forms\Components\Placeholder::make('order_link')
                        ->label('شماره سفارش')
                        ->content(fn (?OrderProcurement $record) => $record?->order_id ?? '-'),
                    Forms\Components\Placeholder::make('customer')
                        ->label('سازمان / شرکت')
                        ->content(fn (?OrderProcurement $record) => $record?->order?->user?->organization?->organization_name
                            ?? trim(($record?->order?->user?->name ?? '') . ' ' . ($record?->order?->user?->last_name ?? '')) ?: '-'),
                    Forms\Components\Placeholder::make('status_view')
                        ->label('وضعیت')
                        ->content(fn (?OrderProcurement $record) => $record?->statusLabel() ?? '-'),
                    Forms\Components\Placeholder::make('valid_until_view')
                        ->label('اعتبار قیمت تا')
                        ->content(fn (?OrderProcurement $record) => $jalali($record?->valid_until)),
                    Forms\Components\Placeholder::make('delivery_mode_view')
                        ->label('نحوه‌ی ارائه')
                        ->content(fn (?OrderProcurement $record) => collect($record?->order?->delivery_mode ?? [])->map(fn ($m) => [
                            'once_short' => 'کوتاه مدت / یکبار',
                            'monthly_short' => 'ماهانه',
                            'yearly_long' => 'سالانه / بلندمدت',
                            'project' => 'پروژه‌ای',
                        ][$m] ?? $m)->implode('، ') ?: '-'),
                    Forms\Components\Placeholder::make('order_des')
                        ->label('توضیحات سفارش')
                        ->content(fn (?OrderProcurement $record) => $record?->order?->des ?: '-')
                        ->columnSpanFull(),
                ])->columns(4),

            Forms\Components\Section::make('اقلام و قیمت')
                ->description('قیمت واحد همه‌ی اقلام را وارد کنید و با «اعلام قیمت» برای کاربر بفرستید. اعتبار قیمت تا پایان ساعات کاری همان روز است.')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->label('اقلام')
                        ->schema([
                            Forms\Components\TextInput::make('title')->label('کالا')->required()->maxLength(191),
                            Forms\Components\Select::make('condition')->label('وضعیت')->options(OrderProcurementItem::CONDITIONS)->required()->default('new'),
                            Forms\Components\TextInput::make('quantity')->label('تعداد')->numeric()->minValue(1)->required()->default(1),
                            Forms\Components\TextInput::make('unit_price')->label('قیمت واحد')->numeric()->minValue(0)->suffix('تومان'),
                            Forms\Components\Textarea::make('description')->label('توضیح')->rows(1)->maxLength(1000)->columnSpanFull(),
                        ])
                        ->columns(4)
                        ->disabled(fn (?OrderProcurement $record) => static::isLocked($record))
                        ->addActionLabel('افزودن قلم'),
                ]),

            Forms\Components\Section::make('مبالغ و پیش‌پرداخت')
                ->schema([
                    Forms\Components\TextInput::make('prepayment_percent')
                        ->label('درصد پیش‌پرداخت')
                        ->numeric()->minValue(0)->maxValue(100)->suffix('%')->required()
                        ->disabled(fn (?OrderProcurement $record) => static::isLocked($record)),
                    Forms\Components\Placeholder::make('total_view')->label('جمع کل')
                        ->content(fn (?OrderProcurement $record) => number_format((int) $record?->total_amount) . ' تومان'),
                    Forms\Components\Placeholder::make('prepayment_view')->label('پیش‌پرداخت')
                        ->content(fn (?OrderProcurement $record) => number_format((int) $record?->prepayment_amount) . ' تومان'
                            . ($record?->prepayment_paid_at ? ' — پرداخت شد ' . $jalali($record->prepayment_paid_at) : ' — پرداخت نشده')),
                    Forms\Components\Placeholder::make('remaining_view')->label('باقی‌مانده')
                        ->content(fn (?OrderProcurement $record) => number_format((int) $record?->remaining_amount) . ' تومان'
                            . ($record?->remaining_paid_at ? ' — پرداخت شد ' . $jalali($record->remaining_paid_at) : ' — پرداخت نشده')),
                    Forms\Components\Textarea::make('note')->label('توضیح پنل برای کاربر')->rows(2)->maxLength(1000)->columnSpanFull(),
                    Forms\Components\Placeholder::make('reject_reason_view')->label('دلیل رد کاربر')
                        ->content(fn (?OrderProcurement $record) => $record?->reject_reason)
                        ->visible(fn (?OrderProcurement $record) => filled($record?->reject_reason)),
                ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_id')->label('شماره سفارش')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('order.user.organization.organization_name')->label('سازمان / شرکت')->placeholder('-')->searchable(),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('تعداد اقلام'),
                Tables\Columns\TextColumn::make('total_amount')->label('جمع کل')->formatStateUsing(fn ($state) => number_format((int) $state) . ' تومان'),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')
                    ->state(fn (OrderProcurement $record) => $record->effectiveStatus())
                    ->formatStateUsing(fn ($state) => OrderProcurement::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        OrderProcurement::STATUS_AWAITING_QUOTE => 'warning',
                        OrderProcurement::STATUS_REJECTED, OrderProcurement::STATUS_EXPIRED => 'danger',
                        OrderProcurement::STATUS_DELIVERED, OrderProcurement::STATUS_SHIPPED => 'success',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('valid_until')->label('اعتبار تا')->jalaliDateTime('Y/m/d H:i')->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ ثبت')->jalaliDateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('وضعیت')->options(OrderProcurement::STATUSES),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('بررسی'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderProcurements::route('/'),
            'edit' => Pages\EditOrderProcurement::route('/{record}/edit'),
        ];
    }
}
