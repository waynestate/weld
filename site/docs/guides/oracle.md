---
title: "Oracle | WELD docs"
description: "How WELD speaks Oracle through yajra/laravel-oci8: the settings, oracle:check, and the parity image for a real connection."
url: https://weld.apps.wayne.edu/docs/guides/oracle.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Oracle[#](https://weld.apps.wayne.edu/docs/guides/oracle.html#oracle)

Set up and check WELD's Oracle connection: the settings, the plain-language check, and the parity image for a real connection from your machine.

## The wiring[#](https://weld.apps.wayne.edu/docs/guides/oracle.html#the-wiring)

WELD speaks Oracle through `yajra/laravel-oci8`, and the wiring is already in: an `oracle` connection in `config/database.php`, a check command, and plain-language sentences for the common errors. Until you set the Oracle settings it stays dormant: nothing connects and nothing errors.

## Set the settings[#](https://weld.apps.wayne.edu/docs/guides/oracle.html#set-the-settings)

Fill in one set of names in `.env` (the commented block in `.env.example`): `ORACLE_USERNAME`, `ORACLE_PASSWORD`, and either `ORACLE_TNS` (an Easy Connect string, `host:port/service`, or a full descriptor) or `ORACLE_HOST`, `ORACLE_PORT` and `ORACLE_SERVICE_NAME`. Leave them empty if your app does not use Oracle; nothing connects until they are set. On a server, set them in the app's environment settings, never in a file in git.

## Check the connection[#](https://weld.apps.wayne.edu/docs/guides/oracle.html#check-the-connection)

Check the settings and the connection in plain language, one line per finding:

```
php artisan oracle:check
```

`oracle:check` is a thin wire to Squire's shared check file: it runs `deploy/oracle-check.php` with the current environment, streams the plain-language lines straight through, and returns the check's exit code, 0 when Oracle is not configured or all passed and 1 when a problem was found. The check file promises to never print a setting value. WELD does not own the check; a kit refresh (`vendor/bin/squire update`) puts the file here. If the check file is not there yet, the command tells you to run `vendor/bin/squire update` to get it.

## A real connection from your machine[#](https://weld.apps.wayne.edu/docs/guides/oracle.html#a-real-connection-from-your-machine)

The local `app` container runs the stock Composer image, which has no Oracle driver. When you need a real connection from your machine, the Oracle-ready image is opt-in through `compose.oracle.yaml`:

PowerShell

```powershell
$env:WELD_ORACLE_IMAGE = "squire/oracle-parity:8.3"
docker compose -f compose.yaml -f compose.oracle.yaml up
```

macOS / Linux

```sh
WELD_ORACLE_IMAGE=squire/oracle-parity:8.3 docker compose -f compose.yaml -f compose.oracle.yaml up
```

The overlay swaps only the app's image and passes the `ORACLE_*` settings through from `.env`; the ports stay bound to `127.0.0.1`. It fails closed when `WELD_ORACLE_IMAGE` is unset.

`squire/oracle-parity:8.3` is the name Squire documents for its parity image; build it once with Squire's `scripts/oracle-image-build.sh --flavour parity --php 8.3` (the name is a placeholder until a registry is chosen).

## Related[#](https://weld.apps.wayne.edu/docs/guides/oracle.html#related)

- [What is welded in](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html)
- [Check your setup](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
