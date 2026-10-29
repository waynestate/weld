# MAINTAINING: keeping the seams strong

How to build, test, release and keep WELD current. WELD is the whole Wayne State app stack joined into one Laravel 13 +
Inertia/Vue app, ready to clone and deploy. Many parts, one piece; and like any weld, it is only as good as the joints,
so most of the upkeep is keeping the parts it joins current.

## What WELD is and what it depends on

- `waynestate/weld`, a Composer `project` (an app you clone), not a library. It is not on Packagist.
- **The stack parts it joins:** `waynestate/helmet` `^0.1` and `waynestate/sesame` `^0.1` at runtime;
  `waynestate/creed` `^0.1` and `waynestate/proctor` `^0.2` for checks. WayneUI provides the interface.
- Laravel 13, Inertia 2 with Vue 3, Ziggy for routes (a committed, generated `resources/js/ziggy.*`), Vite.
- WELD is the Laravel + Inertia/Vue shape that Sourdough's templates describe, already shaped. If every app should get
  a change, it belongs in Sourdough; if only this shape should, it lives here.

## Where it lives

Development happens on Wayne State's GitLab. This GitHub repository is a read-only release mirror: `main` and tags are
copied here automatically, and changes, issues and merge requests go to GitLab first. Nobody pushes to GitHub
directly.

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
| JS lint and format | `npm run lint`, `npm run format:check` |
| Production assets | `npm run build` |

`composer ci` runs `composer validate --strict`, the tests, Pint, PHPStan and `composer audit`. It does not run
PROCTOR, Ziggy or the JS lint.

Pint skips `deploy/`. That folder is the stack's shared front-page deploy layer (SQUIRE kit plus HELMET front controller), kept byte-identical in PER style across every stack repo, so WELD's Laravel preset must not reformat it. Its style is checked where it is made.

## CI

- **GitLab:** PREFLIGHT's `php-test` (PHP 8.3, 8.4, 8.5) and `composer-audit` components, a `composer-ci` job, a
  `proctor` job (warnings pass, failures block) and a `ziggy` drift job.
- **GitHub:** `.github/workflows/ci.yml` calls PREFLIGHT's reusable workflow with `composer ci`.
- Green means all of those pass. JS lint is not in CI yet.

## Keeping the joints current

- When HELMET, SESAME, CREED or PROCTOR release, raise the constraint here (`composer require waynestate/<part>:^0.x`),
  run all the checks above, and commit the lock file.
- When Laravel, Inertia or Vue release, BOOSTER's hops are the way to move; WELD is a good first test for them.
- After route changes, regenerate Ziggy and commit the result.

## Releasing

WELD has no tags yet. When it gets one:

1. Move the lines under `## Unreleased` in `CHANGELOG.md` to a new `## X.Y.Z (YYYY-MM-DD)` heading.
2. All the checks above green locally, then both pipelines green for that commit.
3. Run a secrets scan of the history and check that nothing internal (server names, people, IDs) is in the tree.
4. Tag `X.Y.Z` (no `v`) on GitLab. The mirror carries the tag to GitHub.
5. A release needs a maintainer's approval. Never move or delete a published tag.

## Running it elsewhere

WELD targets Citadel first. `docs/running-off-citadel.md` covers other hosts.

## The front page

`site/` is the stack's front page, for `https://weld.apps.wayne.edu/` on Citadel, served through a small raw-PHP
`deploy/` layer generated with SQUIRE (separate from the WELD app itself).

## Who maintains it

Adam Perkins, Wayne State University Enterprise Applications. Internal runbook (accounts, tokens, mirrors, deploy
access): ask Enterprise Applications.
