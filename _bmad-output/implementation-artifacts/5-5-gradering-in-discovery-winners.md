---
baseline_commit: a10ddb369d6a592a5c04fff549977baa970d7fc3
---

# Story 5.5: Gradering in discovery & winners

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

> **Build-order note:** the discovery surfaces (Ontdek) are Epic 8 and challenge results/winners are Epic 12 — neither is built. Following the presenter/primitive precedent (5.4/5.9 → 9.4), this story ships the **Tiers-side primitives** (filter-by-Gradering + winner-label) that the Epic-8 discovery filters and the Epic-12 winner surfaces consume.

## Story

As a reader,
I want Gradering used in discovery and winner labels,
so that I can filter and understand competition context. (FR-15)

## Acceptance Criteria

1. **Writers can be filtered/segmented by Gradering, and a winner can be labelled with its Gradering in context (e.g. "Oktober Goud-wenner").** Given discovery filters and challenge results, when they render, then a caller can list writers at a given Gradering (the filter/segmentation primitive), and compose a winner label combining a period + the grade + "wenner" → e.g. "Oktober Goud-wenner". The grade labels + the "wenner" term come from the single-source `Ink\I18n\Terms` registry (glossary-backed; never bare literals). The actual Ontdek filter UI (Epic 8) and the challenge-winner surfaces (Epic 12) consume these primitives. _[Source: epics.md#Story-5.5 AC ("writers can be filtered by Gradering, participation is segmented, and winners are labelled (e.g. 'Oktober Goud-wenner')"); afrikaans-terms.md line 102 (wenner), lines 68-72 (the grade terms); src/Tiers/Api.php (5.1 `forUser`); src/I18n/Terms.php (grade labels)]_

2. **The primitives live in `Ink\Tiers`, read the typed grade, and are conflation-clean.** Given the three-layer rule, when the primitives are produced, then `Ink\Tiers\Api::usersByGrade( Tier $tier, array $args = [] ): array` returns the user IDs at a grade (a `get_users()` meta filter on `ink_writer_tier`, the single-source key, merged with caller args), and `Ink\Tiers\Api::winnerLabel( Tier $grade, string $period ): string` composes the Afrikaans label from the `Terms` registry. The "wenner" concept is added to the `Terms` registry (glossary-backed) as its single source. All of it references only the Kernel `Tier` + `Ink\I18n\Terms` + WordPress — **zero `Ink\Entitlement`** (THE conflation rule: discovery/competition context is grade-driven, never entitlement-driven). _[Source: project-context.md ("Controlled-vocabulary UI labels come from the ink-core terminology registry"; "No business logic in the theme"; conflation rule); src/I18n/Terms.php (registry); src/Kernel/Tier.php (`META_KEY`); deptrac.yaml (`Tiers: [Kernel, Notifications]`)]_

3. **WP-house-rules + Afrikaans + authored AND PASSING tests.** Given the project rules, when this is built, then: ink-core `.php` keep strict types / namespace / guards; the meta key is the single-source `Tier::META_KEY`; the label terms come from `Terms` (glossary-first — "wenner" added to the glossary projection, NOT a bare literal); no raw SQL (use `get_users()`). Pest unit tests are authored at `tests/Unit/Tiers/` (+ the `Terms` "wenner" addition covered by the existing `TermsTest` style) and **run with `composer test:unit`; the full suite passes before done** (baseline 338 passed / 1 skipped — zero regressions). `composer cs`/`stan`/`deptrac` run and recorded; deptrac green, no new `Tiers` edge. _[Source: project-context.md (strict types, single-source labels, glossary-first, no raw SQL, **testing rule 2026-06-22**, conflation); architecture.md AD-8]_

## Tasks / Subtasks

> **Current state (read before starting):**
> - **`Api::forUser()` (5.1)** + **`Tier::META_KEY` (5.1)** + **`Ink\I18n\Terms`** (grade labels). Reuse them.
> - **`wenner` is NOT yet in the `Terms` registry** (it is in afrikaans-terms.md line 102). Add it (glossary-first).
> - **The discovery (Ontdek, Epic 8) + winner (Epic 12) surfaces are NOT built.** Ship the Tiers primitives only; those surfaces consume them later.
> - **Use `get_users()`** (a WP function, mockable) for the by-grade filter — NOT `new WP_User_Query()` (harder to unit-test). No raw SQL.
> - **Deptrac `Tiers: [Kernel, Notifications]`** (after 5.10); `Ink\I18n` is uncovered. No new tracked edge.
>
> **Scope is the Tiers PRIMITIVES only.** Do NOT build: the Ontdek filter UI / archive (Epic 8), the challenge-result/winner records or banners (Epic 12 / 12A), or any discovery template. Ship `usersByGrade()` + `winnerLabel()` + the `wenner` term.

- [x] **Task 1 — `wenner` in the Terms registry (AC: 1, 2)**
  - [x] Added `'wenner' => __( 'wenner', 'ink-core' )` to `Terms::map()` (lowercase per the glossary UI-term — a common noun used mid-phrase, "Oktober Goud-wenner").
- [x] **Task 2 — `Api::usersByGrade()` (AC: 1, 2)**
  - [x] `usersByGrade( Tier $tier, array $args = [] ): array` → `get_users()` meta filter on `Tier::META_KEY` (no raw SQL), caller args merged, IDs cast to int. (Slow-query advisory narrowly suppressed — the grade filter is intentional + paging-scoped.)
- [x] **Task 3 — `Api::winnerLabel()` (AC: 1, 2)**
  - [x] `winnerLabel( Tier $grade, string $period ): string` → "{period} {GradeLabel}-{wenner}" via `Terms` → "Oktober Goud-wenner".
- [x] **Task 4 — Author AND run the Pest tests; record the gates (AC: 3)**
  - [x] `tests/Unit/Tiers/DiscoveryTest.php` (5 tests: get_users meta filter + int IDs, caller-arg merge, winnerLabel composition ×2, `wenner` registered). `TermsTest` stays green (no exact-count assertion).
  - [x] `composer test:unit` → **342 passed / 1 skipped** (1466 assertions), zero regressions. `composer cs` clean (after suppressing the slow-query advisory). `composer stan` clean (sandbox-off). `composer deptrac` → 3 pre-existing only, no new `Tiers` edge.

## Dev Notes

- **Primitives, not surfaces.** The Ontdek filter UI (Epic 8) and the winner banners/records (Epic 12/12A) are not built; 5.5 ships the Gradering-side capability they need — list-by-grade + the grade-in-winner label — exactly as 5.4/5.9 shipped presenters for 9.4.
- **`get_users()` not raw SQL** — the meta filter on `ink_writer_tier` (single-source `Tier::META_KEY`) is the WP-native, testable path.
- **Glossary-first:** "wenner" is added to the `Terms` registry before it appears in the composed label (the standing rule); the grade labels were already registered (5.x).
- **Conflation rule:** filtering/segmenting/labelling by Gradering reads only the grade; never entitlement. A writer's discovery placement + winner label are competition concepts.

### Project Structure Notes

- UPDATE: `src/Tiers/Api.php` (`usersByGrade()`, `winnerLabel()`), `src/I18n/Terms.php` (`wenner`). NEW test `tests/Unit/Tiers/DiscoveryTest.php`. Ontdek/winner surfaces are Epic 8/12.

### References

- [Source: epics.md#Story-5.5; afrikaans-terms.md line 102 (wenner), 68-72 (grades)]
- [Source: src/Tiers/Api.php (forUser), src/Kernel/Tier.php (META_KEY), src/I18n/Terms.php]
- [Source: deptrac.yaml; project-context.md (single-source labels, glossary-first, no raw SQL, conflation, testing rule)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop)

### Debug Log References

- `composer test:unit` → 342 passed / 1 skipped (1466 assertions).
- `composer cs` (Api.php, Terms.php) → clean (slow-query advisory on the grade meta filter narrowly suppressed).
- `composer stan` → No errors (sandbox-off).
- `composer deptrac` → 3 pre-existing `Activation → PostTypes`; no new `Tiers` edge.

### Completion Notes List

- **Tiers primitives, not surfaces.** The Ontdek discovery UI (Epic 8) and the challenge-winner records/banners (Epic 12/12A) aren't built; 5.5 ships the Gradering capability they consume — `usersByGrade()` (filter/segment) + `winnerLabel()` (grade-in-context label).
- **`get_users()` meta filter** on the single-source `Tier::META_KEY` — WP-native, testable, no raw SQL. IDs returned as `int`.
- **Glossary-first `wenner`:** added to the `Terms` registry as the lowercase common-noun UI-term (so "Oktober Goud-wenner" composes correctly); the standalone capitalised form is not needed here.
- **Conflation-clean:** filtering/labelling by Gradering reads only the grade + `Terms`; never entitlement.
- **No scope creep:** no Ontdek template, no winner records/banners, no discovery query beyond the by-grade primitive.

### File List

- `wp-content/plugins/ink-core/src/Tiers/Api.php` (UPDATE — `usersByGrade()`, `winnerLabel()`)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (UPDATE — `wenner` term)
- `tests/Unit/Tiers/DiscoveryTest.php` (NEW)

### Change Log

- 2026-06-26 — Story 5.5 implemented (create-story → dev-story). `usersByGrade()` filter/segmentation primitive + `winnerLabel()` ("Oktober Goud-wenner") + the `wenner` registry term. Ontdek/winner surfaces deferred to Epic 8/12. 342 passed / 1 skipped; cs/stan clean; deptrac no new edge. Status → review.
