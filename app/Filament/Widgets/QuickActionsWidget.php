<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Technician;
use App\Models\Order;
use App\Models\Blog;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        if (!Auth::user()?->can('view-dashboard')) {
            return [
                'actions'=>[],
                'labels'=>[],
            ]; // داشبورد خالی
        }
        $today = now()->toDateString();

        $orderCounts = Order::query()
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->selectRaw("
            SUM(CASE WHEN DATE(orders.created_at) = ? AND users.account_type = 'individual' THEN 1 ELSE 0 END) AS individual_today,
            SUM(CASE WHEN DATE(orders.created_at) = ? AND users.account_type = 'company' THEN 1 ELSE 0 END) AS company_today,
            SUM(CASE WHEN DATE(orders.created_at) = ? AND users.account_type NOT IN ('individual','company') THEN 1 ELSE 0 END) AS org_today
        ", [$today, $today, $today])
            ->first();

        $pendingTechnicians = Technician::query()
            ->where('approval_status', 'pending')
            ->count();

        return [
            'actions' => [
                [
                    'title' => 'ثبت نام کاربر جدید',
                    'description' => 'افزودن کاربر جدید به سیستم',
                    'icon' => 'heroicon-o-user-plus',
                    'color' => 'success',
                    'url' => '/admin/users/create',
                    'btnTitle' => 'ثبت',
                    'stats' => [
                        'label' => 'کاربران امروز',
                        'value' => \App\Models\User::whereDate('created_at', today())->count()
                    ]
                ],
                [
                    'title' => 'ثبت نام تکنسین جدید',
                    'description' => 'افزودن تکنسین جدید به سیستم',
                    'icon' => 'heroicon-o-wrench-screwdriver',
                    'color' => 'warning',
                    'btnTitle' => 'ثبت',
                    'url' => '/admin/technicians/create',
                    'stats' => [
                        'label' => 'تکنسین امروز',
                        'value' => \App\Models\Technician::whereDate('created_at', today())->count()
                    ]
                ],
                [
                    'title' => 'مدیریت سفارشات',
                    'description' => 'مشاهده و مدیریت سفارشات',
                    'icon' => 'heroicon-o-clipboard-document-list',
                    'color' => 'info',
                    'url' => '/admin/orders',
                    'btnTitle' => 'مشاهده',
                    'stats' => [
                        'label' => 'سفارش امروز',
                        'value' => Order::whereDate('created_at', today())->count()
                    ]
                ],
                [
                    'title' => 'ثبت سفارش',
                    'description' => 'ایجاد سفارش جدید',
                    'icon' => 'heroicon-o-document-text',
                    'color' => 'primary',
                    'btnTitle' => 'ثبت',
                    'url' => '/admin/orders/create',
                    'stats' => [
                        'label' => 'کل سفارشات',
                        'value' => Order::count()
                    ]
                ]
            ],
            'labels' => [
                [
                    'title' => 'سفارش جدید کاربران',
                    'type' => 'user',
                    'color1' => '#e4fbff',
                    'color2' => '#cdf4fb',
                    'color3' => '#a7e1f7',
                    'color4' => '#88deff',
                    'url'=> asset("admin/orders?activeTab=pending"),
                    'stats' => [
                        'label' => 'امروز',
                        'value' => $orderCounts->individual_today ?? 0,
                    ],
                ],
                [
                    'title' => 'سفارش جدید شرکتی',
                    'type' => 'company',
                    'color1' => '#eeffe4',
                    'color2' => '#cdfbcd',
                    'color3' => '#c0f7a7',
                    'color4' => '#50cb0f',
                    'url'=> asset("admin/orders?activeTab=company"),
                    'stats' => [
                        'label' => 'امروز',
                        'value' => $orderCounts->company_today ?? 0,
                    ],
                ],
                [
                    'title' => 'سفارش جدید سازمانی',
                    'type' => 'organ',
                    'color1' => '#ffe4e4',
                    'color2' => '#fbcdcd',
                    'color3' => '#f7a7a7',
                    'color4' => '#cb0f79',
                    'url'=> asset("admin/orders?activeTab=company"),
                    'stats' => [
                        'label' => 'امروز',
                        'value' => $orderCounts->org_today ?? 0,
                    ],
                ],
                [
                    'title' => 'تکنسین‌های جدید در انتظار تأیید',
                    'type' => 'tech',
                    'color1' => '#ffe4fe',
                    'color2' => '#fbcdf9',
                    'color3' => '#dea7f7',
                    'color4' => '#a40fcb',
                    'url'=> asset("admin/technicians?tableFilters[approval_status][value]=pending"),
                    'stats' => [
                        'label' => 'در انتظار',
                        'value' => $pendingTechnicians,
                    ],
                ],
            ]
        ];
    }
}