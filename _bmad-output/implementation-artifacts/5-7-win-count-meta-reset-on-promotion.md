---
baseline_commit: 08f2a8a54c0be8534295e50f1a1438381cc28270
---

# Story 5.7: Win-count meta + reset-on-promotion

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

> **Build-order note:** developed AFTER 5.1/5.3/5.2 and BEFORE 5.8 (the auto engine). This story adds the `ink_tier_win_count` counter, the accumulator, and the reset inside the existing `Api::promote()` — the engine (5.8) then drives accumulation from challenge results and applies the 5/15 thresholds.

## Story

As an ink-core developer,
I want a win-count meta that resets on promotion,
so that the auto-promotion engine has a counter. (FR-11, R3)

## Acceptance Criteria

1. **`ink_tier_win_count` is a registered, staff-gated integer user-meta that accumulates top-3 wins and is reset to 0 by `Tiers::promote()` on every promotion.** Given the `ink_tier_win_count` meta, when a writer earns top-3 placements, then the count accumulates toward the next Gradering (via a typed accumulator), and `Ink\Tiers\Api::promote()` resets it to 0 on **every** successful promotion (manual or automatic). The meta registers with `default => 0`, integer type, `absint` sanitisation, REST-aware, and the same `MANAGE_TIERS` `auth_callback` gate as the other tier meta — a member never self-writes it. _[Source: epics.md#Story-5.7 AC; architecture.md line 370 (`ink_tier_win_count` holds top-3 wins toward the next gradering and is **reset to 0 by the `Tiers::promote()` path** (R3)), lines 275-281 (the win-counting lives in `Ink\Tiers`); src/Content/UserMeta.php (the 2.3 registrar — `ink_tier_win_count` was explicitly deferred to 5.7); afrikaans-terms.md line 74 (`wins / top-3-uitslag` = `ink_tier_win_count`)]_

2. **The counter has a typed read + a typed accumulator on `Ink\Tiers\Api`, conflation-clean, and the meta-key single source is Kernel-owned.** Given the counter, when code reads or accumulates it, then `Api::winCountForUser( int $user_id ): int` returns the current count (default 0 for unset/junk — never raw meta semantics leaking out) and `Api::recordWin( int $user_id, int $count = 1 ): int` adds to it and returns the new total. The `ink_tier_win_count` key is a Kernel-owned single-source constant (`Tier::WIN_COUNT_META_KEY`) that BOTH `Ink\Content\UserMeta` (registrar) and `Ink\Tiers\Api` (reader/writer) reference — no `Tiers → Content` edge (mirrors the 5.1 `Tier::META_KEY` pattern). All of it references only the Kernel + WordPress — zero `Ink\Entitlement` (THE conflation rule). _[Source: architecture.md lines 275-281 ("The win-counting / threshold engine lives entirely in `Ink\Tiers`"), AD-1; src/Kernel/Tier.php (5.1 — `META_KEY`/`PROMOTED_AT_META_KEY` Kernel single-source pattern), src/Tiers/Api.php (5.1 `forUser()`, 5.2 `promote()`); deptrac.yaml (`Tiers: [Kernel]`)]_

3. **WP-house-rules + conflation-clean + authored AND PASSING Pest tests.** Given the project rules, when this story is built, then: touched `.php` keep strict types / namespace / ABSPATH guard / PascalCase classes / camelCase methods; the meta key is an `ink_`-prefixed Kernel single-source constant; no raw superglobals/SQL. Pest unit tests are authored/updated at `tests/Unit/` and **run with `composer test:unit`; the full suite passes before done** (baseline 305 passed / 1 skipped — zero regressions; the existing `UserMetaTest` is UPDATED to include the third key, which it explicitly anticipated). `composer cs`/`stan`/`deptrac` run and recorded; deptrac green, no new `Tiers` edge. _[Source: project-context.md (strict types, prefix, single-source, no raw superglobals, **testing rule 2026-06-22**, conflation rule); architecture.md AD-8; tests/Unit/Content/UserMetaTest.php (the assertions to update — they assert `ink_tier_win_count` is "Story 5.7")]_

## Tasks / Subtasks

> **Current state (read before starting):**
> - **`Ink\Content\UserMeta` (2.3)** registers `ink_writer_tier` + `ink_tier_promoted_at` and EXPLICITLY defers `ink_tier_win_count` to "Story 5.7" (`keys()` docblock + the test). `UserMetaTest` asserts the count is absent — UPDATE those assertions here (anticipated).
> - **`Ink\Kernel\Tier` (5.1)** holds `META_KEY`/`PROMOTED_AT_META_KEY` Kernel single-source constants + `default()`/`isManualOnly()`/`isAutoPromotable()`. Add `WIN_COUNT_META_KEY` alongside them.
> - **`Ink\Tiers\Api` (5.1/5.2)** has `forUser()` + the sole `promote()` write path. Add `winCountForUser()` + `recordWin()`, and add the reset INSIDE `promote()` (one line — `update_user_meta( ..., WIN_COUNT_META_KEY, 0 )` on a successful change).
> - **Deptrac `Tiers: [Kernel]` only** — the win-count key MUST be Kernel-owned so `Api` can read/write it without a Content edge.
>
> **Scope is the COUNTER ONLY.** Do NOT build: the threshold engine / the 5/15 logic / the "when does a win get recorded from a challenge result" wiring (all Story 5.8), the "wins needed" subtext (5.9), or any challenge-result integration (Epic 12). This story provides the meta + read + accumulate + reset; 5.8 consumes them.

- [x] **Task 1 — Kernel single-source key (AC: 2)**
  - [x] Added `Tier::WIN_COUNT_META_KEY = 'ink_tier_win_count'` to the Kernel `Tier` enum.
- [x] **Task 2 — Register the meta (AC: 1)**
  - [x] `UserMeta::WIN_COUNT = Tier::WIN_COUNT_META_KEY` added, appended to `keys()`, and `register_meta`'d (integer, `default => 0`, `absint`, REST-aware, `MANAGE_TIERS` gate). `keys()` docblock updated.
- [x] **Task 3 — Read + accumulate + reset (AC: 1, 2)**
  - [x] `Api::winCountForUser()` (0 for unset/non-scalar, else int) + `Api::recordWin( $id, $count = 1 )` (accumulates by `max(0,$count)`, persists, returns new total).
  - [x] `Api::promote()` now resets `ink_tier_win_count` to 0 after the meta writes on a successful change (after the no-op early return). Docblock updated.
- [x] **Task 4 — Author AND run the Pest tests; record the gates (AC: 3)**
  - [x] UPDATED `tests/Unit/Content/UserMetaTest.php` (three keys; `WIN_COUNT` constant; win_count integer/default 0; per-key type assertions).
  - [x] NEW `tests/Unit/Tiers/WinCountTest.php` (winCountForUser 0/int; recordWin +1/+N; non-positive never decreases). Extended `PromoteTest` (the three change tests assert the `update_user_meta(..., 'ink_tier_win_count', 0)` reset; the no-op test's `update_user_meta->never()` covers no-reset-on-no-op).
  - [x] `composer test:unit` → **310 passed / 1 skipped** (1389 assertions), zero regressions. `composer cs` (3 files) clean. `composer stan` clean (sandbox-off). `composer deptrac` → 3 pre-existing `Activation → PostTypes` only, no new `Tiers` edge.

## Dev Notes

- **Kernel-owned key (same rationale as 5.1):** `Tiers\Api` must read/write the counter, and deptrac forbids a `Tiers → Content` edge, so the key lives on the Kernel `Tier` enum; `UserMeta::WIN_COUNT` aliases it. The registrar stays in Content (consistent with the other two tier meta).
- **Reset placement:** the reset belongs inside `promote()` (the sole write path), AFTER the no-op early-return, so an unchanged-grade call resets nothing. Every real promotion — manual correction, manual bevordering, or the 5.8 auto engine — clears the counter so accumulation restarts at the new grade.
- **`recordWin` is the accumulator only.** It does NOT check thresholds or trigger promotion — that is the 5.8 engine's job (it calls `recordWin`, then compares against `Tier::isAutoPromotable()` + the 5/15 thresholds, then calls `promote`). Keep this method dumb.
- **Conflation rule:** counter read/write references only Kernel `Tier` + WordPress; zero `Ink\Entitlement`. A win counter is a competition concept, never entitlement.

### Project Structure Notes

- UPDATE: `src/Kernel/Tier.php` (WIN_COUNT_META_KEY), `src/Content/UserMeta.php` (register + keys), `src/Tiers/Api.php` (winCountForUser/recordWin + reset in promote). Tests: UPDATE `tests/Unit/Content/UserMetaTest.php` + `tests/Unit/Tiers/PromoteTest.php`, NEW `tests/Unit/Tiers/WinCountTest.php`.
- No new file, no schema/table change, no new hook.

### References

- [Source: epics.md#Story-5.7]
- [Source: architecture.md lines 275-281, 370; AD-1, AD-8]
- [Source: src/Kernel/Tier.php, src/Content/UserMeta.php, src/Tiers/Api.php]
- [Source: deptrac.yaml; afrikaans-terms.md line 74; project-context.md (single-source, conflation rule, testing rule)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop)

### Debug Log References

- `composer test:unit` → 310 passed / 1 skipped (1389 assertions). (Fixed the pre-existing UserMetaTest "type === 'string'" loop, which assumed all tier meta were strings; win_count is integer.)
- `composer cs` (Tier.php, UserMeta.php, Api.php) → clean.
- `composer stan` → No errors (sandbox-off).
- `composer deptrac` → 3 pre-existing `Activation → PostTypes`; no new `Tiers` edge.

### Completion Notes List

- **The counter the 5.8 engine needs.** `ink_tier_win_count` registered (integer, default 0, `MANAGE_TIERS`-gated), with a Kernel-owned key (`Tier::WIN_COUNT_META_KEY`) so `Ink\Tiers\Api` reads/writes it without a `Tiers → Content` edge (same pattern as 5.1).
- **`recordWin()` is a dumb accumulator** — it adds wins and returns the new total but checks no thresholds and triggers no promotion; that is Story 5.8 (which calls `recordWin`, compares `Tier::isAutoPromotable()` + 5/15, then `promote`).
- **Reset lives in the sole write path.** `promote()` resets the counter to 0 on every successful promotion (after the no-op early return), so a no-op grade set resets nothing while every real promotion — manual or auto — restarts accumulation at the new grade.
- **Closed the 2.3 win-count deferral** (the meta was explicitly left to 5.7). `UserMetaTest` updated as it anticipated.
- **Conflation-clean:** counter read/write references only Kernel `Tier` + WordPress; zero `Ink\Entitlement`.

### File List

- `wp-content/plugins/ink-core/src/Kernel/Tier.php` (UPDATE — `WIN_COUNT_META_KEY`)
- `wp-content/plugins/ink-core/src/Content/UserMeta.php` (UPDATE — register `ink_tier_win_count` + `WIN_COUNT` const + keys)
- `wp-content/plugins/ink-core/src/Tiers/Api.php` (UPDATE — `winCountForUser()`, `recordWin()`, reset in `promote()`)
- `tests/Unit/Content/UserMetaTest.php` (UPDATE — three keys + win_count type)
- `tests/Unit/Tiers/WinCountTest.php` (NEW)
- `tests/Unit/Tiers/PromoteTest.php` (UPDATE — win-count reset assertions)

### Change Log

- 2026-06-26 — Story 5.7 implemented (create-story → dev-story, before 5.8). `ink_tier_win_count` meta (Kernel-owned key) + `winCountForUser()`/`recordWin()` accumulator + reset-on-promotion inside `Api::promote()`. 310 passed / 1 skipped; cs/stan clean; deptrac no new edge. Status → review.
