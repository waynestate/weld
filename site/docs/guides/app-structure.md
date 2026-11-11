---
title: "App structure | WELD docs"
description: "Where the code lives in a WELD app: the Laravel app, the Vue front end, the static site and the deploy layer."
url: https://weld.apps.wayne.edu/docs/guides/app-structure.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# App structure [#](https://weld.apps.wayne.edu/docs/guides/app-structure.html#app-structure)

This page lists the app structure practices for WELD, from the BEST_PRACTICES guide, with the tag that says whether the stack holds each one today.

## W1. One owner of authentication: SESAME [#](https://weld.apps.wayne.edu/docs/guides/app-structure.html#w1-one-owner-of-authentication-sesame)

**Practice:** Remove the Laravel starter kit's Fortify routes and actions (`/login`, `/register`, `/forgot-password`, 2FA). SESAME's `findOrImportUser(Identity)` is the only way in.

**Why:** Two login systems mean two session rules and two audit trails, and the kit's Fortify registers its routes automatically.

**Stack:** `Checked`. SESAME is a dependency (`composer.json:24`), the starter kit's Fortify routes are removed, and `tests/Feature/AuthRoutesTest.php` fails if WELD grows its own auth routes: no register, password, two-factor, forgot, reset, verify or confirm URIs (`:17-30`), no `Laravel\Fortify` actions (`:32-43`), and `/login` and `/logout` owned by SESAME exactly once each (`:45-64`). Open: a PROCTOR check over `php artisan route:list --json`.

## W2. WayneUI is the only component source [#](https://weld.apps.wayne.edu/docs/guides/app-structure.html#w2-wayneui-is-the-only-component-source)

**Practice:** Pages use WayneUI components and tokens. Do not keep the kit's shadcn-vue components or Tailwind theme as a second design system.

**Why:** The stack's promise is one interface contract on every consumer path; a parallel component set breaks the accessibility gate.

