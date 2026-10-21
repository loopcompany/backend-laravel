<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use App\Models\Order;
use App\Models\OrderWorkflow;
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

    /**
     * تب‌ها به ترتیب مراحل فرآیند؛ هر مرحله رنگ و آیکون اختصاصی دارد (هم‌رنگ نشان وضعیت و نوار ردیف).
     */
    public function getTabs(): array
    {
        $counts = Order::query()
            ->selectRaw('workflow_stage, count(*) as c')
            ->groupBy('workflow_stage')
            ->pluck('c', 'workflow_stage');

        $tabs = ['all' => Tab::make('همه سفارشات')->icon('heroicon-o-queue-list')];

        foreach (OrderWorkflow::STAGES as $stage => $label) {
            $tabs[$stage] = Tab::make($label)
                ->icon(OrderWorkflow::STAGE_ICONS[$stage])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('workflow_stage', $stage))
                ->badge($counts[$stage] ?? 0)
                ->badgeColor(OrderWorkflow::STAGE_COLORS[$stage]);
        }

        $tabs['company'] = Tab::make('سفارش‌های سازمانی')
            ->icon('heroicon-o-building-office-2')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('account_type', '!=', 'individual')))
            ->badge(fn () => Order::whereHas('user', fn ($q) => $q->where('account_type', '!=', 'individual'))->count())
            ->badgeColor('lime');

        $tabs['unpaid'] = Tab::make('پرداخت نشده')
            ->icon('heroicon-o-banknotes')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 0)->whereNotIn('workflow_stage', [OrderWorkflow::STAGE_CANCELLED]))
            ->badge(fn () => Order::where('payment_status', 0)->whereNotIn('workflow_stage', [OrderWorkflow::STAGE_CANCELLED])->count())
            ->badgeColor('slate');

        return $tabs;
    }
}
