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

**Stack:** `Checked`. Every `env()` call is inside `config/*.php` except one documented exception: `bootstrap/app.php:44` reads `TRUSTED_PROXIES` because the middleware closure runs before config is loaded, and carries a `@phpstan-ignore` for `larastan.noEnvCallsOutsideOfConfig`. That rule is live (`phpstan.neon:10` sets `reportUnmatchedIgnoredErrors: true`), and `phpstan analyse` runs in the `ci` script (`composer.json:61`, `:66-75`), so a new `env()` outside `config/` fails CI.

## W10. `config:cache` runs at container start, never at image build[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w10-config-cache-runs-at-container-start-never-at-image-build)

**Practice:** Build time may run `route:cache`, `view:cache`, `event:cache`. `php artisan optimize` at start is acceptable if the build did not freeze config.

**Why:** A build-time `config:cache` freezes the build machine's environment into the image.

**Stack:** `Documented`. The image build runs no artisan commands: `deploy/build.sh:49-51` fails if app source is present at build time, and the release step runs `php artisan optimize` once the environment is in place (`deploy/scripts/release.sh:555-561`), so config is cached at deploy, never at build. Target `Checked`: PROCTOR scans the Dockerfile.

## W11. `/up` is the liveness endpoint, and it never checks the database[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w11-up-is-the-liveness-endpoint-and-it-never-checks-the-database)

**Practice:** Keep Laravel's `/up` (200 when the app boots). It must answer for any `Host`, over plain HTTP, before trusted hosts, redirects, sessions, CSP and auth. Put database and cache checks on a separate `/ready` endpoint, through the `DiagnosingHealth` event or a package.

**Why:** A database blip must not make the orchestrator kill every web container.

**Stack:** `Checked`. `bootstrap/app.php:20` sets the health route to `/up`, and `tests/Feature/HelmetHeadersTest.php:41-51` fails the suite when `/up` stops answering 200. `/ready` is a separate route, HELMET's `Route::helmetReady()` registered in the routing `then:` callback outside the web group (`bootstrap/app.php:27-33`), so a probe writes no session row and sets no cookie. It checks the database (`app/Health/DatabaseReady.php` opens the default connection), the cache (`app/Health/CacheReady.php` writes a key, reads it back and forgets it) and the scheduler heartbeat (W23), and answers 503 when one fails. `tests/Feature/ReadyChecksTest.php` proves it: "answers 503 on /ready when the database cannot be reached" (a missing SQLite file), "answers 503 on /ready when the cache store fails" (a store with no driver), "serves /ready outside the web group, with no session cookie" and "keeps /up free of the readiness checks" (the same broken database, and `/up` still answers 200). The checks and the test are byte for byte from Sourdough's Inertia shape; the route lines join WELD's own `bootstrap/app.php` and `routes/web.php`, which the regen baseline already accepts as drift. The checks use Laravel's own `DB` and `Cache` rather than the `DiagnosingHealth` event or a package, because HELMET's route already gives each check a name, a status and a logged reason.

## W12. Image health check matches the platform[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w12-image-health-check-matches-the-platform)

**Practice:** Put a `HEALTHCHECK` in the Dockerfile that calls `/up` with a tool the image has (`curl` or `wget`). On Coolify a Dockerfile `HEALTHCHECK` takes precedence over the dashboard check, and a dashboard check needs `curl` or `wget` inside the container.

**Why:** Traefik will not route to an unhealthy container, and a missing tool makes a healthy app look dead.

**Stack:** `Documented`. The Dockerfile carries a `HEALTHCHECK` (`deploy/.squire/Dockerfile:47`) whose script asks the local web server for `/up` by default and reads no database (`deploy/health.php:27`, `deploy/health.php:13`). Target `Checked`: Preflight portability runs `docker inspect` for a health status of `healthy`.

## W13. Non-root, arbitrary UID in group 0, listen on 8080[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w13-non-root-arbitrary-uid-in-group-0-listen-on-8080)

**Practice:** Declare a non-root `USER`. Make writable paths group 0 and group-writable (`chgrp -R 0`, `chmod -R g=u`) for `storage/` and `bootstrap/cache/`. Do not depend on a `www-data` entry. Honor `PORT`.

**Why:** OpenShift runs a random UID in group 0 with all capabilities dropped.

