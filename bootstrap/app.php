<?php

use App\Http\Middleware\EnsureDiscordTokenValid;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Event;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->web(append: [
            HandleInertiaRequests::class,
            EnsureDiscordTokenValid::class,
        ]);

        // Exempt Stripe webhook from CSRF protection
        $middleware->validateCsrfTokens(except: [
            'api/stripe/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->booted(function () {
        // Register event listeners
        Event::listen(
            \App\Events\LicenseAssigned::class,
            \App\Listeners\SendLicenseAssignedEmail::class
        );
    })->create();
