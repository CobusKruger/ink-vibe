---
baseline_commit: 63fd7d87371d998b8f7211c35d298b731a55f18d
---

# Story 12A.1: EntryID data model (R1 linchpin)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want a per-type EntryID on entries assigned at collation,
so that pasted judges' results can be matched back to the right entries. (FR-50-R1, R1)

## Acceptance Criteria

**Given** the authoritative entry record (`ink_entries` — see Dev Notes: this is the bydrae **post + per-entry meta**, AD-5, not a separate custom table)
**When** the entry model is extended
**Then** an `entry_type` + `entry_number` pair exists on each entry, numbered **per type** (Gedigte / Stories / Artikels separately, each from 1), **assigned at collation time** (not at entry time)
**And** this lands **before** R2 (12A.2/12A.3 depend on it)
**And** any Afrikaans UI label is human-written only if/when surfaced (the EntryID label composes from the existing `Terms` type noun + the number — no new contested copy).

Decomposed:

1. A `Challenges\EntryId` helper stores the per-type number (`ink_entry_number`) and a type snapshot (`ink_entry_type`) on the authoritative entry (the bydrae post), mirroring the `ink_entry_gradering` (12.4) and `ink_entry_placement` (12.6) meta-on-post pattern. **No new custom table** — see Dev Notes.
2. `assign()` is the write primitive collation (12A.2) calls. It is **idempotent / first-assignment-wins**: an already-numbered entry is never renumbered and its number is never burned (the 12A.2 "re-collation must not renumber" invariant lives here, where it is unit-testable).
3. The numbering of a whole round (sort + sequence-per-type from 1) is **12A.2's** job; 12A.1 provides the per-entry data model + the single-entry assign primitive + the canonical EntryID string.
4. `format()` (pure) composes the canonical EntryID string judges see and paste back: `"{type label} {number}"` (e.g. `"Gedig 1"`). `entryIdFor()` resolves it for a stored entry via the `Terms` type label.
5. Conflation-clean: the entry number hangs off the entry post — zero `Ink\Tiers` / `Ink\Entitlement`.

## Tasks / Subtasks

- [x] Task 1: `Challenges\EntryId` value/helper (AC: 1,2,4,5)
  - [x] `TYPE_META_KEY = 'ink_entry_type'`, `NUMBER_META_KEY = 'ink_entry_number'` constants
  - [x] pure `format(string $typeLabel, int $number): string` — `"{label} {n}"`, `''` when number ≤ 0 or label empty
  - [x] `numberFor(int $entry_id): int` (Scalar::asNonNegativeInt over the meta), `typeFor(int $entry_id): string` (Scalar::asString)
  - [x] `isAssigned(int $entry_id): bool` (number > 0)
  - [x] `assign(int $entry_id, string $type, int $number): bool` — idempotent first-wins; guards entry_id > 0, type ≠ '', number > 0; never overwrites an existing number
  - [x] `entryIdFor(int $entry_id): string` — impure compose via `Terms::label(typeFor)` + `numberFor`
- [x] Task 2: Tests — `tests/Unit/Challenges/EntryIdTest.php` (AC: 1,2,4)
  - [x] format pure cases (composed string; empty for non-positive number / empty label)
  - [x] assign writes BOTH meta when unassigned; returns true
  - [x] assign is idempotent — does NOT overwrite when already assigned (no renumber / no burn); returns false (non-vacuous: prove it WOULD write when unassigned)
  - [x] assign rejects junk (entry_id ≤ 0, empty type, number ≤ 0) without writing
  - [x] numberFor / typeFor / isAssigned reads; entryIdFor composes via Terms label
- [x] Task 3: `EntryId` is a pure/static helper (like `Placements`) — **no** `Module::register()` change needed (no hooks). Bootstrap untouched.
- [x] Task 4: Gates — `composer test:unit`, `cs`, `stan`, `deptrac`, `copy:scan` all green; **no new deptrac edge** (Challenges → Kernel already allowed; `Ink\I18n` is an uncovered layer; no Content/Tiers/Entitlement reference).

## Dev Notes

