<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TechnicianAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // بررسی اینکه آیا تکنسین لاگین کرده است
        if (!Auth::guard('technician')->check()) {
            // اگر لاگین نکرده، به صفحه لاگین هدایت می‌شود
            return redirect()->route('web.technician.login-form')
                ->with('error', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');
        }

        return $next($request);
    }
}
