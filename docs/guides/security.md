---
title: Security
description: Security in WELD: sign-in through SESAME, the security headers and CSP from HELMET, and the standards checks from PROCTOR.
---

# Security

This page states WELD's security practices, one per heading, each with the rule and the tag that shows whether the stack holds it today.

## W25: Keep the framework above the published security floor

**Practice:** Require `laravel/framework` at or above the latest patched release, run `composer audit` in CI, and let CREED's `policy.json` name the minimum patch level. A critical TokenGuard authentication bypass was published on 2026-10-19 (fixed in 13.29.0 and 12.68.0).

**Stack:** `Checked`. `composer.json:20` pins `laravel/framework: ^13.29` (at or above the patched release), `composer.json:74` runs `@composer audit` inside the `ci` script, and `.gitlab-ci.yml:14` includes the Preflight `composer-audit` component, so the audit runs in CI. CREED's `policy.json` is a dependency and PROCTOR measures against it, now also inside the test suite (`tests/Feature/ProctorCheckTest.php`).

## W26: Debug is off outside local

> [!WARNING]
> `APP_DEBUG=false` in production. Debug pages expose configuration values, and the debug page has had an XSS advisory (Low, 2026-09-10).

**Practice:** `APP_DEBUG=false` in production. The debug page has had an XSS advisory (Low, 2026-09-10).

**Stack:** `Enforced`. `.env.example:4` sets `APP_DEBUG=true` for the local default, and `app/Support/ProductionDebug.php:23-30` refuses to boot when the environment is production and debug is on; `app/Providers/AppServiceProvider.php:29` calls it in `boot()`, and `tests/Feature/ProductionDebugTest.php:17-30` covers both the refusal and the pass. The release stops it earlier still: Squire 0.10.7's release script (`deploy/scripts/release.sh:271-289`) refuses a Laravel release with no `APP_KEY`, or with `APP_DEBUG` on in production, before anything installs, and `tests/Feature/ReleaseEnvGuardTest.php` fails when a kit update drops that guard.

## W27: CSRF uses the PreventRequestForgery middleware

**Practice:** Laravel 13 formalized request-forgery protection as `PreventRequestForgery` (origin-aware, still token-compatible). Any custom middleware stack or HELMET code must reference the new name.

**Stack:** `Checked`. `bootstrap/app.php:48-51` appends to the default `web` middleware group. `tests/Feature/CsrfTest.php:15-19` posts without a token and expects 419, `:21-29` lets the session token through, and `:31-37` lets a same-origin `Sec-Fetch-Site` request through.

## W28: CSP works with Inertia 3 and Vite

**Practice:** HELMET sets the CSP header and calls `Vite::useCspNonce()`; Laravel then adds the nonce to the tags `@vite` emits. Inertia 3 puts initial page data in a JSON script element. Run the policy in report-only first.

**Stack:** `Checked`. HELMET `^1.3` is a dependency (`composer.json:23`) and its security headers and CSP run in the global chain: HELMET's own config defaults the scope to `global` (`vendor/waynestate/helmet/config/helmet.php:331`; WELD's `config/helmet.php` overrides only `policy_path` and the package merges the rest), `resources/views/app.blade.php:8` reads the per-request nonce and `:32` hands it to `Vite::useCspNonce()`, and `tests/Feature/HelmetHeadersTest.php:14-34` plus `tests/Feature/CspScriptTagsTest.php:23-60` fail the suite on header, nonce or `strict-dynamic` regression. The policy is enforced, not report-only (`vendor/waynestate/helmet/config/helmet.php:352`). Open: a real browser test under the enforced CSP.

## The effective CSP

WELD overrides no CSP key: `config/helmet.php` sets only `policy_path` and `error_pages`, and nothing in `.env.example` or `deploy/` sets `HELMET_CSP`, so every response carries HELMET's default policy (`vendor/waynestate/helmet/config/helmet.php:344`, built from `SecurityHeaders::defaultCspPolicy()` at `vendor/waynestate/helmet/src/Headers/SecurityHeaders.php:52-76`). The `citadel` preset changes none of this: a preset is a named list of trusted proxies only (`vendor/waynestate/helmet/src/Platform/Presets.php:11,53-56`), and WELD ships it commented out for local runs (`.env.example:55`).

