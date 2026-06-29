---
baseline_commit: ef775c1
---

# Story 18.4: Moderation/report path

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a lid,
I want to report content,
so that abuse can be moderated. (§8)

## Acceptance Criteria

1. **Given** a work/review **When** I report it **Then** a **custom `ink-core` report form** handles it (no third-party Report Content).
2. **A logged-in lid** can report a `werk` (gedig/storie/artikel), a `resensie` (review) or a `reaksie` (community response). The form is `ink-core`'s own — never the retired "Report Content" plugin. Anonymous visitors cannot report (no `nopriv` handler): reporting requires a lid identity (so a report is attributable + rate-limitable).
3. **Controlled vocabulary:** the report reason is a fixed value set modelled as an `enum` (project-context "model fixed value sets as enums") — `ReportReason`: `kwetsend` / `spam` / `plagiaat` / `ander`; the target kind is `ReportTarget`: `werk` / `resensie` / `reaksie`. The persisted DB value is the enum string.
4. **Persistence:** reports are stored in a custom `ink-core` table (`ink_reports`) via the Kernel Schema registry (the established `ReactionStore`/`RatingStore` pattern), with `{object_type, object_id, reporter_id, reason, detail, status, created_at}`; new reports default to `status = oop` (open). A `do_action( 'ink/content_reported', … )` seam fires on a successful report so moderation notifications/queues can consume it (no cross-module coupling added now).
5. **Sanctioned write path** (mirrors `ContactForm`): nonce → honeypot drop → `is_scalar` guard + `wp_unslash` + sanitise on every field → pure `validate()` → persist → safe redirect with a notice. `validate()` requires a logged-in reporter (`reporter_id > 0`), a valid `ReportTarget`, `object_id > 0`, and a valid `ReportReason`; detail is optional.
6. **Afrikaans-first:** all form labels, reason options and notices are Afrikaans via the `ink-core` text domain; the moderation table values are enum strings. No English leakage; no new copy debt (or, if any microcopy is genuinely un-authored, it follows the placeholder workflow — but the report copy here is concrete Afrikaans).
7. **Non-vacuous tests:** enum value sets; `ReportStore::schemaSql()` declares the table + columns and `tableName()` is prefixed; `validate()` accepts a good report and rejects each failure mode (anonymous, bad target, non-positive id, bad reason) with the right `WP_Error` code; `toHtml()` renders the nonce, the hidden target fields, the reason `<select>` with every `ReportReason` option, the honeypot and the admin-post action.
8. **Three-layer + conflation clean:** all logic in `ink-core` Forms module; references neither Tiers nor Entitlement (reporting is open to any lid, never gated on membership tier/Gradering). `Forms` deptrac ruleset unchanged (`[Kernel]`) — the store uses `$wpdb` + the Schema registry (registered in the composition root).
9. **Gates green:** `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer copy:scan` clean; `composer deptrac` no new violations; baseline unchanged.

## Tasks / Subtasks

