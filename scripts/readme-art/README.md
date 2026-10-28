# WELD README art kit

Regenerates the README images under `docs/readme/` from this repo's own static
front page (`site/`) and brand files. Nothing is drawn by hand: the hero and
the gallery are screenshots of the real page, the journey diagram is built
from the page's own journey section, the cast is the marks resized, and the
badges read their facts from `composer.json`.

## Run

Windows PowerShell and Linux/macOS sh, side by side:

| Windows PowerShell | Linux / macOS sh |
|---|---|
| `npm --prefix scripts/readme-art ci` | `npm --prefix scripts/readme-art ci` |
| `npx --prefix scripts/readme-art playwright install chromium` | `npx --prefix scripts/readme-art playwright install chromium` |
| `node scripts/readme-art/build.mjs` | `node scripts/readme-art/build.mjs` |

The script resolves every path from its own location, so it works from any
folder on any OS. It opens `site/index.html` as a `file://` URL (no server,
no network) and is deterministic: same inputs, same outputs.

## What it makes

- `docs/readme/hero.jpg`: the hero, with the site header and seam progress
  hidden.
- `docs/readme/journey.svg`: the six stops as a self-contained animated SVG
  (the bead cools to a still diagram under `prefers-reduced-motion`).
- `docs/readme/gallery.jpg`: a contact sheet of three mobile crops.
- `docs/readme/cast/*.png`: one 96×96 mark per stack part.
- `docs/readme/badges/*.svg`: `php`, `licence` and `laravel`, generated
  locally by `badge-maker` from `composer.json`.

Every file must stay at or under 300 KB; the script prints each size and
exits 1 if one grows past it.

If a badge ever looks wrong, the `composer.json` fact is wrong, not the
badge.
