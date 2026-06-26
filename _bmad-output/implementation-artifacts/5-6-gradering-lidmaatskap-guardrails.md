---
baseline_commit: 3c0429c4ea7f63c3386577226b19e9953c34a8c2
---

# Story 5.6: Gradering ≠ lidmaatskap guardrails

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

> **Build-order note:** developed LAST in Epic 5 — it codifies THE conflation rule across everything Epic 5 built (5.1–5.10) plus the Epic-4 entitlement layer. The codebase is already conflation-clean by construction (verified per story); this story locks it down with explicit guardrail tests so a future regression fails CI.

## Story

As an ink-core developer,
I want code-level guardrails separating Gradering and lidmaatskap,
so that the conflation rule cannot be violated. (FR-13)

## Acceptance Criteria

1. **There is no write path between `ink_writer_tier` and membership state in either direction, `Ink\Tiers` never reads `Ink\Entitlement` (and vice-versa), and unit tests assert that a membership-state transition leaves Gradering unchanged.** Given the data layer, when any membership-state change occurs, then no membership-state code writes `ink_writer_tier` (the sole writer of tier *changes* is `Ink\Tiers\Api::promote()`; the only OTHER sanctioned tier write anywhere is `Ink\Accounts\Registration` materialising the `brons` DEFAULT at `user_register` — Story 3.3 — which cannot route through `promote()` since it no-ops a `brons → brons` set; the membership/`Entitlement` layer writes neither), no Gradering change writes membership state, `Ink\Tiers` carries zero `Ink\Entitlement` reference and `Ink\Entitlement` carries zero `Ink\Tiers` reference, and the Deptrac `Entitlement ⟂ Tiers` rule (AD-8) passes. Unit tests assert each membership-state transition (active / expired / cancelled / paused) leaves the writer's `ink_writer_tier` untouched. _[Source: epics.md#Story-5.6 AC; architecture.md AD-1 / lines 81-83 / 654-659 (THE conflation rule — `Ink\Entitlement` and `Ink\Tiers` must not reference each other), AD-8 (Deptrac enforces it); deptrac.yaml; src/Tiers/Api.php (`promote()` the sole tier writer); src/Entitlement/PurchaseActivation.php (the membership-state-change handler)]_

2. **The guardrails are codified as runnable tests (structural + behavioural), not just documentation.** Given the conflation rule, when this story is built, then a structural test asserts the `Ink\Tiers` source tree references no `Ink\Entitlement` symbol and the `Ink\Entitlement` source tree references neither `Ink\Tiers` nor the tier meta keys (`ink_writer_tier` / `ink_tier_*`); and a behavioural test drives the membership-state transitions through the real `Ink\Entitlement` handler(s) and asserts no `update_user_meta` write to `ink_writer_tier`. These run in `composer test:unit` alongside the existing Deptrac gate. _[Source: project-context.md (THE conflation rule; "Test your own seams"; **testing rule 2026-06-22**); architecture.md AD-8; src/Entitlement/PurchaseActivation.php (`onMembershipStatusChanged`); tests/Unit/Entitlement/SubmissionGateTest.php (the precedent that `get_user_meta` is never called on the entitlement path)]_

3. **WP-house-rules + authored AND PASSING tests; no production regression.** Given the project rules, when this story is built, then the guardrail tests are authored at `tests/Unit/` and **run with `composer test:unit`; the full suite passes** (baseline 342 passed / 1 skipped — zero regressions). If the codebase is already clean (expected), this story adds tests only — no production change. `composer cs`/`stan`/`deptrac` run and recorded; Deptrac green with `Entitlement ⟂ Tiers` intact. _[Source: project-context.md (testing rule, conflation rule); architecture.md AD-8; deptrac.yaml]_

## Tasks / Subtasks

> **Current state (read before starting):**
> - **Deptrac already enforces `Entitlement ⟂ Tiers`** — `deptrac.yaml` lists `Tiers: [Kernel, Notifications]` and `Entitlement: [Kernel, Notifications]`; neither lists the other. Confirm it still passes; do NOT weaken it.
> - **The sole `ink_writer_tier` writer is `Ink\Tiers\Api::promote()`** (5.2); the registrar is `Ink\Content\UserMeta` (registration, not a write). `Ink\Entitlement` has no tier write and no `Ink\Tiers` reference (verified across Epic 4 + Epic 5).
> - **The membership-state-change entry point is `Ink\Entitlement\PurchaseActivation::onMembershipStatusChanged()`** (+ `LifecycleEmails`); these send email / schedule, they do NOT write user meta. `SubmissionGateTest` already shows the entitlement path never calls `get_user_meta` for tier.
> - **Tests run from the repo root**; `ABSPATH` is defined by `tests/bootstrap.php` as the repo root, so the `Ink\Tiers` / `Ink\Entitlement` source dirs are reachable for a structural scan.
>
> **Scope is GUARDRAILS only (tests + verification).** Do NOT: weaken deptrac, refactor production code (the layers are already clean — if a scan finds a violation, that is a real bug to fix and flag, but none is expected), or add new features. Author the structural + behavioural conflation tests.

