---
title: Check your setup
description: weld:doctor strikes an arc across the local setup and reports every joint: PASS, WARN or FAIL, one line per check.
---

# Check your setup

Use `weld:doctor` to strike an arc across the local setup and see, one line per check, whether every joint holds.

## What it checks

`weld:doctor` strikes an arc across the local setup and reports every joint: the app key, writable storage, the database and pending migrations. One line per check, PASS or WARN or FAIL. The checks, by name:

- `app-key`: PASS when `APP_KEY` is set; FAIL when it is empty, with a pointer to `php artisan key:generate`.
- `storage-writable`: PASS when `storage` and `bootstrap/cache` are both writable; FAIL when either is missing or not writable.
- `database`: PASS when the connection is up; FAIL names the exception class only, never the DSN, host or password.
- `migrations`: PASS when there are no pending migrations; WARN when some are pending (it lists them) or when it could not inspect them.

## Run it

Run it through the OS scripts or straight to the container:

```powershell tab="PowerShell"
.\bin\weld.ps1 doctor
```

```sh tab="macOS / Linux"
bin/weld.sh doctor
```

```sh tab="Container"
docker compose run --rm app php artisan weld:doctor
```

## Read the report

Each check prints one line: PASS, WARN or FAIL. The exit code is 1 when anything FAILs and 0 otherwise, so it works as a gate. Add `--json` to any of them for a machine-readable report. A good bead is a quiet one: all PASS and nothing to say.

> [!NOTE]
> The standards checks (PHP version, debug in production, secure session cookies) belong to PROCTOR: run `composer proctor`, and the doctor report says so.

Not sure whether you want `weld:doctor`, `squire doctor` or `proctor check`? [Which command when](../reference/which-command.md) puts every look-alike side by side.

## Related

- [Which command when](../reference/which-command.md)
- [What is welded in](whats-welded-in.md)
- [Oracle](oracle.md)
- [Running WELD off Citadel](../running-off-citadel.md)
