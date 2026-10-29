<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HELMET runs a strict CSP (nonce plus strict-dynamic), so a real browser
 * executes no script that does not carry the per-request nonce. Ziggy's
 * route list used to ride in an inline <script> from @routes, which had no
 * nonce and was blocked; it now ships inside the Vite bundle. This test
 * pins the contract: every script tag on the app page carries a nonce.
 */
class CspScriptTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_script_tag_on_the_app_page_carries_a_nonce()
    {
        // Sign in through SESAME's fake driver so /dashboard renders the
        // full Inertia page instead of bouncing to /login.
        $this->get('/login');

        $html = $this->get('/dashboard')->assertOk()->getContent();

        preg_match_all('/<script\b[^>]*>/i', $html, $matches);

        // The Vite bundle must still be on the page: a page with zero
        // scripts would sail through the nonce loop below while shipping
        // nothing at all.
        $this->assertNotEmpty($matches[0], 'Expected at least one script tag on the app page.');

        foreach ($matches[0] as $tag) {
            $this->assertMatchesRegularExpression(
                '/\bnonce="/',
                $tag,
                "Script tag lacks a nonce: {$tag}",
            );
        }
    }
}
