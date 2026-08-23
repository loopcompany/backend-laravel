<?php

namespace App\Filament\Widgets;

use App\Models\Admin;
use App\Models\LoginActivity;
use App\Models\User;
use App\Models\Order;
use App\Models\Technician;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
class DashboardStatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        if (!Auth::user()?->can('view-dashboard')) {
            return []; // داشبورد خالی
        }
        return [
            Stat::make('تعداد کل کاربران تایید شده', new HtmlString(
                number_format($this->getVerifiedUsersCount()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">نفر</span>'
            ))

                ->color('success'),

            Stat::make('تعداد کل کاربران سازمانی', new HtmlString(
                number_format($this->getOrgUsersCount()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">نفر</span>'
            ))

                ->color('info'),

            Stat::make('تعداد کل کاربران شرکتی', new HtmlString(
                number_format($this->getCompanyUsersCount()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">نفر</span>'
            ))

                ->color('primary'),

            Stat::make('تعداد کل تکنسین‌ها', new HtmlString(
                number_format($this->getTotalTechnicians()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">نفر</span>'
            ))

                ->color('warning'),

            Stat::make('تعداد کل مدیران', new HtmlString(
                number_format($this->getTotalAdmins()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">نفر</span>'
            ))

                ->color('danger'),

            Stat::make('سفارش‌های ثبت شده سایت', new HtmlString(
                number_format($this->getOrdersByPlatformWeb()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">امروز</span>'
            ))
                ->color('primary'),

            Stat::make('سفارش‌های ثبت شده اپلیکیشن', new HtmlString(
                number_format($this->getOrdersByPlatformApp()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">امروز</span>'
            ))
                ->color('info'),

            Stat::make('سفارش‌های ثبت شده حضوری', new HtmlString(
                number_format($this->getOrdersByPlatformPerson()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">امروز</span>'
            ))
                ->color('success'),

            Stat::make('سفارش‌های ثبت شده تلفنی', new HtmlString(
                number_format($this->getOrdersByPlatformPhone()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">امروز</span>'
            ))
                ->color('warning'),

            Stat::make('سفارش‌های در انتظار', new HtmlString(
                number_format($this->getOrdersByStatusPending()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">امروز</span>'
            ))
                ->color('warning'),

            Stat::make('سفارش‌های در حال انجام', new HtmlString(
                number_format($this->getOrdersByStatusInProgress()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">امروز</span>'
            ))
                ->color('info'),

            Stat::make('سفارش‌های انجام شده', new HtmlString(
                number_format($this->getOrdersByStatusDone()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">امروز</span>'
            ))
                ->color('success'),

            Stat::make('سفارش‌های لغو شده', new HtmlString(
                number_format($this->getOrdersByStatusCanceled()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">امروز</span>'
            ))
                ->color('danger'),
            Stat::make('کاربران آنلاین', new HtmlString(
                number_format($this->getOnlineUsersCount()) .
                ' <span style="font-size:15px" class="font-normal align-baseline">نفر</span>'
            ))
                ->color('danger'),

        ];
    }

    // کاربران تایید شده
    private function getOnlineUsersCount(): int
    {
        return LoginActivity::where('user_type', 'user')->where('is_online', 1)->whereDate('created_at', today())->count();
    }

    private function getVerifiedUsersCount(): int
    {
        return User::where('has_access', 1)->count();
    }

    // کاربران سازمانی (غیر از individual و company)
    private function getOrgUsersCount(): int
    {
        return User::whereNotIn('account_type', ['individual', 'company'])->count();
    }

    // کاربران شرکتی
    private function getCompanyUsersCount(): int
    {
        return User::where('account_type', 'company')->count();
    }

    // کل تکنسین‌ها
    private function getTotalTechnicians(): int
    {
        return Technician::count();
    }

    // کل مدیران (فرض: ستون is_admin)
    private function getTotalAdmins(): int
    {
        return Admin::count();
    }

    // سفارش‌های سایت
    private function getOrdersByPlatformWeb(): int
    {
        return Order::where('platform', 'web')
            ->whereDate('created_at', today())
            ->count();
    }

    private function getOrdersByPlatformApp(): int
    {
        return Order::whereIn('platform', ['android', 'ios'])
            ->whereDate('created_at', today())
            ->count();
    }

    private function getOrdersByPlatformPerson(): int
    {
        return Order::where('platform', 'person')
            ->whereDate('created_at', today())
            ->count();
    }

    private function getOrdersByPlatformPhone(): int
    {
        return Order::where('platform', 'phone')
            ->whereDate('created_at', today())
            ->count();
    }

    private function getOrdersByStatusPending(): int
    {
        return Order::where('status', 0)
            ->whereDate('created_at', today())
            ->count();
    }

    private function getOrdersByStatusInProgress(): int
    {
        return Order::where('status', 1)
            ->whereDate('created_at', today())
            ->count();
    }

    private function getOrdersByStatusDone(): int
    {
        return Order::where('status', 2)
            ->whereDate('created_at', today())
            ->count();
    }

    private function getOrdersByStatusCanceled(): int
    {
        return Order::whereIn('status', [3, 4, 5, 6])
            ->whereDate('created_at', today())
            ->count();
    }
}
