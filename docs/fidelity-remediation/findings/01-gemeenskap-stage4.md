# 01 — Gemeenskap · Stage 4

INK `/gemeenskap/` ↔ Lovable `/community` (`http://localhost:4173`). Re-run 2026-10-22: deploy, capture
390/768/1440 on both sides; determinism gate (3/3) + `verify --sample 30` passed on all three INK captures.
Fixes in `patterns/gemeenskap.php` (GP), `templates/page-gemeenskap.html` (TP) and the `theme.json`
`styles.css` string (TJ:167, `.ink-gemeenskap-*` rules).

| Finding | Status | Fix (file:line) |
|---|---|---|
| 24px root blockGap between every section in `<main>` (Lovable sections are flush) | fixed | TP:3 |
| Value grid 1368 wide vs `max-w-6xl` 1152 | fixed | GP:82-85 |
| How-it-works + principles content capped at 768 (constrained children) vs `max-w-5xl` 1024 | fixed | GP:140-145, 190-195 |
| S-10 `core/columns` stack below 782px; Lovable is 2-col from 768 (value, how-it-works) | fixed (CSS grid, 1 → 2 cols at 768) | TJ:167 |
| How-it-works grid row gap 32 vs `gap-10` 40 | fixed | GP:156 |
| S-14 principles: two 2-col rows vs one 4-cell grid | fixed (one grid; rows/columns `display:contents`) | GP:206-207, TJ:167 |
| Principle title → text 24 vs `mb-2` 8 | fixed | TJ:167 |
| S-15 CTA: whole column capped at 576 (h2 wraps at 1440) vs only the paragraph `max-w-xl` | fixed | GP:288-290, TJ:167 |
| CTA h2 → text 24 vs 16; text → buttons 24 vs 40 | fixed | GP:291-296 |
| CTA text colour surface-alt/70 vs background (#FAF8F5)/70; line-height 27 vs 28 | fixed | TJ:167 |
| Hero intro line-height 27/30 vs 29.25 (<768) / 28 (≥768) | fixed | GP:62-63, TJ:167 |
| Hero + CTA buttons @390 not full width (Lovable `flex-col` <640) | fixed | TJ:167 |
| CTA secondary button 2px white border + white text vs 1px background/30 + cream text (carried) | primitive request (PR-G1) | — |
| Hero / principles eyebrow: block `<p>` vs inline span in a 24px line → heading block 6px shorter (S-6) | primitive request (P-1, row 00) | — |
| Hero + how-it-works band cream/40 vs secondary/30 | accepted: SB-3 | — |
| CTA h2 36 vs 48 @768 | accepted: HR-4 (30 / 36 / 48, cream on dark) | — |
| Secondary buttons serif vs Inter | accepted: "Button font" ruling | — |
| S-2 stats band, S-3 spotlight missing | owner (G-1) — disclosed deferral GP:10-26 | — |
| S-4 footer, S-5 `<main>`, S-8 button destinations | accepted: SCOPE (spine) | — |
| S-1, S-7, S-11, S-12, S-13 (wrapper/tag/mechanism; section x/w 0/1440 vs container 20/1400; icon-tile colour on the wrapper) | accepted: MECHANISM, no visible delta | — |
| S-16 benefit-list icon carries no id | accepted: coverage gap, unchanged | — |

## Phase 4 re-run (remaining deltas)

- **390:** copy-length only (Afrikaans wraps: CTA h2 2 lines, how-it-works intro, card lists, principle text), the 6px eyebrow (P-1), PR-G1, S-2/S-3 absent.
- **768:** as 390, plus H-1 (header 89 vs 65 tall, row 00) and the HR-4 CTA h2 size.
- **1440:** the 6px eyebrow (P-1), how-it-works list 46px shorter (copy-length), PR-G1, S-2/S-3 absent. Layout widths and section rhythm clean.
