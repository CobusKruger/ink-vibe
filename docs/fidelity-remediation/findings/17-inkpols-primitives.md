# 17 · inkpols — primitive register (Phase 4b)

**Pages:** `https://nuwe-ink.local/inkpols/` (list) · single: **none exists** · **Treatment:** C · **Date:** 2026-10-13 · **Stage 1**
**Register:** `maps/_primitive-observations.inkpols.csv` (4 `ink` rows, 0 `source` rows)
**Captures:** `tmp/fidelity-captures/ink/inkpols@{390,768,1440}.ndjson` — determinism gate 3/3 identical and
`verify --sample 30` 0 mismatches at every width. No duplicate audit-ids.

Header and footer were verified on row 00 (`--exclude-chrome`) and are not re-reported.

## Files read end to end

- `templates/archive-inkpols_uitgawe.html`, `patterns/inkpols.php`, `plugins/ink-core/src/InkPols/Archive.php`
- `templates/single-inkpols_uitgawe.html`, `patterns/reading-inkpols.php`, `InkPols/SingleIssue.php`, `InkPols/Viewer.php`
- `functions.php` (all of it)
- `theme.json` `styles.css`, parsed rule by rule, and every file in `assets/css/`, parsed rule by rule (comments stripped)

## Coverage

The database holds **zero** `inkpols_uitgawe` posts in any status (read-only query of `wpjj_posts`). The list
therefore renders only its heading and empty-state paragraph. **No single issue exists, so the single template
cannot be captured at all.** No QA fixture exists for this post type. The register holds:

- `section-band` — `inkpols-section` (`inkpols.php:22`, inline padding s-16 / s-24 / s-64)
- `heading` ×3 — `inkpols-heading`, one row per width, because the fluid `xxxl` preset gives 24.53 / 27.42 / 32px

Dropped: `inkpols-archive` (the `ink/inkpols-argief` block-root `<section>`, row 02 convention). The empty-state
`<p>` has no border or fill and is not a kind.

## Declaration comparison

- **heading.** `.ink-inkpols__heading` has **no rule anywhere**. Its size comes from `theme.json:167`
  `elements.h1` (fluid xxxl), and its margins are the UA h1 default 0.67em (16.4 / 18.4 / 21.4px, off-token, see
  `17-inkpols-conformance.md`). Compare kontak / lidmaatskap, whose h1 is pinned to 32px at every width, and
  ledegids, whose h1 is `xxl`. The four C pages show three h1 treatments.

## Load-scope cross-check: latent inert classes (populated state, not observable today)

The parse found **no CSS rule for any `ink-inkpols*` or `ink-inkpols-besonderhede*` / `ink-inkpols-leser*` class**,
neither in `theme.json` styles.css nor in `assets/css/*.css`, and `functions.php` registers none. So:

- `Archive.php:246` emits each issue as `<li class="ink-inkpols__item is-style-card">`. The registered card style's
  selector is `.wp-block-group.is-style-card` (`functions.php:1431`), which can never match an `<li>`. **When
  issues exist, the cards render without any card treatment.** Cover image size, date, volume and teaser are also
  unstyled. This is the shape `profiel.css:625` documents for `BydraesSurface` and that `theme.json` #223 patches for
  the ontdek cards.
- The single page's `ink-inkpols-besonderhede__*` meta and the `ink-inkpols-leser` fallback link are likewise
  unstyled.

`PRIMITIVE` (inert `is-style-card` on a non-group element) plus a coverage gap. Record it, do not fix it. Measuring
it needs seeded issues, which is an owner call (a QA-fixture issue, or the real migration).

## OFF-TOKEN

4 values, all margins on `inkpols-heading` (UA `0.67em`) (`findings/17-inkpols-conformance.md`).
