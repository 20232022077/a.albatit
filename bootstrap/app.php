<?php

use App\Http\Middleware\ApplyContentSecurityPolicy;
use App\Http\Middleware\ApplySecurityHeaders;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            ApplyContentSecurityPolicy::class,
            ApplySecurityHeaders::class,
        ]);
        $middleware->alias(['active' => EnsureUserIsActive::class]);

        // Trust a reverse proxy running on the same host (e.g. nginx, or a
        // local tunnel like cloudflared/ngrok during testing) so generated
        // URLs and the client IP reflect X-Forwarded-* headers instead of
        // the proxy's own loopback connection.
        $middleware->trustProxies(at: '127.0.0.1');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
