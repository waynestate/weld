---
title: Queues and scheduling
description: Queues and scheduling in WELD: how jobs and scheduled tasks run, and what changes between local and deploy.
---

# Queues and scheduling

This page lists the queue and scheduling practices for WELD without Citadel workers, from the BEST_PRACTICES guide, with the tag that says whether the stack holds each one today.

## W18. Database queue by default; the worker is a process type

**Practice:** `QUEUE_CONNECTION=database`; run `php artisan queue:work --max-time=3600 --tries=3` as the `worker` process where the platform allows one. Use `#[Tries]`, `#[Timeout]` and `#[Backoff]` on jobs.

**Why:** It needs no extra service, and `--max-time` recycles memory.

**Stack:** `Documented`. `.env.example` sets `QUEUE_CONNECTION=database`, `config/queue.php` defaults to it, and the jobs tables are migrated by `database/migrations/0001_01_01_000002_create_jobs_table.php`. No job classes exist yet, so the `#[Tries]`/`#[Timeout]`/`#[Backoff]` attributes are not in use. Target `Checked`: a test that every job class declares `#[Tries]` and `#[Timeout]`.

## W19. Where no worker can run, run the queue from the scheduler

**Practice:** On Citadel (no queue workers), schedule `queue:work --stop-when-empty --max-time=50 --tries=3` every minute with `withoutOverlapping` and a short expiry. Select it with `WELD_QUEUE_MODE=scheduled`.

**Why:** `--stop-when-empty` is documented for Docker use ("shutdown the container after the queue is empty"), and the scheduler is the one cron entry Citadel can run. This exact pattern is an inference, not a documented recipe.

**Stack:** `Not yet`. `routes/console.php` has no scheduler entries yet (only the `inspire` command). Target `Checked`: a test that `schedule:list` shows the entry under the `citadel` preset and not under the others.

## W20. Timeout below `retry_after`

**Practice:** Set each job timeout lower than the connection's `retry_after`. Timeouts need the PCNTL extension.

**Why:** Otherwise the job is attempted again before it has finished.

**Stack:** `Not yet`. No job classes exist yet. Target `Checked`: a config test comparing the two values; the image installs `pcntl`.

## W21. One scheduler entry; one run per task

**Practice:** Run `php artisan schedule:run` every minute (cron, or a CronJob), or `schedule:work` for a single long-lived container. With more than one scheduler, mark tasks `onOneServer()` (shared cache) and name closures.

**Why:** Three schedulers run a nightly report three times.

**Stack:** `Not yet`. `routes/console.php` has no scheduler entries yet. Target `Checked`: a test fails on any scheduled closure without `->name()`.

## W22. Interrupt and restart long-lived processes at deploy

**Practice:** After a release run `php artisan queue:restart` (or `php artisan reload`), and `schedule:interrupt` when sub-minute tasks exist.

**Why:** Workers and a minute-long `schedule:run` keep the old code until they exit.

**Stack:** `Not yet`. The release script now exists (`deploy/scripts/release.sh`) but runs none of `queue:restart`, `reload` or `schedule:interrupt`. Target `Checked`: the release script lists the three commands; PROCTOR checks the file.

## W23. Make a silent scheduler visible

**Practice:** Record a heartbeat on every scheduler minute and fail `/ready` (not `/up`) when it is older than a few minutes. Export the age as a Prometheus gauge (off by default).

**Why:** With no worker supervisor, a stopped scheduler is otherwise invisible.

**Stack:** `Not yet`. There is no scheduler and no `/ready` endpoint yet. Target `Checked`: a test advances a fake clock and asserts `/ready` fails.

## W24. Run migrations exactly once per release

**Practice:** Use a Job, a pre-deploy hook (Coolify "Pre-deployment Command"), or `migrate --isolated`. Never run `migrate` in a per-replica entrypoint.

**Why:** Parallel replicas race on schema changes.

**Stack:** `Documented`. The release step runs `php artisan migrate --force` (`deploy/scripts/release.sh`) once per release, from the first-release hook (`deploy/deployments/001-first-release.sh`) and the Quick Update post-script (`deploy/scripts/release.sh`); the entrypoint itself runs no artisan commands (`deploy/.squire/fallback/citadel-apache-entrypoint`). Target `Checked`: PROCTOR flags `migrate` in an entrypoint.

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
- [The container contract](./container-contract.md)
- [Running WELD off Citadel](../running-off-citadel.md)
