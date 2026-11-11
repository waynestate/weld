---
title: Making WELD yours
description: Starting an app from WELD: renaming the parts that still say WELD, stripping WELD's product files so composer ci keeps passing, and adopting Sourdough so feed keeps working.
---

# Making WELD yours

WELD is built to be cloned: rename it, strip what is WELD's own, and keep the stack underneath. New kitchen, same dough.

## Rename the parts that still say WELD

| Where | What to change |
| --- | --- |
| `.env` | `APP_NAME` (`.env.example:1`). `VITE_APP_NAME` follows on its own: `.env.example:94` sets it from `${APP_NAME}`. |
| `composer.json` | `name` (`:3`), `description` (`:5`), `homepage` (`:6`) and `support` (`:7-10`) |
| `package.json` | `name` (`:2`) |
| Site title | `resources/views/app.blade.php:30` reads `config('app.name')`, which is `APP_NAME` (`config/app.php:16`); the literal `WELD` there is only the fallback for when the variable is unset |
| Header brand | `resources/js/components/AppHeader.vue:23` falls back to `WELD` when `VITE_APP_NAME` is unset |
| Git remote | `git remote set-url origin <your repo>` |

Then give the copy its own key (`php artisan key:generate`) and re-run the checks. A case-insensitive grep finds the stragglers: `git grep -in weld`. Two are internal names, not user-facing: the `weld-form` class in `resources/js/components/DeleteUser.vue:48,58`, and the npm name above.

## Strip WELD's product files

WELD's drift gate lists its own product files in `.sourdough/product-files.json:1-19`: `site/`, `docs/`, `deploy/`, `bin/`, `scripts/readme-art/`, `compose.yaml`, `compose.oracle.yaml`, `CHANGELOG.md`, `MAINTAINING.md`, `BEST_PRACTICES.md`, `LICENSE`, `README.md`, `agent-ready.json`, `.github/`, `composer.lock`, `package-lock.json` and `.env`. A derived app usually strips the documentation and the site (`site/`, `docs/`, `bin/`, `BEST_PRACTICES.md`, the `.sourdough/` drift-gate files) and keeps the rest: `deploy/` is how it reaches Citadel, and `compose.yaml` is how it runs locally.

The stripping has to reach the tooling that points at the stripped files, or `composer ci` fails at `@test` or at the check steps:

- Drop the three `ci` entries that run the docs and site checks: `@site:agent-ready-check`, `@docs:practices-check` and `@docs:check` (`composer.json:73-75`), with their script definitions (`composer.json:78-83`) and the standalone `regen-check` script (`composer.json:66`).
- Drop the tests that exist only to guard the stripped files: `tests/Unit/FrontPageReleasesTest.php` reads `site/index.html`, `tests/Unit/PracticesCoverageTest.php` checks that `docs/guides/*.md` covers every practice in `BEST_PRACTICES.md`, `tests/Unit/RegenGateRulesTest.php` parses the `regen-gate` job out of `.gitlab-ci.yml`, and `tests/Unit/LocalStackTest.php` reads `bin/weld.ps1` and `bin/weld.sh`.
- `pint.json` records two extra excludes, `deploy/` and `bin/docs-site.php` (`tests/Unit/PinstripeWiringTest.php:43-48` holds the pairing): drop the exclude for a file you drop.

The rule under all of it: a test that reads a file fails when the file goes, so the test goes with the file.

## Adopt with Sourdough so feed keeps working

`sourdough feed` only reaches an app that carries `.sourdough/manifest.json`, and `sourdough new` writes one only into the apps it bakes (Sourdough's [adopt guide](https://github.com/waynestate/sourdough/blob/main/docs/guides/adopt.md), opening paragraph). WELD itself is not adopted: it was built by hand and checks its drift against a fresh Inertia bake with its own regen check (adopt guide, "When to adopt"), so a clone of WELD has no manifest, and `feed` answers exit `2` with the way in (Sourdough's [keeping-current guide](https://github.com/waynestate/sourdough/blob/main/docs/guides/keeping-current.md), "Apps Sourdough did not make, and WELD").

Run adopt once, in the renamed copy, on a clean git tree:

```
sourdough adopt --shape laravel-inertia --dry-run
sourdough adopt --shape laravel-inertia --name your-app
git add .sourdough && git commit -m "Adopt with Sourdough"
sourdough feed --dry-run
```

The shape name is `laravel-inertia`; `--inertia` is the flag `sourdough new` takes, and adopt resolves the name against the five shapes. What adopt does and does not touch:

- It writes one file, `.sourdough/manifest.json`, and never changes your code (adopt guide, opening paragraph).
- Files the template does not ship are not recorded: Sourdough never reads or touches them (adopt guide, "Read the report"). Whether you adopt before or after stripping the product files makes no difference to the manifest.
- `--name` defaults to the folder's basename; the template writes the name into `composer.json`, `public/index.php` and the README (adopt guide, "Pick the shape and the name"). Because you renamed first, those files land in `yours`, and feed never writes, merges or deletes a `yours` file.
- Files that match the template become `managed` and get every template update; files the app lacks arrive with the first feed (adopt guide, "Look first").

One CI note: WELD's pipeline carries the regen gate, not the feed job (`.gitlab-ci.yml:214`), so nothing warns when your app falls behind its template. Copy the `sourdough-feed` job from a fresh bake of the same shape if you want the warning (adopt guide, "Your own .gitlab-ci.yml").

## Related

- [Quick start](quick-start.md)
- [WELD and Sourdough](sourdough.md)
- [Is WELD still Sourdough's shape?](regen-check.md)
