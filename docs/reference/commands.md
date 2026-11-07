---
title: Commands
description: The commands a WELD app runs: the composer scripts, the artisan commands and the bin/weld subcommands.
---

# Commands

This page is the reference for every command a WELD app runs: the `bin/weld` subcommands, the composer scripts, and the artisan and bin PHP commands, with what each one does.

## The bin/weld subcommands

One command strikes the spark; the containers do the welding. Docker is the only prerequisite: PHP and Node run in the containers, so the host needs neither. The same subcommands work on every OS.

| Command | What it does |
|---|---|
| `up` | Copy `.env.example` to `.env` if missing, install the PHP and Node dependencies in the containers, write `.mcp.json` from ADVISOR, start the app and Vite, generate the app key if needed, and migrate |
| `down` | Stop and remove the containers |
| `test` | Run `composer ci` in the app container |
| `doctor` | Run `php artisan weld:doctor` in the app container |
| `logs` | Follow the container logs; Ctrl+C stops |

> [!TIP]
> `up` finishes with a pointer to open http://localhost:8000. Skip the ADVISOR step with `--no-advisor` (`-NoAdvisor` on Windows).

Bring the whole stack up:

```powershell tab="PowerShell"
.\bin\weld.ps1 up
```
```sh tab="sh"
bin/weld.sh up
```

Run the full check suite in the app container:

```powershell tab="PowerShell"
.\bin\weld.ps1 test
```
```sh tab="sh"
bin/weld.sh test
```

Check your setup:

```powershell tab="PowerShell"
.\bin\weld.ps1 doctor
```
```sh tab="sh"
bin/weld.sh doctor
```

Follow the container logs:

```powershell tab="PowerShell"
.\bin\weld.ps1 logs
```
```sh tab="sh"
bin/weld.sh logs
```

Stop and remove the containers:

```powershell tab="PowerShell"
.\bin\weld.ps1 down
```
```sh tab="sh"
bin/weld.sh down
```

## Composer scripts

| Script | What it does |
|---|---|
| `composer test` | Pest, running the PHPUnit class tests and the Pest tests side by side |
| `composer lint` | Run Pint as a check (`pint --test`) |
| `composer fix` | Apply Pint fixes (`pint`) |
| `composer analyse` | Run PHPStan analysis (`phpstan analyse --memory-limit=1G`) |
| `composer proctor` | PROCTOR against CREED's policy |
| `composer ziggy` | Confirm the Ziggy routes are current: `composer ziggy`, then `git diff --exit-code resources/js/ziggy.*` |
| `composer regen-check` | Compare a freshly generated Sourdough Inertia app with WELD by SHA-256 and fail only on new drift versus `.sourdough/regen-baseline.json`; pass `--generated <dir>` (and optionally `--write-baseline`) through |
| `composer ci` | Runs `composer validate --strict`, the tests, Pint, PHPStan, the agent-ready and docs checks and `composer audit`; it does not run PROCTOR, Ziggy or the JS lint |
| `composer site:agent-ready` | Rewrite the Markdown twins, llms.txt, llms-full.txt, robots.txt and sitemap.xml in site/ (Squire's agent-ready kit) |
| `composer site:agent-ready-check` | Fail when the agent-ready files are stale, a page lacks its Markdown link, a prompt drifts from the docs, or the kit was edited here |
| `composer docs` | Write site/docs/ from docs/ with the shared docs builder, and generate docs/changelog.md from CHANGELOG.md |
| `composer docs:check` | Fail when the committed docs site is stale or the docs carry findings; writes nothing |

## Artisan and bin PHP commands

### `php artisan weld:doctor`

`weld:doctor` strikes an arc across the local setup and reports every joint: the app key, writable storage, the database and pending migrations. One line per check, PASS or WARN or FAIL; exit code 1 when anything FAILs, so it works as a gate. The standards checks (PHP version, debug in production, secure session cookies) belong to PROCTOR: run `composer proctor`, and the doctor report says so. A good bead is a quiet one: all PASS and nothing to say.

Add `--json` to any of these for a machine-readable report. Run it through the `doctor` subcommand above, or straight to the container:

```powershell tab="PowerShell"
.\bin\weld.ps1 doctor
```
```sh tab="sh"
bin/weld.sh doctor
```
```sh tab="Docker"
docker compose run --rm app php artisan weld:doctor
```

### `php bin/regen-check.php`

`bin/regen-check.php` compares a freshly generated Sourdough Inertia app (its `.sourdough/manifest.json`, each path with its SHA-256) with WELD file by file, skipping the product files listed in `.sourdough/product-files.json` and any `vendor` or `node_modules` path. Each remaining path is reported as IDENTICAL, DIFFERS, MISSING_IN_WELD or RELOCATED. The gate is a ratchet: `.sourdough/regen-baseline.json` records the drift already known and accepted, and the check fails only on new drift. It is not part of `composer ci`; CI runs it as a separate job that blocks on new drift.

- Run the check: `php bin/regen-check.php --generated <dir>` (or `composer regen-check -- --generated <dir>`)
- Record the current drift as accepted: add `--write-baseline`

### `php bin/agent-ready.php`

- `php bin/agent-ready.php`: rewrite the Markdown twins, llms.txt, llms-full.txt, robots.txt and sitemap.xml in site/ (Squire's agent-ready kit).
- `php bin/agent-ready.php --check`: fail when the agent-ready files are stale, a page lacks its Markdown link, a prompt drifts from the docs, or the kit was edited here.

These run as `composer site:agent-ready` and `composer site:agent-ready-check`.

### `php bin/docs-site.php`

- `php bin/docs-site.php`: write site/docs/ from docs/ with the shared docs builder, and generate docs/changelog.md from CHANGELOG.md.
- `php bin/docs-site.php --check`: fail when the committed docs site is stale or the docs carry findings; writes nothing.

These run as `composer docs` and `composer docs:check`. The docs are built from `docs/` by the shared docs builder and checked in `composer ci`, so the page you read is the page that ships.

## Related

- [Check your setup](../guides/check-your-setup.md)
- [Is WELD still Sourdough's shape?](../guides/regen-check.md)
- [WELD and Sourdough](../guides/sourdough.md)
- [Running WELD off Citadel](../running-off-citadel.md)
- [Status](status.md)
- [Configuration](configuration.md)
