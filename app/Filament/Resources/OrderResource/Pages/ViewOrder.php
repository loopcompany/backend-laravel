<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('print_label')
                ->label('چاپ لیبل')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('admin.orders.label', ['order' => $this->record->getKey()]))
                ->openUrlInNewTab(),
            Actions\Action::make('confirm_delivery')
                ->label(fn (): string => $this->record->delivery_confirmed_at ? 'تأیید مجدد مشخصات تحویل' : 'تأیید و ارسال برای کاربر')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (): bool => $this->record->returnsGoodsToUser())
                ->requiresConfirmation()
                ->modalHeading('تأیید و ارسال مشخصات تحویل برای کاربر')
                ->modalDescription('از این لحظه تحویل‌گیرنده، روش ارسال و تاریخ و ساعت تحویل در رسید کاربر نمایش داده می‌شود. اگر کاربر بعداً تحویل‌گیرنده یا روش ارسال را تغییر دهد، دوباره باید تأیید شود.')
                ->action(fn () => OrderResource::confirmDelivery($this->record)),
        ];
    }

    // protected function resolveRecord($key): Model
    // {
    //     return static::getResource()::resolveRecordRouteBinding($key)
    //         ->load([
    //             'details.field',
    //             'details.fieldDetail',
    //             'user_address',
    //         ]);
    // }
}
