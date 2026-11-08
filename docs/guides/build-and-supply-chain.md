---
title: Build and supply chain
description: Building and shipping WELD: composer ci as the gate, the audit, and the release path through Squire.
---

# Build and supply chain

This page states WELD's build and supply chain practices, one per heading, each with the rule and the tag that shows whether the stack holds it today.

## W30: Support only PHP versions that are not end of life

**Practice:** `composer.json` requires `php: ^8.3` with `config.platform.php` `8.3.0`. Tests run on 8.3, 8.4 and 8.5. Laravel 13 needs 8.3 or newer.

**Stack:** `Checked`. `composer.json` requires `php: ^8.3`, `composer.json` sets `config.platform.php` to `8.3.0`, and the CI matrix runs on 8.3, 8.4 and 8.5 (`.gitlab-ci.yml` and `.github/workflows/ci.yml`).

## W31: Keep dev tools inside the PHP floor

**Practice:** Pest 5 requires PHP `^8.4`. On a `^8.3` floor use Pest 4 or PHPUnit and cap the constraint.

**Stack:** `Checked`. WELD runs Pest 4: `composer.json` requires `pestphp/pest ^4` (Pest 5 needs PHP `^8.4`), and the CI matrix starts at 8.3 (`.gitlab-ci.yml`), so `composer install` runs on 8.3 and would fail if a dev tool required 8.4 or newer. The PHPUnit class tests still run, under Pest.

## W32: Pinstripe owns style and static analysis

**Practice:** Use Pinstripe's Pint preset, PHPStan level 8 through Larastan, ESLint (flat config) and Prettier. No local overrides without a recorded reason.

**Stack:** `Checked`, for the PHP side. `composer.json` runs `pint --test`, `composer.json` runs `phpstan analyse`, and both are in the `ci` script (`composer.json`), which CI runs; `pint.json` uses the `laravel` preset and `phpstan.neon` sets `level: 8`. The JS side runs in CI too: the `frontend` job (`.gitlab-ci.yml`, `.github/workflows/ci.yml`) runs `npm run lint` (`eslint.config.js`, flat config) and `npm run format:check` (`.prettierrc`). Pinstripe is not a dependency yet, so the presets are copied, not included.

## W33: Pin shared CI components to a commit

**Practice:** Preflight components are referenced by commit SHA or a fixed tag, not a moving branch.

**Stack:** `Checked`. `.gitlab-ci.yml` includes every Preflight component at the full release tag 0.3.1 (published tags never move) and `.github/workflows/ci.yml` pins the reusable workflow to a commit SHA (0.1.4, the tag in a trailing comment); the comment "Pinned to Preflight 0.3.1. Never use ~latest." sits at the top of `.gitlab-ci.yml`. PROCTOR's `preflight-pinned` check, run by the `proctor-root` job, warns on an include without a full release tag or a commit SHA.

## Related

- [Security](security.md)
- [Configuration](../reference/configuration.md)
- [Running WELD off Citadel](../running-off-citadel.md)
