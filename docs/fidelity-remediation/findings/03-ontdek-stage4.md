# 03 · ontdek — Stage 4 (Treatment A)

INK `/ontdek/` vs Lovable `:4173/browse`. Both states were captured on both sides. The default is Stories. Writers was reached with `--click '[data-audit-id="tabs-tab"]' --state writers`, which closes the Stage 1 gap ("writers tab unpaired"). Header and footer are out of scope. Spine ids (S*) are from `03-ontdek-spine.md`.

| Finding | Status | Fix (file:line) |
|---|---|---|
| 24px blockGap between every section (`tabs`, `stories` mt 24) | fixed | `templates/page-ontdek.html:3` (main `blockGap` 0) |
| Side gutter 24 vs 16 (390: grid 342 vs 358, tab x 0 vs 16) | fixed | `patterns/ontdek.php:52,62,72,91,101`; `archive-intro.php:14` (px s-16) |
| Intro band: py was on the section (64/48); Lovable `.container py-14 md:py-20` | fixed | `archive-intro.php:14-17` (`ink-band--flush` + `.ink-ontdek-intro__inner` pt 56→80, theme.json) |
| Intro h1 mb 24 / lede mt 18 + mb 18; lede lh 27 vs 29.25 | fixed | `theme.json` `.ink-ontdek-intro h1` mb 0; `.ink-ontdek-intro__tekst` mt 24, mb 0, lh 1.625 (`archive-intro.php:27-28`) |
| S4 search sits in its own band, not inside the intro | fixed (visual) / owner (markup) | Search band continues the cream intro: `ink-band--cream --border-b --flush`, inner pt 32 pb 56→80 (`ontdek.php:52-55`, theme.json `.ink-ontdek-soekband__inner`). The field y now matches at 390 (481). Moving the block into the intro is Q1 |
| S5a "Soek" button; field 40 vs 48px | accepted | stage-2-rulings SRCH-1 |
| Intro bg cream/40 vs secondary/30 | accepted | stage-2-rulings SB-3 |
| Intro eyebrow row 14/20 vs 16/24 (+4px) | owner (lead) | primitives.css change request P-1 |
| S6 discovery surfaces band | owner (SCOPE) | Padding set to the content rhythm, 40→56 (`ontdek.php:62-63`, `.ink-ontdek-vlakband`) |
| Tab rule on the nav (1368px) instead of the full-width band; list wraps | fixed | theme.json `.ink-ontdek-tabbalk` border-b, `.ink-ontdek-tabs` no border, no wrap |
| S7 sticky `top:0` | accepted | disclosure `ontdek.php:19-24` |
| S8 tab counts | owner | Q2 |
| Content panel py 16/64 vs 40→56 | fixed | `ontdek.php:91-92,101-102` `.ink-ontdek-paneel` (theme.json) |
| S10 visible panel headings ("Bydraes" was a second `<h1>`) | fixed | `WorksArchive.php:346` (h2, `screen-reader-text`); `SkrywersTab.php:245` |
| Controls gap 24 / mb 24 vs 16 / 32; sort row 16/24 vs 14/20 | fixed | theme.json `__kontroles`, `__sorteer` |
| S11 GET links vs buttons | accepted | MECHANISM (AD-7) |
| S12 type icons in the filter pills | fixed | `WorksArchive.php:648-671,702` (Lucide Feather / BookOpen / ScrollText; `ink-chip svg` sizes them at 14) |
| S13 "Sort by" label · S15 result count · S24 empty state | owner | need authored Afrikaans copy (Q3) |
| S14 date row · S19 pagination · S22 Gradering label | owner (SCOPE) | standing SCOPE items |
| S16 bookmark · S23 writer stats + Follow | owner | features (Q4) |
| Card meta gap 12 vs 8 | fixed | theme.json `.ink-ontdek-werke__meta-top` |
| Card footer mt 12 / pt 12 vs 20 / 16 | fixed | theme.json `.ink-ontdek-werke__voet` (mt 8 + gap 12, pt 16) |
| Excerpt lh 1.6 vs 1.625; no 3-line clamp | fixed | theme.json `.ink-ontdek-werke__uittreksel` |
| Author name lh 21 vs 20; stats row 16/24 vs 12/16; stat icon 12 vs 14 | fixed | theme.json `__author-naam`, `__tellers`, `__tellers .__teller svg` |
| S17 padding on the `li`, not CardContent; no whole-card link | accepted (box) / owner (link) | The computed box matches (24px). Whole-card link is Q5 |
| S18 card title / writer name are `<a>`, not `<h3>` | owner | Q5 |
| S20 writer sort order and default | owner | product call (`SkrywersTab.php:97-99`) |
| S21 genres sat below the writer-card head | fixed | `SkrywersTab.php:319-339` (genres inside `__kop-teks`; theme.json `__kop-teks` gap 6, flex 1) |
| Writer card bio mt 12 vs 16, lh 1.6 vs 1.625 | fixed | theme.json `.ink-ontdek-skrywers__item` gap 16, `__bio` lh 1.625 |
| Writer name 20/27.5 vs 18/28 | fixed | `SkrywersTab.php:323` → `ink-heading-h4 ink-heading-h4-regular` |
| Writer name weight 600 vs 400 | owner (lead) | primitives.css change request P-2 (`ink-heading-h4-regular` is listed in the class map but not defined) |
| S25 type pill: per-type tint, not outline | accepted | disclosure `WorksArchive.php:507-514` |

**Phase 4 re-run.** Captures verified (`verify --sample 30`) and gated (determinism 3/3) at 1440. Every row below excludes header and footer.
- **390:** Box offsets match: intro text y 361 and search field y 481 on both sides; controls and grid at +40. No colour or type deltas remain beyond the accepted SB-3 tint, S25 pill and P-1 eyebrow.
- **768:** As at 390. The writers state adds only P-2 (name weight).
- **1440:** As at 390. Search y differs by 30px because the English h1/lede wrap to more lines (copy). Card heights differ by copy length. The remaining box deltas are mechanism: padding sits on the section rather than `.container`, and on the `li` rather than CardContent.
