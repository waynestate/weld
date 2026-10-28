# WELD brand files

**The mark is the weld bead itself.** A good TIG weld, seen from above, is a row of overlapping ripples, and every
welder knows it on sight. The tile is split along a seam at -30 degrees into two green plates. A fat gold bead, built
from overlapping circles with darker gold ripple arcs, runs up the seam to the puddle: a cream disc with a white-hot
core. Ahead of the puddle the seam is still an open dark gap. **The gap ahead of the puddle is the work still to
do.** (The line is the lead's.) Many parts, one piece, drawn literally.

There is no letterform in it, so nothing reads as the Wayne State W, a protected university mark. There are no sparks
on purpose: they read as AI sparkles or lightbulb rays. It holds at 32 px as a gold seam joining two halves, and it
animates naturally: the puddle advances and the gap closes.

(PROCTOR would worry if there were sparks. The first torch-and-plates attempt looked like an eyedropper over an open book. We do not talk about the eyedropper.
The second attempt, a row of gold crescents, looked like bananas. The bananas are retired too.)

## Colours
| Role | Colour |
|---|---|
| Upper plate | Wayne State green `#0C5449` |
| Lower plate | Deep green `#073A32` |
| The open gap ahead of the puddle | Near-black green `#021A16` |
| The weld bead | Wayne State gold `#FFCC33`, ripple arcs `#D69C00` |
| The puddle | Cream `#FFF4CC` disc, white `#FFFFFF` core |
| Plates on dark backgrounds | Lifted greens `#1A7566` and `#0C5449` |
| Light surface | Off-white `#F2F7F6` |
| Dark surface, terminal | Near-black `#0F1F1C` |

Gold never sits behind white text. The bead is always gold, except in the one-colour mark. There is no orange in the
mark.

## Files
| File | Use |
|---|---|
| `weld-mark.svg` | The mark on light backgrounds: the full tile, same art as `weld-tile.svg` |
| `weld-mark-white.svg` | The mark on dark backgrounds: plates lifted to lighter greens so the tile holds against near-black |
| `weld-mark-mono.svg` | One colour (`currentColor`): the tile with the bead and puddle knocked out and the ripple arcs drawn back in. Inline it; as an `<img>` it draws black |
| `weld-mark-animated.svg` | Front-page motion. The puddle advances up the seam, the ripples lay down one by one behind it, and the open gap ahead shrinks. About 2.2 seconds, plays once. With `prefers-reduced-motion: reduce` it shows the finished static mark |
| `weld-tile.svg` | The app icon: the finished mark on a 512 tile with corner radius 112 |
| `weld-favicon.svg` | The tile with heavier ripple arcs, for 32 and 64 px |
| `weld-favicon-16.svg` | A simplified 16 px drawing: a thick gold stroke and a white dot on the two-tone tile, no ripples |
| `weld-avatar-512.png` | The GitLab project avatar and the Open Graph image base |
| `favicon-16.png`, `favicon-32.png`, `favicon-64.png`, `favicon.ico` | Browser favicons. The `.ico` holds all three |
| `weld-board.png` | The 12-panel brand board |

## Geometry
Hand-written, on a 512 unit tile with corner radius 112. Everything sits on one axis, rotated -30 degrees about the
tile centre. The lower plate is the half-plane below the axis. The bead is 17 gold circles of radius 56, stepped 26
units apart, so each overlaps its neighbours by more than half. Each has a 5 unit `#D69C00` arc on its trailing side.
The puddle is a radius 56 cream disc with a radius 34 white core, offset 4 units forward. Ahead of it, a 6 unit
near-black line runs to the tile edge: the gap. No gradients, no sparks, no outlines.

## Clear space
Keep a tenth of the mark's width clear on every side.

## Rules
- Never add a letter to the mark, and never rework it until any part reads as a W. The Wayne State W is not ours.
- Never add sparks, glows or lightbulb rays.
- Never recolour the bead. Gold is the weld.
- Never stretch, mirror or rotate the mark. The weld always travels up and to the right.

## Motion
The animation is CSS inside the SVG. The puddle and the gap move linearly while each ripple fades in as the puddle
passes. Nothing flashes.

## Rendering
The PNGs were rendered from the SVGs with headless Edge at 1x. The `.ico` was assembled with Pillow from the three
favicon PNGs.

## The board
`weld-board.png` is composed in code from the real SVGs, so every mark on it is the true mark. It shows primary,
stacked, off-white and dark logos, app icon, standalone and one-colour marks, favicons, palette, type, `proctor check` in
use, and backgrounds.
