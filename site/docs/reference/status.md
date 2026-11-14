---
title: "Status | WELD docs"
description: "What is on the bench today: the state of every part welded into WELD."
url: https://weld.apps.wayne.edu/docs/reference/status.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Status [#](https://weld.apps.wayne.edu/docs/reference/status.html#status)

This page records what is on the bench today: the state of every part welded into WELD, plus the gaps still open for the backlog.

Every part of the stack is welded in: HELMET and SESAME are in `require`; CREED, Pinstripe, PROCTOR and ADVISOR are in `require-dev`; WayneUI 2.0.8 comes from npm; CI pins Preflight's components at 0.9.0; the deploy layer runs Squire's kit 0.11.1. Pinstripe's PHP presets are installed; its npm half is [the next step](https://weld.apps.wayne.edu/docs/reference/status.html#pinstripe-s-npm-half). For what each part does in WELD, see [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html).
Every part of the stack is welded in: HELMET and SESAME are in `require`; CREED, Pinstripe, PROCTOR and ADVISOR are in `require-dev`; WayneUI 2.0.8 comes from npm; CI pins Preflight's components at 0.9.0; the deploy layer runs Squire's kit 0.10.8. Pinstripe's PHP presets and [its npm half](https://weld.apps.wayne.edu/docs/reference/status.html#pinstripe-s-npm-half) are both installed. For what each part does in WELD, see [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html).

## What is on the bench today [#](https://weld.apps.wayne.edu/docs/reference/status.html#what-is-on-the-bench-today)

