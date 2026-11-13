---
title: "Quick start | WELD docs"
description: "Run a fresh copy of WELD locally with Parity, on Citadel's own PHP image, then check it with weld:doctor and composer ci."
url: https://weld.apps.wayne.edu/docs/guides/quick-start.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Quick start [#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#quick-start)

This page takes a fresh copy of WELD from a folder to a running, checked app on your machine.

Start with Parity: it runs every stack app locally (the stack's D-082). Parity is Citadel on your computer: Citadel's PHP image with its `php.ini` and `vhost.conf`, Citadel's proxy in front adding `X-Forwarded-For` and `X-Forwarded-Proto`, and MySQL 5.6, the version of Citadel's shared database server. It is one program for Windows, macOS and Linux, needs no PHP on your computer, and adds one file to the app, `parity.json`. One command strikes the spark; the containers do the welding.

You need Docker with the Compose v2 plugin: `docker compose version` must answer, because Parity calls exactly that (see [Install](https://weld.apps.wayne.edu/docs/install.html#requirements)). PHP and Node run in containers, so the host needs neither.

## Run it like Citadel [#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#run-it-like-citadel)

1. Install Parity once from [its site](https://parity.apps.wayne.edu/), with no administrator needed:
   PowerShell
   ```powershell
   irm https://parity.apps.wayne.edu/install.ps1 | iex
   ```
   macOS / Linux
   ```sh
   curl -fsSL https://parity.apps.wayne.edu/install.sh | sh
   ```
2. Prepare the folder once: a `.env`, the PHP and Node dependencies, the app key, and the front end built, because Citadel serves built assets. These run in the stock Composer and Node containers from `compose.yaml`:
   PowerShell
   ```powershell
   Copy-Item .env.example .env
   docker compose run --rm app composer install
   docker compose run --rm app php artisan key:generate
   docker compose run --rm vite sh -c 'npm ci && npm run build'
   ```
   macOS / Linux
   ```sh
   cp .env.example .env
   docker compose run --rm app composer install
   docker compose run --rm app php artisan key:generate
   docker compose run --rm vite sh -c 'npm ci && npm run build'
   ```
   Skip the copy when the folder already has a `.env`.
3. Check this computer, then write the app's Citadel settings:
   ```sh
   parity check
   parity init --mysql
   ```
   Expected: `parity check` prints one line per finding, with the fix under each one that needs it. `parity init` writes `parity.json`; `--mysql` is Citadel's "MySQL: yes". `bin/weld.sh up` and `.\bin\weld.ps1 up` print `parity init --from-squire (first time only; plain parity init if there is no Squire kit)`. In WELD, Squire's kit sits in `deploy/` rather than at the root, so use plain `parity init`, as above.
4. Add one line to `parity.json`, under `"mode"`, so the health check asks Laravel's `/up`, as Squire's `health.php` does on Citadel:
   ```json
   "health_check": "curl -fsS -o /dev/null http://127.0.0.1/up",
   ```
   Parity's default asks the front page, which needs the sessions table, and that table arrives with the migration in the next step. Without this line the first `parity up` waits five minutes and fails.
5. Bring it up, migrate as Citadel would, and test it:
   ```sh
   parity up
   parity shell --user citadel php artisan migrate
   parity test
   ```
   Expected: `parity up` builds the app behind the proxy and prints its address ([http://localhost:8080](http://localhost:8080) unless you pick a port). On a fresh database the front page answers 500 until the migration runs, so the first `parity up` ends on a warning that says so; on Windows its proxy step can time out on that page instead. Carry on with the migration: `parity test` is the word that counts. Your folder is mounted where Citadel mounts it, so an edit shows on the next request. The app gets Laravel's `DB_*` settings for the local MySQL from Parity, so `.env` keeps its own database settings. `parity test` asks the running app what Citadel's users would.
6. Run the doctor and the checks. These two still run through `bin/weld`, in the app container:
   PowerShell
   ```powershell
   .\bin\weld.ps1 doctor
   .\bin\weld.ps1 test
   ```
   macOS / Linux
   ```sh
   bin/weld.sh doctor
   bin/weld.sh test
   ```
   Expected: the doctor prints one line per check, PASS or WARN or FAIL, with exit code 1 when anything FAILs. A good bead is a quiet one: all PASS and nothing to say. `test` runs `composer ci`: validate, tests, Pint, PHPStan level 8, the agent-ready check and audit.

After a change, run `parity up` again. `parity logs -f` follows the logs, `parity down` stops the app and keeps the data, and `parity down --volumes` empties the database.

A folder on the Windows disk is slow inside the container: in the 2026-11-09 proof run a page took about 10 seconds, and `php artisan --version` 9 seconds against a quarter of a second from the container's own disk. Slow, but it holds.

No Citadel image on this computer yet? `parity image get` downloads it with your own git.wayne.edu access and checks it byte for byte, and `parity up --fallback` builds a labeled stand-in from the official PHP image meanwhile. [Parity's docs](https://parity.apps.wayne.edu/docs/) have the rest. Same app, same folder, now in Citadel's own image: the bead looks identical under the hood, because it is the same hood.

## The bin/weld subcommands [#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#the-bin-weld-subcommands)

`bin/weld.ps1` and `bin/weld.sh` are thin pointers for one release (the stack's D-087), and the next release deletes them:

| Command | What it does |
| --- | --- |
| `test` | Run `composer ci` in the app container |
| `doctor` | Run `php artisan weld:doctor` in the app container |
| `up`, `down`, `logs` | Print the Parity commands that replace them (`parity up`, `parity down`, `parity logs -f`) and exit 2 |

Want Vite reloading as you type instead? Sail and plain Docker Compose still work: `compose.yaml` has the app on [http://localhost:8000](http://localhost:8000) and Vite. After step 2, create `database/database.sqlite`, run `docker compose run --rm app php artisan migrate`, then `docker compose up -d`. Stop it with `docker compose down` and delete `public/hot`, the note Vite's dev server leaves so pages load from it, before you go back to Parity: left behind, it gives you a blank page there.

## What you built [#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#what-you-built)

You have WELD running locally on Citadel's own image: the app behind Citadel's proxy, MySQL 5.6 migrated as a Citadel release would, `.env` copied from `.env.example` and the app key generated. That is the whole Wayne State app stack joined into one application, with sign-in, security headers, checks, coding standards, CI and the WayneUI interface already hooked up. Open the app and every page you see (the welcome page, the dashboard, settings) is drawn by WayneUI's components, light or dark to match your device.

## Next steps [#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#next-steps)

- [Make it yours](https://weld.apps.wayne.edu/docs/guides/making-weld-yours.html): rename the parts that still say WELD (the composer name, the app name, the site title and the git remote), give the copy its own key and re-run the checks, then strip WELD's product files and adopt Sourdough so `feed` keeps working.
- [Check your setup](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html): what the doctor reports, joint by joint.
- [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html): the parts and what each does in the app.
- [Run it like Citadel](https://weld.apps.wayne.edu/docs/guides/quick-start.html#run-it-like-citadel): the steps above, for when you come back to them.
- [Citadel limits](https://weld.apps.wayne.edu/docs/guides/citadel-limits.html): the platform facts an app design depends on, with their sources.
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html): for when the destination is somewhere other than a laptop.

## Related [#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#related)

- [Overview](https://weld.apps.wayne.edu/docs/index.html)
- [Install](https://weld.apps.wayne.edu/docs/install.html)
- [Check your setup](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html)
- [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html)
