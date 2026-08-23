<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $admin = Filament::auth()->user();

        // اگر کاربر لاگین نیست
        if (!$admin) {
            return $this->unauthorized($request);
        }

        // اگر حساب غیرفعال است
        if (!$admin->is_active) {
            Filament::auth()->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->unauthorized($request, 'حساب کاربری شما غیرفعال است');
        }

        // بررسی permission 
        if ($permission && !$admin->can($permission)) {
           
            return $this->forbidden($request);
        }

        return $next($request);
    }

    protected function unauthorized(Request $request, string $message = 'دسترسی غیرمجاز'): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 401);
        }

        return redirect()->route('filament.admin.auth.login');
    }

    protected function forbidden(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'شما مجوز دسترسی به این بخش را ندارید'
            ], 403);
        }

        abort(403, 'شما مجوز دسترسی به این بخش را ندارید');
    }
}
