---
title: "Running WELD off Citadel | WELD docs"
description: "The per-platform contract for running WELD off Citadel: how traffic reaches the app, where logs go, what may be written, which user runs it, how the platform knows it is alive, and what to run after every deploy."
url: https://weld.apps.wayne.edu/docs/running-off-citadel.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Running WELD off Citadel[#](https://weld.apps.wayne.edu/docs/running-off-citadel.html#running-weld-off-citadel)

WELD deploys on Citadel through Squire: one container per app, and builds run at release time inside the app container. The stack rule (ADR-0010, portable beyond Citadel) says the same app must also run on OpenShift, Coolify and plain Docker. This page is the per-platform contract: how traffic reaches the app, where logs go, what may be written, which user runs it, how the platform knows it is alive, and what to run after every deploy.

Two notes before the tables. Claims marked **(check)** depend on something not built yet:

- The image is Squire's portable image, `deploy/.squire/portable.Dockerfile` (see "The portable image" below). `compose.yaml` is the local development stack (`artisan serve` on 8000 plus Vite on 5173, SQLite), not a deploy image.
- HELMET (`^1.1`) owns the trusted proxies (BEST_PRACTICES.md, W17). `HELMET_PRESET` picks a platform preset, and HELMET 1.1 ships two: `citadel` and `docker`. An unknown preset name throws at boot. Anywhere else, list the front proxy's address in `HELMET_PROXIES` (it wins over `HELMET_PRESET`), and never trust a whole private range. Laravel's own proxy list has its own variable: set TRUSTED_PROXIES to the same address (unset means Citadel's 172.18.0.1).

## Common ground, every platform[#](https://weld.apps.wayne.edu/docs/running-off-citadel.html#common-ground-every-platform)

| Concern | Contract |
| --- | --- |
| Config | Environment only. No `.env` in the image; `env()` calls live in `config/` (W9). Set `APP_URL` to the real host (W17) and keep `APP_DEBUG=false` in production (W26). |
| Logs | `LOG_CHANNEL=stderr` (`.env.example:18`). Everything goes to `php://stderr`; nothing under `storage/logs` (W14). |
| Writable paths | `storage/` and `bootstrap/cache/` only, plus `/tmp` (W14). |
| Sessions and cache | A shared store, even for one replica: `SESSION_DRIVER=database` (`.env.example:30`) and `CACHE_STORE=database` (`.env.example:40`), because every deploy overlaps old and new containers (W15). |
| User | Non-root. The portable image declares `USER 10001:0` and never depends on a `www-data` entry; any UID in group 0 runs it (W13). |
| Processes | One image, four roles by `SQUIRE_ROLE`: `web` (Apache on 8080), `worker` (`queue:work --tries=3 --max-time=3600 --sleep=3`), `scheduler` (`schedule:work`, with `QUEUE_WORKER_MODE=none`) and `migrate` (once per release) (W8, W24). |
| Health | `/up`, registered in `bootstrap/app.php`: 200 when the framework boots, and it never checks the database, so a database blip cannot kill every replica (W11). |
| After every deploy | Run `php artisan weld:doctor` in the app container. One PASS, WARN or FAIL line per check; exit code 1 when anything FAILs, so it works as a gate. |

## Citadel (for comparison)[#](https://weld.apps.wayne.edu/docs/running-off-citadel.html#citadel-for-comparison)

| Concern | How it works |
| --- | --- |
| Traffic | One container per app, behind Citadel's front-end proxy (HA-Proxy). |
| Trusted proxies | `HELMET_PRESET=citadel`: trust only the proxy in front, never `*` (W17). |
| Logs | `php://stderr`, captured by the platform. File logging is the one case where a path is tolerated here: legacy apps wrote `storage/logs/laravel.log` **(check)**. The portable baseline keeps `storage/logs` empty (W14). |
| Writable paths | `storage/`, `bootstrap/cache/`. |
| User | Set by the Citadel image **(check)**. |
| Health | `/up`. |
| Queue | No queue workers on Citadel. `QUEUE_WORKER_MODE=scheduler` (the `.env.example` default) schedules a short `queue:work --stop-when-empty --max-time=50 --tries=3` every minute (W19). |
| After deploy | `php artisan weld:doctor` in the app container. |

## OpenShift[#](https://weld.apps.wayne.edu/docs/running-off-citadel.html#openshift)

| Concern | How it works |
| --- | --- |
| Traffic | The OpenShift router fronts the route and forwards to the pod. |
| Trusted proxies | `HELMET_PROXIES=<router address>`, and the same address in TRUSTED_PROXIES: trust the router only (W17). There is no `openshift` preset; setting one throws. |
| Logs | `php://stderr`; read them with `oc logs`. |
| Writable paths | `storage/`, `bootstrap/cache/`. |
| User | OpenShift assigns a random UID in group 0 with capabilities dropped (W13). The portable image already made `storage/`, `bootstrap/cache/` and Apache's folders group-owned and group-writable (`chgrp -R 0`, `chmod -R g=u`), so it runs as that UID unchanged. |
| Processes | A Deployment each for `web` (`SQUIRE_ROLE=web`, container port 8080) and `worker`, one replica of `scheduler`, and a Job (or a pre-deploy hook) for `migrate` (W8). |
| Health | Liveness probe: HTTP GET `/up` on 8080. It answers for any `Host` over plain HTTP, before trusted hosts, sessions and auth (W11). |
| After deploy | `oc exec deploy/<app> -- php artisan weld:doctor`. |

## Coolify[#](https://weld.apps.wayne.edu/docs/running-off-citadel.html#coolify)

| Concern | How it works |
| --- | --- |
| Traffic | Coolify's Traefik instance fronts the app and routes by domain. |
| Trusted proxies | `HELMET_PROXIES=<Traefik container address>`, and the same address in TRUSTED_PROXIES (W17). There is no `coolify` preset; setting one throws. |
| Logs | `php://stderr`; Coolify shows the container logs. |
| Writable paths | `storage/`, `bootstrap/cache/`, on a persistent volume so they survive redeploys. |
| User | Whatever the image declares: `10001:0` for the portable image (W13). |
| Processes | Deploy the portable image's Dockerfile, or the `portable` profile of `deploy/docker-compose.squire.yml` as a Compose app (W8). |
| Health | A Dockerfile `HEALTHCHECK` takes precedence over the dashboard check (W12). The portable image's runs `squire-entrypoint health`: `health.php` asks the web role's `/up`, and the other roles are healthy while they run. A dashboard check instead needs `curl` or `wget` in the image. Traefik stops routing to an unhealthy container. |
| After deploy | Exec into the app container and run `php artisan weld:doctor`. |

## Plain Docker compose[#](https://weld.apps.wayne.edu/docs/running-off-citadel.html#plain-docker-compose)

| Concern | How it works |
| --- | --- |
| Traffic | Direct to the container, or your own reverse proxy in front. With no proxy, trust no forwarded headers: `HELMET_PRESET=docker` **(check)** (W17). |
| Logs | `php://stderr`, and Apache's logs to stdout and stderr; follow them with `docker compose logs -f web worker scheduler`. |
| Writable paths | Mount `storage/` and `bootstrap/cache/` as volumes so data survives the container. |
| User | Non-root `USER` in the image (W13); try OpenShift's way with `SQUIRE_PORTABLE_USER=1000123:0`. |
| Processes | The `portable` profile's `migrate`, `web`, `worker` and `scheduler` services, one image (W8). |
| Health | `HEALTHCHECK` in the image (W12). |
| After deploy | `docker compose exec app php artisan weld:doctor`. |

The `compose.yaml` in this repository is the local development stack, not a production image.

## The portable image[#](https://weld.apps.wayne.edu/docs/running-off-citadel.html#the-portable-image)

Squire's kit in `deploy/` carries the image for every platform above but Citadel. Build it from the repository root (the app), with the kit's folder named:

```sh
docker build -f deploy/.squire/portable.Dockerfile --build-arg SQUIRE_KIT_DIR=deploy --build-arg BASE_IMAGE=<base image> -t weld .
docker run -d --user 1000123:0 --cap-drop ALL --security-opt no-new-privileges:true --env-file .env -p 127.0.0.1:8081:8080 weld
docker run -d --user 1000123:0 --env-file .env -e SQUIRE_ROLE=worker weld
docker run -d --user 1000123:0 --env-file .env -e SQUIRE_ROLE=scheduler -e QUEUE_WORKER_MODE=none weld
```

Or the four roles at once, with MySQL beside them, from `deploy/` (copy `.env.squire.example` to `.env.squire` and set `SQUIRE_PORTABLE_CONTEXT=..`, `SQUIRE_KIT_DIR=deploy` and `SQUIRE_PORTABLE_ENV_FILE=../.env` there):

```sh
docker compose -f docker-compose.squire.yml --env-file .env.squire --profile portable up -d --build web worker scheduler
```

The build runs the install-and-build half of the release (Composer without dev packages, the Vite build); each container runs `php artisan optimize` when it starts, so config is cached for its own environment, never at build (W10), and `migrate` runs once before the others (W24). Every role stops gracefully on `SIGTERM` (W16). Squire's [portable image guide](https://squire.apps.wayne.edu/docs/guides/portable-image.html) has the whole contract.

Every GitLab pipeline builds this image too. Preflight's `portability` job builds it with Buildah on the kit's fallback base and boots the `web` role twice, side by side, as a uid with no name in group 0 with every capability dropped, the way OpenShift runs it. It fails when `/up` does not answer an IP `Host`, when the image carries a `.env` or a config cache, when forged `X-Forwarded-*` headers from an untrusted peer change the answer, when logs go anywhere but stdout and stderr, when the app writes outside `storage/`, `bootstrap/cache/` and the tmpfs paths, or when `SIGTERM` does not stop it by itself. If your platform's settings differ from the job's (`.gitlab-ci.yml`), the job's `env` input is where to try them first.

Note

`php artisan optimize` works in WELD since SESAME 0.6.1, whose `/login` and `/logout` are controller actions instead of closures, so `route:cache` can serialize them (WELD locks SESAME 0.6.2). Under SESAME 0.6.0 the route cache ran out of memory and the container stopped; an app still on 0.6.0 starts with `SQUIRE_ARTISAN_OPTIMIZE=0` until it updates. The same holds for the optimize step in `scripts/release.sh` on Citadel.

Four tables, one bead: if the joint passes `weld:doctor` on all of them, the seam holds.
