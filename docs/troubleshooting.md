---
title: Troubleshooting
description: When something does not hold: the common failures, what they mean, and what to run next.
---

# Troubleshooting

This page covers the failures that trip people up in WELD, with the cause of each, the fix, and a command to check that it holds again.

## Symptoms

Each entry follows the same shape: what you see, why it happens, how to fix it, and the command that proves it holds.

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
