---
baseline_commit: 8317d02
---

# Story 15.4: Kontak

Status: done

## Story

As a besoeker,
I want a contact form,
so that I can reach INK. (FR-61)

## Acceptance Criteria

1. **Given** the Kontak page **When** I submit **Then** a **custom `ink-core` form** handles it (no CF7 / Fluent Forms), with nonces and sanitisation.
2. The form is reachable by anonymous visitors (a besoeker, not just logged-in members) — the handler registers both `admin_post_` and `admin_post_nopriv_`.
3. Every state-changing path is nonce-verified; every raw `$_POST` read is `is_scalar`-guarded + `wp_unslash`-ed before a sanitiser (`sanitize_text_field` / `sanitize_email` / `sanitize_textarea_field`); no raw superglobal reaches a sanitiser un-guarded.
4. Validation is pure + fail-safe: name and message required (non-empty after trim), email must be a valid address (`is_email`); on failure a `WP_Error` is returned and NOTHING is sent — the visitor is returned to the form with a notice.
5. A honeypot field (must stay empty) silently drops bot submissions.
6. Three-layer: the form markup + handler live in `ink-core` (a server-rendered `ink/kontak-vorm` block — single source for field names, nonce, labels, and the handler); the theme only embeds the block via a `kontak.php` pattern in `page-kontak.html`.
7. Copy: functional Afrikaans field labels via gettext (Afrikaans source language); the Kontak microcopy/validation/success copy that is NOT yet curated in `docs/ui-copy-translations.md` is flagged with the `ink-needs-human-af` marker (the standing unauthored-copy workflow) and rows are mirrored to the copy docs + baseline raised deliberately.

## Tasks / Subtasks

