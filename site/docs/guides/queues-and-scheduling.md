---
title: "Queues and scheduling | WELD docs"
description: "Queues and scheduling in WELD: how jobs and scheduled tasks run, and what changes between local and deploy."
url: https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Queues and scheduling [#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#queues-and-scheduling)

This page lists the queue and scheduling practices for WELD without Citadel workers, from the BEST_PRACTICES guide, with the tag that says whether the stack holds each one today.

## W18. Database queue by default; the worker is a process type [#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w18-database-queue-by-default-the-worker-is-a-process-type)

**Practice:** `QUEUE_CONNECTION=database`; run `php artisan queue:work --max-time=3600 --tries=3` as the `worker` process where the platform allows one. Use `#[Tries]`, `#[Timeout]` and `#[Backoff]` on jobs.

**Why:** It needs no extra service, and `--max-time` recycles memory.

**Stack:** `Checked` for the job attributes. `.env.example:67` sets `QUEUE_CONNECTION=database`, `config/queue.php:16` defaults to it, and the jobs tables are migrated by `database/migrations/0001_01_01_000002_create_jobs_table.php:14`. `tests/Feature/JobLimitsTest.php` fails the suite on a queued job under `app/Jobs` without `#[Tries]` and `#[Timeout]` (on the class, a parent or a trait; the older `$tries` and `$timeout` properties count too), proves the guard on fixture jobs, and passes while there are no jobs, which is WELD today. `stubs/job.queued.stub` and `stubs/job.batched.queued.stub` make `php artisan make:job` write `#[Tries(3)]` and `#[Timeout(45)]`. Byte for byte from Sourdough's Inertia shape, held by the regen gate. The worker process type is the portable image's `worker` role (`queue:work --tries=3 --max-time=3600`, W8). Open: nothing asks for `#[Backoff]`, so a failed job retries at once unless it sets one.

## W19. Where no worker can run, run the queue from the scheduler [#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w19-where-no-worker-can-run-run-the-queue-from-the-scheduler)

**Practice:** On Citadel (no queue workers), schedule `queue:work --stop-when-empty --max-time=50 --timeout=45 --tries=3` every minute with `withoutOverlapping` and a short expiry. Select it with `QUEUE_WORKER_MODE=scheduler` (`none` leaves the queue to a supervisor).

**Why:** `--stop-when-empty` is documented for Docker use ("shutdown the container after the queue is empty"), and the scheduler is the one cron entry Citadel can run. This exact pattern is an inference, not a documented recipe.

**Stack:** `Checked`. `routes/console.php` schedules `queue:work --stop-when-empty --max-time=50 --timeout=45 --tries=3` every minute with `withoutOverlapping(10)` when `config('worker.mode')` is `scheduler` (`config/worker.php`, env `QUEUE_WORKER_MODE`, default `scheduler` in `.env.example`); `tests/Feature/QueueWorkerScheduleTest.php` checks both modes. Byte for byte from Sourdough, held by the regen gate.

## W20. Timeout below `retry_after` [#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w20-timeout-below-retry-after)

**Practice:** Set each job timeout lower than the connection's `retry_after`. Timeouts need the PCNTL extension.

**Why:** Otherwise the job is attempted again before it has finished.

**Stack:** `Checked`. `routes/console.php` passes `--timeout=45`, and `tests/Feature/QueueWorkerScheduleTest.php` fails when that is not below the default connection's `retry_after` (or, for `sync`, below every connection's; Laravel's default is 90). Each job's own timeout is held the same way: `tests/Feature/JobLimitsTest.php` fails on a job under `app/Jobs` whose `#[Timeout]` (or `$timeout`) is at or above that `retry_after` ("keeps every job timeout below the queue connection retry_after"; "catches a job timeout at or above retry_after" proves it on a 600-second fixture), and holds the `make:job` stubs' 45 below it too. The deploy image installs `pcntl` (`deploy/.squire/base-fallback.Dockerfile`). Byte for byte from Sourdough, held by the regen gate.

## W21. One scheduler entry; one run per task [#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w21-one-scheduler-entry-one-run-per-task)

**Practice:** Run `php artisan schedule:run` every minute (cron, or a CronJob), or `schedule:work` for a single long-lived container. With more than one scheduler, mark tasks `onOneServer()` (shared cache) and name closures.

**Why:** Three schedulers run a nightly report three times.

**Stack:** `Checked`. `tests/Feature/SchedulerNamingTest.php` (Sourdough's copy) fails the suite on any scheduled closure without `->name()`, and its second case proves the guard catches one.

## W22. Interrupt and restart long-lived processes at deploy [#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w22-interrupt-and-restart-long-lived-processes-at-deploy)

**Practice:** After a release run `php artisan queue:restart` (or `php artisan reload`), and `schedule:interrupt` when sub-minute tasks exist.

**Why:** Workers and a minute-long `schedule:run` keep the old code until they exit.

**Stack:** `Checked`. The Squire release step "laravel restart long-lived processes" (`deploy/scripts/release.sh:563`) runs `php artisan reload` where Laravel has it, otherwise `queue:restart` and `schedule:interrupt`, each when its own `--help` answers; report only, and `SQUIRE_ARTISAN_RESTART=0` turns the step off. `tests/Feature/ReleaseRestartTest.php` holds the commands and the switch in place.

## W23. Make a silent scheduler visible [#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w23-make-a-silent-scheduler-visible)

**Practice:** Record a heartbeat on every scheduler minute and fail `/ready` (not `/up`) when it is older than a few minutes. Export the age as a Prometheus gauge (off by default).

**Why:** With no worker supervisor, a stopped scheduler is otherwise invisible.

**Stack:** `Checked` for the heartbeat. `routes/console.php:17-19` stamps the cache every minute in a closure named `scheduler-heartbeat`, and `bootstrap/app.php:31` puts `App\Health\SchedulerHeartbeat` on HELMET's `/ready` beside the database and cache checks (W11), which answers 503 while the stamp is missing or older than `worker.heartbeat_max_age` (`config/worker.php:31`, env `WORKER_HEARTBEAT_MAX_AGE`, default 180 seconds; 0 turns the check off for an app that runs no scheduler). `tests/Feature/SchedulerHeartbeatTest.php` proves it: "answers 503 on /ready when the scheduler has not written a heartbeat", "answers 200 on /ready right after the heartbeat closure runs", "answers 503 on /ready again once the clock moves past the max age" (the fake clock), "answers 200 on /ready with no heartbeat when the check is turned off" and "schedules the heartbeat every minute under its own name". Byte for byte from Sourdough, held by the regen gate. Open: the Prometheus gauge is not built, and nothing probes `/ready` yet: Squire's `health.php` (Citadel's Health Check and the compose healthcheck) asks `/up`, so only point a traffic probe at `/ready` where `schedule:run` really runs.

## W24. Run migrations exactly once per release [#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w24-run-migrations-exactly-once-per-release)

**Practice:** Use a Job, a pre-deploy hook (Coolify "Pre-deployment Command"), or `migrate --isolated`. Never run `migrate` in a per-replica entrypoint.

**Why:** Parallel replicas race on schema changes.

**Stack:** `Checked`. On Citadel the release step runs `php artisan migrate --force` (`deploy/scripts/release.sh:550`) once per release, from the first-release hook (`deploy/deployments/001-first-release.sh`, which only calls the release script) and the Quick Update post-script (`deploy/scripts/release.sh:7`); Citadel's entrypoint runs no artisan command (`deploy/.squire/fallback/citadel-apache-entrypoint`). Off Citadel the portable image migrates only in its `migrate` role (`deploy/.squire/portable/squire-entrypoint.sh:157-167`), a one-shot service the `web`, `worker` and `scheduler` services wait for (`service_completed_successfully`, `deploy/docker-compose.squire.yml:194-235`). `tests/Feature/MigrateOncePerReleaseTest.php` fails when a kit update moves `migrate` anywhere else. PROCTOR's `entrypoint-migrate` check still warns on the portable entrypoint (it lets a `migrate)` case branch through, not a `run_migrate` function the branch calls), so that one warning is expected; the portability CI job does not run the `migrate` role, but migrates its own test database once, in its own container, before the two replicas start (`setup_command`, `.gitlab-ci.yml:113`).

## How to read the tags [#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#how-to-read-the-tags)

Each practice ends with exactly one tag.

| Tag | Meaning |
| --- | --- |
| `Enforced` | WELD's code does it or refuses to run without it, and a test covers it. |
| `Checked` | PROCTOR or CI warns or fails. |
| `Documented` | Written down in the README or a config file, with no check behind it. |
| `Not yet` | A gap. |

## Related [#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#related)

- [App structure](https://weld.apps.wayne.edu/docs/guides/app-structure.html)
- [The container contract](https://weld.apps.wayne.edu/docs/guides/container-contract.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
