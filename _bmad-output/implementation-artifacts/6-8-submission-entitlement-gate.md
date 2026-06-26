---
baseline_commit: d3bc55b
---

# Story 6.8: Submission entitlement gate

Status: done

## Review Findings

- [x] [Review][Patch] `Api::successModel()` was spoofable — any visitor could view "Jou … is gepubliseer" for an arbitrary published bydrae id (Blind+Edge, LOW). Added an author check (`post_author === current user`) so only the author sees their own success screen; added a non-author → null test. [src/Submission/Api.php]
- [x] [Review][Defer] `ProseSanitizer` stripping tested at policy level only (`wp_kses` mocked) — real-strip exercise is Story 18.8 integration. See deferred-work.md.
- The conflation core (gate keys on lidmaatskap only; lapsed Goud denied; draft preserved) passed all three layers; the non-vacuous conflation guardrail confirms `src/Submission/` is free of `Ink\Tiers`.

## Story

As the system,
I want to allow plaas only for active betaalde lede,
So that publishing tracks lidmaatskap (THE conflation rule). (FR-19)

## Acceptance Criteria

**Given** a publish action
**When** entitlement is evaluated at the moment of plaas
**Then** only lede with active entitlement may publish; others see an Afrikaans denial + link to plans
**And** a konsep saved while entitled but published after lapse is denied at publish time (draft preserved); a lapsed Goud writer is denied (Gradering does not grant entitlement).

1. At the moment of **plaas** (publish intent), entitlement is evaluated via `Ink\Entitlement\Api::can_submit()` — the SAST end-of-date authority (Story 4.3), NOT a status flag.
2. An entitled lid publishes. A **non-entitled** request is denied: the bydrae is **saved as a konsep (draft) — the writer's text is preserved**, never lost — and the writer sees the Afrikaans denial (the 4.7 `status_access_denied` message) + a link to the lidmaatskap plans.
3. THE conflation rule: the gate keys ONLY on lidmaatskap entitlement, never on `ink_writer_tier` — a **lapsed Goud writer is denied** (Gradering does not grant publishing). `src/Submission/` carries ZERO reference to `Ink\Tiers`.

## Tasks / Subtasks

- [x] Task 1: deptrac — Submission → Entitlement (via the Api facade)
  - [x] Extend the `Submission` allowlist with `Entitlement` (the documented "extend as real Api-facade usage appears" path); `Entitlement ⟂ Tiers` untouched.
- [x] Task 2: gate the publish path (AC: #1, #2, #3)
  - [x] `SubmissionForm::canPublish($user_id)` seam → `Entitlement\Api::can_submit()`. In `handlePost`, a `plaas` intent for a non-entitled user is downgraded to `draft` (text preserved) + redirected to the denial state; an entitled plaas publishes as before.
- [x] Task 3: denial copy + theme (AC: #2)
  - [x] `Api::denialMessage()` → `Entitlement\Api::statusMessage(MembershipStatus::AccessDenied)`; `ink_foundation_skryf_denial` bridge; the pattern renders the denial + a `/lidmaatskap` link when `?ink_skryf=geen-toegang`.
- [x] Task 4: tests + gates
  - [x] handlePost: entitled plaas → publish + success; non-entitled plaas → saved as DRAFT + denial redirect (the conflation test — denied regardless of tier); `denialMessage` non-empty. A non-vacuous conflation guardrail: `src/Submission/` contains no `Ink\Tiers` / `ink_writer_tier`. All gates green.

## Dev Notes

- **The gate** [Source: src/Entitlement/Api.php:168, architecture AD-2]: `Api::can_submit(int|WP_User|null): bool` — active INK lidmaatskap, end date valid through end-of-day SAST. Evaluated AT plaas (not cached). The `MembershipStatus` enum doc already notes "Story 6.8 surfaces it" (`AccessDenied`).
- **Draft preserved** [Source: epics.md#Story 6.8, project-context.md:130]: a denied plaas must NOT lose the writer's text — downgrade the insert to `draft` and show the denial. (Our flow inserts a fresh post per submit, so "preserve" = save the konsep rather than refuse outright.)
- **Conflation rule** [Source: project-context.md:53, epics.md#Story 6.8]: gate on lidmaatskap ONLY. A lapsed Goud writer (high tier, no active lidmaatskap) is denied — the gate never reads `ink_writer_tier`. The guardrail test pins `src/Submission/` free of `Ink\Tiers`.
- **Denial copy** [Source: src/Entitlement/Api.php:226, afrikaans-terms.md]: `statusMessage(MembershipStatus::AccessDenied)` = "Slegs betaalde lede kan werk plaas. Sien aansluitingsopsies." (4.7, human-authored). Link to `/lidmaatskap` (the 4.4 page).
- **Testing**: `canPublish` is a seam (the SubmissionGate precedent) so handlePost is tested both entitled (publish+success) and not (draft+denial) without WC. The denial path proves text-preservation (insert status draft) + denial routing.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 6.8]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-2]
- [Source: _bmad-output/project-context.md#conflation-rule]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 6)

### Completion Notes List

- deptrac: `Submission → Entitlement` added (documented Api-facade path); `Entitlement ⟂ Tiers` untouched (still 3 pre-existing violations only, Allowed 172).
- `SubmissionForm::canPublish()` seam → `Entitlement\Api::can_submit()`. In `handlePost`, a `plaas` for a non-entitled user is downgraded to `draft` (text preserved) + redirected to `geen-toegang`; entitled plaas publishes. The gate reads entitlement ONLY — never `ink_writer_tier`.
- `Api::denialMessage()` → `Entitlement\Api::statusMessage(MembershipStatus::AccessDenied)` (the 4.7 copy); `ink_foundation_skryf_denial` bridge; pattern shows the denial + `/lidmaatskap` link on `?ink_skryf=geen-toegang`.
- Tests 402→405 (+3): non-entitled plaas → saved as DRAFT (assert insert status draft) + denial redirect (the conflation test — denied regardless of tier, proving "lapsed Goud denied" + "draft preserved"); denialMessage non-empty; non-vacuous conflation guardrail (`src/Submission/` CODE — comments stripped via token_get_all — free of `Ink\Tiers`/`ink_writer_tier`, asserting it scanned real code + found files). phpcs/phpstan clean; copy:scan no new debt.

### File List

- `deptrac.yaml` (MOD — Submission → Entitlement)
- `wp-content/plugins/ink-core/src/Submission/SubmissionForm.php` (MOD — canPublish gate + denial path)
- `wp-content/plugins/ink-core/src/Submission/Api.php` (MOD — denialMessage)
- `wp-content/themes/ink-foundation/functions.php` (MOD — ink_foundation_skryf_denial bridge)
- `wp-content/themes/ink-foundation/patterns/skryf.php` (MOD — denial banner)
- `tests/Unit/Submission/SubmissionFormTest.php` (MOD — canPublish seam + deny test)
- `tests/Unit/Submission/ApiTest.php` (MOD — denialMessage test)
- `tests/Unit/Submission/ConflationGuardrailTest.php` (NEW — Submission ⟂ Tiers guardrail)
- `_bmad-output/implementation-artifacts/6-8-submission-entitlement-gate.md` (NEW — this story)
