# Story 12.6: Structured placement records

Status: review

## Story

As an ink-core developer,
I want queryable placement records per tier,
so that ingestion and auto-promotion have authoritative data. (FR-50)

## Acceptance Criteria

**Given** results
**When** placements are recorded
**Then** 1st/2nd/3rd per Gradering per round are stored (not only the single winner), distinguishing **algehele wenner** (1st) from **wenner** (2nd/3rd)
**And** they feed R2 ingestion (12A.3), R3 auto-promotion (5.8), and the SM-8 metric.

Decomposed:

1. A `Challenges\Placements` store records a placement **rank (1/2/3)** on the authoritative entry record (AD-5) as `ink_entry_placement` post meta — so 1st, 2nd AND 3rd are stored per pool, not only the single winner.
2. The pool is the entry's entry-time Gradering snapshot (Story 12.4/12.5), so placements are per-Gradering-per-round automatically.
3. **algehele wenner vs wenner:** rank 1 = algehele wenner; ranks 2–3 = wenner — pure `isAlgeheleWenner()` + `placementLabel()` (glossary terms, line 127-128).
4. `record()`/`clear()` are the write API R2 ingestion (12A.3) calls; `forRound()` is the queryable read (pool → rank → entry id) that 5.8 auto-promotion and the SM-8 metric consume.
5. Conflation-clean: placements hang off the entry + its Gradering pool — zero `Ink\Entitlement`.

## Tasks / Subtasks

- [x] Task 1: `Challenges\Placements` — `PLACEMENT_META_KEY`, `RANK_FIRST/SECOND/THIRD`, `MAX_RANK`; pure `isValidRank()`, `isAlgeheleWenner()`, `placementLabel()`, `arrange(array $placed)`; impure `record(int $entry_id, int $rank): bool`, `clear(int $entry_id)`, `placementFor(int $entry_id): int`, `forRound(int $uitdaging_id)`.
- [x] Task 2: Terminology — add `algehele_wenner` ("algehele wenner") to `Terms::map()` (reuse existing `wenner`).
- [x] Task 3: Tests — `tests/Unit/Challenges/PlacementsTest.php` (isValidRank bounds; isAlgeheleWenner; placementLabel 1 vs 2/3; record validation writes meta / rejects junk; arrange groups pool→rank sorted, ignores unplaced).
- [x] Task 4: Gates — test/cs/stan/deptrac green; no new deptrac edge (reuses Pools/SinglePage + Kernel + Content + Terms).

## Dev Notes

- Placement attaches to the entry (AD-5 authoritative record) as `ink_entry_placement` meta; the pool comes from `Entry::GRADERING_META_KEY`. Reuse `Pools::forRound()` to get pool→entry-ids, then read each entry's placement. [Source: src/Challenges/Pools.php; src/Challenges/Entry.php]
- Rank semantics: 1 = algehele wenner ("[Maand] algehele wenner"); 2–3 = wenner ("[Maand] wenner"). [Source: docs/afrikaans-terms.md:127-128]
- This is the authoritative placement data 12A.3 ingestion WRITES and 5.8 auto-promotion (a "win" = a top-3 placement at current grade) + the SM-8 metric READ. The win→promotion wiring itself is 12A.3 — 12.6 provides the queryable store. [Source: epics.md#Story 12.6]
- A "win" for 5.8 = any top-3 placement (algehele wenner or wenner) — see consolidated spec §162. The placement rank 1-3 is exactly that signal.

### Project Structure Notes

- New: `src/Challenges/Placements.php`, `tests/Unit/Challenges/PlacementsTest.php`.
- Modified: `src/I18n/Terms.php` (algehele_wenner key).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12.6]
- [Source: docs/specs/ink-consolidated-spec.md] §162 (a win = any top-3 placement)

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- `Challenges\Placements` stores rank 1/2/3 on the authoritative entry (`ink_entry_placement`) so all of 1st/2nd/3rd per pool are recorded, not only the winner. rank 1 = algehele wenner, 2-3 = wenner (pure isAlgeheleWenner/placementLabel). record()/clear()/placementFor() = the 12A.3 write API; arrange() (pure) + forRound() (reuses Pools::forRound) = the queryable read for 5.8/SM-8.
- Conflation-clean: placements hang off the entry + Gradering pool, zero Entitlement. No new deptrac edge.
- Gates: composer test → 801 passed/2 skipped (+6); cs 0 errors; stan clean; deptrac 3 pre-existing only.

### File List

- `wp-content/plugins/ink-core/src/Challenges/Placements.php` (new)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (modified — algehele_wenner key)
- `tests/Unit/Challenges/PlacementsTest.php` (new)

### Change Log
