---
baseline_commit: 0b8ed0201509a5e81b6056ddde9a62b1f380fea2
---

# Story 5.9: My Profiel "wins needed" subtext

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

> **Build-order note:** uses the 5.7 win counter + the 5.8 thresholds + the 5.4 badge area. The real My Profiel template is Story 9.4; this ships the reusable subtext (ink-core presenter + theme bridge) that 9.4 places.

## Story

As a skrywer,
I want to see how many wins I need for the next Gradering,
so that I understand my progression. (FR-14, R3)

## Acceptance Criteria

1. **The private My Profiel shows a "wins needed" subtext for a Brons/Silwer writer (using `_n()` plurals), and the subtext is hidden at Goud/Meester.** Given the private My Profiel, when it renders for a Brons or Silwer writer, then it shows e.g. "4 top 3 uitslae nodig om Silwer te bereik" (1 → "1 top 3 uitslag nodig om Silwer te bereik" via `_n()`), computed as `threshold − current win count` toward the next grade; for a Goud or Meester writer (no auto-threshold) the subtext is **hidden** (the presenter returns null / the bridge renders nothing). The phrasing is the glossary-approved subtext (afrikaans-terms.md line 74). _[Source: epics.md#Story-5.9 AC; afrikaans-terms.md line 74 ("Subteks op My Profiel: bv. '4 top 3 uitslae nodig om Silwer te bereik'", `ink_tier_win_count`), line 73 (5/15 thresholds, Meester manual); src/Tiers/PromotionEngine.php (5.8 thresholds), src/Tiers/Api.php (5.7 `winCountForUser`)]_

2. **The computation + the `_n()` Afrikaans sentence live in ink-core (single-source thresholds, text-domain'd copy); the theme bridge is thin.** Given the three-layer rule, when the subtext is produced, then `Ink\Tiers\PromotionEngine::progressFor( Tier $current, int $count ): ?array` returns `['needed' => int, 'next' => Tier]` (or null for a terminal grade — single source with the 5.8 thresholds), and `Ink\Tiers\Api::winsNeededSubtext( int $user_id ): ?string` composes the `_n()` sentence with the next-grade `Terms` label (or null when hidden); a `function_exists`-guarded theme bridge `ink_foundation_gradering_wins_needed()` echoes it. The copy is Afrikaans as the gettext source (glossary-approved phrasing — NOT AI-translated). Conflation-clean: zero `Ink\Entitlement`. _[Source: project-context.md ("No business logic in the theme"; i18n with `_n()`; Afrikaans source, no AI translation; single-source; conflation rule); src/Tiers/PromotionEngine.php, src/Tiers/Api.php; src/I18n/Terms.php (next-grade label)]_

3. **WP-house-rules + authored AND PASSING tests.** Given the project rules, when this story is built, then: ink-core `.php` keep strict types / namespace / guards; the threshold map stays the single source (no new literal 5/15); the bridge escapes output + is guarded; copy uses `_n()` with the `ink-core` text domain. Pest unit tests are authored at `tests/Unit/Tiers/` and **run with `composer test:unit`; the full suite passes before done** (baseline 325 passed / 1 skipped — zero regressions). `composer cs`/`stan`/`deptrac` run and recorded; deptrac green, no new `Tiers` edge. _[Source: project-context.md (strict types, single-source, i18n, testing rule, conflation); architecture.md AD-8]_

## Tasks / Subtasks

> **Current state (read before starting):**
> - **`PromotionEngine::THRESHOLDS` (5.8)** is the single source of the 5/15 thresholds + the next grade. Reuse it — do NOT re-type 5/15. Add a public `progressFor()` reading the SAME map.
> - **`Api::winCountForUser()` (5.7)** + **`Api::forUser()` (5.1)** give the count + current grade. **`Terms::label()`** gives the next-grade label.
> - **The My Profiel template is Story 9.4 (not built).** Ship the subtext presenter + bridge; 9.4 places it (near the 5.4 badge).
> - **`_n()` is the WP plural function** — author both forms (uitslag / uitslae) as the Afrikaans gettext source.
>
> **Scope is the SUBTEXT only.** Do NOT build: the My Profiel template (9.4), the badge (5.4 — done), discovery (5.5), or any progress BAR/graphic (text subtext only).

- [x] **Task 1 — `PromotionEngine::progressFor()` (AC: 1, 2)**
  - [x] Added `progressFor( Tier $current, int $count ): ?array` reading the SAME `THRESHOLDS` map — null for Goud/Meester, else `[ 'needed' => max(1, wins - count), 'next' => Tier ]`.
- [x] **Task 2 — `Api::winsNeededSubtext()` (AC: 1, 2)**
  - [x] `winsNeededSubtext( int $user_id ): ?string` composes the `_n()` Afrikaans sentence with the next-grade `Terms` label, or null when terminal.
- [x] **Task 3 — Theme bridge (AC: 2, 3)**
  - [x] `ink_foundation_gradering_wins_needed()` in `functions.php` — guarded, escaped, '' when hidden/inactive.
- [x] **Task 4 — Author AND run the Pest tests; record the gates (AC: 3)**
  - [x] `tests/Unit/Tiers/WinsNeededTest.php` (6 tests: progressFor needed+next, progressFor null terminal, singular subtext, plural subtext, Silwer→Goud, null at Goud/Meester).
  - [x] `composer test:unit` → **331 passed / 1 skipped** (1440 assertions), zero regressions. `composer cs` (3 files) clean. `composer stan` clean (sandbox-off). `composer deptrac` → 3 pre-existing only, no new `Tiers` edge.

## Dev Notes

- **Single-source thresholds:** `progressFor()` reads `PromotionEngine::THRESHOLDS` — the same map the engine promotes on. No duplicated 5/15.
- **Hidden at terminal grades:** Goud (no auto-threshold) and Meester (manual-only) have no next grade, so both the presenter and the bridge yield nothing — matches the AC.
- **`_n()` copy** is the glossary-approved phrasing (afrikaans-terms.md line 74), authored as the Afrikaans gettext source — no AI translation. The next-grade label comes from `Terms` (single source).
- **Conflation-clean:** reads only the grade + win counter (Kernel `Tier` + this module); never entitlement.

### Project Structure Notes

- UPDATE: `src/Tiers/PromotionEngine.php` (`progressFor()`), `src/Tiers/Api.php` (`winsNeededSubtext()`), `functions.php` (bridge). NEW test `tests/Unit/Tiers/WinsNeededTest.php`. My Profiel placement deferred to 9.4.

### References

- [Source: epics.md#Story-5.9; afrikaans-terms.md lines 73-74]
- [Source: src/Tiers/PromotionEngine.php (5.8 thresholds), src/Tiers/Api.php (5.7 winCountForUser, 5.1 forUser), src/I18n/Terms.php]
- [Source: project-context.md (three-layer, i18n/_n, no AI Afrikaans, single-source, conflation, testing rule); deptrac.yaml]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop)

### Debug Log References

- `composer test:unit` → 331 passed / 1 skipped (1440 assertions).
- `composer cs` (PromotionEngine.php, Api.php, functions.php) → clean.
- `composer stan` → No errors (sandbox-off).
- `composer deptrac` → 3 pre-existing `Activation → PostTypes`; no new `Tiers` edge.

### Completion Notes List

- **Single-source thresholds reused.** `progressFor()` reads `PromotionEngine::THRESHOLDS` (the same 5/15 map the engine promotes on) — no duplicated literals.
- **`_n()` glossary-approved copy** ("%d top 3 uitslag/uitslae nodig om %s te bereik", afrikaans-terms.md line 74), authored as the Afrikaans gettext source — no AI translation; the next-grade label comes from `Terms`.
- **Hidden at terminal grades:** Goud (no auto-threshold) and Meester (manual-only) yield null from both the presenter and the bridge — matches the AC.
- **Three-layer + conflation clean:** math + copy in ink-core (tested); the theme bridge is thin/guarded and computes nothing; zero `Ink\Entitlement`.
- **My Profiel placement deferred to Story 9.4** (the template owner), per the badge (5.4) precedent.

### File List

- `wp-content/plugins/ink-core/src/Tiers/PromotionEngine.php` (UPDATE — `progressFor()`)
- `wp-content/plugins/ink-core/src/Tiers/Api.php` (UPDATE — `winsNeededSubtext()`)
- `wp-content/themes/ink-foundation/functions.php` (UPDATE — `ink_foundation_gradering_wins_needed()` bridge)
- `tests/Unit/Tiers/WinsNeededTest.php` (NEW)

### Change Log

- 2026-06-26 — Story 5.9 implemented (create-story → dev-story). `progressFor()` + `winsNeededSubtext()` (`_n()` Afrikaans, hidden at Goud/Meester) + theme bridge. My Profiel placement deferred to 9.4. 331 passed / 1 skipped; cs/stan clean; deptrac no new edge. Status → review.

## Review Findings (code review 2026-06-26, Group C: 5.4+5.5+5.9+5.10)

_3-layer adversarial review. Single-source `THRESHOLDS` (no re-typed 5/15), `_n()` arg order + glossary copy (afrikaans-terms.md:74), null at terminal grades, guarded/escaped bridge — all confirmed. Residual item below._

- [x] [Review][Defer] At/above-threshold `winsNeededSubtext()` shows "1 ... nodig" (clamp masks promotion-pending) [`PromotionEngine.php` progressFor] — deferred, low-likelihood: `max(1, wins - count)` means a writer whose stored count already meets/exceeds the threshold (a non-reset counter — a manual DB edit, a legacy pre-5.7 value, or a future direct `recordWin` path that doesn't promote) reads "1 top 3 uitslag nodig" instead of surfacing "promotion pending / 0 needed". `promote()` resets the counter on every promotion, so the normal path can't reach this; the clamp is documented-defensive. Revisit (return null, or a distinct "pending" state) if a non-reset accumulation path is introduced.