- [x] Task 1: Enums (AC: #3)
  - [x] `src/Forms/ReportReason.php` (`kwetsend`/`spam`/`plagiaat`/`ander`) with `values()` + Afrikaans `label()`.
  - [x] `src/Forms/ReportTarget.php` (`werk`/`resensie`/`reaksie`) with `values()`.
- [x] Task 2: `src/Forms/ReportStore.php` — custom table (AC: #4)
  - [x] `TABLE = 'ink_reports'`; `tableName()`, `schemaSql()` (dbDelta DDL); `record()` returns the new id; `STATUS_OPEN = 'oop'` / `STATUS_RESOLVED = 'afgehandel'`.
  - [x] Registered the schema provider in `ink-core.php` (include-time, mirroring `ReactionStore`).
- [x] Task 3: `src/Forms/ReportForm.php` — the form (AC: #1, #2, #5, #6)
  - [x] Block `ink/rapporteer-vorm` (objekTipe/objekId attributes); logged-in `admin_post_ink_rapporteer` only (no nopriv); `render()` returns '' for anonymous.
  - [x] `render()` defaults target=`werk`, object_id=current post; pure `toHtml()`; pure `validate()`.
  - [x] `handlePost()`: nonce → honeypot → guards/sanitise → validate → persist (overridable seam) → `do_action('ink/content_reported')` → redirect with notice (returns to the referring work).
  - [x] Wired into `Forms\Module::register()`.
- [x] Task 4: Tests (AC: #7)
  - [x] `tests/Unit/Forms/ReportFormTest.php` (9) + `tests/Unit/Forms/ReportStoreTest.php` (7).
- [x] Task 5: Gates (AC: #9)
  - [x] `composer test:unit` ✓ (1077 passed, 1 skipped, +16); `composer cs` ✓ (object-id via `absint()` for the sanitiser sniff; new files 0/0); `php -l` ✓; `composer stan` ✓ (No errors — simplified the enum coalesce off `?->`); `composer copy:scan` ✓ (8/8 — report copy is concrete Afrikaans); `composer deptrac` — `Forms` stays `[Kernel]`, no new violations.

## Dev Notes

### Why a custom table + private status, not the Report Content plugin
Project-context: "a **custom `ink-core` report form** handles it (no third-party Report Content)" and "Report Content" is on the retired-plugin list. The custom table mirrors the existing `ReactionStore`/`RatingStore`/`PromotionLog` storage pattern and keeps reports queryable for a moderation surface (future). The `ink/content_reported` action is the consumption seam (a moderation queue / notification can hook it without Forms taking a new dependency).

### Architecture compliance (project-context.md)
- **Enums for fixed value sets** — `ReportReason`/`ReportTarget`; the string is the persisted value, never duplicated as literals.
- **Sanctioned write path** — identical shape to `ContactForm` (nonce/honeypot/`is_scalar` guard/sanitise/validate/redirect); logged-in-only (no `nopriv`).
- **Conflation rule** — reporting is open to any lid; zero Tiers/Entitlement. `Forms` stays `[Kernel]`.
- **Custom table** — `dbDelta()` DDL via the Kernel Schema registry, registered at include time in `ink-core.php` (the composition root, outside deptrac).
- **Afrikaans-first** — concrete Afrikaans labels/notices; reasons are glossary-consistent (kwetsend/spam/plagiaat/ander).

### Source tree components to touch
- NEW `src/Forms/ReportReason.php`, `ReportTarget.php`, `ReportStore.php`, `ReportForm.php`
- NEW `tests/Unit/Forms/ReportFormTest.php`, `ReportStoreTest.php`
- UPDATE `src/Forms/Module.php` (register `ReportForm`); `ink-core.php` (schema provider)

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 18.4] — custom ink-core report form (no third-party).
- [Source: wp-content/plugins/ink-core/src/Forms/ContactForm.php] — the form pattern (block + admin-post + validate + honeypot).
- [Source: wp-content/plugins/ink-core/src/Engagement/ReactionStore.php] — the custom-table + Schema-registry pattern.
- [Source: _bmad-output/project-context.md] — no Report Content plugin; enums; conflation rule.

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- `composer test:unit -- --filter="ReportForm|ReportStore"` → 16 passed.
- `composer test:unit` → 1077 passed, 1 skipped.
- `composer stan` (sandbox off) → No errors.

### Completion Notes List

- Custom `ink-core` report path: `ReportForm` (block `ink/rapporteer-vorm`,
  logged-in-only admin-post handler) → `ReportStore` (`ink_reports` custom table) +
  `do_action('ink/content_reported')` seam. No third-party Report Content plugin.
- `ReportReason` (kwetsend/spam/plagiaat/ander) + `ReportTarget` (werk/resensie/
  reaksie) enums are the single source for options + validation + persisted values.
- Same sanctioned write path as `ContactForm` (nonce/honeypot/is_scalar/sanitise/
  validate/redirect); pure `validate()` + `toHtml()` are the tested INK outcomes.
- Conflation-clean (open to any lid); `Forms` deptrac ruleset unchanged.
- A moderation admin-queue UI over `ink_reports` is a natural follow-up (the table +
  `ink/content_reported` seam are in place) — noted for the review/retro.

### File List

- NEW `wp-content/plugins/ink-core/src/Forms/ReportReason.php`
- NEW `wp-content/plugins/ink-core/src/Forms/ReportTarget.php`
- NEW `wp-content/plugins/ink-core/src/Forms/ReportStore.php`
- NEW `wp-content/plugins/ink-core/src/Forms/ReportForm.php`
- NEW `tests/Unit/Forms/ReportFormTest.php`
- NEW `tests/Unit/Forms/ReportStoreTest.php`
- UPDATE `wp-content/plugins/ink-core/src/Forms/Module.php` (register `ReportForm`)
- UPDATE `wp-content/plugins/ink-core/ink-core.php` (ReportStore schema provider)
- UPDATE `_bmad-output/implementation-artifacts/sprint-status.yaml` (18.4 status)
