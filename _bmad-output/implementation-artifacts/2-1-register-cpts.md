---
baseline_commit: 0ef5bb6
---

# Story 2.1: Register CPTs

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want the INK custom post types registered with Afrikaans slugs,
so that all content has a typed home and migration can map onto it.

## Acceptance Criteria

1. **The nine INK CPTs exist with Afrikaans slugs and exact, migration-load-bearing code IDs.** Given `ink-core` activation, when CPTs register, then `gedig`, `storie`, `artikel`, `skryfwerk`, `biblioteek_item`, `opleiding_artikel`, `uitdaging`, `inkpols_uitgawe`, `borg` exist as registered post types with Afrikaans slugs per the terms guide and **exact code IDs** — these are migration-load-bearing (old `verhaal`→`storie`, `inkpols`→`inkpols_uitgawe`), so the post-type keys are fixed constants, the single source for the IDs. The library/training CPTs keep their documented URL prefixes (`biblioteek_item` archive → `/biblioteek/`, `opleiding_artikel` → `/opleiding/`). _[Source: epics.md#Story-2.1 AC "`gedig`, `storie`, `artikel`, `skryfwerk`, `biblioteek_item`, `opleiding_artikel`, `uitdaging`, `inkpols_uitgawe`, `borg` exist with Afrikaans slugs per the terms guide and exact code IDs (migration-load-bearing)"; project-context.md line 54 "Use these exact code IDs — they are migration-load-bearing (old `verhaal`→`storie`, `inkpols`→`inkpols_uitgawe`)"; architecture.md AD-5 (bydraes, biblioteek_item, uitdaging, inkpols_uitgawe, borg, opleiding_artikel → CPTs); docs/afrikaans-terms.md Deel 5 post-type slugs (lines 243–250); docs/migration-plan.md "keep `/biblioteek/` `/opleiding/` URL prefixes"]_

2. **All CPT labels are sourced from the terminology registry (Story 2.0), not inline literals.** Given the registered CPTs, when their admin/UI labels render, then every CPT label (singular / plural / menu / admin chrome) is sourced from the `Ink\I18n\Terms` registry (Story 2.0) — the singular and plural nouns come from registry keys (e.g. `gedig`/`gedig_plural`), and the composed admin scaffolding ("Voeg nuwe …", "Wysig …") is generic `ink-core`-domain Afrikaans built around those registry nouns; no controlled-vocabulary noun is inlined as a bare literal. _[Source: epics.md#Story-2.1 AC "all CPT labels (singular/plural/menu/admin) are sourced from the terminology registry (Story 2.0), not inline literals"; sprint-change-proposal-2026-06-21.md §4.2; architecture.md AD-10; project-context.md "Never inline a glossary label as a bare literal outside the registry"]_

3. **CPTs are registered in `ink-core` via the Content module, wired through the Kernel module seam.** Given the AD-1 modular monolith, when CPTs register, then they register inside `Ink\Content` (the module whose `Module.php` reserved this since 1.7), dispatched on `init` through the Kernel module seam (`Plugin::registerModules()`), and `Content\Module` is wired into the bootstrap (`ink-core.php`) via `addModule('content', …)` — it is currently the only un-wired module that Epic 2 needs. The Content facade (`Content\Api`) exposes the post-type slugs as the sole cross-module surface (AD-1). No CPT registration leaks into the theme or `functions.php`. _[Source: architecture.md AD-1 (Content module owns CPTs/taxonomies/meta; facade discipline; "the plugin main file loads Kernel, then each module's bootstrap"); wp-content/plugins/ink-core/src/Content/Module.php (reserved Epic-2 home); src/Kernel/Plugin.php (module seam); src/Kernel/Module.php (contract); project-context.md three-layer "content models in ink-core, never the theme"]_

4. **Registration is block-editor / REST ready and strict, prefixed, statically verifiable.** Given each `register_post_type` call, when it runs, then it sets `show_in_rest => true` (block editor + the `ink/v1`-adjacent REST surface, AD-6), sensible `supports`, `public`/`has_archive` per the entity, and an Afrikaans `menu_icon`; every new `.php` is `<?php` + `declare(strict_types=1)` + `Ink\Content` namespace + `defined('ABSPATH')||exit;`; classes PascalCase, methods camelCase, the post-type code IDs are class constants (single-source), no raw superglobals. A ready-to-run Pest test asserts the registrar's definitions (slugs, label sourcing, archive prefixes). `php -l`/Pest deferred to the CI buildout (18.8) per the Epic-1 precedent; a python3 structural scan substitutes. _[Source: architecture.md AD-6 (`show_in_rest`/REST), Implementation Patterns (naming/structure, tests `tests/Unit/{Module}`); project-context.md "declare(strict_types=1) in all ink-core PHP", "Prefix everything ink_/Ink\", "no raw $_POST/$_GET"; 1-10/1-8 deferred-verification + ready-to-run-Pest precedent]_

## Tasks / Subtasks

> **Current state (read before starting):** Story 2.0 (done/review) added `Ink\I18n\Terms` (concept-key → literal `__()` Afrikaans labels) seeded with every CPT singular/plural key this story needs: `gedig`/`gedig_plural`, `storie`/`storie_plural`, `artikel`/`artikel_plural`, `skryfwerk`/`skryfwerk_plural`, `biblioteek_item`/`_plural`, `opleiding_artikel`/`_plural`, `uitdaging`/`_plural`, `inkpols_uitgawe`/`_plural`, `borg`/`borg_plural`. `Content\Module::register()` is a documented no-op reserved for "CPTs, taxonomies and meta … in Epic 2"; `Content\Api` is an empty reserved facade. The Kernel dispatches `Module::register()` on `init` (`Plugin::registerModules()`); the bootstrap `ink-core.php` currently registers only `engagement` + `notifications` — **`content` is NOT yet wired**. Enums/registries live in `Ink\Kernel`; the procedural surface in `src/functions.php`. No PHP binary / built `vendor/` — verification is the python3 scan + ready-to-run Pest (Epic-1 precedent). Scope is CPT registration ONLY — taxonomies are 2.2, user meta is 2.3, per-CPT admin field sets are 2.4, term images are 2.5. Do not register taxonomies or meta here.

- [x] **Task 1 — Add the post-type slug single-source + the Content registrar (AC: 1, 4)**
  - [x] Create `src/Content/PostTypes.php` — `namespace Ink\Content; final class PostTypes` (strict types + `ABSPATH` guard). Declare a class constant per CPT for the migration-load-bearing code ID (`GEDIG = 'gedig'`, `STORIE = 'storie'`, `ARTIKEL = 'artikel'`, `SKRYFWERK = 'skryfwerk'`, `BIBLIOTEEK_ITEM = 'biblioteek_item'`, `OPLEIDING_ARTIKEL = 'opleiding_artikel'`, `UITDAGING = 'uitdaging'`, `INKPOLS_UITGAWE = 'inkpols_uitgawe'`, `BORG = 'borg'`) — these constants are the single source for the IDs (AC-4 of Story 2.0: slugs stay constant single-source).
  - [x] `private static function definitions(): array` — one entry per CPT with: slug (the constant), singular/plural Terms keys, `supports`, `public`, `has_archive` (string archive slug where it has one; `biblioteek_item` → `'biblioteek'`, `opleiding_artikel` → `'opleiding'` per the migration URL prefixes; `borg` → `false`), `menu_icon` (dashicon), `rewrite` slug.
  - [x] `public function register(): void` — loop the definitions and call `register_post_type( $slug, $args )` with `show_in_rest => true`, `map_meta_cap => true`. Runs on `init` (it is invoked from `Module::register()`, which the Kernel dispatches on `init`).

- [x] **Task 2 — Source all labels from the Terms registry (AC: 2)**
  - [x] `private static function labels( string $singularKey, string $pluralKey ): array` — build the full WP post-type labels array from `Terms::label( $singularKey )` / `Terms::label( $pluralKey )`: `name` = plural, `singular_name` = singular, `menu_name` = plural, and the composed admin chrome (`add_new_item`, `edit_item`, `new_item`, `view_item`, `search_items`, `not_found`, `all_items`, `archives`, `featured_image`, `set_featured_image`, `item_published`, `item_updated`, …) via `sprintf()` over generic `ink-core`-domain Afrikaans scaffolding + the registry noun. No controlled-vocabulary noun inlined as a literal; the scaffolding verbs ("Voeg nuwe %s by", "Wysig %s") are generic admin chrome, not glossary concepts.

- [x] **Task 3 — Wire Content into the module seam + expose the facade (AC: 3)**
  - [x] Edit `src/Content/Module.php`: `register()` calls `( new PostTypes() )->register();` (thin bootstrap → collaborator, matching the Engagement `Module → Comments` house style). Keep the reserved note for taxonomies (2.2) / meta (2.3/2.4).
  - [x] Edit `ink-core.php`: add `Kernel\Plugin::instance()->addModule( 'content', new Content\Module() );` to the `plugins_loaded` registration closure (alongside `engagement`/`notifications`).
  - [x] Edit `src/Content/Api.php`: expose the post-type slugs as the cross-module surface — `Api::all(): array` (all 9 slugs) and `Api::bydraeTypes(): array` (the member-submission CPTs: `gedig`/`storie`/`artikel`/`skryfwerk`), delegating to `PostTypes` constants. Other modules (Submission, Discovery) read these, never the internals (AD-1).

- [x] **Task 4 — Author the ready-to-run Pest unit test (AC: 1, 2, 4)**
  - [x] Create `tests/Unit/Content/PostTypesTest.php` (Brain Monkey): stub `__()`/`sprintf` passthrough; assert the definitions cover exactly the 9 expected slugs; `biblioteek_item`/`opleiding_artikel` archives are `'biblioteek'`/`'opleiding'`; labels resolve through `Terms` (e.g. the `storie` name === `Stories`); `Api::all()` returns the 9 slugs and `Api::bydraeTypes()` the 4 submission CPTs. Optionally assert `register_post_type` is called 9 times via `Functions\expect`.

- [x] **Task 5 — Static verification (no PHP binary — Epic-1 precedent) (AC: 1–4)**
  - [x] python3 scan: structure (`<?php`/one `declare`/`ABSPATH`/balanced braces/no `?>`) on all new/edited `ink-core` files; 9 slug constants present with exact IDs; `register_post_type` looped with `show_in_rest=>true`; labels built via `Terms::label`/`sprintf` (no inlined glossary noun, no `__( $var )`); `Content\Module::register` calls `PostTypes`; bootstrap wires `'content'`; `Api::all`/`bydraeTypes` present; no `register_taxonomy`/`register_meta` (out of scope); no raw superglobals; theme untouched. Record `php -l`/Pest deferral.

## Dev Notes

### What this story is (and is NOT)

- **IS:** registering the **9 INK CPTs** in `Ink\Content` with exact migration-load-bearing code IDs and Afrikaans slugs, all labels sourced from the 2.0 `Terms` registry, `show_in_rest` true, wired through the Kernel module seam and the bootstrap, with the Content facade exposing the slugs. _[epics.md#Story-2.1; AD-1/AD-5/AD-6; AD-10]_
- **IS NOT:** registering taxonomies (`genre`/`vaardigheid`/`uitdagingsrondte`/`ster_gradering` — Story 2.2), user meta (2.3), per-CPT admin field sets / meta boxes (2.4), native term images (2.5), custom capabilities mapping (later), URL/redirect generation or migration (Epic 16), or front-end templates (Epics 7/8/10). No theme changes.

### ⭐ The mechanism (key deliverable)

- **Slug single-source:** the 9 post-type keys are `PostTypes` class constants — the migration-load-bearing IDs live in exactly one place (mirrors AD-10's enum/constant discipline for IDs; the registry holds only the display labels).
- **Label sourcing:** `name`/`singular_name`/`menu_name` come straight from `Terms::label()`; composed admin labels are `sprintf( __( 'Voeg nuwe %s by', 'ink-core' ), Terms::label('gedig') )`-style — the noun from the registry, the scaffolding generic. A term re-decision (e.g. `storie` wording) propagates to every CPT label automatically.
- **Archive prefixes:** `biblioteek_item` → `/biblioteek/`, `opleiding_artikel` → `/opleiding/` (migration plan keeps these). The bydrae CPTs use their own Afrikaans slug; `borg` has no public archive.

### ⚠️ Guardrails

- **Exact code IDs — never rename.** The 9 slugs are migration-load-bearing; they are constants, not literals scattered across calls. _[project-context.md line 54]_
- **Labels from the registry only** — no inlined glossary noun. _[AC-2; AD-10]_
- **No `__( $var )`** — composed labels use `sprintf( __( 'literal %s', 'ink-core' ), $noun )`, never `__( $noun )`. _[AD-10 make-pot caveat]_
- **CPTs live in `ink-core`/Content**, wired via the Kernel seam — never the theme, never a parallel `add_action` in the bootstrap. _[three-layer; AD-1]_
- **Scope discipline:** no taxonomies/meta/field-sets here (2.2–2.5). _[epics.md]_
- **strict types + `ABSPATH` guard + `Ink\Content` namespace + no raw superglobals** in every new file. _[project-context.md]_

### Project Structure Notes

- New `src/Content/PostTypes.php` (the registrar/collaborator); edits to `src/Content/Module.php` (thin bootstrap delegates to `PostTypes`), `src/Content/Api.php` (facade exposes slugs), `ink-core.php` (wire `content` module). Test at `tests/Unit/Content/PostTypesTest.php` mirrors `src/Content/`.
- The `Module → collaborator` split mirrors the Engagement `Module → Comments` house style (1.8); the bootstrap-only-registers-modules property is preserved.

### Testing standards summary

No PHP binary / built `vendor/` (CI harness at 18.8). `php -l`/Pest/PHPStan deferred (Epic-1 precedent); python3 structural scan substitutes; the authored `PostTypesTest.php` runs once the runner is wired. Brain Monkey stubs `__()`/`register_post_type`.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-2.1 — Register CPTs; ACs: the 9 CPTs exist with Afrikaans slugs + exact migration-load-bearing code IDs; all CPT labels sourced from the terminology registry (Story 2.0), not inline literals]
- [Source: _bmad-output/planning-artifacts/architecture.md — AD-1 modular monolith (Content module owns CPTs/taxonomies/meta; facade-only cross-module surface; main file loads Kernel then each module bootstrap); AD-5 persistence (bydraes, biblioteek_item, uitdaging, inkpols_uitgawe, borg, opleiding_artikel → CPTs); AD-6 (`show_in_rest`/REST write path); AD-10 (labels via the registry); Implementation Patterns (naming, `tests/Unit/{Module}`)]
- [Source: _bmad-output/planning-artifacts/sprint-change-proposal-2026-06-21.md — §4.2 Epic 2.1 AC amendment (CPT labels sourced from the registry, not inline literals)]
- [Source: _bmad-output/project-context.md — line 54 exact migration-load-bearing CPT/taxonomy code IDs incl. `skryfwerk` catch-all and the `verhaal`→`storie`/`inkpols`→`inkpols_uitgawe` renames; three-layer (content models in ink-core, never the theme); "declare(strict_types=1)", "Prefix everything ink_/Ink\", "no raw $_POST/$_GET"; "Never inline a glossary label outside the registry"]
- [Source: docs/afrikaans-terms.md — Deel 5 post-type slugs (lines 243–250): gedig/storie/artikel/uitdaging/biblioteek_item/opleiding_artikel/inkpols_uitgawe/borg (NOTE: `skryfwerk` absent — see Story 2.0 flag); Deel 1 UI-terms feeding the registry keys]
- [Source: docs/migration-plan.md — keep `/biblioteek/` and `/opleiding/` URL prefixes; `verhaal`→`storie` slug change]
- [Source: wp-content/plugins/ink-core/src/Content/Module.php + Api.php (1.7 reserved Epic-2 homes); src/Kernel/Plugin.php (`addModule`/`registerModules` seam, init dispatch); src/Kernel/Module.php (contract); ink-core.php (`plugins_loaded` module registration — engagement/notifications wired, content not yet); src/I18n/Terms.php (2.0 registry consumed here)]
- [Source: wp-content/plugins/ink-core/src/Engagement/Module.php (1.8) — thin `Module → Comments` collaborator house style mirrored by `Module → PostTypes`]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m] (Opus 4.8, 1M context)

### Debug Log References

- python3 structural scan (`scan_2_1.py`, run from the job tmp dir): **53/53 checks passed** — structure, 9 exact slug constants, looped `register_post_type` with `show_in_rest=>true`/`map_meta_cap=>true`, labels via `Terms::label`/`sprintf` (no `__( $var )`, no inlined glossary noun), biblioteek/opleiding archive prefixes, `Module::register`→`PostTypes`, bootstrap wiring, facade delegation, no `register_taxonomy`/`register_meta`, no raw superglobals, theme untouched.

### Completion Notes List

- **Registrar (`PostTypes`)** declares the 9 migration-load-bearing code IDs as `public const` single-source; `definitions()` carries per-CPT supports/visibility/archive/icon/rewrite; `register()` loops them into `register_post_type` with `show_in_rest => true`, `map_meta_cap => true`.
- **Labels** are sourced entirely from the 2.0 `Terms` registry: `name`/`singular_name`/`menu_name` straight from `Terms::label()`; composed admin chrome via `sprintf( __( 'literal %s', 'ink-core' ), $noun )` — generic Afrikaans scaffolding, registry noun. `__()` is never wrapped around a variable (make-pot safe).
- **Archive prefixes** keep the documented migration URLs: `biblioteek_item` → `/biblioteek/`, `opleiding_artikel` → `/opleiding/`; `borg` has no public archive.
- **Wiring (AD-1):** `Content\Module::register()` delegates to `PostTypes` (thin `Module → collaborator` house style mirroring Engagement); `ink-core.php` registers the `content` module on `plugins_loaded` (dispatched on `init` by the Kernel seam); `Content\Api` exposes `all()` / `bydraeTypes()` as the sole cross-module slug surface. No registration leaks into the theme.
- **Scope discipline:** CPTs only — no taxonomies (2.2), user meta (2.3), admin field sets (2.4) or term images (2.5).
- **Verification:** `php -l` / Pest / PHPStan deferred to the CI buildout (Story 18.8) per the Epic-1 precedent (no PHP binary / built `vendor/` in-repo). The ready-to-run `PostTypesTest.php` (Brain Monkey, captures `register_post_type` args, asserts slugs/archives/labels/facade) runs once the runner is wired; the python3 structural scan substitutes now (53/53).

### File List

- `wp-content/plugins/ink-core/src/Content/PostTypes.php` (new) — the CPT registrar + slug single-source.
- `wp-content/plugins/ink-core/src/Content/Module.php` (modified) — `register()` delegates to `PostTypes`.
- `wp-content/plugins/ink-core/src/Content/Api.php` (modified) — facade exposes `all()` / `bydraeTypes()`.
- `wp-content/plugins/ink-core/ink-core.php` (modified) — wires the `content` module bootstrap.
- `tests/Unit/Content/PostTypesTest.php` (new) — ready-to-run Pest unit test.

## Change Log

| Date | Change |
|---|---|
| 2026-06-21 | Story created (context-engineered) — register the 9 INK CPTs in `Ink\Content` with exact migration-load-bearing code IDs + Afrikaans slugs; labels sourced from the 2.0 `Terms` registry; `show_in_rest` true; archive prefixes for biblioteek/opleiding; wired through the Kernel module seam + bootstrap; Content facade exposes the slugs. Status → ready-for-dev. |
| 2026-06-21 | Story implemented — `PostTypes` registrar + 9 slug constants, Terms-sourced labels, Module/Api/bootstrap wiring, ready-to-run Pest test. python3 scan 53/53 (`php -l`/Pest deferred to 18.8). Status → review. |
