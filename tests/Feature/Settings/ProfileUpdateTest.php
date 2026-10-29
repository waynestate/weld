<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sign in through SESAME with the fake driver and return the imported
     * user. Every test starts signed out, so one GET /login is enough.
     */
    protected function signInViaSesame(): User
    {
        $this->get('/login');

        return User::where('username', config('sesame.fake.username'))->firstOrFail();
    }

    public function test_profile_page_is_displayed()
    {
        $this->signInViaSesame();

        $response = $this->get('/settings/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = $this->signInViaSesame();

        $response = $this
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = $this->signInViaSesame();

        // Imported users count as verified from the first request.
        $this->assertNotNull($user->email_verified_at);

        $response = $this
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account()
    {
        $user = $this->signInViaSesame();

        $response = $this
            ->delete('/settings/profile', [
                'confirmation' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());

        // The session died with the user: the next protected page bounces
        // back to SESAME's login.
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_confirmation_is_case_insensitive()
    {
        $user = $this->signInViaSesame();

        $response = $this
            ->delete('/settings/profile', [
                'confirmation' => strtoupper($user->email),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
    }

    public function test_wrong_email_cannot_delete_account()
    {
        $user = $this->signInViaSesame();

        $response = $this
            ->from('/settings/profile')
            ->delete('/settings/profile', [
                'confirmation' => 'someone-else@example.com',
            ]);

        $response
            ->assertSessionHasErrors('confirmation')
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_missing_confirmation_cannot_delete_account()
    {
        $user = $this->signInViaSesame();

        $response = $this
            ->from('/settings/profile')
            ->delete('/settings/profile');

        $response
            ->assertSessionHasErrors('confirmation')
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->fresh());
    }
}
