---
baseline_commit: d3bc55b
---

# Story 6.9: Remove legacy edit-link filter

Status: done

## Review Findings

- [x] [Review][Patch] `LegacyRoutingGuardrailTest` non-vacuity was blind to the theme half — `$scanned > 0` was satisfied by ink-core files alone, so a path-layout change dropping the theme glob would pass silently (Edge Case Hunter, LOW; the non-vacuous-guardrail rule). Added a `$theme_scanned` assertion that the theme `functions.php` specifically was read. [tests/Unit/Submission/LegacyRoutingGuardrailTest.php]

## Story

As an ink-core developer,
I want the legacy edit-link override removed,
So that no Youzify-era code remains. (FL 6.9)

## Acceptance Criteria

**Given** Youzify retired
**When** the old `functions.php` `/plaas-nuwe-publikasie` override is dropped
**Then** submission routing uses only the new Skryf flow.

1. No Youzify-era submission/edit-link override (`/plaas-nuwe-publikasie`, Youzify function calls) exists in the ink-foundation theme or ink-core.
2. Submission routing uses ONLY the new Skryf flow (the `admin-post` action from Story 6.1).
3. A regression guardrail keeps the legacy override from creeping back.

## Tasks / Subtasks

- [x] Task 1: verify clean (AC: #1, #2)
  - [x] Re-scanned the theme `functions.php`, theme patterns, and `ink-core` for `youzify` / `plaas-nuwe-publikasie` / `plaas_nuwe` / edit-link overrides — NONE present (the override lived on the legacy/migrated site, never in this greenfield repo; the only "Youzify" mentions are the Story-6.1/6.3 docblocks that say what the Skryf flow REPLACES).
- [x] Task 2: regression guardrail (AC: #3)
  - [x] `LegacyRoutingGuardrailTest`: a CODE-only scan (comments stripped via the shared `CodeScan`) of the theme `functions.php` + patterns + `ink-core/src` asserts no `youzify` / `plaas-nuwe-publikasie` / `plaas_nuwe` token; non-vacuous (asserts it scanned real code) — AND asserts the new Skryf `admin-post` route is wired (`SubmissionForm::postAction()` non-empty).
  - [x] Extracted the comment-stripper to `tests/Support/CodeScan` (shared by both guardrails — single source rather than duplicating the helper).

## Dev Notes

- **Nothing to remove here** [Source: Epic-6 research]: this is a greenfield `ink-core` + `ink-foundation` rebuild; the Youzify `/plaas-nuwe-publikasie` `functions.php` override never existed in THIS repo (it is a legacy-site artifact). So FL 6.9 is satisfied by VERIFICATION + a standing guardrail, not a deletion. If/when the legacy WordPress install is migrated (Epic 16), the override is dropped there; this repo must simply never re-introduce it.
- **Routing** [Source: src/Submission/SubmissionForm.php]: the only submission write path is the Story-6.1 `admin_post_ink_submission_plaas` action behind the Skryf page — no legacy edit-link redirect.
- **Guardrail** [Source: Epic-5 retro Action 3 — non-vacuous guardrails]: the scan strips comments (the Skryf docblocks legitimately NAME Youzify as the thing replaced) and asserts both that it scanned real code and that the new route is wired — so it cannot pass vacuously and it fails if Youzify-era code returns.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 6.9]
- [Source: _bmad-output/planning-artifacts/epics.md#Epic 16 (migration)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 6)

### Completion Notes List

- Verified clean: no `youzify` / `plaas-nuwe-publikasie` / `plaas_nuwe` / edit-link override anywhere in the theme or ink-core (the only "Youzify" mentions are the 6.1/6.3 docblocks stating what the Skryf flow replaces). The legacy override is a legacy-site artifact, dropped at migration (Epic 16), never present in this greenfield repo.
- Added `LegacyRoutingGuardrailTest`: a comment-stripped CODE scan of the theme `functions.php` + patterns + all `ink-core/src` for the banned tokens (non-vacuous — asserts it read real code), plus an assertion that the new Skryf `admin-post` route (`ink_submission_plaas`) is the wired flow.
- Extracted the comment-stripper to `tests/Support/CodeScan` (PSR-4 `Ink\Tests\Support`); refactored `ConflationGuardrailTest` onto it (single source, no duplicated helper). Ran `composer dump-autoload`.
- Tests 405→407 (+2). phpcs/phpstan clean; deptrac unchanged (3 pre-existing, Allowed 172); copy:scan no new debt.

### File List

- `tests/Support/CodeScan.php` (NEW — shared comment-stripper)
- `tests/Unit/Submission/LegacyRoutingGuardrailTest.php` (NEW)
- `tests/Unit/Submission/ConflationGuardrailTest.php` (MOD — use CodeScan)
- `_bmad-output/implementation-artifacts/6-9-remove-legacy-edit-link-filter.md` (NEW — this story)
