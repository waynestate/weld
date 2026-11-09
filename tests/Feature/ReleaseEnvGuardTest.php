<?php

/*
|--------------------------------------------------------------------------
| The release refuses a bad production environment
|--------------------------------------------------------------------------
|
| Squire 0.9.0's release script checks a Laravel release's environment
| before anything installs: no APP_KEY stops it, APP_DEBUG on in
| production stops it, and an APP_ENV that is not production is a
| warning (WELD W26, Sourdough practice 4.14). The kit is Squire's byte
| for byte, so this file reads the copy in deploy/ and fails when a kit
| update drops the guard. Debug in production: a fine way to tell the
| whole internet your database password, and we would rather not.
*/

function weldReleaseGuardScript(): string
{
    $path = dirname(__DIR__, 2).'/deploy/scripts/release.sh';

    expect(is_file($path))->toBeTrue(
        'deploy/scripts/release.sh is missing; the deploy kit lost its release script',
    );

    return (string) file_get_contents($path);
}

it('stops a Laravel release without an APP_KEY', function () {
    expect(weldReleaseGuardScript())
        ->toContain('app_key="$(squire_env_value APP_KEY)"')
        ->toContain('fail "APP_KEY is not set;');
});

it('stops a production release with APP_DEBUG on', function () {
    expect(weldReleaseGuardScript())
        ->toContain('app_debug="$(squire_env_value APP_DEBUG)"')
        ->toContain('fail "APP_DEBUG must be false in production');
});

it('only warns when APP_ENV is not production', function () {
    expect(weldReleaseGuardScript())
        ->toContain('warn "APP_ENV is \'$app_env\', not production; the release goes on"');
});

it('checks the environment before Composer installs anything', function () {
    $script = weldReleaseGuardScript();
    $guard = strpos($script, 'fail "APP_KEY is not set;');
    $install = strpos($script, "\ncomposer install --no-dev");

    expect($guard)->not->toBeFalse()
        ->and($install)->not->toBeFalse()
        ->and($guard)->toBeLessThan($install, 'the APP_KEY guard must run before the release installs anything');
});
