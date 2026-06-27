---
baseline_commit: 3e8a24c
---

# Story 11.1: opleiding_artikel hub

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a reader,
I want a training resource hub,
so that I can find writing guidance. (FR-54)

## Acceptance Criteria

**Given** `opleiding_artikel`
**When** the hub renders
**Then** it is a resource hub (Library-layout archetype), not an LMS.

1. A server-rendered Opleiding hub block (`ink/opleiding-argief`) lists **published `opleiding_artikel`** posts, newest-first, paginated — the migration-load-bearing `/opleiding/` URL prefix is preserved (the CPT already registers `has_archive => 'opleiding'`, Story 2.1). No course/lesson/progress mechanics — it is a **resource hub**, not an LMS.
2. The hub carries a **featured strip** (the most recent N items, an "Uitgelig" lead-in) above the main card grid (the Library-layout archetype — recency, never per-item manual linking).
3. The hub carries a **search** field that filters the listing by keyword (native `s` over `opleiding_artikel`).
4. The hub renders a **card grid** (title → permalink, author), an empty-state line when nothing matches (non-vacuous: heading + controls still render), and prev/next pagination only when more than one page.
5. A **single view** for `opleiding_artikel` is provided via an FSE `single-opleiding_artikel.html` template embedding a `reading-opleiding` pattern (type eyebrow "Hulpbronartikel", title, author/date, content) — within locked header/footer chrome, three-layer-clean (no business logic in the theme).
6. Browsing the Opleiding hub is **open** — never entitlement-gated, zero `Ink\Tiers`/`Ink\Entitlement` coupling (THE conflation rule). All listing logic is server-rendered `WP_Query` in `ink-core` (AD-7 — no REST for listings).
7. The `vaardigheid` faceted filter (11.2), the redakteur-se-rak curated entry points (11.3), auto cross-surfacing (11.4) and the contribution CTA (11.5) are **out of scope here** — this story ships the hub shell + featured strip + search + card grid + single.
8. **Epic-10 carry-forward — shared archive helper.** Rather than copy `Ink\Library\Archive` a third time (after `Ink\Discovery\WorksArchive` + `Ink\Library\Archive`), the genuinely-shared, stable, pure primitives are **extracted** into a `Ink\Kernel\ArchiveRender` helper (the active-markable `pill()`, the prev/next `pagination()`, and the defensive `requestInt`/`requestKey`/`requestText` query-var→GET reads) and adopted by the new hub + the two existing archives. The deferred `pill()` pre-escape hardening (the Epic-10 review LOW) is folded in: `pill()` escapes the URL itself (single escape point) so a caller can never pass an unescaped href.

## Tasks / Subtasks

