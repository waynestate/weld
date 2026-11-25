---
title: Build and supply chain
description: Building and shipping WELD: composer ci as the gate, the audit, and the release path through Squire.
---

# Build and supply chain

This page states WELD's build and supply chain practices, one per heading, each with the rule and the tag that shows whether the stack holds it today.

## W30: Support only PHP versions that are not end of life

**Practice:** `composer.json` requires `php: ^8.3` with `config.platform.php` `8.3.0`. Tests run on 8.3, 8.4 and 8.5. Laravel 13 needs 8.3 or newer.

**Stack:** `Checked`. `composer.json:18` requires `php: ^8.3`, `composer.json:134` sets `config.platform.php` to `8.3.0`, and the CI matrix runs on 8.3, 8.4 and 8.5 (`.gitlab-ci.yml:9` and `.github/workflows/ci.yml:18`).

## W31: Keep dev tools inside the PHP floor

**Practice:** Pest 5 requires PHP `^8.4`. On a `^8.3` floor use Pest 4 or PHPUnit and cap the constraint.

**Stack:** `Checked`. WELD adopted Pest 4 on 2026-11-03: `composer.json` requires `pestphp/pest ^4` (Pest 5 needs PHP `^8.4`), and the CI matrix starts at 8.3 (`.gitlab-ci.yml:9`), so `composer install` runs on 8.3 and fails if a dev tool requires 8.4 or newer. The PHPUnit class tests still run unchanged, under Pest, and new tests are written in Pest.

## W32: Pinstripe owns style and static analysis

**Practice:** Use Pinstripe's Pint preset, PHPStan level 8 through Larastan, ESLint (flat config) and Prettier. No local overrides without a recorded reason.

**Stack:** `Checked`, for both sides. `waynestate/pinstripe` `^0.4` is a dev dependency (`composer.json:43`), and `phpstan.neon:10` includes its `phpstan/laravel.neon`, so level 8, the ignore rules and the banned calls (SQL joined from strings among them) come from the package; WELD's code passes with no new ignore and no baseline. `composer.json:60` runs `pint --test`, `composer.json:63` runs `phpstan analyse`, and both are in the `ci` script (`composer.json:68-77`), which CI runs. `pint.json` is Pinstripe's `pint/laravel.json` plus two recorded excludes, Squire's `deploy/` kit and `bin/docs-site.php`, since Pint cannot extend a config file; `tests/Unit/PinstripeWiringTest.php` fails on any other drift in either file. The JS side comes from Pinstripe too, from npm: `@waynestate/pinstripe` `^0.4` is a dev dependency (`package.json:16`), `eslint.config.js:26` spreads its `eslint/typescript` preset (Vue, accessibility and typed TypeScript rules, every Vue and accessibility warning an error) before WELD's own WayneUI-only import ban (`eslint.config.js:67`, W2), and `package.json:38` points Prettier at its `prettier` preset, so there is no `.prettierrc`. WELD turns no ESLint rule off. Its one recorded override keeps the Blade layout out of Prettier (`.prettierignore:10`), because the preset's Blade plugin would respace a directive that `tests/Feature/WayneUiOnlyTest.php` pins. The `frontend` job runs `npm run lint` and `npm run format:check` on GitLab (`.gitlab-ci.yml:203-204`) and GitHub (`.github/workflows/ci.yml:48-49`).

## W33: Pin shared CI components to a commit

**Practice:** Preflight components are referenced by commit SHA or a fixed tag, not a moving branch.

**Stack:** `Checked`. `.gitlab-ci.yml` pins every Preflight component to the 0.13.0 release tag (`@0.13.0` at `.gitlab-ci.yml:27`, `:36`, `:39`, `:47`, `:57`, `:68`, `:72`, `:79`, `:103`; published tags in the stack never move), and `.github/workflows/ci.yml:16` calls Preflight 0.13.0's reusable workflow at its release commit (`80ff4e366d6d7af1b95c29c7d4303b4cb90a863c`). PROCTOR's `preflight-pinned` check, run by the `proctor-root` job (`composer proctor`), warns on an include without a full release tag or a commit SHA.

## Related

- [Security](security.md)
- [Configuration](../reference/configuration.md)
- [Running WELD off Citadel](../running-off-citadel.md)
