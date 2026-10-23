# 13 — Oor INK · Stage 4

INK `/oor-ink/`, Treatment B. **A region** = `[data-audit-id="sponsors"]` ↔ Lovable `SponsorsSection.tsx` on `/`
(`http://localhost:4173`, both sides scoped). **C region** = everything else in `<main>`, checked OFF-RECIPE against
`maps/_recipes.csv`. Re-run 2026-10-22: deploy, capture 390/768/1440; determinism gate (3/3) + `verify --sample 30`
passed on the scoped INK captures (verify also on the full 1440 capture). Fixes in `patterns/borg-erkenning.php` (BP),
`templates/page-oor-ink.html` (TP) and the `theme.json` `styles.css` string (TJ:167).

## A region — sponsors

| Finding | Status | Fix (file:line) |
|---|---|---|
| S-1 band transparent vs `bg-secondary/20` | fixed in Stage 3 (`ink-band--stone-soft`) | — |
| S-2 column capped at 768 vs full container 1368 | fixed | BP:21-22, TJ:167 (`.ink-borg-erkenning`) |
| S-3 flat 16px gap vs `mt-2 mb-4` h2, `mb-10` intro + grid | fixed (homepage-strip recipe ported) | TJ:167 |
| Chip-row gap 24 vs `gap-8 md:gap-12` | fixed | TJ:167 |
| Intro line-height 27.2 vs 24 | fixed | TJ:167 |
| Chip row shrink-wrapped (flex item) vs full-width block | fixed | TJ:167 |
| h2 32 vs 30px; eyebrow tracking; gold chip `goud` vs `gold`; bronze border | fixed in Stage 3 (tier-chip / h2-sub / eyebrow primitives) | — |
| Eyebrow 20px block vs inline span (17px) | primitive request (P-1, row 00) | — |
| Chip box Lora 500 vs Inter 400 (the name inside is serif on both); name box 28 vs 23 (link is inline-flex) | accepted: MECHANISM (S-6 link), no visible delta | — |
| CTA serif vs Inter | accepted: "Button font" ruling | — |
| S-4, S-5, S-8 (extra wrapper, `<ul>`, `<a>`) | accepted: MECHANISM | — |
| S-6 chips are links, S-7 live data | owner (O-1) | — |
| Chip order follows data (bronze first) vs gold → silver → bronze | owner (T-5, row 00 — same renderer data) | — |
| S-9 recipe defined twice (`.ink-borg-erkenning*` TJ, `.ink-borg-strook*` home.css) | open — now identical values; consolidation request (PR-O1) | — |

## C region — OFF-RECIPE (Stage 4 half of Treatment C)

| Finding | Status | Fix (file:line) |
|---|---|---|
| 24px root blockGap between sections → white strips between the tinted bands | fixed (flush, as every Lovable page) | TP:3 |
| Hero lede `text-lg` line-height 27 vs 28 | fixed | TJ:167 (`.ink-oor-ink-hero`) |
| S-C2 headings at weight 700 | fixed in Stage 3 — h1-entity 48/48 (30/37.5 @390), h2-block 24/32, all 600: on recipe | — |
| S-C3 "Kontak ons" core button without a style | fixed in Stage 3 — `button-primary` + `lg` 44px, radius 6: on recipe | — |
| S-C4 page-scoped `!important` size pins | fixed in Stage 3 (rules gone) | — |
| S-C1 two eyebrow recipes on one page (tracked hero, accent sponsors) | accepted: EB-1 | — |
| S-C5 `alignwide` sections with a 768 reading column | accepted: no reference; matches `contentSize` and the hero/text recipe | — |
| S-C6 "Maandelikse uitdagings" → `/uitdagings` (301 to a post) | fixed (concurrent challenge agent, `oor-ink.php:107`) | — |
| Bands: default 64px, `tint-cream` secondary/30, `stone-soft` /20; eyebrow 12/16/0.2em | on recipe | — |

## Phase 4 re-run (remaining deltas, A region)

- **390:** copy/data-length only (intro wraps one more line; live sponsor names wrap the chip row differently: 384 vs 466 tall), eyebrow 3px (P-1), chip order (T-5).
- **768:** as 390 (chip row 152 vs 248).
- **1440:** as 390 (chip row 54 vs 150 — one row vs two). Width and rhythm clean.
