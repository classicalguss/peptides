<?php

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
        // Signed with HMAC-SHA256 by VERIFIED; there is no session to carry a token.
        $middleware->validateCsrfTokens(except: [
            'webhooks/verified-crypto',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