**Stack:** `Checked`. WayneUI 2.0.8 is the interface: `package.json` pins `@waynestate/wayne-ui-css` and `@waynestate/wayne-ui-vue` exactly (the lock's sha512 integrity is the checksum), `resources/css/app.css` imports the layered `dist/wayne-ui.css`, and the pages and layouts use WayneUI's Vue components (`AppShell`, `AppBar`, `Sidebar`, `Card`, `Input`, `Dialog`, `RadioGroup`). The Tailwind theme, `cn()`, `clsx`, `tailwind-merge`, `@headlessui/vue` and `lucide-vue-next` are gone. ESLint's `no-restricted-imports` rule in `eslint.config.js` bans the kit's `components/ui` path and those helpers, `tests/Feature/WayneUiOnlyTest.php` fails when `components.json`, a `components/ui` folder, a Tailwind dependency or a loose WayneUI pin appears, and the `frontend` job in `.gitlab-ci.yml` and `.github/workflows/ci.yml` runs lint, Prettier, vue-tsc and the build. Where WayneUI has no component (the account menu), a small local one is built on its tokens and says so in a comment.

## W3. Own the app code; take stack changes through `sourdough feed` [#](https://weld.apps.wayne.edu/docs/guides/app-structure.html#w3-own-the-app-code-take-stack-changes-through-sourdough-feed)

**Practice:** The kit gives you the code ("full ownership"), with no kit upgrade path. WELD's wiring files are marked and refreshed by `sourdough feed`, not hand-edited.

**Why:** Without a feed the app forks from the stack on day one and never gets fixes.

**Stack:** `Checked`. Sourdough is still not a Composer dependency, but a drift gate runs: `bin/regen-check.php` compares WELD file by file by SHA-256 against a fresh `sourdough new --inertia` generation, skips the product files in `.sourdough/product-files.json`, and fails only on new drift versus `.sourdough/regen-baseline.json`. The `regen-gate` jobs in `.gitlab-ci.yml:234-277` and `.github/workflows/ci.yml:56-73` block on new drift. On GitLab the gate always runs on a tag (the release check), on a pipeline schedule and on Run pipeline, and on a push only when the push touches a path the gate compares or the gate itself (`rules:` at `.gitlab-ci.yml:238-251`), so a docs or site push, which cannot cause drift, skips it; the job is `interruptible`, and only its network steps (WELD's and Sourdough's `composer install`, the Sourdough clone) get three tries, so drift is never retried into green. `tests/Unit/RegenGateRulesTest.php` holds the rules. Open: the nightly pipeline schedule on `main` that catches upstream drift (Sourdough's main or the Laravel skeleton moving) when nobody pushes is pending the maintainer's approval, and until it exists that drift shows at WELD's next code push or tag; the gate clones Sourdough's `main` from the GitHub mirror (the job-token clone from git.wayne.edu waits on Sourdough's allowlist), not a pinned stack release.

## W4. Inertia 3 only; migrate off the removed APIs [#](https://weld.apps.wayne.edu/docs/guides/app-structure.html#w4-inertia-3-only-migrate-off-the-removed-apis)

**Practice:** Use `@inertiajs/vue3` ^3 and `inertiajs/inertia-laravel` ^3. Do not import Axios, call `Inertia::lazy()` (use `Inertia::optional()`), read `data-page`, or `require()` Inertia packages.

**Why:** Inertia 3 removed all of these; code copied from older tutorials fails or half-works.

**Stack:** `Checked`. WELD is on Inertia 3: `composer.json:19` requires `inertiajs/inertia-laravel: ^3.0` and `package.json:26` requires `@inertiajs/vue3: ^3.0`. `tests/Feature/CspScriptTagsTest.php:53-60` pins the Inertia 3 JSON page island, so a regression to the Inertia 2 layout fails the suite. Open: an ESLint restriction on `axios` and a grep guard for `Inertia::lazy`.

## W5. Commit `.ai/rules`; ignore what Boost regenerates [#](https://weld.apps.wayne.edu/docs/guides/app-structure.html#w5-commit-ai-rules-ignore-what-boost-regenerates)

**Practice:** Laravel Boost is a `require-dev` package. Commit `.ai/`: project rules in `.ai/rules/` (with Boost's `index.md`) and WELD's own conventions in `.ai/guidelines/`. Ignore what `boost:install` and `boost:update` write: `.mcp.json`, `boost.json`, `CLAUDE.md`, `AGENTS.md`, `.claude/skills/` and `.junie/`. Git cannot ignore only the generated block inside `CLAUDE.md`, so the whole file is ignored and team guidance goes in `.ai/guidelines/`, which Boost folds into every agent's file. `.agents/` stays tracked (Booster's playbooks live there). Each stack package may ship `resources/boost/guidelines/core.blade.php` and `resources/boost/skills/*/SKILL.md`.

**Why:** Rules are shared team memory; generated files change with every `boost:update`, and `boost.json` holds each developer's own agent picks.

**Stack:** `Checked`. `composer.json` requires `laravel/boost: ^2.10` in `require-dev`; `.ai/guidelines/wayne-state.md` and `.ai/rules/` (`index.md`, `migrations.md`, `routes.md`) are committed as Sourdough's Inertia template ships them, and the `.gitignore` carries Sourdough's Boost block. `tests/Feature/BoostDevOnlyTest.php` "ignores what Boost regenerates and keeps what the team shares" fails CI when an ignore line or a committed `.ai` file goes missing, and the regen gate keeps the `.ai` files equal to Sourdough's. Open: a PROCTOR check for apps Sourdough did not bake.

## W6. Sail is local development only [#](https://weld.apps.wayne.edu/docs/guides/app-structure.html#w6-sail-is-local-development-only)

**Practice:** Sail's own description is "Laravel's default Docker development environment". It is not a production image and WELD never deploys it.

**Why:** Sail's runtime images carry dev tooling and a bash-script CLI.

**Stack:** `Documented`. `composer.json:33` keeps `laravel/sail` in `require-dev`, so it is a local tool, not a runtime dependency. Target `Checked`: PROCTOR flags `laravel/sail` outside `require-dev` and any `vendor/laravel/sail` path in a production Dockerfile.

## W7. The supported dev path is Compose driven by native scripts, not Sail [#](https://weld.apps.wayne.edu/docs/guides/app-structure.html#w7-the-supported-dev-path-is-compose-driven-by-native-scripts-not-sail)

**Practice:** Sail runs on Windows "via WSL2". The stack rule is that Windows needs no bash and no WSL. Ship `weld.ps1` and `weld.sh` (or one PHP command) over a plain `compose.yaml`; Sail stays an unsupported convenience.

**Why:** Every developer OS gets its own native path.

**Stack:** `Checked`. `compose.yaml` and the `bin/weld.ps1` / `bin/weld.sh` pair exist, and `tests/Unit/LocalStackTest.php:10-47` pins the pair's shared behavior: the SQLite file is created only when missing and never truncated, and the Vite service writes a reachable listen address.

## W8. One image, several process types [#](https://weld.apps.wayne.edu/docs/guides/app-structure.html#w8-one-image-several-process-types)

**Practice:** Build one image. Run it as `web`, `worker` and `scheduler` by changing the command only (plus `ssr` when Inertia SSR is on). On Compose these are services; on OpenShift a Deployment, a Deployment and a CronJob.

**Why:** One build means one tested artifact, and the process types stay identical in code.

**Stack:** `Checked`, for the portable path. Squire's portable image (`deploy/.squire/portable.Dockerfile`) bakes the code in and runs as `web`, `worker` (`queue:work --tries=3 --max-time=3600 --sleep=3`), `scheduler` (`schedule:work`) or `migrate` by `SQUIRE_ROLE` or the first argument (`deploy/.squire/portable/squire-entrypoint.sh`), and the `portable` profile in `deploy/docker-compose.squire.yml` runs the four as services of one image, the scheduler with `QUEUE_WORKER_MODE: none`. `tests/Feature/PortableImageTest.php` fails when a role, the shared image or that switch goes, and Squire's `PortableImageTest` (`testThePortableProfileRunsOneImageAsFourRoles`, `testTheRolePicksTheProcess`) runs the entrypoint itself. Built and run for real on 2026-11-08: `migrate` exited 0, then `web`, `worker` and `scheduler` came up healthy and the worker ran a queued job. The Citadel mirror (`deploy/.squire/Dockerfile`) still runs Apache only, because Citadel has no worker process (W19). In CI, Preflight's `portability` job (`.gitlab-ci.yml`, Preflight 0.6.0) builds this image and boots its `web` role, the image's default; `tests/Unit/PortabilityJobTest.php` holds the include. Open: no `ssr` role (SSR is off); the `portability` job boots only `web`, so `worker`, `scheduler` and `migrate` run only in the compose profile.

## One-time deploy steps (deployments/) [#](https://weld.apps.wayne.edu/docs/guides/app-structure.html#one-time-deploy-steps-deployments)

An app may add its own one-time step as `deployments/002-*.sh`, beside the kit's `001-first-release.sh`. Citadel runs each executable file in `deployments/` once per file name for the life of the app, as the `citadel` user, when the container starts, before Apache (`deploy/deployments/001-first-release.sh:4-5`). A finished file is recorded at `/citadel/logs/deployments/<name>.log` and skipped on later starts; a failed run leaves a `.failed.log` instead, so the next start tries again, up to three times, and then the deployment is marked FAILING (`deploy/.squire/fallback/citadel-deploy:12-14`, `:20-24`; `deploy/deployments/001-first-release.sh:5-6`). Everything in the hook must be safe to run twice (S10). A new file name runs on the next container start after it appears, provided it is executable: the runner takes only executable files (`deploy/.squire/fallback/citadel-deploy:9-10`).

Keep repeatable work in `scripts/release.sh`, which the first-release hook calls with the smoke test off (`deploy/deployments/001-first-release.sh:9-10`, `:25`). Mark a new hook executable in git: `git update-index --chmod=+x deployments/002-your-step.sh`.

Squire's update treats a file it does not pin as yours: `.squire.json` pins the kit's files, including `deployments/001-first-release.sh` (`deploy/.squire.json:28`), and Squire never touches a file it did not write, reports it as `app-owned`, never records or overwrites it, and `init --check` does not count it as drift ([Squire's update-and-drift guide](https://squire.apps.wayne.edu/docs/guides/update-and-drift.html)).

## How to read the tags [#](https://weld.apps.wayne.edu/docs/guides/app-structure.html#how-to-read-the-tags)

Each practice ends with exactly one tag.

| Tag | Meaning |
| --- | --- |
| `Enforced` | WELD's code does it or refuses to run without it, and a test covers it. |
| `Checked` | PROCTOR or CI warns or fails. |
| `Documented` | Written down in the README or a config file, with no check behind it. |
| `Not yet` | A gap. |

## SESAME and fetch [#](https://weld.apps.wayne.edu/docs/guides/app-structure.html#sesame-and-fetch)

The `auth.sesame` middleware lets a request through when SESAME has a user in the session. Otherwise it answers by kind of client (`vendor/waynestate/sesame/src/Laravel/Authenticate.php:25-36`):

- a request for which Laravel's `expectsJson()` is true gets **401** with the body `Unauthorized.` (`:31-33`);
- everything else gets **redirected to the login route**, `GET /login` (`:35`, registered at `vendor/waynestate/sesame/src/Laravel/SesameServiceProvider.php:297`).

Laravel's `expectsJson()` is true when the first type in `Accept` is JSON (`.../json` or `...+json`), or when the request carries `X-Requested-With: XMLHttpRequest`, is not pjax, and accepts any content type (`vendor/laravel/framework/src/Illuminate/Http/Concerns/InteractsWithContentTypes.php:24-27,34-39,136-143`). Two consequences for scripted requests:

- A bare `fetch('/api/answer')` sends `Accept: */*` and no XHR header, so it is not a JSON request and gets the redirect, not a 401. Send the JSON Accept header and handle the 401:
  ```js
  const res = await fetch('/api/answer', { headers: { Accept: 'application/json' } });
  if (res.status === 401) {
    // signed out: send the browser to /login
  }
  ```
- A streaming `text/event-stream` request is never JSON: its first type is not JSON, and `X-Requested-With: XMLHttpRequest` only counts when the request accepts any content type. An unauthenticated stream therefore gets the redirect, and `EventSource` cannot follow one. Check the session before opening the stream, or treat a redirect as signed out.

## Related [#](https://weld.apps.wayne.edu/docs/guides/app-structure.html#related)

- [The container contract](https://weld.apps.wayne.edu/docs/guides/container-contract.html)
- [Queues and scheduling](https://weld.apps.wayne.edu/docs/guides/queues-and-scheduling.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
