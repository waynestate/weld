<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Orchestrators probe /up with an IP or an internal name as the Host header,
 * so a trusted-hosts rule or an HTTPS redirect that rejected them would make
 * every container look dead (BEST_PRACTICES W11). These tests send forged
 * hosts and hold the liveness answer open to all of them.
 */
class HealthHostTest extends TestCase
{
    public function test_up_answers_for_a_forged_host()
    {
        // The full URL stands in for a request whose Host header the app
        // never saw configured, the way an orchestrator's probe arrives.
        $response = $this->getJson('http://evil.example/up');

        $response->assertOk();
        $response->assertJson(['status' => 'up']);
    }

    public function test_up_answers_for_an_ip_and_port_host()
    {
        // Orchestrators often know the container by its IP and port only.
        $response = $this->getJson('http://10.1.2.3:8080/up');

        $response->assertOk();
        $response->assertJson(['status' => 'up']);
    }

    public function test_up_sets_no_session_cookie_for_a_forged_host()
    {
        // A liveness probe must not pay for a session: no Set-Cookie header
        // at all, even for a host the app does not recognize.
        $response = $this->getJson('http://evil.example/up');

        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_up_is_not_redirected_to_https()
    {
        // The probe arrives over plain HTTP and gets the answer there; a
        // redirect would read as "not up" to a checker that follows none.
        $response = $this->getJson('http://evil.example/up');

        $this->assertFalse($response->isRedirection());
    }
}
