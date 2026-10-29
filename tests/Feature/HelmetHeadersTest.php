<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * HELMET appends its SecurityHeaders middleware to the web group, so every
 * web response carries the baseline headers. The values asserted here are
 * the contract from HELMET's README, "The headers" section.
 */
class HelmetHeadersTest extends TestCase
{
    public function test_web_responses_carry_the_baseline_security_headers()
    {
        // A guest hitting /dashboard is redirected to SESAME's /login, but
        // the response still travels the full web stack, headers included.
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=(), payment=(), usb=()');
    }

    public function test_the_content_security_policy_carries_a_nonce_and_strict_dynamic()
    {
        $csp = $this->get('/dashboard')->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString('nonce-', $csp);
        $this->assertStringContainsString('strict-dynamic', $csp);
    }

    public function test_hsts_is_not_sent_for_requests_that_did_not_arrive_over_https()
    {
        $this->get('/dashboard')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_the_health_check_answers_ok_on_its_own()
    {
        // /up is Laravel's built-in health route, registered outside the web
        // group on purpose: a load balancer must be able to ask "are you
        // alive?" without paying for the full stack. Ask for JSON, because
        // the JSON contract is the stable one.
        $response = $this->getJson('/up');

        $response->assertOk();
        $response->assertJson(['status' => 'up']);
    }
}
