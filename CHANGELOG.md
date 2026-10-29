# Changelog

Notable changes to WELD, newest first.

## Unreleased

- `docs/running-off-citadel.md`: the off-Citadel contract: OpenShift, Coolify and plain Docker, with Citadel for
  comparison. Traffic and trusted proxies per platform, `php://stderr` logs, writable paths, non-root user, `/up`,
  and `weld:doctor` after every deploy. Same bead, four different tables.
- README: one line under "Run it locally" linking the guide.
- `php artisan weld:doctor`: checks the app is set up safely, one PASS, WARN or FAIL line per check
  (PHP version, app key, debug in production, secure session cookies, writable storage, database,
  pending migrations); exits 1 when anything FAILs, `--json` for machines. A good bead is a quiet
  one: all PASS and nothing to say.
- `bin/weld.ps1` and `bin/weld.sh`: a `doctor` subcommand runs it in the app container.
- README: "Check your setup".
- `compose.yaml`: the local stack: the app (PHP and Composer in one image) and
  Vite, on pinned images, SQLite per `.env.example`, ports bound to 127.0.0.1 only.
- `bin/weld.ps1` (Windows, PowerShell 5.1 and 7) and `bin/weld.sh` (Linux and
  macOS, POSIX sh): `up`, `down`, `test` and `logs`, the same subcommands on
  every OS.
- README: "Run it locally" is now one command per OS.
