<?php

use App\Providers\AppServiceProvider;
use App\Support\DummyAppKey;

it('boots the app with the dummy key in the testing environment', function () {
    $this->get('/')->assertStatus(200);
});

it('refuses to boot production with the dummy key', function () {
    config(['app.key' => DummyAppKey::VALUE]);
    $this->app['env'] = 'production';

    try {
        expect(fn () => (new AppServiceProvider($this->app))->boot())
            ->toThrow(RuntimeException::class, 'APP_KEY is the dummy test key. Run php artisan key:generate and set a real APP_KEY.');
    } finally {
        $this->app['env'] = 'testing';
    }
});
