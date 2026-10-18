<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * زبان صفحات سایت فارسی است تا پیغام‌های پیش‌فرض اعتبارسنجی لاراول (resources/lang/fa)
 * به‌جای انگلیسی نمایش داده شوند. پنل ادمین (Filament و درخواست‌های Livewire آن) دست نمی‌خورد.
 */
class SetSiteLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('admin', 'admin/*', 'livewire/*', 'filament/*')) {
            app()->setLocale('fa');
        }

        return $next($request);
    }
}
