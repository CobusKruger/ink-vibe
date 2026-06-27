---
baseline_commit: bfa8008
---

# Story 10.2: Date / archive browsing (deferred)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a reader,
I want date/archive browsing,
so that I can navigate by time. (FL 10.2, §9.4 gap)

## Acceptance Criteria

**Given** the deferred-gap status
**When** scoped later
**Then** date/archive browsing is implemented (non-blocking).

1. Date/archive browsing of the Biblioteek is **deferred, non-blocking** (epics.md §9.4 gap) — it is **not built** in Epic 10. This story ships **no code**.
2. The deferral is **recorded** in `deferred-work.md` as a by-design, non-blocking scoping decision tied to the broader biblioteek-organisation analysis (§9.4), to be picked up post-launch.
3. The substrate is **ready when scoped**: the Story 10.1 `Ink\Library\Archive` mirrors `Ink\Discovery\WorksArchive`, whose `dateClause()`/`dateBrowseHtml()` (year/month `date_query` + a pill-per-year control) is the proven pattern to port onto `biblioteek_item` when this is built.

## Tasks / Subtasks

- [x] Task 1: Record the deferral (AC: #1, #2)
  - [x] Add an Epic-10 "Deferred" section to `deferred-work.md` capturing date/archive browse as a non-blocking §9.4-gap scoping decision, with the WorksArchive date-browse pattern named as the port-from source.
- [x] Task 2: Confirm no build (AC: #1)
  - [x] No `ink-core`/theme code change; the Story 10.1 archive ships newest-first + genre filter + search + prev/next only. Date-browse is intentionally absent.

## Dev Notes

- **Deferred by design, not a gap to close now** [Source: epics.md#Story 10.2 (FL 10.2, §9.4 gap)]: the broader biblioteek-organisation analysis (§9.4) is itself deferred; date/archive browse depends on those organisational decisions, so it is correctly held until then. Recording it (not building it) is the BMAD-faithful outcome — the `9.8`/`9.10` deferral-story precedent.
- **The port-from source exists** [Source: wp-content/plugins/ink-core/src/Discovery/WorksArchive.php `dateClause()`/`dateBrowseHtml()`/`YEAR_VAR`/`MONTH_VAR`]: when scoped, add `year`/`month` query vars + a `date_query` to `Ink\Library\Archive::queryArgs()` and a year-pill control to `toHtml()`, mirroring the Ontdek archive. No new module/architecture needed.
- **No copy, no UI, no test** — this story ships no user-facing surface, so there is nothing to translate and nothing new to guard (unlike 9.8, there is no off-switch to keep off; this is an un-built feature, not a feature-to-suppress).

### Project Structure Notes

- MOD `_bmad-output/implementation-artifacts/deferred-work.md` (record the deferral).
- No `ink-core` / theme source change.
- Note (don't build): the year/month `date_query` + year-pill browse on `biblioteek_item` — picked up with the §9.4 organisational analysis post-launch.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 10.2 (FL 10.2, §9.4 gap)]
- [Source: wp-content/plugins/ink-core/src/Discovery/WorksArchive.php (date-browse pattern to port)]
- [Source: _bmad-output/implementation-artifacts/9-8-private-messaging-deferred.md (deferral-record story precedent)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 10)

### Debug Log References

- No `ink-core`/theme source changed — documentation-only deferral record.

### Completion Notes List

- **Recorded deferral, nothing to build** (the 9.8 shape minus the guardrail, since there is no off-switch to keep off): date/archive browse for the Biblioteek is held with the §9.4 organisational analysis (non-blocking, by-design). The port-from source — `WorksArchive`'s date-browse — is named so the future build is a straightforward extension of the 10.1 archive.

### File List

- `_bmad-output/implementation-artifacts/deferred-work.md` (MOD — Epic-10 deferred section)
- `_bmad-output/implementation-artifacts/10-2-date-archive-browsing-deferred.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 10.2 status)
