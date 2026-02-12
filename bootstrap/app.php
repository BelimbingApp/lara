<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust reverse proxy headers (Caddy) so Laravel can correctly detect HTTPS
        // and generate https:// URLs behind the proxy.
        $middleware->trustProxies(at: '*');

        // Add database connection recovery middleware to web group
        $middleware->web(append: [
            \App\Http\Middleware\DatabaseConnectionRecovery::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
