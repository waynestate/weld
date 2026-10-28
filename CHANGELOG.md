# Changelog

Notable changes to WELD, newest first.

## Unreleased

- `compose.yaml`: the local stack — the app (PHP and Composer in one image) and
  Vite, on pinned images, SQLite per `.env.example`, ports bound to 127.0.0.1 only.
- `bin/weld.ps1` (Windows, PowerShell 5.1 and 7) and `bin/weld.sh` (Linux and
  macOS, POSIX sh): `up`, `down`, `test` and `logs`, the same subcommands on
  every OS.
- README: "Run it locally" is now one command per OS.