- [x] **Task 1 — Structural conflation guardrail test (AC: 1, 2)**
  - [x] `tests/Unit/Tiers/ConflationGuardrailTest.php`: scans `src/Tiers/*.php` (no `Entitlement` reference) + `src/Entitlement/*.php` (no `Tiers` / `ink_writer_tier` / `ink_tier_` reference). Scans **code only** — comments/docblocks stripped via `token_get_all` (so the docblocks that legitimately describe the rule, e.g. "Ink\Tiers ⟂ Ink\Entitlement", are not false positives).
  - [x] Asserts the ONLY production writer carrying the tier-meta key (`update_user_meta` + `WIN_COUNT_META_KEY`) lives under `src/Tiers`.
- [x] **Task 2 — Behavioural "transition leaves Gradering unchanged" test (AC: 1, 2)**
  - [x] Drives `PurchaseActivation::onMembershipStatusChanged()` across `''→active`, `expired→active`, `active→expired`, `active→cancelled`, `active→paused` (capturing all `update_user_meta` keys) and asserts none is `ink_writer_tier` / `ink_tier_promoted_at` / `ink_tier_win_count`.
- [x] **Task 3 — Verify the gates; record (AC: 3)**
  - [x] `composer test:unit` → **346 passed / 1 skipped** (1516 assertions), zero regressions. `composer stan` clean. `composer deptrac` → 3 pre-existing `Activation → PostTypes` only; **no `Entitlement ↔ Tiers` edge — AD-8 holds**. `composer cs` n/a (tests excluded; no src change). NO production change — the codebase was already conflation-clean, now locked by tests.

## Dev Notes

- **Why a test on top of Deptrac:** Deptrac catches a *symbol* dependency at CI; the structural test ALSO catches a stringly-typed leak (e.g. an `Ink\Entitlement` file hardcoding `'ink_writer_tier'` and writing it via `update_user_meta` without importing a Tiers class — which Deptrac would miss). Together they close both the typed and the stringly-typed write path.
- **Behavioural assertion** proves the live transition handler leaves Gradering alone — the AC's "each membership-state transition leaves Gradering unchanged" — not just that the symbol isn't referenced.
- **Expected: tests-only.** Every Epic-4/Epic-5 story was built conflation-clean and Deptrac has stayed green throughout, so this story should add no production code. If a scan fails, that is a genuine latent bug — fix it and note it in the completion notes.
- **Tier writers** (corrected in the 2026-06-26 code review): the sole writer of tier *changes* is `Tiers\Api::promote()`; the ONE other sanctioned writer is `Accounts\Registration`, which sets the `brons` DEFAULT at `user_register` (Story 3.3 — it cannot go through `promote()`, which no-ops `brons → brons`). `Content\UserMeta` only *registers* the meta (with the `MANAGE_TIERS` gate) — registration is not a write path — and the membership/`Entitlement` layer touches neither. The guardrail test (Task 1) allows exactly `{Tiers, Accounts\Registration}` and fails any other module that writes a tier meta key.

### Project Structure Notes

- NEW: `tests/Unit/Tiers/ConflationGuardrailTest.php`. No production file changes expected.

### References

