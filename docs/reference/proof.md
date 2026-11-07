---
title: "Proof"
description: "WELD made into a new app from a clean folder on 2026-11-06, both documented ways, with every gate it ships run locally and timed, plus one small Wayne State app ported onto it: what held, what broke, what was fixed, and what it does not prove."
---

# Proof

This page says how WELD was proven on 2026-11-06: a new app made from a clean folder the way this site tells you to, every gate WELD ships run against it, and one small Wayne State app ported onto it. It lists what held, what broke, what was fixed that night, and what it does not prove yet. Next, see the [Quick start](../guides/quick-start.md) for the steps the run followed and [Troubleshooting](../troubleshooting.md) for the fixes it produced.

Welders prove a joint with a bend test: cut a strip across the seam, bend it double, and look for cracks. WELD's promise is that a Wayne State developer gets a full, working, accessible app quickly with the whole stack already joined. So the strip was a brand-new app, and the bending was done by a stopwatch, the app's own gates and a browser.

## Last run

Run on one Windows machine with Docker Desktop; durations are wall clock, including dependency installs where a command makes them.

| Check | Date | Commit | Result | Duration |
|---|---|---|---|---|
| `composer ci` (Pest, Pint, PHPStan level 8, agent-ready, docs site, audit) | 2026-11-07 | `e555f03` | Pass: 90 tests (261 assertions), none risky; 67 files formatted; no PHPStan errors; no advisories | 163 s (warm vendor) |
| `composer proctor` | 2026-11-07 | `baf8e60` | Pass, 1 warning: the PHP 8.3 floor is in security-only support until 2027-12-31 | 176 s |
| `ziggy` job | 2026-11-07 | `baf8e60` | Pass: the regenerated route list matches the committed one | 174 s |
| `frontend` job (ESLint, Prettier, vue-tsc, Vite) | 2026-11-07 | `baf8e60` | Pass: lint, format and types clean; 168 modules built | 27 s |
| Accessibility gate (axe-core, WCAG 2.2 AA) | 2026-11-07 | `e555f03` | Pass: 23 pages, 0 violations, light and dark at 360 px and 1280 px | 70 s |
| Regen gate (a fresh `sourdough new --inertia` compared with WELD) | 2026-11-07 | `e555f03` | Pass: no new drift against Sourdough main `323a82b`; every differing or missing path is in the baseline | 60 s |
| `weld:doctor` | 2026-11-07 | `baf8e60` | Pass: 4 PASS (app key, storage, database, migrations) | 21 s |

Refresh it by re-running those seven checks at the current HEAD, writing down the new date and commit, and rebuilding the site with `composer docs` and `composer site:agent-ready`. The `composer ci`, accessibility and regen rows above are from this refresh at `e555f03`: the regen gate now passes against Sourdough's current main (`323a82b`), with no new drift beyond the recorded baseline, and the accessibility gate runs as `scripts/a11y.ps1` is written on Docker Desktop, checking all 23 committed pages with 0 violations. The `create-project` path, the signed-in app-page accessibility checks and the `up` timings are not in the table: the first installs the newest public tag instead of this commit, and the rest need the running stack and a browser.

## What ran

One Windows 11 machine with Docker Desktop, Node 24 and no PHP or Composer on the host, which is the setup the [Quick start](../guides/quick-start.md) says is enough. Each path started from an empty folder. Every step was timed and logged, and nothing was retried to make a number look better.

1. **The `create-project` path** from [Install](../install.md): `composer create-project waynestate/weld` (run in the `composer:2.10.3` image, since the host has no Composer), `npm ci`, then `bin\weld.ps1 up` and `test`.
2. **The clone path** from the [Quick start](../guides/quick-start.md): `git clone` from the GitHub mirror at `main` (`6da94b9`), `bin\weld.ps1 up`, then the pages in a browser and every gate WELD ships, run locally the way its CI runs them:
   - `bin\weld.ps1 doctor` and `bin\weld.ps1 test` (`composer ci`: validate, Pest, Pint, PHPStan level 8, the agent-ready and docs checks, audit);
   - `composer proctor` against CREED's policy, the `proctor-root` CI job;
   - the `frontend` CI job: ESLint, Prettier, vue-tsc and the Vite build;
   - the `ziggy` CI job: regenerate the route list and fail on any diff;
   - the regen gate: a fresh `sourdough new --inertia` compared with WELD file by file;
   - an accessibility gate: axe-core with the WCAG 2.0, 2.1 and 2.2 A and AA rules on the welcome page, the dashboard and both settings pages, signed in through SESAME's fake driver, at desktop and phone width, light and dark, with reduced motion on (16 page checks).
