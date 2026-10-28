# WELD brand files

**The mark is a torch laying a weld bead.** Two plates sit side by side, a gold bead fills the joint, and a welding
torch comes in from the upper right with a spark burst at its tip. Many parts, one piece. There is no letterform in it:
the mark is deliberately nothing like the Wayne State W, which is a protected university mark.

Why a torch and not a letter: the bead across two plates is the whole idea, and a torch with sparks says "welding" at a
glance, even at 32 px. The spark is the one hot accent; everything else is Wayne State green, gold and off-white.
(Yes, the sparks are normal. `weld doctor` would worry if they were not.)

## Colours
| Role | Colour |
|---|---|
| Plates and torch on a light background, the tile | Wayne State green `#0C5449` |
| The spark (the only accent) | Hot orange `#FF7A1A`, white core |
| The weld bead and torch nozzle (the only gold) | Wayne State gold `#FFCC33` |
| Plates on dark backgrounds | White `#FFFFFF` |
| Light surface, plates and torch on the tile | Off-white `#F2F7F6` |
| Dark surface, terminal | Near-black `#0F1F1C` |

Gold never sits behind white text. The bead is always gold, except in the one-colour mark.

## Files
| File | Use |
|---|---|
| `weld-mark.svg` | The mark on light backgrounds: green plates and torch, gold bead and nozzle, orange spark |
| `weld-mark-white.svg` | The mark on dark or green backgrounds: white plates and torch |
| `weld-mark-mono.svg` | One colour (`currentColor`), for print and single-ink use. Inline it; as an `<img>` it draws black |
| `weld-mark-animated.svg` | Front-page motion. The torch drops in, a spark flashes once, and the bead rises across the joint. About 1.2 seconds, plays once. Plates and torch follow light and dark. With `prefers-reduced-motion: reduce` the animation is off and the page shows the finished mark minus the spark |
| `weld-tile.svg` | The app icon: the mark on a rounded green tile, plates and torch in off-white |
| `weld-favicon.svg` | The tile at 32 and 64 px |
| `weld-favicon-16.svg` | Hand-hinted on the 16 px grid. One unit is one pixel: plates, bead, torch bar and a spark |
| `weld-avatar-512.png` | The GitLab project avatar and the Open Graph image base |
| `favicon-16.png`, `favicon-32.png`, `favicon-64.png`, `favicon.ico` | Browser favicons. The `.ico` holds all three |
| `weld-board.png` | The 12-panel brand board |

## Geometry
Hand-written paths on a 512 unit grid, not traced. Two plates run from x = 56 to 456 with a 28 unit joint at x = 242 to
270. The bead is one gold path: an arch over the joint that drops into it as a stem. The torch is a single group
(nozzle, collar, neck, handle) rotated 44 degrees about the nozzle tip at (256, 292). The spark is six tapered rays and
a round core centred at (256, 300). The tile is the same drawing, scaled 1.1, on a 512 square with corner radius 112.
No masks, no gradients, no traced bitmaps.

## Clear space
Keep a tenth of the mark's width clear on every side. The viewBox of each mark SVG already carries that margin.

## Rules
- Never add a letter to the mark, and never redraw it so any part reads as a W. The Wayne State W is not ours.
- Never recolour the bead. Gold is the weld.
- Never add a glow or gradient to the spark. It is flat orange `#FF7A1A` with a white core.
- Never stretch, rotate or mirror the mark.

## Motion
The animation is CSS inside the SVG. The torch slides in from the upper right, the spark flashes once, and the bead
rises across the joint. Nothing flashes more than once.

## Rendering
The PNGs were rendered from the SVGs with headless Edge at 1x. The `.ico` was assembled with Pillow from the three
favicon PNGs.

## The board
`weld-board.png` is composed in code from the real SVGs, so every mark on it is the true mark. It shows primary,
stacked, reversed and dark logos, app icon, standalone and one-colour marks, favicons, palette, type, `weld doctor` in
use, and backgrounds.