**Stack:** `Checked`, for the portable path. `deploy/.squire/portable.Dockerfile` declares `USER 10001:0` (no `/etc/passwd` entry, no `www-data`), hands Apache's run, lock and log folders and `storage/` and `bootstrap/cache/` to group 0 with `chmod -R g=u`, and Apache listens on `SQUIRE_PORT`, else `PORT`, else 8080 (`deploy/.squire/portable/ports.conf`, `deploy/.squire/portable/vhost.conf`); the entrypoint refuses a port below 1024 for a user that is not root. The compose profile runs it with `cap_drop: [ALL]` and `no-new-privileges`. `tests/Feature/PortableImageTest.php` holds the user, the group-0 folders and the port in WELD's copy, and Squire's `PortableImageTest` (`testThePortableImageRunsAsANumericNonRootUserInGroupZeroOn8080`, `testABadSettingStopsTheContainerWithAPlainMessage`) checks the kit. Run for real on 2026-11-08 as `--user 1000123:0`, the way OpenShift would: `/up` answered 200 on 8080 and `storage/` was writable. The Citadel image is not this image: it starts as root and drops to its own user, and its vhost stays on 80 (`deploy/.squire/vhost.conf:2`). Open: the base image's inherited `EXPOSE 80` still shows in the metadata; a read-only root filesystem is untested; Preflight portability does not run the image in CI yet.

## W14. Logs go to stderr; the app writes only where it may[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w14-logs-go-to-stderr-the-app-writes-only-where-it-may)

**Practice:** Set `LOG_CHANNEL=stderr`. Nothing under `storage/logs`. Writes go to `storage/`, `bootstrap/cache/`, `/tmp` and declared volumes. Uploads go to object storage.

**Why:** Platforms collect stdout/stderr; local files vanish with the container.

**Stack:** `Documented`. `.env.example:18` sets `LOG_CHANNEL=stderr`. Target `Checked`: the portability job fails when `storage/logs` is non-empty.

## W15. Sessions and cache live in a shared store[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w15-sessions-and-cache-live-in-a-shared-store)

**Practice:** Use `SESSION_DRIVER=database` (or Redis) and a shared cache, even for one replica. Every host overlaps old and new containers during a deploy.

**Why:** A login must survive a hop between replicas.

**Stack:** `Documented`. `.env.example:38` sets `SESSION_DRIVER=database` and `.env.example:69` sets `CACHE_STORE=database`; `config/session.php:21` and `config/cache.php:18` default to the same. Target `Checked`: the portability job logs in on replica A and reads the session on replica B.

## W16. Stop cleanly on SIGTERM[#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w16-stop-cleanly-on-sigterm)

**Practice:** Run PHP as PID 1 under a small init, or an entrypoint that `exec`s the server. Queue workers finish the current job on `SIGTERM`. Set the platform's grace period above the longest job `#[Timeout]`.

**Why:** A hard kill mid-job double-runs it after `retry_after`.

**Stack:** `Documented`. The entrypoint replaces the shell with the server (`deploy/.squire/fallback/citadel-apache-entrypoint:58`), so `SIGTERM` reaches the foreground process directly. On the portable path the image sets `STOPSIGNAL SIGTERM`: the `web` role turns it into Apache's graceful stop and the `worker` and `scheduler` roles exec PHP, which finishes the current job (Squire's `testSigtermIsAGracefulStopForApacheAndItsStatusIsTheContainers`; on 2026-11-08 `docker stop` ended all three in about two seconds with exit 0). The grace period is a platform setting. Target `Checked`: the portability job sends `SIGTERM` and requires exit 0 within the grace period.

## W17. Trust proxies by preset, never `*` [#](https://weld.apps.wayne.edu/docs/guides/container-contract.html#w17-trust-proxies-by-preset-never)

**Practice:** Select a HELMET preset (`citadel` or `docker`), or list the front proxy in `HELMET_PROXIES` on other platforms. Do not paste `trustProxies(at: '*')` from tutorials. Set `APP_URL` and trusted hosts; Laravel trusts subdomains of `APP_URL` unless `subdomains: false`.

**Why:** An app that trusts all proxies lets a client forge `X-Forwarded-*` and the host used in callbacks.

**Stack:** `Enforced` by HELMET 1.1 (required `^1.1`): it trusts only the preset or listed proxies and drops `X-Forwarded-Port`. Target `Checked`: a portability probe sends forged `X-Forwarded-Host` and `-Proto` from an untrusted peer.

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
