<p align="center">
  <a href="site/index.html"><img src="docs/readme/hero.jpg" width="100%" alt="WELD's front page: the headline WELD, 'Wired, Everything, Linked, Deployed. Many parts, one piece.', the composer create-project command, and the welder's-helmet mark on a green tile."></a>
</p>

# WELD

<p>
  <img src="docs/readme/badges/php.svg" alt="php ^8.3">
  <img src="docs/readme/badges/laravel.svg" alt="laravel ^13.29">
  <img src="docs/readme/badges/license.svg" alt="licence MIT">
  <a href="https://github.com/waynestate/weld/actions/workflows/ci.yml"><img src="https://github.com/waynestate/weld/actions/workflows/ci.yml/badge.svg" alt="GitHub CI"></a>
</p>

**W**ired, **E**verything, **L**inked, **D**eployed. Many parts, one piece.

WELD is the whole Wayne State app stack joined into one application. Install it, deploy it, and every part is already
hooked up: sign-in, security headers, checks, coding standards, CI and the WayneUI interface. Think of it as the
Megazord, except it comes with a deploy script and nobody has to shout to make it combine.

## Get it

WELD is an app you start from, not a library you require — the Megazord ships in a repo, not a box.
Clone it and it is yours to rename:

```
git clone https://github.com/waynestate/weld.git my-app
cd my-app
composer install
npm ci
# Windows (PowerShell):
Copy-Item .env.example .env
# Linux/macOS:
cp .env.example .env
php artisan key:generate
```

## What is welded in

| Part | What it does in WELD |
|---|---|
| WayneUI | The interface: Wayne State's design system, wired into the Vue front end and Blade layouts |
| CREED | The written standards and `policy.json`: supported PHP, Laravel and Node versions |
| Pinstripe | The coding standard: Pint, PHPStan level 8, ESLint and Prettier |
| PREFLIGHT | The CI pipeline, from GitLab CI/CD components |
| PROCTOR | The checks, run in CI and as the deploy gate |
| SESAME | Sign-in, with CAS today and the auth gateway later, and app-native sessions |
| HELMET | The baseline runtime: security headers and CSP, sessions, trusted proxies, `/up` |

<p align="center">
  <img src="docs/readme/journey.svg" width="100%" alt="WELD's journey, one request and six stops joined by a gold seam: HELMET sets security headers, SESAME signs the user in, WayneUI renders the page, PROCTOR checks the code, PREFLIGHT runs CI, SQUIRE deploys.">
</p>
<p align="center"><sub>One request, six stops, one seam. The bead runs the length of it; with reduced motion on, it stays put.</sub></p>

<details>
<summary><b>See the front page</b>: three cuts from <code>site/</code> at phone width</summary>
<br>
<p align="center"><img src="docs/readme/gallery.jpg" width="100%" alt="Three phone-width crops of WELD's front page on the green shop floor: the hero with the helmet mark, the start of 'The cast' with WayneUI's card, and 'Pick your path' with the raw PHP and Laravel + Blade cards."></p>
</details>

## Status

The Laravel base is in. The stack packages arrive once they are on Packagist, and each one gets welded on as it lands.
Until then, WELD runs on the starter kit plus these stack defaults:

- `composer.json` is `waynestate/weld`, requires PHP `^8.3`, and the lock file resolves for 8.3.0
- `.env.example` sets `APP_NAME=WELD`, `LOG_CHANNEL=stderr` and `SESSION_DRIVER=database`
- `Schema::defaultStringLength(191)` keeps index keys under Citadel's MySQL 5.6 767-byte cap
- `.gitattributes` sets `* text=auto eol=lf`
- `composer ci` is the CI contract: it runs validate, tests, Pint, PHPStan level 8 and audit, the same command on
  GitLab and GitHub
- `pint.json` and `phpstan.neon` copy Pinstripe's 0.1 Laravel presets (Pinstripe is not on Packagist yet); they
  switch to `includes: vendor/waynestate/pinstripe/...` once it lands
- The starter kit's password auth (register, login, password reset, two-factor) is removed on purpose; SESAME owns
  sign-in at `/login`, `/logout` and `/cas/*`, and `/login` answers 503 until SESAME is welded in
- The license is MIT

Best practices for building, running and deploying WELD live in [BEST_PRACTICES.md](BEST_PRACTICES.md).

## Run it locally

The same commands work on Windows, macOS and Linux.

1. `composer install`
2. Copy `.env.example` to `.env`
3. `php artisan key:generate`
4. `php artisan migrate`
5. `npm ci`
6. `npm run build`
7. `composer run dev`

Then open http://localhost:8000.

## WELD and Sourdough

Sourdough makes a new app in any of the stack's shapes: raw PHP, Laravel with Blade, Laravel with Inertia and Vue.
WELD is one of those shapes, Laravel with Inertia and Vue, with every part already joined and proved to work together.
Pick Sourdough when you want a starter you shape yourself. Pick WELD when you want the finished piece.

## The cast

<p align="center">
  <a href="https://github.com/waynestate/wayne-ui" title="WayneUI"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/cast/wayneui-dark.png"><img src="docs/readme/cast/wayneui.png" width="48" alt="WayneUI"></picture></a>
  <a href="https://github.com/waynestate/creed" title="CREED"><img src="docs/readme/cast/creed.png" width="48" alt="CREED"></a>
  <a href="https://github.com/waynestate/proctor" title="PROCTOR"><img src="docs/readme/cast/proctor.png" width="48" alt="PROCTOR"></a>
  <a href="https://github.com/waynestate/sesame" title="SESAME"><img src="docs/readme/cast/sesame.png" width="48" alt="SESAME"></a>
  <a href="https://github.com/waynestate/helmet" title="HELMET"><img src="docs/readme/cast/helmet.png" width="48" alt="HELMET"></a>
  <a href="https://github.com/waynestate/sourdough" title="Sourdough"><img src="docs/readme/cast/sourdough.png" width="48" alt="Sourdough"></a>
  <a href="https://github.com/waynestate/squire" title="SQUIRE"><img src="docs/readme/cast/squire.png" width="48" alt="SQUIRE"></a>
  <a href="https://github.com/waynestate/preflight" title="PREFLIGHT"><img src="docs/readme/cast/preflight.png" width="48" alt="PREFLIGHT"></a>
  <a href="https://github.com/waynestate/pinstripe" title="Pinstripe"><img src="docs/readme/cast/pinstripe.png" width="48" alt="Pinstripe"></a>
  <a href="https://github.com/waynestate/booster" title="BOOSTER"><img src="docs/readme/cast/booster.png" width="48" alt="BOOSTER"></a>
  <a href="https://github.com/waynestate/rollcall" title="ROLLCALL"><img src="docs/readme/cast/rollcall.png" width="48" alt="ROLLCALL"></a>
  <a href="https://github.com/waynestate/drawbridge" title="DRAWBRIDGE"><img src="docs/readme/cast/drawbridge.png" width="48" alt="DRAWBRIDGE"></a>
  <a href="https://github.com/waynestate/advisor" title="ADVISOR"><img src="docs/readme/cast/advisor.png" width="48" alt="ADVISOR"></a>
  <a href="https://github.com/waynestate/weld" title="WELD"><img src="docs/readme/cast/weld.png" width="48" alt="WELD"></a>
</p>

<p align="center"><sub>Fourteen parts, one bead. The README art is cut from the front page by
<a href="scripts/readme-art/README.md"><code>scripts/readme-art</code></a>; no sparks were drawn in its making.</sub></p>
