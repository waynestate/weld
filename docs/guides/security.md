---
title: Security
description: Security in WELD: sign-in through SESAME, the security headers and CSP from HELMET, and the standards checks from PROCTOR.
---

# Security

This page states WELD's security practices, one per heading, each with the rule and the tag that shows whether the stack holds it today.

## W25: Keep the framework above the published security floor

**Practice:** Require `laravel/framework` at or above the latest patched release, run `composer audit` in CI, and let CREED's `policy.json` name the minimum patch level. A critical TokenGuard authentication bypass was published on 2026-10-19 (fixed in 13.29.0 and 12.68.0).

**Stack:** `Checked`. `composer.json` pins `laravel/framework: ^13.29` (at or above the patched release), `composer.json` runs `@composer audit` inside the `ci` script, and `.gitlab-ci.yml` includes the Preflight `composer-audit` component, so the audit runs in CI. CREED's `policy.json` is a dependency and PROCTOR measures against it, now also inside the test suite (`tests/Feature/ProctorCheckTest.php`).

## W26: Debug is off outside local

> [!WARNING]
> `APP_DEBUG=false` in production. Debug pages expose configuration values, and the debug page has had an XSS advisory (Low, 2026-09-10).

**Practice:** `APP_DEBUG=false` in production. The debug page has had an XSS advisory (Low, 2026-09-10).

**Stack:** `Not yet`. `.env.example` sets `APP_DEBUG=true` for the local default, and there is no boot assertion that fails when `APP_ENV=production` and `APP_DEBUG=true`. Target `Enforced`: a boot assertion fails when `APP_ENV=production` and `APP_DEBUG=true`.

## W27: CSRF uses the PreventRequestForgery middleware

**Practice:** Laravel 13 formalized request-forgery protection as `PreventRequestForgery` (origin-aware, still token-compatible). Any custom middleware stack or HELMET code must reference the new name.

**Stack:** `Documented`. `bootstrap/app.php` appends to the default `web` middleware group, which carries `PreventRequestForgery` in Laravel 13, so the protection is in place by default. There is no feature test that posts without a token and expects 419. Target `Checked`: a feature test posts without a token and expects 419.

## W28: CSP works with Inertia 3 and Vite

**Practice:** HELMET sets the CSP header and calls `Vite::useCspNonce()`; Laravel then adds the nonce to the tags `@vite` emits. Inertia 3 puts initial page data in a JSON script element. Run the policy in report-only first.

**Stack:** `Documented`. HELMET `^1.0` is a dependency and its security headers and CSP middleware run in the global chain. Target `Checked`: a browser test under an enforced CSP.

## W29: Agent tooling stays out of production

> [!WARNING]
> A dev tool with write and query abilities is attack surface. Its MCP server can write rules to disk and run database queries, and its docs tool sends queries to a hosted service. Keep it out of production: build production images with `composer install --no-dev`.

**Practice:** Laravel Boost is `require-dev`. Its MCP server can write rules to disk and run database queries; its docs tool sends queries to a hosted service. Production images are built with `composer install --no-dev`; guard any `post-update-cmd` that calls `boost:update`.

**Stack:** `Checked`. Boost is in `require-dev` only and `composer.lock` lists it under `packages-dev`; `tests/Feature/BoostDevOnlyTest.php` "keeps Boost in require-dev only" and "locks Boost as a dev package, so composer install --no-dev leaves it out" fail the suite otherwise. The `no-dev-install` CI job (GitLab and GitHub) installs with `composer install --no-dev`, as Squire's `release.sh` does, fails when `vendor/laravel/boost` exists, and boots `php artisan`. The `post-update-cmd` hook calls `composer boost-update`, which runs `boost:update` only when `vendor/laravel/boost` is installed and `boost.json` names an agent ("skips the refresh where Boost is not set up", "runs boost:update and keeps its exit code where Boost is set up"). Open: the same check inside the built image, once Preflight portability is wired into WELD.

## Related

- [Build and supply chain](build-and-supply-chain.md)
- [Configuration](../reference/configuration.md)
- [Running WELD off Citadel](../running-off-citadel.md)
