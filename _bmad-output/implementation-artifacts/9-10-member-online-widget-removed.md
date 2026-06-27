---
baseline_commit: 9cc1a1a
---

# Story 9.10: Member online widget (removed)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a product owner,
I want the retired online-widget removed,
so that no CBX dependency remains. (FL 9.10)

## Acceptance Criteria

**Given** CBX retired
**When** chrome is reviewed
**Then** the member-online widget is gone (replaced by engagement signals only if needed).

1. No CBX / member-online-widget code, markup, asset or dependency exists in the `ink-foundation` theme or `ink-core` — verified by scan (this greenfield rebuild never carried the CBX "who's online" widget; it is a legacy-site artifact).
2. A **standing regression guardrail** keeps the CBX online-widget from creeping back: a code scan (comments stripped) of the theme `functions.php` + patterns + all `ink-core/src` asserts none of the banned tokens (`cbx`, `whos-online`/`whos_online`, `member-online`, `online-widget`, `wie is aanlyn`) appear; non-vacuous (it asserts it scanned real code, including the theme half). The 6.9 verification-and-guardrail pattern.
3. No replacement online-widget is built — community presence is conveyed by the existing engagement signals (reactions, follow, activity feed) "only if needed"; this story removes/keeps-out the widget, it does not add one.

## Tasks / Subtasks

- [x] Task 1: Verify clean (AC: #1)
  - [x] Scan the theme + `ink-core` for `cbx` / who's-online / member-online / online-widget tokens — confirm NONE (the CBX widget is a legacy-site artifact, never present in this greenfield repo; dropped at migration, Epic 16).
- [x] Task 2: Standing guardrail (AC: #2)
  - [x] `tests/Unit/Social/CbxWidgetRemovalGuardrailTest.php`: a comment-stripped CODE scan (via the shared `tests/Support/CodeScan`) of the theme `functions.php` + `patterns/*.php` + all `ink-core/src/**.php` asserts no banned CBX/online-widget token; non-vacuous — asserts it read real code AND that the theme half (functions.php) was specifically scanned (mirroring `LegacyRoutingGuardrailTest`).
- [x] Task 3: Gates (AC: all)
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt; `composer deptrac` clean. (No production code — a guardrail test only.)

## Dev Notes

- **Nothing to remove here** [Source: Story 6.9 precedent; project-context "Reactivate retired plugins … ❌"]: this is a greenfield `ink-core` + `ink-foundation` rebuild; the CBX member-online widget lived on the legacy WordPress site, never in this repo. So FL 9.10 is satisfied by **verification + a standing guardrail**, not a deletion. If/when the legacy install is migrated (Epic 16), the widget is dropped there; this repo must simply never reintroduce it.
- **Mirror the 6.9 guardrail exactly** [Source: tests/Unit/Submission/LegacyRoutingGuardrailTest.php]: comment-stripped scan (so a docblock that legitimately NAMES CBX as the retired thing doesn't false-fail), non-vacuous (asserts it scanned real code + the theme half), banned-token list. Reuse `tests/Support/CodeScan::withoutComments`.
- **"Replaced by engagement signals only if needed"** [AC; epics.md#Story 9.10]: do NOT build a presence widget. The follow graph (9.2), activity feed (9.3) and reactions already convey community life; an online-presence indicator is explicitly out of scope.
- **No copy, no UI**: a guardrail-test-only story; no Afrikaans copy, no theme change.

### Project Structure Notes

- NEW tests: `tests/Unit/Social/CbxWidgetRemovalGuardrailTest.php`.
- No `ink-core` / theme source change.
- deptrac / copy:scan: unchanged.
- Note (don't build): any online-presence / "who's online" widget; the legacy CBX removal on the migrated site (Epic 16).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 9.10 (FL 9.10)]
- [Source: tests/Unit/Submission/LegacyRoutingGuardrailTest.php (the verification-and-guardrail precedent, Story 6.9)]
- [Source: tests/Support/CodeScan.php (shared comment-stripper)]
- [Source: _bmad-output/project-context.md#Never-reactivate-retired-plugins]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 9)

### Debug Log References

- No `ink-core`/theme source changed — gates re-run for rigor: stan OK, deptrac 3 pre-existing, copy:scan no new debt.

### Completion Notes List

- **Greenfield verification + standing guardrail** (the 6.9 shape — nothing to remove): the CBX member-online widget is a legacy-site artifact, never present in this repo. `CbxWidgetRemovalGuardrailTest` is a comment-stripped code scan of the theme `functions.php` + patterns + all `ink-core/src` asserting no `cbx` / whos-online / member-online / online-widget / "wie is aanlyn" token; non-vacuous (asserts it read real code AND the theme half specifically). It fails the suite if the widget ever creeps back.
- No replacement presence widget built — community life is conveyed by the follow graph (9.2), activity feed (9.3) and reactions (out of scope per the AC's "only if needed").
- Tests 666→667 (+1); cs 0 errors; stan OK; copy:scan no new debt; deptrac 3 pre-existing (0 new).

### File List

- `tests/Unit/Social/CbxWidgetRemovalGuardrailTest.php` (NEW — standing CBX-removal guardrail)
- `_bmad-output/implementation-artifacts/9-10-member-online-widget-removed.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 9.10 status)