- **The `ink_entries` "table" is the bydrae post + per-entry post-meta (AD-5), not a separate custom table.** Epic 12 built the authoritative entry record this way: `Entry::GRADERING_META_KEY = 'ink_entry_gradering'` (12.4) and `Placements::PLACEMENT_META_KEY = 'ink_entry_placement'` (12.6) both `update_post_meta()` on the entry post; entries are queried as published bydraes carrying the round term (`SinglePage::entriesQueryArgs`). The epics.md "ink_entries table / columns" phrasing is planning shorthand. 12A.1 follows the established pattern: `ink_entry_number` + `ink_entry_type` as post-meta. This avoids a brand-new custom table + a migration of existing entries, and stays conflation-clean. [Source: src/Challenges/Entry.php:56; src/Challenges/Placements.php:44; epics.md#Story-12A.1]
- **Internal meta — no `register_post_meta`.** `ink_entry_gradering` / `ink_entry_placement` are written via bare `update_post_meta()` (internal, not REST-exposed, not user-editable). `ink_entry_number` / `ink_entry_type` follow suit. [Source: src/Challenges/Entry.php:92; src/Challenges/Placements.php:107]
- **Idempotency lives in the write.** R12's migration lesson: idempotency belongs in the write (get-or-create / first-wins on a stable key), not only in a downstream flag. `assign()` is first-assignment-wins so re-collation can never renumber or burn an EntryID — exactly the 12A.2 AC-6 invariant, made unit-testable at the data layer. [Source: epics.md#12A.2 admin-flow AC-6; epic-12-retro §3]
- **EntryID string format:** `"{type label} {number}"` (e.g. `"Gedig 1"`, `"Storie 2"`). The type label is the existing `Terms` singular noun (`gedig`/`storie`/`artikel`), so no new copy. The CPT slug == the `Terms` key, so `Terms::label($typeSlug)` resolves. 12A.3's parser matches these back. [Source: src/I18n/Terms.php:136-141]
- **House style:** static methods on a `final` class, pure helpers + thin impure accessors, `Scalar::asNonNegativeInt` / `Scalar::asString` guards over read meta — mirror `Placements`. [Source: src/Challenges/Placements.php]
- **Testing rules (both newly codified, applied here):** test the OUTCOME (assert the meta WE write + the string WE compose, never a mock-of-a-mock); guardrail non-vacuity (the idempotency test must first prove `assign` WOULD write when unassigned, so the "does not overwrite" assertion can actually fail). Brain-Monkey isolation: `Functions\when('__')->returnArg(1)` in `beforeEach`, `Monkey\setUp/tearDown`. [Source: project-context.md Testing Rules]

### Project Structure Notes

- New: `wp-content/plugins/ink-core/src/Challenges/EntryId.php`, `tests/Unit/Challenges/EntryIdTest.php`.
- No bootstrap / `Module::register()` change (pure/static helper, no hooks).
- No new deptrac edge. No new Afrikaans copy (the label composes from existing `Terms` nouns + a number).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12A.1]
- [Source: _bmad-output/planning-artifacts/epics.md#12A.2 admin-flow AC (EntryID assignment deferred to collation)]
- [Source: src/Challenges/Placements.php; src/Challenges/Entry.php; src/I18n/Terms.php]
- [Source: _bmad-output/implementation-artifacts/epic-12-retro-2026-06-27.md §3, §6 (idempotency-in-the-write; readiness flags)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- Initial test run failed 2/9 with Patchwork `DefinedTooEarly` on `did_action()` — it is a real bootstrap-defined function (not a Brain-Monkey mock target). Removed the `Functions\when('did_action')` lines; `Terms::label()` runs the real `did_action` (the `_doing_it_wrong` spy is a no-op in unit tests, exactly as in `PlacementsTest`). 9/9 green after.

### Completion Notes List

- `Challenges\EntryId` is the per-type EntryID data model (R1 linchpin): `ink_entry_number` + `ink_entry_type` post-meta on the authoritative entry post (AD-5), mirroring the `ink_entry_gradering` (12.4) / `ink_entry_placement` (12.6) meta-on-post pattern. **No `ink_entries` custom table** — the epics.md "table/columns" phrasing is planning shorthand; entries are bydrae posts. Decision documented in Dev Notes.
- `assign()` is the idempotent first-assignment-wins write primitive 12A.2 collation calls — an already-numbered entry is never renumbered/burned (the 12A.2 AC-6 invariant, made unit-testable at the data layer per R12's idempotency-in-the-write lesson). The round-wide sort + per-type sequence is 12A.2's job.
- Pure `format()` composes the canonical EntryID string `"{type label} {number}"` (e.g. "Gedig 1") from the existing `Terms` type noun — no new contested copy. `entryIdFor()` resolves it for a stored entry.
- Static helper, no hooks → bootstrap untouched. Conflation-clean (zero Tiers/Entitlement).
- Gates: `composer test:unit` 1133 passed / 1 skipped (+9); `cs` 0 errors (2 pre-existing warnings in untouched Engagement files); `stan` OK; `deptrac` 3 pre-existing Kernel→Content only (no new edge — `Ink\I18n` is an uncovered layer); `copy:scan` clean (no new placeholder debt).

### File List

- `wp-content/plugins/ink-core/src/Challenges/EntryId.php` (new)
- `tests/Unit/Challenges/EntryIdTest.php` (new)

### Change Log

- 2026-06-29 — Story 12A.1 implemented: per-type EntryID data model (`ink_entry_number`/`ink_entry_type` meta + idempotent `assign` + canonical `format`/`entryIdFor`). 9 unit tests. Suite 1124→1133.