3. **A port** onto the fresh clone: one small app from the stack's proof corpus, below.

## Results

The clone path, the one the Quick start teaches:

| Step | Time | Result |
|---|---|---|
| `git clone` | 2 s | `main` at `6da94b9` |
| `bin\weld.ps1 up`, first time | 238 s | Up: Composer and npm installs, the app and Vite started, key generated, migrations run |
| Open `http://localhost:8000` | | **Blank page.** Only HELMET's `local` ribbon drew. Fixed the same night, below |
| The pages, after the fix | 10 to 20 s for the first load, then instant | Welcome, dashboard, profile and appearance all drawn by WayneUI; SESAME's fake driver signed in as the fake user |
| `bin\weld.ps1 doctor` | 6 s | 4 PASS: app key, storage, database, migrations |
| `bin\weld.ps1 test` (`composer ci`) | 153 s | Pass: 85 tests (2 marked risky), Pint, PHPStan level 8, docs and agent-ready current, no advisories |
| `composer proctor` | 6 s | Pass, 1 warning (the PHP 8.3 floor is in security-only support) |
| `frontend` job | 72 s | Pass |
| `ziggy` job | 7 s | Pass: the committed route list matches |
| Regen gate | 15 s | Fails with 3 paths of new drift; the GitLab job blocks on new drift. Below |
| Accessibility gate | 65 s | **0 violations** in 16 page checks; 26 to 29 rules passed on each page |
| A second `bin\weld.ps1 up` | 188 s | **Emptied the local database.** Fixed the same night, below |

From an empty folder to a signed-in WayneUI dashboard took about four and a half minutes of machine time once the blank page was fixed, and to every gate green about eight. Your network and disk will move both numbers; the shape will not change much, because the first `up` is mostly Composer and npm downloading.

The `create-project` path:

| Step | Time | Result |
|---|---|---|
| `composer create-project waynestate/weld` | 156 s | Installed **0.1.2**, the newest tag (2026-11-03): 67 commits behind `main`, from before WayneUI drew the app |
| `npm ci` | 14 s | Pass |
| `bin\weld.ps1 up` | 159 s | Up; the same blank page |
| `bin\weld.ps1 test` | | **Fails.** PROCTOR's `composer-audit` check found 7 advisories in Symfony components locked in 0.1.2, published after the tag, and one trusted-proxy test failed. `main` passes both |

That is PROCTOR doing its job on a three-day-old lock: the gate caught advisories nobody had to go looking for. It is also a reason not to start from 0.1.2; 0.1.3, tagged later the same day, carries the patched lock and WayneUI.

## WayneUI in the pages

After the fix, every page of the new app was WayneUI's: the app bar with the shield and the theme switch, the sidebar that turns into a drawer on a phone, breadcrumbs, cards, the form fields and the footer, in light and dark. Tab moved through the bar with a visible gold focus ring. The axe gate, run with reduced motion on, found nothing to report on any page, at either width, in either scheme.

## A port: one small app onto WELD

The second workload asked a plainer question: how much of a real app's work is left once WELD has done its part? The app was the smallest useful one in the stack's proof corpus: a read-only JSON feed, about 100 lines of raw PHP with no framework, one Oracle query and two optional filters, with its own error shape for bad input.

On the fresh clone it became:

- a controller that keeps the old contract exactly: the same query string, the same validation and the same `{"message", "error": true}` body with a 400 for bad input, the same row keys, and nulls turned into empty strings as the old feed did;
- a `ProgramSource` interface with a fake source of made-up rows for local work and tests, and an Oracle source that runs the old query, kept outside the repository, on WELD's `oracle` connection;
- a new page drawn by WayneUI that reads the same JSON: a filter card with a native select and a text field, and WayneUI's sortable data table with a caption;
- nine Pest tests for the contract.

| What | Result |
|---|---|
| Size | 369 lines added across 16 files, including the tests and the regenerated route list |
| Time | 11 minutes 20 seconds of wall clock for the agent that wrote it, from first file to last check; a person new to Laravel will take longer |
| `composer ci` | Pass first time apart from formatting: Pint fixed 2 style issues, Prettier 1 file. PHPStan level 8 passed the new code as written |
| `composer proctor` | Pass, the same single warning |
| `frontend` job | Pass after Prettier |
| Accessibility gate on the new page | 0 violations at both widths and both schemes; 31 to 32 rules passed |

