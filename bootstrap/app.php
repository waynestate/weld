<?php

use App\Health\CacheReady;
use App\Health\DatabaseReady;
use App\Health\SchedulerHeartbeat;
use App\Http\Middleware\HandleInertiaRequests;
use App\Support\OracleErrors;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // HELMET's readiness probe: /ready answers 503 while the database,
        // the cache or the scheduler's heartbeat fails (WELD W11, W23), so
        // the balancer skips the instance instead of anything restarting it.
        // It sits here, outside the web group, as HELMET's guide asks: no
        // session row, no cookie and no auth for a request that polls every
        // few seconds. /up stays the liveness answer and never asks.
        then: function () {
            Route::helmetReady('/ready', [
                'database' => DatabaseReady::class,
                'cache' => CacheReady::class,
                'scheduler' => SchedulerHeartbeat::class,
            ]);
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // SESAME owns sign-in at /login, so any guest Laravel's own guards
        // would bounce still lands on SESAME's door.
        $middleware->redirectGuestsTo('/login');

        // Trusted proxies come from HELMET, not from this file: the HELMET
        // provider copies helmet.proxies (HELMET_PROXIES, or the HELMET_PRESET
        // platform preset, or the older TRUSTED_PROXIES alias in
        // config/helmet.php) into Laravel's trusted-proxy setting, so a
        // platform address never lives in the app's code.
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // The common Oracle errors get one plain sentence in the log, on
        // top of the normal report: what to check, never the raw message.
        $exceptions->reportable(function (Throwable $e): void {
            $sentence = OracleErrors::describe($e);

            if ($sentence !== null) {
                Log::error('[oracle] '.$sentence);
            }
        });
    })->create();
