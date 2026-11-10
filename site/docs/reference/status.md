---
title: "Status | WELD docs"
description: "What is on the bench today: the state of every part welded into WELD."
url: https://weld.apps.wayne.edu/docs/reference/status.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Status [#](https://weld.apps.wayne.edu/docs/reference/status.html#status)

This page records what is on the bench today: the state of every part welded into WELD, plus the gaps still open for the backlog.

Every part of the stack is welded in: HELMET and SESAME are in `require`; CREED, Pinstripe, PROCTOR and ADVISOR are in `require-dev`; WayneUI 2.0.8 comes from npm; CI pins Preflight's components at 0.6.0; the deploy layer runs Squire's kit 0.10.7. Pinstripe's PHP presets are installed; its npm half is [the next step](https://weld.apps.wayne.edu/docs/reference/status.html#pinstripe-s-npm-half). For what each part does in WELD, see [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html).

## What is on the bench today [#](https://weld.apps.wayne.edu/docs/reference/status.html#what-is-on-the-bench-today)

| Item | Value |
| --- | --- |
| `composer.json` | `waynestate/weld`, requires PHP `^8.3`, and the lock file resolves for 8.3.0 |
| Framework | Laravel 13 with Inertia 3 and Vue 3 |
| `.env.example` | sets `APP_NAME=WELD`, `LOG_CHANNEL=stderr` and `SESSION_DRIVER=database` |
| Index length | `Schema::defaultStringLength(191)` keeps index keys under Citadel's MySQL 5.6 767-byte cap |
| `.gitattributes` | sets `* text=auto eol=lf` |
| CI contract | `composer ci` runs validate, tests, Pint, PHPStan level 8, the agent-ready check, the practices sync check, the docs check and audit, the same command on GitLab and GitHub |
| CI jobs | on GitLab, Preflight 0.6.0 adds the PHP 8.3, 8.4 and 8.5 test matrix, the audit, PROCTOR, the accessibility gate over `site/` and over the app's own pages, an SBOM, secret detection, the MySQL 5.6 migrate job (`php artisan migrate` against a real 5.6.51) and the portability job, which builds the portable image and boots it the way OpenShift would; the `frontend` job runs ESLint and Prettier |
| WayneUI | an npm dependency at 2.0.8; it draws every page of the app and the site |
| HELMET | runs its security headers and CSP middleware in the global chain, so every response carries the baseline; the Vite build takes the per-request nonce; trusted proxies come from `TRUSTED_PROXIES` (`bootstrap/app.php`); `/up` stays Laravel's built-in liveness route and `/ready` is HELMET's readiness route, checking the database, the cache and the scheduler heartbeat |
| SESAME | owns sign-in at `/login` and `/logout`; the fake driver stands in for the identity provider locally, and CAS is an env change away |
| PROCTOR | measures WELD against CREED's `policy.json` as a CI job (`composer proctor`): warnings pass, failures block |
| ADVISOR | in `require-dev`; `php vendor/bin/advisor install --client=claude --write` points a coding agent at the stack's read-only MCP server |
| Squire | the deploy kit in `deploy/` at 0.10.7: the Citadel image, an idempotent release script and one portable image for other hosts |
| Pinstripe | `waynestate/pinstripe` `^0.3` in `require-dev`. `phpstan.neon` includes its `phpstan/laravel.neon` and adds only WELD's paths, so the level, the ignore rules and the banned calls (`eval()`, `dd()`, TLS checks turned off, SQL joined from strings) come from the package; WELD's own code passes them with no new ignore and no baseline. `pint.json` is Pinstripe's `pint/laravel.json` plus two recorded excludes (Squire's `deploy/` kit and `bin/docs-site.php`), and `tests/Unit/PinstripeWiringTest.php` fails on any other drift. `composer-ci` saves Pint's findings as a Code Quality report (`composer lint:gitlab`). ESLint and Prettier still run on WELD's own config: see [Pinstripe's npm half](https://weld.apps.wayne.edu/docs/reference/status.html#pinstripe-s-npm-half) |
| Password auth | the starter kit's password auth (register, login, password reset, two-factor) is removed on purpose; the door is SESAME's |
| License | MIT |

## Gaps for the backlog [#](https://weld.apps.wayne.edu/docs/reference/status.html#gaps-for-the-backlog)

These mirror the gaps at the end of [BEST_PRACTICES.md](https://git.wayne.edu/enterprise-apps/weld/-/blob/main/BEST_PRACTICES.md).

- Preflight's `portability` job builds the portable image and boots its `web` role in CI, on `main` and tags (manual on a merge request, blocking) (W10, W14 and W16 are `Checked`; W8, W13 and W17 gain a CI check). It leaves out the `HEALTHCHECK` command (W12), a session shared across replicas (W15, which needs a database service in the job) and migrations once per release (W24). Pinstripe (W32) is a dependency for the PHP side; the JS side waits on [its npm half](https://weld.apps.wayne.edu/docs/reference/status.html#pinstripe-s-npm-half).
- The deploy-time scheduler work (W23) is still a plan.
- `tests/Feature/HealthHostTest.php` sends a forged `Host` and an IP:port `Host` to `/up` and expects 200, no cookie and no redirect, so W11 is `Checked` in full. The other items that once sat on an "Unverified" list are pinned: the CSRF check (`tests/Feature/CsrfTest.php`), a random UID (W13), Pest 4 on the PHP 8.3 floor, and `/ready` without `spatie/laravel-health`.
- Decision needed: queue mode on Citadel (W19). The scheduled-worker pattern is a stopgap.
- Decided: `/ready` is HELMET's `Route::helmetReady()` with app checks as invokable classes: the database and the cache (W11) and the scheduler heartbeat (W23).
- Decision needed: whether WELD keeps Fortify for a non-SSO mode.
- Decision needed: SSR on by default or off.

## Pinstripe's npm half [#](https://weld.apps.wayne.edu/docs/reference/status.html#pinstripe-s-npm-half)

WELD takes Pinstripe's PHP side from Composer today. The JS side, `@waynestate/pinstripe` (ESLint and Prettier presets for Vue), waits for 0.3.0 on npm, because npm still has 0.1.4. The presets inside have not changed since 0.1.4, but the move is a real refit, not a version bump: Pinstripe's presets need ESLint 10 (WELD runs 9), print at 100 columns with 2-space indents where WELD's `.prettierrc` prints at 150 with 4, and add eslint-plugin-vuejs-accessibility, so every Vue file is reformatted once and the accessibility rules may report findings. Once 0.3.0 is on npm:

1. Install it with ESLint 10: `npm install --save-dev @waynestate/pinstripe@^0.3 eslint@^10 prettier`, and drop the packages the preset now brings (`@eslint/js`, `@vue/eslint-config-typescript`, `eslint-config-prettier`, `eslint-plugin-vue`, `typescript-eslint`).
2. In `eslint.config.js`, spread `@waynestate/pinstripe/eslint/typescript` first and keep WELD's own blocks after it: the WayneUI-only import ban (W2, checked by `tests/Feature/WayneUiOnlyTest.php`) and the ignore for Squire's `site/assets/docs.js`. If the accessibility rules report more than one change should fix, take the `typescript-warn` twin for that cut, then switch back.
3. Point Prettier at the preset (`"prettier": "@waynestate/pinstripe/prettier"` in `package.json`) and delete `.prettierrc`. If `prettier-plugin-organize-imports` stays, that is a local override: record why.
4. Run `npm run format` as a commit of its own, with nothing else in it, and list its hash in `.git-blame-ignore-revs` so `git blame` skips the reformat.
5. `npm run lint`, `npm run format:check`, `npm run typecheck` and `npm run build` pass, the a11y gates stay green, and W32 says the JS side comes from the package too.

One tailor, two fittings: the trousers are done, the jacket waits for the cloth to arrive.

## Related [#](https://weld.apps.wayne.edu/docs/reference/status.html#related)

- [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
- [Changelog](https://weld.apps.wayne.edu/docs/changelog.html)
