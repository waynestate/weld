# Changelog

Notable changes to WELD, newest first.

## Unreleased

- Changed: the README and front page now say Sourdough is the starter tool for every shape of Wayne State app (raw PHP and Laravel with Blade today, the Vue shapes planned) and WELD is the Inertia and Vue shape, already assembled and ready to clone.
- Changed: CI pulls its Docker Hub images through the GitLab Dependency Proxy (the group's CI_DEPENDENCY_PROXY_GROUP_IMAGE_PREFIX), so pipelines no longer hit Docker Hub's anonymous pull limit.
- Added: `waynestate/advisor` joins `require-dev` at `^0.1`, and "Clone it and rename" gains one step: `php vendor/bin/advisor install --client=claude --write` writes `.mcp.json`, which is now git-ignored because a committed MCP config would run a program on whoever opens the project. Set `ADVISOR_ENABLED=1` in `.env` to switch the advisor on.
- Changed: `bin/weld.ps1 up` and `bin/weld.sh up` write `.mcp.json` from ADVISOR inside the app container as part of the first-run setup (no PHP on the host PATH in the Docker path), with a `--no-advisor` opt-out on both scripts (`-NoAdvisor` on the PowerShell one).
- Changed: the deploy layer's Squire kit is refreshed to 0.3.3 (release options can now live in ./.env.squire).
- Changed: the deploy layer's Squire kit is refreshed to 0.3.4 (the local .env.squire file is now git-ignored).
- Changed: the deploy layer's Squire kit is refreshed to 0.4.0: deploy/oracle-check.php arrives, so `php artisan oracle:check` runs the real check, and release.sh runs it without failing the release.
- Changed: the Oracle overlay docs name Squire's parity image, `squire/oracle-parity:8.3`.
- Added: `StackIntegrationTest` pins HELMET's security headers on a signed-in dashboard, a 404 error page and `/up`; `ProctorCheckTest` runs PROCTOR against CREED's policy.json inside the test suite, which `composer ci` alone did not cover.
- Added: README section "Clone it and rename": copy WELD, rename the composer name, APP_NAME, site title and git remote, regenerate the key and re-run the checks, for PowerShell and sh.
- Changed: BEST_PRACTICES.md re-checked against the code on 2026-11-02: the Squire kit's Dockerfile, entrypoint and release script move W10, W12, W16 and W24 from `Not yet` to `Documented`, W8/W13/W22 sentences name the new deploy files, and the shifted line numbers in composer.json, .env.example and bootstrap/app.php are corrected.

## 0.1.1 (2026-10-29)

- Added: agent-ready site (prompt blocks, Markdown twins, llms.txt), from Squire's kit.
- Fixed: stale capability claims in the site and docs.
- Changed: project names written as words except acronyms.
- `npm run typecheck` (vue-tsc, no emit) joins the scripts, and it passes: the ten errors from the first run
  are fixed in the bullets below.
- `site/assets/wayne-ui/marks/warrior-strong-reverse.svg` comes back from the WayneUI static bundle; the CSS
  has pointed at it since the front page assets landed.
- The tsconfig `types` array keeps `vite/client` and regains `./resources/js/types`: the shared interfaces
  move from `index.ts` to `index.d.ts`, which is the shape that entry resolves to. `vue/tsx` stays out,
  because it never resolved.
- `VITE_APP_NAME` is declared in a new `resources/js/env.d.ts` against vite/client's `ImportMetaEnv`,
  replacing the inline `declare module` block in the app entry.
- The app entry imports the Ziggy config from a new `ziggy-value.js` beside the generated `ziggy.js`, with a
  matching `ziggy-value.d.ts`: the generated declaration names the routes but not the value, and the value's
  string `location` predates the package's typed shape, so the declaration stops at the config minus that
  field.
- `SharedData` extends Inertia's `PageProps`, so it satisfies the constraint `usePage` puts on its type
  argument.
- AppHeader types its page with `usePage<SharedData>()`, so the user menu meets a `User` instead of
  `unknown`.
- TextLink's optional `method` prop takes Inertia's `Method` union instead of `string`.
- Welcome reads the signed-in state from `usePage<SharedData>()` instead of `$page`, which vue-tsc does not
  recognize on the component instance.

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
