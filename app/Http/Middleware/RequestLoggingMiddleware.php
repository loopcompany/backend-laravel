<?php

namespace App\Http\Middleware;

use App\Support\ProjectLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RequestLoggingMiddleware
{
    /** Add a request id and one compact structured record per request. */
    public function handle(Request $request, Closure $next): Response
    {
        $requestIdHeader = (string) config('logging.request_id_header', 'X-Request-ID');
        $requestId = (string) $request->headers->get($requestIdHeader, '');

        if (!preg_match('/^[A-Za-z0-9._:-]{8,100}$/', $requestId)) {
            $requestId = (string) Str::uuid();
        }

        $request->attributes->set('request_id', $requestId);
        $startedAt = microtime(true);
        $baseContext = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => '/' . ltrim($request->path(), '/'),
            'route' => $request->route()?->getName(),
            'ip' => $request->ip(),
        ];

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            ProjectLogger::write(ProjectLogger::APP, 'error', 'request.failed', $baseContext + [
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $status = $response->getStatusCode();
        $level = $status >= 500 ? 'error' : ($status >= 400 ? 'notice' : 'info');
        $shouldLog = (bool) config('logging.log_requests', false) || $status >= 400;

        if ($shouldLog && (!$this->isNoise($request) || $status >= 400)) {
            ProjectLogger::write(ProjectLogger::APP, $level, 'request.completed', $baseContext + [
                'status' => $status,
                'duration_ms' => $this->durationMs($startedAt),
                'user_id' => $request->user()?->getAuthIdentifier(),
                'content_type' => $response->headers->get('Content-Type'),
            ]);
        }

        $response->headers->set($requestIdHeader, $requestId);

        return $response;
    }

    private function durationMs(float $startedAt): float
    {
        return round((microtime(true) - $startedAt) * 1000, 2);
    }

    private function isNoise(Request $request): bool
    {
        return $request->is('build/*')
            || $request->is('storage/*')
            || in_array($request->path(), ['up', 'favicon.ico', 'robots.txt'], true);
    }
}
