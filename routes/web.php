<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;

// /ready, HELMET's readiness probe, is registered in bootstrap/app.php,
// outside the web group: a probe carries no session and no cookies.

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth.sesame'])->name('dashboard');

// Preflight's portability job signs in on one replica and reads the session
// on the other (session-hop). These routes exist only when that job sets
// PORTABILITY_PROBE=yes; they use the default guard, not SESAME's CAS.
if (config('app.portability_probe')) {
    Route::get('_portability/login', function () {
        Auth::login(User::firstOrCreate(
            ['email' => 'portability@example.invalid'],
            ['name' => 'Portability', 'password' => Str::random(40)],
        ));

        return 'signed in';
    });
    Route::get('_portability/me', fn () => Auth::check() ? 'ok' : abort(401));
}

require __DIR__.'/settings.php';
