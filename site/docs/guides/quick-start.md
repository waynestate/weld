---
title: "Quick start | WELD docs"
description: "Get a new app running in Docker, check the setup with weld:doctor, and plan the rename."
url: https://weld.apps.wayne.edu/docs/guides/quick-start.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Quick start[#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#quick-start)

This page takes a fresh copy of WELD from a folder to a running, checked app on your machine.

One command strikes the spark; the containers do the welding. Docker is the only prerequisite: PHP and Node run in the containers, so the host needs neither.

## Steps[#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#steps)

1. Bring the stack up:
   PowerShell
   ```powershell
   .\bin\weld.ps1 up
   ```
   macOS / Linux
   ```sh
   bin/weld.sh up
   ```
   Expected: `up` copies `.env.example` to `.env` if it is missing, installs the PHP and Node dependencies in the containers, starts the app and Vite, generates the app key if it is not set, and runs the migrations.
2. Open [http://localhost:8000](http://localhost:8000). Expected: the app, served by the local stack.
3. Run the doctor:
   PowerShell
   ```powershell
   .\bin\weld.ps1 doctor
   ```
   macOS / Linux
   ```sh
   bin/weld.sh doctor
   ```
   Expected: one line per check, PASS or WARN or FAIL; exit code 1 when anything FAILs. A good bead is a quiet one: all PASS and nothing to say.
4. Run the checks:
   PowerShell
   ```powershell
   .\bin\weld.ps1 test
   ```
   macOS / Linux
   ```sh
   bin/weld.sh test
   ```
   Expected: `composer ci` runs in the app container: validate, tests, Pint, PHPStan level 8, the agent-ready check and audit.

## The subcommands[#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#the-subcommands)

The same subcommands work on every OS:

| Command | What it does |
| --- | --- |
| `up` | Bring the whole stack up, as above |
| `down` | Stop and remove the containers, and clear Vite's `public/hot` |
| `test` | Run `composer ci` in the app container |
| `doctor` | Run `php artisan weld:doctor` in the app container |
| `logs` | Follow the container logs |

## Run it like Citadel[#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#run-it-like-citadel)

The steps above are the everyday bench: the stock PHP image, SQLite and Vite reloading as you type, and Sail or plain Docker Compose work too. Parity does not replace them. When "it works here" has to mean "it works on Citadel", run the same folder on Citadel's own machinery with [Parity](https://parity.apps.wayne.edu/): Citadel's PHP image with its `php.ini` and `vhost.conf`, Citadel's proxy in front adding `X-Forwarded-For` and `X-Forwarded-Proto`, and MySQL 5.6, the version of Citadel's shared database server. Parity is one program for Windows, macOS and Linux on Podman (recommended) or Docker. It needs no PHP on your computer and adds one file to the app, `parity.json`. Install it once from [its site](https://parity.apps.wayne.edu/), with no administrator needed. On Windows, in PowerShell:

```powershell
irm https://parity.apps.wayne.edu/install.ps1 | iex
```

On macOS and Linux:

```sh
curl -fsSL https://parity.apps.wayne.edu/install.sh | sh
```

1. Stop the Compose stack (`down`), so Vite's dev server is not in the way, and build the front end once, because Citadel serves built assets:
   ```sh
   docker compose run --rm vite npm run build
   ```
   Expected: `down` also clears `public/hot`, the note Vite's dev server leaves so pages load from it. Left behind, it gives you a blank page under Parity.
2. Check this computer, then write the app's Citadel settings:
   ```sh
   parity check
   parity init --mysql
   ```
   Expected: `parity check` prints one line per finding, with the fix under each one that needs it. `parity init` writes `parity.json`; `--mysql` is Citadel's "MySQL: yes".
3. Add one line to `parity.json`, under `"mode"`, so the health check asks Laravel's `/up`, as Squire's `health.php` does on Citadel:
   ```json
   "health_check": "curl -fsS -o /dev/null http://127.0.0.1/up",
   ```
   Parity's default asks the front page, which needs the sessions table, and that table arrives with the migration in the next step. Without this line the first `parity up` waits five minutes and fails.
4. Bring it up, migrate as Citadel would, and test it:
   ```sh
   parity up
   parity shell --user citadel php artisan migrate
   parity test
   ```
   Expected: `parity up` builds the app behind the proxy and prints its address ([http://localhost:8080](http://localhost:8080) unless you pick a port). On a fresh database the front page answers 500 until the migration runs, so the first `parity up` ends on a warning that says so; on Windows its proxy step can time out on that page instead. Carry on with the migration: `parity test` is the word that counts. Your folder is mounted where Citadel mounts it, so an edit shows on the next request. The app gets Laravel's `DB_*` settings for the local MySQL, so `.env` keeps SQLite for the everyday bench. `parity test` asks the running app what Citadel's users would; `parity down` stops it and keeps the data.

A folder on the Windows disk is slow inside the container: in the 2026-11-09 proof run a page took about 10 seconds, and `php artisan --version` 9 seconds against a quarter of a second from the container's own disk. Slow, but it holds.

No Citadel image on this computer yet? `parity image get` downloads it with your own git.wayne.edu access and checks it byte for byte, and `parity up --fallback` builds a labeled stand-in from the official PHP image meanwhile. Parity 0.1.0 is live, and [its docs](https://parity.apps.wayne.edu/docs/) have the rest. It takes over local runs from the parity kit in Squire. Same app, same folder, now in Citadel's own image: the bead looks identical under the hood, because it is the same hood.

## What you built[#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#what-you-built)

You have WELD running locally in Docker: the app and Vite in containers, `.env` copied from `.env.example`, the app key generated and the migrations run. That is the whole Wayne State app stack joined into one application, with sign-in, security headers, checks, coding standards, CI and the WayneUI interface already hooked up. Open the app and every page you see (the welcome page, the dashboard, settings) is drawn by WayneUI's components, light or dark to match your device.

## Next steps[#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#next-steps)

- Make it yours: rename the four things that still say WELD (the composer name, the app name, the site title and the git remote), give the copy its own key, and re-run the checks.
- [Check your setup](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html): what the doctor reports, joint by joint.
- [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html): the parts and what each does in the app.
- [Run it like Citadel](https://weld.apps.wayne.edu/docs/guides/quick-start.html#run-it-like-citadel): the same folder on Citadel's image, proxy and MySQL 5.6, through Parity.
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html): for when the destination is somewhere other than a laptop.

## Related[#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#related)

- [Overview](https://weld.apps.wayne.edu/docs/index.html)
- [Install](https://weld.apps.wayne.edu/docs/install.html)
- [Check your setup](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html)
- [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html)
