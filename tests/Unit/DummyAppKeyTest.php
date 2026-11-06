<?php

use App\Support\DummyAppKey;

it('refuses the dummy key in production', function () {
    expect(fn () => DummyAppKey::refuseIn('production', DummyAppKey::VALUE))
        ->toThrow(RuntimeException::class, 'APP_KEY is the dummy test key. Run php artisan key:generate and set a real APP_KEY.');
});

it('allows the dummy key while testing and locally', function () {
    expect(fn () => DummyAppKey::refuseIn('testing', DummyAppKey::VALUE))->not->toThrow(RuntimeException::class);
    expect(fn () => DummyAppKey::refuseIn('local', DummyAppKey::VALUE))->not->toThrow(RuntimeException::class);
});

it('allows a real key in production', function () {
    expect(fn () => DummyAppKey::refuseIn('production', 'base64:'.str_repeat('B', 44)))->not->toThrow(RuntimeException::class);
});

it('uses the same dummy key as phpunit.xml', function () {
    $xml = file_get_contents(dirname(__DIR__, 2).'/phpunit.xml');

    expect($xml)->toContain(DummyAppKey::VALUE);
});
