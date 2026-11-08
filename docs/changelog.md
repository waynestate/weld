---
title: "Changelog"
description: All notable changes to this project.
---

<!-- Generated from CHANGELOG.md by bin/docs-site.php. Edit CHANGELOG.md, not this file. -->

# Changelog

Notable changes to WELD, newest first.

## Unreleased

- Docs: a "Which command when" page (Reference) and README section put the look-alike commands side by side (`weld:doctor`, `squire doctor`, `proctor check`, `proctor gate`, `oracle:check` and the rest): what each checks, when to run it and where it runs. It is the canonical copy; the READMEs of Squire, PROCTOR, Sourdough, Booster and Rollcall carry it word for word (D-064).

## 0.1.5 (2026-11-07)

- Fixed: the committed Ziggy route list (`resources/js/ziggy.js` and `ziggy.d.ts`) includes SESAME 0.5's `sesame.status` route, so the `ziggy` job is green again.
- Fixed: `composer proctor` and `ProctorCheckTest` run plain `proctor check`; PROCTOR 0.7 refuses `--policy` in CI, which turned `proctor-root` and every `php-test` job red.
- Fixed: the pipeline's PROCTOR jobs no longer pass `--policy`, which PROCTOR 0.7 refuses in CI; PROCTOR checks against the CREED policy it bundles.

## 0.1.4 (2026-11-07)

- Changed: the front page deploy layer (`deploy/`) runs Squire's kit 0.6.0 (was 0.5.0): `scripts/release.sh` now asks PROCTOR for a go or no-go right after `composer audit`, before the front end builds and the migrations run (the verdict lands in `proctor-gate.json` under `SQUIRE_PROCTOR_RECORD_DIR`, ratcheted against the last release that went on; `SQUIRE_PROCTOR=enforce` stops the release on a no-go, the default is report only), and `.env.squire.example` gains `SQUIRE_PROCTOR_RECORD_DIR`. The squire holds the gate; the proctor says who may pass.
- Changed: the GitLab pipeline pins the Preflight components to the 0.2.0 release SHA (was 0.1.4).
- Changed: the stack pins move up: HELMET `^0.4` (locked 0.4.0), SESAME `^0.5` (locked 0.5.0) and PROCTOR `^0.7` (locked 0.7.0). SESAME 0.5 binds each sign-in to the browser that started it, so `config/sesame.php` gains `require_pending_login` (on by default; off only for an app that must accept a ticket for a login it did not start) and the fake driver answers `jane.doe` when `SESAME_FAKE_USERNAME` is unset.
- Added: `tests/Feature/SkeletonTest.php`, the skeleton tests Sourdough ships with its Inertia template: `/up`, the guest and signed-in routes, the 404 page, and the resolver's round trip from a JSON session back to the User. WELD's branded Welcome page is asserted where the skeleton shows Home.
- Changed: the regen baseline rebalances for the 0.6.0 kit: `config/sesame.php` converges byte for byte, `docker-compose.squire.yml` and `scripts/release.sh` sit under `deploy/` as relocated copies, and `tests/Feature/SkeletonTest.php` joins the accepted drift (52 known paths now).
- Fixed: the front page and the Sourdough guide say Sourdough bakes all five app shapes (they said four, with raw PHP plus a Vue SPA still waiting). "Pick your path" gives that shape its own card (`sourdough new my-app --raw --vue`), and the WELD card spans the row's last two cells.
- Fixed: the README, "What is welded in" and the status page name Pinstripe 0.1.4 as the release on Packagist (they said 0.1.1).
- Changed: the front page's deploy layer (`deploy/`) runs Squire's kit 0.5.0 (was 0.4.0): `.squire/base-fallback.Dockerfile` and `scripts/parity.ps1` take the new templates, and `docker-compose.squire.yml`, which Renovate had edited, is Squire's template again, so `squire init --check` passes.
- Changed: The canonical repository is git.wayne.edu (ADR-0021); docs, links and package metadata no longer point at the archived nougat copy.
- Changed: the GitLab `regen-gate` job blocks: `allow_failure` is gone, so new drift against Sourdough's main fails the pipeline. Fix the drift here or upstream in Sourdough, or record the current drift as accepted with `--write-baseline` and commit `.sourdough/regen-baseline.json`; the GitHub pipeline keeps `continue-on-error`.
- Fixed: the README says Sourdough bakes five shapes, including raw PHP with a Vue SPA, which ships; it no longer says that shape is not available yet.
- Fixed: `scripts/a11y.ps1` and its `.sh` twin mount the repo read-write instead of read-only plus a named volume for `tools/a11y/node_modules`: Docker Desktop refuses the nested volume, so on Windows the script failed before the container started. `node_modules` is gitignored, so the tree stays clean and npm still installs once and reuses it.
- Changed: WELD reconciles with Sourdough main (`323a82b`). `App\Sesame\UserResolver` adopts the template's username lookup (the same fix for Laravel 13's JSON sessions as WELD's id lookup; the docblock keeps WELD's name), and the regen baseline records four product drifts (`docker-compose.squire.yml`, where renovate keeps the haproxy digest newer than the Squire template copy; `eslint.config.js`; `AppHeader.vue`; `navigation.ts`) while dropping 17 paths that converged with the WayneUI-wired template or left it (53 known paths now). The full report of the run against `323a82b` sits at `.sourdough/regen-report-2026-11-07.txt`.
- Added: an accessibility gate for the site: `scripts/a11y.sh` (Git Bash, Linux, macOS) and `scripts/a11y.ps1` (Windows) run axe-core (WCAG 2.2 AA, light and dark, at 360 px and 1280 px) in Docker over every committed HTML page under `site/`, so the Proof page's hand-run check is repeatable with one command; the first full run checked all 23 pages with 0 violations.
- Added: The Proof page records its last run.

