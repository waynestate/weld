<?php

use App\Support\DummyAppKey;
use RuntimeException;

it('refuses the dummy key in production', function () {
    expect(fn () => DummyAppKey::refuseIn('production', DummyAppKey::VALUE))
        ->toThrow(RuntimeException::class, 'APP_KEY is the dummy test key. Run php artisan key:generate and set a real APP_KEY.');
});

it('allows the dummy key while testing and locally', function () {
    DummyAppKey::refuseIn('testing', DummyAppKey::VALUE);
    DummyAppKey::refuseIn('local', DummyAppKey::VALUE);
});

it('allows a real key in production', function () {
    DummyAppKey::refuseIn('production', 'base64:'.str_repeat('B', 44));
});

it('uses the same dummy key as phpunit.xml', function () {
    $xml = file_get_contents(dirname(__DIR__, 2).'/phpunit.xml');

    expect($xml)->toContain(DummyAppKey::VALUE);
});
