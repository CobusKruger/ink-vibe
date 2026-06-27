---
baseline_commit: bfa8008
---

# Story 10.1: biblioteek_item archive + single

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a reader,
I want a Biblioteek archive and single view,
so that I can browse curated/reference work. (FR-52)

## Acceptance Criteria

**Given** `biblioteek_item`
**When** the archive/single render
**Then** a featured strip + category filter + search + card grid (archive) and a single view are provided (Library layout reference).

1. A server-rendered Biblioteek archive block (`ink/biblioteek-argief`) lists **published `biblioteek_item`** posts, newest-first, paginated — the migration-load-bearing `/biblioteek/` URL prefix is preserved (the CPT already registers `has_archive => 'biblioteek'`, Story 2.1).
2. The archive carries a **featured strip** (the most recent N items, an "Uitgelig" lead-in) above the main card grid.
3. The archive carries a **category filter** over the `genre` taxonomy (an "Alles" pill + one pill per genre term in use), each a GET link that narrows the listing and resets the page; the active genre is visually marked (`is-active` + `aria-current`).
4. The archive carries a **search** field that filters the listing by keyword (native `s` over `biblioteek_item`), preserving the active genre.
5. The archive renders a **card grid** (title → permalink, genre badge, author), an empty-state line when nothing matches (non-vacuous: heading + controls still render), and prev/next pagination only when more than one page.
6. A **single view** for `biblioteek_item` is provided via an FSE `single-biblioteek_item.html` template embedding a `reading-biblioteek` pattern (type eyebrow "Biblioteek", title, author/date, content) — within locked header/footer chrome, three-layer-clean (no business logic in the theme).
7. Browsing the Biblioteek is **open** — never entitlement-gated, zero `Ink\Tiers`/`Ink\Entitlement` coupling (conflation rule). All listing logic is server-rendered `WP_Query` in `ink-core` (AD-7 — no REST for listings).
8. Date/archive browsing (10.2), pagination beyond prev/next (10.3) and an author filter (10.4) are **deferred, non-blocking** — out of scope here; this story ships the archive shell + featured strip + genre filter + search + single.

## Tasks / Subtasks

