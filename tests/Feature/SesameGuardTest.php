<?php

/*
|--------------------------------------------------------------------------
| The web guard runs on SESAME's sesame driver
|--------------------------------------------------------------------------
|
| config/auth.php points the web guard at the sesame driver, so Laravel's
| own views of the signed-in person ($request->user(), auth()->user(),
| @can, Gate::allows()) see the user SESAME imported, not an empty session.
| These tests run that loop on routes behind auth.sesame, with the fake
| driver standing in for the identity provider.
*/

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // The probe routes sit in the web group, like the app's own routes, so
    // the profile param binds to a real user, and behind auth.sesame, so a
    // guest is bounced to sign-in before either closure runs.
    Gate::define('own-profile', fn (User $user, User $profile): bool => $user->is($profile));

    Route::middleware(['web', 'auth.sesame'])->group(function () {
        Route::get('/guard-probe/whoami', function (Request $request) {
            $fromRequest = $request->user()?->username ?? 'none';
            $fromFacade = auth()->user()?->username ?? 'none';

            return $fromRequest.' / '.$fromFacade;
        });

        Route::get('/guard-probe/profile/{profile}', function (User $profile) {
            abort_unless(Gate::allows('own-profile', $profile), 403);

            return 'ok';
        });
    });
});

it('lets the owner through the policy check on an auth.sesame route', function () {
    $this->get('/login');

    $owner = User::where('username', config('sesame.fake.username'))->first();

    $this->get('/guard-probe/profile/'.$owner->id)->assertOk();
});

it('answers 403 when the signed-in user does not own the profile', function () {
    $stranger = User::factory()->create();

    $this->get('/login');

    $this->get('/guard-probe/profile/'.$stranger->id)->assertForbidden();
});

it('hands the sesame user to request user and the auth facade', function () {
    $this->get('/login');

    $username = config('sesame.fake.username');

    $this->get('/guard-probe/whoami')
        ->assertOk()
        ->assertSee($username.' / '.$username);
});

it('sends a guest to sign in', function () {
    $this->get('/guard-probe/whoami')->assertRedirect('/login');
});

it('reads no user from the guard after logout', function () {
    $this->get('/login');
    $this->get('/guard-probe/whoami')->assertOk();

    $this->post('/logout')->assertRedirect('/');

    // The session is gone: the next guarded page bounces back to sign-in,
    // so the guard reads no user.
    $this->get('/guard-probe/whoami')->assertRedirect('/login');
});
