<?php

namespace App\Filament\Resources;

use App\Models\OrderReceipt;
use App\Services\OrderSettlementService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderReceiptResource extends Resource
{
    protected static ?string $model = OrderReceipt::class;
    protected static ?string $navigationGroup = 'گزارشات و آمار';
    protected static ?string $navigationLabel = 'دریافت‌ها و امانت تکنسین';
    protected static ?string $modelLabel = 'دریافت سفارش';
    protected static ?string $pluralModelLabel = 'دریافت‌ها و امانت تکنسین';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function canViewAny(): bool
    {
        $admin = auth('admin')->user();
        return $admin?->is_active && ($admin->isSuperAdmin() || $admin->can('view-technician-custody') || $admin->can('review-order-settlements'));
    }
    public static function canView($record): bool { return static::canViewAny(); }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            Tables\Columns\TextColumn::make('order_id')->label('سفارش')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('technician.name')->label('تکنسین')->searchable(),
            Tables\Columns\TextColumn::make('method')->label('روش')->formatStateUsing(fn ($state) => OrderReceipt::METHODS[$state] ?? $state),
            Tables\Columns\TextColumn::make('amount')->label('مبلغ / ارزش (تومان)')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('item_description')->label('کالای تهاتری')->searchable()->wrap(),
            Tables\Columns\TextColumn::make('reference')->label('پیگیری')->searchable(),
            Tables\Columns\TextColumn::make('status')->label('تأیید تسویه')->badge()->formatStateUsing(fn ($state) => OrderReceipt::STATUSES[$state] ?? $state),
            Tables\Columns\TextColumn::make('custody_status')->label('امانت')->badge()->formatStateUsing(fn ($state) => ['held' => 'در اختیار تکنسین', 'returned' => 'تحویل شرکت', 'none' => 'بدون امانت'][$state] ?? $state),
            Tables\Columns\TextColumn::make('created_at')->label('ثبت')->jalaliDateTime('Y/m/d H:i')->sortable(),
            Tables\Columns\TextColumn::make('returned_at')->label('تحویل شرکت')->jalaliDateTime('Y/m/d H:i'),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->label('وضعیت تأیید')->options(OrderReceipt::STATUSES),
            Tables\Filters\SelectFilter::make('custody_status')->label('امانت')->options(['held' => 'در اختیار تکنسین', 'returned' => 'تحویل شرکت', 'none' => 'بدون امانت']),
            Tables\Filters\SelectFilter::make('technician_id')->label('تکنسین')->relationship('technician', 'name')->searchable()->preload(),
        ])->actions(static::receiptActions());
    }

    public static function receiptActions(): array
    {
        return [
            Tables\Actions\Action::make('order')->label('مشاهده سفارش')->url(fn ($record) => OrderResource::getUrl('edit', ['record' => $record->order_id]))->visible(fn () => OrderResource::canViewAny()),
            Tables\Actions\Action::make('image')->label('تصویر رسید')->icon('heroicon-o-photo')->visible(fn ($record) => (bool) $record->receipt_path)->url(fn ($record) => route('admin.order-receipt.image', $record))->openUrlInNewTab(),
            Tables\Actions\Action::make('approve')->label('تأیید دریافت')->color('success')->requiresConfirmation()
                ->visible(fn ($record) => $record->status === 'pending' && (auth('admin')->user()?->isSuperAdmin() || auth('admin')->user()?->can('review-order-settlements')))
                ->action(fn ($record) => app(OrderSettlementService::class)->review($record, true, null, auth('admin')->user())),
            Tables\Actions\Action::make('reject')->label('رد دریافت')->color('danger')
                ->form([Forms\Components\Textarea::make('note')->label('علت رد')->required()->maxLength(2000)])
                ->visible(fn ($record) => $record->status === 'pending' && (auth('admin')->user()?->isSuperAdmin() || auth('admin')->user()?->can('review-order-settlements')))
                ->action(fn ($record, $data) => app(OrderSettlementService::class)->review($record, false, $data['note'], auth('admin')->user())),
            Tables\Actions\Action::make('receive')->label('تحویل امانت به شرکت')->requiresConfirmation()
                ->visible(fn ($record) => $record->custody_status === 'held' && (auth('admin')->user()?->isSuperAdmin() || auth('admin')->user()?->can('receive-technician-custody')))
                ->action(fn ($record) => app(OrderSettlementService::class)->returnCustody($record, auth('admin')->user())),
        ];
    }
    public static function getPages(): array { return ['index' => OrderReceiptResource\Pages\ListOrderReceipts::route('/')]; }
}
