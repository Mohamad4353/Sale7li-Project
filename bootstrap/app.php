<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'active' => EnsureAccountActive::class,
        ]);

        // Reverse proxies whose X-Forwarded-* headers this application should
        // trust (a TLS-terminating load balancer / ingress in front of the app).
        //
        // Empty by default — NO proxy is trusted and forwarded headers are
        // ignored, so a spoofed `X-Forwarded-Proto: https` can never make a
        // plain HTTP request look secure (and thus never trigger HSTS).
        //
        // Production sets TRUSTED_PROXIES to the proxy IP(s)/CIDR(s),
        // comma-separated (e.g. "10.0.0.0/8,192.0.2.10"), or the literal "*"
        // only when the app is reachable *exclusively* through the proxy.
        // Read from env() here because bootstrap runs before config caching.
        // See docs/DEPLOYMENT.md → "Reverse proxy, TLS & trusted proxies".
        $trustedProxies = trim((string) env('TRUSTED_PROXIES', ''));

        if ($trustedProxies !== '') {
            $middleware->trustProxies(
                at: $trustedProxies === '*'
                    ? '*'
                    : array_values(array_filter(array_map('trim', explode(',', $trustedProxies)))),
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Keep exception handling centralized; production rendering can be customized here.
    })->create();
