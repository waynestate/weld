<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SESAME
|--------------------------------------------------------------------------
|
| Authentication is owned by SESAME, the stack's auth project - not by
| this app. When SESAME lands it replaces this file: /login, /logout and
| /cas/* are its routes. Until then, /login is a placeholder so the app
| has somewhere to send guests.
|
*/

Route::get('login', fn () => response('Sign-in arrives with SESAME', 503))->name('login');
