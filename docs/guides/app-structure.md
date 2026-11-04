---
title: App structure
description: Where the code lives in a WELD app: the Laravel app, the Vue front end, the static site and the deploy layer.
---

# App structure

This page lists the app structure practices for WELD, from the BEST_PRACTICES guide, with the tag that says whether the stack holds each one today.

## W1. One owner of authentication: SESAME

**Practice:** Remove the Laravel starter kit's Fortify routes and actions (`/login`, `/register`, `/forgot-password`, 2FA). SESAME's `findOrImportUser(Identity)` is the only way in.

**Why:** Two login systems mean two session rules and two audit trails, and the kit's Fortify registers its routes automatically.

**Stack:** `Documented`. SESAME is a dependency (`composer.json`), the starter kit's Fortify routes are removed, and `tests/Feature/AuthRoutesTest.php` fails if WELD grows its own auth routes. Target `Checked`: a PROCTOR check and a feature test over `php artisan route:list --json`.

## W2. WayneUI is the only component source

**Practice:** Pages use WayneUI components and tokens. Do not keep the kit's shadcn-vue components or Tailwind theme as a second design system.

**Why:** The stack's promise is one interface contract on every consumer path; a parallel component set breaks the accessibility gate.

**Stack:** `Not yet`. The shadcn-vue components and their npm dependencies are gone, but WayneUI is not wired in: the Vue components carry `TODO(wayneui)` stand-ins (for example `resources/js/layouts/settings/Layout.vue` and `resources/js/pages/settings/Profile.vue`) because WELD does not load WayneUI's stylesheet yet, and the Tailwind theme (`tailwind.config.js`) plus `cn()`/`clsx`/`tailwind-merge` (`resources/js/lib/utils.ts`, `package.json`) remain. Target `Checked`: an ESLint rule (Pinstripe) bans imports from the kit's `components/ui` path, and a check fails when `components.json` exists.

## W3. Own the app code; take stack changes through `sourdough feed`

**Practice:** The kit gives you the code ("full ownership"), with no kit upgrade path. WELD's wiring files are marked and refreshed by `sourdough feed`, not hand-edited.

**Why:** Without a feed the app forks from the stack on day one and never gets fixes.

**Stack:** `Not yet`. Sourdough is not a dependency yet, so there is no feed to run. Target `Checked`: WELD's CI runs `sourdough feed` against each stack release and fails on drift.

## W4. Inertia 3 only; migrate off the removed APIs

**Practice:** Use `@inertiajs/vue3` ^3 and `inertiajs/inertia-laravel` ^3. Do not import Axios, call `Inertia::lazy()` (use `Inertia::optional()`), read `data-page`, or `require()` Inertia packages.

**Why:** Inertia 3 removed all of these; code copied from older tutorials fails or half-works.

**Stack:** `Not yet`. WELD is on Inertia 2, not 3: `composer.json` pins `inertiajs/inertia-laravel: ^2.0` and `package.json` pins `@inertiajs/vue3: ^2.0.0-beta.3`. The removed APIs are not used (no `axios` dependency, no `Inertia::lazy`, no `data-page`), so the migration is a version bump, not a rewrite. Target `Checked`: ESLint `no-restricted-imports` for `axios`, a grep check for `Inertia::lazy`, and Composer constraints.

## W5. Commit `.ai/rules`; ignore what Boost regenerates

**Practice:** Laravel Boost is a `require-dev` package. Commit `.ai/rules/` (project rules). Ignore `.mcp.json`, `boost.json` and the generated block of `CLAUDE.md`/`AGENTS.md`. WELD's own conventions go in `.ai/guidelines/` and each stack package may ship `resources/boost/guidelines/core.blade.php` and `resources/boost/skills/*/SKILL.md`.

**Why:** Rules are shared team memory; generated files change with every `boost:update`.

**Stack:** `Not yet`. Boost is not a dependency and there is no `.ai/` directory yet. Target `Checked`: PROCTOR confirms the `.gitignore` entries and that `.ai/rules/` exists.

## W6. Sail is local development only

**Practice:** Sail's own description is "Laravel's default Docker development environment". It is not a production image and WELD never deploys it.

**Why:** Sail's runtime images carry dev tooling and a bash-script CLI.

**Stack:** `Documented`. `composer.json` keeps `laravel/sail` in `require-dev`, so it is a local tool, not a runtime dependency. Target `Checked`: PROCTOR flags `laravel/sail` outside `require-dev` and any `vendor/laravel/sail` path in a production Dockerfile.

## W7. The supported dev path is Compose driven by native scripts, not Sail

**Practice:** Sail runs on Windows "via WSL2". The stack rule is that Windows needs no bash and no WSL. Ship `weld.ps1` and `weld.sh` (or one PHP command) over a plain `compose.yaml`; Sail stays an unsupported convenience.

**Why:** Every developer OS gets its own native path.

**Stack:** `Documented`. `compose.yaml` and the `bin/weld.ps1` / `bin/weld.sh` pair exist. Target `Checked`: a pair-parity test.

## W8. One image, several process types

**Practice:** Build one image. Run it as `web`, `worker` and `scheduler` by changing the command only (plus `ssr` when Inertia SSR is on). On Compose these are services; on OpenShift a Deployment, a Deployment and a CronJob.

**Why:** One build means one tested artifact, and the process types stay identical in code.

**Stack:** `Not yet`. A Dockerfile now exists (`deploy/.squire/Dockerfile`, a local mirror of the image Citadel generates), but it runs one process type only: `CMD ["apache2-foreground"]` (`deploy/.squire/Dockerfile`), with no worker or scheduler variant. Target `Checked`: Preflight portability boots the image with each command.

## How to read the tags

Each practice ends with exactly one tag.

| Tag | Meaning |
| --- | --- |
| `Enforced` | WELD's code does it or refuses to run without it, and a test covers it. |
| `Checked` | PROCTOR or CI warns or fails. |
| `Documented` | Written down in the README or a config file, with no check behind it. |
| `Not yet` | A gap. |

## Related

- [The container contract](./container-contract.md)
- [Queues and scheduling](./queues-and-scheduling.md)
- [Running WELD off Citadel](../running-off-citadel.md)
