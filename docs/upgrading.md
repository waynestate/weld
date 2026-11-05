---
title: Upgrading
description: How to move a WELD app forward: PHP and Laravel upgrades through Booster, one hop at a time.
---

# Upgrading

This page shows how to keep a WELD app current: how to take stack changes, how to read the CHANGELOG, what a release does, and whether any released version breaks your app.

## Take stack changes

WELD owns its app code. The wiring files are marked and refreshed by `sourdough feed`, not hand-edited, so the app does not fork from the stack. Sourdough is not a dependency yet, so there is no feed to run today; the target is for WELD's CI to run `sourdough feed` against each stack release and fail on drift.

When HELMET, SESAME, CREED or PROCTOR releases, raise the constraint here, run all the checks, and commit the lock file:

```sh tab="sh"
composer require waynestate/<part>:^0.x
bin/weld.sh test
```

```powershell tab="PowerShell"
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

## Read the CHANGELOG

The CHANGELOG lists notable changes to WELD, newest first. Pending work sits under `## Unreleased`; each released version sits under a `## X.Y.Z (YYYY-MM-DD)` heading. To find what moved, read the entries between your current version and the one you are moving to.

## What a release does

WELD is tagged (0.1.0 through 0.1.2 so far). A release:

1. Moves the lines under `## Unreleased` in `CHANGELOG.md` to a new `## X.Y.Z (YYYY-MM-DD)` heading.
2. Needs all the checks green locally, then both pipelines green for that commit.
3. Runs a secrets scan of the history and checks that nothing internal (server names, people, IDs) is in the tree.
4. Tags `X.Y.Z` (no `v`) on nougat; the mirror carries the tag to GitHub.
5. Needs a maintainer's approval. Never move or delete a published tag.

## Breaking changes

No released version (0.1.0, 0.1.1, 0.1.2) carries a breaking change yet, so upgrading is just taking in the new release:

```sh
composer require waynestate/<part>:^0.x
```

Then run all the checks and commit the lock file.

## Related

- [Install](install.md)
- [Running WELD off Citadel](running-off-citadel.md)
- [Check your setup](guides/check-your-setup.md)
- [Changelog](changelog.md)
- [Contributing](contributing.md)
