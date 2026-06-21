---
baseline_commit: 319e685
---

# Story 2.4: CPT admin field sets

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a redakteur,
I want per-CPT meta fields in admin,
so that I can manage issue/challenge/sponsor details.

## Acceptance Criteria

1. **Each relevant CPT exposes its editorial meta on the edit screen, in Afrikaans.** Given the `inkpols_uitgawe`, `uitdaging`, and `borg` edit screens, when a redakteur edits an item, then the right meta is available with Afrikaans `ink-core` labels: **InkPols** issue date / volume / cover / PDF / teaser; **challenge** theme / deadline; **sponsor** link / tier / dates / placement. The fields are rendered in a native meta box on each CPT's edit screen (server-rendered PHP — works in the block editor, no JS build pipeline exists). _[Source: epics.md#Story-2.4 AC "the right meta is available (e.g. InkPols issue date/volume/cover/PDF/teaser; challenge theme/deadline; sponsor link/tier/dates/placement) with Afrikaans `ink-core` labels"; project-context.md admin-language split ("All `ink-core` admin surfaces … = Afrikaans"); Block theme, no ACF/CMB2 in the plugin set]_

2. **Every field is registered post meta — typed, REST-aware, sanitised, capability-gated.** Given each field, when it registers, then it is `register_post_meta( <cpt>, <key>, … )` with `single => true`, the correct `type`, `show_in_rest => true` (AD-6), a `sanitize_callback`, a `default`, and an `auth_callback` gated on the CPT's editorial capability (`uitdaging` → `MANAGE_CHALLENGES`, `borg` → `MANAGE_SPONSORS`, `inkpols_uitgawe` → `edit_posts`). All meta keys are `ink_`-prefixed class constants (single-source), grouped per CPT, with the CPT slug sourced from `PostTypes` constants — never a re-typed literal. _[Source: architecture.md AD-6 (`show_in_rest`, three-tier capability check `current_user_can('ink_{cap}')`); src/Kernel/Capabilities.php (`MANAGE_CHALLENGES`, `MANAGE_SPONSORS`); project-context.md "All input is sanitised and capability-checked", "Prefix everything ink_/Ink\"; Story 2.1 PostTypes constants]_

