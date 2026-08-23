<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
class MonthlyRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'درآمد ماهانه (۱۲ ماه اخیر)';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        if (!Auth::user()?->can('view-dashboard')) {
            return []; // داشبورد خالی
        }
        $data = [];
        $labels = [];

        // Get revenue for last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $startDate = now()->subMonths($i)->startOfMonth();
            $endDate = now()->subMonths($i)->endOfMonth();

            $revenue = Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('payment_status', 1)
                ->whereNotNull('pakar_price')
                ->sum('pakar_price');

            $data[] = $revenue;

            // Format month name in Persian
            $monthNames = [
                1 => 'فروردین',
                2 => 'اردیبهشت',
                3 => 'خرداد',
                4 => 'تیر',
                5 => 'مرداد',
                6 => 'شهریور',
                7 => 'مهر',
                8 => 'آبان',
                9 => 'آذر',
                10 => 'دی',
                11 => 'بهمن',
                12 => 'اسفند'
            ];

            $monthNumber = (int) $startDate->format('n');
            $labels[] = $monthNames[$monthNumber] ?? $startDate->format('M');
        }

        return [
            'datasets' => [
                [
                    'label' => 'درآمد (تومان)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return value.toLocaleString(); }',
                    ],
                ],
            ],
        ];
    }
}
