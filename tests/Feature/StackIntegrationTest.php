<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The consolidation plan wants proof that the welded parts hold together in
 * one app. This file pins HELMET's half of that proof: the security headers
 * must be on a normal page, on an error page, and on /up.
 *
 * What the other files already prove, so this file does not repeat it:
 * SesameAuthTest and DashboardTest sign in through SESAME's fake driver (the
 * fake gateway identity from phpunit.xml, never a real credential), reach
 * /dashboard, and show an anonymous request bouncing to /login. AuthRoutesTest
 * pins that /login and /logout stay SESAME's. HelmetHeadersTest pins the same
 * header values on a redirect response and the JSON contract of /up.
 */
class StackIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_signed_in_dashboard_carries_the_security_headers()
    {
        // Sign in through SESAME's fake driver, then ask for the dashboard:
        // one request that proves SESAME let the user in and HELMET put the
        // baseline on the page that answered.
        $this->get('/login');

        $response = $this->get('/dashboard')->assertOk();

        $this->assertSecurityHeaders($response);
    }

    public function test_an_error_page_carries_the_security_headers()
    {
        // A route that does not exist answers 404 from the exception path,
        // not from a controller. The baseline must still be on the wire.
        $response = $this->get('/no-such-page')->assertNotFound();

        $this->assertSecurityHeaders($response);
    }

    public function test_the_health_check_answers_200_with_the_security_headers()
    {
        // /up is outside the web group on purpose, so a load balancer can ask
        // "are you alive?" cheaply. Cheap must not mean bare: the headers
        // come with it.
        $response = $this->get('/up')->assertOk();

        $this->assertSecurityHeaders($response);
    }

    /**
     * Assert HELMET's baseline is on the response: the CSP with its per
     * request nonce and strict-dynamic, nosniff, the referrer policy, and
     * frame protection both as X-Frame-Options and as the CSP
     * frame-ancestors directive.
     */
    private function assertSecurityHeaders(TestResponse $response): void
    {
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertIsString($csp);
        $this->assertNotEmpty($csp, 'The Content-Security-Policy header is missing.');
        $this->assertStringContainsString('nonce-', $csp);
        $this->assertStringContainsString('strict-dynamic', $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
