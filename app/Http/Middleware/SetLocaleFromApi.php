<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // ۱) اولویت با header
        $locale = $request->header('Accept-Language');
        
        // ۲) یا از query string (مثلاً ?lang=fa)
        if (!$locale) {
            $locale = $request->query('lang');
        }

        // ۳) چک کن جزو زبان‌های ساپورت‌شده هست یا نه
        $supported = ['fa', 'en'];
        if (! in_array($locale, $supported)) {
            $locale = config('app.locale'); // پیش‌فرض fa
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
