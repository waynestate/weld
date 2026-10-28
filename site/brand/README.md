# WELD brand files

**The mark is the weld bead itself.** A good TIG weld, seen from above, is a row of overlapping crescent ripples, and
every welder knows it on sight. The tile is split along a slanted seam into two green fields, a row of eight gold
ripples is laid down the seam, and a white-hot dot sits at the leading end: the puddle. Many parts, one piece, drawn
literally. There is no letterform in it, so nothing reads as the Wayne State W, which is a protected university mark.
It holds at 32 px as a gold seam joining two halves, and it animates naturally: the ripples lay down one by one.

(The first torch-and-plates attempt looked like an eyedropper over an open book. We do not talk about the eyedropper.
The bead is also, strictly, a stack of dimes, minus the money.)

## Colours
| Role | Colour |
|---|---|
| The upper plate (field) | Wayne State green `#0C5449` |
| The lower plate (field) | Deep green `#04231E`, a shade of the same green |
| The puddle | White `#FFFFFF`. Orange `#FF7A1A` is reserved as the one optional hot accent and is not in the mark today |
| The weld bead (the only gold) | Wayne State gold `#FFCC33` |
| Plates on dark backgrounds | White `#FFFFFF` |
| Light surface | Off-white `#F2F7F6` |
| Dark surface, terminal | Near-black `#0F1F1C` |

Gold never sits behind white text. The bead is always gold, except in the one-colour mark.

## Files
| File | Use |
|---|---|
| `weld-mark.svg` | The mark on light backgrounds: green fields, gold bead, white puddle, rounded square |
| `weld-mark-white.svg` | The mark on dark backgrounds: the fields are lifted to a lighter green so the square holds against near-black |
| `weld-mark-mono.svg` | One colour (`currentColor`): the rounded square with the ripples and puddle knocked out. Inline it; as an `<img>` it draws black |
| `weld-mark-animated.svg` | Front-page motion. The ripples lay down one by one along the seam, then the puddle pulses once. About 2.5 seconds, plays once. With `prefers-reduced-motion: reduce` it shows the finished mark |
| `weld-tile.svg` | The app icon. Same drawing as the mark, on a 512 tile with corner radius 112 |
| `weld-favicon.svg` | The tile with a larger puddle, for 32 and 64 px |
| `weld-favicon-16.svg` | A coarser drawing for 16 px: three ripples and a puddle, every shape at least 2 px |
| `weld-avatar-512.png` | The GitLab project avatar and the Open Graph image base |
| `favicon-16.png`, `favicon-32.png`, `favicon-64.png`, `favicon.ico` | Browser favicons. The `.ico` holds all three |
| `weld-board.png` | The 12-panel brand board |

## Geometry
Hand-written paths on a 512 unit square, not traced. Everything is drawn along the seam axis and then rotated 18
degrees, so the seam climbs to the right. The two fields are the halves of that axis. Each ripple is one crescent path:
tips 148 units apart across the seam, outer arc bulging 58 units forward, inner arc 18, so each is 40 thick. Eight of
them step 50 units apart, with a 6 unit round-joined stroke in the same gold to soften the tips. The puddle is a 28 unit
circle, 46 units ahead of the last ripple. Four kinds of shape, no gradients, no masks (except the one-colour version),
no outlines.

## Clear space
Keep a tenth of the mark's width clear on every side. The mark SVGs already carry a 26 unit margin.

## Rules
- Never add a letter to the mark, and never rework the ripples until any part reads as a W. The Wayne State W is not ours.
- Never recolour the bead. Gold is the weld.
- Never add a glow or gradient to the puddle.
- Never stretch, mirror or rotate the mark. The weld always travels up and to the right.

## Motion
The animation is CSS inside the SVG. Each ripple fades and slides in from behind, 0.16 seconds after the last, and the
puddle pulses once at the end. Nothing flashes.

## Rendering
The PNGs were rendered from the SVGs with headless Edge at 1x. The `.ico` was assembled with Pillow from the three
favicon PNGs.

## The board
`weld-board.png` is composed in code from the real SVGs, so every mark on it is the true mark. It shows primary,
stacked, off-white and dark logos, app icon, standalone and one-colour marks, favicons, palette, type, `weld doctor` in
use, and backgrounds.