3. **Admin field-set labels are sourced from the terminology registry where they map to glossary concepts; the rest are generic Afrikaans admin chrome.** Given the rendered labels, when they display, then the meta-box title uses the CPT noun from `Ink\I18n\Terms` (e.g. the `borg` box title around `Terms::label('borg')`), and individual field labels (`Uitgawedatum`, `Tema`, `Sperdatum`, `Skakel`, …) — which are NOT controlled-vocabulary glossary concepts — are generic `ink-core`-domain Afrikaans `__()` literals (source language Afrikaans, no English `.mo`). `__()` is never wrapped around a variable. _[Source: epics.md#Story-2.4 AC "admin field-set labels are sourced from the terminology registry (Story 2.0) where they map to glossary concepts"; architecture.md AD-10; project-context.md "Controlled-vocabulary UI labels come from the ink-core terminology registry … Never inline a glossary label as a bare literal outside the registry" (field labels here are admin chrome, not glossary concepts)]_

4. **The meta-box save path is secure: nonce + capability + sanitise; no other CPTs touched.** Given a meta-box save, when the form posts back (Gutenberg posts classic meta boxes to `post.php`), then the handler verifies a nonce, checks `current_user_can( 'edit_post', $post_id )`, skips autosave/revisions, and writes each field via `update_post_meta` after `wp_unslash` + the field's `sanitize_*` — the only `$_POST` access in the codebase, and it is the sanctioned (never raw) WP pattern. Output in the render callback is escaped (`esc_attr`/`esc_textarea`/`esc_html`). No field set is attached to any CPT other than the three named. _[Source: project-context.md "all input is sanitised and capability-checked; nonces on every state-changing form/AJAX/REST call. No raw $_POST/$_GET"; "All output is escaped at the point of output"; WordPress meta-box save contract (Gutenberg posts classic meta boxes back to post.php)]_

5. **Registered in `ink-core`/Content, strict, statically verifiable.** Given the new code, when written, then it lives in `Ink\Content` via `Content\Module::register()`, the Content facade (`Content\Api`) exposes the per-CPT meta-key surface (AD-1), every new `.php` is `<?php` + `declare(strict_types=1)` + `Ink\Content` namespace + `defined('ABSPATH')||exit;`; classes PascalCase, methods camelCase. A ready-to-run Pest test asserts the definitions + sanitisers; `php -l`/Pest deferred to the CI buildout (18.8) per the Epic-1 precedent; a python3 structural scan substitutes (asserting the nonce/`wp_unslash`/sanitise/capability safety wrappers around the single sanctioned `$_POST` site). _[Source: architecture.md AD-1; Implementation Patterns (tests `tests/Unit/{Module}`); project-context.md naming/strict-types; Stories 2.1–2.3 (registrar + python3-scan precedent)]_

## Tasks / Subtasks

> **Current state (read before starting):** Story 2.1–2.3 (review) built `Ink\Content` with `PostTypes` (9 CPTs incl. `PostTypes::INKPOLS_UITGAWE`, `UITDAGING`, `BORG`), `Taxonomies` (4), and `UserMeta` (2 user-meta keys). `Content\Module::register()` calls `PostTypes` → `Taxonomies` → `UserMeta` on `init`; `Content\Api` exposes `all()`/`bydraeTypes()`/`taxonomies()`/`userMetaKeys()`. The Kernel owns `Ink\Kernel\Capabilities` (`MANAGE_CHALLENGES = 'ink_manage_challenges'`, `MANAGE_SPONSORS = 'ink_manage_sponsors'`); `Ink\I18n\Terms` has the CPT nouns (`borg`, `uitdaging`, `inkpols_uitgawe`, …). There is NO fields plugin (ACF/CMB2) and NO JS build pipeline — use native `register_post_meta` + classic `add_meta_box` (server-rendered PHP; Gutenberg renders classic boxes and posts them back to `post.php`). No PHP binary / built `vendor/` — verification is the python3 scan + ready-to-run Pest (Epic-1 precedent). Scope is the three named CPTs' field sets ONLY — no native term images (2.5), no `SponsorTier` enum (Epic 14 — model `borg` tier/placement as sanitised text for now), no front-end rendering (Epics 13/14), no media-picker JS (a number attachment-ID input suffices for cover/PDF at the substrate level).

- [x] **Task 1 — Define the per-CPT field sets + meta-key single-source (AC: 1, 2, 3)**
  - [x] Create `src/Content/FieldSets.php` — `namespace Ink\Content; final class FieldSets` (strict types + `ABSPATH` guard). Declare an `ink_`-prefixed class constant per meta key, grouped by CPT:
    - InkPols: `INKPOLS_ISSUE_DATE = 'ink_inkpols_issue_date'`, `INKPOLS_VOLUME = 'ink_inkpols_volume'`, `INKPOLS_COVER_ID = 'ink_inkpols_cover_id'`, `INKPOLS_PDF_ID = 'ink_inkpols_pdf_id'`, `INKPOLS_TEASER = 'ink_inkpols_teaser'`.
    - Challenge: `UITDAGING_THEME = 'ink_uitdaging_theme'`, `UITDAGING_DEADLINE = 'ink_uitdaging_deadline'`.
    - Sponsor: `BORG_LINK = 'ink_borg_link'`, `BORG_TIER = 'ink_borg_tier'`, `BORG_START_DATE = 'ink_borg_start_date'`, `BORG_END_DATE = 'ink_borg_end_date'`, `BORG_PLACEMENT = 'ink_borg_placement'`.
  - [x] `private static function definitions(): array` — keyed by CPT slug (`PostTypes::INKPOLS_UITGAWE` / `UITDAGING` / `BORG` constants, never literals). Each CPT entry: a `cap` (the auth/edit capability) and a `fields` list, each field = `key` (constant), `label` (generic Afrikaans `__()` literal — `Uitgawedatum`, `Volume`, `Omslagbeeld`, `PDF`, `Voorskou-teks`, `Tema`, `Sperdatum`, `Skakel`, `Borgvlak`, `Begindatum`, `Einddatum`, `Plasing`), `type` (`'string'`/`'integer'`), `input` (`'date'`/`'text'`/`'number'`/`'url'`/`'textarea'`/`'datetime-local'`), `sanitize` (a callable name).
  - [x] `public static function metaKeys(): array` — all field keys (the facade surface).

- [x] **Task 2 — Register the post meta (AC: 2)**
  - [x] `public function register(): void` — for each CPT/field, call `register_post_meta( $cpt, $field['key'], array( 'single' => true, 'type' => $field['type'], 'show_in_rest' => true, 'default' => …, 'sanitize_callback' => $field['sanitize'], 'auth_callback' => static fn (): bool => current_user_can( $cap ) ) )`. Then `add_action( 'add_meta_boxes', array( $this, 'addMetaBoxes' ) )` and `add_action( 'save_post', array( $this, 'save' ), 10, 2 )`. Invoked on `init` from `Module::register()`.
  - [x] Map caps: `uitdaging` → `Capabilities::MANAGE_CHALLENGES`; `borg` → `Capabilities::MANAGE_SPONSORS`; `inkpols_uitgawe` → `'edit_posts'` (no dedicated cap).

- [x] **Task 3 — Render the meta boxes (AC: 1, 3, 4)**
  - [x] `public function addMetaBoxes(): void` — loop definitions; `add_meta_box( "ink_{$cpt}_besonderhede", $title, array( $this, 'renderBox' ), $cpt, 'normal', 'high', array( 'cpt' => $cpt ) )`. The `$title` composes the CPT noun from `Terms::label()` (e.g. `sprintf( __( '%s — besonderhede', 'ink-core' ), Terms::label( <singular key> ) )`).
  - [x] `public function renderBox( \WP_Post $post, array $box ): void` — output `wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME )` then, per field, a `<label>` (the field label) + an input pre-filled from `get_post_meta( $post->ID, $key, true )`, **escaped at output** (`esc_attr` for inputs/`<label for>`, `esc_textarea` for the teaser, `esc_html` for the label text). No inline `<script>`.

- [x] **Task 4 — Secure save handler (AC: 4)**
  - [x] `public function save( int $post_id, \WP_Post $post ): void` — early-return on: missing/invalid nonce (`isset` + `wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION )`), `wp_is_post_autosave`/`wp_is_post_revision`, a post type not in the definitions, and `! current_user_can( 'edit_post', $post_id )`. Then for each field of that CPT: if the key is present in `$_POST`, `update_post_meta( $post_id, $key, <field sanitize>( wp_unslash( $_POST[ $key ] ) ) )`. This is the ONLY `$_POST` site — the sanctioned nonce+unslash+sanitise+capability pattern, never raw.

- [x] **Task 5 — Field sanitisers (AC: 2, 4)**
  - [x] Private static sanitiser methods (or map to WP core ones): text/theme/volume/tier/placement → `sanitize_text_field`; teaser → `sanitize_textarea_field`; link → `esc_url_raw`; cover/pdf id → `absint`; date/deadline → a small `sanitizeDate` (allow `Y-m-d` / datetime-local, else `''`) or `sanitize_text_field`. Keep each referenced by callable so the test can invoke it directly.

- [x] **Task 6 — Wire the module seam + facade (AC: 5)**
  - [x] Edit `src/Content/Module.php`: `register()` calls `( new FieldSets() )->register();` after `UserMeta`. Update the doc-comment (2.4 live; reserved note now only term images 2.5).
  - [x] Edit `src/Content/Api.php`: add `Api::fieldMetaKeys(): array` delegating to `FieldSets::metaKeys()`.
  - [x] No `ink-core.php` change.

- [x] **Task 7 — Author the ready-to-run Pest unit test (AC: 1–4)**
  - [x] Create `tests/Unit/Content/FieldSetsTest.php` (Brain Monkey): alias `register_post_meta` to capture `(cpt, key, args)`; stub `add_action`/`__`; assert the meta registers against exactly `inkpols_uitgawe`/`uitdaging`/`borg`; the expected keys per CPT are present (issue_date/volume/cover_id/pdf_id/teaser; theme/deadline; link/tier/start/end/placement); each is `single`/`show_in_rest` true with a `sanitize_callback` and `auth_callback`; invoking representative captured `sanitize_callback`s coerces correctly (e.g. cover_id `'12abc'` → `12` via absint stub, link sanitised); `Api::fieldMetaKeys()` returns all keys; no meta registers against a bydrae CPT.

- [x] **Task 8 — Static verification (no PHP binary — Epic-1 precedent) (AC: 1–5)**
  - [x] python3 scan: structure (`<?php`/one `declare`/`ABSPATH`/balanced braces/no `?>`) on all new/edited `ink-core` files; all `ink_`-prefixed field constants present; CPT slugs sourced from `PostTypes` constants (no `'inkpols_uitgawe'`/`'uitdaging'`/`'borg'` literals in code); `register_post_meta` looped with `show_in_rest`/`single`/`sanitize_callback`/`auth_callback`; caps via `Capabilities::MANAGE_CHALLENGES`/`MANAGE_SPONSORS`; meta-box title via `Terms::label`; `add_meta_box` + `save_post`; the save handler verifies a nonce (`wp_verify_nonce`), checks `current_user_can( 'edit_post'`, guards autosave/revision, and every `$_POST` read is wrapped in `wp_unslash` + a sanitiser (assert no bare `$_POST[...]` without `wp_unslash`); no `$_GET`/`$_REQUEST`; render output escaped (`esc_attr`/`esc_textarea`); no `__( $var )`; `Module::register` calls `FieldSets` after `UserMeta`; `Api::fieldMetaKeys` present; no `register_taxonomy`/term-image/`SponsorTier` code; theme untouched. Record `php -l`/Pest deferral.

## Dev Notes

### What this story is (and is NOT)

- **IS:** per-CPT editorial meta + native admin meta boxes for `inkpols_uitgawe` (issue date/volume/cover/PDF/teaser), `uitdaging` (theme/deadline), `borg` (link/tier/dates/placement), all `register_post_meta` (typed, REST, sanitised, capability-gated), Afrikaans labels, secure save (nonce+cap+sanitise), in `Ink\Content`, facade exposes the keys. _[epics.md#Story-2.4; AD-1/AD-6]_
- **IS NOT:** native term images (2.5), a `SponsorTier` enum or sponsor scheduling/rotation (Epic 14), challenge entry/pool logic (Epic 12), InkPols issue archive / Real3D Flipbook viewer (Epic 13), a wp.media JS picker (cover/PDF are attachment-ID number inputs at this substrate level), front-end rendering of any field, or fields on the bydrae/library/training CPTs. No theme changes.

### ⭐ The mechanism (key deliverable)

- **Declarative field sets:** one `definitions()` map (CPT → cap + fields) drives registration, rendering, and saving — add a field in one place. CPT slugs come from `PostTypes` constants; meta keys are `FieldSets` constants (single-source).
- **Native, JS-free admin UI:** classic `add_meta_box` renders server-side and Gutenberg shows it + posts it back to `post.php`, so the `save_post` handler is required. No build pipeline, no ACF.
- **Security at the save seam:** nonce verify → autosave/revision guard → `current_user_can('edit_post', $id)` → per-field `wp_unslash` + `sanitize_*` → `update_post_meta`. This is the project's ONLY sanctioned `$_POST` site; it is never raw. Render escapes at output.
- **Label sourcing:** meta-box titles use the CPT noun from `Terms`; field labels are generic Afrikaans admin literals (not glossary concepts), per AC-3.

### ⚠️ Guardrails

- **`$_POST` only in `save()`, only sanctioned** — nonce + capability + `wp_unslash` + `sanitize_*`; never a bare `$_POST[...]`. No `$_GET`/`$_REQUEST`. _[project-context.md]_
- **Escape at output** in `renderBox()` (`esc_attr`/`esc_textarea`/`esc_html`). _[project-context.md]_
- **CPT slugs from `PostTypes` constants; meta keys are `FieldSets` constants** — no re-typed literals. _[single-source]_
- **No `__( $var )`** — meta-box titles use `sprintf( __( '…%s…', 'ink-core' ), Terms::label(...) )`. _[AD-10]_
- **No `SponsorTier` enum / scheduling** — Epic 14; model tier/placement as sanitised text. **No term images** — 2.5. _[epics.md]_
- **Field sets attach to ONLY the three named CPTs.** _[AC-4]_
- **strict types + `ABSPATH` guard + `Ink\Content` namespace** in every new file. _[project-context.md]_

### Project Structure Notes

- New `src/Content/FieldSets.php` (registrar + meta boxes + save); edits to `src/Content/Module.php` (delegate to `FieldSets` after `UserMeta`), `src/Content/Api.php` (facade exposes `fieldMetaKeys()`). Test at `tests/Unit/Content/FieldSetsTest.php`.
- Mirrors the `Module → collaborator` house style (now `PostTypes` + `Taxonomies` + `UserMeta` + `FieldSets`).

### Previous story intelligence (Stories 2.1–2.3)

- Registrar pattern: `final class` in `Ink\Content`, `public const` single-source IDs, `public static` facade-surface accessor, `public function register()`, private static `definitions()`/helpers. This story adds two hook callbacks (`addMetaBoxes`, `save`) — bind them with `add_action` inside `register()`.
- Test pattern: alias the WP registrar via `Functions\when(...)->alias(...)` to capture args; call captured `sanitize_callback`s directly. For this story also `Functions\when('add_action')->justReturn(true)` and stub `absint`/`esc_url_raw`/`sanitize_text_field` as needed (Brain Monkey passthrough) so the registration path runs without WP.
- python3 scan precedent (2.1 53/53, 2.2 52/52, 2.3 45/45): use regex for aligned `=>` entries; run absence checks against a comment-stripped copy so doc references don't false-positive. **New for 2.4:** the no-superglobal check must EXEMPT `save()`'s sanctioned `$_POST` (assert the safety wrappers instead) — do not blanket-forbid `$_POST` in `FieldSets.php`.

### Testing standards summary

No PHP binary / built `vendor/` (CI harness at 18.8). `php -l`/Pest/PHPStan deferred (Epic-1 precedent); python3 structural scan substitutes; `FieldSetsTest.php` runs once the runner is wired. Brain Monkey stubs `register_post_meta`/`add_action`/`__` and the sanitiser core functions. Tests live at the repo-root `tests/` tree.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-2.4 — CPT admin field sets; AC: the right meta available (InkPols issue date/volume/cover/PDF/teaser; challenge theme/deadline; sponsor link/tier/dates/placement) with Afrikaans `ink-core` labels; labels from the terminology registry where they map to glossary concepts]
- [Source: _bmad-output/planning-artifacts/architecture.md — AD-1 (Content owns meta; facade discipline); AD-6 (`show_in_rest`, three-tier capability check); AD-10 (labels via registry); module tree (`Content/ # Epic 2 — CPTs, taxonomies, meta`); Kernel/Enum notes a planned `SponsorTier` (NOT yet created — Epic 14)]
- [Source: _bmad-output/project-context.md — "All output is escaped at the point of output; all input is sanitised and capability-checked; nonces on every state-changing form/AJAX/REST call. No raw $_POST/$_GET"; admin-language split (ink-core admin = Afrikaans); "Prefix everything ink_/Ink\"; "declare(strict_types=1)"; three-layer (content models in ink-core, never the theme)]
- [Source: wp-content/plugins/ink-core/src/Content/PostTypes.php (CPT slug constants `INKPOLS_UITGAWE`/`UITDAGING`/`BORG`); src/Kernel/Capabilities.php (`MANAGE_CHALLENGES`/`MANAGE_SPONSORS`); src/I18n/Terms.php (CPT nouns for box titles); src/Content/Module.php + Api.php (wired module + facade to extend)]
- [Source: tests/Unit/Content/PostTypesTest.php / TaxonomiesTest.php / UserMetaTest.php (capture-and-assert + sanitize-callback test patterns to mirror)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m] (Opus 4.8, 1M context)

### Debug Log References

- python3 structural scan (`scan_2_4.py`, run from the job tmp dir): **67/67 checks passed** — structure on all files; 12 `ink_`-prefixed field constants; CPT slugs passed to `register_post_meta`/`add_meta_box` via the `$cpt` loop variable (not literals; `'term' => '…'` are Terms concept keys, the established literal-key pattern); `register_post_meta` with `single`/`show_in_rest`/`sanitize_callback`/`auth_callback`; caps `MANAGE_CHALLENGES`/`MANAGE_SPONSORS`; box title via `Terms::label`; `add_meta_boxes`+`save_post` hooks; render escapes (`esc_attr`/`esc_textarea`/`esc_html`) + `wp_nonce_field`; save verifies nonce + `current_user_can('edit_post')` + autosave/revision guard, every `$_POST` read isset-guarded or `wp_unslash`-wrapped (no raw `$_POST`), no `$_GET`/`$_REQUEST`; no `register_taxonomy`/`SponsorTier`/term-image code; `Module::register` calls `FieldSets` after `UserMeta`; `Api::fieldMetaKeys()` present; theme untouched. (Absence checks run against a comment-stripped copy.)

### Completion Notes List

- **Declarative `FieldSets`** drives registration + rendering + saving from one `definitions()` map (CPT → cap + term key + fields), keyed by `PostTypes` slug constants; 12 `ink_`-prefixed meta keys as `public const` single-source. `metaKeys()` is the facade surface.
- **Registration (AC-2):** each field is `register_post_meta( $cpt, $key, … )` with `single`, correct `type` (`integer` for cover/PDF attachment IDs, else `string`), `show_in_rest`, a `default` (0 for integers, '' otherwise), a per-field `sanitize_callback`, and an `auth_callback` gated on the CPT cap (`uitdaging`→`MANAGE_CHALLENGES`, `borg`→`MANAGE_SPONSORS`, `inkpols_uitgawe`→`edit_posts`).
- **Admin UI (AC-1/AC-3):** classic `add_meta_box` per CPT (Gutenberg renders it). Box title composes the CPT noun from `Terms::label()`; field labels are generic Afrikaans admin literals (not glossary concepts). `renderBox()` escapes every value at output and emits `wp_nonce_field`.
- **Secure save (AC-4):** `save()` is the sole `$_POST` site — nonce verify → autosave/revision guard → post-type-in-scope → `current_user_can('edit_post', $id)` → per-field `wp_unslash` + the field's sanitiser → `update_post_meta`. Never reads a raw superglobal; no `$_GET`/`$_REQUEST`.
- **Sanitisers:** text/theme/volume/tier/placement → `sanitize_text_field`; teaser → `sanitize_textarea_field`; link → `esc_url_raw`; cover/PDF id → `absint`; date/deadline → `FieldSets::sanitizeDate()` (keeps `Y-m-d` / datetime-local shapes, drops junk to '').
- **Wiring (AC-5):** `Content\Module::register()` calls `FieldSets` after `UserMeta`; `Content\Api::fieldMetaKeys()` exposes the keys. No `ink-core.php` change. No theme changes.
- **Scope discipline:** the three named CPTs only — no term images (2.5), no `SponsorTier` enum / sponsor scheduling (Epic 14), no media-picker JS (cover/PDF are attachment-ID number inputs at the substrate level), no front-end rendering.
- **Verification:** `php -l`/Pest/PHPStan deferred to Story 18.8 (Epic-1 precedent). The ready-to-run `FieldSetsTest.php` (Brain Monkey captures `register_post_meta`, runs captured sanitisers — `absint`/date) runs once the runner is wired; the python3 scan substitutes now (67/67).

### File List

- `wp-content/plugins/ink-core/src/Content/FieldSets.php` (new) — the field-set registrar + meta boxes + secure save.
- `wp-content/plugins/ink-core/src/Content/Module.php` (modified) — `register()` delegates to `FieldSets` after `UserMeta`.
- `wp-content/plugins/ink-core/src/Content/Api.php` (modified) — facade exposes `fieldMetaKeys()`.
- `tests/Unit/Content/FieldSetsTest.php` (new) — ready-to-run Pest unit test.

## Change Log

| Date | Change |
|---|---|
| 2026-06-21 | Story created (context-engineered) — per-CPT editorial meta + native classic meta boxes for `inkpols_uitgawe`/`uitdaging`/`borg`; `register_post_meta` (typed, REST, sanitised, capability-gated); Afrikaans labels (box title from `Terms`, field labels generic admin literals); secure save (nonce+`edit_post`+`wp_unslash`+sanitise); facade exposes the keys. Term images (2.5), `SponsorTier`/scheduling (Epic 14), media-picker JS deferred. Status → ready-for-dev. |
| 2026-06-21 | Story implemented — declarative `FieldSets` (12 meta keys across 3 CPTs), `register_post_meta` + classic meta boxes, escaped render + `wp_nonce_field`, secure `save()` (nonce+`edit_post`+`wp_unslash`+sanitise), `sanitizeDate`, Module/Api wiring, ready-to-run Pest test. python3 scan 67/67 (`php -l`/Pest deferred to 18.8). Status → review. |
