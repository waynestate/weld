# Running WELD off Citadel

WELD deploys on Citadel through SQUIRE: one container per app, and builds run at release time inside the app
container. The stack rule (ADR-0010, portable beyond Citadel) says the same app must also run on OpenShift, Coolify
and plain Docker. This page is the per-platform contract: how traffic reaches the app, where logs go, what may be
written, which user runs it, how the platform knows it is alive, and what to run after every deploy.

Two notes before the tables. Claims marked **(check)** depend on something not built yet:

- There is no production Dockerfile or entrypoint. `compose.yaml` is the local development stack (`artisan serve`
  on 8000 plus Vite on 5173, SQLite), not a deploy image.
- HELMET (`^0.2`) owns the trusted proxies (BEST_PRACTICES.md, W17). `HELMET_PRESET` picks a platform preset, and
  HELMET 0.2 ships two: `citadel` and `docker`. An unknown preset name throws at boot. Anywhere else, list the front
  proxy's address in `HELMET_PROXIES` (it wins over `HELMET_PRESET`), and never trust a whole private range.

## Common ground, every platform

| Concern | Contract |
|---|---|
| Config | Environment only. No `.env` in the image; `env()` calls live in `config/` (W9). Set `APP_URL` to the real host (W17) and keep `APP_DEBUG=false` in production (W26). |
| Logs | `LOG_CHANNEL=stderr` (`.env.example:18`). Everything goes to `php://stderr`; nothing under `storage/logs` (W14). |
| Writable paths | `storage/` and `bootstrap/cache/` only, plus `/tmp` (W14). |
| Sessions and cache | A shared store, even for one replica: `SESSION_DRIVER=database` (`.env.example:30`) and `CACHE_STORE=database` (`.env.example:40`), because every deploy overlaps old and new containers (W15). |
| User | Non-root. Declare a `USER` in the image; never depend on a `www-data` entry (W13). |
| Health | `/up`, registered in `bootstrap/app.php`: 200 when the framework boots, and it never checks the database, so a database blip cannot kill every replica (W11). |
| After every deploy | Run `php artisan weld:doctor` in the app container. One PASS, WARN or FAIL line per check; exit code 1 when anything FAILs, so it works as a gate. |

## Citadel (for comparison)

| Concern | How it works |
|---|---|
| Traffic | One container per app, behind Citadel's front-end proxy (HA-Proxy). |
| Trusted proxies | `HELMET_PRESET=citadel`: trust only the proxy in front, never `*` (W17). |
| Logs | `php://stderr`, captured by the platform. File logging is the one case where a path is tolerated here: legacy apps wrote `storage/logs/laravel.log` **(check)**. The portable baseline keeps `storage/logs` empty (W14). |
| Writable paths | `storage/`, `bootstrap/cache/`. |
| User | Set by the Citadel image **(check)**. |
| Health | `/up`. |
| Queue | No queue workers on Citadel. The scheduled-worker mode `WELD_QUEUE_MODE=scheduled` is planned, not built (W19, **check**). |
| After deploy | `php artisan weld:doctor` in the app container. |

## OpenShift

| Concern | How it works |
|---|---|
| Traffic | The OpenShift router fronts the route and forwards to the pod. |
| Trusted proxies | `HELMET_PROXIES=<router address>`: trust the router only (W17). There is no `openshift` preset; setting one throws. |
| Logs | `php://stderr`; read them with `oc logs`. |
| Writable paths | `storage/`, `bootstrap/cache/`. |
| User | OpenShift assigns a random UID in group 0 with capabilities dropped (W13), so make both writable paths group-owned and group-writable before the pod starts: `chgrp -R 0 storage bootstrap/cache && chmod -R g=u storage bootstrap/cache`. |
| Health | Liveness probe: HTTP GET `/up`. It answers for any `Host` over plain HTTP, before trusted hosts, sessions and auth (W11). |
| After deploy | `oc exec deploy/<app> -- php artisan weld:doctor`. |

## Coolify

| Concern | How it works |
|---|---|
| Traffic | Coolify's Traefik instance fronts the app and routes by domain. |
| Trusted proxies | `HELMET_PROXIES=<Traefik container address>` (W17). There is no `coolify` preset; setting one throws. |
| Logs | `php://stderr`; Coolify shows the container logs. |
| Writable paths | `storage/`, `bootstrap/cache/`, on a persistent volume so they survive redeploys. |
| User | Whatever the image declares; keep it non-root (W13). |
| Health | A Dockerfile `HEALTHCHECK` that calls `/up` takes precedence over the dashboard check; either way the image needs `curl` or `wget` (W12). Traefik stops routing to an unhealthy container. |
| After deploy | Exec into the app container and run `php artisan weld:doctor`. |

## Plain Docker compose

| Concern | How it works |
|---|---|
| Traffic | Direct to the container, or your own reverse proxy in front. With no proxy, trust no forwarded headers: `HELMET_PRESET=docker` **(check)** (W17). |
| Logs | `php://stderr`; follow them with `docker compose logs -f app`. |
| Writable paths | Mount `storage/` and `bootstrap/cache/` as volumes so data survives the container. |
| User | Non-root `USER` in the image (W13). |
| Health | `HEALTHCHECK` on `/up` in the image (W12). |
| After deploy | `docker compose exec app php artisan weld:doctor`. |

The `compose.yaml` in this repository is the local development stack, not a production image. A production image
for raw Docker is **(check)** until one ships.

Four tables, one bead: if the joint passes `weld:doctor` on all of them, the seam holds.
