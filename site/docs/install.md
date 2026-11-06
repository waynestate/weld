---
title: "Install | WELD docs"
description: "How to get WELD: composer create-project or clone, then one Docker command brings the app, PHP and Node up."
url: https://weld.apps.wayne.edu/docs/install.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Install[#](https://weld.apps.wayne.edu/docs/install.html#install)

This page gets you a working copy of WELD: what it needs, the two ways to get it, and how to verify the result.

## Requirements[#](https://weld.apps.wayne.edu/docs/install.html#requirements)

- PHP `^8.3`
- Laravel `^13.29`
- Node, for `npm ci`
- Docker, for the local stack

Note

To run WELD locally, Docker is the only prerequisite: PHP and Node run in the containers, so the host needs neither.

## Get it[#](https://weld.apps.wayne.edu/docs/install.html#get-it)

WELD is an app you start from, not a library you require. The Megazord ships in a repo, not a box. Composer makes you a fresh copy, writes `.env`, generates the app key and runs the first migration:

```sh
composer create-project waynestate/weld my-app
cd my-app
npm ci
```

Note

`create-project` installs the newest tagged release, and `main` can be ahead of it: the [Changelog](https://weld.apps.wayne.edu/docs/changelog.html) lists what is unreleased. On 2026-11-06 the newest tag, 0.1.2, was from before WayneUI drew the app (see [Proof](https://weld.apps.wayne.edu/docs/reference/proof.html)). Clone when you want `main`.

Or clone it, if you want the history too, and it is yours to rename:

```sh
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

Note

The `advisor install` line writes `.mcp.json`, the MCP config that points Claude Code at ADVISOR's server; the file is git-ignored, because a committed `.mcp.json` would run a program on whoever opens the project. Set `ADVISOR_ENABLED=1` in `.env` to switch the advisor on.

## Verify[#](https://weld.apps.wayne.edu/docs/install.html#verify)

Run the checks the way CI does. `composer ci` is the CI contract: it runs validate, tests, Pint, PHPStan level 8, the agent-ready check and audit, the same command on GitLab and GitHub. The `test` subcommand runs it in the app container: `.\bin\weld.ps1 test` on Windows (PowerShell), `bin/weld.sh test` on macOS / Linux.

The doctor strikes an arc across the local setup: `.\bin\weld.ps1 doctor` on Windows (PowerShell), `bin/weld.sh doctor` on macOS / Linux. It reports one line per check, PASS or WARN or FAIL, and exits with code 1 when anything FAILs, so it works as a gate. A good bead is a quiet one: all PASS and nothing to say.

## Next steps[#](https://weld.apps.wayne.edu/docs/install.html#next-steps)

- [Quick start](https://weld.apps.wayne.edu/docs/guides/quick-start.html): bring the stack up with one command and open the app.
- [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html): the parts already hooked up in your copy.
- [Check your setup](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html): what the doctor reports, joint by joint.

## Related[#](https://weld.apps.wayne.edu/docs/install.html#related)

- [Overview](https://weld.apps.wayne.edu/docs/index.html)
- [Quick start](https://weld.apps.wayne.edu/docs/guides/quick-start.html)
- [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html)
- [Check your setup](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html)
