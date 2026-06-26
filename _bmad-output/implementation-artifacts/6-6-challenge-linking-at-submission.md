---
baseline_commit: d3bc55b
---

# Story 6.6: Challenge linking at submission

Status: review

## Story

As a skrywer,
I want to link my piece to active uitdaging(s),
So that it is entered into the right round. (FR-22, UJ-4)

## Acceptance Criteria

**Given** active uitdagings
**When** I tick them at submission
**Then** the `uitdagingsrondte` term is written
**And** linking is allowed only while the uitdaging is open (before the SAST deadline).

1. The Skryf form lists the currently **open** uitdagings (published + deadline not passed) as optional tick boxes.
2. Ticking an open uitdaging at submission writes the bydrae's `uitdagingsrondte` term for that round.
3. "Open" is the AD-3 boundary: `now <= end-of-day SAST of the uitdaging's deadline` (inclusive 23:59:59 SAST), via the single `Ink\Kernel\Sast` helper — NOT a status flag. A passed-deadline, unpublished, missing-deadline, or non-`uitdaging` id is fail-safe **not linkable** (a tampered/closed tick is silently ignored; the bydrae still saves).

## Tasks / Subtasks

- [x] Task 1: `Ink\Submission\ChallengeLinking` (AC: #1, #2, #3)
  - [x] `isOpen($id, $now)` (uitdaging + publish + `Sast::isThroughEndOfDay(parse(deadline), now)`; fail-safe closed); `openChallenges($now)` (published uitdagings filtered to open, `{id,title}`); `link($post_id, $ids, $now)` (link only open ticked ids → `uitdagingsrondte` term; dedupe; skip ≤0). Seams: `publishedChallenges`, `resolveRoundTerm` (get-or-create round term keyed to the uitdaging), `assign` (`wp_set_object_terms`, append).
- [x] Task 2: wire into the handler + view-model + theme (AC: #1, #2)
  - [x] `SubmissionForm::linkChallenges($post_id)` reads the `ink_submission_uitdagings[]` checkbox array (absint each, nonce already verified) → `ChallengeLinking::link`. `Api::formModel()` exposes `field_challenges` + `open_challenges`; theme renders open-challenge tick boxes.
- [x] Task 3: tests + gates
  - [x] `ChallengeLinkingTest`: isOpen (open/past/missing-deadline/wrong-type/draft, pinned `now` + real Sast); link orchestration (only-open + dedupe + skip-invalid, via seam subclass); openChallenges filtering. All gates green.

## Dev Notes

- **Open = SAST deadline** [Source: architecture AD-3, src/Kernel/Sast.php]: `Sast::isThroughEndOfDay($deadline, $now)` — inclusive 23:59:59 SAST; the same helper the 4.3 entitlement gate uses. Deadline meta is `Ink\Content\FieldSets::UITDAGING_DEADLINE` (`ink_uitdaging_deadline`), stored `Y-m-d[ T]H:i(:s)`; parse in the SAST tz (date-only → valid through that SAST day). Fail-safe **closed** on a missing/unparseable deadline (don't enter an undefined round).
- **Term written** [Source: epics.md#Story 6.6, src/Content/Taxonomies.php]: assign the `uitdagingsrondte` term (`Taxonomies::UITDAGINGSRONDTE`) via `wp_set_object_terms(..., append=true)`. The authoritative entry record (`ink_entries`) + the definitive round model are Epic 12/12A; `resolveRoundTerm()` is a documented seam (get-or-create a round term keyed to the uitdaging) that Epic 12 may refine — `Ink\Challenges\Api::enter()` does not exist yet (AD-3 seam).
- **Scope** [Source: epics.md#Story 6.6]: links only at submission to OPEN rounds; no winner/placement logic (Epic 12/12A). Submission depends on Kernel (Sast) + Content (FieldSets/Taxonomies/PostTypes) — both allowed. Conflation-clean — no `Ink\Tiers`.
- **Testing**: `isOpen` mocks `get_post_type/status/meta` + pins `now` (real Sast math); `link`/`openChallenges` use a seam subclass so the orchestration (only-open, dedupe, skip-invalid) is tested without the WP term API.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 6.6]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-3]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 6)

### Completion Notes List

- `ChallengeLinking` (non-final testability seam, like SubmissionGate): `isOpen` (uitdaging + publish + `Sast::isThroughEndOfDay(parseDeadline, now)`, fail-safe closed), `openChallenges`, `link` (only-open + dedupe + skip ≤0 → `uitdagingsrondte` term via `wp_set_object_terms` append). Deadline meta `FieldSets::UITDAGING_DEADLINE`, taxonomy `Taxonomies::UITDAGINGSRONDTE`. `resolveRoundTerm` is a documented Epic-12 seam (get-or-create round term keyed to the uitdaging).
- `SubmissionForm::linkChallenges()` reads the `ink_submission_uitdagings[]` array (absint each; justified phpcs:ignore — nonce verified, each value absint'd). `Api::formModel()` exposes `field_challenges` + `open_challenges`; theme renders open-challenge tick boxes (only when any are open).
- Fixed a missing `use Ink\Kernel\Scalar;` (caught by the isOpen tests — Scalar resolved to the wrong namespace); phpcbf fixed array alignment + pattern indentation.
- Tests 391→397 (+6): isOpen open/past/missing-deadline/wrong-type/draft (pinned now + real Sast); link only-open + dedupe + skip-invalid; openChallenges filtering. phpcs/phpstan clean; deptrac 3 pre-existing (Allowed 166 — new Kernel/Content edges); copy:scan no new debt. Conflation-clean.

### File List

- `wp-content/plugins/ink-core/src/Submission/ChallengeLinking.php` (NEW)
- `wp-content/plugins/ink-core/src/Submission/SubmissionForm.php` (MOD — linkChallenges)
- `wp-content/plugins/ink-core/src/Submission/Api.php` (MOD — field_challenges + open_challenges)
- `wp-content/themes/ink-foundation/patterns/skryf.php` (MOD — challenge tick boxes)
- `tests/Unit/Submission/ChallengeLinkingTest.php` (NEW)
- `tests/Unit/Submission/ApiTest.php` (MOD — mock get_posts)
- `_bmad-output/implementation-artifacts/6-6-challenge-linking-at-submission.md` (NEW — this story)
