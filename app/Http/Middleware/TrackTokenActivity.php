<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * «IP آخرین اتصال» هر نشست. زمان آخرین فعالیت را خود Sanctum در last_used_at ثبت می‌کند.
 */
class TrackTokenActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        // فقط وقتی IP عوض شده می‌نویسد، تا هر درخواست یک UPDATE اضافه نداشته باشد.
        if ($token instanceof PersonalAccessToken && $token->exists && $token->last_ip !== $request->ip()) {
            $token->forceFill(['last_ip' => $request->ip()])->saveQuietly();
        }

        return $next($request);
    }
}
