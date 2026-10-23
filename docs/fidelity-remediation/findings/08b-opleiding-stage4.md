# 08b · opleiding — Stage 4

Lovable `/library` (`src/pages/Library.tsx`) · INK `/opleiding/` + cards via `/qa-bloks/` (scope `div.ink-opleiding`).

| Finding | Status | Fix (file:line) |
|---|---|---|
| O-1 hub + CTA nested in one constrained shell (no full-bleed bands) | fixed | `patterns/opleiding.php:22-27`; `Training/Hub.php:486-520` (root `div.alignfull`, bands emitted by the block) |
| O-2 intro not a full-bleed tinted band | fixed | `Hub.php:486-495` (`ink-band--intro` + `ink-container` + 48rem inner); `theme.json` `.ink-opleiding__intro-houer` py 64→80 |
| Intro rhythm (uniform gap 16) | fixed | `theme.json` eyebrow mb 16, lede mt 24, search mt 32; h1 `20ch` / lede `44rem` caps removed |
| O-17 CTA an `<aside>` in the content column, top rule, no tint | fixed | `Training/ContributionCta.php:103-113` (`alignfull ink-band--cream ink-band--border-t` + container py 64, centred); text max 36rem |
| Intro/CTA tint cream/40 vs secondary/30 | accepted (SB-3) | — |
| O-6 shelf header wrapper absent; sub mb 24 vs head mb 32 | fixed | `Hub.php:596-601` (`library-shelf-head`); `theme.json` `.ink-opleiding__rak-kop` |
| O-10 grids `auto-fit minmax` | fixed | `theme.json` shelf 1→md 3; list 1→md 2→lg 3 |
| Shelf/browse sections had no band padding | fixed | `Hub.php:497, 596` (`ink-container ink-band ink-band--step`) |
| O-14 no card body wrapper; card inner rhythm (gap 8, meta packed left) | fixed | `Hub.php:749-760` (`__item-inhoud`, `library-card-body`); `theme.json` meta mb 16 + space-between, excerpt mt 12, footer mt 20 |
| Card line-heights; count line-height 21 | fixed | `theme.json` excerpt 1.625, author 16, read 20, count 20 |
| Card hover shadow lg / title colour | fixed | `theme.json` shadow md, title → primary on card hover |
| Filters mb 32 vs 40; search icon left 12 vs 16 | fixed | `theme.json` |
| Empty state spacing | fixed | `theme.json` `.ink-opleiding__leeg*` |
| Shelf h2 / CTA h2 600 vs 400 | accepted (HR-1) | — |
| CTA button 48 / 18px serif / px 40 | accepted (BTN-1) | — |
| CTA button radius 8 vs 6 | accepted (`ink-btn--cta` recipe; see lead report) | — |
| O-4 "Soek" button; input 40 vs 48 | accepted (SRCH-1) | — |
| O-3/O-5 eyebrow `<p>`, search form mechanism | accepted (mechanism) | — |
| Eyebrow row 14/20 inline-flex vs Lovable flex 16/24 (4px row height) | owner→lead (primitive `ink-eyebrow--kicker`) | — |
| O-7 category icons | owner (feature) | — |
| O-13 "deur" prefix; O-16 empty-state shapes | owner (copy) | — |
| O-8/O-9/O-11/O-12/O-15/O-18 | accepted (mechanism / SCOPE per spine) | — |
| Excerpt clamp | accepted (Stage 1 spine §7) | — |

**Phase 4 re-run** (verify --sample 30 passed):
- 1440: clean except accepted (SB-3, SRCH-1, HR-1, BTN-1, chip/pill primitives, kicker eyebrow 4px).
- 768: same; plus container max-width 1400 vs `none` (same rendered width).
- 390: same; CTA h2 height differs by copy length only.
