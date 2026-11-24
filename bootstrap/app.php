<?php

use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckUserNotBlocked;
use App\Http\Middleware\CheckServiceActive;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // NEW: Caddy sits in front of this app and speaks https to the visitor. Trust its X-Forwarded-* headers so
        // Laravel knows the request was https and writes https addresses (form actions, redirects, asset links).
        $middleware->trustProxies(at: '*');

        // Register middleware aliases
        $middleware->alias([
            'auth'   => Authenticate::class,
            'guest'  => RedirectIfAuthenticated::class,
            'admin'  => AdminMiddleware::class,
            'check.user.not.blocked' => CheckUserNotBlocked::class,
            'check.service.active' => CheckServiceActive::class,
        ]);

        // Replace the default VerifyCsrfToken with our custom one
        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class => VerifyCsrfToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash(['bvn', 'nin']);
    })->create();