---
baseline_commit: 345bfd1
---

# Story 9.8: Private messaging (deferred)

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a product owner,
I want messaging out of launch scope,
so that we ship focused. (FL 9.8, §14.7)

## Acceptance Criteria

**Given** launch scope
**When** BuddyPress is configured
**Then** Messaging is off at launch and revisited later (deferred, non-blocking).

1. BuddyPress **Private Messaging (`messages`) is off at launch** — code-enforced by the Story 9.1 `bp_active_components` scope (`messages` ∈ `BuddyPress::FORCED_OFF`, never in `SCOPED_ON`), so a cloned-DB-active messaging component is forced off regardless.
2. A **standing guardrail** keeps Messaging from creeping back into launch scope: a test asserting `messages` is in `FORCED_OFF` and absent from `SCOPED_ON`, and that `scopeComponents()` strips it even when the input has it active (non-vacuous) — so a future edit that re-adds `messages` to the scope fails the suite. (The 6.9 verification-and-guardrail pattern.)
3. The deferral is **recorded** in `deferred-work.md` as a non-blocking, by-design launch decision (§14.7) to be revisited post-launch — not a gap.
4. **No messaging UI / endpoint is shipped** — there is no INK messaging surface, route, or block. (This story ships no production code; the off-switch already lives in 9.1.)

## Tasks / Subtasks

- [x] Task 1: Standing launch-scope guardrail (AC: #1, #2)
  - [x] `tests/Unit/Social/MessagingDeferredTest.php`: assert `in_array('messages', BuddyPress::FORCED_OFF, true)` and `! in_array('messages', BuddyPress::SCOPED_ON, true)`; assert `scopeComponents(['messages'=>'1', ...])` does NOT contain `messages` (non-vacuous — the input HAS it). A focused, named guard distinct from the general `BuddyPressTest` so the launch-scope decision has an explicit, self-documenting test.
- [x] Task 2: Record the deferral (AC: #3)
  - [x] Add a `Deferred from: Story 9.8` section to `_bmad-output/implementation-artifacts/deferred-work.md` — Private Messaging is out of launch scope (§14.7), the `messages` component is forced off in 9.1, revisit post-launch; non-blocking, by-design.
- [x] Task 3: Gates (AC: all)
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt; `composer deptrac` clean. (No production code changes — only a test + the deferral note.)

## Dev Notes

- **The off-switch already exists** [Source: Story 9.1 `Ink\Social\BuddyPress::FORCED_OFF`]: 9.1 made the scope code-enforced and put `messages` in `FORCED_OFF`. So 9.8 is satisfied by **verification + a standing guardrail + the recorded deferral**, not by new code — exactly the Story 6.9 (remove-legacy-edit-link) shape: nothing to build, a guard to keep it that way.
- **Why a dedicated test** [Source: Story 6.9 standing-guardrail precedent]: `BuddyPressTest` already covers `scopeComponents` strips `FORCED_OFF` generically; the dedicated `MessagingDeferredTest` names the *launch-scope decision* so a future contributor who tries to enable messaging gets a clear, self-documenting failure pointing at §14.7 — not just a generic scope assertion. Non-vacuous (the input carries `messages`).
- **Deferred, not dropped** [Source: epics.md#Story 9.8, §14.7]: messaging is revisited post-launch; record it in `deferred-work.md` so it is a tracked decision. When it is picked up, it will be a BuddyPress `messages`-component re-enable (or an INK alternative) — out of scope now.
- **No copy, no UI**: this story ships no user-facing surface, so there is no Afrikaans copy and no theme change.

### Project Structure Notes

- NEW tests: `tests/Unit/Social/MessagingDeferredTest.php`.
- MOD `_bmad-output/implementation-artifacts/deferred-work.md` (record the deferral).
- No `ink-core` / theme source change (the off-switch is the 9.1 scope).
- deptrac / copy:scan: unchanged.
- Note (don't build): any messaging UI, route, table, or block; the post-launch messaging feature itself.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 9.8 (FL 9.8, §14.7)]
- [Source: wp-content/plugins/ink-core/src/Social/BuddyPress.php (FORCED_OFF includes messages — Story 9.1)]
- [Source: _bmad-output/implementation-artifacts/6-9-remove-legacy-edit-link-filter.md (verification-and-guardrail story precedent)]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-9 (messaging off at launch)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 9)

### Debug Log References

- No `ink-core`/theme source changed — gates re-run for rigor: stan OK, deptrac 3 pre-existing, copy:scan no new debt.

### Completion Notes List

- **Verification + standing guardrail + recorded deferral** (the 6.9 shape — nothing to build): Private Messaging's off-switch is the Story 9.1 scope (`messages` ∈ `BuddyPress::FORCED_OFF`). `MessagingDeferredTest` is a named launch-scope guard — it fails the suite if `messages` is ever moved to `SCOPED_ON`, and asserts `scopeComponents` strips a cloned-DB-active `messages` (non-vacuous). A future contributor enabling messaging hits a self-documenting failure pointing at §14.7.
- Deferral recorded in `deferred-work.md` (by-design, non-blocking, revisit post-launch). No INK messaging UI/route/table/block shipped.
- Tests 649→651 (+2); cs 0 errors; stan OK; copy:scan no new debt; deptrac 3 pre-existing (0 new).

### File List

- `tests/Unit/Social/MessagingDeferredTest.php` (NEW — standing launch-scope guardrail)
- `_bmad-output/implementation-artifacts/deferred-work.md` (MOD — record the messaging deferral)
- `_bmad-output/implementation-artifacts/9-8-private-messaging-deferred.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 9.8 status)
