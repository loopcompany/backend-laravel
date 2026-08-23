<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderStatusChart extends ChartWidget
{
    protected static ?string $heading = 'توزیع وضعیت سفارشات';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        if (!Auth::user()?->can('view-dashboard')) {
            return []; // داشبورد خالی
        }
        $statusCounts = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $labels = [];
        $data = [];
        $colors = [];

        $statusMap = [
            0 => ['label' => 'در انتظار', 'color' => '#f59e0b'], // warning
            1 => ['label' => 'در حال انجام', 'color' => '#3b82f6'], // info
            2 => ['label' => 'تکمیل شده', 'color' => '#10b981'], // success
            3 => ['label' => 'لغو توسط کاربر', 'color' => '#ef4444'], // danger
            4 => ['label' => 'لغو توسط تکنسین', 'color' => '#dc2626'],
            5 => ['label' => 'لغو توسط سیستم', 'color' => '#b91c1c'],
            6 => ['label' => 'لغو شده', 'color' => '#991b1b'],
        ];

        foreach ($statusMap as $status => $info) {
            if (isset($statusCounts[$status]) && $statusCounts[$status] > 0) {
                $labels[] = $info['label'];
                $data[] = $statusCounts[$status];
                $colors[] = $info['color'];
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'تعداد سفارشات',
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
