# WELD brand files

**The mark is a weld seam that spells W.** Two green plates meet along a zigzag seam. The seam is the letter: down, up,
down, up. A gold weld bead fills it. Many parts, one piece.

The two plates are the Megazord pieces. The bead is the thing that makes them one. Unlike the Megazord, nobody has to
shout to make it happen.

## Colours
| Role | Colour |
|---|---|
| Plates on a light background, the tile | Wayne State green `#0C5449` |
| The weld bead (the only gold) | Wayne State gold `#FFCC33` |
| Plates on dark backgrounds | White `#FFFFFF` |
| Light surface, the gap on the tile | Off-white `#F2F7F6` |
| Dark surface, terminal | Near-black `#0F1F1C` |

Gold never sits behind white text. The bead is always gold, except in the one-colour mark.

## Files
| File | Use |
|---|---|
| `weld-mark.svg` | The mark on light backgrounds: green plates, gold bead |
| `weld-mark-white.svg` | The mark on dark or green backgrounds: white plates, gold bead |
| `weld-mark-mono.svg` | One colour (`currentColor`), for print and single-ink use |
| `weld-mark-animated.svg` | Front-page motion. The plates slide together and lock. The bead runs along the seam, then a small spark flashes at its end. One flash, about 2.2 seconds, plays once. Plates follow light and dark. With `prefers-reduced-motion: reduce` the animation is off and the page shows the finished mark |
| `weld-tile.svg` | The app icon: the mark filling a rounded tile, with an off-white seam |
| `weld-favicon.svg` | The tile with a thicker bead, for 32 and 64 px |
| `weld-favicon-16.svg` | Hand-hinted on the 16 px grid. One unit is one pixel. A green block and a 2.4 px gold bead, no white seam |
| `weld-avatar-512.png` | The GitLab project avatar and the Open Graph image base |
| `favicon-16.png`, `favicon-32.png`, `favicon-64.png`, `favicon.ico` | Browser favicons. The `.ico` holds all three |
| `weld-board.png` | The 12-panel brand board |

## Geometry
Drawn from exact geometry, not traced. The block is 300 by 300 units with corner radius 30. The seam runs through five
points at x = 0, 75, 150, 225 and 300, with y = 60, 212, 60, 212 and 60. Both peaks sit at the same height, so the W is
even. The bead is 38 units tall, measured straight up and down. Each plate stands 10 units back from the bead, so the
seam reads as three clean lines. Two paths make the plates and one makes the bead. No masks, no traced bitmaps.

The tile uses the same drawing scaled to 512 with a corner radius of 112, and a 44 unit bead. The 32 and 64 px version
has a 54 unit bead, because a thin bead mushes at small sizes.

## Clear space
Keep 30 units of the block clear on every side, which is one tenth of the block. Every SVG here already carries that
margin in its viewBox. Do not put text, edges or other marks inside it.

## Rules
- Never recolour the bead. Gold is the weld.
- Never add a welder's mask, a torch, sparks or a glow. The spark in the animation is the only one, and it lives for half a second.
- Never close the seam into a plain W of solid bars. The plates and the bead are the idea.
- Never stretch or rotate the mark.

## Motion
The animation is CSS inside the SVG. The top and bottom plates slide in from above and below, the bead is revealed from
left to right by a clip, and a four-point spark flashes once at the end of the seam. Nothing flashes more than once.

## Rendering
The PNGs were rendered from the SVGs with headless Edge at 1x. The `.ico` was assembled with Pillow from the three
favicon PNGs.

## The board
`weld-board.png` is composed in code from the real SVG geometry, so every mark on it is the true mark. It follows the
Pinstripe board: primary and stacked logos, wordmarks, icon system, app icon, standalone mark, favicons, backgrounds,
palette, type, `weld doctor` in use, and the motion frames.
