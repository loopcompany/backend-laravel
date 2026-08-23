<?php

namespace App\Http\Middleware;

use App\Models\Analytics;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // فقط برای درخواست‌های GET ثبت کنیم
        if ($request->isMethod('GET') && !$request->ajax()) {
            // عدم ثبت برای admin panel و API
            if (!$request->is('admin/*') && !$request->is('api/*')) {
                try {
                    Analytics::recordVisit(
                        $request->userAgent(),
                        $request->headers->get('referer')
                    );
                } catch (\Exception $e) {
                    // در صورت خطا، analytics را نادیده می‌گیریم
                    Log::warning('Analytics recording failed: ' . $e->getMessage());
                }
            }
        }

        return $response;
    }
}
