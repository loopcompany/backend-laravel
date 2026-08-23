<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\QuickActionsWidget; 
use App\Filament\Widgets\MonthlyRevenueChart;
use App\Filament\Widgets\OrderStatusChart;
use App\Filament\Widgets\TechnicianStatusChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.pages.dashboard';

    public function getTitle(): string
    {
        return 'داشبورد';
    }

    public function getWidgets(): array
    {
        return [
            DashboardStatsOverview::class,
            QuickActionsWidget::class,
            MonthlyRevenueChart::class,
            OrderStatusChart::class,
            TechnicianStatusChart::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 12;
    }
}