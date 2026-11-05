---
title: "The container contract | WELD docs"
description: "The container contract a WELD app meets: environment-only config, logs to stderr, writable paths, non-root, /up, and what to run after every deploy."
url: https://weld.apps.wayne.edu/docs/guides/container-contract.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# The container contract[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#the-container-contract)

This page lists the container contract practices for WELD, from the BEST_PRACTICES guide, with the tag that says whether the stack holds each one today.

## W9. Config comes from the environment only[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w9-config-comes-from-the-environment-only)

**Practice:** No `.env` in the image. Call `env()` only inside `config/*.php`.

**Why:** After `config:cache` the `.env` file is not loaded and `env()` returns `null` elsewhere.

**Stack:** `Documented`. Every `env()` call in the app is inside `config/*.php`; none is in `app/` or `routes/`. The one exception is `bootstrap/app.php`, which reads `TRUSTED_PROXIES` with `env()` because that closure runs before config loads (it carries a PHPStan ignore). Target `Checked`: Pinstripe/PROCTOR ban `env(` outside `config/`; the portability job boots without `.env`.

## W10. `config:cache` runs at container start, never at image build[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w10-config-cache-runs-at-container-start-never-at-image-build)

**Practice:** Build time may run `route:cache`, `view:cache`, `event:cache`. `php artisan optimize` at start is acceptable if the build did not freeze config.

**Why:** A build-time `config:cache` freezes the build machine's environment into the image.

**Stack:** `Documented`. The image build runs no artisan commands: `deploy/build.sh` fails if app source is present at build time, and the release step runs `php artisan optimize` once the environment is in place (`deploy/scripts/release.sh`, on by default, off with `SQUIRE_ARTISAN_OPTIMIZE=0`), so config is cached at deploy, never at build. Target `Checked`: PROCTOR scans the Dockerfile.

## W11. `/up` is the liveness endpoint, and it never checks the database[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w11-up-is-the-liveness-endpoint-and-it-never-checks-the-database)

**Practice:** Keep Laravel's `/up` (200 when the app boots). It must answer for any `Host`, over plain HTTP, before trusted hosts, redirects, sessions, CSP and auth. Put database and cache checks on a separate `/ready` endpoint, through the `DiagnosingHealth` event or a package.

**Why:** A database blip must not make the orchestrator kill every web container.

**Stack:** `Documented`. `bootstrap/app.php` sets the health route to `/up`, which answers 200 when the framework boots and touches no database. The `/ready` endpoint is not built yet. Target `Checked`: the portability job requests `/up` with `Host: 10.1.2.3:8080` and no forwarded headers, and again with the database stopped.

## W12. Image health check matches the platform[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w12-image-health-check-matches-the-platform)

**Practice:** Put a `HEALTHCHECK` in the Dockerfile that calls `/up` with a tool the image has (`curl` or `wget`). On Coolify a Dockerfile `HEALTHCHECK` takes precedence over the dashboard check, and a dashboard check needs `curl` or `wget` inside the container.

**Why:** Traefik will not route to an unhealthy container, and a missing tool makes a healthy app look dead.

**Stack:** `Documented`. The Dockerfile carries a `HEALTHCHECK` (`deploy/.squire/Dockerfile`) whose script asks the local web server for `/up` by default and reads no database (`deploy/health.php`, `deploy/health.php`). Target `Checked`: Preflight portability runs `docker inspect` for a health status of `healthy`.

## W13. Non-root, arbitrary UID in group 0, listen on 8080[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w13-non-root-arbitrary-uid-in-group-0-listen-on-8080)

**Practice:** Declare a non-root `USER`. Make writable paths group 0 and group-writable (`chgrp -R 0`, `chmod -R g=u`) for `storage/` and `bootstrap/cache/`. Do not depend on a `www-data` entry. Honor `PORT`.

**Why:** OpenShift runs a random UID in group 0 with all capabilities dropped.

**Stack:** `Not yet`. A Dockerfile now exists (`deploy/.squire/Dockerfile`), but it is the Citadel Apache image: it declares no `USER`, the vhost listens on 80 (`deploy/.squire/vhost.conf`) and the fallback base exposes 80 (`deploy/.squire/base-fallback.Dockerfile`), so the non-root, group-0, 8080 contract is not held. Target `Checked`: Preflight portability starts the image with a UID that has no `/etc/passwd` entry, `cap_drop: ALL`, `no-new-privileges` and a read-only root filesystem.

## W14. Logs go to stderr; the app writes only where it may[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w14-logs-go-to-stderr-the-app-writes-only-where-it-may)

**Practice:** Set `LOG_CHANNEL=stderr`. Nothing under `storage/logs`. Writes go to `storage/`, `bootstrap/cache/`, `/tmp` and declared volumes. Uploads go to object storage.

**Why:** Platforms collect stdout/stderr; local files vanish with the container.

**Stack:** `Documented`. `.env.example` sets `LOG_CHANNEL=stderr`. Target `Checked`: the portability job fails when `storage/logs` is non-empty.

## W15. Sessions and cache live in a shared store[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w15-sessions-and-cache-live-in-a-shared-store)

**Practice:** Use `SESSION_DRIVER=database` (or Redis) and a shared cache, even for one replica. Every host overlaps old and new containers during a deploy.

**Why:** A login must survive a hop between replicas.

**Stack:** `Documented`. `.env.example` sets `SESSION_DRIVER=database` and `.env.example` sets `CACHE_STORE=database`; `config/session.php` and `config/cache.php` default to the same. Target `Checked`: the portability job logs in on replica A and reads the session on replica B.

## W16. Stop cleanly on SIGTERM[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w16-stop-cleanly-on-sigterm)

**Practice:** Run PHP as PID 1 under a small init, or an entrypoint that `exec`s the server. Queue workers finish the current job on `SIGTERM`. Set the platform's grace period above the longest job `#[Timeout]`.

**Why:** A hard kill mid-job double-runs it after `retry_after`.

**Stack:** `Documented`. The entrypoint replaces the shell with the server (`deploy/.squire/fallback/citadel-apache-entrypoint`), so `SIGTERM` reaches the foreground process directly; no queue worker process type exists yet, and the grace period is a platform setting. Target `Checked`: the portability job sends `SIGTERM` and requires exit 0 within the grace period.

## W17. Trust proxies by preset, never `*` [#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w17-trust-proxies-by-preset-never)

**Practice:** Select a HELMET preset (`citadel` or `docker`), or list the front proxy in `HELMET_PROXIES` on other platforms. Do not paste `trustProxies(at: '*')` from tutorials. Set `APP_URL` and trusted hosts; Laravel trusts subdomains of `APP_URL` unless `subdomains: false`.

**Why:** An app that trusts all proxies lets a client forge `X-Forwarded-*` and the host used in callbacks.

**Stack:** `Enforced` by HELMET 0.3 (required `^0.3`): it trusts only the preset or listed proxies and drops `X-Forwarded-Port`. Target `Checked`: a portability probe sends forged `X-Forwarded-Host` and `-Proto` from an untrusted peer.

## How to read the tags[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#how-to-read-the-tags)

Each practice ends with exactly one tag.

| Tag | Meaning |
| --- | --- |
| `Enforced` | WELD's code does it or refuses to run without it, and a test covers it. |
| `Checked` | PROCTOR or CI warns or fails. |
| `Documented` | Written down in the README or a config file, with no check behind it. |
| `Not yet` | A gap. |

## Related[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#related)

- [App structure](https://weld.apps.wayne.edu/docs/guides/app-structure.html)
- [Queues and scheduling](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
