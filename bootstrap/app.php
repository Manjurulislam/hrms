<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RoutePermissionMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust the reverse proxy / load balancer so request()->ip() resolves to the
        // real client IP from X-Forwarded-For. Only loopback and private ranges are
        // trusted (nginx-on-host / DO load balancer) so a public client cannot spoof
        // the header — this keeps the office-network attendance gate unforgeable.
        $middleware->trustProxies(at: [
            '127.0.0.1',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->alias([
            'menu.permission' => RoutePermissionMiddleware::class,
        ]);
        // Apply to all authenticated routes
        $middleware->appendToGroup('auth', [
            RoutePermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