- [x] Task 1: New `Ink\Library` module skeleton (AC: #1, #7)
  - [x] `wp-content/plugins/ink-core/src/Library/Module.php` — `implements Ink\Kernel\Module`, `register()` delegates to `( new Archive() )->register()` (thin bootstrap, the Discovery/Engagement house style).
  - [x] Wired into `wp-content/plugins/ink-core/ink-core.php` bootstrap: `addModule( 'library', new Library\Module() )` (after `social`).
  - [x] Added a `Library` layer to `deptrac.yaml` allowed to depend on `Kernel` + `Content`. NO Entitlement/Tiers edge (browsing is open; conflation-clean) — mirrors the Discovery→Content (8.1) edge.
- [x] Task 2: `Library\Archive` server block — pure query + pure render (AC: #1–#5, #7)
  - [x] `wp-content/plugins/ink-core/src/Library/Archive.php` mirroring `Ink\Discovery\WorksArchive`: `BLOCK`/`PER_PAGE`/`FEATURED`/`PAGED_VAR`/`GENRE_VAR`/`SEARCH_VAR`; pure `queryArgs()` (genre `tax_query` only for a real slug, `s` only for a non-empty trimmed term), pure `featuredArgs()`, thin `render()` (featured strip only on the unfiltered first page), pure `toHtml()`/`featuredHtml()`/`searchHtml()`/`filterHtml()` with the `is-style-card` + `pill()` idiom.
  - [x] All new Afrikaans copy as glossary-consistent `__( …, 'ink-core' )` source literals ("Uitgelig", "Soek in die biblioteek…", "Soek", "Alles", "Vorige"/"Volgende", the `Geen %s gevind nie.` empty-state composition) — copy-debt to ratify (8.x precedent); no AI Afrikaans, nothing English leaks.
- [x] Task 3: Theme archive + single (AC: #1, #6)
  - [x] `templates/archive-biblioteek_item.html` — locked chrome + `<main>` embedding `ink-foundation/biblioteek`.
  - [x] `patterns/biblioteek.php` — embeds `<!-- wp:ink/biblioteek-argief /-->`; label via the `ink_foundation_term()` bridge (single-source).
  - [x] `templates/single-biblioteek_item.html` — locked chrome + `<main>` embedding `ink-foundation/reading-biblioteek`.
  - [x] `patterns/reading-biblioteek.php` — eyebrow via `ink_foundation_term( 'biblioteek_item', … )`, `post-title`, `post-author-name`/`post-date`, `post-content`; tokens-only (mirrors `reading-gedig.php`). No business logic.
- [x] Task 4: Tests (AC: all)
  - [x] `tests/Unit/Library/ArchiveTest.php` — 11 tests covering `queryArgs`/`featuredArgs`/`toHtml`/`featuredHtml`/`filterHtml`/`searchHtml` (incl. garbage-degrades, active marking, non-vacuous empty-state, prev/next gating).
  - [x] `tests/Unit/Library/BiblioteekTemplateTest.php` — 4 off-disk structural guardrails (non-vacuous) for the archive/single templates + patterns.
- [x] Task 5: Gates (AC: all)
  - [x] `composer test:unit` 695 passed / 1 skipped (+15, no regressions); `composer stan` OK; `composer cs` 0 errors on Library; `composer copy:scan` no new debt (6/6 baseline); `composer deptrac` 3 PRE-EXISTING `Kernel\Activation→Content` violations, 0 new (new `Library → Kernel,Content` edge allowed; Entitlement⟂Tiers untouched).

## Dev Notes

- **Mirror `Ink\Discovery\WorksArchive` exactly** [Source: wp-content/plugins/ink-core/src/Discovery/WorksArchive.php]: pure `queryArgs()` + pure `toHtml()` + thin `render()`; defensive request reads (`get_query_var` → `filter_input` fallback, `sanitize_key`/`absint`/`sanitize_text_field`); `pill()` for active-marked GET links; prev/next pagination only when `max_pages > 1`; composed `Geen %s gevind nie.` empty-state. This is the house style for server-rendered listings (AD-7 — `WP_Query`, never REST).
- **The CPT + taxonomy already exist** [Source: wp-content/plugins/ink-core/src/Content/PostTypes.php:172-180; Taxonomies.php:102-143]: `biblioteek_item` is public, `show_in_rest`, `has_archive => 'biblioteek'`, supports title/editor/author/thumbnail/excerpt; `genre` (hierarchical) is attached. **Do NOT re-register** — read the slugs from `PostTypes::BIBLIOTEEK_ITEM` + `Taxonomies::GENRE`.
- **Genre filter as data, not a side-effect** [Source: WorksArchive::dateBrowseHtml years pattern]: `toHtml` takes the genre term list as a param (like the `years` list) so it stays pure; `render()` does the `get_terms` fetch. Pass `array{slug,name}` per term. An empty genre list renders no filter row (like `dateBrowseHtml` with no years).
- **Search** [Source: WorksArchive request-read idiom]: use WP's native `s` query param scoped to `post_type=biblioteek_item` — a plain keyword filter is sufficient here (the diacritic-folded `SearchIndex` is a Discovery substrate over bydraes+skrywers, NOT biblioteek_item; do not reach for it). Add `s` to `queryArgs` only for a non-empty trimmed term.
- **Featured strip** [Source: AC #2]: the most-recent `FEATURED` published items, a separate small `WP_Query` in `render()`, rendered above the grid with an "Uitgelig" lead-in. Keep it simple — no editorial pick mechanism (that would be per-item manual linking, an anti-pattern; recency is automatic).
- **Conflation rule** [Source: project-context THE conflation rule; deptrac.yaml]: browsing published Biblioteek work is open — NO `Ink\Entitlement` gate, NO `Ink\Tiers` read. The `Library` module depends only on `Kernel` + `Content`. (The winner↔challenge linkage display is Story 10.5; the auto-update hook is 10.6 — not this story.)
- **Afrikaans-first** [Source: project-context Afrikaans-first; Epic-9 carry-forward copy-debt process]: new copy ships as glossary-consistent authored `__()` source flagged copy-debt to ratify (the 8.x precedent) — never AI Afrikaans. `Terms::label('biblioteek')` already returns "Biblioteek". Theme patterns route every user-facing label through `ink_foundation_term()` / `ink-foundation` gettext (Gate D; the leak-scan flags bare literals in `patterns/*.php`).
- **Single view = theme + pattern** [Source: single-gedig.html + reading-gedig.php]: `single-biblioteek_item.html` → `reading-biblioteek` pattern; presentation only, eyebrow label via the bridge, core blocks for title/author/date/content. WP comments stay disabled site-wide (Ink\Engagement\Comments) — no comments UI.

### Project Structure Notes

- NEW: `wp-content/plugins/ink-core/src/Library/Module.php`, `wp-content/plugins/ink-core/src/Library/Archive.php`.
- NEW theme: `templates/archive-biblioteek_item.html`, `templates/single-biblioteek_item.html`, `patterns/biblioteek.php`, `patterns/reading-biblioteek.php`.
- NEW tests: `tests/Unit/Library/ArchiveTest.php`, `tests/Unit/Library/BiblioteekTemplateTest.php`.
- MOD: `wp-content/plugins/ink-core/ink-core.php` (register the module), `deptrac.yaml` (new `Library` layer), `_bmad-output/implementation-artifacts/sprint-status.yaml` (status).
- **Expected new deptrac edges (pre-flagged, Epic-9 carry-forward):** `Library → Kernel`, `Library → Content` (reads `PostTypes::BIBLIOTEEK_ITEM` + `Taxonomies::GENRE`). NO `Library → Entitlement`/`Tiers`.
- Note (don't build here): date/archive browse (10.2), full pagination (10.3), author filter (10.4) — deferred; winner↔challenge linkage (10.5); auto-update hook (10.6).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 10.1 (FR-52)]
- [Source: wp-content/plugins/ink-core/src/Discovery/WorksArchive.php (house style for server-rendered listings)]
- [Source: wp-content/plugins/ink-core/src/Content/PostTypes.php:172-180 + Taxonomies.php:102-143 (CPT + genre already registered)]
- [Source: wp-content/themes/ink-foundation/templates/single-gedig.html + patterns/reading-gedig.php (single-view pattern)]
- [Source: wp-content/themes/ink-foundation/patterns/ontdek.php + tests/Unit/Discovery/OntdekTemplateTest.php (theme embed + structural-guardrail pattern)]
- [Source: deptrac.yaml (Discovery→Content edge precedent; conflation rule)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 10)

### Debug Log References

- `composer stan` must run with the sandbox disabled (PHPStan binds a local TCP analysis socket → EPERM under the command sandbox); result OK / 123 files.

### Completion Notes List

- **New `Ink\Library` module** (the Biblioteek section's home), wired into the bootstrap after `social`. Thin `Module::register()` → `Archive` block, the Discovery/Engagement house style. New `Library → Kernel,Content` deptrac layer declared (reads the migration-load-bearing `PostTypes::BIBLIOTEEK_ITEM` + `Taxonomies::GENRE`); zero Entitlement/Tiers — browsing published library work is open (conflation-clean).
- **`ink/biblioteek-argief` server block** mirrors `WorksArchive`: pure `queryArgs()`/`featuredArgs()` + pure render helpers + a thin `render()`. Featured strip ("Uitgelig", most-recent 3) shows only on the unfiltered first page; genre filter is the `genre` taxonomy (terms fetched via `get_terms`, passed as data to keep `toHtml` pure); search is native `s` scoped to the CPT (NOT the Discovery folded `SearchIndex`, which indexes bydraes+skrywers). Defensive request reads (query-var → GET fallback, sanitised); garbage genre/search degrade to the unfiltered listing. The `tax_query` carries a documented `phpcs:ignore` (bounded single-facet, AD-7) mirroring WorksArchive's `meta_query`.
- **Theme:** `archive-biblioteek_item.html`/`single-biblioteek_item.html` embed `biblioteek.php`/`reading-biblioteek.php` within locked header/footer chrome; all labels route through `ink_foundation_term()` (Gate D, single-source). Three-layer-clean — no business logic in the theme.
- **Tests:** 15 new (11 Archive unit + 4 template structural guardrails, non-vacuous). Suite 680→695 (+15), zero regressions.
- **Deferred (out of scope, non-blocking):** date/archive browse (10.2), full pagination (10.3), author filter (10.4) — prev/next pagination is provided; deeper paging is 10.3.

### File List

- `wp-content/plugins/ink-core/src/Library/Module.php` (NEW — Library module bootstrap)
- `wp-content/plugins/ink-core/src/Library/Archive.php` (NEW — `ink/biblioteek-argief` server block)
- `wp-content/plugins/ink-core/ink-core.php` (MOD — register the `library` module)
- `deptrac.yaml` (MOD — new `Library` layer: Kernel + Content)
- `wp-content/themes/ink-foundation/templates/archive-biblioteek_item.html` (NEW)
- `wp-content/themes/ink-foundation/templates/single-biblioteek_item.html` (NEW)
- `wp-content/themes/ink-foundation/patterns/biblioteek.php` (NEW)
- `wp-content/themes/ink-foundation/patterns/reading-biblioteek.php` (NEW)
- `tests/Unit/Library/ArchiveTest.php` (NEW)
- `tests/Unit/Library/BiblioteekTemplateTest.php` (NEW)
- `_bmad-output/implementation-artifacts/10-1-biblioteek-item-archive-single.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — epic-10 + 10.1 status)
