<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Waynestate\Sesame\Sesame;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_sesames_login()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_signing_in_with_the_fake_driver_opens_the_dashboard()
    {
        // The fake driver signs in on the spot: one GET /login stores the
        // user in the session and redirects to the intended page.
        $this->get('/login')->assertRedirect('/');

        $response = $this->get('/dashboard');
        $response->assertOk();
    }

    public function test_the_signed_in_user_is_the_one_sesame_imported()
    {
        $this->get('/login');

        $this->assertDatabaseHas('users', [
            'username' => config('sesame.fake.username'),
        ]);

        $user = User::where('username', config('sesame.fake.username'))->firstOrFail();
        $this->assertSame($user->id, app(Sesame::class)->user()->id);
    }
}
