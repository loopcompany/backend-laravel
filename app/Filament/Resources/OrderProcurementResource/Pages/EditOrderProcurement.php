<?php

namespace App\Filament\Resources\OrderProcurementResource\Pages;

use App\Exceptions\BusinessRuleException;
use App\Filament\Resources\OrderProcurementResource;
use App\Models\OrderProcurement;
use App\Services\ProcurementService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrderProcurement extends EditRecord
{
    protected static string $resource = OrderProcurementResource::class;

    protected function afterSave(): void
    {
        // مبالغ از روی اقلام و درصد پیش‌پرداخت (تا پیش از پرداخت پیش‌پرداخت)
        $this->record->refresh()->recalculate();
        $this->record->save();
    }

    protected function getHeaderActions(): array
    {
        $service = app(ProcurementService::class);
        $run = function (callable $step, string $success) {
            try {
                $step();
                Notification::make()->title($success)->success()->send();
                $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
            } catch (BusinessRuleException $e) {
                Notification::make()->title($e->getMessage())->danger()->send();
            }
        };
        $is = fn (string ...$statuses) => in_array($this->record->effectiveStatus(), $statuses, true);

        return [
            Actions\Action::make('quote')
                ->label('اعلام قیمت')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->visible(fn () => $is(OrderProcurement::STATUS_AWAITING_QUOTE, OrderProcurement::STATUS_QUOTED, OrderProcurement::STATUS_EXPIRED))
                ->requiresConfirmation()
                ->modalDescription('ابتدا تغییرات اقلام را ذخیره کنید. قیمت تا پایان ساعات کاری برای کاربر معتبر است.')
                ->action(fn () => $run(fn () => $service->quote($this->record->refresh()), 'قیمت برای کاربر اعلام شد.')),
            Actions\Action::make('ready')
                ->label('کالا آماده شد')
                ->icon('heroicon-o-archive-box')
                ->visible(fn () => $is(OrderProcurement::STATUS_PREPARING))
                ->requiresConfirmation()
                ->action(fn () => $run(fn () => $service->markReady($this->record), 'کالا آماده شد؛ کاربر باقی‌مانده را پرداخت می‌کند.')),
            Actions\Action::make('shipped')
                ->label('ارسال شد')
                ->icon('heroicon-o-truck')
                ->visible(fn () => $is(OrderProcurement::STATUS_READY))
                ->requiresConfirmation()
                ->action(fn () => $run(fn () => $service->markShipped($this->record), 'ارسال ثبت شد.')),
            Actions\Action::make('delivered')
                ->label('تحویل شد')
                ->icon('heroicon-o-check-badge')
                ->visible(fn () => $is(OrderProcurement::STATUS_SHIPPED))
                ->requiresConfirmation()
                ->action(fn () => $run(fn () => $service->markDelivered($this->record), 'تحویل ثبت شد.')),
            Actions\Action::make('order')
                ->label('مشاهده سفارش')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => \App\Filament\Resources\OrderResource::getUrl('view', ['record' => $this->record->order_id]))
                ->openUrlInNewTab(),
        ];
    }
}
