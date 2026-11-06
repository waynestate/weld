---
title: What is welded in
description: The parts welded into WELD: WayneUI, CREED, Pinstripe, Preflight, PROCTOR, SESAME, HELMET and Ziggy, and what each does in the app.
---

# What is welded in

This page lists the parts welded into WELD, what each one does in the app, and what is on the bench today.

## The parts

| Part | What it does in WELD |
|---|---|
| WayneUI | The interface: Wayne State's design system, 2.0.8 from npm, pinned exactly. Its app bar, sidebar drawer, cards, forms and dialog draw every page, in light and dark, and ESLint keeps any other component kit out |
| CREED | The written standards and `policy.json`: supported PHP, Laravel and Node versions |
| Pinstripe | The coding standard: Pint, PHPStan level 8, ESLint and Prettier |
| Preflight | The CI pipeline, from GitLab CI/CD components |
| PROCTOR | The checks, run in CI and as the deploy gate |
| SESAME | Sign-in: CAS, Entra ID (OpenID Connect) and the auth gateway driver, and app-native sessions |
| HELMET | The baseline runtime: security headers and CSP, sessions, trusted proxies, `/up` |
| Ziggy | Route names as URLs: the route list ships inside the Vite bundle, so `route()` works in Vue templates and scripts without an inline script |

![WELD's journey, one request and six stops joined by a gold seam: HELMET sets security headers, SESAME signs the user in, WayneUI renders the page, PROCTOR checks the code, Preflight runs CI, Squire deploys.](../readme/journey.svg)

One request, six stops, one seam. The bead runs the length of it; with reduced motion on, it stays put.

## What is on the bench today

HELMET, SESAME and PROCTOR are welded in from Packagist; the rest of the cast is on Packagist too and gets welded on in turn.

- `composer.json` is `waynestate/weld`, requires PHP `^8.3`, and the lock file resolves for 8.3.0
- `.env.example` sets `APP_NAME=WELD`, `LOG_CHANNEL=stderr` and `SESSION_DRIVER=database`
- `Schema::defaultStringLength(191)` keeps index keys under Citadel's MySQL 5.6 767-byte cap
- `.gitattributes` sets `* text=auto eol=lf`
- `composer ci` is the CI contract: it runs validate, tests, Pint, PHPStan level 8, the agent-ready check and audit, the same command on GitLab and GitHub
- `pint.json` and `phpstan.neon` still copy Pinstripe's 0.1 Laravel presets; Pinstripe 0.1.1 is on Packagist (tagged), and they switch to `includes: vendor/waynestate/pinstripe/...` when WELD requires it
- HELMET runs its security headers and CSP middleware in the global chain, so every response carries the baseline; the Vite build takes the per-request nonce; trusted proxies are a literal list in `bootstrap/app.php`; `/up` stays Laravel's built-in health route
- SESAME owns sign-in at `/login` and `/logout`; the fake driver stands in for the identity provider locally, and CAS is an env change away
- PROCTOR measures WELD against CREED's `policy.json` as a CI job (`composer proctor`): warnings pass, failures block
- The starter kit's password auth (register, login, password reset, two-factor) is removed on purpose; the door is SESAME's
- WayneUI draws the Vue pages: `resources/css/app.css` imports its layered stylesheet, the pages use `@waynestate/wayne-ui-vue` components, and the appearance setting is WayneUI's own theme switch (`wsu-theme`), with the bar's sun and moon button in step. The starter kit's Tailwind theme and shadcn-vue copies are gone, and `npm run lint` fails on an import from a `components/ui` folder
- The license is MIT

## Related

- [Check your setup](check-your-setup.md)
- [Oracle](oracle.md)
- [Running WELD off Citadel](../running-off-citadel.md)
