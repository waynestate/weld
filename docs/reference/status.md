---
title: Status
description: What is on the bench today: the state of every part welded into WELD.
---

# Status

This page records what is on the bench today: the state of every part welded into WELD, plus the gaps still open for the backlog.

HELMET, SESAME and PROCTOR are welded in from Packagist; the rest of the cast is on Packagist too and gets welded on
in turn. For what each part does in WELD, see [What is welded in](../guides/whats-welded-in.md).

## What is on the bench today

| Item | Value |
|---|---|
| `composer.json` | `waynestate/weld`, requires PHP `^8.3`, and the lock file resolves for 8.3.0 |
| `.env.example` | sets `APP_NAME=WELD`, `LOG_CHANNEL=stderr` and `SESSION_DRIVER=database` |
| Index length | `Schema::defaultStringLength(191)` keeps index keys under Citadel's MySQL 5.6 767-byte cap |
| `.gitattributes` | sets `* text=auto eol=lf` |
| CI contract | `composer ci` runs validate, tests, Pint, PHPStan level 8, the agent-ready check and audit, the same command on GitLab and GitHub |
| Pinstripe presets | `pint.json` and `phpstan.neon` still copy Pinstripe's 0.1 Laravel presets; Pinstripe 0.1.4 is on Packagist (tagged), and they switch to `includes: vendor/waynestate/pinstripe/...` when WELD requires it |
| HELMET | runs its security headers and CSP middleware in the global chain, so every response carries the baseline; the Vite build takes the per-request nonce; trusted proxies are a literal list in `bootstrap/app.php`; `/up` stays Laravel's built-in health route |
| SESAME | owns sign-in at `/login` and `/logout`; the fake driver stands in for the identity provider locally, and CAS is an env change away |
| PROCTOR | measures WELD against CREED's `policy.json` as a CI job (`composer proctor`): warnings pass, failures block |
| Password auth | the starter kit's password auth (register, login, password reset, two-factor) is removed on purpose; the door is SESAME's |
| License | MIT |

## Gaps for the backlog

The Laravel base has landed; the gaps below are the stack packages and the deploy path that have not.

- The stack packages are dependencies now: SESAME and HELMET in `require`, CREED and PROCTOR in `require-dev`, all from Packagist. The items that waited on them (SESAME W1, HELMET W17 and W28, PROCTOR's `Checked` targets, CREED W25) can be checked against the installed packages. Preflight portability (W8, W10, W12, W13, W16, W24), Pinstripe (W2, W32) and WayneUI (W2) are not wired into WELD yet.
- The Squire kit in `deploy/` now carries the Citadel deploy path: a Dockerfile mirroring the platform image, an entrypoint and an idempotent release script, so W10, W12, W16 and W24 hold for that path. Still plans: worker and scheduler process types (W8), the non-root, group-0, 8080 contract (W13), the restart commands in the release script (W22), and the deploy-time queue/scheduler work (W19, W21).
- The JS side of the coding standard (W32) is configured but not in CI.
- Unverified: OpenShift arbitrary-UID text on a primary Red Hat page; `serversideup/php` behavior under a random UID; Pest 4's PHP floor; `spatie/laravel-health` scheduler and queue checks; whether Laravel's health route runs before `TrustHosts`; whether the old CSRF middleware name is still an alias.
- Decision needed: queue mode on Citadel (W19). The scheduled-worker pattern is a stopgap. Confirm with the deploy kit project whether Citadel can run a cron-driven `schedule:run` at all.
- Decided: `/ready` is HELMET's `Route::helmetReady()` with app checks as invokable classes (the scheduler heartbeat today, W23); database and cache checks (W11) can join it the same way.
- Decision needed: whether WELD keeps Fortify for a non-SSO mode.
- Decision needed: SSR on by default or off.

## Related

- [What is welded in](../guides/whats-welded-in.md)
- [Running WELD off Citadel](../running-off-citadel.md)
- [Changelog](../changelog.md)
