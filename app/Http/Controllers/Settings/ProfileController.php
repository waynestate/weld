<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Waynestate\Sesame\Sesame;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $this->user($request);

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // The signed-in user lives in the session, so refresh the stored copy
        // or the next page would still show the old values.
        $request->session()->put('sesame.user', $user);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     *
     * WELD signs people in through SESAME, so there is no local credential to
     * confirm with. Deletion is confirmed by typing the user's own email address.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $this->user($request);

        $request->validate([
            'confirmation' => [
                'required',
                'bail',
                function (string $attribute, mixed $value, Closure $fail) use ($user) {
                    if (! is_string($value) || ! hash_equals(strtolower((string) $user->email), strtolower($value))) {
                        $fail('The confirmation must match your email address.');
                    }
                },
            ],
        ]);

        $user->delete();

        // Sign out through SESAME so the session dies with the user; the cas
        // driver points at its own logout, the fake driver ends it locally.
        $url = app(Sesame::class)->logout(url('/'));

        return $url !== null ? redirect($url) : redirect('/');
    }

    /**
     * The signed-in user, from SESAME's session rather than a Laravel guard.
     */
    private function user(Request $request): User
    {
        $user = app(Sesame::class)->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