- [x] Task 1: Extract `Ink\Kernel\ArchiveRender` shared helper (AC: #8)
  - [x] `wp-content/plugins/ink-core/src/Kernel/ArchiveRender.php` — pure static helpers: `pill()` (escapes `$url` via `esc_url` internally — the hardening), `pagination( int $paged, int $max_pages, string $css_prefix, string $paged_var )` (prev/next only when `max_pages > 1`), and `requestInt`/`requestKey`/`requestText`. Uses only WP core — zero `Ink\*` deps, sits in Kernel (no new deptrac edge).
  - [x] Refactored `Ink\Library\Archive` to delegate `pill()`/pagination/request-reads to `ArchiveRender` (call sites pass RAW urls now that `pill()` self-escapes); output byte-identical (CSS classes `ink-biblioteek__*` preserved; Library suite green).
  - [x] Refactored `Ink\Discovery\WorksArchive` to delegate the same primitives (CSS classes `ink-ontdek-werke__*` preserved); Epic-8 suite stayed green.
- [x] Task 2: New `Ink\Training` module skeleton (AC: #1, #6)
  - [x] `wp-content/plugins/ink-core/src/Training/Module.php` — `implements Ink\Kernel\Module`, `register()` delegates to `( new Hub() )->register()`.
  - [x] Wired into bootstrap: `addModule( 'training', new Training\Module() )` (after `library`).
  - [x] Added a `Training` layer to `deptrac.yaml` allowed `Kernel` + `Content`. NO Entitlement/Tiers edge.
- [x] Task 3: `Training\Hub` server block — pure query + pure render (AC: #1–#4, #6)
  - [x] `wp-content/plugins/ink-core/src/Training/Hub.php` mirroring `Ink\Library\Archive`: `BLOCK = 'ink/opleiding-argief'`, `PAGED_VAR = 'opleiding_bladsy'`, `SEARCH_VAR = 'opleiding_soek'`; pure `queryArgs()`/`featuredArgs()`, thin `render()`, pure `toHtml()`/`featuredHtml()`/`searchHtml()` via `ArchiveRender`. No vaardigheid filter (11.2 seam left).
  - [x] New copy as glossary-consistent `__()` source (reuse "Uitgelig"/"Soek"/`Geen %s gevind nie.`; new "Soek in opleiding…") — copy-debt to ratify; no AI Afrikaans.
- [x] Task 4: Theme archive + single (AC: #1, #5)
  - [x] `templates/archive-opleiding_artikel.html` embeds `ink-foundation/opleiding`.
  - [x] `patterns/opleiding.php` embeds `wp:ink/opleiding-argief`; label via `ink_foundation_term( 'opleiding', … )`.
  - [x] `templates/single-opleiding_artikel.html` embeds `ink-foundation/reading-opleiding`.
  - [x] `patterns/reading-opleiding.php` eyebrow via `ink_foundation_term( 'opleiding_artikel', … )` + title/author/date/content; tokens-only.
- [x] Task 5: Tests (AC: all)
  - [x] `tests/Unit/Kernel/ArchiveRenderTest.php` — 6 tests (pill active-marking + esc_url-is-the-escape-point; pagination gating/prefix/var/clamp).
  - [x] `tests/Unit/Training/HubTest.php` — 10 tests (queryArgs incl. resource-hub-not-LMS assertion, featuredArgs, toHtml/featuredHtml/searchHtml, prev/next gating, non-vacuous empty-state).
  - [x] `tests/Unit/Training/OpleidingTemplateTest.php` — 4 off-disk structural guardrails (non-vacuous).
- [x] Task 6: Gates (AC: all)
  - [x] `composer test:unit` 734 passed / 1 skipped (+20, no regressions; Library + Discovery suites green after the refactor); `composer stan` OK (130 files); `composer cs` 0 errors; `composer copy:scan` 6/6 baseline, no new debt; `composer deptrac` 3 PRE-EXISTING `Kernel\Activation→Content` violations, 0 new (new `Training → Kernel,Content` edge allowed; Entitlement⟂Tiers untouched).

## Dev Notes

- **Mirror `Ink\Library\Archive` exactly** [Source: wp-content/plugins/ink-core/src/Library/Archive.php]: pure `queryArgs()`/`featuredArgs()` + pure render helpers + a thin `render()`; the featured strip ("Uitgelig", most-recent N) shows only on the unfiltered first page; native `s` search scoped to the CPT; composed `Geen %s gevind nie.` empty-state; prev/next only when `max_pages > 1`. This is the house style for server-rendered listings (AD-7 — `WP_Query`, never REST).
- **The CPT + taxonomies already exist** [Source: PostTypes.php:60,181-188; Taxonomies.php:97-127]: `opleiding_artikel` is public, `show_in_rest`, `has_archive => 'opleiding'`, `rewrite => 'opleiding'`; `genre` (shared) + `vaardigheid` (opleiding-only) are attached. **Do NOT re-register** — read `PostTypes::OPLEIDING_ARTIKEL`. (The `vaardigheid` filter is 11.2; this story does not query it.)
- **Resource hub, not an LMS** [Source: epics.md#Story 11.1; spec line 220]: NO course/module/lesson/enrolment/progress mechanics. It is the Library-layout archetype applied to `opleiding_artikel` — featured strip + search + card grid + single.
- **Shared archive helper (Epic-10 carry-forward)** [Source: epic-10-retro §carry-forward; Discovery\WorksArchive + Library\Archive]: the third archive must not be a third copy. Extract `Ink\Kernel\ArchiveRender` (pill/pagination/request-reads). `pill()` self-escapes the URL (folds in the Epic-10 review LOW), so existing call sites drop their `esc_url(...)` wrapping. Under the Brain-Monkey stubs (`esc_url` = `returnArg(1)`) the output is identical, so the Library/Discovery suites guard the refactor — run them and confirm green before committing. Kernel depends on no module and everything depends on Kernel, so no deptrac edges change.
- **Conflation rule** [Source: project-context THE conflation rule; deptrac.yaml]: browsing published Opleiding work is open — NO `Ink\Entitlement` gate, NO `Ink\Tiers` read. The `Training` module depends only on `Kernel` + `Content`.
- **Afrikaans-first** [Source: project-context Afrikaans-first; copy-debt process]: reuse the already-authored "Uitgelig"/"Soek"/`Geen %s gevind nie.` literals; the one new string ("Soek in opleiding…") ships as a glossary-consistent authored `__()` source flagged copy-debt to ratify — never AI Afrikaans. `Terms::label('opleiding')` returns "Opleiding"; `Terms::label('opleiding_artikel')` returns "Hulpbronartikel". Theme patterns route every label through `ink_foundation_term()` (Gate D).
- **Single view = theme + pattern** [Source: single-biblioteek_item.html + reading-biblioteek.php]: `single-opleiding_artikel.html` → `reading-opleiding` pattern; presentation only, eyebrow label via the bridge, core blocks for title/author/date/content. WP comments stay disabled site-wide — no comments UI. (The 11.4 related-training block is added to the reading patterns in that story, not here.)

### Project Structure Notes

- NEW: `wp-content/plugins/ink-core/src/Kernel/ArchiveRender.php`, `wp-content/plugins/ink-core/src/Training/Module.php`, `wp-content/plugins/ink-core/src/Training/Hub.php`.
- NEW theme: `templates/archive-opleiding_artikel.html`, `templates/single-opleiding_artikel.html`, `patterns/opleiding.php`, `patterns/reading-opleiding.php`.
- NEW tests: `tests/Unit/Kernel/ArchiveRenderTest.php`, `tests/Unit/Training/HubTest.php`, `tests/Unit/Training/OpleidingTemplateTest.php`.
- MOD: `wp-content/plugins/ink-core/ink-core.php` (register the module), `deptrac.yaml` (new `Training` layer), `Ink\Library\Archive` + `Ink\Discovery\WorksArchive` (delegate to `ArchiveRender`), `_bmad-output/implementation-artifacts/sprint-status.yaml` (status).
- **Expected new deptrac edges (pre-flagged, carry-forward habit):** `Training → Kernel`, `Training → Content` (reads `PostTypes::OPLEIDING_ARTIKEL`). NO `Training → Entitlement`/`Tiers`. `ArchiveRender` is in Kernel → no edge.
- Note (don't build here): vaardigheid facets (11.2), redakteur se rak (11.3), auto cross-surfacing (11.4), contribution CTA (11.5).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 11.1 (FR-54)]
- [Source: wp-content/plugins/ink-core/src/Library/Archive.php (the archive house style being shared)]
- [Source: wp-content/plugins/ink-core/src/Content/PostTypes.php:60,181-188 + Taxonomies.php:97-127 (CPT + taxonomies already registered)]
- [Source: wp-content/themes/ink-foundation/templates/single-biblioteek_item.html + patterns/reading-biblioteek.php (single-view pattern)]
- [Source: tests/Unit/Library/ArchiveTest.php + BiblioteekTemplateTest.php (test idiom)]
- [Source: epic-10-retro-2026-06-27.md (shared-archive-helper carry-forward decision)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 11)

### Debug Log References

- `composer stan` must run with the command sandbox disabled (PHPStan binds a local TCP analysis socket → EPERM under the sandbox); result OK / 130 files.

### Completion Notes List

- **Epic-10 carry-forward resolved — shared archive helper extracted.** `Ink\Kernel\ArchiveRender` now owns the three identical primitives (`pill`, `pagination`, `requestInt`/`requestKey`/`requestText`) that `Discovery\WorksArchive` + `Library\Archive` had each copied. `pill()` now self-escapes the href via `esc_url` (the single escape point — folds in the Epic-10 review's deferred LOW), so all call sites pass the RAW `add_query_arg`/`remove_query_arg` result. Both existing archives were refactored to delegate; their Epic-8/Epic-10 suites stayed green (the Brain-Monkey `esc_url`=returnArg stub makes the refactor output-identical), guarding the change. Kernel depends on no module and everything depends on Kernel → no deptrac edges changed.
- **New `Ink\Training` module** (the Opleiding section's home), wired into the bootstrap after `library`. Thin `Module::register()` → `Hub` block, the Library/Discovery house style. New `Training → Kernel,Content` deptrac layer (reads `PostTypes::OPLEIDING_ARTIKEL`); zero Entitlement/Tiers — browsing published training is open (conflation-clean).
- **`ink/opleiding-argief` server block** is the Library-layout archetype applied to `opleiding_artikel`: pure `queryArgs()`/`featuredArgs()` + pure render helpers + a thin `render()`. Featured strip ("Uitgelig", most-recent 3) only on the unfiltered first page; native `s` search scoped to the CPT; defensive request reads via `ArchiveRender`; garbage search degrades to the unfiltered listing. A **resource hub, not an LMS** — recency ordering only, no course/lesson/progress mechanics (asserted in a test). The `vaardigheid` faceted filter is Story 11.2 (seam left, never queried here).
- **Theme:** `archive-opleiding_artikel.html`/`single-opleiding_artikel.html` embed `opleiding.php`/`reading-opleiding.php` within locked header/footer chrome; all labels route through `ink_foundation_term()` (Gate D, single-source). Three-layer-clean.
- **Tests:** 20 new (6 ArchiveRender + 10 Hub + 4 template guardrails, non-vacuous). Suite 714→734, zero regressions.
- **Deferred (out of scope, non-blocking):** vaardigheid facets (11.2), redakteur se rak (11.3), auto cross-surfacing (11.4), contribution CTA (11.5).

### File List

- `wp-content/plugins/ink-core/src/Kernel/ArchiveRender.php` (NEW — shared archive primitives: pill/pagination/request-reads)
- `wp-content/plugins/ink-core/src/Training/Module.php` (NEW — Training module bootstrap)
- `wp-content/plugins/ink-core/src/Training/Hub.php` (NEW — `ink/opleiding-argief` server block)
- `wp-content/plugins/ink-core/src/Library/Archive.php` (MOD — delegate to `ArchiveRender`)
- `wp-content/plugins/ink-core/src/Discovery/WorksArchive.php` (MOD — delegate to `ArchiveRender`)
- `wp-content/plugins/ink-core/ink-core.php` (MOD — register the `training` module)
- `deptrac.yaml` (MOD — new `Training` layer: Kernel + Content)
- `wp-content/themes/ink-foundation/templates/archive-opleiding_artikel.html` (NEW)
- `wp-content/themes/ink-foundation/templates/single-opleiding_artikel.html` (NEW)
- `wp-content/themes/ink-foundation/patterns/opleiding.php` (NEW)
- `wp-content/themes/ink-foundation/patterns/reading-opleiding.php` (NEW)
- `tests/Unit/Kernel/ArchiveRenderTest.php` (NEW)
- `tests/Unit/Training/HubTest.php` (NEW)
- `tests/Unit/Training/OpleidingTemplateTest.php` (NEW)
- `_bmad-output/implementation-artifacts/11-1-opleiding-artikel-hub.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — epic-11 + 11.1 status)
