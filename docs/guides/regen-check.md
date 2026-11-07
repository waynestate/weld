---
title: Is WELD still Sourdough's shape?
description: Whether WELD is still Sourdough's Inertia shape: the regen gate compares a fresh generation with WELD and fails only on new drift.
---

# Is WELD still Sourdough's shape?

This page shows how the regen gate proves that WELD is still Sourdough's Inertia shape, and how to run it against your own generation.

## How the check works

Sourdough writes a manifest of every file it generates (`.sourdough/manifest.json`: each path with its SHA-256).
`bin/regen-check.php` compares that manifest with WELD file by file, skipping the product files WELD adds on top
(listed in `.sourdough/product-files.json`) and any path with a `vendor` or `node_modules` segment. Each remaining
path is reported as IDENTICAL, DIFFERS or MISSING_IN_WELD.

The generated app folder must contain `.sourdough/manifest.json`, the file list Sourdough writes into every app it
generates (files maps path to `{class, sha256}`). Paths listed in `.sourdough/product-files.json` (exact paths or
`dir/` prefixes) and every path with a `vendor` or `node_modules` segment at any depth are skipped. A DIFFERS path
means the WELD file exists with another SHA-256.

## The ratchet baseline

The gate is a ratchet: `.sourdough/regen-baseline.json` records the drift already known and accepted, and the check
fails only on new drift. CI runs it against a fresh `sourdough new --inertia` generation from Sourdough's main, and
the GitLab `regen-gate` job blocks on new drift; locally, point it at your own generation. When it reports new drift,
fix it here or upstream in Sourdough; if the drift is accepted as intended, record the current drift as accepted with
`--write-baseline` and commit `.sourdough/regen-baseline.json`.

## Running the check

Run the check:

```sh
php bin/regen-check.php --generated <dir>
```

or through Composer:

```sh
composer regen-check -- --generated <dir>
```

Record the current drift as accepted: add `--write-baseline`.

> [!NOTE]
> Exit codes: 0 no new drift (or the baseline was written), 1 new drift, listed on stderr, 2 usage error. The script
> is plain PHP 8.3 with no Composer packages, so it runs on Windows, macOS and Linux the same way.

## Related

- [WELD and Sourdough](sourdough.md)
- [Status](../reference/status.md)
- [Changelog](../changelog.md)
