---
title: Install
description: How to get WELD: composer create-project or clone, then run it locally with Parity on Docker with the Compose v2 plugin.
---

# Install

This page gets you a working copy of WELD: what it needs, the two ways to get it, and how to verify the result.

## Requirements

- PHP `^8.3`
- Laravel `^13.29`
- Node 22 or newer, for `npm ci` (`.nvmrc` says 22; `engines` in `package.json` says `>=22`)
- Docker, for the local stack: Docker Engine with the Compose v2 plugin (Docker Desktop includes it). Check it with
  `docker compose version`; Parity runs that exact command. On a Linux host with Docker 27 or older and no plugin,
  that command fails: install the `docker-compose-plugin` package.
- [Parity](https://parity.apps.wayne.edu/), which runs the app locally on Citadel's own PHP image

> [!NOTE]
> To run WELD locally, Docker and Parity are the only prerequisites: PHP and Node run in containers, so the host needs
> neither.

## Get it

WELD is an app you start from, not a library you require. The Megazord ships in a repo, not a box. Composer makes you a
fresh copy, writes `.env`, generates the app key and runs the first migration:

```sh tab="composer create-project"
composer create-project waynestate/weld my-app
cd my-app
npm ci
```

> [!NOTE]
> `create-project` installs the newest tagged release, and `main` can be ahead of it: the [Changelog](changelog.md)
> lists what is unreleased. 0.1.3 (2026-11-06) is the first tag where WayneUI draws the app; 0.1.2 was from before it
> (see [Proof](reference/proof.md)). Clone when you want `main`.

Or clone it, if you want the history too, and it is yours to rename:

```sh tab="clone"
git clone https://github.com/waynestate/weld.git my-app
cd my-app
composer install
php vendor/bin/advisor install --client=claude --write
npm ci
# Windows (PowerShell):
Copy-Item .env.example .env
# Linux/macOS:
cp .env.example .env
php artisan key:generate
```

> [!NOTE]
> The `advisor install` line writes `.mcp.json`, the MCP config that points Claude Code at ADVISOR's server; the file
> is git-ignored, because a committed `.mcp.json` would run a program on whoever opens the project. Set
> `ADVISOR_ENABLED=1` in `.env` to switch the advisor on.

No PHP or Node on your computer? Step 2 of the [Quick start](guides/quick-start.md#run-it-like-citadel) runs the same
install in the stock Composer and Node containers, and the ADVISOR line runs there too:
`docker compose run --rm app php vendor/bin/advisor install --client=claude --write`.

## Verify

Run the checks the way CI does. `composer ci` is the CI contract: it runs validate, tests, Pint, PHPStan level 8, the
agent-ready check and audit, the same command on GitLab and GitHub. The `test` subcommand runs it in the app container:
`.\bin\weld.ps1 test` on Windows (PowerShell), `bin/weld.sh test` on macOS / Linux.

The doctor strikes an arc across the local setup: `.\bin\weld.ps1 doctor` on Windows (PowerShell), `bin/weld.sh doctor`
on macOS / Linux. It reports one line per check, PASS or WARN or FAIL, and exits with code 1 when anything FAILs, so it
works as a gate. A good bead is a quiet one: all PASS and nothing to say.

## Next steps

- [Quick start](guides/quick-start.md): run the app with Parity on Citadel's own image and open it.
- [What is welded in](guides/whats-welded-in.md): the parts already hooked up in your copy.
- [Check your setup](guides/check-your-setup.md): what the doctor reports, joint by joint.

## Related

- [Overview](index.md)
- [Quick start](guides/quick-start.md)
- [What is welded in](guides/whats-welded-in.md)
- [Check your setup](guides/check-your-setup.md)
