<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HELMET runs a strict CSP (nonce plus strict-dynamic), so a real browser
 * executes no script that does not carry the per-request nonce. Ziggy's
 * route list used to ride in an inline <script> from @routes, which had no
 * nonce and was blocked; it now ships inside the Vite bundle. This test
 * pins the contract: every executable script tag on the app page carries a
 * nonce. Inertia 3 hands the page to Vue as a data island, a
 * <script data-page="app" type="application/json"> element the browser
 * parses but never executes (it is not a JavaScript MIME type), so it needs
 * no nonce; its presence is asserted at the end of the test.
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
            // The Inertia 3 data island is data, not code: the browser never
            // executes a script whose type is not a JavaScript MIME type, so
            // it sits outside the nonce contract.
            if (str_contains($tag, 'type="application/json"')) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/\bnonce="/',
                $tag,
                "Script tag lacks a nonce: {$tag}",
            );
        }

        // Inertia 3 moved the page out of the root div's data-page attribute
        // into this JSON element; pin the shape so a regression to the
        // Inertia 2 layout fails here, not as a blank browser.
        $this->assertStringContainsString(
            '<script data-page="app" type="application/json">',
            $html,
            'The Inertia 3 page data island is missing from the app page.',
        );
    }
}
