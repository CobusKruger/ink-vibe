# 18 · ledegids — Stage 4 (Treatment C, OFF-RECIPE)

INK `/ledegids/` was checked against the recipes for its components. The intro uses the index-intro recipe (`section-band,intro`, `heading,h1-index`). The directory reuses the `ink/ontdek-skrywers` block, so its recipe is Lovable's `/browse` writers state. Method: the ledegids captures were remapped onto their recipe-source ids (`ledegids-intro→intro`, `-h1→intro-h1`, `-lede→intro-text`, `-directory→stories`) and diffed with `run.sh diff` against `lovable/ontdek@{vp}-writers`. Scratch: `tmp/stage4/ledegids/`.

| Finding | Status | Fix (file:line) |
|---|---|---|
| Live bug: QA accounts ("INK QA MyProfiel", "INK QA Skryf") are listed publicly | owner (data) | Q6. No data changed |
| Directory clamped to the 768px content size at ≥1024 (3 cards × 240px, names wrapping) | fixed | `patterns/ledegids.php:53-54` (inner wide group has no `layout`, as in `ontdek.php`); cards are now 440px, as on /ontdek |
| Intro h1 + lede centred at 768 instead of left-aligned | fixed | `ledegids.php:37-38` (no `layout`); theme.json `.ink-ledegids-intro h1` / `__tekst` max-width 768 |
| OFF-RECIPE intro: no cream band or bottom rule; py 48/16 on the section; lede 16px | fixed | `ledegids.php:35-44`: `ink-band--intro --flush`, inner py 56→80, lede `lg` with lh 1.625 and mt 24 (theme.json `.ink-ledegids-intro*`) |
| Two headings for one listing (h1 "Ledegids" + visible h2 "Skrywers") | fixed | `SkrywersTab.php:245` (the h2 is `screen-reader-text`) |
| OFF-RECIPE directory band py 16/64 vs 40→56; gutter 24 vs 16 | fixed | `ledegids.php:51-52` `.ink-ontdek-paneel` |
| 24px blockGap between sections | fixed | `templates/page-ledegids.html:3` |
| OFF-TOKEN genre-pill padding 2px | accepted | this is the pill-outline recipe, 2px 10px (`maps/_recipes.csv:229`) |
| OFF-TOKEN bio lh 1.6 | fixed | now 1.625, the Lovable `writers-card-bio` recipe source. That value is not in the theme's 1.2/1.5/1.7 token set; this is a gap in the token set |
| Writer-card spacing, genre placement, name size | fixed | shared with row 03 (`03-ontdek-stage4.md`) |
| Writer name weight 600 vs 400 | owner (lead) | primitives.css request P-2 |
| Gradering tier label (no Lovable counterpart) | accepted | NR-1 (SCOPE) |
| Writer stats + Follow missing | owner | as row 03 Q4 |

**Phase 4 re-run (OFF-RECIPE).** Verify and determinism (390) pass.
- **390:** clean apart from P-2, the SB-3 band tint (accepted), and box mechanism (padding on the section, not the container).
- **768:** as at 390.
- **1440:** as at 390. The h1 height differs because of copy (one word against Lovable's three lines).
