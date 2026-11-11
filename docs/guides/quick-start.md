---
title: Quick start
description: Get a new app running in Docker, check the setup with weld:doctor, and plan the rename.
---

# Quick start

This page takes a fresh copy of WELD from a folder to a running, checked app on your machine.

One command strikes the spark; the containers do the welding. Docker is the only prerequisite: PHP and Node run in the
containers, so the host needs neither.

## Steps

1. Bring the stack up:

   ```powershell tab="PowerShell"
   .\bin\weld.ps1 up
   ```

   ```sh tab="macOS / Linux"
   bin/weld.sh up
   ```

   Expected: `up` copies `.env.example` to `.env` if it is missing, installs the PHP and Node dependencies in the
   containers, starts the app and Vite, generates the app key if it is not set, and runs the migrations.

2. Open http://localhost:8000. Expected: the app, served by the local stack.

3. Run the doctor:

   ```powershell tab="PowerShell"
   .\bin\weld.ps1 doctor
   ```

   ```sh tab="macOS / Linux"
   bin/weld.sh doctor
   ```

   Expected: one line per check, PASS or WARN or FAIL; exit code 1 when anything FAILs. A good bead is a quiet one:
   all PASS and nothing to say.

4. Run the checks:

   ```powershell tab="PowerShell"
   .\bin\weld.ps1 test
   ```

   ```sh tab="macOS / Linux"
   bin/weld.sh test
   ```

   Expected: `composer ci` runs in the app container: validate, tests, Pint, PHPStan level 8, the agent-ready check
   and audit.

## The subcommands

The same subcommands work on every OS:

| Command | What it does |
|---|---|
| `up` | Bring the whole stack up, as above |
| `down` | Stop and remove the containers, and clear Vite's `public/hot` |
| `test` | Run `composer ci` in the app container |
| `doctor` | Run `php artisan weld:doctor` in the app container |
| `logs` | Follow the container logs |

## Run it like Citadel

The steps above are the everyday bench: the stock PHP image, SQLite and Vite reloading as you type, and Sail or plain
Docker Compose work too. Parity does not replace them. When "it works here" has to mean "it works on Citadel", run the same folder on
Citadel's own machinery with [Parity](https://parity.apps.wayne.edu/): Citadel's PHP image with its
`php.ini` and `vhost.conf`, Citadel's proxy in front adding `X-Forwarded-For` and `X-Forwarded-Proto`, and MySQL 5.6,
the version of Citadel's shared database server. Parity is one program for Windows, macOS and Linux on Podman
(recommended) or Docker. It needs no PHP on your computer and adds one file to the app, `parity.json`.
Install it once from [its site](https://parity.apps.wayne.edu/), with no administrator needed. On Windows, in
PowerShell:

```powershell
irm https://parity.apps.wayne.edu/install.ps1 | iex
```

On macOS and Linux:

```sh
curl -fsSL https://parity.apps.wayne.edu/install.sh | sh
```

1. Stop the Compose stack (`down`), so Vite's dev server is not in the way, and build the front end once, because
   Citadel serves built assets:

   ```sh
   docker compose run --rm vite npm run build
   ```

   Expected: `down` also clears `public/hot`, the note Vite's dev server leaves so pages load from it. Left behind,
   it gives you a blank page under Parity.

2. Check this computer, then write the app's Citadel settings:

   ```sh
   parity check
   parity init --mysql
   ```

   Expected: `parity check` prints one line per finding, with the fix under each one that needs it. `parity init`
   writes `parity.json`; `--mysql` is Citadel's "MySQL: yes".

3. Add one line to `parity.json`, under `"mode"`, so the health check asks Laravel's `/up`, as Squire's `health.php`
   does on Citadel:

   ```json
   "health_check": "curl -fsS -o /dev/null http://127.0.0.1/up",
   ```

   Parity's default asks the front page, which needs the sessions table, and that table arrives with the migration
   in the next step. Without this line the first `parity up` waits five minutes and fails.

4. Bring it up, migrate as Citadel would, and test it:

   ```sh
   parity up
   parity shell --user citadel php artisan migrate
   parity test
   ```

   Expected: `parity up` builds the app behind the proxy and prints its address (http://localhost:8080 unless you pick
   a port). On a fresh database the front page answers 500 until the migration runs, so the first `parity up` ends on
   a warning that says so; on Windows its proxy step can time out on that page instead. Carry on with the migration:
   `parity test` is the word that counts. Your folder is mounted where Citadel mounts it, so an edit shows on the next
   request. The app gets Laravel's `DB_*` settings for the local MySQL, so `.env` keeps SQLite for the everyday bench.
   `parity test` asks the running app what Citadel's users would; `parity down` stops it and keeps the data.

A folder on the Windows disk is slow inside the container: in the 2026-11-09 proof run a page took about 10 seconds,
and `php artisan --version` 9 seconds against a quarter of a second from the container's own disk. Slow, but it holds.

No Citadel image on this computer yet? `parity image get` downloads it with your own git.wayne.edu access and checks it
byte for byte, and `parity up --fallback` builds a labeled stand-in from the official PHP image meanwhile. Parity 0.1.0
is live, and [its docs](https://parity.apps.wayne.edu/docs/) have the rest. It takes over local runs from the parity
kit in Squire. Same app, same folder, now in Citadel's own image: the bead looks identical under the hood, because it
is the same hood.

## What you built

You have WELD running locally in Docker: the app and Vite in containers, `.env` copied from `.env.example`, the app key
generated and the migrations run. That is the whole Wayne State app stack joined into one application, with sign-in,
security headers, checks, coding standards, CI and the WayneUI interface already hooked up. Open the app and every page
you see (the welcome page, the dashboard, settings) is drawn by WayneUI's components, light or dark to match your device.

## Next steps

- [Make it yours](making-weld-yours.md): rename the parts that still say WELD (the composer name, the app name, the
  site title and the git remote), give the copy its own key and re-run the checks, then strip WELD's product files
  and adopt Sourdough so `feed` keeps working.
- [Check your setup](check-your-setup.md): what the doctor reports, joint by joint.
- [What is welded in](whats-welded-in.md): the parts and what each does in the app.
- [Run it like Citadel](#run-it-like-citadel): the same folder on Citadel's image, proxy and MySQL 5.6, through Parity.
- [Citadel limits](citadel-limits.md): the platform facts an app design depends on, with their sources.
- [Running WELD off Citadel](../running-off-citadel.md): for when the destination is somewhere other than a laptop.

## Related

- [Overview](../index.md)
- [Install](../install.md)
- [Check your setup](check-your-setup.md)
- [What is welded in](whats-welded-in.md)
