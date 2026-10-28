<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * SESAME owns authentication: /login, /logout and /cas/*.
 *
 * This test fails if WELD ever grows its own auth routes again -
 * register, password reset, two-factor, email verification, password
 * confirmation - or if a Fortify controller sneaks back in.
 */
class AuthRoutesTest extends TestCase
{
    public function test_the_app_defines_no_auth_routes_of_its_own()
    {
        $forbidden = '/(register|password|two-factor|forgot|reset|verify|confirm)/';

        $offenders = collect(Route::getRoutes())
            ->map(fn ($route) => $route->uri())
            ->filter(fn (string $uri) => preg_match($forbidden, $uri) === 1)
            ->values();

        $this->assertTrue(
            $offenders->isEmpty(),
            'Routes that belong to SESAME, not WELD: '.$offenders->implode(', '),
        );
    }

    public function test_no_fortify_routes_are_registered()
    {
        $fortify = collect(Route::getRoutes())
            ->map(fn ($route) => $route->getActionName())
            ->filter(fn (string $action) => str_starts_with($action, 'Laravel\Fortify'))
            ->values();

        $this->assertTrue(
            $fortify->isEmpty(),
            'Fortify routes are back: '.$fortify->implode(', '),
        );
    }
}