What WELD supplied without a line of the port's own: sign-in in front of the page, HELMET's security headers on every response (the JSON included), the database connection for the real query, WayneUI for every pixel, and the gates that judged the result.

Two honest differences from the old app. A POST now gets Laravel's own 405 body instead of the old JSON one. And the Oracle source was never run: the proof never connects to Oracle or Banner, so the real query is only shown to be wired, not to work.

## What it caught in WELD

A run that only finds praise was not looking hard enough. This one found these, and every finding is about WELD, not the ported app:

- **Fixed: a blank page on the first open.** The Vite container ran `vite --host`, so Laravel's Vite plugin wrote the address it listened on, `http://[::]:5173`, into `public/hot`, and every page asked the browser to load its scripts from an address it cannot reach. `compose.yaml` now sets `LARAVEL_SAIL=1` instead, which makes the plugin listen on all addresses in the container and hand the browser `http://localhost:5173`. Checked by `tests/Unit/LocalStackTest.php` and by the pages rendering on the next run.
- **Fixed: every second `up` on Windows emptied the local database.** `bin\weld.ps1 up` created the SQLite file with `New-Item -Force`, which truncates a file that already exists. One signed-in user before the second `up`, none after. It now creates the file only when it is missing (macOS and Linux used `touch` and were never affected). Checked by the same test and by a later `up` with the fix, which kept the user.
- **Fixed: `create-project` installed 0.1.2.** The newest tag was from before WayneUI drew the app, and its locked Symfony components now carry advisories. 0.1.3 (2026-11-06) is the fix, and the pipeline it was tagged from was green.
- **Open: a new page on Windows says "Page not found".** Docker Desktop passes edits to existing files into the Vite container, but not new files, so Inertia cannot find a page added after `up` until Vite restarts: `docker compose restart vite`. Polling would see new files, but it took 21 seconds to notice a save and kept the Vite container at about a third of one CPU, so it is not the default. See [Troubleshooting](../troubleshooting.md).
- **Open: the regen gate.** Run by hand, today's Sourdough stops before generating, at its check for read access to Playbook, a private package; in CI that check is skipped and the app is generated with Playbook wired, which WELD does not require. With CI set, the gate reported 3 paths of new drift on top of the recorded baseline. Fixed the same day: WELD took Sourdough's `SkipEnvFile` and `TestCase`, Sourdough took WELD's `DummyAppKey`, and the gate reports no new drift. Sourdough's Inertia template still draws with Tailwind while WELD draws with WayneUI, so the front-end files stay in the baseline until that template moves to WayneUI.
- **Open: a warm `up` still reinstalls.** The second `up` took 188 seconds because it runs `composer install`, ADVISOR's install and a full `npm ci` again. It is correct, just slow; a faster path for an app that is already up is worth having.
- **Open, small: Sign in lands on the welcome page.** SESAME signs the user in and returns them to `/`, where the button now reads Open the dashboard. One more click than it needs.

## What this does not prove

- Only Windows ran. `bin/weld.sh` on macOS and Linux was not exercised, apart from reading that it creates the SQLite file with `touch`.
- Nothing was deployed. Citadel, Squire's release script and the container contract are proven elsewhere, not here.
- SESAME ran its fake driver only; CAS and Entra ID were not part of this run.
- No real data, Oracle or Banner. The port's real query was never executed.
- One port of one small app is a sample of one. A larger app, with forms that write, would take longer and find more.
- The timings are from one machine on one night. Treat them as the shape, not a promise.

## Run it yourself

The same bend test on your machine, from an empty folder:

```powershell tab="Windows"
git clone https://github.com/waynestate/weld.git my-app
cd my-app
.\bin\weld.ps1 up
.\bin\weld.ps1 doctor
.\bin\weld.ps1 test
docker compose run --rm app composer proctor
```

```sh tab="macOS / Linux"
git clone https://github.com/waynestate/weld.git my-app
cd my-app
bin/weld.sh up
bin/weld.sh doctor
bin/weld.sh test
docker compose run --rm app composer proctor
```

Then open http://localhost:8000, sign in, and run `bin\weld.ps1 up` (or `bin/weld.sh up`) a second time: your data should still be there.

## Related

- [Quick start](../guides/quick-start.md)
- [Install](../install.md)
- [Check your setup](../guides/check-your-setup.md)
- [Status](status.md)
- [Troubleshooting](../troubleshooting.md)
