<?php

namespace App\Http\Middleware;

use App\Models\Analytics;
use App\Models\EngagementEvent;
use App\Support\ProjectLogger;
use Closure;
use Illuminate\Http\Request;
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

        // فقط برای صفحات عمومی/پنل کاربر ثبت کنیم؛ پنل ادمین و API جدا هستند.
        if ($request->isMethod('GET') && !$request->ajax()) {
            // پنل ادمین از engagement عمومی جداست؛ دانلودهای API هم باید ثبت شوند.
            if (!$request->is('admin/*')) {
                try {
                    $contentDisposition = (string) $response->headers->get('Content-Disposition');
                    $isDownload = str_contains(strtolower($contentDisposition), 'attachment');

                    if ($isDownload) {
                        EngagementEvent::recordFromRequest(EngagementEvent::DOWNLOAD, $request, [
                            'content_type' => $response->headers->get('Content-Type'),
                            'filename' => $contentDisposition,
                        ]);
                    } elseif (!$request->is('api/*') && str_contains(strtolower((string) $response->headers->get('Content-Type')), 'text/html')) {
                        Analytics::recordVisit(
                            $request->userAgent(),
                            $request->headers->get('referer')
                        );

                        EngagementEvent::recordFromRequest(EngagementEvent::PAGE_VIEW, $request);
                    }
                } catch (\Exception $e) {
                    // در صورت خطا، analytics را نادیده می‌گیریم
                    ProjectLogger::write(ProjectLogger::ENGAGEMENT, 'warning', 'analytics.recording.failed', [
                        'exception' => $e::class,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $response;
    }
}
