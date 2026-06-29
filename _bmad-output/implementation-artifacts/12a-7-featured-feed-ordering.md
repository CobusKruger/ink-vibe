---
baseline_commit: 3c4099648899ea1bd531091bce515a6315aa8042
---

# Story 12A.7: Featured-feed ordering

Status: done

## Story

As a reader,
I want the overall winner featured most prominently,
so that the top result leads. (FR-50-R2, R2)

## Acceptance Criteria

**Given** committed winners
**When** the featured feed orders
**Then** `algehele wenner` (1st) is placed ahead of ordinary wenners (drives the home featured ordering, 15.6).

Decomposed:

1. 15.6 `FeaturedWinners::order()` already leads with algehele wenner BUT collapses to one entry per rank (a single-spotlight dedup). With the 12A.3 per-(Gradering × category) pools there is legitimately one algehele wenner **per category**, so the FEED must list every winner — algehele wenner(s) first — not collapse them.
2. `FeaturedWinners::orderFeed()` is the feed-ordering contract: orders ALL valid winners by rank (algehele wenner first) then id, deterministically, with NO one-per-rank dedup. `toHtml` renders the feed via `orderFeed()`.
3. `order()` (the deduped single-spotlight) is retained intact (its 15.6 tests stay green) for any spotlight caller.

## Tasks / Subtasks

- [x] Task 1: `FeaturedWinners::orderFeed(array $winners): array` — order all valid winners by (rank asc → id asc); algehele wenner(s) lead; drop no-id / non-placement rows; carry `is_algehele_wenner` + label. No dedup. Pure.
- [x] Task 2: `FeaturedWinners::toHtml()` renders the winners list via `orderFeed()` (the feed) instead of `order()` (the spotlight). `order()` kept for compat (its dedup tests unchanged).
- [x] Task 3: Tests — `orderFeed` puts algehele wenner first; **preserves multiple algehele wenners (one per category pool)** — the distinction from `order()` (non-vacuous: assert `order()` would collapse them, `orderFeed()` does not); deterministic id tiebreak; drops invalid.
- [x] Task 4: Gates — test:unit / cs / stan / deptrac / copy:scan green; existing 15.6 `order()` + `toHtml` tests stay green. No new edge, no new copy.

## Dev Notes

- **Feed vs spotlight:** `order()` collapses to one-per-rank (a single headline spotlight) — correct for a "Desember-wenner" single banner, but it would HIDE the per-category algehele wenners the 12A.3 per-(Gradering × category) pools produce. `orderFeed()` is the feed: all winners, algehele wenner(s) first. This is the 12A.7 ordering contract that "drives the home featured ordering" without losing winners. [Source: src/Challenges/FeaturedWinners.php:96 order(); 12a-3 story pool decision]
- The `toHtml` switch is safe: the 15.6 `toHtml` test uses distinct ranks (identical output under either orderer); the dedup assertions target `order()` directly and are untouched. [Source: tests/Unit/Challenges/FeaturedWinnersTest.php]
- Pure ordering, test the OUTCOME (the order WE produce). Conflation-clean; no new deptrac edge. [Source: project-context.md]

### Project Structure Notes

- Modified: `src/Challenges/FeaturedWinners.php` (add orderFeed, toHtml uses it), `tests/Unit/Challenges/FeaturedWinnersTest.php` (orderFeed tests).
- No new files, no new edge, no new copy.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12A.7]
- [Source: src/Challenges/FeaturedWinners.php (15.6), Placements.php (12.6)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- None.

### Completion Notes List

- `FeaturedWinners::orderFeed()` added — the featured-FEED ordering contract: all valid winners by (rank asc → id asc), algehele wenner(s) first, NO one-per-rank dedup. `toHtml` now renders the feed via `orderFeed()`.
- The distinction from `order()` (the single-spotlight dedup, retained intact for compat): `orderFeed` preserves the per-category algehele wenners the 12A.3 per-(Gradering × category) pools produce — collapsing them (as `order()` does) would hide winners. Non-vacuous test asserts `order()` WOULD collapse two rank-1s while `orderFeed()` keeps both.
- The 15.6 `order()` + `toHtml` tests stayed green (the `toHtml` test uses distinct ranks; the dedup assertions target `order()` directly).
- Conflation-clean; no new file, edge, or copy.
- Gates: `composer test:unit` 1178→1181 (+3), 1 skipped; `cs` 0 errors; `stan` OK; `deptrac` 3 pre-existing only; `copy:scan` clean.

### File List

- `wp-content/plugins/ink-core/src/Challenges/FeaturedWinners.php` (modified — orderFeed + toHtml uses it)
- `tests/Unit/Challenges/FeaturedWinnersTest.php` (modified — orderFeed tests)

### Change Log

- 2026-06-29 — Story 12A.7 implemented: featured-FEED ordering (orderFeed — algehele wenner(s) first, all winners kept) consumed by toHtml; complements the spotlight order(). 3 unit tests. Suite 1178→1181.
