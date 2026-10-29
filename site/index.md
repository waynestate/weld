---
title: "WELD: Many parts, one piece."
description: "WELD is the front page of the Wayne State developer stack: one Laravel + Inertia/Vue app with every part already joined. Clone it, rename it, deploy it."
url: https://weld.apps.wayne.edu/
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

![WELD: a welder's helmet](https://weld.apps.wayne.edu/brand/weld-avatar-512.png)

Wayne State's app stack, welded

# WELD

WELD stands for Wired, Everything, Linked, Deployed.

Many parts, one piece.

One Laravel + Inertia/Vue app with every part of the stack already joined: WayneUI, CREED, Pinstripe, Preflight, PROCTOR, SESAME and HELMET. Clone it, rename it, deploy it.

`$ composer create-project waynestate/weld my-app`

[Get started](https://weld.apps.wayne.edu/#paths) [Meet the cast](https://weld.apps.wayne.edu/#cast-title)

## The journey

One request, six stops, from the edge of the network to the deploy.

1. [HELMET](https://helmet.apps.wayne.edu/) sets security headers
2. [SESAME](https://sesame.apps.wayne.edu/) signs the user in
3. [WayneUI](https://wayneui.apps.wayne.edu/) renders the page
4. [PROCTOR](https://proctor.apps.wayne.edu/) checks the code
5. [Preflight](https://preflight.apps.wayne.edu/) runs CI
6. [Squire](https://squire.apps.wayne.edu/) deploys

Every part, its own character

## The cast

Seven parts welded into WELD, five that work the shop floor around it, one sibling that bakes your own, and the crew still on the drawing board.

![WayneUI mark: a rounded frame holding a solid form, with a gold tab on the lower edge](https://weld.apps.wayne.edu/assets/marks/wayneui.svg)

### [WayneUI](https://wayneui.apps.wayne.edu/)

2.0 Contrast | AA

The interface. Wayne State's design system, wired into the Vue front end and the Blade layouts.

```
$ pnpm verify
✓ tokens     built
✓ contrast   AA, every pair
✓ axe        0 violations
a11y: 100
$
```

![CREED mark: a stone arch whose gold keystone holds the other stones in place](https://weld.apps.wayne.edu/assets/marks/creed.png)

### [CREED](https://creed.apps.wayne.edu/)

0.1 Volume | Eleven

The written standards, and the `policy.json` every other part reads.

```
$ creed policy show
php      8.3, 8.4
laravel  12, 13
node     22 LTS
volume   eleven
$
```

![HELMET mark: a white football helmet with a gold facemask](https://weld.apps.wayne.edu/assets/marks/helmet.png)

### [HELMET](https://helmet.apps.wayne.edu/)

0.2 Fit | Snug

Security headers and runtime hardening.

```
$ helmet headers
CSP      strict, nonce
HSTS     1 year
frames   DENY
$
```

![SESAME mark: an open door with a gold gap](https://weld.apps.wayne.edu/assets/marks/sesame.png)

### [SESAME](https://sesame.apps.wayne.edu/)

0.2 Says | Open

Sign-in: CAS, Entra ID and the auth gateway.

```
$ sesame login
→ off to CAS and back
✓ signed in as ab1234
✓ session: app-native
$
```

![PROCTOR mark: a watchful eye with a gold square pupil](https://weld.apps.wayne.edu/assets/marks/proctor.png)

### [PROCTOR](https://proctor.apps.wayne.edu/)

0.2 Strictness | Kind

One command that checks an app against the standards.

```
$ proctor check
✓ php 8.4 supported
✓ headers present
✓ 42 passed, 0 failed
$
```

![Preflight mark: a runway with a gold centre line](https://weld.apps.wayne.edu/assets/marks/preflight.png)

### [Preflight](https://preflight.apps.wayne.edu/)

0.1 Checklist | Long

Shared CI, from GitLab CI/CD components.

```
$ preflight run
✓ lint         pass
✓ test         312 pass
✓ portability  pass
$
```

![Pinstripe mark: a striped necktie with a gold tie bar](https://weld.apps.wayne.edu/assets/marks/pinstripe.png)

### [Pinstripe](https://pinstripe.apps.wayne.edu/)

0.1 Creases | Sharp

The coding standard: Pint, PHPStan level 8, ESLint and Prettier, pressed and ready.

```
$ pinstripe apply
✓ pint      14 files pressed
✓ phpstan   level 8, 0 errors
✓ eslint    clean
style: sharp
$
```

![Booster mark: a syringe with fins, lifting off on a gold flame](https://weld.apps.wayne.edu/assets/marks/booster.png)

### [Booster](https://booster.apps.wayne.edu/)

0.1 Sting | Mild

Upgrades, one hop at a time: Rector rewrites the code, your tests sign off, a person merges.

```
$ booster plan
1. laravel-10-to-11
2. laravel-11-to-12
3. php-82-to-83
$
```

![Rollcall mark: an open register with gold ticks](https://weld.apps.wayne.edu/assets/marks/rollcall.png)

### [Rollcall](https://rollcall.apps.wayne.edu/)

0.1 Attendance | Taken

The fleet scanner: every app answers “present”, with its PHP, framework and auth, read against the policy.

```
$ rollcall report
✓ snapshot   today
drift      none
back row   PHP 7.4, waving
$
```

![Squire mark: a satchel with a gold buckle](https://weld.apps.wayne.edu/assets/marks/squire.png)

### [Squire](https://squire.apps.wayne.edu/)

0.2 Armour | Carried

The Citadel kit: build.sh, the release script, the health check, and local parity on the real images.

```
$ squire doctor
✓ build.sh     packages only
✓ release.sh   idempotent
✓ /up          answers
$
```

![Drawbridge mark: a castle tower lowering its bridge on a gold chain](https://weld.apps.wayne.edu/assets/marks/drawbridge.png)

### [Drawbridge](https://drawbridge.apps.wayne.edu/)

0.1 Moat | Full

The sign-in gateway in front of the apps: one OpenID Connect client through Entra ID, so each app only checks one small signed token.

```
$ drawbridge config:validate
✓ oidc        Entra ID, PKCE
✓ protected   /admin/
✓ headers     stripped, every spelling
bridge: lowered
$
```

![ADVISOR mark: a speech bubble holding a gold compass needle](https://weld.apps.wayne.edu/assets/marks/advisor.png)

### [ADVISOR](https://advisor.apps.wayne.edu/)

0.1 Office hours | Always

The stack's own MCP server: read-only tools that tell your coding agent what applies to your app, and what to do next.

```
$ advisor mcp
✓ serving   stdio
✓ tools     read-only
answers: from policy.json
$
```

![Sourdough mark: a starter jar with a gold band](https://weld.apps.wayne.edu/assets/marks/sourdough.png)

### [Sourdough](https://sourdough.apps.wayne.edu/)

0.1 Rise | Daily

Starters you feed. Pick Sourdough when you want a starter you shape yourself; pick WELD when you want the finished piece.

```
$ sourdough new --raw
Next steps:
  cd my-app
  composer install
$
```

### Next off the line

- Rolodex next
  Person data with types, so nobody has to flip through cards by hand.

## Pick your path

Three shapes, one stack. WELD is the Inertia/Vue shape, already joined; Sourdough bakes the others.

From Sourdough

### Raw PHP

No framework, no build: plain PHP on WayneUI's standalone path.

`composer global require waynestate/sourdough sourdough new my-app --raw`

From Sourdough Coming soon

### Laravel + Blade

The classic WSU shape: Laravel with Blade views. Sourdough has the plan for it, but 0.1 doesn't bake it yet; it rises in a later release.

This is WELD

### Laravel + Inertia/Vue

Inertia and Vue with every part already joined, the way WELD runs.

`composer create-project waynestate/weld my-app`

Hand it to your agent

## Job tickets

Two tickets, ready for the coding agent you already use. Copy one, or run it straight in OpenCode, Claude Code, Codex or ChatGPT. Each one says what is already welded, what the agent may not touch, and to ask before it guesses.

### Get started with WELD

Clone it, bring it up in Docker, run the doctor and the gate, and plan the rename that makes it yours.

A prompt for your coding agent. Copy it as it stands:

```text
You are a coding agent helping a Wayne State University developer start a new app from WELD: the Wayne State application stack welded into one Laravel app with Inertia and Vue. Sign-in (SESAME), security headers (HELMET), the standards check (PROCTOR) and CI are already joined. The job is to get it running and make it this developer's own.

Read first:
- https://weld.apps.wayne.edu/llms.txt, the index of WELD's pages, each one as Markdown. Fetch the pages you need; do not work from memory.
- ADVISOR, the stack's MCP server, if it is connected: get_project_status and read_project_docs for weld answer from the stack's own docs, and policy_lookup gives today's PHP, Laravel and Node support from CREED. If it is not connected, say so and work from the pages. How to add it: https://advisor.apps.wayne.edu/llms.txt

Task:
1. Clone WELD into a new folder with `git clone https://github.com/waynestate/weld.git my-app`, using the app name I give you in place of my-app.
2. Bring it up with Docker, the only prerequisite: `.\bin\weld.ps1 up` on Windows (PowerShell), `bin/weld.sh up` on macOS and Linux. It copies .env.example to .env, installs the PHP and Node dependencies in the containers, sets the app key and runs the migrations.
3. Check the setup with the doctor subcommand (`.\bin\weld.ps1 doctor` or `bin/weld.sh doctor`) and report every line that is not PASS.
4. Run the gate with the test subcommand, which runs `composer ci` in the app container, and the standards check with `composer proctor` (in the app container if the host has no PHP).
5. Propose what to rename to make it mine: APP_NAME in .env.example and the package name in composer.json. Do not change them yet.

Constraints:
- WELD is Laravel with Inertia and Vue. If I want raw PHP or Laravel with Blade, stop and point me at Sourdough: https://sourdough.apps.wayne.edu/llms.txt
- Sign-in is SESAME's, at /login and /logout, and its fake driver stands in locally. Never add password auth back.
- Never commit .env, and never paste a secret into a prompt or a file.
- Give separate commands for Windows (PowerShell), macOS and Linux wherever they differ.
- Ask me before you guess: an app name, a check that fails for a reason you cannot explain, or anything that touches a deploy.

Output: what you ran and what it said, the doctor report, then the rename plan as numbered steps, then your questions.
```

### Add a signed-in page to a WELD app

One route behind SESAME, one Vue page in the app's layout, one test, and the gate run green.

A prompt for your coding agent. Copy it as it stands:

```text
You are a coding agent adding one signed-in page to an app built from WELD: the Wayne State application stack welded into one Laravel app with Inertia and Vue, at Wayne State University.

Read first:
- https://weld.apps.wayne.edu/llms.txt, then the pages it lists. BEST_PRACTICES.md in this repo says what each practice expects and which stack tool checks it.
- ADVISOR, the stack's MCP server, if it is connected: call list_stack_rules with permutation laravel-inertia-vue, and read_project_docs for weld and sesame. If it is not connected, say so and work from the pages.
- routes/web.php, resources/js/pages/Dashboard.vue, resources/js/layouts/ and tests/Feature/DashboardTest.php.

Task:
1. If I have not said what the page is for, its URL and who may see it, ask.
2. Add the route in routes/web.php the way the dashboard route does it: Inertia::render for a page in resources/js/pages, behind the auth.sesame middleware, with a route name.
3. Write the Vue page with script setup and TypeScript, inside AppLayout with breadcrumbs, the way Dashboard.vue does.
4. Add a feature test beside DashboardTest.php: a guest is sent to /login, and a user signed in with the fake driver gets the page.
5. Run the gate: `composer ci`, or the test subcommand of bin/weld.sh (macOS, Linux) or bin\weld.ps1 (Windows) when the host has no PHP.

Constraints:
- Sign-in belongs to SESAME: protect the route with auth.sesame, and never add password auth.
- HELMET sends a strict CSP with a per-request nonce: no inline scripts, and no new script hosts.
- Keep PHPStan at level 8 and Pint clean. Never lower either to get the gate green.
- Ask me before you guess, and before you add a migration, a Composer or npm package, or anything that touches a deploy.

Output: the files you changed, the gate's result, then your questions.
```

The agent reads the same pages you do: [llms.txt](https://weld.apps.wayne.edu/llms.txt) lists them, [llms-full.txt](https://weld.apps.wayne.edu/llms-full.txt) holds them all in one file, and this page has a [Markdown twin](https://weld.apps.wayne.edu/index.md). Nothing here phones home; the buttons only copy text or open the tool you picked. A good bead is a quiet one, and so is a good agent run.
