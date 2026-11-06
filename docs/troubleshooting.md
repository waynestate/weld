---
title: Troubleshooting
description: When something does not hold: the common failures, what they mean, and what to run next.
---

# Troubleshooting

This page covers the failures that trip people up in WELD, with the cause of each, the fix, and a command to check that it holds again.

## Symptoms

Each entry follows the same shape: what you see, why it happens, how to fix it, and the command that proves it holds.

### The page is blank except the `local` ribbon

**Cause.** The page's scripts come from the Vite dev server, at the address Laravel's Vite plugin writes into `public/hot`. Copies of WELD from before 2026-11-06 ran `vite --host` in the container, so the plugin wrote the address it listened on, `http://[::]:5173`, which the browser cannot reach. On any copy, the very first load after `up` also waits while Vite compiles every module for the first time, which takes 10 to 20 seconds on a Windows bind mount.

**Fix.** Take the current `compose.yaml` (its `vite` service runs `npm run dev` with `LARAVEL_SAIL: '1'`), restart Vite, and give the first load a moment:

```
docker compose up -d vite
```

**Check.** `public/hot` names an address your browser can open:

```
docker compose exec vite cat public/hot
```

Expected: `http://localhost:5173`.

### A new page says "Page not found" (Windows)

**Cause.** Docker Desktop on Windows passes edits to existing files into the Vite container, but not new files. Vite builds the list of pages when it starts, so a page added after `up` is missing from it, and Inertia throws `Page not found: ./pages/YourPage.vue` in the browser console.

**Fix.** Restart Vite after adding a page:

```
docker compose restart vite
```

**Check.** Reload the page: it draws, and the console is quiet.

### The local database was empty after `up` (Windows)

**Cause.** Copies of `bin/weld.ps1` from before 2026-11-06 created `database/database.sqlite` with `New-Item -Force` on every `up`, which empties a file that already exists. `bin/weld.sh` uses `touch` and never did.

**Fix.** Take the current `bin/weld.ps1`, which creates the file only when it is missing. The lost rows do not come back; run the migrations and seeders again.

**Check.** Sign in, run `.\bin\weld.ps1 up` again, and sign in once more: your user is still there.

### `composer ci` fails because the docs site is stale

**Cause.** The docs at `site/docs/` are built from `docs/` by the shared docs builder, and `composer ci` runs `composer docs:check`, which fails when the committed docs site is stale or the docs carry findings. Change a page in `docs/` or `CHANGELOG.md` without rebuilding, and the check fails.

**Fix.** Rebuild the site from the source and commit the output. Do not edit `site/` by hand; the builder writes it, so the page you read is the page that ships.

```
composer docs
```

`composer docs` writes `site/docs/` from `docs/` and generates `docs/changelog.md` from `CHANGELOG.md`.

**Check.** Run the same check that `composer ci` runs; it writes nothing:

```
composer docs:check
```

### The agent-ready files are stale

**Cause.** The agent-ready site (prompt blocks, Markdown twins, llms.txt) comes from Squire's kit and is generated into `site/`. `composer ci` runs `composer site:agent-ready-check`, which fails when the agent-ready files are stale, a page lacks its Markdown link, a prompt drifts from the docs, or the kit was edited here.

**Fix.** Regenerate the files and commit the result:

```
composer site:agent-ready
```

That rewrites the Markdown twins, llms.txt, llms-full.txt, robots.txt and sitemap.xml in `site/`.

**Check.**

```
composer site:agent-ready-check
```

### regen-check reports new drift

**Cause.** The regen gate compares a freshly generated Sourdough Inertia app with WELD file by file, from Sourdough's `.sourdough/manifest.json` (each path with its SHA-256). It skips the product files listed in `.sourdough/product-files.json` and any `vendor` or `node_modules` path, and reports each remaining path as IDENTICAL, DIFFERS, MISSING_IN_WELD or RELOCATED (a Squire kit file WELD keeps byte-identical under `deploy/`). The gate is a ratchet: `.sourdough/regen-baseline.json` records the drift already known and accepted, and the check fails only on new drift, so a change in what Sourdough generates surfaces here.

**Fix.** Fix the drift here or upstream in Sourdough. If the drift is accepted as intended, record the current drift as accepted and commit `.sourdough/regen-baseline.json`:

```
php bin/regen-check.php --generated <dir> --write-baseline
```

> [!NOTE]
> The regen gate is not part of `composer ci`. CI runs it as a separate, warning-only job, and the GitHub pipeline keeps it warning-only with `continue-on-error` until the baseline is filled in.

**Check.** Re-run the check against your own generation; it passes once every path is IDENTICAL or already recorded in the baseline:

```
php bin/regen-check.php --generated <dir>
```

Through Composer the same check is `composer regen-check -- --generated <dir>`.

## Related

- [Running WELD off Citadel](running-off-citadel.md)
- [Is WELD still Sourdough's shape?](guides/regen-check.md)
- [Check your setup](guides/check-your-setup.md)
- [Changelog](changelog.md)
