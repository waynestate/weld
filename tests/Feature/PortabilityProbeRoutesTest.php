<?php

/*
|--------------------------------------------------------------------------
| The portability probe routes
|--------------------------------------------------------------------------
|
| Preflight's portability job sets PORTABILITY_PROBE=yes and makes a
| PORTABILITY_PROBE_TOKEN for the run, so its session-hop check can sign in
| on one replica and read the session on the other. The routes must never
| exist anywhere else, so with the default test config (PORTABILITY_PROBE
| unset) they answer 404 and the config key is false. A stray
| PORTABILITY_PROBE=yes without a token opens nothing either: the config key
| stays false and the routes are not registered. With both set, every request
| must carry the token in X-Portability-Token; a missing or wrong token
| answers 404, so a stranger cannot tell the route exists.
*/

use App\Models\User;
use Illuminate\Support\Str;

/**
 * The 48-character hex token the tests send in X-Portability-Token, shaped
 * like the one the CI job makes from /dev/urandom. Never a real secret.
 */
function weldProbeToken(): string
{
    return 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4';
}

/**
 * Put the probe env vars in the process the way the CI job sets them, so a
 * refreshed app reads them from its real environment. An empty token is the
 * stray-env case: PORTABILITY_PROBE=yes alone must open nothing.
 */
function weldSetProbeEnv(string $token): void
{
    foreach ([['PORTABILITY_PROBE', 'yes'], ['PORTABILITY_PROBE_TOKEN', $token]] as [$name, $value]) {
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        putenv($name.'='.$value);
    }
}

it('answers 404 on /_portability/login when PORTABILITY_PROBE is not set', function () {
    $this->get('/_portability/login')->assertNotFound();
});

it('answers 404 on /_portability/me when PORTABILITY_PROBE is not set', function () {
    $this->get('/_portability/me')->assertNotFound();
});

it('reads portability_probe as false in the default test config', function () {
    expect(config('app.portability_probe'))->toBeFalse();
});

it('reads portability_probe as false when PORTABILITY_PROBE=yes has no token', function () {
    weldSetProbeEnv('');
    $this->refreshApplication();

    expect(config('app.portability_probe'))->toBeFalse();
});

describe('the probe routes with the CI env', function () {
    beforeEach(function () {
        weldSetProbeEnv(weldProbeToken());
        $this->refreshApplication();
        // In-memory SQLite starts empty with the refreshed app, and the
        // login route creates the probe user.
        $this->artisan('migrate')->run();
    });

    it('answers 404 on /_portability/login without the token header', function () {
        $this->get('/_portability/login')->assertNotFound();
    });

    it('answers 404 on /_portability/login with a wrong token header', function () {
        $this->get('/_portability/login', ['X-Portability-Token' => str_repeat('f', 48)])->assertNotFound();
    });

    it('answers signed in on /_portability/login with the right token header', function () {
        $this->get('/_portability/login', ['X-Portability-Token' => weldProbeToken()])
            ->assertOk()
            ->assertSee('signed in');
    });

    it('answers ok on /_portability/me with the right token header', function () {
        $user = User::firstOrCreate(
            ['email' => 'portability@example.invalid'],
            ['name' => 'Portability', 'password' => Str::random(40)],
        );

        $this->actingAs($user)
            ->get('/_portability/me', ['X-Portability-Token' => weldProbeToken()])
            ->assertOk()
            ->assertSee('ok');
    });
});
