---
baseline_commit: af61b73
---

# Story 8.1: Ontdek section + works archive

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a reader,
I want a discovery hub with a works archive,
so that I can browse published writing. (FR-32)

## Acceptance Criteria

**Given** the Ontdek section
**When** it renders
**Then** it provides the reading/discovery hub and a works archive with date/archive browse (single-piece reading lives in Epic 7).

1. The **Discovery module is activated** — `Ink\Discovery\Module` (today a reserved no-op) is registered in the `ink-core.php` bootstrap and `register()` wires this story's surface(s). This is the first live Discovery code (AD-7).
2. A server-rendered **works archive** block `ink/ontdek-werke` lists **published bydraes** (`gedig`/`storie`/`artikel` — the `skryfwerk` migration bucket is excluded, never reader-facing), **newest-first**, paginated. Reads stay **server-rendered via `WP_Query`** (AD-7 — no REST for discovery listings).
3. The archive supports **date/archive browse**: an optional year (and optional month) narrows the listing via a `date_query`; out-of-range/garbage values are ignored (fall back to the unfiltered newest-first listing). Page position is driven by a paged value.
4. Each work renders as a card: its **type badge** (Afrikaans, from the `Terms` registry — `Terms::label($post_type)`), the **title** (linked, escaped), and the **author** (escaped). Card markup follows the existing `is-style-card` convention so it inherits the design-system styling.
5. When the archive has **no matching works**, it renders a single Afrikaans empty-state line (composed from authored glossary copy, no new unauthored string) — NOT a blank section.
6. An **Ontdek hub** surface exists in the theme: a locked page pattern (`ink-foundation/ontdek`) + a `page-ontdek.html` template, built on the archive-discovery archetype (archetype-b), embedding the `ink/ontdek-werke` block. It carries the Bydraes/Skrywers **tab scaffold** (Bydraes active; the Skrywers tab + the filter/sort controls are realised in Stories 8.2/8.3 — this story is the hub shell + default works listing).
7. **Three-layer & conflation-clean:** the block + query live in `ink-core` (`Ink\Discovery`), referencing only `Ink\Content\PostTypes` (the migration-load-bearing slug source) + the `Terms` registry + WP core — **zero** `Ink\Tiers`/`Ink\Entitlement`. The archive is **not** entitlement-gated (browsing published work is open). No business logic in the theme.
8. **Deferred Epic-2 item closed (term-archive/discovery URLs now land):** the `ster_gradering` taxonomy rewrite slug is restored to the **constant single-source** — all four taxonomy rewrite slugs derive from their code-id constant (the hand-typed `'ster-gradering'` literal at `Taxonomies.php:126` is removed). The **public URL stays `/ster-gradering/`** (no URL change, no redirect needed); the value is now derived, not duplicated.

## Tasks / Subtasks

