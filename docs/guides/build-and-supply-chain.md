---
title: Build and supply chain
description: Building and shipping WELD: composer ci as the gate, the audit, and the release path through Squire.
---

# Build and supply chain

This page states WELD's build and supply chain practices, one per heading, each with the rule and the tag that shows whether the stack holds it today.

## W30: Support only PHP versions that are not end of life

**Practice:** `composer.json` requires `php: ^8.3` with `config.platform.php` `8.3.0`. Tests run on 8.3, 8.4 and 8.5. Laravel 13 needs 8.3 or newer.

**Stack:** `Checked`. `composer.json:17` requires `php: ^8.3`, `composer.json:110` sets `config.platform.php` to `8.3.0`, and the CI matrix runs on 8.3, 8.4 and 8.5 (`.gitlab-ci.yml:8` and `.github/workflows/ci.yml:18`).

## W31: Keep dev tools inside the PHP floor

**Practice:** Pest 5 requires PHP `^8.4`. On a `^8.3` floor use Pest 4 or PHPUnit and cap the constraint.

**Stack:** `Checked`. WELD uses PHPUnit, not Pest: `composer.json:35` pins `phpunit/phpunit: ^12.0`, and the CI matrix starts at 8.3 (`.gitlab-ci.yml:8`), so `composer install` runs on 8.3 and would fail if a dev tool required 8.4 or newer.

## W32: Pinstripe owns style and static analysis

**Practice:** Use Pinstripe's Pint preset, PHPStan level 8 through Larastan, ESLint (flat config) and Prettier. No local overrides without a recorded reason.

**Stack:** `Checked`, for the PHP side. `composer.json:53` runs `pint --test`, `composer.json:55` runs `phpstan analyse`, and both are in the `ci` script (`composer.json:58-65`), which CI runs; `pint.json:2` uses the `laravel` preset and `phpstan.neon:8` sets `level: 8`. The JS side is configured but not yet in the CI pipeline: `eslint.config.js` (flat config) and `.prettierrc` exist, but no CI job runs `npm run lint` or `npm run format:check`. Pinstripe is not a dependency yet, so the presets are copied, not included.

## W33: Pin shared CI components to a commit

**Practice:** Preflight components are referenced by commit SHA or a fixed tag, not a moving branch.

**Stack:** `Documented`. `.gitlab-ci.yml:6` pins the `php-test` component to `@0.1.0` and `.github/workflows/ci.yml:16` pins the reusable workflow to a commit SHA; the comment "Pinned to Preflight 0.1.0. Never use ~latest." sits at `.gitlab-ci.yml:2`. There is no check that flags an unpinned include. Target `Checked`: PROCTOR flags `include:` entries without a SHA.

## Related

- [Security](security.md)
- [Configuration](../reference/configuration.md)
- [Running WELD off Citadel](../running-off-citadel.md)
