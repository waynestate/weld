# Changelog

Notable changes to WELD, newest first.

## Unreleased

## 0.1.0 (2026-10-29)

- `waynestate/helmet` moves from `^0.1` to `^0.2` (locked at 0.2.3). The published `config/helmet.php`
  re-syncs with the package: `HELMET_PRESET` fills the proxy list (an unknown name throws at boot),
  and the security headers middleware joins the global chain, so every response carries the baseline.
  The trusted-header set narrows to `X-Forwarded-For` and `X-Forwarded-Proto`; `X-Forwarded-Port` is
  no longer trusted, because Citadel's HA-Proxy never sends it.
- `php artisan weld:doctor`: local setup only: the app key, writable storage, the database and
  pending migrations. The standards checks (PHP version, debug in production, secure session
  cookies) belong to PROCTOR, and the report points at `composer proctor`.
- Ziggy's route list leaves the inline `<script>` from `@routes` and moves into the Vite bundle: `composer ziggy`
  generates `resources/js/ziggy.js` (plus `ziggy.d.ts`), the app entry imports it and passes it to `ZiggyVue`, so
  `route()` works in Vue exactly as before while HELMET's strict CSP stays closed. A CI job regenerates the file and
  fails on any diff, and a feature test pins the contract that every script tag on the app page carries a nonce.
- PROCTOR is welded in from Packagist (`waynestate/proctor` dev dependency, CREED's `policy.json` carried by
  `waynestate/creed`): `composer proctor` measures WELD against the version policy, and a CI job runs it on every
  pipeline. Warnings pass, failures block. The package's own bin looks for its autoloader where only a project-root
  install puts one, so `composer proctor` runs directly without a temporary launcher.
- SESAME is welded in from Packagist (`waynestate/sesame`): sign-in moves from the starter kit's password forms to
  `/login` and `/logout`, owned by the package. The fake driver signs in locally; pointing at CAS is an env change.
  A `username` column joins `users`, and `App\Sesame\UserResolver` imports the identity on first sign-in.
- HELMET is welded in from Packagist (`waynestate/helmet`): security headers and CSP on every web response, a
  published `config/helmet.php`, trusted proxies in `bootstrap/app.php`, and the Vite build asks for the per-request
  nonce. `/up` stays Laravel's built-in health route. Every web response now wears the baseline.
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
