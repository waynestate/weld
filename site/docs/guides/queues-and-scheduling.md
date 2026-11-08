---
title: "Queues and scheduling | WELD docs"
description: "Queues and scheduling in WELD: how jobs and scheduled tasks run, and what changes between local and deploy."
url: https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Queues and scheduling[#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#queues-and-scheduling)

This page lists the queue and scheduling practices for WELD without Citadel workers, from the BEST_PRACTICES guide, with the tag that says whether the stack holds each one today.

## W18. Database queue by default; the worker is a process type[#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w18-database-queue-by-default-the-worker-is-a-process-type)

**Practice:** `QUEUE_CONNECTION=database`; run `php artisan queue:work --max-time=3600 --tries=3` as the `worker` process where the platform allows one. Use `#[Tries]`, `#[Timeout]` and `#[Backoff]` on jobs.

**Why:** It needs no extra service, and `--max-time` recycles memory.

**Stack:** `Documented`. `.env.example:67` sets `QUEUE_CONNECTION=database`, `config/queue.php:16` defaults to it, and the jobs tables are migrated by `database/migrations/0001_01_01_000002_create_jobs_table.php:14`. No job classes exist yet, so the `#[Tries]`/`#[Timeout]`/`#[Backoff]` attributes are not in use. Target `Checked`: a test that every job class declares `#[Tries]` and `#[Timeout]`.

## W19. Where no worker can run, run the queue from the scheduler[#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w19-where-no-worker-can-run-run-the-queue-from-the-scheduler)

**Practice:** On Citadel (no queue workers), schedule `queue:work --stop-when-empty --max-time=50 --tries=3` every minute with `withoutOverlapping` and a short expiry. Select it with `QUEUE_WORKER_MODE=scheduler` (`none` leaves the queue to a supervisor).

**Why:** `--stop-when-empty` is documented for Docker use ("shutdown the container after the queue is empty"), and the scheduler is the one cron entry Citadel can run. This exact pattern is an inference, not a documented recipe.

**Stack:** `Checked`. `routes/console.php` schedules `queue:work --stop-when-empty --max-time=50 --tries=3` every minute with `withoutOverlapping(10)` when `config('worker.mode')` is `scheduler` (`config/worker.php`, env `QUEUE_WORKER_MODE`, default `scheduler` in `.env.example`); `tests/Feature/QueueWorkerScheduleTest.php` checks both modes. Byte for byte from Sourdough, held by the regen gate.

## W20. Timeout below `retry_after` [#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w20-timeout-below-retry-after)

**Practice:** Set each job timeout lower than the connection's `retry_after`. Timeouts need the PCNTL extension.

**Why:** Otherwise the job is attempted again before it has finished.

**Stack:** `Not yet`. No job classes exist yet. Target `Checked`: a config test comparing the two values; the image installs `pcntl`.

## W21. One scheduler entry; one run per task[#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w21-one-scheduler-entry-one-run-per-task)

**Practice:** Run `php artisan schedule:run` every minute (cron, or a CronJob), or `schedule:work` for a single long-lived container. With more than one scheduler, mark tasks `onOneServer()` (shared cache) and name closures.

**Why:** Three schedulers run a nightly report three times.

**Stack:** `Checked`. `tests/Feature/SchedulerNamingTest.php` (Sourdough's copy) fails the suite on any scheduled closure without `->name()`, and its second case proves the guard catches one.

## W22. Interrupt and restart long-lived processes at deploy[#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w22-interrupt-and-restart-long-lived-processes-at-deploy)

**Practice:** After a release run `php artisan queue:restart` (or `php artisan reload`), and `schedule:interrupt` when sub-minute tasks exist.

**Why:** Workers and a minute-long `schedule:run` keep the old code until they exit.

**Stack:** `Checked`. The Squire 0.7.0 release step "laravel restart long-lived processes" (`deploy/scripts/release.sh:461`) runs `php artisan reload` where Laravel has it, otherwise `queue:restart` and `schedule:interrupt`, each when its own `--help` answers; report only, and `SQUIRE_ARTISAN_RESTART=0` turns the step off. `tests/Feature/ReleaseRestartTest.php` holds the commands and the switch in place.

## W23. Make a silent scheduler visible[#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w23-make-a-silent-scheduler-visible)

**Practice:** Record a heartbeat on every scheduler minute and fail `/ready` (not `/up`) when it is older than a few minutes. Export the age as a Prometheus gauge (off by default).

**Why:** With no worker supervisor, a stopped scheduler is otherwise invisible.

**Stack:** `Not yet`. There is no scheduler and no `/ready` endpoint yet. Target `Checked`: a test advances a fake clock and asserts `/ready` fails.

## W24. Run migrations exactly once per release[#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#w24-run-migrations-exactly-once-per-release)

**Practice:** Use a Job, a pre-deploy hook (Coolify "Pre-deployment Command"), or `migrate --isolated`. Never run `migrate` in a per-replica entrypoint.

**Why:** Parallel replicas race on schema changes.

**Stack:** `Documented`. The release step runs `php artisan migrate --force` (`deploy/scripts/release.sh:448`) once per release, from the first-release hook (`deploy/deployments/001-first-release.sh:25`) and the Quick Update post-script (`deploy/scripts/release.sh:7`); the entrypoint itself runs no artisan commands (`deploy/.squire/fallback/citadel-apache-entrypoint`). Target `Checked`: PROCTOR flags `migrate` in an entrypoint.

## How to read the tags[#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#how-to-read-the-tags)

Each practice ends with exactly one tag.

| Tag | Meaning |
| --- | --- |
| `Enforced` | WELD's code does it or refuses to run without it, and a test covers it. |
| `Checked` | PROCTOR or CI warns or fails. |
| `Documented` | Written down in the README or a config file, with no check behind it. |
| `Not yet` | A gap. |

## Related[#](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html#related)

- [App structure](https://weld.apps.wayne.edu/docs/guides/app-structure.html)
- [The container contract](https://weld.apps.wayne.edu/docs/guides/container-contract.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
