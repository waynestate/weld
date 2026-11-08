<?php

use App\Health\SchedulerHeartbeat;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// HELMET's readiness probe: /ready answers 503 while the scheduler's
// heartbeat is stale, so a stopped scheduler shows up (WELD W23). It sits
// outside the auth middleware, like Citadel's own probes, and /up stays
// the liveness answer.
Route::helmetReady('/ready', ['scheduler' => SchedulerHeartbeat::class]);

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth.sesame'])->name('dashboard');

require __DIR__.'/settings.php';