- [x] Task 1: `Ink\Forms\ContactForm` handler + `ink/kontak-vorm` server block (AC: #1–#7)
  - [ ] Constants: BLOCK `ink/kontak-vorm`; NONCE_ACTION/NONCE_NAME; POST_ACTION `ink_kontak`; FIELD_NAME/EMAIL/SUBJECT/MESSAGE; FIELD_HONEYPOT.
  - [ ] `register()`: `admin_post_` + `admin_post_nopriv_` ink_kontak → handlePost; `init` → registerBlock.
  - [ ] Pure `validate(name,email,subject,message): true|WP_Error` (name+message required, `is_email`).
  - [ ] Pure `toHtml(nonceField, actionUrl, notice='')`: the `<form>` (POST to admin-post), labelled fields, honeypot, submit button, optional notice. House-style split: thin `render()` (supplies nonce + admin_url + notice from query) → pure `toHtml()`.
  - [ ] `handlePost()`: nonce verify → honeypot drop → is_scalar guards + unslash + sanitise → `validate()` → `send()` → safe redirect with notice; seams `send()`/`recipient()`/`redirect()`/`halt()` overridable for tests.
  - [ ] `send()`: `wp_mail` to `recipient()` (`get_option('admin_email')`, filterable) behind a filterable `ink_kontak_send_enabled` toggle; Afrikaans subject.
- [x] Task 2: Wire the module (AC: #6)
  - [ ] `Forms\Module::register()` → `( new ContactForm() )->register();`
  - [ ] Register the module in `ink-core.php` (`addModule( 'forms', new Forms\Module() )`).
- [x] Task 3: Theme assembly (AC: #6)
  - [ ] `patterns/kontak.php`: hero (eyebrow "Kontak" + heading + intro) + `<!-- wp:ink/kontak-vorm /-->`.
  - [ ] `templates/page-kontak.html`: thin wrapper embedding `ink-foundation/kontak`.
- [x] Task 4: Tests (AC: #1, #3, #4, #5)
  - [ ] `tests/Unit/Forms/ContactFormTest.php`: block-name constant; `validate()` accepts good input, rejects empty name / empty message / invalid email (WP_Error); `toHtml()` is non-vacuous (contains the nonce field, the four field names, the honeypot, posts to the admin-post action) — test the OUTCOME (INK-owned markup), Brain-Monkey-mocked.
  - [ ] `tests/Unit/Org/KontakTemplateTest.php`: template embeds the `kontak` pattern within locked chrome; pattern embeds the `wp:ink/kontak-vorm` block (the seam).
- [x] Task 5: Gates
  - [ ] `composer test:unit` green; `phpcs` 0 errors; `php -l` clean; `composer copy:scan` — baseline raised deliberately for the new `ink-needs-human-af` Kontak marker (noted in commit); deptrac: Forms depends only on Kernel/WP (no cross-module business edge).

## Dev Notes

### Precedent (read first)
- `Ink\Submission\SubmissionForm` (Story 6.1) is the exact handler pattern: nonce const + field consts, logged-in `admin-post`, `is_scalar` guards + `wp_unslash` + sanitiser, pure `buildPost()` returning array|WP_Error, overridable `redirect()`/`halt()` seams. Kontak differs: it's PUBLIC (`nopriv` too), sends an email instead of creating a post, and adds a honeypot.
- `Ink\Sponsors\RecognitionSection` (Story 14.4) is the server-block pattern: `BLOCK` const, `register()` adds `init`→`registerBlock`, thin `render()` + pure `toHtml()`. Mirror this; test `toHtml()` with Brain Monkey (`__`/`esc_*`/`home_url` mocked) — see `RecognitionSectionTest`.
- Module wiring: `Forms\Module` is the reserved stub; register `ContactForm` in it, then add `addModule( 'forms', new Forms\Module() )` to `ink-core.php` (mirrors the `sponsors` line).
- Email gating: the project keeps transactional-email send-toggles OFF until copy is authored. The contact email goes to STAFF (admin) with visitor content, so the only INK copy is the subject — still, gate the `wp_mail` behind `ink_kontak_send_enabled` (filterable) for consistency and operator control; default it ON (a contact form is meant to deliver), documented.
- Unauthored-copy: `auth-register.php` is the precedent — functional gettext Afrikaans labels (`esc_html__('E-pos','ink-foundation')`) + ONE hidden `ink-needs-human-af` marker flagging the field/validation/success copy as pending human curation. The Kontak block emits the same marker; mirror rows to `ui-copy-translations.md` + `afrikaans-translation-sheet.md` + `afrikaans-copy-worklist.md`; raise the copy:scan baseline with `--update-baseline` and say so in the commit.

### Architecture compliance
- Business logic (handler, validation, nonce, sanitise, send) in `ink-core` only; the theme embeds the block. THE conflation rule: ZERO reference to Tiers/Entitlement — a contact form is not gated on membership or Gradering.
- Escape on output (the block HTML), sanitise on input, nonce on the state-changing POST. `is_email` for the email field.

### Project Structure Notes
- New: `wp-content/plugins/ink-core/src/Forms/ContactForm.php`; modified `Forms/Module.php`, `ink-core.php`. New theme: `patterns/kontak.php`, `templates/page-kontak.html`. New tests under `tests/Unit/Forms/` + `tests/Unit/Org/`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 15.4: Kontak] (FR-61)
- [Source: docs/specs/ink-consolidated-spec.md — custom ink-core contact form, OQ-8 resolved (no CF7/Fluent)]
- [Source: wp-content/plugins/ink-core/src/Submission/SubmissionForm.php — handler precedent]
- [Source: wp-content/plugins/ink-core/src/Sponsors/RecognitionSection.php — server-block precedent]
- [Source: wp-content/themes/ink-foundation/patterns/auth-register.php — unauthored-form-copy precedent]
- [Source: _bmad-output/project-context.md — escape/sanitise/nonce, conflation rule, unauthored-copy workflow]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 930 passed, 1 skipped. New `ContactFormTest` (7) + `KontakTemplateTest` (2) = 9 passed.
- `phpcs` initially flagged the honeypot `$_POST` read as non-sanitised → wrapped in `sanitize_text_field`; re-ran exit 0.
- `composer copy:scan` initially counted docblock self-references to the marker token → reworded the `ContactForm`/`kontak.php` docblocks so only the real hidden span is tracked; then raised the baseline deliberately 6 → 8 (the Kontak `[NEEDS HUMAN AFRIKAANS]` + `ink-needs-human-af` pair).
- `composer stan` → 0 errors. `php -l` clean on all new PHP.
- `composer deptrac` → the **Forms layer is clean (0 violations)** — `ContactForm` depends only on `WP_Error` + WP core, so no new edge. The 3 reported violations are PRE-EXISTING (`Kernel\Activation → Content\PostTypes`, `Activation.php:12/73/114`) — untouched by this story; flagged for the epic review/retro as standing debt.

### Completion Notes List

- The Kontak form is a custom `ink-core` form (NOT CF7/Fluent): `Ink\Forms\ContactForm` owns the `ink/kontak-vorm` server block (form markup + nonce + field names) AND the `admin-post` handler — a true single source. Registered via the now-live `Forms\Module` (added to `ink-core.php`).
- Public reach: registers both `admin_post_ink_kontak` and `admin_post_nopriv_ink_kontak` (a besoeker need not be logged in). AC #2.
- Security: nonce-verified; every `$_POST` read is `is_scalar`-guarded + `wp_unslash` + a field-appropriate sanitiser (`sanitize_text_field` / `sanitize_email` / `sanitize_textarea_field`); pure `validate()` (name+message required, `is_email`) returns `WP_Error` and sends nothing on failure; a hidden honeypot silently drops bots. AC #1, #3, #4, #5.
- Email: `send()` `wp_mail`s the (filterable) `admin_email` with the visitor's Reply-To, behind the filterable `ink_kontak_send_enabled` toggle (default ON — a contact form is meant to deliver). THE conflation rule: zero Tiers/Entitlement reference.
- Theme: `patterns/kontak.php` (hero + `wp:ink/kontak-vorm`) → `templates/page-kontak.html` thin wrapper. Confirms the 14.4 `borg-erkenning` "Word 'n borg" CTA (→ /kontak) now resolves.
- Copy: visible field labels + the two result notices render in functional Afrikaans-as-source via gettext; the not-yet-curated Kontak validation/success microcopy is flagged with the standing `ink-needs-human-af` marker (baseline raised to 8) and tracked in `afrikaans-copy-worklist.md` + `afrikaans-translation-sheet.md` (§8) — the auth-microcopy precedent.

### File List

- `wp-content/plugins/ink-core/src/Forms/ContactForm.php` (NEW)
- `wp-content/plugins/ink-core/src/Forms/Module.php` (MODIFIED — wires ContactForm)
- `wp-content/plugins/ink-core/ink-core.php` (MODIFIED — registers the forms module)
- `wp-content/themes/ink-foundation/patterns/kontak.php` (NEW)
- `wp-content/themes/ink-foundation/templates/page-kontak.html` (NEW)
- `tests/Unit/Forms/ContactFormTest.php` (NEW)
- `tests/Unit/Org/KontakTemplateTest.php` (NEW)
- `tools/leak-scan/placeholder-baseline.json` (MODIFIED — baseline 6 → 8, deliberate)
- `docs/afrikaans-copy-worklist.md`, `docs/afrikaans-translation-sheet.md` (MODIFIED — Kontak copy-debt rows)
- `_bmad-output/implementation-artifacts/15-4-kontak.md`, `sprint-status.yaml` (tracking)

## Change Log

- 2026-06-28 — Story 15.4 implemented: custom ink-core Kontak contact form (ink/kontak-vorm block + admin-post handler, nonce + sanitisation + honeypot + filtered email), theme assembly, and tests. Forms module now live. Status → done.
