# Story 12.7: Winner → Gradering promotion link

Status: review

## Story

As a redakteur,
I want to link a winner to a promotion,
so that the audit trail connects results to advancement. (FR-51, UJ-5)

## Acceptance Criteria

**Given** graderingsgeskiedenis
**When** a promotion is recorded
**Then** it can optionally link to the challenge result.

Decomposed:

1. The WRITE side already exists: `Tiers\AdminProfile` (Story 5.2) lets a redakteur record a promotion with an optional linked uitdaging, and `Tiers\Api::promote()` + `PromotionLog` persist `challenge_id` (Story 5.3). This story makes the link **visible** — the audit trail that "connects results to advancement".
2. `Tiers\Api::historyFor()` exposes the graderingsgeskiedenis through the facade (newest-first `PromotionLogEntry` list, each carrying its optional `challengeId`).
3. `Challenges\PromotionHistory` renders a read-only graderingsgeskiedenis section on the writer's user-edit screen (redakteur, `MANAGE_TIERS`-gated), each row showing from→to grade, reason, actor (Stelsel vs redakteur) and — when linked — the uitdaging title as a link (resolved Challenges-side, which Tiers can't do without a forbidden `Tiers -> Content` edge).
4. Conflation-clean: reads `Ink\Tiers` (the history facade) + `Ink\Content` (resolve the uitdaging) — zero `Ink\Entitlement`.

## Tasks / Subtasks

- [x] Task 1: `Tiers\Api::historyFor(int $user_id): list<PromotionLogEntry>` — facade read over `PromotionLog::forUser` (so Challenges consumes the facade, not the table).
- [x] Task 2: `Challenges\PromotionHistory` (non-final seam) — `register()` on `edit_user_profile` (MANAGE_TIERS-gated); `resolveChallenge()` seam (uitdaging → title/permalink); pure `rowView(PromotionLogEntry, ?challenge)` + pure `toHtml(array $rows)`; thin `renderField()`.
- [x] Task 3: Terminology — add `graderingsgeskiedenis` heading term.
- [x] Task 4: Module wiring — `Challenges\Module::register()` registers `PromotionHistory`.
- [x] Task 5: Tests — `tests/Unit/Challenges/PromotionHistoryTest.php` (rowView linked vs unlinked, system vs staff actor; toHtml renders rows + the challenge link + empty state) + `tests/Unit/Tiers/ApiTest.php` historyFor delegation (if an ApiTest exists; else a focused PromotionLog facade test).
- [x] Task 6: Gates — test/cs/stan/deptrac green; no new deptrac edge (Challenges already → Tiers + Content).

## Dev Notes

- `PromotionLogEntry` already exposes `challengeId` + `isChallengeLinked()` + `isSystem()`; the linked id is a uitdaging post id. [Source: src/Tiers/PromotionLogEntry.php]
- The redakteur write UI + the `challenge_id` persistence exist (5.2/5.3); 12.7 is the read/display of the linkage. The challenge resolution lives in Challenges because `Tiers` may not depend on `Content` (deptrac `Tiers: [Kernel, Notifications]`). [Source: src/Tiers/AdminProfile.php:200-231; deptrac.yaml]
- Render mirrors `AdminProfile`'s `edit_user_profile` section + escaping discipline; read-only (no save). [Source: src/Tiers/AdminProfile.php:70-148]
- Tier labels via `Terms::label($tier->value)`; the section heading via a new `graderingsgeskiedenis` term.

### Project Structure Notes

- New: `src/Challenges/PromotionHistory.php`, `tests/Unit/Challenges/PromotionHistoryTest.php`.
- Modified: `src/Tiers/Api.php` (historyFor facade), `src/Challenges/Module.php`, `src/I18n/Terms.php`.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12.7]
- [Source: docs/afrikaans-terms.md] lines 68-72

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- Surfaced the graderingsgeskiedenis as a read-only redakteur audit-trail section (MANAGE_TIERS-gated) on the user-edit screen, each row showing from→to, reason, actor (Stelsel/Redakteur) and the linked uitdaging as a link. The write/link UI + challenge_id persistence already existed (5.2/5.3); 12.7 made it visible.
- Added `Tiers\Api::historyFor()` facade so Challenges consumes the audit log through the facade, not the table. Challenge resolution lives in Challenges (Tiers may not depend on Content); pure rowView + toHtml, resolveChallenge seam.
- Conflation-clean: Challenges -> Tiers (history) + Content (resolve uitdaging), zero Entitlement. No new deptrac edge.
- Gates: composer test → 805 passed/2 skipped (+4); cs 0 errors (one alignment nit auto-fixed); stan clean; deptrac 3 pre-existing only.

### File List

- `wp-content/plugins/ink-core/src/Challenges/PromotionHistory.php` (new)
- `wp-content/plugins/ink-core/src/Tiers/Api.php` (modified — historyFor facade)
- `wp-content/plugins/ink-core/src/Challenges/Module.php` (modified — register PromotionHistory)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (modified — graderingsgeskiedenis term)
- `tests/Unit/Challenges/PromotionHistoryTest.php` (new)

### Change Log

- 2026-06-27: Story 12.7 implemented — graderingsgeskiedenis audit-trail display with optional challenge link. Status → review.
