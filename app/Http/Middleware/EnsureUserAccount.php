<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * مسیرهای /account فقط برای حساب کاربر عادی و سازمانی/شرکتی (مدل User) هستند؛
 * امنیت حساب تکنسین زیر /technician/security است.
 */
class EnsureUserAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'این بخش فقط برای حساب کاربری و سازمانی در دسترس است.',
                'error_code' => 'USER_ACCOUNT_ONLY',
            ], 403);
        }

        return $next($request);
    }
}
