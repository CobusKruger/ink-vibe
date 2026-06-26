---
baseline_commit: 994c5da
---

# Story 6.1: Custom front-end submission form

Status: done

## Review Findings

- [x] [Review][Patch] H1 "Deel jou woorde" + supporting paragraph were raw literals, not in the `ink-foundation` text domain like every other string in the pattern (Acceptance Auditor, HIGH) — wrapped both in `esc_html_e( …, 'ink-foundation' )`. [patterns/skryf.php]
- [x] [Review][Defer] No anti-spam / rate-limit on draft creation (logged-in + nonce only, by design) — Story 18.10. See deferred-work.md.

## Story

As a skrywer,
I want a custom front-end submission form,
So that I can submit a gedig/storie/artikel without the legacy plugin. (FR-16)

## Acceptance Criteria

**Given** the Skryf page
**When** I submit
**Then** type-appropriate fields and validation serve gedig/storie/artikel (replacing Youzify FES).

1. A logged-in skrywer can reach a custom Skryf form (no Youzify) that lets them choose a bydrae type — **gedig**, **storie**, or **artikel** — and enter a **title** and **body**.
2. Submitting creates a bydrae of the chosen CPT, authored by the current user. (Epic-6 build-forward: 6.1 saves the bydrae as a **konsep/draft** — the publish path + entitlement gate land in 6.7/6.8; draft saving is never entitlement-gated, per FR-23.)
3. Validation is type-aware and fail-safe: the type must be one of the three submittable CPTs (an unknown/tampered type is rejected — `skryfwerk` is the migration holding bucket and is NOT submittable); title and body are required (empty → no post created, user returned with no silent data loss).
4. The flow uses the sanctioned logged-in `admin-post` write seam: nonce-verified, capability-checked, raw `$_POST` never reaches a sanitiser un-guarded, redirect on completion. No business logic lives in the theme — the Skryf page pattern only renders + escapes, sourcing the form action/nonce/labels/type-list from `ink-core`.

## Tasks / Subtasks

