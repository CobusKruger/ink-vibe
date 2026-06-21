---
baseline_commit: 68c552f
---

# Story 2.2: Register taxonomies

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want the shared taxonomies registered,
so that training and contributions auto-surface via shared terms (no manual linking).

## Acceptance Criteria

1. **The four INK taxonomies exist with exact, migration-load-bearing code IDs.** Given `ink-core` activation, when taxonomies register, then `genre`, `vaardigheid`, `uitdagingsrondte`, `ster_gradering` exist as registered taxonomies with their **exact code IDs** — these are migration-load-bearing (categories/tags remap onto them; `uitdagingsrondte` terms are referenced by the challenge entry record), so the taxonomy keys are fixed class constants, the single source for the IDs. _[Source: epics.md#Story-2.2 AC "`genre`, `vaardigheid`, `uitdagingsrondte`, `ster_gradering` exist"; architecture.md AD-5 storage table ("genre, vaardigheid, uitdagingsrondte, ster_gradering → taxonomies"); project-context.md line 54 "Taxonomies: `genre`, `vaardigheid`, `uitdagingsrondte`, `ster_gradering`. Use these exact code IDs — they are migration-load-bearing"; docs/afrikaans-terms.md Deel 5 taxonomy slugs (lines 268–273)]_

2. **`genre` and `vaardigheid` are shared across the bydrae CPTs and training for auto-surfacing.** Given the registered taxonomies, when `genre`/`vaardigheid` register, then both are attached to the member-submission ("bydrae") CPTs (`gedig`, `storie`, `artikel`, `skryfwerk`) AND to `opleiding_artikel` (training), so a training resource tagged with the same term auto-surfaces alongside contributions — no per-item manual editorial linking (Principle 8). The shared `genre`/`vaardigheid` coupling across Content/Discovery/Training is intentional, not a smell to decouple. _[Source: epics.md#Story-2.2 AC "`genre`/`vaardigheid` are shared across bydraes and training for auto-surfacing"; architecture.md lines 162–164 (shared coupling intentional, FR-55), line 94 (Editorial low-friction); project-context.md "Shared-taxonomy surfacing, not manual linking … Never build a feature that needs per-item manual editorial linking (Principle 8)"]_

3. **All taxonomy labels are sourced from the terminology registry (Story 2.0), not inline literals.** Given the registered taxonomies, when their admin/UI labels render, then every taxonomy label (singular / plural / menu / admin chrome) is sourced from the `Ink\I18n\Terms` registry (Story 2.0) — the singular and plural nouns come from registry keys (e.g. `genre`/`genre_plural`, `vaardigheid`/`vaardigheid_plural`), and the composed admin scaffolding is generic `ink-core`-domain Afrikaans built around those registry nouns via `sprintf()`; no controlled-vocabulary noun is inlined as a bare literal, and `__()` is never wrapped around a variable. _[Source: epics.md#Story-2.2 AC "taxonomy labels are sourced from the terminology registry (Story 2.0)"; architecture.md AD-10; project-context.md "Controlled-vocabulary UI labels come from the ink-core terminology registry … Never inline a glossary label as a bare literal outside the registry"; Story 2.1 PostTypes label pattern (mirrored)]_

4. **Taxonomies are registered in `ink-core`/Content, block-editor / REST ready, controlled-vocabulary, statically verifiable.** Given each `register_taxonomy` call, when it runs, then it registers inside `Ink\Content` via `Content\Module::register()` (after the CPTs, both on `init`), sets `show_in_rest => true` (block editor + REST, AD-6), `hierarchical => true` (controlled checkbox terms — prevents free-text duplicates that would silently break shared-term auto-surfacing), `show_admin_column => true`, and the correct `object_type` attachments; every new `.php` is `<?php` + `declare(strict_types=1)` + `Ink\Content` namespace + `defined('ABSPATH')||exit;`; classes PascalCase, methods camelCase, the taxonomy code IDs are class constants (single-source), no raw superglobals. The Content facade (`Content\Api`) exposes the taxonomy slugs as the cross-module surface (AD-1). A ready-to-run Pest test asserts the registrar's definitions; `php -l`/Pest deferred to the CI buildout (18.8) per the Epic-1 precedent; a python3 structural scan substitutes. _[Source: architecture.md AD-1 (Content owns taxonomies; facade discipline), AD-6 (`show_in_rest`/REST), Implementation Patterns (naming/structure, tests `tests/Unit/{Module}`); project-context.md "declare(strict_types=1)", "Prefix everything ink_/Ink\", "no raw $_POST/$_GET"; Story 2.1 (the registrar + python3-scan precedent)]_

## Tasks / Subtasks

> **Current state (read before starting):** Story 2.1 (done/review) registered the nine CPTs via `Ink\Content\PostTypes` (slug constants single-source, `Terms`-sourced labels, `register()` looped on `init`), wired `Content\Module::register()` → `( new PostTypes() )->register();`, wired the `content` module in `ink-core.php`, and exposed `Content\Api::all()` / `Api::bydraeTypes()`. Story 2.0 added `Ink\I18n\Terms` seeded with every taxonomy singular/plural key this story needs: `genre`/`genre_plural`, `vaardigheid`/`vaardigheid_plural`, `uitdagingsrondte`/`uitdagingsrondte_plural`, `ster_gradering`/`ster_gradering_plural`. No PHP binary / built `vendor/` — verification is the python3 scan + ready-to-run Pest (Epic-1 precedent). Scope is TAXONOMY registration ONLY — user meta is 2.3, per-CPT admin field sets are 2.4, term images are 2.5. Do not register meta or field sets here.

- [x] **Task 1 — Add the taxonomy slug single-source + the Content registrar (AC: 1, 4)**
  - [x] Create `src/Content/Taxonomies.php` — `namespace Ink\Content; final class Taxonomies` (strict types + `ABSPATH` guard, mirroring `PostTypes`). Declare a class constant per taxonomy for the migration-load-bearing code ID (`GENRE = 'genre'`, `VAARDIGHEID = 'vaardigheid'`, `UITDAGINGSRONDTE = 'uitdagingsrondte'`, `STER_GRADERING = 'ster_gradering'`) — these constants are the single source for the IDs.
  - [x] `public static function all(): array` — the four taxonomy slugs, registration order preserved (the facade surface).
  - [x] `private static function definitions(): array` — one entry per taxonomy with: slug (the constant), singular/plural `Terms` keys, `object_types` (the CPT slugs it attaches to, sourced from `PostTypes` constants — never re-typed string literals), `hierarchical` (`true` — controlled checkbox vocabulary), `rewrite` slug.
  - [x] `public function register(): void` — loop the definitions and call `register_taxonomy( $slug, $object_types, $args )` with `show_in_rest => true`, `hierarchical => true`, `show_admin_column => true`, `public => true`. Runs on `init` (invoked from `Module::register()` after `PostTypes`).

- [x] **Task 2 — Wire the shared `object_type` attachments (AC: 2)**
  - [x] `genre` → `PostTypes::bydraeTypes()` (gedig/storie/artikel/skryfwerk) + `PostTypes::OPLEIDING_ARTIKEL` + `PostTypes::BIBLIOTEEK_ITEM` (winning/library works are genre-classified). The bydraes ∪ training overlap is the auto-surfacing seam.
  - [x] `vaardigheid` → `PostTypes::OPLEIDING_ARTIKEL` (training, its primary home) + `PostTypes::bydraeTypes()` (shared so contributions surface against training skill areas).
  - [x] `uitdagingsrondte` → `PostTypes::bydraeTypes()` + `PostTypes::BIBLIOTEEK_ITEM` (entered works + winning works; the term stays for discovery while the `ink_entries` custom table — Epic 12/12A — is the authoritative competition record; do NOT build that table here).
  - [x] `ster_gradering` → `PostTypes::bydraeTypes()` + `PostTypes::BIBLIOTEEK_ITEM` (a star-rating taxonomy term "waar van toepassing").
  - [x] Source every CPT slug in `object_types` from `PostTypes` constants/`bydraeTypes()` — do not re-type `'gedig'` etc. as literals (single-source discipline).

- [x] **Task 3 — Source all labels from the Terms registry (AC: 3)**
  - [x] `private static function labels( string $singularKey, string $pluralKey ): array` — build the full WP TAXONOMY labels array from `Terms::label( $singularKey )` / `Terms::label( $pluralKey )`: `name` = plural, `singular_name` = singular, `menu_name` = plural, and the composed admin chrome (`all_items`, `edit_item`, `view_item`, `update_item`, `add_new_item`, `new_item_name`, `search_items`, `not_found`, `no_terms`, `popular_items`, `parent_item`, …) via `sprintf()` over generic `ink-core`-domain Afrikaans scaffolding + the registry noun. Note the taxonomy label key set differs from the post-type set (`new_item_name`, `parent_item`, `add_or_remove_items`, etc.). No controlled-vocabulary noun inlined; never `__( $var )`.

- [x] **Task 4 — Register through the module seam + expose the facade (AC: 4)**
  - [x] Edit `src/Content/Module.php`: `register()` calls `( new Taxonomies() )->register();` AFTER `( new PostTypes() )->register();` (both on `init`; CPTs first so `object_type` targets exist). Keep the reserved note for meta (2.3) / field sets (2.4) / term images (2.5).
  - [x] Edit `src/Content/Api.php`: add `Api::taxonomies(): array` (the four slugs) delegating to `Taxonomies::all()`. Other modules (Discovery, Training, Challenges) read this, never the internals (AD-1).
  - [x] No `ink-core.php` change needed — the `content` module is already wired (2.1).

- [x] **Task 5 — Author the ready-to-run Pest unit test (AC: 1, 2, 3, 4)**
  - [x] Create `tests/Unit/Content/TaxonomiesTest.php` (Brain Monkey, mirroring `PostTypesTest`): stub `__()` passthrough; alias `register_taxonomy` to capture `(slug, object_types, args)`; assert the four expected slugs register; `genre`/`vaardigheid` `object_types` include the four bydrae CPTs AND `opleiding_artikel`; every taxonomy is `show_in_rest`/`hierarchical`/`show_admin_column` true; labels resolve through `Terms` (e.g. the `genre` name === `Genres`, `vaardigheid` singular === `Vaardigheidsarea`); composed admin chrome interpolates the registry noun; `Api::taxonomies()` returns the four slugs.

- [x] **Task 6 — Static verification (no PHP binary — Epic-1 precedent) (AC: 1–4)**
  - [x] python3 scan: structure (`<?php`/one `declare`/`ABSPATH`/balanced braces/no `?>`) on all new/edited `ink-core` files; 4 slug constants present with exact IDs; `register_taxonomy` looped with `show_in_rest=>true`/`hierarchical=>true`; `object_types` sourced from `PostTypes` (no re-typed CPT literals); labels built via `Terms::label`/`sprintf` (no inlined glossary noun, no `__( $var )`); `Content\Module::register` calls `Taxonomies` after `PostTypes`; `Api::taxonomies` present; no `register_post_type`/`register_meta`/`add_meta_box` (out of scope); no raw superglobals; theme untouched. Record `php -l`/Pest deferral.

## Dev Notes

### What this story is (and is NOT)

- **IS:** registering the **4 INK taxonomies** in `Ink\Content` with exact migration-load-bearing code IDs, all labels sourced from the 2.0 `Terms` registry, `show_in_rest` true, `hierarchical` (controlled vocabulary), `genre`/`vaardigheid` SHARED across bydraes + training for auto-surfacing, registered through the already-wired Content module, with the facade exposing the slugs. _[epics.md#Story-2.2; AD-1/AD-5/AD-6/AD-10]_
- **IS NOT:** registering user meta (2.3), per-CPT admin field sets / meta boxes (2.4), native term images (2.5), the `ink_entries` challenge custom table (Epic 12/12A), term seeding/migration (Epic 16), or front-end discovery templates (Epic 8). No new CPTs (2.1 done). No theme changes.

### ⭐ The mechanism (key deliverable)

- **Shared-term auto-surfacing:** `genre` and `vaardigheid` attach to BOTH the bydrae CPTs and `opleiding_artikel`. Because a training article and a contribution can carry the *same* term, discovery/training surfaces (Epics 8/11) can query by shared term — no manual editorial linking (Principle 8). This is the FR-55 editorial-low-friction coupling; it is intentional.
- **Controlled vocabulary (`hierarchical => true`):** checkbox term selection, not free-text tags. Free-text tags would let a typo ("Digkuns" vs "digkuns") create duplicate terms that silently break shared-term matching. Hierarchical/controlled keeps the shared-surfacing integrity.
- **Slug single-source:** the 4 taxonomy keys are `Taxonomies` class constants — mirrors the `PostTypes` discipline (2.1). `object_types` reference `PostTypes` constants, so the CPT↔taxonomy wiring has one source of truth.
- **Label sourcing:** `name`/`singular_name`/`menu_name` from `Terms::label()`; composed admin labels via `sprintf( __( 'literal %s', 'ink-core' ), $noun )` — noun from the registry, scaffolding generic. A term re-decision propagates automatically.

### ⚠️ Guardrails

- **Exact code IDs — never rename.** The 4 slugs are migration-load-bearing constants, not scattered literals. _[project-context.md line 54]_
- **Labels from the registry only** — no inlined glossary noun; **no `__( $var )`** (composed labels use `sprintf( __( 'literal %s', 'ink-core' ), $noun )`). _[AC-3; AD-10 make-pot caveat]_
- **`object_types` from `PostTypes` constants** — never re-type `'gedig'`/`'opleiding_artikel'` as literals. _[single-source discipline]_
- **Register CPTs before taxonomies** (both on `init`): `Module::register()` calls `PostTypes` then `Taxonomies`. _[WP registration order]_
- **Taxonomies live in `ink-core`/Content**, via the already-wired module — never the theme, never a parallel `add_action`. _[three-layer; AD-1]_
- **Scope discipline:** no meta/field-sets/term-images/entry-table here (2.3–2.5, Epic 12). _[epics.md]_
- **strict types + `ABSPATH` guard + `Ink\Content` namespace + no raw superglobals** in every new file. _[project-context.md]_

### Project Structure Notes

- New `src/Content/Taxonomies.php` (the registrar/collaborator, mirroring `PostTypes`); edits to `src/Content/Module.php` (delegate to `Taxonomies` after `PostTypes`), `src/Content/Api.php` (facade exposes taxonomy slugs). Test at `tests/Unit/Content/TaxonomiesTest.php` mirrors `tests/Unit/Content/PostTypesTest.php`.
- No `ink-core.php` change — the `content` module bootstrap is already wired (2.1). The `Module → collaborator` split (now `Module → PostTypes` + `Module → Taxonomies`) preserves the Engagement house style.

### Previous story intelligence (Story 2.1)

- `PostTypes` exposes `all()` (9 slugs) and `bydraeTypes()` (gedig/storie/artikel/skryfwerk) as static methods, plus per-CPT constants (`PostTypes::OPLEIDING_ARTIKEL`, `PostTypes::BIBLIOTEEK_ITEM`, …) — use these directly for `object_types`.
- The 2.1 label pattern is the template to mirror: a private `labels()` builder, `name`/`singular_name`/`menu_name` from `Terms::label()`, composed chrome via `sprintf( __( '…%s…', 'ink-core' ), $noun )` with `/* translators: %s … */` comments. Reuse it, but with the TAXONOMY label key set (not the post-type set).
- The 2.1 test pattern: a capture helper aliasing the WP registrar to record args, then `expect()` assertions on the captured map. Mirror it with `register_taxonomy` (note its 3-arg signature: slug, object_type, args).
- The 2.1 python3 scan (53/53) is the verification template; extend it for taxonomy specifics.

### Testing standards summary

No PHP binary / built `vendor/` (CI harness at 18.8). `php -l`/Pest/PHPStan deferred (Epic-1 precedent); python3 structural scan substitutes; the authored `TaxonomiesTest.php` runs once the runner is wired. Brain Monkey stubs `__()`/`register_taxonomy`. Tests live at the repo-root `tests/` tree (not plugin-local).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-2.2 — Register taxonomies; ACs: the 4 taxonomies exist; `genre`/`vaardigheid` shared across bydraes + training for auto-surfacing; labels sourced from the terminology registry (Story 2.0)]
- [Source: _bmad-output/planning-artifacts/architecture.md — AD-1 (Content owns taxonomies; facade-only cross-module surface); AD-5 storage table (genre/vaardigheid/uitdagingsrondte/ster_gradering → taxonomies; `uitdagingsrondte` term stays for discovery, `ink_entries` table is the authoritative competition record); AD-6 (`show_in_rest`); lines 162–164 + 94 (shared `genre`/`vaardigheid` coupling intentional, FR-55, editorial low-friction); lines 240–242 (`uitdagingsrondte` taxonomy registered by Content, attached under challenge rules by Challenges); AD-10 (labels via the registry); Implementation Patterns (naming, `tests/Unit/{Module}`)]
- [Source: _bmad-output/project-context.md — line 54 exact migration-load-bearing taxonomy code IDs; "Shared-taxonomy surfacing, not manual linking … Principle 8"; three-layer (content models in ink-core, never the theme); "declare(strict_types=1)", "Prefix everything ink_/Ink\", "no raw $_POST/$_GET"; "Never inline a glossary label outside the registry"]
- [Source: docs/afrikaans-terms.md — Deel 5 taxonomy slugs (lines 268–273): `vaardigheid` (vir opleidingsartikels), `genre` (vir bydraes), `uitdagingsrondte` (vir inskrywings en wenwerk), `ster_gradering` (waar van toepassing as 'n taksonomieterm); line 100 Vaardigheidsarea → `vaardigheid`]
- [Source: docs/migration-plan.md — categories/tags remap to new taxonomy; library/training CPTs carry taxonomy for content type, challenge round, tier, skill area]
- [Source: wp-content/plugins/ink-core/src/Content/PostTypes.php (2.1 — slug constants, `all()`/`bydraeTypes()`, the `labels()` + `register()` patterns to mirror); src/Content/Module.php + Api.php (2.1 — wired module + facade to extend); src/I18n/Terms.php (2.0 registry consumed here)]
- [Source: tests/Unit/Content/PostTypesTest.php (2.1 — the capture-and-assert test pattern to mirror)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m] (Opus 4.8, 1M context)

### Debug Log References

- python3 structural scan (`scan_2_2.py`, run from the job tmp dir): **52/52 checks passed** — structure, 4 exact slug constants, looped `register_taxonomy` with `show_in_rest`/`hierarchical`/`show_admin_column => true`, `object_types` sourced from `PostTypes` (no re-typed CPT literals), labels via `Terms::label`/`sprintf` (no `__( $var )`, no inlined glossary noun), `Module::register` calls `Taxonomies` after `PostTypes`, `Api::taxonomies()` present, no `register_post_type`/`register_meta`/`add_meta_box`, no raw superglobals, theme untouched.

### Completion Notes List

- **Registrar (`Taxonomies`)** mirrors `PostTypes`: 4 migration-load-bearing code IDs as `public const` single-source; `definitions()` carries per-taxonomy `object_types`/labels/rewrite; `register()` loops them into `register_taxonomy` with `show_in_rest`, `hierarchical`, `show_admin_column`, `public` all true.
- **Shared-term auto-surfacing (AC-2):** `genre` and `vaardigheid` both attach to the four bydrae CPTs AND `opleiding_artikel` (+ `biblioteek_item`), so a training resource and a contribution can carry the same term — the FR-55 editorial-low-friction coupling, no manual linking (Principle 8). `uitdagingsrondte` and `ster_gradering` attach to the works (bydraes + library). Every `object_type` is sourced from `PostTypes` constants / `bydraeTypes()` — no re-typed CPT literals.
- **Controlled vocabulary:** all four are `hierarchical => true` (checkbox terms), so a free-text typo cannot fork a term and silently break shared-term matching.
- **Labels (AC-3)** are sourced entirely from the 2.0 `Terms` registry, using the WP **taxonomy** label key set (`new_item_name`, `parent_item`, `add_or_remove_items`, …); `name`/`singular_name`/`menu_name` from `Terms::label()`, composed chrome via `sprintf( __( 'literal %s', 'ink-core' ), $noun )`. `__()` never wraps a variable (make-pot safe).
- **Wiring (AC-4):** `Content\Module::register()` calls `Taxonomies` AFTER `PostTypes` (both on `init`, CPTs first so `object_type` targets exist); `Content\Api::taxonomies()` exposes the four slugs as the cross-module surface. No `ink-core.php` change (the `content` module was wired in 2.1). No registration leaks into the theme.
- **Scope discipline:** taxonomies only — no user meta (2.3), admin field sets (2.4), term images (2.5) or `ink_entries` table (Epic 12).
- **Verification:** `php -l` / Pest / PHPStan deferred to the CI buildout (Story 18.8) per the Epic-1 precedent. The ready-to-run `TaxonomiesTest.php` (Brain Monkey, captures `register_taxonomy` args, asserts slugs/sharing/labels/facade) runs once the runner is wired; the python3 structural scan substitutes now (52/52).

### File List

- `wp-content/plugins/ink-core/src/Content/Taxonomies.php` (new) — the taxonomy registrar + slug single-source.
- `wp-content/plugins/ink-core/src/Content/Module.php` (modified) — `register()` delegates to `Taxonomies` after `PostTypes`.
- `wp-content/plugins/ink-core/src/Content/Api.php` (modified) — facade exposes `taxonomies()`.
- `tests/Unit/Content/TaxonomiesTest.php` (new) — ready-to-run Pest unit test.

## Change Log

| Date | Change |
|---|---|
| 2026-06-21 | Story created (context-engineered) — register the 4 INK taxonomies in `Ink\Content` with exact migration-load-bearing code IDs; `genre`/`vaardigheid` shared across bydraes + training for auto-surfacing; controlled `hierarchical` vocabulary; labels sourced from the 2.0 `Terms` registry; `show_in_rest` true; registered through the already-wired Content module; facade exposes the taxonomy slugs. Status → ready-for-dev. |
| 2026-06-21 | Story implemented — `Taxonomies` registrar + 4 slug constants, shared `genre`/`vaardigheid` object_types (bydraes ∪ training), controlled hierarchical vocab, Terms-sourced taxonomy labels, Module/Api wiring, ready-to-run Pest test. python3 scan 52/52 (`php -l`/Pest deferred to 18.8). Status → review. |
