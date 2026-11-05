---
title: "Check your setup | WELD docs"
description: "weld:doctor strikes an arc across the local setup and reports every joint: PASS, WARN or FAIL, one line per check."
url: https://weld.apps.wayne.edu/docs/guides/check-your-setup.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Check your setup[#](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html#check-your-setup)

Use `weld:doctor` to strike an arc across the local setup and see, one line per check, whether every joint holds.

## What it checks[#](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html#what-it-checks)

`weld:doctor` strikes an arc across the local setup and reports every joint: the app key, writable storage, the database and pending migrations. One line per check, PASS or WARN or FAIL. The checks, by name:

- `app-key`: PASS when `APP_KEY` is set; FAIL when it is empty, with a pointer to `php artisan key:generate`.
- `storage-writable`: PASS when `storage` and `bootstrap/cache` are both writable; FAIL when either is missing or not writable.
- `database`: PASS when the connection is up; FAIL names the exception class only, never the DSN, host or password.
- `migrations`: PASS when there are no pending migrations; WARN when some are pending (it lists them) or when it could not inspect them.

## Run it[#](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html#run-it)

Run it through the OS scripts or straight to the container:

```powershell
.\bin\weld.ps1 doctor
```

```sh
bin/weld.sh doctor
```

```sh
docker compose run --rm app php artisan weld:doctor
```

## Read the report[#](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html#read-the-report)

Each check prints one line: PASS, WARN or FAIL. The exit code is 1 when anything FAILs and 0 otherwise, so it works as a gate. Add `--json` to any of them for a machine-readable report. A good bead is a quiet one: all PASS and nothing to say.

Note

The standards checks (PHP version, debug in production, secure session cookies) belong to PROCTOR: run `composer proctor`, and the doctor report says so.

## Related[#](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html#related)

- [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html)
- [Oracle](https://weld.apps.wayne.edu/docs/guides/oracle.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
