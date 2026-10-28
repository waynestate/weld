#!/usr/bin/env node
/* ============================================================================
   WELD README art kit.

   Renders the README images from this repo's own static front page (site/)
   and brand files, and writes them under docs/readme/. Nothing is drawn by
   hand: the hero and the gallery are screenshots of the real page, the
   journey diagram is built from the page's own journey section, the cast is
   the marks resized (WayneUI composed on its green tile), and the badges
   read their facts from composer.json.

   Run from any folder (every path resolves from this file's own location):

     npm --prefix scripts/readme-art ci
     npx --prefix scripts/readme-art playwright install chromium
     node scripts/readme-art/build.mjs

   The page is opened as a file:// URL: no server, no network.
   ========================================================================== */

import { chromium } from "playwright";
import { makeBadge } from "badge-maker";
import sharp from "sharp";
import { existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

/* ------------------------------------------------------------- paths, limits */

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(scriptDir, "..", "..");
const siteDir = path.join(repoRoot, "site");
const outDir = path.join(repoRoot, "docs", "readme");
const castDir = path.join(outDir, "cast");
const badgeDir = path.join(outDir, "badges");

const MAX_BYTES = 300 * 1024; // every file in docs/readme/ stays at or under this
const FORBIDDEN = /noug[a]t|snu[.]gs|citadel/i; // internal names never ship in art

const outputs = [];

function record(rel, buf) {
  const abs = path.join(outDir, rel);
  mkdirSync(path.dirname(abs), { recursive: true });
  writeFileSync(abs, buf);
  outputs.push({ rel, bytes: buf.length });
  console.log(`  ${rel}  ${buf.length} bytes`);
}

function fail(msg) {
  console.error(`FAIL: ${msg}`);
  process.exit(1);
}

/* ------------------------------------------------------------- 1. cast strip */

// One 96x96 PNG per stack part (shown at 48 px in the README). The marks are
// copied and resized only, never redrawn. WayneUI is composed instead: its
// reverse mark (white frame, gold tab) set on a Wayne State green tile, so it
// reads as a sibling of the other thirteen.
const CAST = [
  "creed",
  "proctor",
  "sesame",
  "helmet",
  "sourdough",
  "squire",
  "preflight",
  "pinstripe",
  "booster",
  "rollcall",
  "drawbridge",
  "advisor",
  "weld",
];

function castSource(name) {
  if (name === "weld") return path.join(siteDir, "brand", "weld-avatar-256.png");
  return path.join(siteDir, "assets", "marks", `${name}.png`);
}

// The WayneUI tile: the reverse mark, traced from WayneUI's approved artwork,
// set on a green rounded tile. The traced paths are placed unchanged: no edits
// to any d= value, fill, or the mark's own viewBox. The visible ink spans
// 1546 x 1551 of the 1980 x 1989 viewBox and is centred in it, so this scale
// makes the mark 352 px tall on the 512 tile (about 69%), matching the square
// marks WELD and SESAME.
function wayneuiTileSvg() {
  const raw = readFileSync(path.join(scriptDir, "src", "wayneui-mark-reverse.svg"), "utf8");
  const open = raw.indexOf("<svg");
  const openEnd = raw.indexOf(">", open) + 1;
  const close = raw.lastIndexOf("</svg>");
  if (open < 0 || openEnd < 2 || close < openEnd) fail("wayneui-mark-reverse.svg: no <svg> element");
  // Everything between the opening tag and the final </svg>, verbatim (the
  // file's own line endings included, so the paths stay byte-identical).
  const inner = raw.slice(openEnd, close).replace(/^\r?\n/, "");
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="512" height="512" role="img" aria-label="WayneUI">
  <rect width="512" height="512" rx="80" ry="80" fill="#0C5449"/>
  <svg x="31.3" y="30.3" width="449.4" height="451.4" viewBox="0 0 1980 1989">
${inner}</svg>
</svg>
`;
}

async function buildCast() {
  console.log("cast:");
  // WayneUI first: one source (the traced mark) for both the SVG and the PNG.
  const tile = Buffer.from(wayneuiTileSvg(), "utf8");
  record("cast/wayneui.svg", tile);
  const tilePng = await sharp(tile, { density: 300 })
    .resize(96, 96, { fit: "contain", background: { r: 0, g: 0, b: 0, alpha: 0 } })
    .png({ palette: true, quality: 85, compressionLevel: 9 })
    .toBuffer();
  record("cast/wayneui.png", tilePng);
  // The old dark-variant tile is gone: the green tile works on every surface.
  const stale = path.join(castDir, "wayneui-dark.png");
  if (existsSync(stale)) {
    rmSync(stale);
    console.log("  removed cast/wayneui-dark.png (no longer generated)");
  }
  for (const name of CAST) {
    const src = castSource(name);
    if (!existsSync(src)) {
      console.log(`  skip cast/${name}.png: missing source ${path.relative(repoRoot, src)}`);
      continue;
    }
    const buf = readFileSync(src);
    const png = await sharp(buf)
      .resize(96, 96, { fit: "contain", background: { r: 0, g: 0, b: 0, alpha: 0 } })
      .png({ palette: true, quality: 85, compressionLevel: 9 })
      .toBuffer();
    record(`cast/${name}.png`, png);
  }
}

/* ---------------------------------------------------------------- 2. badges */

// Facts are read from composer.json, never typed: a badge that lies is worse
// than no badge at all.
function buildBadges() {
  console.log("badges:");
  const composer = JSON.parse(readFileSync(path.join(repoRoot, "composer.json"), "utf8"));
  const defs = [
    { key: "php", label: "php", message: composer.require.php },
    { key: "license", label: "licence", message: composer.license },
    { key: "laravel", label: "laravel", message: composer.require["laravel/framework"] },
  ];
  for (const d of defs) {
    if (!d.message) fail(`composer.json has no fact to badge for "${d.key}"`);
    const svg = makeBadge({
      label: d.label,
      message: d.message,
      labelColor: "#0C5449",
      color: "#FFCC33",
      style: "flat-square",
    });
    record(`badges/${d.key}.svg`, svg);
  }
}

/* ------------------------------------------------------------- 3. the page */

async function openPage(browser) {
  const context = await browser.newContext({
    viewport: { width: 1280, height: 900 },
    deviceScaleFactor: 2, // crisp images
    reducedMotion: "reduce", // the finished weld: everything visible, nothing moving
  });
  const page = await context.newPage();
  await page.goto(pathToFileURL(path.join(siteDir, "index.html")).href, {
    waitUntil: "networkidle",
  });
  await page.evaluate(() => document.fonts.ready);
  return { context, page };
}

// The hard rule: no internal hostnames in anything we capture.
async function assertClean(page, selector, what) {
  const text = await page.locator(selector).innerText();
  if (FORBIDDEN.test(text)) fail(`forbidden text matched ${FORBIDDEN} in ${what}`);
}

/* ------------------------------------------------------------------ 4. hero */

async function buildHero(page) {
  console.log("hero:");
  // Hide the chrome that must not appear in the README art.
  await page.addStyleTag({
    content: ".seam-progress, .site-header { display: none !important; }",
  });
  await assertClean(page, ".hero", "hero");
  const png = await page.locator(".hero").screenshot();
  const jpg = await sharp(png).resize({ width: 1280 }).jpeg({ quality: 82, mozjpeg: true }).toBuffer();
  record("hero.jpg", jpg);
}

/* -------------------------------------------------------------- 5. journey */

function esc(s) {
  return s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

// The journey as a self-contained animated SVG: the six stops read from the
// page's own DOM, the marks embedded as base64 data URIs, one gold bead
// travelling the seam. No script, no external refs; reduced motion gets the
// still diagram.
function buildJourneySvg(stops) {
  const W = 1280;
  const H = 240;
  const margin = 110;
  const step = (W - 2 * margin) / (stops.length - 1);
  const mark = 72;
  // Centred vertically: the margin above the marks (markTop) equals the margin
  // below the captions (H - captionY - descender), with a 4px descender at 14px.
  const markTop = 55;
  const centerY = markTop + mark / 2;
  const nameY = markTop + mark + 30;
  const captionY = nameY + 24;
  const seamX1 = margin;
  const seamX2 = W - margin;
  const beadLen = 48;
  const travel = seamX2 - seamX1 - beadLen; // bead's right end lands on the last stop
  const font = "Lato, 'Segoe UI', Helvetica, Arial, sans-serif";

  const images = stops
    .map((s, i) => {
      const cx = margin + i * step;
      const castName = s.name.toLowerCase();
      const castFile = path.join(castDir, `${castName}.png`);
      if (!existsSync(castFile)) fail(`journey needs cast/${castName}.png (build the cast first)`);
      const b64 = readFileSync(castFile).toString("base64");
      return `  <image x="${cx - mark / 2}" y="${markTop}" width="${mark}" height="${mark}" href="data:image/png;base64,${b64}"/>`;
    })
    .join("\n");

  const texts = stops
    .map((s, i) => {
      const cx = margin + i * step;
      return (
        `  <text x="${cx}" y="${nameY}" text-anchor="middle" font-family="${font}" font-size="18" font-weight="bold" fill="#FFF4CC">${esc(s.name)}</text>\n` +
        `  <text x="${cx}" y="${captionY}" text-anchor="middle" font-family="${font}" font-size="14" fill="rgba(255,244,204,.75)">${esc(s.caption)}</text>`
      );
    })
    .join("\n");

  const desc = `WELD's journey: one request, six stops: ${stops
    .map((s) => `${s.name} ${s.caption}`)
    .join(", ")}.`;

  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" role="img" aria-labelledby="journey-title journey-desc">
  <title id="journey-title">WELD's journey: one request, six stops</title>
  <desc id="journey-desc">${esc(desc)}</desc>
  <style>
    @keyframes bead-run {
      0% { transform: translateX(0); }
      75% { transform: translateX(${travel}px); }
      100% { transform: translateX(${travel}px); }
    }
    .bead { animation: bead-run 4.8s ease-in-out infinite; }
    @media (prefers-reduced-motion: reduce) { .bead { animation: none; opacity: 0; } }
  </style>
  <defs>
    <filter id="bead-glow" x="-250%" y="-600%" width="600%" height="1300%">
      <feGaussianBlur in="SourceGraphic" stdDeviation="5" result="blur"/>
      <feFlood flood-color="#FFCC33" flood-opacity="0.85" result="gold"/>
      <feComposite in="gold" in2="blur" operator="in" result="glow"/>
      <feMerge>
        <feMergeNode in="glow"/>
        <feMergeNode in="SourceGraphic"/>
      </feMerge>
    </filter>
  </defs>
  <rect x="0" y="0" width="${W}" height="${H}" rx="16" fill="#0C5449"/>
  <rect x="${seamX1}" y="${centerY - 2}" width="${seamX2 - seamX1}" height="4" fill="#FFCC33"/>
  <line class="bead" x1="${seamX1}" y1="${centerY}" x2="${seamX1 + beadLen}" y2="${centerY}" stroke="#FFF4CC" stroke-width="6" stroke-linecap="round" filter="url(#bead-glow)"/>
${images}
${texts}
</svg>
`;
}