| Directive | Sources |
|---|---|
| `default-src` | `'self'` |
| `script-src` | `'self'`, `https://wayneui.apps.wayne.edu`, the per-request nonce, `strict-dynamic` |
| `style-src` | `'self'`, `https://wayneui.apps.wayne.edu`, the nonce |
| `style-src-attr` | `'unsafe-inline'` |
| `style-src-elem` | `'self'`, `https://wayneui.apps.wayne.edu`, the nonce |
| `img-src` | `'self'`, `data:`, `https://wayneui.apps.wayne.edu` |
| `connect-src` | `'self'`, `https://wayneui.apps.wayne.edu` |
| `font-src` | `'self'`, `data:`, `https://wayneui.apps.wayne.edu` |
| `frame-ancestors` | `'self'` |

The nonce binds to `script-src`, `style-src` and `style-src-elem` (`SecurityHeaders.php:138-147`); `strict-dynamic` lets a nonced script load further scripts without opening `script-src` to inline (`:149-151`). `base-uri 'self'`, `object-src 'none'` and `form-action 'self'` complete the policy (`:71-73`).

Four consequences for page authors:

- **`style=""` attributes are allowed.** `style-src-attr` carries `'unsafe-inline'`, deliberately: WayneUI's recipes set inline style attributes for block spacing that would be silently dropped without it (`SecurityHeaders.php:57-62`).
- **`<style>` elements are allowed only with the nonce.** `style-src-elem` does not carry `'unsafe-inline'` (`:42-44,64`), but Vite's tags carry the nonce because the Blade template calls `Vite::useCspNonce()` (`resources/views/app.blade.php:32`).
- **`connect-src` allows the app's own origin and the WayneUI CDN only** (`:70`). Browser fetches cannot reach any other host, which is why an AI app calls its model server-side ([Building an AI or LLM app](ai-apps.md)).
- **Inline `<svg>` markup is fine.** None of the directives above govern document markup; SVG in the HTML is neither a script nor a style.

## W29: Agent tooling stays out of production

> [!WARNING]
> A dev tool with write and query abilities is attack surface. Its MCP server can write rules to disk and run database queries, and its docs tool sends queries to a hosted service. Keep it out of production: build production images with `composer install --no-dev`.

**Practice:** Laravel Boost is `require-dev`. Its MCP server can write rules to disk and run database queries; its docs tool sends queries to a hosted service. Production images are built with `composer install --no-dev`; guard any `post-update-cmd` that calls `boost:update`.

**Stack:** `Checked`. Boost is in `require-dev` only and `composer.lock` lists it under `packages-dev`; `tests/Feature/BoostDevOnlyTest.php` "keeps Boost in require-dev only" and "locks Boost as a dev package, so composer install --no-dev leaves it out" fail the suite otherwise. The `no-dev-install` CI job (GitLab and GitHub) installs with `composer install --no-dev`, as Squire's `release.sh` does, fails when `vendor/laravel/boost` exists, and boots `php artisan`. The `post-update-cmd` hook calls `composer boost-update`, which runs `boost:update` only when `vendor/laravel/boost` is installed and `boost.json` names an agent ("skips the refresh where Boost is not set up", "runs boost:update and keeps its exit code where Boost is set up"). The portable image is held too: its build context leaves `vendor/` out (`portable.Dockerfile.dockerignore`) and its only Composer install is `scripts/release.sh`'s `--no-dev` one, so Boost cannot reach the image; `tests/Feature/PortableImageTest.php` "builds vendor/ without dev packages, so Boost never reaches the image (W29)" fails the suite if either of those facts drifts.

## Related

- [Build and supply chain](build-and-supply-chain.md)
- [Configuration](../reference/configuration.md)
- [Running WELD off Citadel](../running-off-citadel.md)
