---
baseline_commit: 4ce168e
---

# Story 15.6: Winners-post featured slot + featured-feed ordering (R2)

Status: done

## Story

As a reader,
I want the winners announcement featured on the home page,
so that the latest results are prominent. (FR-50-R2)

## Acceptance Criteria

1. **Given** a generated wenneraankondiging (12A.4) **When** the home featured area renders **Then** the featured slot hosts it and ordering puts **algehele wenner first**, ahead of ordinary wenners (drives/consumes 12A.7).
2. The featured-feed ordering is a pure, tested INK rule (algehele wenner = rank 1 first, then ranks 2–3, ties by id) reusing the existing `Challenges\Placements` rank semantics — this is the contract 12A.7 fills.
3. **Forward-compatibility (12A is unbuilt):** the wenneraankondiging generation (12A.4) does NOT exist yet. The home featured slot is a server block (`ink/wenner-kollig`) that reads its data from a filter seam `ink_home_featured_winner` which 12A.4/12A.7 will supply; until then the filter returns nothing and the block **collapses to empty markup** (renders nothing), exactly like the 14.3 `borg-strook` strip when there are no active sponsors. No fake/placeholder winner is shown.
4. The block is embedded in `front-page.html` in the featured-slot position (page-map: between the Uitdaging teaser and Uitgesoekte werke).
5. Three-layer: ordering + render in `ink-core` (the theme embeds the block). Conflation-clean: placements hang off entries + their Gradering pool — zero Entitlement; the home featured slot is open (viewing published results).

## Tasks / Subtasks