async function buildJourney(page) {
  console.log("journey:");
  await assertClean(page, ".section--journey", "journey");
  const stops = await page.$$eval(".section--journey .journey__stop", (els) =>
    els.map((el) => ({
      name: el.querySelector(".journey__name").textContent.trim(),
      caption: el.querySelector(".journey__role").textContent.trim(),
    })),
  );
  if (stops.length !== 6) fail(`expected six journey stops, found ${stops.length}`);
  record("journey.svg", Buffer.from(buildJourneySvg(stops), "utf8"));
}

/* -------------------------------------------------------------- 6. gallery */

// A contact sheet of three existing mobile crops, composed as a tiny HTML
// page and screenshotted: the same page, three windows.
async function buildGallery(page) {
  console.log("gallery:");
  const crops = ["mobile-02-hero.png", "mobile-04-cast-1.png", "mobile-09-paths.png"];
  for (const c of crops) {
    if (!existsSync(path.join(siteDir, "screenshots", "sections", c))) fail(`missing crop ${c}`);
  }
  const html = `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
  html, body { margin: 0; padding: 0; }
  /* The sheet is exactly as wide as its content: 3×360 + 2×32 + 2×40 = 1224. */
  .sheet { width: fit-content; background: #0C5449; padding: 40px; display: flex; gap: 32px; }
  img {
    box-sizing: border-box; /* the 1px border sits inside the 360x640 */
    display: block;
    width: 360px;
    height: 640px;
    object-fit: cover;
    object-position: top;
    border-radius: 14px;
    border: 1px solid rgba(255, 204, 51, .35);
  }
</style>
</head>
<body>
<div class="sheet">
${crops.map((c) => `  <img src="${pathToFileURL(path.join(siteDir, "screenshots", "sections", c)).href}" alt="">`).join("\n")}
</div>
</body>
</html>`;
  await page.setContent(html, { waitUntil: "networkidle" });
  await page.evaluate(() => document.fonts.ready);
  await assertClean(page, ".sheet", "gallery");
  const png = await page.locator(".sheet").screenshot();
  const jpg = await sharp(png).resize({ width: 1280 }).jpeg({ quality: 82, mozjpeg: true }).toBuffer();
  record("gallery.jpg", jpg);
}

/* -------------------------------------------------------------------- main */

async function main() {
  await buildCast();
  buildBadges();

  const browser = await chromium.launch();
  try {
    const { context, page } = await openPage(browser);
    await buildHero(page);
    await buildJourney(page);
    await buildGallery(page);
    await context.close();
  } finally {
    await browser.close();
  }

  // The size gate: print what we have, fail if anything grew past the limit.
  let over = false;
  for (const { rel, bytes } of outputs) {
    if (bytes > MAX_BYTES) {
      console.error(`FAIL: ${rel} is ${bytes} bytes (limit ${MAX_BYTES})`);
      over = true;
    }
  }
  if (over) process.exit(1);
  console.log(`done: ${outputs.length} files, all at or under ${MAX_BYTES} bytes`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
