<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * TRUSTED_PROXIES reaches the app through .env, the way production loads it:
 * config/helmet.php turns it into helmet.proxies (when HELMET_PROXIES is
 * unset), and HELMET's provider copies that list into Laravel's own
 * trusted-proxy setting at boot. The observable effect is the
 * Strict-Transport-Security header: HELMET sends it only when the request is
 * seen as arriving over HTTPS, and X-Forwarded-Proto counts only when the
 * peer is a trusted proxy.
 */
class TrustedProxiesTest extends TestCase
{
    /**
     * The .env contents as found when the test started, if any: the test
     * writes its own .env while it runs and must leave the tree as it found
     * it.
     */
    private ?string $originalEnv = null;

    protected function setUp(): void
    {
        parent::setUp();

        $envFile = base_path('.env');
        $this->originalEnv = is_file($envFile) ? (string) file_get_contents($envFile) : null;
    }

    protected function tearDown(): void
    {
        if ($this->originalEnv === null) {
            @unlink(base_path('.env'));
        } else {
            file_put_contents(base_path('.env'), $this->originalEnv);
        }

        // The boots in this test left the proxy values in the process-wide
        // env sources; clear them so the next test loads .env.example fresh.
        foreach (['TRUSTED_PROXIES', 'HELMET_PROXIES'] as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }

        parent::tearDown();
    }

    public function test_the_alias_from_env_is_trusted_through_helmet(): void
    {
        $this->bootWithProxies('127.0.0.1', null);

        $response = $this->ip('127.0.0.1')
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/dashboard');

        $response->assertRedirect('/login');
        $response->assertHeader('Strict-Transport-Security');
    }

    public function test_helmet_proxies_wins_over_the_alias(): void
    {
        $this->bootWithProxies('10.9.8.7', '127.0.0.1');

        $response = $this->ip('10.9.8.7')
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/dashboard');

        $response->assertRedirect('/login');
        $response->assertHeaderMissing('Strict-Transport-Security');

        // The alias's address is out, but HELMET_PROXIES's own address still
        // works. Boot a fresh app before the second call, because the harness
        // builds each request URL from the currently bound request and the
        // first call rebinds it to a secure one.
        $this->bootWithProxies('10.9.8.7', '127.0.0.1');

        $response = $this->ip('127.0.0.1')
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/dashboard');

        $response->assertRedirect('/login');
        $response->assertHeader('Strict-Transport-Security');
    }

    public function test_a_set_but_empty_alias_trusts_nothing(): void
    {
        $this->bootWithProxies('', null);

        $response = $this->ip('172.18.0.1')
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/dashboard');

        $response->assertRedirect('/login');
        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_unset_variables_trust_nothing_without_a_preset(): void
    {
        $this->bootWithProxies(null, null);

        $response = $this->ip('172.18.0.1')
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/dashboard');

        $response->assertRedirect('/login');
        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_laravels_own_trust_honors_forwarded_proto(): void
    {
        $this->bootWithProxies('10.9.8.7', null);

        Route::get('/__tp', fn () => response()->json(['secure' => request()->isSecure()]));

        $trusted = $this->ip('10.9.8.7')
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->getJson('/__tp');

        $trusted->assertOk();
        $trusted->assertJson(['secure' => true]);

        // Boot a fresh app for the second call. The test harness builds each
        // request URL from the currently bound request, and the first call
        // rebinds it to a secure one, so a relative URL here would inherit
        // HTTPS=on and mask what is under test: proxy trust.
        $this->bootWithProxies('10.9.8.7', null);

        Route::get('/__tp', fn () => response()->json(['secure' => request()->isSecure()]));

        $untrusted = $this->ip('10.9.8.8')
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->getJson('/__tp');

        $untrusted->assertOk();
        $untrusted->assertJson(['secure' => false]);
    }

    /**
     * Re-boot the app with a .env that carries exactly the proxy lines under
     * test, the way production loads them: the value arrives through the
     * environment the app loads, not as a closure-time process variable.
     * null removes the line from the file.
     */
    private function bootWithProxies(?string $trustedProxies, ?string $helmetProxies): void
    {
        // Drop both variables from every source env() reads first: the first
        // boot of this test already loaded .env.example into the process-wide
        // sources, and Dotenv's immutable repository keeps existing values
        // over anything the new .env says.
        foreach (['TRUSTED_PROXIES', 'HELMET_PROXIES'] as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }

        $env = (string) file_get_contents(base_path('.env.example'));
        $env = (string) preg_replace(
            '/^TRUSTED_PROXIES=.*/m',
            $trustedProxies === null ? '' : 'TRUSTED_PROXIES='.$trustedProxies,
            $env,
        );
        $env = (string) preg_replace(
            '/^HELMET_PROXIES=.*/m',
            $helmetProxies === null ? '' : 'HELMET_PROXIES='.$helmetProxies,
            $env,
        );
        file_put_contents(base_path('.env'), $env);

        $this->refreshApplication();
    }

    /**
     * Pin the peer address the app sees it as: REMOTE_ADDR. The framework's
     * HTTP test client has no ip() shorthand, so this is the one line it
     * needs before a trusted-proxy check can fire.
     */
    private function ip(string $ip): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }
}
