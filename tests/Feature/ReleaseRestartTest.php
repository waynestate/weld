<?php

/*
|--------------------------------------------------------------------------
| Restart long-lived processes at deploy
|--------------------------------------------------------------------------
|
| A queue worker and a running schedule:work keep the old code in
| memory until they are told to stop. The Squire 0.7.0 release step
| "laravel restart long-lived processes" runs php artisan reload where
| Laravel has it, otherwise queue:restart and schedule:interrupt, each
| when its --help answers; report only, and SQUIRE_ARTISAN_RESTART=0
| turns the step off (WELD W22). The kit is Squire's byte for byte, so
| this file reads the copy in deploy/ and fails when a kit update drops
| the step or the switch.
*/

function weldReleaseScript(): string
{
    $path = dirname(__DIR__, 2).'/deploy/scripts/release.sh';

    expect(is_file($path))->toBeTrue(
        'deploy/scripts/release.sh is missing; the deploy kit lost its release script',
    );

    return (string) file_get_contents($path);
}

it('restarts the queue workers and the scheduler after optimize', function () {
    $script = weldReleaseScript();

    expect($script)
        ->toContain('php artisan reload')
        ->toContain('php artisan queue:restart')
        ->toContain('php artisan schedule:interrupt');
});

it('reports only and can be turned off', function () {
    $script = weldReleaseScript();

    expect($script)
        ->toContain('SQUIRE_ARTISAN_RESTART')
        ->toContain('SQUIRE_ARTISAN_RESTART=0');
});