| Item | Value |
| --- | --- |
| `composer.json` | `waynestate/weld`, requires PHP `^8.3`, and the lock file resolves for 8.3.0 |
| Framework | Laravel 13 with Inertia 3 and Vue 3 |
| `.env.example` | sets `APP_NAME=WELD`, `LOG_CHANNEL=stderr` and `SESSION_DRIVER=database` |
| Index length | `Schema::defaultStringLength(191)` keeps index keys under Citadel's MySQL 5.6 767-byte cap |
| `.gitattributes` | sets `* text=auto eol=lf` |
| CI contract | `composer ci` runs validate, tests, Pint, PHPStan level 8, the agent-ready check, the practices sync check, the docs check and audit, the same command on GitLab and GitHub |
| CI jobs | on GitLab, Preflight 0.9.0 adds the PHP 8.3, 8.4 and 8.5 test matrix, the audit, PROCTOR, the accessibility gate over `site/` and over the app's own pages, an SBOM, secret detection, the MySQL 5.6 migrate job (`php artisan migrate` against a real 5.6.51) and the portability job, which builds the portable image and boots it the way OpenShift would; the `frontend` job runs ESLint and Prettier |
| WayneUI | an npm dependency at 2.0.8; it draws every page of the app and the site |
| HELMET | runs its security headers and CSP middleware in the global chain, so every response carries the baseline; the Vite build takes the per-request nonce; trusted proxies come from `TRUSTED_PROXIES` (`bootstrap/app.php`); `/up` stays Laravel's built-in liveness route and `/ready` is HELMET's readiness route, checking the database, the cache and the scheduler heartbeat |
| SESAME | owns sign-in at `/login` and `/logout`; the fake driver stands in for the identity provider locally, and CAS is an env change away |
| PROCTOR | measures WELD against CREED's `policy.json` as a CI job (`composer proctor`): warnings pass, failures block |
| ADVISOR | in `require-dev`; `php vendor/bin/advisor install --client=claude --write` points a coding agent at the stack's read-only MCP server |
| Squire | the deploy kit in `deploy/` at 0.11.1: the Citadel image, an idempotent release script and one portable image for other hosts |
| Pinstripe | `waynestate/pinstripe` `^0.4` in `require-dev`. `phpstan.neon` includes its `phpstan/laravel.neon` and adds only WELD's paths, so the level, the ignore rules and the banned calls (`eval()`, `dd()`, TLS checks turned off, SQL joined from strings) come from the package; WELD's own code passes them with no new ignore and no baseline. `pint.json` is Pinstripe's `pint/laravel.json` plus two recorded excludes (Squire's `deploy/` kit and `bin/docs-site.php`), and `tests/Unit/PinstripeWiringTest.php` fails on any other drift. `composer-ci` saves Pint's findings as a Code Quality report (`composer lint:gitlab`). ESLint and Prettier still run on WELD's own config: see [Pinstripe's npm half](https://weld.apps.wayne.edu/docs/reference/status.html#pinstripe-s-npm-half) |
| Squire | the deploy kit in `deploy/` at 0.10.8: the Citadel image, an idempotent release script and one portable image for other hosts |
| Pinstripe | `waynestate/pinstripe` `^0.4` in `require-dev`. `phpstan.neon` includes its `phpstan/laravel.neon` and adds only WELD's paths, so the level, the ignore rules and the banned calls (`eval()`, `dd()`, TLS checks turned off, SQL joined from strings) come from the package; WELD's own code passes them with no new ignore and no baseline. `pint.json` is Pinstripe's `pint/laravel.json` plus two recorded excludes (Squire's `deploy/` kit and `bin/docs-site.php`), and `tests/Unit/PinstripeWiringTest.php` fails on any other drift. `composer-ci` saves Pint's findings as a Code Quality report (`composer lint:gitlab`). ESLint and Prettier run on `@waynestate/pinstripe` `^0.4` from npm: see [Pinstripe's npm half](https://weld.apps.wayne.edu/docs/reference/status.html#pinstripe-s-npm-half) |
| Password auth | the starter kit's password auth (register, login, password reset, two-factor) is removed on purpose; the door is SESAME's |
| License | MIT |

## Gaps for the backlog [#](https://weld.apps.wayne.edu/docs/reference/status.html#gaps-for-the-backlog)

These mirror the gaps at the end of [BEST_PRACTICES.md](https://git.wayne.edu/enterprise-apps/weld/-/blob/main/BEST_PRACTICES.md).

- Preflight's `portability` job builds the portable image and boots its `web` role in CI, on `main` and tags (manual on a merge request, blocking) (W10, W14 and W16 are `Checked`; W8, W13 and W17 gain a CI check), and its `healthcheck` check runs the image's own `HEALTHCHECK` (W12 is `Checked`), and its `session-hop` check signs in on one replica and reads the session on the other over a MySQL the job starts and migrates once (W15 is `Checked`). Pinstripe (W32) is a dependency for both sides, PHP from Composer and [JS from npm](https://weld.apps.wayne.edu/docs/reference/status.html#pinstripe-s-npm-half).
- The `mysql56-migrate` job runs the migrations against MySQL 5.6.51 on every pipeline, and migrations run once per release on both deploy paths: the release script on Citadel, the one-shot `migrate` role off it (W24, `Checked` by `tests/Feature/MigrateOncePerReleaseTest.php`).
- The scheduler heartbeat on `/ready` is built (W23, `Checked` for the heartbeat); its Prometheus gauge is not.
- `tests/Feature/HealthHostTest.php` sends a forged `Host` and an IP:port `Host` to `/up` and expects 200, no cookie and no redirect, so W11 is `Checked` in full. The other items that once sat on an "Unverified" list are pinned: the CSRF check (`tests/Feature/CsrfTest.php`), a random UID (W13), Pest 4 on the PHP 8.3 floor, and `/ready` without `spatie/laravel-health`.
- Decision needed: queue mode on Citadel (W19). The scheduled-worker pattern is a stopgap.
- Decided: `/ready` is HELMET's `Route::helmetReady()` with app checks as invokable classes: the database and the cache (W11) and the scheduler heartbeat (W23).
- Decision needed: whether WELD keeps Fortify for a non-SSO mode.
- Decision needed: SSR on by default or off.

## Signed-in pages and the a11y job [#](https://weld.apps.wayne.edu/docs/reference/status.html#signed-in-pages-and-the-a11y-job)

WELD's pipeline audits signed-in pages in two steps, copied from Sourdough's Inertia shape:

1. The `a11y-pages` job boots the app as its first run on a desk does (`.env` from `.env.example`, a generated key, an SQLite database, `php artisan serve`), signs in with SESAME's fake driver, and saves each path in `A11Y_PATHS` (WELD: `/`, `/dashboard`, `/settings/profile` and `/settings/appearance`) into `a11y-site/` with `public/`'s static files. One cookie jar carries the sign-in from one path to the next, and the server's origin is cut out of the saved pages (`.gitlab-ci.yml:296-344`).
2. The `a11y-app` include checks those saved pages: axe-core 4.14.0, light and dark, at 320 and 1280 px, plus WCAG 1.4.10 reflow, at WCAG 2.2 AA (`.gitlab-ci.yml:31-40`).

For a derived app: add each page you build to `A11Y_PATHS` and to the `a11y-app` include's `pages` together (`.gitlab-ci.yml:39`, `:301`). `tests/Feature/A11yPagesTest.php` holds you to it: it fails when the two lists differ, and it walks the list the way the job does (one session, redirects followed) and fails when an Inertia page under `resources/js/pages` is drawn by none of the paths. Until 2026-11-11 neither settings page was on the list. A fully gated app should keep one public page, the landing page, so the site audit has something to read.

## Pinstripe's npm half [#](https://weld.apps.wayne.edu/docs/reference/status.html#pinstripe-s-npm-half)

Both halves of Pinstripe are installed now. The PHP side comes from Composer, and the JS side from npm: `@waynestate/pinstripe` `^0.4` is a dev dependency in `package.json`, with ESLint 10 and Prettier beside it.

- **ESLint.** `eslint.config.js` spreads `@waynestate/pinstripe/eslint/typescript` first: ESLint's recommended rules, eslint-plugin-vue's `flat/recommended-error`, eslint-plugin-vuejs-accessibility, typescript-eslint's typed `recommendedTypeChecked` and eslint-config-prettier, with every Vue and accessibility warning raised to an error. The preset brings those packages, so `package.json` no longer lists `@eslint/js`, `@vue/eslint-config-typescript`, `eslint-config-prettier`, `eslint-plugin-vue` or `typescript-eslint`. WELD's own blocks follow it: the WayneUI-only import ban (W2, held by `tests/Feature/WayneUiOnlyTest.php`); the ignores for copies another repo owns (Squire's `site/assets/docs.js` and `site/assets/agent-ready.js`, Preflight's `tools/a11y/check.mjs`) and for the generated `resources/js/ziggy.*`; a default project for `vite.config.ts`, the one TypeScript file outside `tsconfig.json`; and Node's `process` and `Buffer` for the README art kit. WELD turns no rule off: its old two offs (one-word component names, `no-explicit-any`) are gone, and the settings layout names itself `SettingsLayout`.
- **Prettier.** `package.json` says `"prettier": "@waynestate/pinstripe/prettier"`, and `.prettierrc` is gone. Lines wrap at 100 columns (WELD printed at 150); `.editorconfig` keeps the 4-space indent, since the preset sets none. `prettier-plugin-organize-imports` went with `.prettierrc`: the preset does not sort imports, and WELD keeps no local override. The reformat is one commit with nothing else in it, listed in `.git-blame-ignore-revs`.
- **The one local override.** The preset formats Blade too, and `.prettierignore` keeps `resources/views/app.blade.php` out for now: Prettier's Blade plugin respaces `@if($cspNonce)` to `@if ($cspNonce)` in the theme script tag, which `tests/Feature/WayneUiOnlyTest.php` pins byte for byte. The layout and that test move together, in a change of their own.
- **What the accessibility rules found.** Three findings, all fixed in the templates, none with a disable comment. `UserMenu.vue`'s wrapper `<div>` listened for keydown; Escape is now a document listener, like the click outside that already closed the panel. Both nav lists put `aria-current` on `<Link>`, which the rules read as HTML's `<link>`; the layouts import Inertia's Link as `InertiaLink`, which renders the same `<a>`. Typed linting found seven more (two promises now marked `void`, three casts that changed nothing, a duplicate `| undefined`, the one-word layout name). The strict preset is the one in use, not its `-warn` twin.
- **Sourdough.** Its Inertia shape still ships `.prettierrc`, so the regen gate's baseline (`.sourdough/regen-baseline.json`) accepts that file and four others as drift until Sourdough takes the same presets.

`npm run lint`, `npm run format:check`, `npm run typecheck` and `npm run build` pass, in the `frontend` job and on a desk. One tailor, two fittings, both done: the jacket matches the trousers, down to the last pinstripe.

## Related [#](https://weld.apps.wayne.edu/docs/reference/status.html#related)

- [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
- [Changelog](https://weld.apps.wayne.edu/docs/changelog.html)
