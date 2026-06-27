# Story 12.5: Gradering-based competition pools

Status: review

## Story

As a skrywer,
I want to compete within my Gradering,
so that judging is fair. (FR-49, UJ-4)

## Acceptance Criteria

**Given** entries
**When** judged
**Then** pools are Brons vs Brons, Silwer vs Silwer, Goud vs Goud; placements (1st–3rd) announced per Gradering (tier governs pools — THE conflation rule).

Decomposed:

1. A `Challenges\Pools` helper groups a round's entries into per-Gradering pools using the **entry-time** Gradering snapshot (`Challenges\Entry::GRADERING_META_KEY`, Story 12.4) — never the writer's live grade, so a post-entry promotion can't move an entry between pools.
2. `competingTiers()` is the canonical pool set Brons / Silwer / Goud — derived from `Kernel\Tier` (the non-manual-only grades); Meester (manual-only, terminal) does not form a monthly competition pool.
3. `group()` is pure: given `[{id, gradering}]` it buckets entries by their gradering value (entries with an empty/unknown snapshot are excluded from the competition pools).
4. `forRound()` queries the round's entries and returns the per-pool entry-id map for the adjudication surfaces (12.6 placements, 12A ingestion).
5. **THE conflation rule:** pools are governed by Gradering (`Kernel\Tier`) ONLY — zero `Ink\Entitlement`. A paid/free lidmaatskap never affects the pool.

## Tasks / Subtasks

- [x] Task 1: `Challenges\Pools` — `competingTiers()` (Brons/Silwer/Goud via `Tier::cases()` minus manual-only), pure `group(array $entries)`, `poolLabel(Tier)` (Terms), thin `forRound(int $uitdaging_id)` querying the round entries + their entry-time gradering snapshot.
- [x] Task 2: Module wiring is not needed (Pools is a read helper consumed by 12.6/12A, not hook-registered) — documented.
- [x] Task 3: Tests — `tests/Unit/Challenges/PoolsTest.php` (competingTiers excludes Meester; group buckets multiple per pool; empty/unknown excluded; poolLabel).
- [x] Task 4: Gates — test/cs/stan/deptrac green; no new deptrac edge (Pools reads `Kernel\Tier` + `Ink\Content` + the same-module entry meta key; no Entitlement).

## Dev Notes

- The pool key is the entry-time snapshot from 12.4 (`Challenges\Entry::GRADERING_META_KEY` = `ink_entry_gradering`), read off each entry — NOT `Tiers\Api::forUser` (which is the live grade). This is what makes judging fair + stable. [Source: src/Challenges/Entry.php]
- Competing tiers = `Tier::cases()` filtered by `! isManualOnly()` → Brons/Silwer/Goud (Meester is manual-only/terminal). [Source: src/Kernel/Tier.php:88]
- Round entries are the published readable bydraes carrying the round term — reuse the `SinglePage::entriesQueryArgs()` shape (round `tax_query` via `ChallengeRound::slugFor`). [Source: src/Challenges/SinglePage.php]
- Conflation: pools come from Gradering only. No `Ink\Entitlement` reference anywhere in Pools.

### Project Structure Notes

- New: `src/Challenges/Pools.php`, `tests/Unit/Challenges/PoolsTest.php`.
- No new deptrac edge (Challenges already → Content, Tiers, Kernel; Pools needs only Kernel\Tier + Content + the same-module meta key).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12.5]
- [Source: docs/afrikaans-terms.md] lines 68-72, 127-128

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- `Challenges\Pools`: `competingTiers()` (Brons/Silwer/Goud via `Tier::cases()` minus manual-only Meester), pure `group()` (buckets by the entry-time snapshot; empty/junk/Meester excluded; pool order = grade order), `poolLabel()` (Terms), thin `forRound()` reusing `SinglePage::entriesQueryArgs` + reading `Entry::GRADERING_META_KEY`.
- Pools use the entry-time snapshot (12.4), never the live grade, so a post-entry promotion can't move an entry between pools. Conflation-clean: Gradering only (`Kernel\Tier`), zero `Ink\Entitlement`. No new deptrac edge.
- **Gates:** `composer test` → 795 passed / 2 skipped (+4); `composer cs` → 0 errors; `composer stan` → No errors; `composer deptrac` → 3 pre-existing only.

### File List

- `wp-content/plugins/ink-core/src/Challenges/Pools.php` (new)
- `tests/Unit/Challenges/PoolsTest.php` (new)

### Change Log

- 2026-06-27: Story 12.5 implemented — Gradering-based competition pools (entry-time snapshot grouping). Status → review.
