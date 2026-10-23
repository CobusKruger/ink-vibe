# 07 · lees-storie — Stage 4

Lovable `http://localhost:4173/read/sample` · INK `https://nuwe-ink.local/storie/die-wenteltrap-na-die-lig/` · 2026-10-23.
Paths are relative to `wp-content/themes/ink-foundation/`. Header and footer are out of scope.

| Finding | Status | Fix (file:line) |
|---|---|---|
| Live bug: the artikel floating heart is clickable but `line-reactions.js` loads only on gedig/storie | fixed | `functions.php:660-667` (scope + artikel) |
| Header: 12px gaps between badge, h1 and byline (Lovable `mb-4` = 16) (S-1) | fixed | `patterns/reading-storie.php:74` blockGap s-16 |
| Byline: 14px text, 8px gaps (Lovable 16px; gap-4 between items, gap-2 avatar→name) | fixed | `patterns/reading-storie.php:82-92`; `assets/css/reading.css` `.ink-lees-byline` |
| A 24px blockGap between `<main>`'s sections (header→body, body→bar, bar→author band) | fixed | `templates/single-storie.html:3` blockGap 0 |
| Floating bar `margin-top:32` (Lovable 0) | fixed | `theme.json` `.ink-reaksie-bar{margin:0 auto}` |
| Floating bar 768px wide at 1440 and full-bleed at 390 (Lovable pill `max-w-md` inside `px-4`) | fixed | `assets/css/reading.css` `.ink-reading-main > .ink-reaksie-bar` |
| Bar controls 26–27px tall, heart 14px with gap 16 (Lovable 20/24px, 16px, gap 8) | fixed | `reading.css` `.ink-reaksie-bar svg`; `theme.json` `.ink-reaksie-tellers:not(.ink-icon-label)` |
| Responses h2 margin 19.92/19.92 (Lovable 0/32) | fixed | `reading.css` `.ink-reaksies__heading` |
| Compose intro 16/24 ink-text with extra 16px top margin (Lovable 14/20 muted) | fixed | `reading.css` `.ink-reaksies__intro` |
| Compose card gap 12 (Lovable `mb-4`); fieldset 2px margin; textarea 80px (Lovable min-h 100) | fixed | `theme.json` `.ink-reaksies__form` gap s-16; `reading.css` `__types`, `__input` |
| Response list gap 16 (Lovable `space-y-6`) | fixed | `theme.json` `.ink-reaksies__list` gap s-24 |
| Response text: ink-text, line-height 24, 16px margins (Lovable muted, `leading-relaxed`, 0) | fixed | `reading.css` `.ink-reaksies__text` |
| Response meta order: badge before author (S-14) | fixed | `reading.css` `.ink-reaksies__who .ink-reaksies__badge{order:1}` |
| Read time vs date (S-5/S-6) | owner | — |
| Share button missing (S-8); the bar's 3 items spread across the pill | owner | — |
| Upvote inert with no icon (S-15); Reply removed (S-16) | owner | disclosures `ResponsesList.php:38-43, 59-65` |
| Follow only for logged-in non-authors (S-10) | owner (row 06 F22) | — |
| Bar node is the pill, not a wrapper (S-7): bg/radius/shadow deltas compare different boxes | accepted (structure; geometry now matches the pill) | — |
| "Wys alle werk" is serif (Lovable Inter) | accepted (ruling "Button font") | — |
| Submit button shadow-soft (Lovable capture shows none; source `literary` = `shadow-soft`) | accepted (recipe) | — |
| `storie-prose` box is 768 wide (paragraphs are 672 at the same x) | accepted (no visible delta; S-9 mechanism) | — |
| Avatar img radius 0 (its `.ink-avatar` wrapper is round + `overflow:hidden`) | accepted (renders round) | — |
| Margin-vs-gap rows (badge/title/intro/types/input `margin-bottom` vs `gap`) | accepted (same geometry) | — |
| Title lines, paragraph count, badge/chip/submit widths, response badge colour (S-20) | accepted (data/copy) | — |

**Artikel:** the same header, byline and template fixes were applied to `patterns/reading-artikel.php` and `templates/single-artikel.html`, since it shares the storie shape. It has not been captured.

## Phase 4 re-run (all INK captures pass verify; determinism passed @390)

- **1440:** clean apart from the owner items (read time/date, share, upvote/reply) and data or copy widths.
- **768:** same as 1440.
- **390:** same as 1440.
