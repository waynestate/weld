---
title: Contributing
description: How to contribute to WELD: the canonical repository, the mirrors, the checks and the release path.
---

# Contributing

This page tells you how to build, test, keep current and release WELD, and where each piece of work belongs.

## Where it lives

Development happens on git.wayne.edu, the canonical repository: [enterprise-apps/weld](https://git.wayne.edu/enterprise-apps/weld). Changes, issues and merge requests go there; tags are made there, by the maintainer, with an approver. The GitHub repository ([waynestate/weld](https://github.com/waynestate/weld)) is a read-only push mirror: `main` and tags are copied automatically, nobody pushes to it, and Packagist reads from it.

> [!NOTE]
> The weld is made on git.wayne.edu; the mirrors only get the polished bead.

## Build and test

The Docker path installs, migrates and serves in one command:

```sh tab="sh"
bin/weld.sh up
```

```powershell tab="PowerShell"
.\bin\weld.ps1 up
```

All checks run `composer ci` in the container:

```sh tab="sh"
bin/weld.sh test
```

```powershell tab="PowerShell"
.\bin\weld.ps1 test
```

Check your setup:

```sh tab="sh"
bin/weld.sh doctor
```

```powershell tab="PowerShell"
.\bin\weld.ps1 doctor
```

Logs and stop:

```sh tab="sh"
bin/weld.sh logs
bin/weld.sh down
```

```powershell tab="PowerShell"
.\bin\weld.ps1 logs
.\bin\weld.ps1 down
```

Without Docker: run `composer install`, `npm ci`, copy `.env.example` to `.env`, `php artisan key:generate`, then `composer ci`.

### Other checks

| Check | Command |
|---|---|
| PROCTOR against CREED's policy | `composer proctor` |
| Ziggy routes are current | `composer ziggy`, then `git diff --exit-code resources/js/ziggy.*` |
| JS lint, format and types | `npm run lint` (pure check, includes the WayneUI-only import rule), `npm run fix` (apply fixes), `npm run format:check`, `npm run typecheck` |
| Production assets | `npm run build` |

`composer ci` runs `composer validate --strict`, the tests, Pint, PHPStan and `composer audit`. It does not run PROCTOR, Ziggy or the JS lint.

The regen gate checks that WELD is still Sourdough's Inertia shape. Generate a fresh app (`sourdough new <dir> --inertia`), then `php bin/regen-check.php --generated <dir>` (or `composer regen-check -- --generated <dir>`). It skips the product files listed in `.sourdough/product-files.json` and any `vendor` or `node_modules` path, and fails only on drift that is not already recorded in `.sourdough/regen-baseline.json`; `--write-baseline` records the current drift as accepted. It is not part of `composer ci`; CI runs it as a separate job that blocks on new drift.

Pint skips `deploy/`. That folder is the stack's shared front-page deploy layer (Squire kit plus HELMET front controller), kept byte-identical in PER style across every stack repo, so WELD's Laravel preset must not reformat it. Its style is checked where it is made.

## CI

- **GitLab:** Preflight's `php-test` (PHP 8.3, 8.4, 8.5, running `vendor/bin/pest`) and `composer-audit` components, a `composer-ci` job, a `proctor` job (warnings pass, failures block), a `frontend` job (`npm ci`, lint, Prettier, vue-tsc and the build), a `ziggy` drift job and a `regen-gate` job (new drift fails the job; `.sourdough/regen-baseline.json` records the drift already accepted).
- **GitHub:** `.github/workflows/ci.yml` calls Preflight's reusable workflow with `composer ci`, runs the same `frontend` job, and runs the same `regen-gate` check (`continue-on-error` until the baseline is filled in).

Green means all of those pass; on GitLab the regen gate blocks on new drift, and on GitHub it may warn while the baseline is being filled in.

## Keeping the joints current

- When HELMET, SESAME, CREED or PROCTOR release, raise the constraint here (`composer require waynestate/<part>:^0.x`), run all the checks above, and commit the lock file.
- When Laravel, Inertia or Vue release, Booster's hops are the way to move; WELD is a good first test for them.
- After route changes, regenerate Ziggy and commit the result.
- When Sourdough changes what it generates, the regen gate flags the new drift in CI. Fix it here or upstream in Sourdough; if the drift is accepted as intended, record it with `--write-baseline` and commit `.sourdough/regen-baseline.json`.

## Releasing

WELD has no tags yet. When it gets one:

1. Move the lines under `## Unreleased` in `CHANGELOG.md` to a new `## X.Y.Z (YYYY-MM-DD)` heading.
2. All the checks above green locally, then both pipelines green for that commit.
3. Run a secrets scan of the history and check that nothing internal (server names, people, IDs) is in the tree.
4. Tag `X.Y.Z` (no `v`) on git.wayne.edu, with an approver. The push mirror carries the tag to GitHub.
5. A release needs a maintainer's approval. Never move or delete a published tag.

## Running it elsewhere

WELD targets Citadel first. [Running WELD off Citadel](running-off-citadel.md) covers OpenShift, Coolify and plain Docker, with Citadel for comparison.

## The front page

`site/` is the stack's front page, for `https://weld.apps.wayne.edu/` on Citadel, served through a small raw-PHP `deploy/` layer generated with Squire (separate from the WELD app itself).

## The docs site

The docs site is built from `docs/` by the shared docs builder (`composer docs`) and checked by `composer docs:check`. Sources live in `docs/`; the page you read is the page that ships: same source, same bead.

The pages wear the front page's look. The header on every docs page is `docs/_header.html` (keep its links in step with
the front page's main menu), and colors, type, callouts and motion live in `docs/theme/weld.css`; `docs/docs.json` names
both under `theme`. Change the look there, never in the kit files (`site/assets/docs.css`, `site/assets/docs.js`), which
the builder pins by hash.

## Related

- [Running WELD off Citadel](running-off-citadel.md)
