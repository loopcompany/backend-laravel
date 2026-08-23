<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // تنظیم redirect برای authentication
        $middleware->redirectUsersTo('/user/dashboard');
        $middleware->redirectGuestsTo(fn () => route('web.login'));
        
        // اضافه کردن Analytics middleware به web group
        $middleware->web(append: [
            \App\Http\Middleware\AnalyticsMiddleware::class,
        ]);

        // Register custom middleware aliases
        $middleware->alias([
            'organization.approved' => \App\Http\Middleware\EnsureOrganizationApproved::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
