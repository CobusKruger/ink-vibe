# 05 · uitdaging-enkel — Stage 4

Lovable `/challenges/silent-protagonist` (`src/pages/Challenge.tsx`, HEAD) · INK `/uitdaging/skryf-n-storie-waarin-die-hoofkarakter-nooit-praat-nie/`.
Scratch captures: `tmp/stage4/uitdaging/cap2/`. Paths: `P` = `patterns/reading-uitdaging.php`, `T` = `theme.json` `styles.css`.

## Layout drift (Phase 4)

| Finding | Status | Fix (file:line) |
|---|---|---|
| Hero column centred at x=336 instead of left-aligned in the 1400 container (x=36) | fixed | P:70 inner group `alignwide` and constrained 768 `justifyContent:left` |
| Hero rhythm: back→badge 12 (Lovable 24), badge→title 12 (16), title→meta 12 (16), meta→actions 12 (32), tagline mb 24 (32) | fixed | P:70 `blockGap:0`; T `.ink-uitdaging__hero .ink-uitdaging__terug/__badge/.wp-block-post-title/__tagline/__status` margins |
| Meta row 16/24 ink and gap 12 (Lovable 14/20 muted, gap 12×24) | fixed | T `.ink-uitdaging__status` |
| Hero texture: 4-plus tile at opacity .03 (Lovable 2-plus tile at .04, `Challenge.tsx:131-133`) | fixed | T `.ink-uitdaging__hero::before` (token `custom.ink.pattern.plus` left for home) |
| Prompt column: 768 centred, with a 672 body (Lovable: 2/3 of a 1368 grid at lg = 899, full width below) | fixed | P:108 `alignwide` and flow; T `@media(min-width:1024px) .ink-uitdaging__opdrag>.wp-block-post-content` |
| Entries container 768 (contentSize 1120 never applied) vs 1400 container | fixed | P:118-119 `alignwide` and flow |
| Entries grid `auto-fit minmax(260px)`: one entry spans the whole row (Lovable `md:grid-cols-3`, card 440) | fixed | T `.ink-uitdaging__inskrywings` 1 col, then 3 cols at ≥768 |
| Entries block mt 32 (none in Lovable); h2 mb 24 (Lovable head mb-10 = 40) | fixed | T `.ink-uitdaging__inskrywings-titel` (blok margin removed) |
| CTA content 576 → h2 wraps to 2 lines (Lovable `max-w-2xl px-4` = 640) | fixed | P:128 contentSize 640 |
| CTA icon inline-flex line box: icon→h2 is 22+16 (Lovable 16) | fixed | T `.ink-uitdaging-cta__ikoon` display:flex |
| CTA text→actions 16 (Lovable mb-8 = 32); button gap 16 (Lovable gap-3 = 12) | fixed | T `.ink-uitdaging-cta__subtitel+.wp-block-buttons`; P:144 blockGap s-12 |
| 24px gaps between the four bands (main blockGap; Lovable bands abut) | fixed | `templates/single-uitdaging.html:3` `blockGap:0` |
| Back link: audit-id sits on the `<p>`, so p=ink vs a=muted | accepted (MECHANISM; the visible `<a>` is muted) | — |
| Primary button shadow | accepted (Lovable's value ends in the same `shadow.md`; the diff truncates it) | — |
| CTA gradient `color(srgb …)` vs `rgba` | accepted (same values) | — |

## Structural findings (Stage 1 spine)

| Finding | Status | Fix (file:line) |
|---|---|---|
| F1 texture mechanism (DOM div vs `::before`) | accepted (MECHANISM; values now match) | — |
| F2 hero wrappers / F3 back link `<p><a>` / F4 badge `<p>` / F8 deadline label / F12 `<p>` vs pre-line / F14 entries nesting / F17 `ul/li` / F18 / F20 `<span>` title / F21 / F23 icon `<span>` | accepted (STRUCTURE/MECHANISM; geometry matches at all 3 viewports) | — |
| F5 tagline (excerpt-only) | accepted (DIVERGENCE, `reading-uitdaging.php:50-55`) | — |
| F6 Editor's pick, F7 Oop/Gesluit pill, F13 Resources section | owner (SCOPE, disclosed `reading-uitdaging.php:20-25,44-48`) | — |
| F9 prompt/rules/prize as one blob; F11 literary devices | accepted (Story 12.1 decision, `reading-uitdaging.php:14-20`) | — |
| F10 "The Prompt" h2 | owner | — |
| F15 "Already Submitted" eyebrow; F16 "View all entries" link; F19 read time; F22 likes/comments stats | owner (MISSING; need copy and/or data) | — |

## Phase 4 re-run (deploy → capture → `verify --sample 30` PASSED)

- 1440: clean on layout. Every paired element sits at the Lovable x and width, with the same in-section offsets. The remaining y/height deltas come only from absent content (tagline F5, prompt h2 F10, devices/rules F9/F11, resources F13, entries eyebrow F15) and longer copy.
- 768: clean on layout (same caveats). The entry footer is 45 vs 57 because Lovable's author name wraps beside the stats (F22).
- 390: clean on layout (same caveats). The title is 3 lines vs 2 (copy).
