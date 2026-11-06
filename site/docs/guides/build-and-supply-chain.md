---
title: "Build and supply chain | WELD docs"
description: "Building and shipping WELD: composer ci as the gate, the audit, and the release path through Squire."
url: https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Build and supply chain[#](https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html#build-and-supply-chain)

This page states WELD's build and supply chain practices, one per heading, each with the rule and the tag that shows whether the stack holds it today.

## W30: Support only PHP versions that are not end of life[#](https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html#w30-support-only-php-versions-that-are-not-end-of-life)

**Practice:** `composer.json` requires `php: ^8.3` with `config.platform.php` `8.3.0`. Tests run on 8.3, 8.4 and 8.5. Laravel 13 needs 8.3 or newer.

**Stack:** `Checked`. `composer.json` requires `php: ^8.3`, `composer.json` sets `config.platform.php` to `8.3.0`, and the CI matrix runs on 8.3, 8.4 and 8.5 (`.gitlab-ci.yml` and `.github/workflows/ci.yml`).

## W31: Keep dev tools inside the PHP floor[#](https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html#w31-keep-dev-tools-inside-the-php-floor)

**Practice:** Pest 5 requires PHP `^8.4`. On a `^8.3` floor use Pest 4 or PHPUnit and cap the constraint.

**Stack:** `Checked`. WELD uses PHPUnit, not Pest: `composer.json` pins `phpunit/phpunit: ^12.0`, and the CI matrix starts at 8.3 (`.gitlab-ci.yml`), so `composer install` runs on 8.3 and would fail if a dev tool required 8.4 or newer.

## W32: Pinstripe owns style and static analysis[#](https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html#w32-pinstripe-owns-style-and-static-analysis)

**Practice:** Use Pinstripe's Pint preset, PHPStan level 8 through Larastan, ESLint (flat config) and Prettier. No local overrides without a recorded reason.

**Stack:** `Checked`, for the PHP side. `composer.json` runs `pint --test`, `composer.json` runs `phpstan analyse`, and both are in the `ci` script (`composer.json`), which CI runs; `pint.json` uses the `laravel` preset and `phpstan.neon` sets `level: 8`. The JS side runs in CI too: the `frontend` job (`.gitlab-ci.yml`, `.github/workflows/ci.yml`) runs `npm run lint` (`eslint.config.js`, flat config) and `npm run format:check` (`.prettierrc`). Pinstripe is not a dependency yet, so the presets are copied, not included.

## W33: Pin shared CI components to a commit[#](https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html#w33-pin-shared-ci-components-to-a-commit)

**Practice:** Preflight components are referenced by commit SHA or a fixed tag, not a moving branch.

**Stack:** `Documented`. `.gitlab-ci.yml` pins the `php-test` component to `@0.1.0` and `.github/workflows/ci.yml` pins the reusable workflow to a commit SHA; the comment "Pinned to Preflight 0.1.0. Never use ~latest." sits at `.gitlab-ci.yml`. There is no check that flags an unpinned include. Target `Checked`: PROCTOR flags `include:` entries without a SHA.

## Related[#](https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html#related)

- [Security](https://weld.apps.wayne.edu/docs/guides/security.html)
- [Configuration](https://weld.apps.wayne.edu/docs/reference/configuration.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
