---
title: The container contract
description: The container contract a WELD app meets: environment-only config, logs to stderr, writable paths, non-root, /up, and what to run after every deploy.
---

# The container contract

This page lists the container contract practices for WELD, from the BEST_PRACTICES guide, with the tag that says whether the stack holds each one today.

## W9. Config comes from the environment only

**Practice:** No `.env` in the image. Call `env()` only inside `config/*.php`.

**Why:** After `config:cache` the `.env` file is not loaded and `env()` returns `null` elsewhere.

**Stack:** `Checked`. Every `env()` call is inside `config/*.php` except one documented exception: `bootstrap/app.php:27` reads `TRUSTED_PROXIES` because the middleware closure runs before config is loaded, and carries a `@phpstan-ignore` for `larastan.noEnvCallsOutsideOfConfig`. That rule is live (`phpstan.neon:10` sets `reportUnmatchedIgnoredErrors: true`), and `phpstan analyse` runs in the `ci` script (`composer.json:60`, `:64-72`), so a new `env()` outside `config/` fails CI.

## W10. `config:cache` runs at container start, never at image build

**Practice:** Build time may run `route:cache`, `view:cache`, `event:cache`. `php artisan optimize` at start is acceptable if the build did not freeze config.

**Why:** A build-time `config:cache` freezes the build machine's environment into the image.

**Stack:** `Documented`. The image build runs no artisan commands: `deploy/build.sh:49-51` fails if app source is present at build time, and the release step runs `php artisan optimize` once the environment is in place (`deploy/scripts/release.sh:453-459`), so config is cached at deploy, never at build. Target `Checked`: PROCTOR scans the Dockerfile.

## W11. `/up` is the liveness endpoint, and it never checks the database

**Practice:** Keep Laravel's `/up` (200 when the app boots). It must answer for any `Host`, over plain HTTP, before trusted hosts, redirects, sessions, CSP and auth. Put database and cache checks on a separate `/ready` endpoint, through the `DiagnosingHealth` event or a package.

**Why:** A database blip must not make the orchestrator kill every web container.

**Stack:** `Checked`. `bootstrap/app.php:16` sets the health route to `/up`, and `tests/Feature/HelmetHeadersTest.php:41-51` fails the suite when `/up` stops answering 200. `/ready` is a separate route (HELMET's `Route::helmetReady()`, `routes/web.php:11`) that checks only the scheduler heartbeat (W23); it runs no database or cache checks yet.

## W12. Image health check matches the platform

**Practice:** Put a `HEALTHCHECK` in the Dockerfile that calls `/up` with a tool the image has (`curl` or `wget`). On Coolify a Dockerfile `HEALTHCHECK` takes precedence over the dashboard check, and a dashboard check needs `curl` or `wget` inside the container.

**Why:** Traefik will not route to an unhealthy container, and a missing tool makes a healthy app look dead.

**Stack:** `Documented`. The Dockerfile carries a `HEALTHCHECK` (`deploy/.squire/Dockerfile:47`) whose script asks the local web server for `/up` by default and reads no database (`deploy/health.php:27`, `deploy/health.php:13`). Target `Checked`: Preflight portability runs `docker inspect` for a health status of `healthy`.

## W13. Non-root, arbitrary UID in group 0, listen on 8080

**Practice:** Declare a non-root `USER`. Make writable paths group 0 and group-writable (`chgrp -R 0`, `chmod -R g=u`) for `storage/` and `bootstrap/cache/`. Do not depend on a `www-data` entry. Honor `PORT`.

**Why:** OpenShift runs a random UID in group 0 with all capabilities dropped.

**Stack:** `Not yet`. A Dockerfile now exists (`deploy/.squire/Dockerfile`), but it is the Citadel Apache image: it declares no `USER`, the vhost listens on 80 (`deploy/.squire/vhost.conf:2`) and the fallback base exposes 80 (`deploy/.squire/base-fallback.Dockerfile:113`), so the non-root, group-0, 8080 contract is not held. Target `Checked`: Preflight portability starts the image with a UID that has no `/etc/passwd` entry, `cap_drop: ALL`, `no-new-privileges` and a read-only root filesystem.

## W14. Logs go to stderr; the app writes only where it may

**Practice:** Set `LOG_CHANNEL=stderr`. Nothing under `storage/logs`. Writes go to `storage/`, `bootstrap/cache/`, `/tmp` and declared volumes. Uploads go to object storage.

**Why:** Platforms collect stdout/stderr; local files vanish with the container.

**Stack:** `Documented`. `.env.example:18` sets `LOG_CHANNEL=stderr`. Target `Checked`: the portability job fails when `storage/logs` is non-empty.

## W15. Sessions and cache live in a shared store

**Practice:** Use `SESSION_DRIVER=database` (or Redis) and a shared cache, even for one replica. Every host overlaps old and new containers during a deploy.

**Why:** A login must survive a hop between replicas.

**Stack:** `Documented`. `.env.example:38` sets `SESSION_DRIVER=database` and `.env.example:69` sets `CACHE_STORE=database`; `config/session.php:21` and `config/cache.php:18` default to the same. Target `Checked`: the portability job logs in on replica A and reads the session on replica B.

## W16. Stop cleanly on SIGTERM

**Practice:** Run PHP as PID 1 under a small init, or an entrypoint that `exec`s the server. Queue workers finish the current job on `SIGTERM`. Set the platform's grace period above the longest job `#[Timeout]`.

**Why:** A hard kill mid-job double-runs it after `retry_after`.

**Stack:** `Documented`. The entrypoint replaces the shell with the server (`deploy/.squire/fallback/citadel-apache-entrypoint:58`), so `SIGTERM` reaches the foreground process directly; no queue worker process type exists yet, and the grace period is a platform setting. Target `Checked`: the portability job sends `SIGTERM` and requires exit 0 within the grace period.

## W17. Trust proxies by preset, never `*`

**Practice:** Select a HELMET preset (`citadel` or `docker`), or list the front proxy in `HELMET_PROXIES` on other platforms. Do not paste `trustProxies(at: '*')` from tutorials. Set `APP_URL` and trusted hosts; Laravel trusts subdomains of `APP_URL` unless `subdomains: false`.

**Why:** An app that trusts all proxies lets a client forge `X-Forwarded-*` and the host used in callbacks.

**Stack:** `Enforced` by HELMET 1.0 (required `^1.0`): it trusts only the preset or listed proxies and drops `X-Forwarded-Port`. Target `Checked`: a portability probe sends forged `X-Forwarded-Host` and `-Proto` from an untrusted peer.

## How to read the tags

Each practice ends with exactly one tag.

| Tag | Meaning |
| --- | --- |
| `Enforced` | WELD's code does it or refuses to run without it, and a test covers it. |
| `Checked` | PROCTOR or CI warns or fails. |
| `Documented` | Written down in the README or a config file, with no check behind it. |
| `Not yet` | A gap. |

## Related

- [App structure](./app-structure.md)
- [Queues and scheduling](./queues-and-scheduling.md)
- [Running WELD off Citadel](../running-off-citadel.md)
