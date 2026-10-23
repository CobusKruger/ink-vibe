# 22 · lees-gedig — Stage 4

Lovable `http://localhost:4173/read/s2` · INK `https://nuwe-ink.local/gedig/piet-punte/` · 2026-10-23.
Paths are relative to `wp-content/themes/ink-foundation/`. Shared `reading-*` fixes are listed in `07-lees-storie-stage4.md` and apply here unchanged.

| Finding | Status | Fix (file:line) |
|---|---|---|
| Live bug: artikel floating heart had no script (row 22 entry) | fixed | `functions.php:660-667` |
| Header gaps 12 (Lovable 16) | fixed | `patterns/reading-gedig.php:85` blockGap s-16 |
| Byline gap 8 (Lovable gap-4 / gap-2) | fixed | `patterns/reading-gedig.php:93-94`; `reading.css` `.ink-lees-byline` |
| `<main>` blockGap 24 between sections | fixed | `templates/single-gedig.html:3` |
| Floating bar: width, margin, control heights, heart size and gap | fixed | as row 07 (`reading.css`, `theme.json`) |
| Poem lines 20/28 below 768 (Lovable `text-lg leading-relaxed` = 18/29.25) | fixed | `reading.css` `@media (max-width:767.98px) .ink-gedig__line` |
| Responses block (h2, intro, card gaps, textarea, list) | fixed | as row 07 |
| Pre-filled aggregate hearts and highlights at rest (S-7) | owner | `functions.php:651-657` disclosure |
| Dedication field (S-4) | owner | — |
| Poetry-specific response copy (S-14) | owner | — |
| Read time vs date (S-3); share (S-11); Reply (S-16) | owner | — |
| Resonance summary "You marked N lines" (S-8) | owner (it follows from S-7) | — |
| Header bg `cream/50` vs INK `secondary/30` | accepted (SB-3) | — |
| Badge and hint sage tint 10% vs 15% | accepted (NR-3) | — |
| Body column 768 with a 576 poem inside (S-9); stanza structure (S-5/S-6) | accepted (same x/w for the poem) | — |
| Author card with no bio, 96 vs 112 high (S-12); empty response list | accepted (data) | — |

## Phase 4 re-run (all INK captures pass verify; determinism passed @390)

- **1440:** clean apart from the owner items (S-7 hearts, dedication, read time/date, share) and data.
- **768:** same as 1440.
- **390:** same as 1440. Line type now matches.
