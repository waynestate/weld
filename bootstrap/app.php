<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Support\OracleErrors;
use App\Support\TrustedProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // SESAME owns sign-in at /login, so any guest Laravel's own guards
        // would bounce still lands on SESAME's door.
        $middleware->redirectGuestsTo('/login');

        // Trust only the proxies named in TRUSTED_PROXIES, so X-Forwarded-Proto
        // is honored only for requests that really arrived over HTTPS. env() is
        // read directly because this closure runs before config is loaded;
        // unset falls back to Citadel's proxy, set-but-empty trusts nothing.
        $raw = env('TRUSTED_PROXIES'); // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig (closure runs before config is loaded)
        // A bool-looking value such as true is not a valid proxy list, so fall back to the default.
        $middleware->trustProxies(at: TrustedProxies::fromEnv(is_string($raw) ? $raw : null));

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
