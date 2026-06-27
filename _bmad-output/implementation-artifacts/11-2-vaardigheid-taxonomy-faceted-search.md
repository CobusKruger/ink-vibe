---
baseline_commit: (Story 11.1 commit)
---

# Story 11.2: vaardigheid taxonomy + faceted search

Status: done

## Story

As a reader,
I want faceted search by skill area,
so that I can find relevant training. (FR-54)

## Acceptance Criteria

**Given** the `vaardigheid` taxonomy
**When** I search
**Then** facets include Begin hier, Skryfkuns, Digkuns, Prosa, Stylfigure, Redigeer en hersien, Stem en styl.

1. The Opleiding hub (`ink/opleiding-argief`, Story 11.1) carries a **`vaardigheid` faceted filter** — an "Alles" pill + one pill per `vaardigheid` term in use, each a GET link that narrows the listing and resets the page; the active facet is visually marked (`is-active` + `aria-current`). The facets are the `vaardigheid` taxonomy terms (the canonical seed set per `ui-copy-translations.md`: Begin hier, Skryfkuns, Digkuns, Prosa, Stylfigure, Redigeer en hersien, Stem en styl).
2. The facet narrows the listing via a `vaardigheid` `tax_query` added **only for a real term slug** — a hostile/garbage facet degrades to the unfiltered listing rather than a broken query (the 10.1 genre-filter idiom).
3. The keyword **search preserves the active facet** (a GET form would otherwise reset it to "Alles") via a hidden field — and vice-versa, the facet links preserve nothing stateful but reset the page.
4. The featured "Uitgelig" strip shows only on the **unfiltered first page** (no facet, no search, page 1) — consistent with 11.1.
5. The filter renders **nothing** when there are no `vaardigheid` terms in use (an empty hub shows no filter row), and the empty-state stays non-vacuous (heading + controls render).
6. Browsing stays **open** — zero `Ink\Tiers`/`Ink\Entitlement` (conflation rule); the facet reads only `Ink\Content\Taxonomies::VAARDIGHEID`. Server-rendered `WP_Query` (AD-7).

## Tasks / Subtasks

- [x] Task 1: Extend `Training\Hub` with the vaardigheid facet (AC: #1–#6)
  - [x] Added `VAARDIGHEID_VAR = 'opleiding_vaardigheid'`; `use Ink\Content\Taxonomies`.
  - [x] `queryArgs()` gained a `?string $vaardigheid` param → a `VAARDIGHEID` `tax_query` only for a non-empty slug (phpcs:ignore single-facet, mirroring `Library\Archive`).
  - [x] `render()` reads the facet via `ArchiveRender::requestKey`; the featured strip gates on (page 1 ∧ no facet ∧ no search); fetches `vaardigheidTerms()` and passes the rows to `toHtml`.
  - [x] `vaardigheidTerms()` — `get_terms` over `VAARDIGHEID` (hide_empty), mapped to `{slug,name}` rows (kept out of the pure `toHtml`).
  - [x] `toHtml()` gained a `$facets` param + a `filterHtml()` row; `searchHtml()` gained the active-facet param + a hidden field to preserve it.
- [x] Task 2: Tests (AC: all)
  - [x] Extended `tests/Unit/Training/HubTest.php` (+5 tests): `queryArgs` vaardigheid tax_query only for a real slug; `filterHtml` renders the canonical facets + marks the active one + renders nothing without terms; `searchHtml` no-hidden-field/with-hidden-field; `toHtml` integrates the filter row. Updated the existing `toHtml`/`queryArgs` calls for the new param order.
- [x] Task 3: Gates — `composer test:unit` 738 passed / 1 skipped (+4 net); `stan` OK; `cs` clean (phpcbf aligned the new const block); `copy:scan` 6/6 baseline; `deptrac` 3 pre-existing, 0 new (no new edge — `Training → Content` already covers `Taxonomies::VAARDIGHEID`).

## Dev Notes

- **Mirror `Ink\Library\Archive`'s genre filter exactly** [Source: Library/Archive.php filterHtml/genreTerms/searchHtml]: pure `filterHtml( array $facets, ?string $active )` via `ArchiveRender::pill`; `searchHtml` carries the active facet in a hidden field (a `method="get"` form replaces the whole query string on submit); `get_terms` fetched in `render()` and passed as data so `toHtml` stays pure.
- **Facets = `vaardigheid` terms in use** [Source: spec line 174; ui-copy-translations.md:179-186]: the canonical seed set (Begin hier … Stem en styl) lives as taxonomy terms (seeded via migration, Epic 16), not in the `Terms` registry. The filter is content-driven (`hide_empty`), consistent with the 10.1 genre filter — a facet with no published training does not show. `vaardigheid` is attached only to `opleiding_artikel` (Taxonomies.php:127), so `get_terms` returns the training skill areas.
- **No new copy** — reuse "Alles" (already authored). The facet names are term names from the DB, not `__()` literals.
- **No new deptrac edge** — `Training → Content` (10.1/11.1) already covers reading `Taxonomies::VAARDIGHEID`.

### Project Structure Notes

- MOD: `wp-content/plugins/ink-core/src/Training/Hub.php` (vaardigheid facet), `tests/Unit/Training/HubTest.php` (facet tests), `sprint-status.yaml`.
- No new files; no new deptrac edges; conflation-clean.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 11.2 (FR-54)]
- [Source: wp-content/plugins/ink-core/src/Library/Archive.php (genre-filter idiom being mirrored)]
- [Source: wp-content/plugins/ink-core/src/Content/Taxonomies.php:121-127 (vaardigheid attached to opleiding_artikel)]
- [Source: docs/ui-copy-translations.md:179-186 (canonical vaardigheid facets)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 11)

### Debug Log References

- `composer stan` runs sandbox-disabled (PHPStan TCP socket EPERM); OK.

### Completion Notes List

- **`vaardigheid` faceted filter added to the hub**, mirroring the 10.1 genre-filter idiom over the `vaardigheid` taxonomy (attached only to `opleiding_artikel`): "Alles" + a pill per term in use (`get_terms` hide_empty, fetched in `render()` and passed as data so `toHtml` stays pure), each a GET link that narrows + resets the page, active marked (`is-active` + `aria-current`) via `ArchiveRender::pill`. The canonical seed facets (Begin hier … Stem en styl) are taxonomy terms seeded via migration; the filter is content-driven so a facet with no published training does not show.
- **Search preserves the active facet** via a hidden field (a `method="get"` form would otherwise reset it to "Alles"); the facet `tax_query` is added only for a real slug (garbage degrades to the unfiltered listing). Featured strip now also suppressed when a facet is active (unfiltered-first-page only).
- **No new copy, no new deptrac edge** — reuses authored "Alles"; facet names come from the DB; `Training → Content` already covers `Taxonomies::VAARDIGHEID`.
- **Tests:** +5 (queryArgs facet, filterHtml, searchHtml ±hidden, toHtml integration); the 11.1 `toHtml`/`queryArgs` calls updated for the new signature. Suite 734→738.

### File List

- `wp-content/plugins/ink-core/src/Training/Hub.php` (MOD — vaardigheid facet: VAARDIGHEID_VAR, queryArgs tax_query, render, vaardigheidTerms, filterHtml, searchHtml hidden field, toHtml $facets param)
- `tests/Unit/Training/HubTest.php` (MOD — +5 facet tests; updated existing calls)
- `_bmad-output/implementation-artifacts/11-2-vaardigheid-taxonomy-faceted-search.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 11.2 status)
