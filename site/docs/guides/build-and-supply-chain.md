---
title: "Build and supply chain | WELD docs"
description: "Building and shipping WELD: composer ci as the gate, the audit, and the release path through Squire."
url: https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Build and supply chain [#](https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html#build-and-supply-chain)

This page states WELD's build and supply chain practices, one per heading, each with the rule and the tag that shows whether the stack holds it today.

## W30: Support only PHP versions that are not end of life [#](https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html#w30-support-only-php-versions-that-are-not-end-of-life)

**Practice:** `composer.json` requires `php: ^8.3` with `config.platform.php` `8.3.0`. Tests run on 8.3, 8.4 and 8.5. Laravel 13 needs 8.3 or newer.

**Stack:** `Checked`. `composer.json:18` requires `php: ^8.3`, `composer.json:134` sets `config.platform.php` to `8.3.0`, and the CI matrix runs on 8.3, 8.4 and 8.5 (`.gitlab-ci.yml:9` and `.github/workflows/ci.yml:18`).

## W31: Keep dev tools inside the PHP floor [#](https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html#w31-keep-dev-tools-inside-the-php-floor)

**Practice:** Pest 5 requires PHP `^8.4`. On a `^8.3` floor use Pest 4 or PHPUnit and cap the constraint.

**Stack:** `Checked`. WELD adopted Pest 4 on 2026-11-03: `composer.json` requires `pestphp/pest ^4` (Pest 5 needs PHP `^8.4`), and the CI matrix starts at 8.3 (`.gitlab-ci.yml:9`), so `composer install` runs on 8.3 and fails if a dev tool requires 8.4 or newer. The PHPUnit class tests still run unchanged, under Pest, and new tests are written in Pest.

## W32: Pinstripe owns style and static analysis [#](https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html#w32-pinstripe-owns-style-and-static-analysis)

**Practice:** Use Pinstripe's Pint preset, PHPStan level 8 through Larastan, ESLint (flat config) and Prettier. No local overrides without a recorded reason.

**Stack:** `Checked`, for the PHP side. `waynestate/pinstripe` `^0.3` is a dev dependency (`composer.json:43`), and `phpstan.neon:10` includes its `phpstan/laravel.neon`, so level 8, the ignore rules and the banned calls (SQL joined from strings among them) come from the package; WELD's code passes with no new ignore and no baseline. `composer.json:60` runs `pint --test`, `composer.json:63` runs `phpstan analyse`, and both are in the `ci` script (`composer.json:68-77`), which CI runs. `pint.json` is Pinstripe's `pint/laravel.json` plus two recorded excludes, Squire's `deploy/` kit and `bin/docs-site.php`, since Pint cannot extend a config file; `tests/Unit/PinstripeWiringTest.php` fails on any other drift in either file. The JS side runs in CI too, on WELD's own config until `@waynestate/pinstripe` 0.3.0 is on npm: the `frontend` job (`.gitlab-ci.yml`, `.github/workflows/ci.yml`) runs `npm run lint` (`eslint.config.js`, flat config) and `npm run format:check` (`.prettierrc`).

## W33: Pin shared CI components to a commit [#](https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html#w33-pin-shared-ci-components-to-a-commit)

**Practice:** Preflight components are referenced by commit SHA or a fixed tag, not a moving branch.

**Stack:** `Checked`. `.gitlab-ci.yml` pins every Preflight component to the 0.8.0 release tag (`@0.8.0` at `.gitlab-ci.yml:7`, `:15`, `:18`, `:26`, `:35`, `:45`, `:49`, `:56`, `:80`; published tags in the stack never move), and `.github/workflows/ci.yml:16` calls Preflight 0.8.0's reusable workflow at its release commit (`28d9c5994d06c633850d431dcf6ca56c48a96055`). PROCTOR's `preflight-pinned` check, run by the `proctor-root` job (`composer proctor`), warns on an include without a full release tag or a commit SHA.

## Related [#](https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html#related)

- [Security](https://weld.apps.wayne.edu/docs/guides/security.html)
- [Configuration](https://weld.apps.wayne.edu/docs/reference/configuration.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
