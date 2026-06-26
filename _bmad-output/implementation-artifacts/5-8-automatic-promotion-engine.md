---
baseline_commit: fbf68590cdd41ae2e1ba173154b39a4668628199
---

# Story 5.8: Automatic promotion engine

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

> **Build-order note:** developed AFTER 5.1/5.3/5.2/5.7. Uses `Tier::isAutoPromotable()` (5.1), `Api::recordWin()`/`winCountForUser()` (5.7) and `Api::promote()` (5.2). The R2 ingestion (Story 12A.3, Epic 12A) that CALLS the engine is NOT built — this story provides the engine entry point 12A.3 will invoke.

## Story

As a skrywer,
I want automatic promotion on challenge wins,
so that my Gradering advances without staff busywork. (FR-12a, R3, UJ-4)

## Acceptance Criteria

1. **A win = any top-3 placement at the writer's current Gradering (each counts); the engine promotes Brons→Silwer at 5 and Silwer→Goud at 15; Goud and Meester have no auto-threshold.** Given placement records (from R2 ingestion, Story 12A.3), when the engine processes a writer's top-3 wins at their current Gradering, then each win accumulates (`Api::recordWin()`, 5.7), and on reaching **5 Brons wins** the writer is promoted Brons→Silwer, on **15 Silwer wins** Silwer→Goud. **Goud has no auto-threshold** (terminal for auto) and **Meester is manual-only** — a win for a Goud/Meester writer never auto-promotes. The win count resets to 0 on the promotion (the 5.7 reset inside `promote()`). Multiple wins in one award are honoured (e.g. crossing the threshold in one call promotes once). _[Source: epics.md#Story-5.8 AC ("a win = any top-3 placement, any entry type, at the writer's current Gradering (multiple each count); Brons→Silwer at 5, Silwer→Goud at 15; Goud/Meester have no auto-threshold"); architecture.md lines 275-281 (the win-counting/threshold engine lives entirely in `Ink\Tiers`; 5/15; Goud/Meester terminal); src/Kernel/Tier.php (5.1 `isAutoPromotable()`), src/Tiers/Api.php (5.7 `recordWin`/`winCountForUser`, 5.2 `promote`)]_

2. **The engine lives in `Ink\Tiers`, runs the threshold check + the write via the sole `promote()` path with `actor_id = 0` (system), and never reads `Ink\Entitlement`.** Given the three-layer rule, when the engine runs, then it lives in `Ink\Tiers` (a `PromotionEngine` collaborator, exposed via the `Api::awardWins()` facade — the surface a future Challenges/12A.3 step calls); it accumulates via `recordWin()`, reads the current grade via `forUser()`, applies the 5/15 thresholds (the single-source threshold map), and on crossing a threshold calls `Api::promote( $user_id, $next, 0, <Afrikaans system reason>, $challenge_id )` — `actor_id = 0` marks the automatic engine in the `graderingsgeskiedenis` log (Story 5.3) and triggers the `ink/tier_promoted` event (the 5.10 email seam). The engine references only the Kernel `Tier` + this module — **zero `Ink\Entitlement`** (THE conflation rule; Gradering advancement is competition-driven, never entitlement-driven — a lapsed-membership Goud writer is unaffected). _[Source: architecture.md lines 269-283 (`Tiers::promote()` the sole write path; "Challenges still never touches `ink_writer_tier` or the threshold logic"; the engine in `Ink\Tiers`), AD-1 (conflation), line 483 (the `ink/...` event surface); src/Tiers/Api.php (`promote()` actor 0 = system); deptrac.yaml (`Tiers: [Kernel]`)]_

3. **WP-house-rules + Afrikaans system reason + conflation-clean + authored AND PASSING Pest tests.** Given the project rules, when this story is built, then: the new `.php` keeps strict types / namespace / ABSPATH guard / PascalCase / camelCase; the thresholds are a single-source const map (no scattered literals); the auto-promotion log reason is an Afrikaans `__()` literal using the approved glossary term (**bevordering** — afrikaans-terms.md line 73), never AI-translated; no raw superglobals/SQL. Pest unit tests are authored at `tests/Unit/Tiers/` and **run with `composer test:unit`; the full suite passes before done** (baseline 310 passed / 1 skipped — zero regressions). `composer cs`/`stan`/`deptrac` run and recorded; deptrac green, no new `Tiers` edge. _[Source: project-context.md (strict types, single-source, no AI Afrikaans/glossary-first, no raw superglobals, **testing rule 2026-06-22**, conflation rule); architecture.md AD-8; afrikaans-terms.md line 73 (bevorder); deptrac.yaml]_

## Tasks / Subtasks

> **Current state (read before starting):**
> - **`Api::recordWin()` (5.7)** accumulates wins and returns the new total; **`Api::winCountForUser()` (5.7)** reads it; **`Api::promote()` (5.2/5.7)** is the sole write path (writes grade + promoted_at, resets win_count to 0, logs with the given actor/challenge, fires `ink/tier_promoted`). **`Tier::isAutoPromotable()` (5.1)** is true for Brons/Silwer, false for Goud/Meester. Reuse ALL of these; do NOT re-derive.
> - **The R2 ingestion that calls the engine (Story 12A.3) is NOT built** (Epic 12A). This story builds the engine entry point only — a synchronous facade method 12A.3 will call. No challenge-result parsing, no `ink_entries` table, no scheduling here.
> - **`promote()` already resets the win count.** The engine does NOT reset separately — it relies on `promote()`'s reset (5.7). After a promotion the counter is 0 at the new grade.
> - **Deptrac `Tiers: [Kernel]` only.** The engine references only Kernel + this module. No new edge.
>
> **Scope is the THRESHOLD ENGINE ONLY.** Do NOT build: the R2 ingestion / challenge-result parsing / `ink_entries` (Epic 12/12A), the congratulation email body (5.10 — only the `ink/tier_promoted` event the engine already fires via promote()), the "wins needed" subtext (5.9), or any UI. Provide the engine + its facade.

- [x] **Task 1 — `Tiers\PromotionEngine` (AC: 1, 2)**
  - [x] New `Ink\Tiers\PromotionEngine` with the single-source `THRESHOLDS` map (Brons→Silwer@5, Silwer→Goud@15).
  - [x] `award( int $user_id, int $wins = 1, int $challenge_id = 0 ): ?Tier` — reads `forUser`, accumulates via `recordWin`, returns null for Goud/Meester (not in map) or below threshold, else `Api::promote( ..., 0, __('Outomatiese bevordering','ink-core'), $challenge_id )` and returns the new grade. One step per call (promote resets the counter).
  - [x] Docblock records the engine, 5/15, Goud/Meester terminal, actor 0 = system, conflation-clean, called by 12A.3 (not built).
- [x] **Task 2 — Facade method (AC: 2)**
  - [x] `Api::awardWins()` delegates to `PromotionEngine::award()`.
- [x] **Task 3 — Author AND run the Pest tests; record the gates (AC: 3)**
  - [x] `tests/Unit/Tiers/PromotionEngineTest.php` (8 tests: Brons→Silwer@5, Brons below 5, Silwer→Goud@15, Silwer below 15, Goud never promotes, multi-win one-call crossing, challenge id carried, Api facade delegate). Per-key `get_user_meta` stub helper.
  - [x] `composer test:unit` → **318 passed / 1 skipped** (1413 assertions), zero regressions. `composer cs` (2 files) clean. `composer stan` clean (sandbox-off). `composer deptrac` → 3 pre-existing `Activation → PostTypes` only, no new `Tiers` edge.

## Dev Notes

- **Engine vs write path:** `promote()` (5.2) is the DIRECT setter (any grade, used by the manual UI and by this engine for the write). `PromotionEngine::award()` is the threshold DECISION layer that decides whether/where to promote, then calls `promote()`. Architecture's "Challenges hands placement records to `Tiers::promote()` which runs the threshold check" is realised as: Challenges/12A.3 → `Api::awardWins()` → `PromotionEngine::award()` (threshold) → `Api::promote()` (write). Challenges never touches `ink_writer_tier` or the thresholds. ✓
- **Single promotion per call:** because `promote()` resets the win count to 0, a single `award()` promotes at most one step even if the accumulated total far exceeds the threshold. This matches the spec (reset on promotion); a writer cannot skip Silwer to land on Goud from Brons wins.
- **Goud/Meester:** a win still accumulates onto the counter (harmless; the 5.9 subtext hides it at Goud/Meester) but never promotes — no threshold entry. This mirrors `Tier::isAutoPromotable()`.
- **Conflation rule:** the engine reads/writes only Kernel `Tier` + this module. A win promotes Gradering irrespective of membership; entitlement is never consulted. Deptrac confirms.
- **Afrikaans reason:** `__( 'Outomatiese bevordering', 'ink-core' )` — Afrikaans gettext source, glossary term "bevordering" (line 73), no AI translation. The log row's from→to + challenge link carry the specifics.

### Project Structure Notes

- New: `src/Tiers/PromotionEngine.php`; test `tests/Unit/Tiers/PromotionEngineTest.php`. UPDATE: `src/Tiers/Api.php` (`awardWins()` facade).
- No hook, no schema, no UI. The engine is a synchronous facade call (AD-6).

### References

- [Source: epics.md#Story-5.8]
- [Source: architecture.md lines 269-283, 483; AD-1, AD-6, AD-8]
- [Source: src/Tiers/Api.php (recordWin/winCountForUser/promote/forUser), src/Kernel/Tier.php (isAutoPromotable)]
- [Source: deptrac.yaml; afrikaans-terms.md line 73 (bevorder); project-context.md (single-source, conflation, no AI Afrikaans, testing rule)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop)

### Debug Log References

- `composer test:unit` → 318 passed / 1 skipped (1413 assertions).
- `composer cs` (PromotionEngine.php, Api.php) → clean.
- `composer stan` → No errors (sandbox-off).
- `composer deptrac` → 3 pre-existing `Activation → PostTypes`; no new `Tiers` edge.

### Completion Notes List

- **The engine = threshold decision layer.** `PromotionEngine::award()` accumulates wins (5.7 `recordWin`), checks the single-source 5/15 threshold map, and on crossing calls the sole `Api::promote()` write path (5.2) with `actor_id = 0`. Architecture's "Challenges hands placement records to Tiers::promote() which runs the threshold check" is realised as Challenges/12A.3 → `Api::awardWins()` → `PromotionEngine::award()` (threshold) → `Api::promote()` (write) — Challenges never touches the field or the thresholds.
- **One step per call:** `promote()` resets the counter, so a single `award()` promotes at most one grade even when the total far exceeds the threshold (matches the reset spec — no Brons→Goud skip).
- **Goud/Meester terminal:** absent from the threshold map (mirrors `Tier::isAutoPromotable()`); a win still accumulates but never auto-promotes.
- **Conflation-clean:** references only Kernel `Tier` + this module's `Api`; zero `Ink\Entitlement` — a lapsed-membership Goud writer is unaffected. Auto-promotion fires the same `ink/tier_promoted` event (the 5.10 seam).
- **Afrikaans system reason** `Outomatiese bevordering` (glossary "bevordering", afrikaans-terms.md line 73), authored as the gettext source — no AI translation.
- **No scope creep:** no R2 ingestion / `ink_entries` (Epic 12A), no email body (5.10), no UI. The engine is a synchronous facade call for 12A.3 to invoke.

### File List

- `wp-content/plugins/ink-core/src/Tiers/PromotionEngine.php` (NEW)
- `wp-content/plugins/ink-core/src/Tiers/Api.php` (UPDATE — `awardWins()` facade)
- `tests/Unit/Tiers/PromotionEngineTest.php` (NEW)

### Change Log

- 2026-06-26 — Story 5.8 implemented (create-story → dev-story). `PromotionEngine` (5/15 thresholds, Goud/Meester terminal, system actor, one-step-per-call) + `Api::awardWins()` facade. 318 passed / 1 skipped; cs/stan clean; deptrac no new edge. Status → review.

## Review Findings (code review 2026-06-26, Group B: 5.2+5.8)

_3-layer adversarial review (Blind Hunter + Edge Case Hunter + Acceptance Auditor). Engine threshold logic verified correct (5/15 boundaries, Goud/Meester terminal, one-step-per-call, conflation-clean). Residual items below._

- [x] [Review][Decision→Patch] **APPLIED 2026-06-26** (`award()` now returns at the terminal-grade check BEFORE `recordWin()`; new test asserts no meta write for a terminal grade) — Terminal-grade (Goud/Meester) win count accumulates unbounded — `award()` calls `recordWin()` BEFORE the terminal-grade early return, so a Goud/Meester writer's `ink_tier_win_count` is incremented and persisted on every award but never reset (only `promote()` resets it, and terminal grades never promote). Dev Notes (5.8) document this as "harmless" (the 5.9 subtext hides it), but Blind + Edge Hunter both flag the silently-growing junk value (a meaningless ever-increasing number any future stats surface would read). Decide: accept-as-documented (no change) vs patch (move `recordWin()` after the terminal check, so wins aren't accumulated for non-auto-promotable grades). [`PromotionEngine.php` award()]
- [x] [Review][Defer] Large-batch `award()` overshoot discards surplus wins [`PromotionEngine.php`] — deferred, spec-intentional: `award($id, 100)` on a Brons writer promotes one step and resets to 0, losing the surplus past the first threshold. "One step per call" is the documented spec intent (no Brons→Goud skip); batch-award semantics (whether a backlog should promote multiple steps) are owned by the not-yet-built R2 ingestion caller (Story 12A.3). Revisit when 12A.3 is designed.
- [x] [Review][Defer] `awardWins()` is not idempotent on `$challenge_id` re-runs [`PromotionEngine.php`] — deferred: there is no dedupe on the challenge link, so a retry/replay of the same challenge result double-counts wins. Idempotency is the caller's (Story 12A.3) concern by spec design (the engine's contract is "caller supplies the win count"). Record as a known seam risk for 12A.3.
