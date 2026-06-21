---
baseline_commit: 9d6e4efdaac0a3760168057abda36cbd92394d67
---

# Story 2.0: Terminology label registry (glossary-backed label source)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an Afrikaans-first product with an owner-maintained controlled vocabulary,
I want every code-rendered UI label sourced from a single glossary-backed registry,
so that re-deciding a term is a one-file edit, not a codebase-wide search. (Layer K · P0)

## Acceptance Criteria

1. **A glossary-backed label registry in `ink-core` maps concept keys to literal `__()` definitions.** Given the `ink-core` plugin, when the terminology registry is built, then a registry (`Ink\I18n\Terms`) maps glossary concept keys (`membership`, `gradering`, `bydrae`, the CPT/taxonomy concepts, …) to literal `__( '<Afrikaans>', 'ink-core' )` label definitions, seeded from `docs/afrikaans-terms.md` (the **UI-term** column). The label literals are Afrikaans (gettext SOURCE language, per the §14.15 no-English-`.mo` policy established in Story 1.10); the glossary UI-term column supplies the concept spelling/wording, while the registry presents them as Title-case display labels per WP admin/label convention (owner decision 2026-06-21 — surface casing is not glossary-verbatim). _[Source: epics.md#Story-2.0 AC "a registry (e.g. `Ink\I18n\Terms`) maps glossary concept keys … to literal `__( '<Afrikaans>', 'ink-core' )` label definitions, seeded from `docs/afrikaans-terms.md` (UI-term column)"; architecture.md AD-10 (terminology label registry — `Ink\I18n\Terms`, literal `__()`, seeded from the UI-term column); sprint-change-proposal-2026-06-21.md §4.1/§4.6; project-context.md "Controlled-vocabulary UI labels come from the ink-core terminology registry (single-source, glossary-backed)"; docs/afrikaans-terms.md Deel 1 UI-term column + Onderhoud note line 287]_

2. **A helper returns the label so callers never inline the literal.** Given the registry, when a caller needs a label, then a helper (`Ink\ink_term('key')` / `Terms::label('key')`) returns the label, so callers pass the concept **key**, never the inline literal. An unknown key fails safe (returns the key + a `WP_DEBUG`-gated developer notice), never a fatal. `Terms::has()` / `Terms::all()` expose the registry as an inspectable surface for the NFR-1 English-leak scan (Story 17.4). _[Source: epics.md#Story-2.0 AC "a helper (`ink_term('key')` / `Terms::label('key')`) returns the label, so callers never inline the literal"; project-context.md "Never inline a glossary label as a bare literal outside the registry"; sprint-change-proposal-2026-06-21.md §5 success-criterion 4 (registry is an inspected surface for the leak scan)]_

3. **`wp i18n make-pot` extracts every registry label (literals only — never `__( $var )`).** Given the registry, when `wp i18n make-pot` runs, then every label is extracted because the registry holds literal `__( 'string', 'ink-core' )` calls — the registry file **is** the extraction surface. No label anywhere in the codebase wraps `__()` around a variable (`__( $label )`), which `make-pot` cannot extract; dynamic resolution happens by KEY (`ink_term($key)`), and the only `__()` literals for these concepts live in the registry. _[Source: epics.md#Story-2.0 AC "`wp i18n make-pot` extracts every registry label (literals only — never `__( $var )`)"; architecture.md AD-10 "the registry file is the extraction surface … never wrap `__()` around a variable"; sprint-change-proposal-2026-06-21.md §2 "CRITICAL caveat to document: never wrap `__()` around a *variable*"]_

4. **Code IDs, slugs, and enums remain the existing enum/constant single-source (unchanged).** Given the registry, when it is built, then it governs UI **display labels only**; code IDs / CPT & taxonomy slugs / enum backing values remain the existing `enum`/constant single-source (`Ink\Kernel\Tier`, the CPT/taxonomy slug constants registered in Epic 2.1/2.2). The registry complements — does not replace — that discipline. _[Source: epics.md#Story-2.0 AC "code IDs, slugs, and enums remain the existing enum/constant single-source (unchanged)"; architecture.md AD-10 (complements the enum/code-ID rule); project-context.md line 42 "never duplicate these literals across the codebase" (enums) + line 54 (fixed CPT/taxonomy slugs); 1-* `Ink\Kernel\Tier` enum]_

5. **A theme-side bridge exposes the same labels to PHP patterns; static block-template HTML uses the Block Bindings API.** Given the theme cannot call `ink-core` PHP from static block-template HTML, when a pattern or template needs a glossary label, then (a) a theme-side bridge (`ink_foundation_term()`) exposes the same labels to PHP patterns, decoupled via a `function_exists` guard so the theme never fatals when `ink-core` is inactive; and (b) `ink-core` registers an `ink/term` **Block Bindings** source (WP 6.5+) so static `templates/*.html` can bind heading/paragraph/button text to a term key. This story stands up the bridge mechanism; it does **not** remediate the Epic 1 patterns (that is a separate scheduled task — sprint-change-proposal-2026-06-21 §4.5). _[Source: epics.md#Story-2.0 AC "a theme-side bridge exposes the same labels to PHP patterns, and static `templates/*.html` uses the Block Bindings API where a dynamic term is needed"; architecture.md AD-10 (Block Bindings is the bridge for static block-template HTML); sprint-change-proposal-2026-06-21.md §2 "theme side: (a) a small theme bridge … (b) the Block Bindings API (WP 6.5+) can bind … to a registered `ink/term` source", §4.5 (Epic 1 remediation is a separate task)]_

6. **The registry's relationship to `afrikaans-terms.md` is documented.** Given the registry, when it ships, then its relationship to `docs/afrikaans-terms.md` is documented in code: the glossary remains the **human source of truth**; the registry is its **machine projection** of the UI-term column; a term change is made once in the glossary and reflected in the registry; and **DB content** (page bodies, nav menus, migrated posts) is explicitly out of registry scope — it remains a `wp search-replace` operation. _[Source: epics.md#Story-2.0 AC "the registry's relationship to `afrikaans-terms.md` is documented: the glossary remains the human source of truth; the registry is its machine projection"; docs/afrikaans-terms.md Onderhoud line 287 "Die UI-term-kolom word in 'n masjienleesbare register (ink-core Terms) geprojekteer; die gids bly die menslike bron van waarheid … (Story 2.0 / AD-10)"; sprint-change-proposal-2026-06-21.md §2 "Out of scope for any code mechanism: DB content … remains a `wp search-replace` operation"]_

## Tasks / Subtasks

> **Current state (read before starting):** Epic 1 is `done`. `ink-core` is the AD-1 modular monolith: the `Ink\Kernel` holds the boot (`Plugin`), the `Module` contract, enums (`Tier`/`Reaction`/`ResponseType`), `Schema` (dbDelta registry), `Capabilities`, and the i18n loader (`I18n` — built out in Story 1.10: loads the `ink-core` text domain on `init`, forces staff to English admin via `get_user_locale`, and **ships no English `.mo` so Afrikaans-source literals fall through unchanged**). Feature modules (`Content`, `Tiers`, `Engagement`, …) each have a `Module.php` (bootstrap, `register()` no-op until their epic) + `Api.php` (facade); `Content\Module::register()` is the reserved Epic-2 home for CPTs/taxonomies/meta and is **not yet wired** in `ink-core.php` (only `engagement` + `notifications` are). The procedural surface lives in `src/functions.php` under `namespace Ink` (e.g. `Ink\ink_core()`), eagerly required by `src/autoload.php`. There is **no PHP binary and no built `vendor/`** in the repo — `php -l`/Pest/PHPStan are deferred (Epic-1 precedent); a python3 structural scan substitutes and a ready-to-run Pest test is authored. This is the **first story of Epic 2** and a prerequisite for 2.1/2.2/2.4 (they register their labels through this registry). Scope is the registry + helpers + bridge ONLY — do **not** register CPTs/taxonomies (2.1/2.2), build admin field sets (2.4), or remediate Epic 1 patterns (separate task).

- [x] **Task 1 — Build the `Ink\I18n\Terms` registry (AC: 1, 3, 4, 6)**
  - [x] Create `src/I18n/Terms.php` — `namespace Ink\I18n; final class Terms` with `declare(strict_types=1)` + `ABSPATH` guard. A single `private static function map(): array` returns the concept-key → **literal** `__( '<Afrikaans>', 'ink-core' )` map (rebuilt per call — NOT memoized — so a runtime `switch_to_locale()` is honoured; the no-English-`.mo` policy means the Afrikaans source is returned regardless, but rebuilding keeps it correct if a future `.mo` is ever added). Seed from `docs/afrikaans-terms.md` UI-term column: core concepts (`lid`, `skrywer`, `membership`→`Lidmaatskap`, `betaalde_lid`, `gratis_lid`, `gradering`→`Gradering`, `brons`/`silwer`/`goud`/`meester`, `bydrae`/`bydrae_plural`); CPT singular/plural (`gedig`/`gedig_plural`→`Gedig`/`Gedigte`, `storie`/`storie_plural`→`Storie`/`Stories`, `artikel`/`artikel_plural`, `skryfwerk`/`skryfwerk_plural`→`Skryfwerk`/`Skrywes`, `biblioteek_item`/`_plural`→`Biblioteekitem`/`Biblioteekitems`, `opleiding_artikel`/`_plural`→`Hulpbronartikel`/`Hulpbronartikels`, `uitdaging`/`_plural`, `inkpols_uitgawe`/`_plural`→`Uitgawe`/`Uitgawes`, `borg`/`borg_plural`→`Borg`/`Borge`); sections (`biblioteek`→`Biblioteek`, `opleiding`→`Opleiding`); taxonomy singular/plural (`genre`/`_plural`, `vaardigheid`/`_plural`→`Vaardigheidsarea`/`Vaardigheidsareas`, `uitdagingsrondte`/`_plural`, `ster_gradering`/`_plural`→`Ster gradering`/`Ster graderings`).
  - [x] `public static function label( string $key ): string` — returns the mapped label, or fails safe for an unknown key: returns `$key` and, under `WP_DEBUG`, emits a developer notice (no fatal, no English string to a visitor).
  - [x] `public static function has( string $key ): bool` and `public static function all(): array` (the inspectable surface for the 17.4 leak scan).
  - [x] Class docblock documents AC-6: glossary = human source of truth; registry = machine projection of the UI-term column; the make-pot literals-only caveat (AC-3); the enum/slug single-source boundary (AC-4); DB-content out of scope (`wp search-replace`).
  - [x] Flag the `skryfwerk` glossary gap: `skryfwerk` is the migration catch-all CPT (epics.md 2.1 + project-context.md line 54/98) but is absent from `afrikaans-terms.md` Deel 5's slug list — seeded here as `Skryfwerk`/`Skrywes`; record in Dev Notes that the glossary should add it.

- [x] **Task 2 — Add the `Ink\ink_term()` procedural helper (AC: 2)**
  - [x] Extend `src/functions.php` (`namespace Ink`) with `function ink_term( string $key ): string { return I18n\Terms::label( $key ); }`, guarded by `function_exists( 'Ink\\ink_term' )` (mirroring the existing `Ink\ink_core()` precedent — the `ink_` procedural surface lives under `namespace Ink`). Docblock notes templates/patterns may call `\Ink\ink_term()`, but the theme prefers the decoupled `ink_foundation_term()` bridge (Task 4).

- [x] **Task 3 — Register the `ink/term` Block Bindings source (AC: 5)**
  - [x] Create `src/I18n/Bindings.php` — `namespace Ink\I18n; final class Bindings` with `static register(): void` calling `register_block_bindings_source( 'ink/term', [ 'label' => __( 'INK-terminologie', 'ink-core' ), 'get_value_callback' => [ self::class, 'resolve' ], 'uses_context' => [] ] )`. `resolve( array $source_args ): string` returns `Terms::label( (string) ( $source_args['key'] ?? '' ) )` (escaped by the binding consumer at render). Guard `function_exists( 'register_block_bindings_source' )` for WP < 6.5 safety even though the build target is 7.0+.
  - [x] Wire `Bindings::register()` on `init` from `Kernel\Plugin::run()` — next to `I18n::load()` — as a cross-cutting Kernel i18n concern (NOT a feature module, NOT the bootstrap), consistent with how Story 1.10 wired i18n.

- [x] **Task 4 — Add the theme-side bridge `ink_foundation_term()` (AC: 5)**
  - [x] Add `ink_foundation_term( string $key, string $fallback = '' ): string` to `wp-content/themes/ink-foundation/functions.php` — returns `\Ink\ink_term( $key )` when `function_exists( 'Ink\\ink_term' )`, else `$fallback`. This is the decoupled presentation bridge for PHP patterns (no business logic — a label lookup with a graceful degrade). Document that static `templates/*.html` use the `ink/term` Block Bindings source instead.

- [x] **Task 5 — Author the ready-to-run Pest unit test (AC: 1–4)**
  - [x] Create `tests/Unit/I18n/TermsTest.php` (`namespace Ink\Tests\Unit\I18n`, no `ABSPATH` guard — runs under Pest) with Brain Monkey: stub `__()` as an identity passthrough; assert `Terms::label('membership') === 'Lidmaatskap'`, `Terms::label('gradering') === 'Gradering'`, a CPT plural (`Terms::label('storie_plural') === 'Stories'`); `Terms::has('membership') === true` / `Terms::has('nope') === false`; `Terms::all()` contains every seeded key; unknown-key fallback returns the key. Header documents the Brain Monkey assumptions + that the runner arrives with the CI build (18.8).

- [x] **Task 6 — Static verification (no PHP binary — deferred per Epic-1 precedent) (AC: 1–6)**
  - [x] python3 structural scan over the new/edited files: `<?php` + exactly one `declare(strict_types=1)`; `ink-core` files `Ink\I18n`-namespaced + `ABSPATH`-guarded; balanced braces; no closing `?>`; every registry value is a literal `__( '…', 'ink-core' )` (no `__( $var )` anywhere); `ink_term`/`ink_foundation_term`/`Bindings::register` present and wired; `register_block_bindings_source('ink/term', …)` wired on `init` from `Kernel\Plugin`; theme bridge `function_exists`-guarded; no CPT/taxonomy/meta registration (out of scope); no raw superglobals. Record `php -l`/Pest deferral.

### Review Findings

_Code review 2026-06-21 (3-layer adversarial: Blind Hunter / Edge Case Hunter / Acceptance Auditor). Result: 2 decision-needed, 2 patch, 2 defer, 4 dismissed as noise. Status → in-progress (patches left as action items)._

- [x] [Review][Decision] Label casing vs glossary — RESOLVED 2026-06-21 (owner, Cobus): **accept Title-case display labels.** The registry's Title-cased nouns (`Gedig`/`Storie`/`Artikel`/`Borg`/`Vaardigheidsarea`/`Lid`, …) stay as-is — the glossary UI-term column supplies the concept spelling/wording, not the surface casing, which follows WP display-label convention. No code change; AC-1 + the guardrail wording amended to say "Title-case display labels" instead of "follow the glossary's casing". [src/I18n/Terms.php:444-491]
- [x] [Review][Decision] Invented plurals not in glossary — RESOLVED 2026-06-21 (owner, Cobus): the 14 plural labels are confirmed correct **except** `skryfwerk_plural`, corrected `Skryfwerke` → `Skrywes`. The remaining generated plurals (`bydrae_plural`, `genre_plural`, `uitdagingsrondte_plural`, `ster_gradering_plural`, `biblioteek_item_plural`, `opleiding_artikel_plural`, `inkpols_uitgawe_plural`, `uitdaging_plural`) are sanctioned. Follow-up: add the owner-confirmed `skryfwerk`/`Skrywes` (and the other sanctioned plurals) to docs/afrikaans-terms.md so the glossary matches the registry. [src/I18n/Terms.php:86]
- [x] [Review][Patch] APPLIED 2026-06-21 — dropped the `esc_html()` wrapper from the unknown-key `trigger_error` (`sprintf( '… "%s".', $key )`); escaping a non-HTML PHP error message was pointless and risked an "undefined function esc_html()" fatal in the fail-safe path before `wp-includes/formatting.php` loads or under Pest with `WP_DEBUG` true. [wp-content/plugins/ink-core/src/I18n/Terms.php:131]
- [x] [Review][Patch] APPLIED 2026-06-21 — wrapped `ink_foundation_term()` in `if ( ! function_exists( 'ink_foundation_term' ) )` so a child-theme override no longer fatals "Cannot redeclare"; the doc comment's "function_exists-guarded" claim is now accurate for the declaration itself. [wp-content/themes/ink-foundation/functions.php:127]
- [x] [Review][Defer] Block Bindings `resolve()` renders a blank/raw key to front-end HTML when `args.key` is missing/empty/typo'd (coerces to `''` → `Terms::label('')` → returns `''`), with no production signal (`WP_DEBUG` off). Matches the documented "returns the key if unregistered" intent, so deferred — track for a future visible-fallback / key-normalisation decision. [src/I18n/Bindings.php:69-71]
- [x] [Review][Defer] No "called before `init` / text-domain loaded" guard on `Terms::label()` / `ink_term()` — a too-early caller triggers a `_doing_it_wrong` notice on WP 6.7+. Harmless under the no-`.mo` policy (literal still returned); deferred, pre-existing pattern. [src/I18n/Terms.php:125]

## Dev Notes

### What this story is (and is NOT)

- **IS:** the glossary-backed terminology **label registry** (AD-10) — `Ink\I18n\Terms` mapping concept keys to literal Afrikaans `__()` labels seeded from the `afrikaans-terms.md` UI-term column; the `Ink\ink_term()` / `Terms::label()` helpers; the `ink/term` Block Bindings source + `ink_foundation_term()` theme bridge; and the documentation of the glossary↔registry relationship. It extends the project's existing single-definition discipline (enums for IDs) to **UI display labels**. _[Source: epics.md#Story-2.0; architecture.md AD-10; sprint-change-proposal-2026-06-21.md]_
- **IS NOT:** registering CPTs/taxonomies or their labels (2.1/2.2 — they *consume* this registry); building admin field sets (2.4); remediating the Epic 1 theme patterns to route through the registry (a separate scheduled task, sprint-change-proposal §4.5 — "PO's call", likely Epic 17); a full gettext-`.mo` inversion (explicitly rejected — contradicts the no-`.mo` policy); covering DB-content term changes (those remain `wp search-replace`); changing any code ID / slug / enum (AC-4 — unchanged).

### ⭐ The mechanism (key deliverable)

| Surface | API | Used by |
|---|---|---|
| Class API (canonical) | `Ink\I18n\Terms::label('key')` | `ink-core` PHP (CPT/taxonomy label arrays in 2.1/2.2/2.4) |
| Procedural helper | `\Ink\ink_term('key')` | `ink-core` template tags / integrations (mirrors `Ink\ink_core()`) |
| Theme PHP-pattern bridge | `ink_foundation_term('key', $fallback)` | theme `patterns/*.php` — `function_exists`-guarded, never fatals |
| Static HTML bridge | `ink/term` Block Bindings source | theme `templates/*.html` — `<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"ink/term","args":{"key":"gradering"}}}}} -->` |

**Why literals-only matters (AC-3):** `wp i18n make-pot` does a static parse — it can only extract `__( 'literal', 'domain' )`, never `__( $var )`. So every Afrikaans label literal for a controlled-vocabulary concept lives in **one file** (`Terms.php`); everywhere else passes a **key**. That is what makes a term re-decision a one-file edit while keeping the `.pot`/leak-scan surface complete.

**Why Afrikaans literals (not English):** Story 1.10 established Afrikaans as the gettext SOURCE and ships no English `ink-core` `.mo`; so the literal IS what renders. The registry inherits that policy — its values are the Afrikaans words a member reads.

### ⚠️ Guardrails (prevent disasters)

- **NEVER wrap `__()` around a variable** anywhere (`__( $label )`) — it breaks `make-pot`. Dynamic resolution is by KEY through `ink_term()`; literals live only in the registry. _[AC-3; AD-10]_
- **NEVER inline a glossary label** as a bare literal outside the registry. _[project-context.md Afrikaans-first]_
- **NEVER AI-translate or invent Afrikaans.** Every seeded label's spelling/wording traces to `docs/afrikaans-terms.md` (UI-term column); surface casing follows WP display-label convention (Title case), per the 2026-06-21 owner decision — not glossary-verbatim casing. _[project-context.md "No AI-generated Afrikaans"; "afrikaans-terms.md is the glossary source of truth"]_
- **NEVER ship an English `ink-core` `.mo`** — the registry's Afrikaans literals are the source; gettext returns them unchanged. _[1.10 §14.15]_
- **NEVER let the theme fatal when `ink-core` is inactive** — the `ink_foundation_term()` bridge is `function_exists`-guarded with a fallback. _[AC-5; three-layer]_
- **Registry governs LABELS only** — not code IDs/slugs/enums (those stay enum/constant single-source). _[AC-4]_
- **DB content is out of scope** — page bodies/menus/migrated posts remain `wp search-replace`. _[AC-6]_
- **`declare(strict_types=1)` + `ABSPATH` guard** in every new `ink-core` `.php`; `Ink\I18n`-namespaced PascalCase classes, camelCase methods, `ink_` snake_case on the procedural surface; no raw superglobals. _[project-context.md]_
- **Wire the binding source from the Kernel seam** (`Kernel\Plugin::run()` on `init`), not the bootstrap, not a feature module — i18n is a cross-cutting Kernel concern. _[1.10; AD-1]_

### Project Structure Notes

- New `Ink\I18n` namespace (`src/I18n/Terms.php`, `src/I18n/Bindings.php`) — AD-10 names the registry `Ink\I18n\Terms` explicitly. It sits beside (not inside) `Ink\Kernel\I18n` (the loader): the Kernel loads the text domain + admin-language split; `Ink\I18n` holds the label projection. The autoloader maps `Ink\` → `src/`, so `Ink\I18n\Terms` → `src/I18n/Terms.php` with no wiring change.
- `ink_term()` joins `ink_core()` in `src/functions.php` under `namespace Ink` (the established procedural convention) — eagerly required by `src/autoload.php`, so no autoload edit needed.
- Test at `tests/Unit/I18n/TermsTest.php` mirrors `src/I18n/` per the `tests/Unit/{Module}` convention (1.8/1.10 precedent).
- Theme edit is one guarded helper in `functions.php` — presentation infra, no business logic.

### Testing standards summary

No PHP binary / built `vendor/` in-repo (CI harness arrives at 18.8; the Pest scaffold is 1.11). `php -l`/Pest/PHPStan deferred per the Epic-1 precedent; a python3 structural + semantic scan substitutes (see Task 6), and the authored `TermsTest.php` runs once the runner is wired. The test mocks `__()` as identity (Brain Monkey), matching the 1.10 `AdminLanguageTest` pattern.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-2.0 — Terminology label registry; the BDD ACs: registry `Ink\I18n\Terms` maps concept keys to literal `__('<Afrikaans>','ink-core')` seeded from afrikaans-terms.md UI-term column; helper `ink_term('key')`/`Terms::label('key')`; make-pot extracts literals only (never `__($var)`); code IDs/slugs/enums unchanged; theme bridge + Block Bindings for static HTML; documented glossary↔registry relationship]
- [Source: _bmad-output/planning-artifacts/architecture.md — AD-10 Terminology label registry (`Ink\I18n\Terms`, literal `__()`, seeded from the UI-term column; registry file is the make-pot extraction surface; never `__($var)`; complements the enum/code-ID single-source rule + no-English-`.mo` policy; Block Bindings API as the bridge for static block-template HTML; DB content out of scope); AD-1 modular monolith (Kernel + feature modules, facade discipline); Kernel "i18n loader" concern]
- [Source: _bmad-output/planning-artifacts/sprint-change-proposal-2026-06-21.md — terminology management strategy: §2 technical impact (registry keyed by concept; make-pot literals-only caveat; theme bridge (a) + Block Bindings (b); DB content out of scope); §3 recommended mechanism (glossary-backed label registry over .mo inversion); §4.1 new Story 2.0; §4.6 AD-10; §5 success criteria (one-file edit; make-pot clean; Epic 2 registers through it; leak-scan inspects it; DB content remains search-replace)]
- [Source: _bmad-output/project-context.md — "Controlled-vocabulary UI labels come from the ink-core terminology registry (single-source, glossary-backed literal `__()`) … Never inline a glossary label as a bare literal outside the registry"; "afrikaans-terms.md is the glossary source of truth"; "No AI-generated Afrikaans"; "Heading casing: sentence case"; line 42 enum single-source; line 54 fixed migration-load-bearing CPT/taxonomy slugs incl. `skryfwerk` catch-all; three-layer "No business logic in the theme"; "declare(strict_types=1) in all ink-core PHP"; "Prefix everything ink_/Ink\"]
- [Source: docs/afrikaans-terms.md — UI-term column (Deel 1): membership→Lidmaatskap (line 43); Gradering (54); Brons/Silwer/Goud/Meester (55–58); bydrae (69); gedig "Gedigte" (70); storie "Stories" (71); artikel "artikels" (72); biblioteekitem (86); hulpbronartikel (98); uitdaging (108); uitgawe (128); borg "borge" (156); vaardigheidsarea (100); taxonomy slugs genre/vaardigheid/uitdagingsrondte/ster_gradering (271–273); Onderhoud note (287) "Die UI-term-kolom word in 'n masjienleesbare register (ink-core Terms) geprojekteer … (Story 2.0 / AD-10)". NOTE: `skryfwerk` is absent from Deel 5 slug list (lines 243–250) though it is the epics.md 2.1 / project-context catch-all CPT — flagged for a glossary addition]
- [Source: wp-content/plugins/ink-core/src/Kernel/I18n.php (1.10) — `ink-core` text-domain load on `init`; admin-language split; no-English-`.mo` Afrikaans-source policy the registry inherits]
- [Source: wp-content/plugins/ink-core/src/Kernel/Plugin.php (1.7/1.10) — `run()` wires cross-cutting Kernel concerns on `init` via first-class callables; the seam this story uses to wire `Bindings::register()`]
- [Source: wp-content/plugins/ink-core/src/functions.php (1.7) — `namespace Ink`; `Ink\ink_core()` precedent for the `ink_` procedural surface; eagerly required by src/autoload.php]
- [Source: wp-content/themes/ink-foundation/functions.php (1.10) — `init`-hooked `load_theme_textdomain('ink-foundation', …)`; presentation-only; the file the `ink_foundation_term()` bridge is added to]
- [Source: WordPress Block Bindings API — `register_block_bindings_source( $name, [ 'label', 'get_value_callback', 'uses_context' ] )` (WP 6.5+); paragraph/heading/button `content`/`url`/`text`/`alt` bindable; stable in WP 7.0]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m] (Opus 4.8, 1M context)

### Debug Log References

- **`php -l` not run — no PHP binary in-env** (`which php` → not found) and no built `vendor/` (Composer deps fetched at CI build, AD-4). Pest/PHPStan/WPCS deferred to the 18.8 CI buildout, per the Epic-1 deferred-verification precedent. Substituted a python3 structural + semantic scan.
- **Static scan (`$CLAUDE_JOB_DIR/tmp/verify_20.py`) — 72 PASS / 0 FAIL** over the 6 created/edited files (`src/I18n/Terms.php`, `src/I18n/Bindings.php`, `src/functions.php`, `src/Kernel/Plugin.php`, theme `functions.php`, `tests/Unit/I18n/TermsTest.php`):
  - **Structure:** `<?php` + exactly one `declare(strict_types=1)` + balanced braces + no closing `?>` in all runtime files; `Ink\I18n` namespace + `ABSPATH` guard in both new Kernel/I18n files; theme keeps its header.
  - **AC-1:** Terms registry has 38 literal `__( '…', 'ink-core' )` labels; all seed keys present (`membership`/`gradering`/`storie_plural`/`skryfwerk`/`vaardigheid`/`ster_gradering`).
  - **AC-3:** **no `__( $var )` in code** anywhere (the only textual occurrence is the docblock documenting the anti-pattern); the registry holds literals only — make-pot extractable.
  - **AC-2:** `label()` fail-safe returns the key + `WP_DEBUG`-gated `trigger_error`; `has()`/`all()` present; map rebuilt per call (not memoized → locale-switch-safe).
  - **AC-5:** `Bindings` registers `ink/term` via `register_block_bindings_source` (`function_exists`-guarded for WP<6.5), `resolve()` delegates to `Terms::label()`; wired on `init` from `Kernel\Plugin::run()`; theme `ink_foundation_term()` is `function_exists`-guarded.
  - **Scope:** no `register_post_type`/`register_taxonomy`/`register_meta` in any file (2.1/2.2/2.4 scope); no raw superglobals.
  - **Test:** `<?php`+`declare`, `Ink\Tests\Unit\I18n` namespace, **no** `ABSPATH` guard (correct for Pest), `__()` identity stub.
- **Working tree:** the `ink-core` files live under the (untracked-until-Epic-1-commit) plugin tree; this story's edits are additive. No `.mo` introduced.

### Completion Notes List

- **Built the terminology label registry (AD-10) — the single source for code-rendered UI labels.** `Ink\I18n\Terms` maps 38 glossary concept keys to literal `__( 'Afrikaans', 'ink-core' )` definitions seeded from the `afrikaans-terms.md` UI-term column (core concepts + all 9 CPT singular/plural + 4 taxonomy singular/plural + sections). `label()`/`has()`/`all()` expose it; `all()` is the 17.4 leak-scan inspection surface.
- **AC-2 helper:** `\Ink\ink_term('key')` added to `src/functions.php` (mirrors the `Ink\ink_core()` procedural precedent), delegating to `Terms::label()`. Callers pass keys, never literals. Unknown key fails safe (returns the key + a `WP_DEBUG` notice) — no fatal, no English string to a visitor.
- **AC-3 make-pot:** literals live ONLY in the registry; no `__( $var )` anywhere. The registry file is the extraction surface — a term re-decision is a one-file edit while `.pot`/leak-scan stays complete.
- **AC-4 boundary:** registry governs display LABELS only; code IDs/slugs/enums (`Ink\Kernel\Tier`, the 2.1/2.2 slug constants) remain the enum/constant single-source — unchanged.
- **AC-5 bridges:** `Ink\I18n\Bindings` registers the `ink/term` Block Bindings source (the bridge for static `templates/*.html`), wired on `init` from the Kernel seam (not the bootstrap, not a feature module). `ink_foundation_term()` added to the theme as the decoupled PHP-pattern bridge (`function_exists`-guarded → graceful fallback, no theme fatal, no business logic). **Did NOT** remediate Epic 1 patterns (separate scheduled task, sprint-change-proposal §4.5).
- **AC-6 docs:** `Terms` class docblock documents glossary = human source of truth, registry = machine projection of the UI-term column, the make-pot literals-only caveat, the enum/slug boundary, and DB content out of scope (`wp search-replace`).
- **Glossary gap flagged:** `skryfwerk` (the epics.md 2.1 / project-context line 54 migration catch-all CPT) is absent from `afrikaans-terms.md` Deel 5's slug list — seeded here as `Skryfwerk`/`Skrywes`; recommend the glossary add it under Werk en indiening (a glossary edit, owner-confirmed, not an AI translation).
- **Test:** `tests/Unit/I18n/TermsTest.php` ready-to-run (Brain Monkey `__()` identity stub) covering known labels, CPT singular/plural, taxonomy labels, `has()`, `all()`, and the unknown-key fail-safe. Runs once the runner is wired (18.8).

### File List

- `wp-content/plugins/ink-core/src/I18n/Terms.php` (new) — `Ink\I18n\Terms` registry: concept-key → literal `__()` Afrikaans-label map (38 entries) seeded from the UI-term column; `label()` (fail-safe + `WP_DEBUG` notice), `has()`, `all()`; class docblock documents the glossary↔registry relationship, make-pot caveat, enum/slug boundary, DB-content scope.
- `wp-content/plugins/ink-core/src/I18n/Bindings.php` (new) — `Ink\I18n\Bindings` registers the `ink/term` Block Bindings source (WP<6.5-guarded); `resolve()` maps a binding `key` arg to `Terms::label()`.
- `wp-content/plugins/ink-core/src/functions.php` (modified) — added the `\Ink\ink_term( string $key ): string` procedural helper (guarded; delegates to `Terms::label()`), beside the existing `Ink\ink_core()`.
- `wp-content/plugins/ink-core/src/Kernel/Plugin.php` (modified) — wired `add_action( 'init', \Ink\I18n\Bindings::register( ... ) )` in `run()` (cross-cutting i18n concern, Kernel seam) beside the existing i18n wiring.
- `wp-content/themes/ink-foundation/functions.php` (modified) — added `ink_foundation_term( string $key, string $fallback = '' ): string` theme bridge (`function_exists`-guarded → fallback). Presentation infra only.
- `tests/Unit/I18n/TermsTest.php` (new) — ready-to-run Pest + Brain Monkey unit test for `Ink\I18n\Terms`.
- `_bmad-output/implementation-artifacts/2-0-terminology-label-registry.md` (new) — this story file.
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (modified) — `epic-2` backlog → in-progress; `2-0-…` backlog → ready-for-dev → in-progress → review.

## Change Log

| Date | Change |
|---|---|
| 2026-06-21 | Story created (context-engineered) — terminology label registry (AD-10): `Ink\I18n\Terms` concept-key→literal-`__()` map seeded from the afrikaans-terms.md UI-term column; `Ink\ink_term()`/`Terms::label()` helpers; `ink/term` Block Bindings source wired from the Kernel seam; `ink_foundation_term()` theme bridge; documented glossary↔registry relationship. Status → ready-for-dev. |
| 2026-06-21 | Implemented (Tasks 1–6). Created `Ink\I18n\Terms` (38 literal `__('Afrikaans','ink-core')` labels seeded from the UI-term column; `label()` fail-safe + `WP_DEBUG` notice; `has()`/`all()`); `Ink\I18n\Bindings` registering the `ink/term` Block Bindings source (WP<6.5-guarded) wired on `init` from `Kernel\Plugin::run()`; `\Ink\ink_term()` procedural helper in `src/functions.php`; `ink_foundation_term()` theme bridge (`function_exists`-guarded). Registry governs labels only (code IDs/slugs/enums unchanged, AC-4); no `__( $var )` anywhere (make-pot clean, AC-3); glossary↔registry relationship + DB-content boundary documented (AC-6). Flagged `skryfwerk` glossary-slug-list gap. Ready-to-run Pest test authored. `php -l`/Pest deferred to 18.8; python3 static scan — 72 PASS / 0 FAIL. Status → review. |
| 2026-06-21 | Code review (3-layer adversarial). Owner-confirmed plural set: corrected `skryfwerk_plural` `Skryfwerke` → `Skrywes` (`Terms.php:86`); remaining generated plurals sanctioned (review decision D2 resolved). Propagated the corrected term through the story doc references. Remaining open: review D1 (singular-noun casing vs glossary) + 2 patches. |
| 2026-06-21 | Code review D1 resolved (owner): accept Title-case display labels — registry unchanged; AC-1 + guardrail wording amended from "follow the glossary's casing" to "Title-case display labels (glossary supplies spelling/wording, not surface casing)". Both review decisions now resolved; 2 patches remain open. |
| 2026-06-21 | Both review patches applied: removed `esc_html()` from the unknown-key `trigger_error` (`Terms.php:131`); wrapped `ink_foundation_term()` in `function_exists` (`functions.php:127`). python3 structural re-scan clean (balanced braces/parens, strict_types, no `?>`). All review findings closed (2 decisions resolved, 2 patches applied, 2 defers logged). Status → done. |