- [x] Task 1: `Ink\Discovery\WorksArchive` server block (AC: #2, #3, #4, #5, #7)
  - [x] `const BLOCK = 'ink/ontdek-werke'`; `const PER_PAGE = 12`; browse query-var constants (`PAGED_VAR`/`YEAR_VAR`/`MONTH_VAR`).
  - [x] `public static function readableTypes(): array` → `[PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL]` (mirrors `SuggestedReads::readableTypes()`; `skryfwerk` excluded — single-source via `PostTypes`).
  - [x] `public static function queryArgs(int $paged, int $perPage, ?int $year, ?int $month): array` — **pure**. Builds: `post_type`=readableTypes, `post_status`='publish', `posts_per_page`=$perPage, `paged`=max(1,$paged), `orderby`='date', `order`='DESC', `ignore_sticky_posts`=true. Adds a `date_query` only for a sane 4-digit year (`['year'=>$year]`, plus `'month'=>$month` only when 1–12) via the private `dateClause()`. Sorts are 8.2.
  - [x] `public static function render(): string` — gather inputs defensively via `requestInt()` (`filter_input(INPUT_GET, …, FILTER_SANITIZE_NUMBER_INT)` + `absint`, falling back from the query var); run `new \WP_Query(self::queryArgs(...))`, map `$query->posts` to card arrays `{title, permalink, type, author}`, then `toHtml($cards, $nav)` with `$nav` = `{paged, max_pages}`. Read-only GET browse (idempotent, no nonce).
  - [x] `public static function toHtml(array $cards, array $nav): string` — **pure**: heading + a card per work (type badge via `Terms::label`, linked escaped title, escaped author) on `is-style-card`; prev/next pagination via `paginationHtml()` only when `max_pages > 1`; empty-state line when `$cards === []`. Every dynamic value escaped.
- [x] Task 2: Activate the Discovery module (AC: #1, #7)
  - [x] `Ink\Discovery\Module::register()` → `( new WorksArchive() )->register()`; `WorksArchive::register()` adds `register_block_type` on `init` (guarded, mirroring `ReactionTotals`).
  - [x] Wired in `ink-core.php`: `addModule( 'discovery', new Discovery\Module() )`.
  - [x] `deptrac.yaml`: extended the `Discovery` ruleset with `Content` (Story-8.1 comment, mirroring Engagement→Content 7.6). Discovery→Content is Allowed.
- [x] Task 3: `ster_gradering` rewrite-slug single-source (AC: #8)
  - [x] `Content/Taxonomies.php`: removed the four per-definition `'rewrite'` literals (incl. `'ster-gradering'`); added `private static function rewriteSlug(string $code): string { return str_replace('_', '-', $code); }`, applied per-slug in `args(string $slug, array $def)` (now passed the slug from `register()`). `ster_gradering`→`ster-gradering` (URL preserved), the other three identity.
- [x] Task 4: Theme hub (AC: #6) + copy
  - [x] New pattern `patterns/ontdek.php` — `archive-intro` (authored Ontdek H1/intro), the Bydraes/Skrywers tab scaffold (locked pills; Bydraes active, Skrywers placeholder until 8.3), then `<!-- wp:ink/ontdek-werke /-->`. Tab labels read from the `ink_foundation_term()` registry bridge (`bydrae_plural`/`skrywer_plural`), never bare literals. Structure locked.
  - [x] New template `templates/page-ontdek.html` — header part + the `ink-foundation/ontdek` pattern + footer part (mirrors `page-skryf.html`). Binding a `page` at slug `ontdek` is editorial; nav link is Epic 15/16 (noted).
  - [x] Empty-state composed from authored glossary (`sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'bydrae_plural' ) )`); heading = `Terms::label('bydrae_plural')`. **No new unauthored Afrikaans.** Added `skrywer_plural => 'Skrywers'` to the Terms registry (glossary-backed plural of the existing `skrywer` noun) for the tab label single-source.
- [x] Task 5: Tests + gates (AC: all)
  - [x] `tests/Unit/Discovery/WorksArchiveTest.php` (8 tests): `queryArgs` — three bydrae types, publish, newest-first, paged clamp, date_query only for a valid year + month only for 1–12, garbage year degrades; `toHtml` — heading + a card per work + escaping, prev/next only when `max_pages>1`, empty-state line (non-vacuous).
  - [x] `tests/Unit/Content/TaxonomiesTest.php` (extended): every rewrite slug is constant-derived; `ster_gradering`→`ster-gradering`; URL carries no underscore (non-vacuous).
  - [x] `tests/Unit/Discovery/OntdekTemplateTest.php`: `page-ontdek.html` + `ontdek.php` embed `wp:ink/ontdek-werke`, carry the tab scaffold + registry-bridge labels (non-vacuous positive markers first).
  - [x] `composer test:unit` green (507 passed, 1 skipped; +11); `composer stan` clean; `composer copy:scan` no new debt; `composer cs` **0 errors** (my files clean; absorbed pre-existing Epic-7 tooling-drift autofixes + missing `translators:` comments); `composer deptrac` 3 pre-existing `Kernel→Content` violations (0 new), Discovery→Content Allowed.

## Dev Notes

- **Server-rendered discovery, not REST** [Source: architecture.md#AD-7 §3]: discovery listings are server-rendered via `WP_Query`; "custom *dynamic* blocks (server-rendered via `render_callback` in `ink-core`) only where a component is both dynamic and tied to INK logic." A unified multi-CPT works archive is exactly that — a server block is the right shape (same as `SuggestedReads`/`ReactionTotals`). The **bydraes tab uses extended Query Loop** per AD-7; here the unified cross-CPT archive is a custom block, and 8.2 extends *this* block with the type filter + sorts (Nuut/Opspraakwekkend/Mees geliefd) rather than re-deriving a Query Loop.
- **Mirror the `SuggestedReads` house style** [Source: src/Engagement/SuggestedReads.php]: pure `queryArgs` + pure `toHtml` + thin `render`; `BLOCK` constant single-source; `readableTypes()` from `PostTypes` constants; `register()` adds the block on `init` with a `function_exists` guard. Differences here: the archive is the page's purpose, so an **empty state renders a message** (SuggestedReads renders `''`); and it paginates (carry `paged`/`max_pages`).
- **Readable types & slug single-source** [Source: src/Content/PostTypes.php; `bydraeTypes()`]: query `GEDIG/STORIE/ARTIKEL`; exclude `SKRYFWERK` (migration bucket). Reference the constants — this is the new, Allowed **Discovery→Content** deptrac edge (mirrors Submission→Content 6.1 and Engagement→Content 7.6).
- **`ster_gradering` slug fix** [Source: deferred-work.md "Deferred from: code review of Story 2.2"; Taxonomies.php:122-127]: the literal `'ster-gradering'` breaks the `rewrite => self::CONST` single-source the other three use; "Standardise when term-archive templates land (Epic 8)." Derive every rewrite from the constant (`str_replace('_','-', $code)`) so the URL is preserved AND single-sourced. The other three constants have no underscore → the transform is a no-op for them; verify no URL changes.
- **Terms registry for labels** [Source: src/I18n/Terms.php]: type badges via `Terms::label( get_post_type() )`; plural nouns (`bydrae_plural` = "Bydraes") for the heading/empty-state. Never inline a controlled-vocabulary noun.
- **Date/archive browse**: keep `queryArgs` pure and inputs sanitised (`absint`); validate year (4-digit) + month (1–12) inside `queryArgs` so a hostile/garbage query string degrades to the unfiltered listing. Use a custom paged query var (`werke_bladsy`) to avoid colliding with WP page pagination on the host page.
- **Theme conventions** [Source: patterns/archetype-b-archive-discovery.php, featured-grid.php, page-skryf.html]: cards use `is-style-card`; pills use `is-style-pill`/`is-style-outline`; structural groups are locked (`lock":{"move":true,"remove":true}`); colours/spacing are `theme.json` tokens only (no hardcoded values). User-facing pattern strings go through `esc_html_e(…, 'ink-foundation')` (Gate D pattern-i18n convention — `composer copy:scan` flags bare text nodes).
- **No anti-pattern**: this is shared-taxonomy/auto-surfacing discovery — no per-item editorial linking (Principle 8). The archive query is generic over published bydraes.

### Project Structure Notes

- New ink-core: `src/Discovery/WorksArchive.php`; MOD `src/Discovery/Module.php` (activate), `ink-core.php` (addModule 'discovery'), `deptrac.yaml` (Discovery → Content), `src/Content/Taxonomies.php` (rewrite single-source).
- New theme: `patterns/ontdek.php`, `templates/page-ontdek.html`.
- New tests: `tests/Unit/Discovery/WorksArchiveTest.php`; MOD `tests/Unit/Content/TaxonomiesTest.php` (+ pattern-embed guard).
- deptrac after this story: Discovery → [Kernel, Content]. No Entitlement/Tiers edge (conflation-clean).
- Editorial/Epic-15+ follow-ups (note, don't build): binding a `page` at slug `ontdek`; the Ontdek nav link; the Skrywers tab activates in 8.3; filter/sort controls in 8.2.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 8.1]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-7]
- [Source: wp-content/plugins/ink-core/src/Engagement/SuggestedReads.php, ReactionTotals.php]
- [Source: wp-content/plugins/ink-core/src/Content/PostTypes.php, Taxonomies.php]
- [Source: wp-content/plugins/ink-core/src/I18n/Terms.php]
- [Source: wp-content/themes/ink-foundation/patterns/archetype-b-archive-discovery.php, page-skryf.html]
- [Source: _bmad-output/implementation-artifacts/deferred-work.md#Story 2.2, #Story 2.5]
- [Source: _bmad-output/project-context.md#three-layer, #conflation-rule, #shared-taxonomy, #afrikaans-first]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 8)

### Debug Log References

- `composer stan` runs outside the sandbox (PHPStan parallel-worker TCP bind is blocked under the sandbox — `EPERM` on `tcp://127.0.0.1:0`), as in prior Epic 5–7 stories. No code issues; "No errors".
- Baseline `composer cs` was already RED at `af61b73` (pre-existing Epic-7 debt): 10 `Squiz.Commenting.FunctionComment.SpacingAfterParamType` errors across `ReactionCounts`/`ResponsesList`/`SuggestedReads` (phpcs/PHPCSUtils tooling drift in aligned-param docblocks) + 3 `_n()` `MissingTranslatorsComment` errors in `ReactionCounts`. Absorbed as repo-hygiene (pure formatting autofix via `cs:fix` + three one-line `translators:` comments). 8.1's own files are 100% cs-clean.

### Completion Notes List

- `ink/ontdek-werke` server block surfaces the Ontdek works archive: all published bydraes (gedig/storie/artikel via `PostTypes` constants — `skryfwerk` bucket excluded), newest-first, paginated, with defensive year/month date-archive browse. Server-rendered via `WP_Query` (AD-7 — no REST). Pure `queryArgs`/`toHtml` + thin `render`, mirroring the `SuggestedReads` house style; unlike SuggestedReads it renders an empty-state line (the archive is the page's purpose) and paginates.
- Discovery module is now LIVE (was a reserved no-op): `Module::register()` wires `WorksArchive`; bootstrap `addModule('discovery', …)`; deptrac Discovery→Content edge added (Allowed; mirrors Submission→Content 6.1 / Engagement→Content 7.6). Conflation-clean — zero Tiers/Entitlement; the archive is not entitlement-gated (browsing published work is open).
- Closed the deferred 2.2 single-source gap: every taxonomy rewrite slug is now derived from its code-id constant (`str_replace('_','-', $code)`). `ster_gradering`→`ster-gradering` (public URL preserved, no redirect needed); the hand-typed `'ster-gradering'` literal is gone. The other three are identity transforms.
- Ontdek hub: `patterns/ontdek.php` (archive-intro + Bydraes/Skrywers tab scaffold + the works block) and `templates/page-ontdek.html`. Tab labels read from the terminology registry via `ink_foundation_term('bydrae_plural'|'skrywer_plural')` — added `skrywer_plural => 'Skrywers'` to the registry (glossary-backed plural, single-source). The Skrywers tab + filter/sort controls realise in 8.2/8.3.
- All copy is authored/glossary-backed (empty state reuses the existing "Geen %s gevind nie." frame; no AI Afrikaans). Pagination labels "Vorige"/"Volgende" are authored `__()` source literals (dictionary-standard nav terms) — copy-debt to ratify into the glossary on the next copy pass (consistent with the Epic-7 engagement copy-debt note).
- Tests 496→507 (+11); cs 0 errors; stan clean; copy:scan no new debt; deptrac 3 pre-existing violations (0 new; Discovery→Content Allowed).

### Review Notes (for the Epic-8 code review)

- **Pre-existing slow-query WARNINGS remain** (`SuggestedReads` tax_query, `ResponseStore` meta_query) — present in the committed `af61b73` baseline (`SuggestedReads`' tax_query warning shipped in "cs-clean" 7.6), inherent to those bounded queries, not 8.1's. Left untouched (warning-level; 0 errors is the bar).
- **Term-image attachment-validity** (deferred 2.5: `wp_attachment_is_image` where genre/vaardigheid images render) is NOT closed here — 8.1's works archive renders no term images. It lands with the genre rendering in Story 8.3 (skrywers/genre filter). Tracked.

### File List

- `wp-content/plugins/ink-core/src/Discovery/WorksArchive.php` (NEW — `ink/ontdek-werke` works-archive block)
- `wp-content/plugins/ink-core/src/Discovery/Module.php` (MOD — activate; wire WorksArchive)
- `wp-content/plugins/ink-core/ink-core.php` (MOD — addModule 'discovery')
- `deptrac.yaml` (MOD — Discovery → Content)
- `wp-content/plugins/ink-core/src/Content/Taxonomies.php` (MOD — rewrite-slug single-source via `rewriteSlug()`)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (MOD — `skrywer_plural` registry key)
- `wp-content/themes/ink-foundation/patterns/ontdek.php` (NEW — Ontdek hub pattern)
- `wp-content/themes/ink-foundation/templates/page-ontdek.html` (NEW — Ontdek page template)
- `wp-content/themes/ink-foundation/theme.json` (MOD — `.ink-ontdek-werke` token styles)
- `tests/Unit/Discovery/WorksArchiveTest.php` (NEW)
- `tests/Unit/Discovery/OntdekTemplateTest.php` (NEW)
- `tests/Unit/Content/TaxonomiesTest.php` (MOD — rewrite-slug single-source guard)
- `wp-content/plugins/ink-core/src/Engagement/ReactionCounts.php` (MOD — pre-existing Epic-7 cs fix: `translators:` comments)
- `wp-content/plugins/ink-core/src/Engagement/ResponsesList.php` (MOD — pre-existing Epic-7 cs autofix: docblock param spacing)
- `wp-content/plugins/ink-core/src/Engagement/SuggestedReads.php` (MOD — pre-existing Epic-7 cs autofix: docblock param spacing)
- `_bmad-output/implementation-artifacts/8-1-ontdek-section-works-archive.md` (NEW — this story)