## 0.1.3 (2026-11-06)

- Changed: the Proof page marks the 0.1.2 install and the regen drift as fixed.
- Fixed: every `php-test` job in the GitLab pipeline (PHP 8.3, 8.4, 8.5) crashed with exit 255 since the move to Pest. Preflight's component runs `vendor/bin/phpunit` unless told otherwise, and PHPUnit refuses to load a Pest file ("Please run ./vendor/bin/pest instead"); the jobs now run `vendor/bin/pest`, which runs the PHPUnit class tests too. Sourdough's Laravel templates got the same fix.
- Fixed: the suite printed 62 warnings in CI, where there is no `.env` (Pest reports phpdotenv's suppressed read of the missing file). `tests/TestCase.php` now boots through `tests/Support/SkipEnvFile.php`, which reads `.env` when it exists and `.env.example` otherwise, the same files Sourdough's Inertia template ships.
- Changed: `App\Support\DummyAppKey` and its call in `AppServiceProvider` now come from Sourdough's Laravel templates, so every Sourdough app refuses the dummy test key in production too. The regen gate reports no new drift, and `.sourdough/regen-baseline.json` shrinks from 87 to 65 known paths (35 files now match Sourdough byte for byte, up from 29).
- Fixed: the two `DummyAppKey` tests that only proved "no exception" by not crashing now assert it, so the suite reports no risky tests.

- Added: a Proof page (Reference, `docs/reference/proof.md`). On 2026-11-06 a new app was made from an empty folder both documented ways, every gate WELD ships was run locally and timed, an axe-core accessibility gate checked four pages at two widths in light and dark (0 violations), and one small Wayne State app was ported onto a fresh clone. It lists what held, what broke, and what it does not prove.
- Fixed: the first page after `up` was blank. The Vite container ran `vite --host`, so `public/hot` said `http://[::]:5173`, which the browser cannot reach; `compose.yaml` now runs `npm run dev` with `LARAVEL_SAIL: '1'`, and the plugin writes `http://localhost:5173`. `tests/Unit/LocalStackTest.php` holds it.
- Fixed: on Windows every second `bin\weld.ps1 up` emptied the local SQLite database (`New-Item -Force` truncates an existing file). It now creates the file only when it is missing; the same test holds it, and the macOS and Linux script, which uses `touch`, is pinned too.
- Changed: Install and the README say that `composer create-project` installs the newest tag, which can trail `main`; Troubleshooting covers the blank page, a new page Vite cannot find on Windows (restart Vite), and the emptied database.
- Changed: the README's note about the Citadel deploy layer moves from above the title into Documentation.
- Security: the front-page deploy layer moves to Symfony 7.4.23 (symfony/http-foundation and symfony/yaml advisories, including CVE-2026-118514).
- Changed: dark docs are the shop after hours instead of a green wallpaper: a steel field and steel-plate pages with cream type and gold links, green kept for the helmet and the footer band. The theme now restates every kit dark token, so WayneUI's dark green surface no longer leaks in. Numbered steps sit on gold discs along a dashed seam, as on the front page; each page title lands hot and cools; a pager card runs a bead along its top on hover or focus (motion only under `prefers-reduced-motion: no-preference`). Wide tables keep a readable notes column and show a shade at the edge that scrolls.
- Fixed: on a phone the header (front page and docs) takes two rows, so the version chip no longer rides over the WELD name.
- Changed: WayneUI draws the app. `@waynestate/wayne-ui-css` and `@waynestate/wayne-ui-vue` 2.0.8 are npm dependencies pinned exactly (the lock's sha512 integrity is the checksum), `resources/css/app.css` imports WayneUI's layered stylesheet, and every page wears its components: the app bar with the shield and the theme toggle, the sidebar that becomes a focus-trapping drawer on phones, breadcrumbs, cards, fields, the delete-account dialog and the footer. The welcome page is new: the WELD wordmark over a gold seam that draws itself once, and the six stops of a request lighting up along it (still and finished with reduced motion). All 14 `TODO(wayneui)` stand-ins are resolved.
- Changed: the appearance setting is WayneUI's own theme switch (`wsu-theme`), so the settings page and the bar's sun and moon button stay in step, a pre-paint script (under HELMET's nonce) stops the dark-mode flash, and an old `appearance` choice carries over once.
- Removed: the starter kit's Tailwind theme and its helpers: `tailwind.config.js`, `cn()` (`resources/js/lib/utils.ts`), `clsx`, `tailwind-merge`, `@headlessui/vue`, `lucide-vue-next`, `@vueuse/core`, `prettier-plugin-tailwindcss`, `@tailwindcss/forms` and the 16 kit components they served.
- Added: BEST_PRACTICES W2 (WayneUI is the only component source) is `Checked`. ESLint bans imports from a `components/ui` path and the kit's helper packages, `tests/Feature/WayneUiOnlyTest.php` fails on a `components.json`, a Tailwind dependency or a loose WayneUI pin, and a new `frontend` CI job (GitLab and GitHub) runs `npm ci`, lint, Prettier, vue-tsc and the build. Ziggy's client now comes from the `ziggy-js` npm package, so the front end builds without `vendor/`.
- Fixed: Save on the profile page and Delete on the account dialog did nothing in a browser. Laravel's JSON sessions hand SESAME's stored user back as an array, so the profile called the user's own email "already taken" and deletion answered 403; `App\Sesame\UserResolver` now implements SESAME's `RefreshesUser` and looks the user up again on each request (a user deleted elsewhere is signed out). Script-side `route()` calls threw "route is not defined" since the route list moved into the bundle; `app.ts` defines the global again.
- Fixed: HELMET's environment ribbon is a polite status region, so it is no longer content outside every landmark (axe: region), and a link followed from the open phone drawer no longer leaves the ribbon `inert`.
- Changed: the regen baseline records the front-end files that now differ from Sourdough's Tailwind-based Inertia template, until Sourdough's template takes the same WayneUI wiring.
- Changed: the docs pages wear the front page's look: the same header and main menu (Docs marked current), each page a cream sheet on the green field, commands on the quick start's terminal with its gold Copy button, the weld bead under every title, branded callouts and a derived dark mode. Motion only for readers who allow it. Sources: `docs/_header.html`, `docs/theme/weld.css`, the `theme` key in `docs/docs.json`.
- Fixed: code on the front page's Sourdough card is cream, so it meets AA contrast on the deep green.
- Changed: the front page header has a main nav that includes Docs.
- Changed: the test APP_KEY is an obvious dummy (32 zero bytes), labeled test-only, and `App\Support\DummyAppKey` makes the app refuse it when `APP_ENV=production`; tests cover both.
- Changed: the regen gate treats Squire kit files that WELD keeps under `deploy/` as relocated (not drift), and `oracle:check` finds the check file at the app root or under `deploy/`.
- Changed: WELD adopts Pest 4 for new tests: the PHPUnit class tests run unchanged, `composer test` runs Pest over the whole suite, `tests/Pest.php` is configured, the example feature test is converted, and `pestphp/pest` is required at `^4` because Pest 5 needs PHP 8.4.
- Changed: CI pins the Preflight components to the 0.1.4 release SHA, so PROCTOR's preflight-pinned check passes.

- Changed: the GitHub Actions in WELD's CI (`actions/checkout`, `shivammathur/setup-php`) are pinned to commit SHAs with the tag in a comment, so PROCTOR's `ci-pipelines-unpinned-uses` warning is gone; setup-php moves from the unpublished `@v3` to 2.40.0.

- Added: a docs site (site/docs/) built by the shared docs builder from docs/, checked in composer ci.
- Changed: the regeneration baseline shrinks from 66 to 62 known-drift paths now that Sourdough no longer ships `resources/js/app.js`, `welcome.blade.php` or a phantom `vite.config.js`, and the `.editorconfig` matches.

- Fixed: `npm run lint` passes on the shipped code: the dead `badgeDir` constant leaves `scripts/readme-art/build.mjs`, and Squire's kit bytes (`site/assets/agent-ready.js`, `site/assets/stack-env.js`, with the unused `catch (e)` bindings and dead variables fixed in Squire's kit) are synced in, so the ESLint config no longer excludes them.
- Changed: `npm run lint` is now a pure check (`eslint .`); a new `npm run fix` script runs `eslint . --fix`.
- Fixed: TrustedProxiesTest no longer depends on a local .env file. The trustProxies() closure in bootstrap/app.php runs when the HTTP kernel resolves on the first request, after the test's app refresh has re-read .env into the process environment, so the .env that composer create-project writes clobbered the value the test set and one of the four tests failed. The helper now re-asserts TRUSTED_PROXIES after the refresh, so the suite is green with a .env present and without one.
- Changed: the README and front page say Sourdough bakes four shapes today (raw PHP, Laravel with Blade, Laravel with Blade plus Vue islands, and Laravel with Inertia and Vue) and only raw PHP with a Vue SPA is not available yet; the front page gains a card for the islands shape. WELD is the Inertia and Vue shape, already assembled with every part wired.
- Changed: the front-page deploy layer pins HELMET ^0.3, PROCTOR ^0.4 and CREED ^0.1 (lock: HELMET 0.3.0, PROCTOR 0.4.0, CREED 0.1.6).
- Changed: CI pins Preflight 0.1.4 (GitLab component includes and the GitHub reusable workflow at its tag commit); the deploy layer sets minimum-stability to stable.
- Changed: HELMET moves to ^0.3 and PROCTOR to ^0.4 (lock: HELMET 0.3.0, PROCTOR 0.4.x, CREED 0.1.6, ADVISOR 0.1.5), so the newest PROCTOR checks run on WELD itself; an empty `HELMET_PROXIES=` now trusts nobody. Tests, style, analysis and PROCTOR pass unchanged.

## 0.1.2 (2026-11-03)

- Changed: SESAME moves from 0.2 to 0.4 (`^0.4`). It now logs sign-in events (`sesame.login`, `sesame.logout`, with a per sign-in `session_ref`; turn off with `SESAME_LOG=false`), re-reads the signed-in user when the resolver asks for it, and the gateway driver refuses an `at+jwt` access token. WELD itself needed no code change.
- Fixed: the user menu gains a Sign out item on SESAME's logout route, the app bar branding drops the Laravel Starter Kit name and links (WELD logo text, waynestate/weld repository, weld.apps.wayne.edu documentation), .editorconfig covers compose.yaml and its variants, ExampleTest stops booting the framework, and UiBrandingTest pins the branding.
- Changed: docs and package metadata name nougat as the canonical repository, with git.wayne.edu and GitHub as read-only mirrors, until git.wayne.edu has CI runners.
- Changed: Laravel's trusted proxy list now comes from the TRUSTED_PROXIES environment variable (unset keeps Citadel's 172.18.0.1, set but empty trusts none, `*` trusts all), instead of a literal address. Citadel behaviour is unchanged.
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
- Added: the regen gate, which makes "WELD is the prebuilt Inertia shape" checkable: `bin/regen-check.php` compares a freshly generated Sourdough Inertia app (its `.sourdough/manifest.json`, path to SHA-256) with WELD file by file, skipping `.sourdough/product-files.json` entries and any `vendor` or `node_modules` path, and fails only on drift not already recorded in `.sourdough/regen-baseline.json` (a ratchet; `--write-baseline` records the current drift). The logic lives in `App\Support\RegenCheck` with unit tests, `composer regen-check` passes arguments through (not part of `ci`), and warning-only `regen-gate` jobs in both pipelines generate a fresh app from Sourdough's main and run the gate.

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
