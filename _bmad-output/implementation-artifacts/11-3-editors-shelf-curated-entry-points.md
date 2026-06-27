---
baseline_commit: (Story 11.2 commit)
---

# Story 11.3: Editor's shelf / curated entry points

Status: done

## Story

As a reader,
I want curated entry points,
so that I have guided starting places. (FR-56)

## Acceptance Criteria

**Given** the hub
**When** it renders
**Then** "Die redakteur se rak" and empty states are provided.

1. The hub's curated entry-point shelf is labelled **"Die redakteur se rak"** (H2) with the supporting line **"Drie stukke om mee te begin."** — the recency-driven 3-piece shelf shipped generically as "Uitgelig" in Story 11.1 is dressed in the design copy here. Curation is **automatic (recency)**, never per-item manual editorial linking (Principle 8).
2. The shelf shows only on the **unfiltered first page** (no facet, no search, page 1) — a guided starting place, not a result of a query.
3. **Context-aware empty states** replace the single generic line:
   - When nothing is active and the hub has no content → **"Nog niks op hierdie rak nie."** (the shelf is empty).
   - When a search or facet is active and nothing matches → **"Probeer 'n ander soekterm of blaai deur alle artikels."** plus a **"Vee filters uit"** link that clears all browse vars back to the clean hub.
4. The empty states stay **non-vacuous** — the heading + controls (search, and the facet filter when terms exist) still render around the empty line.
5. All copy is the already-authored Afrikaans from `ui-copy-translations.md` (glossary-consistent `__()` source) — copy-debt to ratify; no AI Afrikaans, nothing English leaks. Open browsing, conflation-clean, server-rendered (unchanged from 11.1/11.2).

## Tasks / Subtasks

- [x] Task 1: Re-dress the shelf as "Die redakteur se rak" (AC: #1, #2)
  - [x] `Training\Hub::featuredHtml()` — heading "Uitgelig" → **"Die redakteur se rak"** (H2) + supporting line "Drie stukke om mee te begin."; CSS `ink-opleiding__uitgelig*` → `ink-opleiding__rak*`. Still gated to the unfiltered first page (render unchanged).
- [x] Task 2: Context-aware empty states (AC: #3, #4)
  - [x] `Training\Hub::toHtml()` empty branch now computes `$is_filtered` from `$nav` and delegates to `emptyStateHtml()`: filtered → "Probeer 'n ander soekterm of blaai deur alle artikels." + a "Vee filters uit" link (`remove_query_arg` over `VAARDIGHEID_VAR`/`SEARCH_VAR`/`PAGED_VAR`); unfiltered → "Nog niks op hierdie rak nie." Heading + controls still render (non-vacuous).
  - [x] Private `emptyStateHtml( bool $is_filtered )` helper.
- [x] Task 3: Tests (AC: all)
  - [x] `HubTest`: shelf renders "Die redakteur se rak" + "Drie stukke om mee te begin."; filtered-empty shows "Probeer 'n ander soekterm…" + "Vee filters uit"; unfiltered-empty shows "Nog niks op hierdie rak nie." with no clear-filters link.
- [x] Task 4: Gates — `composer test:unit` 739 passed / 1 skipped; `stan` OK; `cs` clean; `copy:scan` 6/6 baseline; `deptrac` 3 pre-existing, 0 new.

## Dev Notes

- **The redakteur se rak IS the featured-3 shelf** [Source: ui-copy-translations.md:192-193]: "Die redakteur se rak" (H2) + "Drie stukke om mee te begin." map exactly onto the recency-3 strip from 11.1. No manual editorial pick mechanism (that is per-item manual linking — the Principle-8 anti-pattern); recency is automatic. This is a copy/label refinement, not a new data path.
- **Empty-state copy is authored** [Source: ui-copy-translations.md:194-196]: "Nog niks op hierdie rak nie." (shelf empty), "Probeer 'n ander soekterm of blaai deur alle artikels." (no match), "Vee filters uit" (clear-filters button → `remove_query_arg` over `VAARDIGHEID_VAR`/`SEARCH_VAR`/`PAGED_VAR`). All already in the UI-copy doc — copy-debt to ratify, not new placeholders (copy:scan stays clean).
- **Filtered vs shelf-empty** [Source: AC #3]: `toHtml` already receives `vaardigheid` + `search` in `$nav`; `$is_filtered = ( null !== $active_facet ) || ( '' !== $search )` drives which empty state renders. Mirrors the design's two distinct empty contexts.

### Project Structure Notes

- MOD: `wp-content/plugins/ink-core/src/Training/Hub.php` (featuredHtml copy/class, toHtml empty branch + emptyStateHtml helper), `tests/Unit/Training/HubTest.php`, `sprint-status.yaml`.
- No new files; no new deptrac edges; conflation-clean.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 11.3 (FR-56)]
- [Source: docs/ui-copy-translations.md:188-200 (Redakteur se rak en leë toestande)]
- [Source: wp-content/plugins/ink-core/src/Training/Hub.php (featuredHtml + toHtml from 11.1/11.2)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 11)

### Debug Log References

- `composer stan` runs sandbox-disabled (PHPStan TCP socket EPERM); OK.

### Completion Notes List

- **"Die redakteur se rak"** — the 11.1 recency-3 shelf is re-dressed in the design copy (H2 "Die redakteur se rak" + "Drie stukke om mee te begin.", CSS `ink-opleiding__rak*`). Still recency-curated (no manual editorial linking) and still gated to the unfiltered first page (`render()` unchanged).
- **Context-aware empty states** — `toHtml` derives `$is_filtered` from `$nav` (active facet or search) and `emptyStateHtml()` renders either "Probeer 'n ander soekterm of blaai deur alle artikels." + a "Vee filters uit" clear-all link (filtered) or "Nog niks op hierdie rak nie." (unfiltered shelf). Both non-vacuous (heading + controls still render). Replaces the single generic "Geen Opleiding gevind nie." line.
- **All copy authored** (ui-copy-translations.md) — copy-debt to ratify, not new placeholders; copy:scan stays at the 6/6 baseline.
- **Tests:** net +1 (featuredHtml re-dress assertions; split the empty-state test into filtered + unfiltered). Suite 738→739.

### File List

- `wp-content/plugins/ink-core/src/Training/Hub.php` (MOD — featuredHtml → "Die redakteur se rak"; toHtml empty branch + emptyStateHtml helper)
- `tests/Unit/Training/HubTest.php` (MOD — shelf + two empty-state tests)
- `_bmad-output/implementation-artifacts/11-3-editors-shelf-curated-entry-points.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 11.3 status)
