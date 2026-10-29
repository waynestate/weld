<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sign-in is SESAME's job: /login and /logout come from the package, and
 * the fake driver stands in for the identity provider. These tests cover
 * the full loop a developer runs locally before pointing at CAS.
 */
class SesameAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_fake_driver_signs_in_on_a_single_login_request()
    {
        $response = $this->get('/login');

        $response->assertRedirect('/');

        $this->assertDatabaseHas('users', [
            'username' => config('sesame.fake.username'),
            'email' => config('sesame.fake.username').'@wayne.edu',
        ]);
    }

    public function test_a_signed_in_user_reaches_protected_pages()
    {
        $this->get('/login');

        $this->get('/dashboard')->assertOk();
        $this->get('/settings/profile')->assertOk();
    }

    public function test_logout_ends_the_session()
    {
        $this->get('/login');
        $this->get('/dashboard')->assertOk();

        $this->post('/logout')->assertRedirect('/');

        // The session is gone: the next protected page bounces back to
        // SESAME's login.
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_an_intended_url_is_honoured_after_login()
    {
        $this->get('/dashboard')->assertRedirect('/login');

        $this->get('/login')->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk();
    }
}