- [Source: epics.md#Story-5.6]
- [Source: architecture.md AD-1, AD-8, lines 81-83, 654-659]
- [Source: deptrac.yaml; src/Tiers/Api.php (promote sole writer); src/Entitlement/PurchaseActivation.php; tests/Unit/Entitlement/SubmissionGateTest.php]
- [Source: project-context.md (THE conflation rule, testing rule)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop)

### Debug Log References

- `composer test:unit` → 346 passed / 1 skipped (1516 assertions).
- `composer stan` → No errors (sandbox-off).
- `composer deptrac` → 3 pre-existing `Activation → PostTypes`; no `Entitlement ↔ Tiers` edge (AD-8 holds).
- First test run flagged the docblock mentions of the rule → switched the structural scan to code-only via `token_get_all` (comments stripped).

### Completion Notes List

- **Guardrails are now runnable tests, not just Deptrac + prose.** Structural scan (code-only, comments stripped) proves `Ink\Tiers` references no `Ink\Entitlement` and `Ink\Entitlement` references neither `Ink\Tiers` nor the tier meta keys; the "sole writer" scan proves only `src/Tiers` writes the tier meta; the behavioural test proves every membership-state transition leaves the writer's Gradering untouched.
- **No production change** — every Epic-4/Epic-5 story was built conflation-clean and Deptrac stayed green throughout, so the scans pass as-authored. The tests lock it: a future regression (typed OR stringly-typed) now fails CI.
- **Why on top of Deptrac:** Deptrac catches a typed symbol dependency; the structural test ALSO catches a stringly-typed leak (an entitlement file hardcoding `'ink_writer_tier'`), and the behavioural test asserts the live transition handler's effect — together closing both the symbol and the write-path vectors.
- **Closes Epic 5.** All ten stories (5.1–5.10) are now `review`; FR-13 (the conflation rule) is code-enforced.

### File List

- `tests/Unit/Tiers/ConflationGuardrailTest.php` (NEW — structural + behavioural conflation guardrails)

### Change Log

- 2026-06-26 — Story 5.6 implemented (create-story → dev-story; final Epic-5 story). Conflation-rule guardrail tests (structural code-only scan + behavioural transition test); no production change (codebase already clean). 346 passed / 1 skipped; stan clean; deptrac `Entitlement ⟂ Tiers` holds. Status → review.

## Review Findings (code review 2026-06-26, Group D)

_3-layer adversarial review — all three layers independently converged. The PRIMARY rule (Tiers ⟂ Entitlement / FR-13) is genuinely enforced and well-tested: the Entitlement structural scan (code-only, comments stripped) is clean, the behavioural transition test drives the real `PurchaseActivation` handler, and deptrac holds. The findings are all in the SECONDARY "sole writer" guardrail (overclaims + under-enforces) and general test-robustness._

- [x] [Review][Decision→Patch] **APPLIED 2026-06-26** (test 3 rewritten as an all-module code-only scan for `update_user_meta` + a tier-key marker, allowing only `{Tiers, Accounts/Registration}` and asserting both are found; AC-1 + Dev-Notes corrected to acknowledge the Registration default-setter) — The "sole writer" guardrail does not enforce its stated invariant, and the invariant as written is false — Test 3 (`the only ink_writer_tier writer is in Ink\Tiers`) gates on `update_user_meta` **AND** `WIN_COUNT_META_KEY` co-occurring, which only ever matches `Tiers\Api::promote()`. Meanwhile `Accounts\Registration::applyDefaults()` (`src/Accounts/Registration.php:95`, hooked on `user_register`) writes `update_user_meta( $user_id, UserMeta::WRITER_TIER, Tier::Brons->value )` — a real second writer of `ink_writer_tier` — that the test silently misses (it has no `WIN_COUNT_META_KEY`). So AC-1's "no code writes `ink_writer_tier` (the sole writer is `Api::promote()`)" is **factually inaccurate**. The Registration write is **legitimate** (the brons default at registration, Story 3.3; it cannot route through `promote()`, which no-ops `brons→brons`) and is **not** a conflation violation (zero `Ink\Entitlement` reference). Decide how to reconcile: **(a)** strengthen the test to the accurate invariant — scan code-only for `update_user_meta` + a tier grade-key marker (`ink_writer_tier` / `Tier::META_KEY` / `WRITER_TIER` / `PROMOTED_AT_META_KEY` / `WIN_COUNT_META_KEY`), allow only `{Tiers, the sanctioned Accounts\Registration default-setter}`, fail any other module, and assert ≥1 writer was found — AND correct AC-1/Dev-Notes prose to acknowledge Registration; **(b)** narrow the claim to "promote() is the sole writer of tier *changes*" + document Registration; **(c)** doc-only: correct the 5.6 prose, leave the test. [`ConflationGuardrailTest.php:100-113`, `Accounts/Registration.php:95`, spec AC-1]
- [x] [Review][Patch] **APPLIED 2026-06-26** (sole-writer test now asserts `Tiers/Api.php` + `Accounts/Registration.php` are found; behavioural test asserts `get_userdata` was invoked, proving the active-transition handler body executed before the negative tier-write assertions) — Harden the test against vacuous passes — (1) the sole-writer test never checks `$modules`/inner glob for emptiness and runs its `expect` only inside the match `if`, so a path/constant regression yields a green test with zero assertions; (2) the behavioural test's `not->toContain` passes trivially if the handler short-circuits before any `update_user_meta` (empty `$written_keys`). Fix: assert the sole-writer scan found ≥1 writer; assert the behavioural handler actually wrote ≥1 meta key (proving the path executed) before the negative assertions. [`ConflationGuardrailTest.php:100-113, 131-161`]
- [x] [Review][Defer] Behavioural test covers only `PurchaseActivation`, not `LifecycleEmails` [`ConflationGuardrailTest.php:131`] — deferred: `LifecycleEmails` (the second handler on `wc_memberships_user_membership_status_changed`) has zero `update_user_meta` today (verified — no live leak); the claim is just broader than the drive. Add a transition drive through `LifecycleEmails` when convenient.
- [x] [Review][Defer] Structural/sole-writer globs are single-level + skip top-level `src/*.php` [`ConflationGuardrailTest.php:78,93,101,104`] — deferred, latent: `Tiers`/`Entitlement` are flat dirs today and no tier write lives in `src/functions.php`/`src/autoload.php`; the scan silently degrades the moment a nested subdir is introduced. Recurse (or assert the flat assumption) when a module grows subdirectories.
- [x] [Review][Defer] Substring `toContain('Tiers')`/`toContain('Entitlement')` retains string literals [`ConflationGuardrailTest.php:74,89-91`] — deferred, brittle-not-broken: a future user-facing `__()` label containing "Tiers"/"Entitlement" would false-FAIL the scan (a safe failure, not false confidence). Tighten to a namespaced/symbol match if it ever bites.
