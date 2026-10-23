# 08a · biblioteek — Stage 4

Lovable `/library` (`src/pages/Library.tsx`) · INK `/biblioteek/` + cards via `/qa-bloks/` (scope `div.ink-biblioteek`).

| Finding | Status | Fix (file:line) |
|---|---|---|
| B-1 shell nests everything in one constrained `section` › `alignwide` (no full-bleed bands) | fixed | `patterns/biblioteek.php:22-25` (shell: no padding, default layout, blockGap 0); `Library/Archive.php:484-506` (root `div.alignfull`, bands emitted by the block) |
| B-2 intro not a full-bleed tinted band | fixed | `Archive.php:490-495` (`ink-band--intro` + `ink-container` + `max-w-3xl` inner); `theme.json` `.ink-biblioteek__intro-houer` py 64→80 |
| Intro band tint cream/40 vs INK secondary/30 | accepted (SB-3) | — |
| B-9 shelf header wrapper absent | fixed | `Archive.php:545-548` (`library-shelf-head`, mb 32) |
| B-10 grids `auto-fit minmax` (4th 0px track at 1440; 342 vs 358 at 390) | fixed | `theme.json` `.ink-biblioteek__uitgelig-lys` 1→md 3; `.ink-biblioteek__list` 1→md 2→lg 3 |
| Shelf/browse sections had no band padding (Lovable `container py-12 md:py-16`) | fixed | `Archive.php:500-502, 545` (`ink-container ink-band ink-band--step`) |
| Card inner rhythm (uniform gap 8; meta packed left) | fixed | `theme.json` meta mb 16 + space-between, excerpt mt 12, footer mt 20 / pt 16, no gaps |
| Card line-heights (excerpt 23.8, author 18, read 21) | fixed | `theme.json` excerpt 1.625, author 16px, read 20px |
| Card hover: shadow lg, title colour only on title hover | fixed | `theme.json` `.ink-biblioteek__item:hover` shadow md (= shadow-soft), title → primary on card hover |
| Filters mb 32 vs 40 | fixed | `theme.json` `.ink-biblioteek__filter` |
| Search icon left 12 vs 16 | fixed | `theme.json` `.ink-biblioteek__soek-ikoon` |
| h1 `max-width:20ch` | fixed | removed; inner is 48rem |
| Empty state: title mb 16, clear button flush | fixed | `theme.json` `.ink-biblioteek__leeg-teks` m 0 / lh 28; `.ink-biblioteek__leeg-skoon` mt 24 |
| Shelf card surface / pill padding (Stage 1 card drifts) | fixed (Stage 3) | `primitives.css` `ink-card--shelf`, `ink-pill--outline` |
| Shelf h2 600 vs 400 | accepted (HR-1) | — |
| B-5 "Soek" button; input 40 vs 48 | accepted (SRCH-1) | — |
| B-6/B-7 search form/icon mechanism | accepted (mechanism; same rendered position) | — |
| Card `display:flex` + `overflow:hidden` (holds the cover image) | owner (B-18) | — |
| Excerpt 3-line clamp (`flow-root`) | accepted (Stage 1 spine §7: not a drift) | — |
| B-3 eyebrow, B-4 lede, B-8 shelf subtitle, B-14 result count, B-17 "deur", B-20 empty help text, B-22 closing CTA | owner (copy / missing elements) | — |
| B-11 category icons on chips | owner (feature: term→icon mapping) | — |
| B-12/B-13/B-15/B-16/B-19/B-21 | accepted (mechanism / SCOPE per spine) | — |
| Chip `white-space:nowrap`, `text-align`; pill gap 4 | accepted (primitive) | — |

**Phase 4 re-run** (`tmp/stage4/lib/cap`, verify --sample 30 passed):
- 1440: clean except accepted (SB-3 tint, SRCH-1 search, chip primitive, HR-1 shelf h2, B-18 card flex).
- 768: same as 1440; plus `ink-container` max-width 1400 vs Lovable `none` below 1400 (same rendered width).
- 390: same as 768.
