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
   ```powershell
   .\bin\weld.ps1 up
   ```
   ```sh
   bin/weld.sh up
   ```
   Expected: `up` copies `.env.example` to `.env` if it is missing, installs the PHP and Node dependencies in the containers, starts the app and Vite, generates the app key if it is not set, and runs the migrations.
2. Open [http://localhost:8000](http://localhost:8000). Expected: the app, served by the local stack.
3. Run the doctor:
   ```powershell
   .\bin\weld.ps1 doctor
   ```
   ```sh
   bin/weld.sh doctor
   ```
   Expected: one line per check, PASS or WARN or FAIL; exit code 1 when anything FAILs. A good bead is a quiet one: all PASS and nothing to say.
4. Run the checks:
   ```powershell
   .\bin\weld.ps1 test
   ```
   ```sh
   bin/weld.sh test
   ```
   Expected: `composer ci` runs in the app container: validate, tests, Pint, PHPStan level 8, the agent-ready check and audit.

## The subcommands[#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#the-subcommands)

The same subcommands work on every OS:

| Command | What it does |
| --- | --- |
| `up` | Bring the whole stack up, as above |
| `down` | Stop and remove the containers |
| `test` | Run `composer ci` in the app container |
| `doctor` | Run `php artisan weld:doctor` in the app container |
| `logs` | Follow the container logs |

## What you built[#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#what-you-built)

You have WELD running locally in Docker: the app and Vite in containers, `.env` copied from `.env.example`, the app key generated and the migrations run. That is the whole Wayne State app stack joined into one application, with sign-in, security headers, checks, coding standards, CI and the WayneUI interface already hooked up.

## Next steps[#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#next-steps)

- Make it yours: rename the four things that still say WELD (the composer name, the app name, the site title and the git remote), give the copy its own key, and re-run the checks.
- [Check your setup](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html): what the doctor reports, joint by joint.
- [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html): the parts and what each does in the app.
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html): for when the destination is somewhere other than a laptop.

## Related[#](https://weld.apps.wayne.edu/docs/guides/quick-start.html#related)

- [Overview](https://weld.apps.wayne.edu/docs/index.html)
- [Install](https://weld.apps.wayne.edu/docs/install.html)
- [Check your setup](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html)
- [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html)
