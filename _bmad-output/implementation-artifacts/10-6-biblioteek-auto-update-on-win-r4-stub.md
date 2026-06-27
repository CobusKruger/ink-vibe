---
baseline_commit: bfa8008
---

# Story 10.6: Biblioteek auto-update on win (R4 — stub)

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want a reserved auto-update hook for wins,
so that R2 ingestion can call it later without rework. (R4)

## Acceptance Criteria

**Given** R2 ingestion (12A.3)
**When** winners are committed
**Then** a Biblioteek update **hook** exists and is invoked (P0 stub)
**And** the hook **body is deferred** with the broader biblioteek organisation analysis (§9.4).

1. A reserved Biblioteek auto-update **action hook** exists as a single-source constant in `ink-core` (`Ink\Library\AutoUpdate::HOOK`), following the INK `ink/…` event-surface convention (the `ink/tier_promoted` precedent).
2. A stable **invocation seam** exists for R2 ingestion (Story 12A.3) to call when winners are committed — `Ink\Library\Api::notifyWinnerCommitted( int $uitdaging_id, array $winner_post_ids )` → fires the hook. So 12A.3 can wire to it later **without rework** (R4); the facade is the AD-1 cross-module surface (12A → Library).
3. The hook is **invoked** by the seam (proven by test: calling the seam fires `do_action( HOOK, … )` with the winner payload), and is **fail-safe** — a non-positive `uitdaging_id` does not fire.
4. The hook **body is deferred**: at P0 the registered listener (`AutoUpdate::onWinnerCommitted`) is a **documented no-op** — the actual biblioteek_item create/update logic is held with the §9.4 biblioteek-organisation analysis. No biblioteek content is written at P0.
5. Conflation rule holds: zero `Ink\Tiers` / `Ink\Entitlement` — the seam carries challenge/post ids only; whether a piece becomes a library item is editorial/organisational (§9.4), never a tier/entitlement gate. `Library` depends only on `Kernel` + `Content`.

## Tasks / Subtasks

