# 17 · inkpols — Stage 4 (Treatment C, OFF-RECIPE)

INK `/inkpols/` (list; 0 issues in the DB) · single `single-inkpols_uitgawe` (no issue exists). Populated list verified with a static fixture: the live page HTML with `Archive::card()`-shaped markup injected (`tmp/stage4/lib/inkpols-fixture/populated.html`), captured at 390/768/1440. No data written.

| Finding | Status | Fix (file:line) |
|---|---|---|
| Latent bug: no CSS for any `ink-inkpols*` class (live bug #10) | fixed | `theme.json` `.ink-inkpols*` / `.ink-inkpols-besonderhede*` / `.ink-inkpols-leser*` |
| Card recipe: `<li>` card | fixed (Stage 3 `ink-card`) + layout | `theme.json` flex column, hover lift + shadow md (library-card recipe) |
| Grid recipe | fixed | `.ink-inkpols__lys` 1→md 2→lg 3, gap 24 (fixture: 342 / 348×2 / 440×3) |
| Archive constrained to 768px content width | fixed | `InkPols/Archive.php:193,197` (`alignwide`) |
| Cover / title / meta / teaser unstyled | fixed | cover block, radius md, mb 16; title `h3-card` + hover primary; date + volume in `ink-meta-row--tight` (`Archive.php:257`); teaser 14/22.75 muted, mt 12, 3-line clamp |
| Year groups | fixed | `.ink-inkpols__jaar + .ink-inkpols__jaar` mt 48; year h2 mb 24 |
| OFF-TOKEN h1 margins (UA 0.67em) | fixed | `.ink-inkpols .ink-inkpols__heading` margin 0 0 32 |
| h1 16px below header after the header-gap removal | fixed | `patterns/inkpols.php:22-23` top padding s-16 → s-64 (archive-intro recipe) |
| Empty state a bare `<p>` | fixed | `Archive.php:194` `ink-card--empty`; text Lora 20/28 (library empty recipe) |
| Single: date / volume unstyled | fixed | `InkPols/SingleIssue.php:85,89` `ink-meta ink-meta--sm` |
| Single: cover / teaser unstyled | fixed | `theme.json` cover max 240px, border, radius lg; teaser = lede (18 / 1.625 muted) |
| Single: PDF fallback link unstyled | fixed | `InkPols/Viewer.php:155` `ink-btn ink-btn--primary ink-btn--lg`, centred |
| Measure populated list/single on the live site | owner (seed a QA-fixture issue) | — |

**Phase 4 re-run** (live `/inkpols/`, verify passed): 390 / 768 / 1440 — clean (heading margins on-token, empty state = `ink-card--empty` recipe). Populated fixture: card / grid / title / teaser values match the library-card recipe at all three widths. The single page is unmeasured (no issue exists); verified from markup and CSS only.
