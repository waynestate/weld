---
title: "Citadel limits | WELD docs"
description: "The platform facts an app design depends on when it runs on Citadel: MySQL 5.6.51, no separate queue worker, no sidecars, and Squire's php.ini limits."
url: https://weld.apps.wayne.edu/docs/guides/citadel-limits.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Citadel limits [#](https://weld.apps.wayne.edu/docs/guides/citadel-limits.html#citadel-limits)

This page lists the platform facts an app design depends on when it runs on Citadel, each with its source. Design around them and the deploy is a formality; discover them at deploy time and the weld cracks.

## MySQL 5.6.51 [#](https://weld.apps.wayne.edu/docs/guides/citadel-limits.html#mysql-5-6-51)

Citadel shares one MySQL 5.6.51 server. Two limits bite at design time:

- No JSON or `vector` columns. Store JSON as `text` or `longText` with an Eloquent `array` or `json` cast, and filter in PHP (CREED's databases handbook).
- Indexed utf8mb4 strings at most 191 characters. `Schema::defaultStringLength(191)` keeps index keys under the 767-byte cap; WELD sets it in `app/Providers/AppServiceProvider.php:32`.

The `mysql56-migrate` job runs `php artisan migrate` against a real 5.6.51 service on every pipeline, because the SQLite tests take a JSON column or a long utf8mb4 index that 5.6 rejects at deploy (`.gitlab-ci.yml:50-56`).

## No separate queue worker [#](https://weld.apps.wayne.edu/docs/guides/citadel-limits.html#no-separate-queue-worker)

Citadel runs no queue worker process. The scheduler drains the queue instead: `QUEUE_WORKER_MODE=scheduler` (the `.env.example` default) schedules a short `queue:work --stop-when-empty --max-time=50 --tries=3` every minute (W19, `docs/guides/queues-and-scheduling.md:20-24`; `docs/running-off-citadel.md:46`). Design jobs to be short; do not assume a long-lived worker.

## No sidecars [#](https://weld.apps.wayne.edu/docs/guides/citadel-limits.html#no-sidecars)

One container per app. WELD deploys on Citadel through Squire as one container per app, and builds run at release time inside the app container (`docs/running-off-citadel.md:8`); traffic comes in through Citadel's front-end proxy, HA-Proxy (`docs/running-off-citadel.md:40`). The database is the one Citadel shares, the queue rides the scheduler, and the proxy sits in front; nothing runs beside the app.

## Squire's php.ini limits [#](https://weld.apps.wayne.edu/docs/guides/citadel-limits.html#squire-s-php-ini-limits)

Squire's `php.ini` (the safe defaults HELMET also enforces at runtime) sets, among others:

- `memory_limit = 128M` (`deploy/.squire/php.ini:20`)
- `max_execution_time = 30` (`deploy/.squire/php.ini:18`)
- `output_buffering = 4096` (`deploy/.squire/php.ini:6`)

Size uploads and long-running work around them.

## Related [#](https://weld.apps.wayne.edu/docs/guides/citadel-limits.html#related)

- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
- [The container contract](https://weld.apps.wayne.edu/docs/guides/container-contract.html)
- [Queues and scheduling](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html)
