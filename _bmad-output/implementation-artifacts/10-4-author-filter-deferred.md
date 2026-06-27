---
baseline_commit: bfa8008
---

# Story 10.4: Author filter (deferred)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a reader,
I want to filter the library by author,
so that I can find a writer's library items. (FL 10.4, §9.4 gap)

## Acceptance Criteria

**Given** the deferred-gap status
**When** scoped later
**Then** an author filter is implemented (non-blocking).

1. An author filter on the Biblioteek is **deferred, non-blocking** (epics.md §9.4 gap) — **not built** in Epic 10. This story ships **no code**.
2. The deferral is **recorded** in `deferred-work.md` as a by-design, non-blocking scoping decision tied to the §9.4 organisation analysis.
3. The substrate is **ready when scoped**: `Ink\Library\Archive::queryArgs()` can take an `author`/`author_name` arg (WP_Query native), and the pill-control idiom (`filterHtml()`) ports directly — the build is an incremental extension of the 10.1 archive.

## Tasks / Subtasks

- [x] Task 1: Record the deferral (AC: #1, #2)
  - [x] Add the author-filter row to the Epic-10 "Deferred" section in `deferred-work.md`, naming the `queryArgs()` `author` arg + `filterHtml()` pill idiom as the extension points.
- [x] Task 2: Confirm no build (AC: #1)
  - [x] No `ink-core`/theme change; the 10.1 archive filters by genre + keyword only. Author filter is intentionally absent.

## Dev Notes

- **Deferred by design** [Source: epics.md#Story 10.4 (FL 10.4, §9.4 gap)]: held with the §9.4 biblioteek-organisation analysis (which informs how authorship surfaces in the library), non-blocking. Recording (not building) is the BMAD-faithful outcome — the `9.8`/`9.10` precedent.
- **The extension points exist** [Source: wp-content/plugins/ink-core/src/Library/Archive.php]: `queryArgs()` would add a WP_Query `author`/`author_name` arg behind a defensive request read; `filterHtml()`/`pill()` is the active-marked control idiom to reuse. No new module/architecture needed.
- **Avoid per-item manual linking** [Source: project-context Principle 8]: an author filter must derive from the post's native `post_author` (automatic), never an editorial per-item author tag — consistent with the shared-taxonomy/auto-surfacing discipline.
- **No copy, no UI, no test** — no new user-facing surface; nothing to translate, nothing to guard.

### Project Structure Notes

- MOD `_bmad-output/implementation-artifacts/deferred-work.md` (record the deferral).
- No `ink-core` / theme source change.
- Note (don't build): the `author`/`author_name` query arg + author-pill control on `biblioteek_item` — picked up with the §9.4 analysis post-launch.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 10.4 (FL 10.4, §9.4 gap)]
- [Source: wp-content/plugins/ink-core/src/Library/Archive.php (queryArgs/filterHtml extension points)]
- [Source: _bmad-output/implementation-artifacts/9-8-private-messaging-deferred.md (deferral-record story precedent)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 10)

### Debug Log References

- No `ink-core`/theme source changed — documentation-only deferral record.

### Completion Notes List

- **Recorded deferral, nothing to build**: author filter held with the §9.4 organisation analysis (non-blocking, by-design). Extension points named (`queryArgs()` `author` arg + `filterHtml()` pill idiom) so the future build is incremental and avoids per-item manual linking (derive from native `post_author`).

### File List

- `_bmad-output/implementation-artifacts/deferred-work.md` (MOD — Epic-10 deferred section)
- `_bmad-output/implementation-artifacts/10-4-author-filter-deferred.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 10.4 status)
