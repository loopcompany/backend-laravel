<?php

namespace App\Http\Middleware;

use App\Services\Security\GeoIpLocator;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * «IP آخرین اتصال» و موقعیت تقریبی هر نشست. زمان آخرین فعالیت را خود Sanctum در last_used_at ثبت می‌کند.
 */
class TrackTokenActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        // فقط وقتی IP عوض شده می‌نویسد، تا هر درخواست یک UPDATE اضافه نداشته باشد.
        // موقعیت تقریبی هم فقط در همین لحظه (تغییر IP) از GeoIP محلی محاسبه می‌شود.
        if ($token instanceof PersonalAccessToken && $token->exists && $token->last_ip !== $request->ip()) {
            $token->forceFill([
                'last_ip' => $request->ip(),
                'last_location' => app(GeoIpLocator::class)->locate($request->ip()),
            ])->saveQuietly();
        }

        return $next($request);
    }
}
