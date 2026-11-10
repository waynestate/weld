<?php

/*
|--------------------------------------------------------------------------
| The portability probe routes
|--------------------------------------------------------------------------
|
| Preflight's portability job sets PORTABILITY_PROBE=yes so its session-hop
| check can sign in on one replica and read the session on the other. The
| routes must never exist anywhere else, so with the default test config
| (PORTABILITY_PROBE unset) they answer 404 and the config key is false.
*/

it('answers 404 on /_portability/login when PORTABILITY_PROBE is not set', function () {
    $this->get('/_portability/login')->assertNotFound();
});

it('answers 404 on /_portability/me when PORTABILITY_PROBE is not set', function () {
    $this->get('/_portability/me')->assertNotFound();
});

it('reads portability_probe as false in the default test config', function () {
    expect(config('app.portability_probe'))->toBeFalse();
});
