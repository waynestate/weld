# MAINTAINING: keeping the seams strong

How to build, test, release and keep WELD current. WELD is the whole Wayne State app stack joined into one Laravel 13 +
Inertia/Vue app, ready to clone and deploy. Many parts, one piece; and like any weld, it is only as good as the joints,
so most of the upkeep is keeping the parts it joins current.

## What WELD is and what it depends on

- `waynestate/weld`, a Composer `project` (an app you clone), not a library. It is on Packagist, installed with `composer create-project waynestate/weld`.
- **The stack parts it joins:** `waynestate/helmet` `^0.2` and `waynestate/sesame` `^0.2` at runtime;
  `waynestate/creed` `^0.1` and `waynestate/proctor` `^0.2` for checks. WayneUI provides the interface.
- Laravel 13, Inertia 2 with Vue 3, Ziggy for routes (a committed, generated `resources/js/ziggy.*`), Vite.
- WELD is the Laravel + Inertia/Vue shape that Sourdough's templates describe, already shaped. If every app should get
  a change, it belongs in Sourdough; if only this shape should, it lives here.

## Where it lives

Development happens on git.wayne.edu, the canonical repository: [enterprise-apps/weld](https://git.wayne.edu/enterprise-apps/weld).
Changes, issues and merge requests go there; tags are made there, by the maintainer, with an approver. The GitHub
repository ([waynestate/weld](https://github.com/waynestate/weld)) is a read-only push mirror: `main` and tags are
copied automatically, nobody pushes to it, and Packagist reads from it.

## Build and test

| Task | Windows (PowerShell 5.1 or 7) | Linux and macOS |
|---|---|---|
| Start the app (Docker: installs, migrates, serves) | `.\bin\weld.ps1 up` | `bin/weld.sh up` |
| All checks (runs `composer ci` in the container) | `.\bin\weld.ps1 test` | `bin/weld.sh test` |
| Check your setup | `.\bin\weld.ps1 doctor` | `bin/weld.sh doctor` |
| Logs, stop | `.\bin\weld.ps1 logs`, `.\bin\weld.ps1 down` | `bin/weld.sh logs`, `bin/weld.sh down` |

Without Docker: `composer install`, `npm ci`, copy `.env.example` to `.env`, `php artisan key:generate`, then
`composer ci`. Other checks:

| Check | Command |
|---|---|
| PROCTOR against CREED's policy | `composer proctor` |
| Ziggy routes are current | `composer ziggy`, then `git diff --exit-code resources/js/ziggy.*` |
| JS lint and format | `npm run lint` (pure check), `npm run fix` (apply fixes), `npm run format:check` |
| Production assets | `npm run build` |

`composer ci` runs `composer validate --strict`, the tests, Pint, PHPStan and `composer audit`. It does not run
PROCTOR, Ziggy or the JS lint. `composer test` is Pest, running the PHPUnit class tests and the Pest tests side by side.
The suite boots on a fixed dummy `APP_KEY` set in `phpunit.xml`; `App\Support\DummyAppKey` refuses that value when the app runs in production.
Tests read `.env`, or `.env.example` when there is none (`tests/Support/SkipEnvFile.php`), so a bare CI container boots without warnings.

The regen gate checks that WELD is still Sourdough's Inertia shape. Generate a fresh app (`sourdough new <dir>
--inertia`), then `php bin/regen-check.php --generated <dir>` (or `composer regen-check -- --generated <dir>`).
It skips the product files listed in `.sourdough/product-files.json` and any `vendor` or `node_modules` path, and
fails only on drift that is not already recorded in `.sourdough/regen-baseline.json`; `--write-baseline` records
the current drift as accepted. Its per-file statuses are IDENTICAL, DIFFERS, MISSING_IN_WELD and RELOCATED. It is
not part of `composer ci`; CI runs it as a separate job that blocks on new drift.

Pint skips `deploy/`. That folder is the stack's shared front-page deploy layer (Squire kit plus HELMET front controller), kept byte-identical in PER style across every stack repo, so WELD's Laravel preset must not reformat it. Its style is checked where it is made.

## CI

- **GitLab:** Preflight's `php-test` (PHP 8.3, 8.4, 8.5, running `vendor/bin/pest`) and `composer-audit` components, a `composer-ci` job, a
  `proctor` job (warnings pass, failures block), a `ziggy` drift job and a `regen-gate` job (new drift fails the job;
  `.sourdough/regen-baseline.json` records the drift already accepted).
- **GitHub:** `.github/workflows/ci.yml` calls Preflight's reusable workflow with `composer ci` and runs the same
  `regen-gate` check (`continue-on-error` until the baseline is filled in).
- Green means all of those pass; on GitLab the regen gate blocks on new drift, and on GitHub it may warn while the
  baseline is being filled in. JS lint is not in CI yet.

## Keeping the joints current

- When HELMET, SESAME, CREED or PROCTOR release, raise the constraint here (`composer require waynestate/<part>:^0.x`),
  run all the checks above, and commit the lock file.
- When Laravel, Inertia or Vue release, Booster's hops are the way to move; WELD is a good first test for them.
- After route changes, regenerate Ziggy and commit the result.
- When Sourdough changes what it generates, the regen gate flags the new drift in CI. Fix it here or upstream in
  Sourdough; if the drift is accepted as intended, record it with `--write-baseline` and commit
  `.sourdough/regen-baseline.json`.

## Releasing

WELD is tagged (0.1.0 through 0.1.4 so far). To release a version:

1. Move the lines under `## Unreleased` in `CHANGELOG.md` to a new `## X.Y.Z (YYYY-MM-DD)` heading.
2. All the checks above green locally, then both pipelines green for that commit.
3. Run a secrets scan of the history and check that nothing internal (server names, people, IDs) is in the tree.
4. Tag `X.Y.Z` (no `v`) on git.wayne.edu, with an approver. The push mirror carries the tag to GitHub.
5. A release needs a maintainer's approval. Never move or delete a published tag.

## Running it elsewhere

WELD targets Citadel first. `docs/running-off-citadel.md` covers other hosts.

## The front page

`site/` is the stack's front page, for `https://weld.apps.wayne.edu/` on Citadel, served through a small raw-PHP
`deploy/` layer generated with Squire (separate from the WELD app itself).

## Who maintains it

Adam Perkins, Wayne State University Enterprise Applications. Internal runbook (accounts, tokens, mirrors, deploy
access): ask Enterprise Applications.
