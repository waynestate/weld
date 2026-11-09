<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// /ready, HELMET's readiness probe, is registered in bootstrap/app.php,
// outside the web group: a probe carries no session and no cookies.

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth.sesame'])->name('dashboard');

require __DIR__.'/settings.php';
