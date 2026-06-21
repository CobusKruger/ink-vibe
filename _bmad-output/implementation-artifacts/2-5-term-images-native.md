---
baseline_commit: add85da
---

# Story 2.5: Term images native

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a content manager,
I want native term images,
so that the WPCustom Category Image plugin can be retired.

## Acceptance Criteria

1. **A native term-image capability exists on the INK content taxonomies — no third-party plugin.** Given the INK taxonomies, when a content manager adds/edits a `genre`, `vaardigheid` or `uitdagingsrondte` term, then a native "term image" field (an attachment ID) is available on the term add and edit screens with an Afrikaans `ink-core` label, stored as native term meta. This replaces the WPCustom Category Image plugin's capability entirely — nothing depends on that plugin after this story. _[Source: epics.md#Story-2.5 AC "the native term-image capability … the legacy plugin is no longer required"; project-context.md "do not reimplement commodity capabilities" — but the retired-plugin list explicitly includes WPCustom-style add-ons to be replaced natively; "Reactivate retired plugins … ❌"]_

2. **The term image is native term meta — single, integer (attachment ID), REST-aware, sanitised, capability-gated.** Given the capability, when it registers, then it is `register_term_meta( <taxonomy>, 'ink_term_image_id', … )` for each image taxonomy with `single => true`, `type => 'integer'`, `show_in_rest => true` (AD-6), `default => 0`, `sanitize_callback => 'absint'`, and an `auth_callback` gated on `current_user_can( 'manage_categories' )` (the default hierarchical-term management cap). The meta key is an `ink_`-prefixed class constant (single-source); the image taxonomies are sourced from `Taxonomies` constants — never re-typed literals. _[Source: architecture.md AD-6 (`show_in_rest`); project-context.md "All input is sanitised and capability-checked", "Prefix everything ink_/Ink\"; Story 2.2 Taxonomies constants]_

3. **The term add/edit admin UI is native, escaped, and securely saved.** Given the term screens, when a content manager sets the image, then a native field renders via the `{taxonomy}_add_form_fields` / `{taxonomy}_edit_form_fields` hooks (escaped output, an attachment-ID number input — no JS build pipeline / wp.media picker at this substrate level), and saves via `created_{taxonomy}` / `edited_{taxonomy}` with a nonce, `current_user_can( 'manage_categories' )`, and `absint( wp_unslash( $_POST[...] ) )`. The only `$_POST` access is this sanctioned (never raw) save path. _[Source: project-context.md "all input is sanitised and capability-checked; nonces on every state-changing form/AJAX/REST call. No raw $_POST/$_GET"; "All output is escaped at the point of output"; WordPress term-meta admin-form hooks]_

4. **A read API exposes the term image as the cross-module surface; migration reassignment is Epic 16.** Given a term, when a module needs its image, then `Content\Api::termImageId( int $term_id ): int` returns the stored attachment ID (0 if none) — the sole cross-module surface (AD-1), used later by Discovery/Training/Library. This story builds the CAPABILITY; the actual reassignment of the 11 existing term images and the deactivation of the legacy plugin run in the Epic-16 migration, which writes through this same native meta key. _[Source: architecture.md AD-1 (facade discipline); epics.md#Story-2.5 ("when migration runs, the 11 existing term images are reassigned"); docs/migration-plan.md (Epic-16 ordered migration; library/training taxonomy)]_

5. **Registered in `ink-core`/Content, strict, statically verifiable.** Given the new code, when written, then it lives in `Ink\Content` via `Content\Module::register()`, every new `.php` is `<?php` + `declare(strict_types=1)` + `Ink\Content` namespace + `defined('ABSPATH')||exit;`; classes PascalCase, methods camelCase. A ready-to-run Pest test asserts the registration + sanitiser + read API; `php -l`/Pest deferred to the CI buildout (18.8) per the Epic-1 precedent; a python3 structural scan substitutes (asserting the nonce/`wp_unslash`/sanitise/capability wrappers around the single sanctioned `$_POST` site). _[Source: architecture.md AD-1; Implementation Patterns (tests `tests/Unit/{Module}`); project-context.md naming/strict-types; Stories 2.1–2.4 (registrar + python3-scan precedent)]_

## Tasks / Subtasks

> **Current state (read before starting):** Story 2.1–2.4 (review) built `Ink\Content` with `PostTypes` (9 CPTs), `Taxonomies` (4 taxonomies incl. constants `Taxonomies::GENRE`, `VAARDIGHEID`, `UITDAGINGSRONDTE`, `STER_GRADERING`), `UserMeta` (2 user-meta keys), and `FieldSets` (per-CPT meta boxes + the secure-`$_POST`-save pattern to mirror). `Content\Module::register()` calls `PostTypes` → `Taxonomies` → `UserMeta` → `FieldSets` on `init`; `Content\Api` exposes `all()`/`bydraeTypes()`/`taxonomies()`/`userMetaKeys()`/`fieldMetaKeys()`. There is NO JS build pipeline — render an attachment-ID number input (a wp.media picker is a later UX nicety, not this substrate story). No PHP binary / built `vendor/` — verification is the python3 scan + ready-to-run Pest (Epic-1 precedent). Scope is the native term-image CAPABILITY ONLY — the reassignment of the 11 existing images + legacy-plugin deactivation is the Epic-16 migration (do NOT script migration here), and there is no front-end rendering of the image (Epics 8/11).

- [x] **Task 1 — Add the term-image registrar + meta-key single-source (AC: 1, 2)**
  - [x] Create `src/Content/TermImages.php` — `namespace Ink\Content; final class TermImages` (strict types + `ABSPATH` guard). Declare `public const META_KEY = 'ink_term_image_id';` (single-source). Declare `private const NONCE_ACTION`/`NONCE_NAME` for the save round-trip.
  - [x] `private static function imageTaxonomies(): array` — the taxonomies that carry a term image, sourced from `Taxonomies` constants: `Taxonomies::GENRE`, `Taxonomies::VAARDIGHEID`, `Taxonomies::UITDAGINGSRONDTE` (the public content taxonomies; `ster_gradering` is a rating, no image). Never re-type taxonomy literals.
  - [x] `public static function imageTaxonomyList(): array` — public accessor (facade/test surface).

- [x] **Task 2 — Register the term meta + bind the admin-form hooks (AC: 2, 3)**
  - [x] `public function register(): void` — for each image taxonomy: `register_term_meta( $tax, self::META_KEY, array( 'single' => true, 'type' => 'integer', 'show_in_rest' => true, 'default' => 0, 'sanitize_callback' => 'absint', 'auth_callback' => static fn (): bool => current_user_can( 'manage_categories' ) ) )`; then `add_action( "{$tax}_add_form_fields", array( $this, 'renderAddField' ) )`, `add_action( "{$tax}_edit_form_fields", array( $this, 'renderEditField' ), 10, 2 )`, `add_action( "created_{$tax}", array( $this, 'save' ) )`, `add_action( "edited_{$tax}", array( $this, 'save' ) )`. Invoked on `init` from `Module::register()`.

- [x] **Task 3 — Render the native fields (AC: 3)**
  - [x] `public function renderAddField(): void` — output `wp_nonce_field(...)` + a `<div class="form-field">` with a `<label>` ("Termbeeld (heg-ID)", Afrikaans literal) and a number `<input name="ink_term_image_id">`. Escaped output.
  - [x] `public function renderEditField( \WP_Term $term ): void` — the edit-screen `<tr class="form-field">` variant, pre-filled from `get_term_meta( $term->term_id, self::META_KEY, true )`, `esc_attr`-escaped, with the nonce.

- [x] **Task 4 — Secure save (AC: 3)**
  - [x] `public function save( int $term_id ): void` — early-return unless the nonce is present and valid (`wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION )`) and `current_user_can( 'manage_categories' )`. If `ink_term_image_id` is present in `$_POST`, `update_term_meta( $term_id, self::META_KEY, absint( wp_unslash( $_POST['ink_term_image_id'] ) ) )`. The sole `$_POST` site; never raw; no `$_GET`/`$_REQUEST`.

- [x] **Task 5 — Read API + facade (AC: 4)**
  - [x] `public static function imageId( int $term_id ): int` — `return (int) get_term_meta( $term_id, self::META_KEY, true );` (0 if unset).
  - [x] Edit `src/Content/Module.php`: `register()` calls `( new TermImages() )->register();` after `FieldSets`. Update the doc-comment (2.5 live; Epic 2 content-model stories complete).
  - [x] Edit `src/Content/Api.php`: add `Api::termImageId( int $term_id ): int` → `TermImages::imageId( $term_id )` and `Api::termImageTaxonomies(): array` → `TermImages::imageTaxonomyList()`.
  - [x] No `ink-core.php` change.

- [x] **Task 6 — Author the ready-to-run Pest unit test (AC: 1–4)**
  - [x] Create `tests/Unit/Content/TermImagesTest.php` (Brain Monkey): alias `register_term_meta` to capture `(taxonomy, key, args)`; stub `add_action`/`__`/`absint`; assert the meta registers against exactly `genre`/`vaardigheid`/`uitdagingsrondte` with key `ink_term_image_id`, `single`/`show_in_rest` true, `type` integer, `default` 0, `sanitize_callback` `'absint'`, callable `auth_callback`; `ster_gradering` is NOT an image taxonomy; `imageId()` returns `(int) get_term_meta(...)` (stub `get_term_meta` → `'7'`, expect `7`; → `''`, expect `0`); `Api::termImageId()`/`Api::termImageTaxonomies()` delegate.

- [x] **Task 7 — Static verification (no PHP binary — Epic-1 precedent) (AC: 1–5)**
  - [x] python3 scan: structure on all new/edited `ink-core` files; `META_KEY = 'ink_term_image_id'` constant; image taxonomies sourced from `Taxonomies` constants (no `'genre'`/`'vaardigheid'`/`'uitdagingsrondte'` literals in code); `register_term_meta` with `single`/`show_in_rest`/`type integer`/`sanitize_callback`/`auth_callback`/`manage_categories`; `{tax}_add_form_fields`/`{tax}_edit_form_fields`/`created_`/`edited_` hooks; render escapes (`esc_attr`) + `wp_nonce_field`; save verifies nonce + `current_user_can('manage_categories')`, every `$_POST` isset-guarded or `wp_unslash`-wrapped (no raw), no `$_GET`/`$_REQUEST`; `imageId()` reads `get_term_meta`; `Module::register` calls `TermImages` after `FieldSets`; `Api::termImageId`/`termImageTaxonomies` present; no migration code (no `wp_insert_term`/loop over "11"), no `register_post_meta`/`register_taxonomy`; theme untouched. Record `php -l`/Pest deferral.

## Dev Notes

### What this story is (and is NOT)

- **IS:** a native term-image capability (`ink_term_image_id` term meta + native add/edit admin fields + secure save + a read API) on `genre`/`vaardigheid`/`uitdagingsrondte`, replacing the WPCustom Category Image plugin, in `Ink\Content`. _[epics.md#Story-2.5; AD-1/AD-6]_
- **IS NOT:** the Epic-16 migration that reassigns the 11 existing images / deactivates the legacy plugin (this story only builds the native target the migration writes into), a wp.media JS image picker (attachment-ID number input at this substrate level), front-end rendering of term images (Epics 8/11), or term images on `ster_gradering` (a rating). No theme changes.

### ⭐ The mechanism (key deliverable)

- **Native term meta:** `register_term_meta(<tax>, 'ink_term_image_id', …)` per image taxonomy — single integer attachment ID, REST-aware, `absint`-sanitised, `manage_categories`-gated. The legacy plugin's capability is fully replaced by core term meta.
- **Native admin UI:** the `{taxonomy}_add_form_fields` / `{taxonomy}_edit_form_fields` + `created_/edited_{taxonomy}` hooks render and save the field — no plugin, no JS build. Output escaped; save is the sanctioned `$_POST` pattern (nonce + cap + `wp_unslash` + `absint`).
- **Read surface:** `Content\Api::termImageId( $term_id )` is the sole cross-module accessor; the Epic-16 migration writes the 11 images through the same `ink_term_image_id` key, so the data lands where every consumer already reads.

### ⚠️ Guardrails

- **`$_POST` only in `save()`, sanctioned** — nonce + `manage_categories` + `wp_unslash` + `absint`; no bare `$_POST`, no `$_GET`/`$_REQUEST`. _[project-context.md]_
- **Escape at output** in the render callbacks (`esc_attr`). _[project-context.md]_
- **Taxonomy slugs from `Taxonomies` constants; meta key is the `TermImages` constant** — no re-typed literals. _[single-source]_
- **No `__( $var )`**; field labels are generic Afrikaans admin literals. _[AD-10]_
- **No migration here** — the reassignment of the 11 images + legacy-plugin deactivation is Epic 16. This story builds the capability only. _[epics.md]_
- **strict types + `ABSPATH` guard + `Ink\Content` namespace** in every new file. _[project-context.md]_

### Project Structure Notes

- New `src/Content/TermImages.php` (registrar + admin fields + save + read API); edits to `src/Content/Module.php` (delegate to `TermImages` after `FieldSets`), `src/Content/Api.php` (facade exposes `termImageId()`/`termImageTaxonomies()`). Test at `tests/Unit/Content/TermImagesTest.php`.
- Completes the `Module → collaborator` set for Epic 2 (`PostTypes` + `Taxonomies` + `UserMeta` + `FieldSets` + `TermImages`).

### Previous story intelligence (Stories 2.1–2.4)

- Mirror `FieldSets` (2.4) for the secure-`$_POST`-save + escaped-render + nonce pattern; the term-meta admin hooks differ from post meta-boxes (`{taxonomy}_add_form_fields` has no args; `{taxonomy}_edit_form_fields` receives the `WP_Term`; `created_/edited_{taxonomy}` receive `$term_id`).
- Registrar pattern: `final class` in `Ink\Content`, `public const` single-source key, `public static` accessor, `public function register()` binding hooks. Image taxonomies come from `Taxonomies::GENRE` etc.
- Test pattern: alias `register_term_meta` to capture args; stub `get_term_meta` for the `imageId()` test; stub `absint`/`add_action`/`__`.
- python3 scan precedent (2.1 53/53 … 2.4 67/67): regex for aligned `=>`; absence checks against a comment-stripped copy; EXEMPT `save()`'s sanctioned `$_POST` from the blanket superglobal ban (assert the safety wrappers instead).

### Testing standards summary

No PHP binary / built `vendor/` (CI harness at 18.8). `php -l`/Pest/PHPStan deferred (Epic-1 precedent); python3 structural scan substitutes; `TermImagesTest.php` runs once the runner is wired. Brain Monkey stubs `register_term_meta`/`add_action`/`get_term_meta`/`absint`/`__`. Tests live at the repo-root `tests/` tree.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-2.5 — Term images native; AC: native term-image capability; when migration runs the 11 existing term images are reassigned and the legacy plugin is no longer required]
- [Source: _bmad-output/planning-artifacts/architecture.md — AD-1 (Content owns meta; facade discipline); AD-6 (`show_in_rest`); module tree (`Content/ # Epic 2 — CPTs, taxonomies, meta`)]
- [Source: _bmad-output/project-context.md — "All output is escaped at the point of output; all input is sanitised and capability-checked; nonces … No raw $_POST/$_GET"; retired-plugins list (replace WPCustom-style add-ons natively; never reactivate); "Prefix everything ink_/Ink\"; "declare(strict_types=1)"; three-layer]
- [Source: docs/migration-plan.md — Epic-16 ordered migration; library/training taxonomy; the 11 term images reassigned during migration through the native key]
- [Source: wp-content/plugins/ink-core/src/Content/Taxonomies.php (taxonomy slug constants `GENRE`/`VAARDIGHEID`/`UITDAGINGSRONDTE`); src/Content/FieldSets.php (the secure-$_POST-save + escaped-render + nonce pattern to mirror); src/Content/Module.php + Api.php (wired module + facade to extend)]
- [Source: tests/Unit/Content/FieldSetsTest.php (capture-and-assert + sanitiser test pattern to mirror)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m] (Opus 4.8, 1M context)

### Debug Log References

- python3 structural scan (`scan_2_5.py`, run from the job tmp dir): **61/61 checks passed** — structure on all files; `META_KEY = 'ink_term_image_id'`; image taxonomies from `Taxonomies::GENRE`/`VAARDIGHEID`/`UITDAGINGSRONDTE` (no re-typed literals); `register_term_meta` with `single`/`type integer`/`show_in_rest`/`default 0`/`sanitize_callback absint`/`auth_callback manage_categories`; `{tax}_add_form_fields`/`{tax}_edit_form_fields`/`created_`/`edited_` hooks; render escapes (`esc_attr`/`esc_html`) + `wp_nonce_field`; save verifies nonce + `manage_categories`, every `$_POST` isset-guarded or `wp_unslash`+`absint`-wrapped (no raw), no `$_GET`/`$_REQUEST`; `imageId()` reads `get_term_meta`; no migration/`wp_insert_term`, no `register_post_meta`/`register_taxonomy`; `Module::register` calls `TermImages` after `FieldSets`; `Api::termImageId`/`termImageTaxonomies` present; theme untouched. (Absence checks run against a comment-stripped copy.)

### Completion Notes List

- **Registrar (`TermImages`)** registers `ink_term_image_id` term meta (single, integer attachment ID, `show_in_rest`, default 0, `absint` sanitiser, `manage_categories` auth gate) on `genre`/`vaardigheid`/`uitdagingsrondte` (sourced from `Taxonomies` constants; `ster_gradering` excluded as a rating). The meta key is a single-source `public const`.
- **Native admin UI (AC-3):** the core `{tax}_add_form_fields` / `{tax}_edit_form_fields` hooks render an escaped attachment-ID number input (with `wp_nonce_field`); `created_{tax}` / `edited_{tax}` save. No plugin, no JS build — replaces the WPCustom Category Image capability.
- **Secure save (AC-3):** `save()` is the sole `$_POST` site — nonce verify → `current_user_can('manage_categories')` → `absint( wp_unslash( … ) )` → `update_term_meta`. Never a raw superglobal; no `$_GET`/`$_REQUEST`.
- **Read API (AC-4):** `TermImages::imageId( $term_id )` → `(int) get_term_meta(...)`; `Content\Api::termImageId()` / `Api::termImageTaxonomies()` are the cross-module surface. The Epic-16 migration reassigns the 11 existing images by writing the same `ink_term_image_id` key, so consumers read one surface.
- **Wiring (AC-5):** `Content\Module::register()` calls `TermImages` after `FieldSets`; the Epic-2 content models are now complete (CPTs, taxonomies, user meta, field sets, term images). No `ink-core.php` change. No theme changes.
- **Scope discipline:** the native capability only — no Epic-16 migration scripting (no `wp_insert_term` / loop over "11"), no wp.media JS picker (attachment-ID number input at substrate level), no front-end rendering, no term image on the rating taxonomy.
- **Verification:** `php -l`/Pest/PHPStan deferred to Story 18.8 (Epic-1 precedent). The ready-to-run `TermImagesTest.php` (Brain Monkey captures `register_term_meta`; stubs `get_term_meta` for `imageId()`) runs once the runner is wired; the python3 scan substitutes now (61/61).

### File List

- `wp-content/plugins/ink-core/src/Content/TermImages.php` (new) — the native term-image registrar + admin fields + secure save + read API.
- `wp-content/plugins/ink-core/src/Content/Module.php` (modified) — `register()` delegates to `TermImages` after `FieldSets`.
- `wp-content/plugins/ink-core/src/Content/Api.php` (modified) — facade exposes `termImageId()` / `termImageTaxonomies()`.
- `tests/Unit/Content/TermImagesTest.php` (new) — ready-to-run Pest unit test.

## Change Log

| Date | Change |
|---|---|
| 2026-06-21 | Story created (context-engineered) — native term-image capability (`ink_term_image_id` term meta + native add/edit admin fields + secure save + `Content\Api::termImageId()` read API) on `genre`/`vaardigheid`/`uitdagingsrondte`, replacing the WPCustom Category Image plugin. The Epic-16 migration reassigns the 11 existing images through the same native key; no migration/JS-picker here. Status → ready-for-dev. |
| 2026-06-21 | Story implemented — `TermImages` registrar (`ink_term_image_id` on 3 taxonomies), native add/edit admin fields (escaped + nonce), secure save (nonce+`manage_categories`+`wp_unslash`+`absint`), `imageId()` read API, Module/Api wiring, ready-to-run Pest test. python3 scan 61/61 (`php -l`/Pest deferred to 18.8). Status → review. |
