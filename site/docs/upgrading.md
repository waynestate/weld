---
title: "Upgrading | WELD docs"
description: "How to move a WELD app forward: PHP and Laravel upgrades through Booster, one hop at a time."
url: https://weld.apps.wayne.edu/docs/upgrading.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Upgrading[#](https://weld.apps.wayne.edu/docs/upgrading.html#upgrading)

This page shows how to keep a WELD app current: how to take stack changes, how to read the CHANGELOG, what a release does, and whether any released version breaks your app.

## Take stack changes[#](https://weld.apps.wayne.edu/docs/upgrading.html#take-stack-changes)

WELD owns its app code. The wiring files are marked and refreshed by `sourdough feed`, not hand-edited, so the app does not fork from the stack. Sourdough is not a dependency yet, so there is no feed to run today; the target is for WELD's CI to run `sourdough feed` against each stack release and fail on drift.

When HELMET, SESAME, CREED or PROCTOR releases, raise the constraint here, run all the checks, and commit the lock file:

sh

```sh
composer require waynestate/<part>:^0.x
bin/weld.sh test
```

PowerShell

```powershell
composer require waynestate/<part>:^0.x
.\bin\weld.ps1 test
```

`<part>` is the lowercase package name: `helmet`, `sesame`, `creed` or `proctor`.

When Laravel, Inertia or Vue release, Booster's hops are the way to move; WELD is a good first test for them.

After route changes, regenerate Ziggy and commit the result:

```sh
composer ziggy
git diff --exit-code resources/js/ziggy.*
```

When Sourdough changes what it generates, the regen gate flags the new drift in CI. Fix it here or upstream in Sourdough; if the drift is accepted as intended, record it with `--write-baseline` and commit `.sourdough/regen-baseline.json`.

## Read the CHANGELOG[#](https://weld.apps.wayne.edu/docs/upgrading.html#read-the-changelog)

The CHANGELOG lists notable changes to WELD, newest first. Pending work sits under `## Unreleased`; each released version sits under a `## X.Y.Z (YYYY-MM-DD)` heading. To find what moved, read the entries between your current version and the one you are moving to.

## What a release does[#](https://weld.apps.wayne.edu/docs/upgrading.html#what-a-release-does)

WELD is tagged (0.1.0 through 0.2.1 so far). A release:

1. Moves the lines under `## Unreleased` in `CHANGELOG.md` to a new `## X.Y.Z (YYYY-MM-DD)` heading.
2. Needs all the checks green locally, then both pipelines green for that commit.
3. Runs a secrets scan of the history and checks that nothing internal (server names, people, IDs) is in the tree.
4. Tags `X.Y.Z` (no `v`) on git.wayne.edu, with an approver; the push mirror carries the tag to GitHub.
5. Needs a maintainer's approval. Never move or delete a published tag.

## Breaking changes[#](https://weld.apps.wayne.edu/docs/upgrading.html#breaking-changes)

0.1.0 through 0.1.5 carry no breaking change, so moving between them is just taking in the new release:

```sh
composer require waynestate/<part>:^0.x
```

Then run all the checks and commit the lock file.

0.2.0 is the first release to move majors under you. An app built from 0.1.x takes these by hand (the [changelog](https://weld.apps.wayne.edu/docs/changelog.html) has each one in full):

- **Inertia 3**: `inertiajs/inertia-laravel` and `@inertiajs/vue3` move to `^3.0`.
- **HELMET's config**: `config/helmet.php` becomes a thin override (only `policy_path`); copy it from WELD and move any setting you changed into `.env` or that file.
- **Part constraints**: HELMET `^1.3`, SESAME `^0.7` (its CAS protocol defaults to 2.0), PROCTOR `^0.11`, CREED `^0.1.8` and ADVISOR `^0.2`.
- **Deprecations fail the tests**: `phpunit.xml` sets `failOnDeprecation`, so fix what it reports before you merge.

0.2.1 carries no breaking change. An app built from 0.2.0 that keeps WELD's `bin/weld.ps1` and `bin/weld.sh` copies their `down` from WELD, so it clears `public/hot` too.

## Related[#](https://weld.apps.wayne.edu/docs/upgrading.html#related)

- [Install](https://weld.apps.wayne.edu/docs/install.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
- [Check your setup](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html)
- [Changelog](https://weld.apps.wayne.edu/docs/changelog.html)
- [Contributing](https://weld.apps.wayne.edu/docs/contributing.html)
