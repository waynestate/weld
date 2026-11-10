---
title: "What is welded in | WELD docs"
description: "The parts welded into WELD: WayneUI, CREED, Pinstripe, Preflight, PROCTOR, SESAME, HELMET, ADVISOR, Squire and Ziggy, and what each does in the app."
url: https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# What is welded in [#](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html#what-is-welded-in)

This page lists the parts welded into WELD, what each one does in the app, and what is on the bench today.

## The parts [#](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html#the-parts)

| Part | What it does in WELD |
| --- | --- |
| WayneUI | The interface: Wayne State's design system, 2.0.8 from npm, pinned exactly. Its app bar, sidebar drawer, cards, forms and dialog draw every page, in light and dark, and ESLint keeps any other component kit out |
| CREED | The written standards and `policy.json`: supported PHP, Laravel and Node versions |
| Pinstripe | The coding standard, 0.3 in `require-dev`: PHPStan includes its Laravel preset (level 8 with Larastan, and the banned calls, SQL joined from strings among them) and Pint runs its Laravel preset. ESLint and Prettier run on WELD's own config until its npm half moves in |
| Preflight | The CI pipeline, from GitLab CI/CD components pinned at 0.5.0: tests on PHP 8.3, 8.4 and 8.5, audit, PROCTOR, accessibility, SBOM, secret detection and portability |
| PROCTOR | The checks, run in CI and as the deploy gate |
| SESAME | Sign-in: CAS, Entra ID (OpenID Connect) and the auth gateway driver, and app-native sessions |
| HELMET | The baseline runtime: security headers and CSP, sessions, trusted proxies, `/up` and the `/ready` readiness route |
| ADVISOR | The stack's read-only MCP server for coding agents, in `require-dev` |
| Squire | The deploy kit in `deploy/`, at 0.10.5: the Citadel image, an idempotent release script and one portable image for other hosts |
| Ziggy | Route names as URLs: the route list ships inside the Vite bundle, so `route()` works in Vue templates and scripts without an inline script |

![WELD's journey, one request and six stops joined by a gold seam: HELMET sets security headers, SESAME signs the user in, WayneUI renders the page, PROCTOR checks the code, Preflight runs CI, Squire deploys.](https://weld.apps.wayne.edu/docs/readme/journey.svg)

One request, six stops, one seam. The bead runs the length of it; with reduced motion on, it stays put.

## What is on the bench today [#](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html#what-is-on-the-bench-today)

Every part is welded in: HELMET and SESAME are in `require`; CREED, Pinstripe, PROCTOR and ADVISOR are in `require-dev`; WayneUI 2.0.8 comes from npm; CI pins Preflight 0.5.0; the deploy layer runs Squire's kit 0.10.5. Pinstripe's PHP presets are installed; its ESLint and Prettier presets move in once `@waynestate/pinstripe` 0.3.0 is on npm ([the steps](https://weld.apps.wayne.edu/docs/reference/status.html#pinstripe-s-npm-half)).

- `composer.json` is `waynestate/weld`, requires PHP `^8.3`, and the lock file resolves for 8.3.0
- `.env.example` sets `APP_NAME=WELD`, `LOG_CHANNEL=stderr` and `SESSION_DRIVER=database`
- `Schema::defaultStringLength(191)` keeps index keys under Citadel's MySQL 5.6 767-byte cap
- `.gitattributes` sets `* text=auto eol=lf`
- `composer ci` is the CI contract: it runs validate, tests, Pint, PHPStan level 8, the agent-ready check, the practices sync check, the docs check and audit, the same command on GitLab and GitHub
- `phpstan.neon` includes `vendor/waynestate/pinstripe/phpstan/laravel.neon` and adds only WELD's paths, so the level, the ignore rules and the banned calls (`eval()`, `dd()`, TLS checks turned off, SQL joined from strings) come from Pinstripe 0.3; `pint.json` is Pinstripe's `pint/laravel.json` plus two recorded excludes, Squire's `deploy/` kit and its vendored `bin/docs-site.php`, and `tests/Unit/PinstripeWiringTest.php` fails if either file drifts from that
- HELMET runs its security headers and CSP middleware in the global chain, so every response carries the baseline; the Vite build takes the per-request nonce; trusted proxies come from `TRUSTED_PROXIES` (`bootstrap/app.php`); `/up` stays Laravel's built-in liveness route, and `/ready` checks the database, the cache and the scheduler heartbeat
- SESAME owns sign-in at `/login` and `/logout`; the fake driver stands in for the identity provider locally, and CAS is an env change away
- PROCTOR measures WELD against CREED's `policy.json` as a CI job (`composer proctor`): warnings pass, failures block
- The starter kit's password auth (register, login, password reset, two-factor) is removed on purpose; the door is SESAME's
- WayneUI draws the Vue pages: `resources/css/app.css` imports its layered stylesheet, the pages use `@waynestate/wayne-ui-vue` components, and the appearance setting is WayneUI's own theme switch (`wsu-theme`), with the bar's sun and moon button in step. The starter kit's Tailwind theme and shadcn-vue copies are gone, and `npm run lint` fails on an import from a `components/ui` folder
- The license is MIT

## Related [#](https://weld.apps.wayne.edu/docs/guides/whats-welded-in.html#related)

- [Check your setup](https://weld.apps.wayne.edu/docs/guides/check-your-setup.html)
- [Oracle](https://weld.apps.wayne.edu/docs/guides/oracle.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
