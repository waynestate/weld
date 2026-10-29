<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Guests go to the SESAME placeholder until SESAME owns /login.
        $middleware->redirectGuestsTo('/login');

        // Trust only the address HA-Proxy connects from, so X-Forwarded-Proto
        // is honored only for requests that really arrived over HTTPS. A
        // literal list, because this closure runs before config is loaded.
        $middleware->trustProxies(at: ['172.18.0.1']);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