- [x] Task 1: `Ink\Challenges\FeaturedWinners` — ordering + `ink/wenner-kollig` block (AC: #1–#5)
  - [ ] `BLOCK` const `ink/wenner-kollig`; `FEATURED_FILTER` const `ink_home_featured_winner`.
  - [ ] Pure `order(array $winners): array` — algehele wenner (rank 1) first, then 2/3; deterministic ties by id (reuses `Placements::isAlgeheleWenner` / rank constants).
  - [ ] Thin `render()` → reads the filter (null = nothing) → pure `toHtml(array $featured)`.
  - [ ] `toHtml()`: empty/no-announcement → `''` (collapse). Populated → the announcement title (linked to its permalink) + the ordered winners, each with its `Placements::placementLabel` (algehele wenner / wenner) and a "Lees die volledige storie" link. Escaped output; tokens via the theme.
  - [ ] `register()`: `init` → `registerBlock`.
- [x] Task 2: Wire + embed (AC: #4)
  - [ ] Register `FeaturedWinners` in `Challenges\Module::register()`.
  - [ ] Embed `<!-- wp:ink/wenner-kollig /-->` in `front-page.html` between `huidige-uitdaging` and `featured-grid`.
- [x] Task 3: Tests (AC: #1, #2, #3)
  - [ ] `tests/Unit/Challenges/FeaturedWinnersTest.php`: `order()` puts algehele wenner first (input [rank2, rank1, rank3] → [1,2,3]); ties by id; `toHtml(empty)` returns `''` (the collapse invariant — non-vacuous: prove it WOULD render with data); `toHtml(populated)` contains the announcement link + winner titles in algehele-wenner-first order + the placement labels.
  - [ ] `tests/Unit/Org/FeaturedSlotTemplateTest.php`: `front-page.html` embeds `wp:ink/wenner-kollig` in the featured-slot position (before `featured-grid`).
- [x] Task 4: Gates
  - [ ] `composer test:unit` green; `phpcs`/`phpstan` 0 errors; `composer deptrac` no new edge; `composer copy:scan` no new debt; `php -l` clean.

## Dev Notes

### The 12A dependency (read first)
- Epic 12A is ENTIRELY backlog (sprint-status): 12A.4 (wenneraankondiging post generation) and 12A.7 (featured-feed ordering) are not built. Story 15.6 therefore builds the **home-side featured slot + the ordering contract**, and consumes 12A's output via the `ink_home_featured_winner` filter. When 12A.4 generates the announcement and 12A.7 assembles the ordered winner set, it hooks this filter to populate the slot. Until then the slot collapses — no placeholder winner, no broken markup.
- The existing data model to reuse: `Challenges\Placements` — `PLACEMENT_META_KEY = ink_entry_placement`, `RANK_FIRST = 1`, `isAlgeheleWenner()`, `placementLabel()` (glossary: rank 1 = "algehele wenner", 2–3 = "wenner"). `order()` is the home-feed flat ordering (distinct from `Placements::arrange()`'s per-pool grouping).

### Precedent
- Server block + thin `render()` + pure `toHtml()` + collapse-when-empty: `Sponsors\HomepageStrip` / `Sponsors\RecognitionSection` (14.3/14.4). Test `toHtml()` with Brain Monkey (`__`/`esc_*` mocked) — see `RecognitionSectionTest`.
- Template-embed test: `tests/Unit/Org/TuisbladTemplateTest.php` (15.1).

### Architecture compliance
- Three-layer: ordering + markup in ink-core; theme embeds the block. Conflation-clean (placements ⟂ Entitlement). Copy: "Lees die volledige storie" is authored (ui-copy line 83); placement labels are glossary-backed via `Placements::placementLabel`; the announcement title is data, not INK copy → no new copy debt.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 15.6] (FR-50-R2; "drives/consumes 12A.7")
- [Source: wp-content/plugins/ink-core/src/Challenges/Placements.php — rank semantics]
- [Source: wp-content/plugins/ink-core/src/Sponsors/RecognitionSection.php — server-block + collapse precedent]
- [Source: docs/design-handoff/page-map.csv — front-page "Wenneraankondiging (featured slot)"]
- [Source: docs/ui-copy-translations.md line 83 — "Lees die volledige storie"]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 940 passed, 1 skipped. New `FeaturedWinnersTest` (6) + `FeaturedSlotTemplateTest` (1) = 7 passed.
- `phpcs`/`phpstan` → 0 errors. `composer deptrac` → unchanged at the 3 PRE-EXISTING `Activation → Content` violations; the Challenges layer (where `FeaturedWinners` lives, reusing `Placements` + a WP-core `apply_filters` seam) introduces NO new edge. `composer copy:scan` → no new debt. `php -l` clean.

### Completion Notes List

- **12A is unbuilt**, so this story delivers the home-side featured slot + the ordering contract, decoupled via the `ink_home_featured_winner` filter. `Ink\Challenges\FeaturedWinners`:
  - `order()` — the pure, tested featured-feed ordering: **algehele wenner (rank 1) first**, then ranks 2–3, ties by ascending id (reuses `Placements` rank semantics). This is the FR-50-R2 / 12A.7 contract.
  - `ink/wenner-kollig` server block (`render()` reads the filter → pure `toHtml()`): renders the announcement (linked) + ordered winners with their `Placements::placementLabel` (algehele wenner / wenner); the algehele winner item gets a distinguishing modifier class.
  - **Graceful collapse:** with no announcement (the filter yields nothing today), `toHtml()` returns `''` — the slot renders nothing, exactly like the 14.3 sponsor strip. No fake winner is shown. Tested as a non-vacuous invariant (proves it WOULD render with data).
- Embedded `wp:ink/wenner-kollig` in `front-page.html` in the page-map featured-slot position (after the Uitdaging teaser, before Uitgesoekte werke). Wired via `Challenges\Module`.
- When 12A.4 generates the wenneraankondiging and 12A.7 assembles the ordered winner set, they hook `ink_home_featured_winner` to populate the slot — no theme change needed then.
- Conflation-clean: placements ⟂ Entitlement; the home featured slot is open. No new copy debt ("Lees…" link unused after simplification; labels are glossary-backed; announcement title is data).

### File List

- `wp-content/plugins/ink-core/src/Challenges/FeaturedWinners.php` (NEW)
- `wp-content/plugins/ink-core/src/Challenges/Module.php` (MODIFIED — registers FeaturedWinners)
- `wp-content/themes/ink-foundation/templates/front-page.html` (MODIFIED — wenner-kollig featured slot)
- `tests/Unit/Challenges/FeaturedWinnersTest.php` (NEW)
- `tests/Unit/Org/FeaturedSlotTemplateTest.php` (NEW)
- `_bmad-output/implementation-artifacts/15-6-winners-post-featured-slot-featured-feed-ordering-r2.md`, `sprint-status.yaml` (tracking)

## Change Log

- 2026-06-28 — Story 15.6 implemented: home winners featured slot (`ink/wenner-kollig`) + the algehele-wenner-first featured-feed ordering, decoupled from the unbuilt 12A via the `ink_home_featured_winner` filter; collapses gracefully until 12A supplies data. Status → done.