- [x] Task 1: Submission module foundation (AC: #4)
  - [x] Wire `Ink\Submission\Module::register()` to register the form collaborator; add `submission` to the `ink-core.php` bootstrap module list.
  - [x] deptrac: extend `Submission` allowlist with `Content` (it reads the bydrae CPT slugs from `Ink\Content\PostTypes`, the migration-load-bearing single source).
- [x] Task 2: `Ink\Submission\SubmissionForm` handler (AC: #1, #2, #3, #4)
  - [x] Constants: nonce action/name, admin-post action, field names; `submittableTypes()` = `[gedig, storie, artikel]` (skryfwerk excluded).
  - [x] `register()` hooks `admin_post_{action}`; static `nonceAction()/nonceName()/postAction()` accessors for the theme bridge.
  - [x] Pure, testable validation (`buildDraft()`): type ∈ submittable, title non-empty, body non-empty → otherwise fail-safe `WP_Error` (no write).
  - [x] `handlePost()`: logged-in guard → nonce verify → inline `is_scalar` superglobal guards + `wp_unslash` + `sanitize_*`/`wp_kses_post` → `wp_insert_post` (status `draft`, author = current user, chosen CPT) → `wp_safe_redirect` (seam-able `halt()`).
- [x] Task 3: `Ink\Submission\Api` facade view-model (AC: #1, #4)
  - [x] `formModel()` returns the theme view-model: post action, nonce action/name, type options (slug + Afrikaans label via Terms), field names — no logic in theme.
- [x] Task 4: Theme surface (AC: #1, #4)
  - [x] `patterns/skryf.php` (Afrikaans copy from ui-copy-translations.md "Skryf-bladsy"), `templates/page-skryf.html` (filename-convention bind to page slug `skryf`), bridges `ink_foundation_skryf_form_fields()` + `ink_foundation_skryf_model()` in `functions.php` (class_exists-guarded, graceful). Logged-out visitors see a meld-aan prompt, not the form.
- [x] Task 5: Tests + gates
  - [x] `tests/Unit/Submission/SubmissionFormTest.php` (8 tests) + `ApiTest.php` (2 tests): type allowlist (skryfwerk + unknown rejected), required-field validation, accessors, handlePost happy path asserts `wp_insert_post` args, handlePost fail-safe (bad nonce / logged-out → `wp_insert_post` never), view-model shape.
  - [x] `composer test:unit` green (360→370), `cs`/`stan` clean, `deptrac` only the 3 pre-existing `Activation→PostTypes` (new `Submission→Content` edge Allowed); `copy:scan` no new debt.

## Dev Notes

- **Module bootstrap** [Source: ink-core.php:74-84]: `Plugin::instance()->addModule('submission', new Submission\Module())` on `plugins_loaded`; modules' `register()` fire on `init`. `Ink\Kernel\Module` contract = single `register(): void`.
- **Form handler model** [Source: src/Accounts/Onboarding.php]: copy the `admin_post_{action}` (logged-in, NO `nopriv`) seam, `nonceAction()/nonceName()/postAction()` accessors, the `wp_nonce_field` + hidden `action` input bridge (`ink_foundation_onboarding_form_fields()`), nonce-verify → `is_scalar` inline guard → `wp_unslash` → `sanitize_text_field` → write → `wp_safe_redirect; exit`.
- **CPT slugs** [Source: src/Content/PostTypes.php]: `GEDIG/STORIE/ARTIKEL/SKRYFWERK` constants; `bydraeTypes()` returns all four. Submittable types EXCLUDE `skryfwerk` (migration holding bucket, not user-facing — see afrikaans-terms.md / project-context §3). Bydrae CPTs support `title, editor, author, thumbnail, excerpt, custom-fields, revisions`; cap family `ink_content`.
- **THE conflation rule** [project-context.md:53]: submission entitlement (6.8) keys on lidmaatskap, NEVER on `ink_writer_tier`. `src/Submission/` must carry ZERO reference to `Ink\Tiers`. 6.1 writes only the bydrae post — no tier, no entitlement.
- **Three-layer** [project-context.md:52]: no submission logic in the theme; the pattern renders + escapes; all shaping in `ink-core` (mirror `PlanPresenter`/`ink_foundation_membership_plans()`).
- **Copy** [Source: docs/ui-copy-translations.md:360-405, "Skryf-bladsy"]: H1 "Deel jou woorde"; supporting "Elke storie begin met 'n enkele woord. Begin joune hier."; types Gedig/Storie/Artikel with sub-descriptions; "Titel" + placeholder "Gee jou werk 'n titel..."; buttons "Stoor konsep" / "Plaas". Theme-only presentation strings use `__( '...', 'ink-foundation' )`; nouns come from `Ink\I18n\Terms` (gedig/storie/artikel/bydrae already registered).
- **Testing** [Source: tests/Unit/Entitlement/SubmissionGateTest.php, tests/Pest.php]: Pest + Brain Monkey; `Monkey\setUp()/tearDown()`, `Functions\when('__')->returnArg(1)`, `Functions\expect('wp_insert_post')->once()->with(...)`. Test the pure validation directly; for `handlePost`, mock the WP globals and assert the insert (and assert `->never()` on the fail-safe paths).

### Project Structure Notes

- New: `src/Submission/SubmissionForm.php`; edits to `src/Submission/Module.php`, `src/Submission/Api.php`, `ink-core.php`, `deptrac.yaml`; theme `patterns/skryf.php`, `templates/page-skryf.html`, `functions.php` bridges; `tests/Unit/Submission/SubmissionFormTest.php`.
- deptrac: `Submission: [Kernel, Content]` after this story (Entitlement added in 6.8). Permanent `Entitlement ⟂ Tiers` untouched; Submission never lists Tiers.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 6.1]
- [Source: _bmad-output/project-context.md#three-layer, #conflation-rule, #escaping]
- [Source: docs/ui-copy-translations.md#Skryf-bladsy]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 6)

### Debug Log References

- phpcs initially flagged the body read: assigning the raw `$_POST` value to an intermediate variable trips `ValidatedSanitizedInput.InputNotSanitized`. Fixed by inlining `wp_kses_post( wp_unslash( $_POST[...] ) )` (a recognised sanitiser wrapping), mirroring the title/type reads — no `phpcs:ignore` needed.

### Completion Notes List

- Built the Submission module's first real surface: `SubmissionForm` (admin-post handler), `Api::formModel()` (theme view-model), `Module::register()` wiring, `submission` added to bootstrap.
- Authorisation is INK's own (logged-in + nonce for the draft), NOT WP `ink_content` caps (those are admin/editor-only, Story 3.3) — front-end submission creates the post directly via `wp_insert_post` after the handler's own checks.
- 6.1 saves a **konsep/draft** (ungated per FR-23); the publish path + entitlement gate are deferred to 6.7/6.8 by design (build-forward). `skryfwerk` is excluded from submittable types (migration bucket).
- Three-layer clean: the `patterns/skryf.php` renders + escapes only; all dynamic data (types, wiring) flows from `Ink\Submission\Api` via guarded `ink_foundation_skryf_*` bridges. Conflation-clean: zero `Ink\Tiers` reference.
- Tests 360→370 (+10), zero regressions; phpcs/phpstan clean; deptrac adds the documented `Submission→Content` edge (Allowed), no new violation; copy:scan no new debt.

### File List

- `wp-content/plugins/ink-core/src/Submission/SubmissionForm.php` (NEW)
- `wp-content/plugins/ink-core/src/Submission/Module.php` (MOD — register SubmissionForm)
- `wp-content/plugins/ink-core/src/Submission/Api.php` (MOD — formModel)
- `wp-content/plugins/ink-core/ink-core.php` (MOD — bootstrap submission module)
- `deptrac.yaml` (MOD — Submission → Content)
- `wp-content/themes/ink-foundation/functions.php` (MOD — ink_foundation_skryf_model/_form_fields bridges)
- `wp-content/themes/ink-foundation/patterns/skryf.php` (NEW)
- `wp-content/themes/ink-foundation/templates/page-skryf.html` (NEW)
- `tests/Unit/Submission/SubmissionFormTest.php` (NEW)
- `tests/Unit/Submission/ApiTest.php` (NEW)
- `_bmad-output/implementation-artifacts/6-1-custom-front-end-submission-form.md` (NEW — this story)
