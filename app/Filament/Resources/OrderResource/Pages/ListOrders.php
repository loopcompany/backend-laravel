<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('همه سفارشات'),

            'pending' => Tab::make('در انتظار')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 0))
                ->badge(fn() => \App\Models\Order::where('status', 0)->count())
                ->badgeColor('warning'),
            'company' => Tab::make('سفارش‌های سازمانی')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query
                        ->whereHas('user', fn($q) => $q->where('account_type', '!=', 'individual'))
                )
                ->badge(fn() => \App\Models\Order::whereHas('user', fn($q) => $q->where('account_type', '!=', 'individual'))->count())
                ->badgeColor('warning'),

            'processing' => Tab::make('در حال انجام')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 1))
                ->badge(fn() => \App\Models\Order::where('status', 1)->count())
                ->badgeColor('primary'),

            'completed' => Tab::make('انجام شده')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 2))
                ->badge(fn() => \App\Models\Order::where('status', 2)->count())
                ->badgeColor('success'),

            'cancelled' => Tab::make('لغو شده')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', [3, 4, 5, 6]))
                ->badge(fn() => \App\Models\Order::whereIn('status', [3, 4, 5, 6])->count())
                ->badgeColor('danger'),


            'unpaid' => Tab::make('پرداخت نشده')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('payment_status', 0))
                ->badge(fn() => \App\Models\Order::where('payment_status', 0)->count())
                ->badgeColor('warning'),
        ];
    }
}
