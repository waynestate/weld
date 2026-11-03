<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\TrustedProxies;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * TRUSTED_PROXIES feeds trustProxies() in bootstrap/app.php. The observable
 * effect is the Strict-Transport-Security header: HELMET sends it only when
 * the request is seen as arriving over HTTPS, and X-Forwarded-Proto counts
 * only when the peer is a trusted proxy.
 */
class TrustedProxiesTest extends TestCase
{
    public function test_a_request_from_a_trusted_proxy_is_seen_as_secure(): void
    {
        $this->bootWithTrustedProxies('127.0.0.1');

        $response = $this->ip('127.0.0.1')
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/dashboard');

        $response->assertRedirect('/login');
        $response->assertHeader('Strict-Transport-Security');
    }

    public function test_an_unset_variable_still_trusts_citadels_proxy(): void
    {
        $this->bootWithTrustedProxies(null);

        $response = $this->ip('172.18.0.1')
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/dashboard');

        $response->assertRedirect('/login');
        $response->assertHeader('Strict-Transport-Security');
    }

    public function test_a_set_but_empty_variable_trusts_nothing(): void
    {
        $this->bootWithTrustedProxies('');

        $response = $this->ip('172.18.0.1')
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/dashboard');

        $response->assertRedirect('/login');
        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_laravels_own_trust_honors_forwarded_proto(): void
    {
        $this->bootWithTrustedProxies('10.9.8.7');

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
        $this->bootWithTrustedProxies('10.9.8.7');

        Route::get('/__tp', fn () => response()->json(['secure' => request()->isSecure()]));

        $untrusted = $this->ip('10.9.8.8')
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->getJson('/__tp');

        $untrusted->assertOk();
        $untrusted->assertJson(['secure' => false]);
    }

    /**
     * Set (or clear) TRUSTED_PROXIES in every source env() reads, then
     * re-boot the app so the middleware closure picks up the new value.
     */
    private function bootWithTrustedProxies(?string $value): void
    {
        $this->setTrustedProxiesEnv($value);

        $this->refreshApplication();

        // The re-boot's Dotenv pass re-reads .env into the process-wide env
        // sources and clobbers the value set above. That matters because the
        // trustProxies() closure in bootstrap/app.php runs lazily, when the
        // HTTP kernel resolves on the first request, and reads env() at that
        // moment. Re-assert the value so a local .env cannot decide which
        // proxies this test trusts.
        $this->setTrustedProxiesEnv($value);

        // HELMET detects HTTPS from its own proxy list, which the bare test
        // container leaves empty. Point it at the same TRUSTED_PROXIES value
        // so the HSTS header tracks the variable under test.
        config(['helmet.proxies' => TrustedProxies::fromEnv($value)]);
    }

    /**
     * Put (or remove) TRUSTED_PROXIES in every source env() reads: putenv,
     * $_ENV and $_SERVER.
     */
    private function setTrustedProxiesEnv(?string $value): void
    {
        putenv('TRUSTED_PROXIES');
        unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);

        if ($value !== null) {
            putenv('TRUSTED_PROXIES='.$value);
            $_ENV['TRUSTED_PROXIES'] = $value;
            $_SERVER['TRUSTED_PROXIES'] = $value;
        }
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