- [x] Task 1: Reserved hook + deferred-body listener (AC: #1, #3, #4)
  - [x] `wp-content/plugins/ink-core/src/Library/AutoUpdate.php`: `const HOOK = 'ink/biblioteek_wen_bywerking'`; `register()` wires `add_action(self::HOOK, [$this,'onWinnerCommitted'], 10, 2)`; `onWinnerCommitted()` documented **no-op** (deferred §9.4); `triggerForWinner()` fires `do_action(self::HOOK, …)` for a positive id only (fail-safe). `do_action` carries the INK `ink/…` hook-name `phpcs:ignore`.
  - [x] Wired `( new AutoUpdate() )->register()` into `Library\Module::register()`.
- [x] Task 2: Library facade for the cross-module seam (AC: #2)
  - [x] `wp-content/plugins/ink-core/src/Library/Api.php` (NEW — Library's first facade): `winnerCommittedHook()` + `notifyWinnerCommitted()` delegating to `AutoUpdate`. The surface Story 12A.3 calls.
- [x] Task 3: Tests (AC: all)
  - [x] `tests/Unit/Library/AutoUpdateTest.php` — 5 tests (HOOK constant; `register` adds the action; `triggerForWinner` fires once with payload + never for non-positive; `onWinnerCommitted` callable no-op).
  - [x] `tests/Unit/Library/ApiTest.php` — 3 tests (hook constant exposed; delegates/fires once; fail-safe skip).
- [x] Task 4: Gates (AC: all)
  - [x] `composer test:unit` 712 passed / 1 skipped (+8); `composer stan` OK; `composer cs` clean; `composer copy:scan` no new debt (event seam, no copy); `composer deptrac` 3 PRE-EXISTING, 0 new (`Library → Kernel,Content` holds; the future `12A → Library` edge is declared when 12A is built).

## Dev Notes

- **Mirror the Tiers event seam** [Source: wp-content/plugins/ink-core/src/Tiers/Api.php:148 `do_action('ink/tier_promoted', …)`; PromotionEmails.php:48 `const HOOK` + :62 `add_action`]: the `ink/…` hook-name convention carries a `phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- INK ink/... event-surface convention (AD)` on the `do_action` line. Test the fire with `Brain\Monkey\Actions\expectDone(HOOK)->once()->with(…)` and the wiring with `Actions\expectAdded(HOOK)`.
- **Stub = hook + invoker, body deferred** [Source: epics.md#Story 10.6 (R4)]: R4 reserves the hook so 12A.3 ingestion can commit winners → biblioteek without rework. At P0 the *invocation seam* exists and fires; the *listener body* (create/update the biblioteek_item from the winning post) is deferred with the §9.4 organisation analysis (how the library is curated/organised). The no-op listener is the documented landing spot for that future logic.
- **Why a facade** [Source: project-context AD-1 facade discipline; Content\Api/Tiers\Api precedent]: Story 12A.3 lives in a different module (Challenges/12A), so it must reach Library through `Library\Api` (the sole public cross-module surface), not `AutoUpdate` internals. Providing `Api::notifyWinnerCommitted` now is exactly "callable later without rework" (R4).
- **Conflation rule** [Source: deptrac.yaml; THE conflation rule]: the seam passes a `uitdaging_id` + winning `post_id`s only — no tier, no entitlement. Becoming a library item is an editorial/organisational outcome (§9.4), never gated on writer tier or subscription.
- **No copy** — an event seam has no user-facing string; copy:scan unaffected.

### Project Structure Notes

- NEW: `wp-content/plugins/ink-core/src/Library/AutoUpdate.php`, `wp-content/plugins/ink-core/src/Library/Api.php`.
- MOD: `wp-content/plugins/ink-core/src/Library/Module.php` (register AutoUpdate).
- NEW tests: `tests/Unit/Library/AutoUpdateTest.php`, `tests/Unit/Library/ApiTest.php`.
- **Expected deptrac edges (pre-flagged):** none new — `AutoUpdate`/`Api` use only Kernel/WP + their own module. The `12A → Library` edge is declared when Story 12A.3 wires the call.
- Note (don't build): the listener body (biblioteek_item create/update from a winning post); the curation/organisation rules — deferred to the §9.4 analysis. No new CPT/meta.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 10.6 (R4)]
- [Source: wp-content/plugins/ink-core/src/Tiers/Api.php:148 + Tiers/PromotionEmails.php:48,62 (the do_action/HOOK/add_action event-seam precedent)]
- [Source: wp-content/plugins/ink-core/src/Content/Api.php (module-facade precedent)]
- [Source: deptrac.yaml (Library layer; conflation rule)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 10)

### Debug Log References

- `composer stan` run with the sandbox disabled (PHPStan binds a local TCP analysis socket → EPERM under the command sandbox); result OK.

### Completion Notes List

- **Reserved hook stub, body deferred (R4)**: `Ink\Library\AutoUpdate::HOOK = 'ink/biblioteek_wen_bywerking'` is the single-source action. `triggerForWinner()` is the invocation seam (fires `do_action` for a positive uitdaging id; fail-safe no-fire otherwise); `register()` wires the listener `onWinnerCommitted()`, a documented **no-op** — the future biblioteek_item create/update logic lands there with the §9.4 organisation analysis. End-to-end seam exists today; nothing is written at P0.
- **`Ink\Library\Api` facade** (the module's first): `winnerCommittedHook()` + `notifyWinnerCommitted()` are the AD-1 cross-module surface Story 12A.3 (R2 ingestion, a different module) calls — "callable later without rework."
- **Conflation-clean:** the payload is challenge/post ids only; zero Tiers/Entitlement. No new deptrac edge (the future `12A → Library` edge is declared when 12A wires the call).
- **Tests:** +8 (5 AutoUpdate + 3 Api), mirroring the Tiers `Actions\expectDone`/`expectAdded` event-seam test idiom. Suite 704→712, zero regressions. No copy (event seam) → copy:scan unaffected.

### File List

- `wp-content/plugins/ink-core/src/Library/AutoUpdate.php` (NEW — reserved `ink/biblioteek_wen_bywerking` hook stub)
- `wp-content/plugins/ink-core/src/Library/Api.php` (NEW — Library module facade)
- `wp-content/plugins/ink-core/src/Library/Module.php` (MOD — register AutoUpdate)
- `tests/Unit/Library/AutoUpdateTest.php` (NEW)
- `tests/Unit/Library/ApiTest.php` (NEW)
- `_bmad-output/implementation-artifacts/10-6-biblioteek-auto-update-on-win-r4-stub.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 10.6 status)
