---
baseline_commit: d3bc55b
---

# Story 6.7: Draft / publish states

Status: review

## Story

As a skrywer,
I want to save a draft or publish,
So that I can work iteratively and publish when ready. (FR-23)

## Acceptance Criteria

**Given** the editor
**When** I act
**Then** "Stoor konsep" saves a draft (not entitlement-gated) and "Plaas" publishes
**And** publishing shows a success screen with read-and-respond prompts.

1. The Skryf form has two actions: **Stoor konsep** (saves `draft`) and **Plaas** (saves `publish`), distinguished by an intent field.
2. Saving a konsep is **never entitlement-gated** (FR-23) — 6.1's behaviour. (The publish entitlement gate is Story 6.8, immediately next; 6.7 builds the publish status path.)
3. After a successful **Plaas**, the writer is shown a **success screen** ("Jou [gedig/storie/artikel] is gepubliseer") with **read-and-respond prompts** ("Skryf nog 'n stuk" / "Terug na tuis"), not the empty form.

## Tasks / Subtasks

- [x] Task 1: intent → status (AC: #1, #2)
  - [x] `INTENT_FIELD` + `INTENT_PUBLISH='plaas'`/`INTENT_DRAFT='konsep'`; `statusForIntent($intent)` (plaas→publish, else draft). Rename `buildDraft`→`buildPost` with a `$status` param (default `draft`); `handlePost` reads intent → status → `buildPost`.
- [x] Task 2: success screen model + routing (AC: #3)
  - [x] `successUrl($post_id)` redirect after publish (`ink_skryf=geplaas&id=`); `Api::successModel($post_id)` (published bydrae → `{title,type_label,permalink}`, else null). Theme: two buttons; the pattern renders the success screen (with read-and-respond prompts) when `?ink_skryf=geplaas`.
- [x] Task 3: tests + gates
  - [x] `statusForIntent`; `buildPost` honours status (+ existing draft cases renamed); handlePost publish path (status publish + success redirect) vs draft path; `successModel` (published/non-published/non-bydrae/≤0). All gates green.

## Dev Notes

- **Intent** [Source: ui-copy-translations.md:388-389]: two submit buttons "Stoor konsep" (`konsep`) / "Plaas" (`plaas`). `plaas` → `post_status=publish`; anything else → `draft` (fail-safe to the ungated state). Draft is never gated (FR-23); the publish gate is 6.8.
- **Success screen** [Source: ui-copy-translations.md:399-409]: H1 "Jou [gedig/storie/artikel] is gepubliseer" (type label via `Terms`), read-and-respond prompts "Skryf nog 'n stuk" / "Terug na tuis" / "Lees en reageer" (static prompts; suggested-reads is Epic 7/8). Post-redirect, the pattern reads `$_GET['ink_skryf']` (display-only, phpcs:ignore NonceVerification.Recommended like `Accounts\Approval::renderNotice`) + `id`, fetches `Api::successModel` via a bridge.
- **Build-forward** [Source: epics.md#Story 6.8]: 6.7 ships the publish STATUS path; 6.8 inserts the entitlement check before allowing publish (lapsed-at-publish denial, draft preserved). Conflation-clean.
- **Testing**: pure `statusForIntent`/`buildPost`; handlePost publish vs draft via the seam subclass (assert insert status + redirect target); `successModel` mocks `get_post_type/status`, `get_the_title`, `get_permalink`.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 6.7]
- [Source: docs/ui-copy-translations.md#Skryf-bladsy]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 6)

### Completion Notes List

- Intent → status: `INTENT_FIELD`/`INTENT_DRAFT`/`INTENT_PUBLISH` + `statusForIntent()` (plaas→publish, else→draft fail-safe). Renamed `buildDraft`→`buildPost` with a `$status` param (default draft). `handlePost` reads the intent, builds with the resolved status, and branches the redirect (publish → `successUrl`; draft → `konsep-gestoor`).
- Success screen: `Api::successModel($post_id)` (published bydrae → title/type_label/permalink, else null) + `ink_foundation_skryf_success` bridge. The pattern shows the success screen ("Jou {gedig/storie/artikel} is gepubliseer" + "Dankie..." + read-and-respond prompts "Skryf nog 'n stuk" / "Terug na tuis") when `?ink_skryf=geplaas` (display-only $_GET read, justified phpcs:ignore like Approval::renderNotice). Two submit buttons (Stoor konsep / Plaas).
- Build-forward note: 6.7 ships the publish STATUS path; the entitlement gate on plaas is 6.8 (next).
- Tests 397→402 (+5): statusForIntent; buildPost publish status; handlePost publish→success redirect; successModel published/draft/non-bydrae/≤0. phpcs/phpstan clean; deptrac unchanged (3 pre-existing, Allowed 166); copy:scan no new debt. Conflation-clean.

### File List

- `wp-content/plugins/ink-core/src/Submission/SubmissionForm.php` (MOD — intent/status, buildPost, successUrl)
- `wp-content/plugins/ink-core/src/Submission/Api.php` (MOD — intent fields + successModel)
- `wp-content/themes/ink-foundation/functions.php` (MOD — ink_foundation_skryf_success bridge)
- `wp-content/themes/ink-foundation/patterns/skryf.php` (MOD — success screen + two buttons)
- `tests/Unit/Submission/SubmissionFormTest.php` (MOD — buildPost rename + intent/publish tests)
- `tests/Unit/Submission/ApiTest.php` (MOD — successModel tests)
- `_bmad-output/implementation-artifacts/6-7-draft-publish-states.md` (NEW — this story)
