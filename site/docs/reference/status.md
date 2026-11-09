---
title: "Status | WELD docs"
description: "What is on the bench today: the state of every part welded into WELD."
url: https://weld.apps.wayne.edu/docs/reference/status.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Status[#](https://weld.apps.wayne.edu/docs/reference/status.html#status)

This page records what is on the bench today: the state of every part welded into WELD, plus the gaps still open for the backlog.

Every part of the stack is welded in except one: HELMET and SESAME are in `require`; CREED, PROCTOR and ADVISOR are in `require-dev`; WayneUI 2.0.8 comes from npm; CI pins Preflight's components at 0.4.0; the deploy layer runs Squire's kit 0.9.0. Pinstripe's presets are still copied rather than installed. For what each part does in WELD, see [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html).

## What is on the bench today[#](https://weld.apps.wayne.edu/docs/reference/status.html#what-is-on-the-bench-today)

| Item | Value |
| --- | --- |
| `composer.json` | `waynestate/weld`, requires PHP `^8.3`, and the lock file resolves for 8.3.0 |
| Framework | Laravel 13 with Inertia 3 and Vue 3 |
| `.env.example` | sets `APP_NAME=WELD`, `LOG_CHANNEL=stderr` and `SESSION_DRIVER=database` |
| Index length | `Schema::defaultStringLength(191)` keeps index keys under Citadel's MySQL 5.6 767-byte cap |
| `.gitattributes` | sets `* text=auto eol=lf` |
| CI contract | `composer ci` runs validate, tests, Pint, PHPStan level 8, the agent-ready check, the practices sync check, the docs check and audit, the same command on GitLab and GitHub |
| CI jobs | on GitLab, Preflight 0.4.0 adds the PHP 8.3, 8.4 and 8.5 test matrix, the audit, PROCTOR, the accessibility gate over `site/` and over the app's own pages, an SBOM and secret detection; the `frontend` job runs ESLint and Prettier |
| WayneUI | an npm dependency at 2.0.8; it draws every page of the app and the site |
| HELMET | runs its security headers and CSP middleware in the global chain, so every response carries the baseline; the Vite build takes the per-request nonce; trusted proxies come from `TRUSTED_PROXIES` (`bootstrap/app.php`); `/up` stays Laravel's built-in liveness route and `/ready` is HELMET's readiness route, checking the database, the cache and the scheduler heartbeat |
| SESAME | owns sign-in at `/login` and `/logout`; the fake driver stands in for the identity provider locally, and CAS is an env change away |
| PROCTOR | measures WELD against CREED's `policy.json` as a CI job (`composer proctor`): warnings pass, failures block |
| ADVISOR | in `require-dev`; `php vendor/bin/advisor install --client=claude --write` points a coding agent at the stack's read-only MCP server |
| Squire | the deploy kit in `deploy/` at 0.9.0: the Citadel image, an idempotent release script and one portable image for other hosts |
| Pinstripe presets | `pint.json` and `phpstan.neon` still copy Pinstripe's 0.1 Laravel presets; Pinstripe 0.3.0 is on npm (Packagist still lists 0.1.4), and they switch to `includes: vendor/waynestate/pinstripe/...` when WELD requires it |
| Password auth | the starter kit's password auth (register, login, password reset, two-factor) is removed on purpose; the door is SESAME's |
| License | MIT |

## Gaps for the backlog[#](https://weld.apps.wayne.edu/docs/reference/status.html#gaps-for-the-backlog)

These mirror the gaps at the end of [BEST_PRACTICES.md](https://git.wayne.edu/enterprise-apps/weld/-/blob/main/BEST_PRACTICES.md).

- Preflight portability does not boot the image in CI yet (W8, W10, W12, W13, W16, W24), and Pinstripe (W32) is copied presets, not a dependency.
- The deploy-time scheduler work (W23) is still a plan.
- Still open: no test sends a forged `Host` to `/up`, so that half of W11 stays `Documented`. The other items that once sat on an "Unverified" list are pinned: the CSRF check (`tests/Feature/CsrfTest.php`), a random UID (W13), Pest 4 on the PHP 8.3 floor, and `/ready` without `spatie/laravel-health`.
- Decision needed: queue mode on Citadel (W19). The scheduled-worker pattern is a stopgap.
- Decided: `/ready` is HELMET's `Route::helmetReady()` with app checks as invokable classes: the database and the cache (W11) and the scheduler heartbeat (W23).
- Decision needed: whether WELD keeps Fortify for a non-SSO mode.
- Decision needed: SSR on by default or off.

## Related[#](https://weld.apps.wayne.edu/docs/reference/status.html#related)

- [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
- [Changelog](https://weld.apps.wayne.edu/docs/changelog.html)
