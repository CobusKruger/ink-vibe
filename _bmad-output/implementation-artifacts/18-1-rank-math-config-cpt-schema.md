---
baseline_commit: ef775c1
---

# Story 18.1: Rank Math config + CPT schema

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a site owner,
I want SEO via Rank Math from the start,
so that URLs, schema, and sitemaps are correct. (NFR-4)

## Acceptance Criteria

1. **Given** Rank Math **When** configured **Then** sitemaps, meta, breadcrumbs, and **native schema for `gedig`/`storie`/`artikel`** exist; the Rank Math importer runs as a safety net; InkPols OG images verified, then Yoast deactivated (deliberate override of the transition guide, §14.11).
2. **The ink-core code deliverable** is a defensive `Ink\Seo` module that sets the correct schema `@type` for the three reader-facing INK CPTs in Rank Math's JSON-LD output — **without** reimplementing SEO (Rank Math owns sitemaps/meta/breadcrumbs; ink-core only refines the schema `@type` per CPT, which Rank Math cannot infer from a custom post type). `gedig` → `CreativeWork`, `storie` → `CreativeWork`, `artikel` → `Article` (sentence-rationale in Dev Notes).
3. **Inert without Rank Math:** the integration hooks Rank Math's documented `rank_math/json_ld` filter and is a no-op when Rank Math is absent (guarded by an overridable `rankMathActive()` seam, per the Brain-Monkey test-isolation rule — no inline `function_exists()` a test can't vary). No fatal, no schema emitted by ink-core itself.
4. **Single source for the CPT→type map** — read the reader-facing CPT slugs from `Ink\Content\PostTypes::readableTypes()` (the migration-load-bearing single source); never duplicate the `gedig`/`storie`/`artikel` literals. A non-readable/unknown CPT yields no override (Rank Math's default stands).
5. **Runbook documents the manual ops** that need a running site (cannot be code): the fresh Rank Math config (titles/meta, sitemap inclusion of the public CPTs, breadcrumbs), the importer-as-safety-net step, InkPols OG-image verification, and the **deliberate §14.11 override** — Yoast deactivated after verification (the transition guide kept Yoast as the default; this story overrides it). `docs/seo-rank-math-runbook.md`.
6. **Non-vacuous tests** prove the schema map and the filter: `defaultSchemaTypeFor()` returns the right `@type` for each readable CPT and `null` for a non-readable/unknown CPT; the `rank_math/json_ld` callback rewrites the article node's `@type` for an INK CPT and leaves a non-INK post untouched; the callback is inert when `rankMathActive()` is false.
7. **Three-layer + conflation clean:** all logic in `ink-core` (zero SEO logic in the theme); `Ink\Seo` references neither `Ink\Tiers` nor `Ink\Entitlement` (SEO schema is not gated on membership or Gradering). New deptrac layer `Seo` → allowed deps `Kernel`, `Content`.
8. **Gates green:** `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan` all clean; baseline unchanged.

## Tasks / Subtasks

- [x] Task 1: Create the `Ink\Seo` module skeleton (AC: #2, #3, #7)
  - [x] `src/Seo/Module.php` implements `Ink\Kernel\Module`; `register()` wires `( new SchemaTypes() )->register()`.
  - [x] `src/Seo/Api.php` reserved facade exposing `defaultSchemaTypeFor( string $cpt ): ?string`.
  - [x] Register the module in `ink-core.php` `plugins_loaded` (`addModule( 'seo', new Seo\Module() )`).
- [x] Task 2: `src/Seo/SchemaTypes.php` — the CPT→schema map + Rank Math seam (AC: #2, #3, #4)
  - [x] Pure `defaultTypeFor( string $cpt ): ?string` mapping `PostTypes::readableTypes()` → `@type` (gedig/storie → `CreativeWork`, artikel → `Article`); `null` for anything else.
  - [x] `register()` adds the `rank_math/json_ld` filter only when `rankMathActive()` is true.
  - [x] `filterJsonLd( array $data, $jsonld ): array` — for a singular INK CPT, set the schema article/richSnippet node's `@type` to the mapped value; leave non-INK / non-singular untouched. Article-family detection covers `Article`/`BlogPosting`/`NewsArticle` (Rank Math's configurable article types).
  - [x] `protected function rankMathActive(): bool` (overridable seam, defaults to a Rank Math class/constant check) and a `protected function currentPostType(): string` seam for `get_post_type()`.
- [x] Task 3: Deptrac layer (AC: #7)
  - [x] Added `Seo` layer (`#^Ink\\Seo\\.*#`) + ruleset `Seo: [Kernel, Content]` with the standard "reads the readable-CPT slug single source" rationale comment.
- [x] Task 4: Runbook doc (AC: #1, #5)
  - [x] `docs/seo-rank-math-runbook.md`: fresh config, sitemap/breadcrumb settings, importer safety net, InkPols OG verification, §14.11 Yoast-deactivation override, and the note that the ink-core schema seam supplies the per-CPT `@type`.
- [x] Task 5: Tests (AC: #6)
  - [x] `tests/Unit/Seo/SchemaTypesTest.php` (12 tests): map correctness + null/unknown path + "covers exactly readableTypes" guard; filter rewrites `@type` for gedig/storie/artikel incl. BlogPosting subtype, untouched for non-INK posts and non-Article nodes; `register()` wires the filter when active and is inert (asserts `add_filter` never called) when absent.
- [x] Task 6: Run gates (AC: #8)
  - [x] `composer test:unit` ✓ (1037 passed, 1 skipped, +11); `composer cs` ✓ (new Seo files 0 errors/0 warnings; 2 unrelated pre-existing Engagement slow-query warnings on baseline); `php -l` ✓; `composer stan` ✓ (No errors — needs sandbox network for its worker pool); `composer copy:scan` ✓ (8/8 baseline unchanged); `composer deptrac` — new `Seo → Content` edge is Allowed, **no new violations**.

## Dev Notes — completion addendum

**Pre-existing deptrac debt surfaced (NOT from this story, flag for retro):** deptrac
reports 3 violations `Ink\Kernel\Activation → Ink\Content\PostTypes`
(`grantContentCaps()`/`revokeContentCaps()` at activation, `Activation.php:73,114`).
This edge exists on baseline `ef775c1` (Kernel ruleset is `Kernel: ~`) and is
unrelated to the Seo module — no Seo class appears in any violation. Prior epics
read deptrac's "Errors: 0" line as green; the 3 violations were below a truncated
report tail. Left untouched (out of 18.1 scope; Kernel/Content concern). Recommend
the Epic 18 review/retro decide: either move the capability-type constants to
Kernel, or add an explicit deptrac allowance for the activation cap-grant.

## Dev Notes

### What is code vs. ops here
Rank Math is a configured platform plugin (project-context: "do not reimplement"). Sitemaps, meta, breadcrumbs, and the importer are **admin configuration on a running site** → runbook. The one thing Rank Math *cannot* infer is the correct schema `@type` for a custom post type (it defaults all singulars to `Article`). That per-CPT `@type` refinement is the legitimate `ink-core` code deliverable, expressed as a thin, inert-without-Rank-Math integration on the documented `rank_math/json_ld` filter.

### Schema `@type` rationale
- `gedig` (poem) and `storie` (short story) are creative works → `CreativeWork` (schema.org has no first-class Poem/ShortStory type in Rank Math's set; `CreativeWork` is the accurate supertype and is valid structured data).
- `artikel` (article) → `Article` (Rank Math's natural default; mapped explicitly so the single source is complete and self-documenting).
- `skryfwerk` is the unclassifiable migration bucket and is **not** reader-facing (`readableTypes()` excludes it) → no override.

### Architecture compliance (project-context.md)
- **Single source:** read `Ink\Content\PostTypes::readableTypes()` — never re-list gedig/storie/artikel. New deptrac edge `Seo -> Content` mirrors the Discovery/Submission/Library → Content slug-registry edges.
- **Brain-Monkey isolation:** the Rank Math presence check and `get_post_type()` read are **protected overridable seams** (`rankMathActive()`, `currentPostType()`), not inline `function_exists()`/static calls — so tests vary them by subclass-and-override, not global stubbing (project-context Testing Rules).
- **Conflation rule:** `Ink\Seo` carries zero `Ink\Tiers` / `Ink\Entitlement` — schema is never gated. Deptrac allowlist omits both (permanent prohibition untouched).
- **No copy debt:** schema `@type` values are schema.org identifiers, not user-facing Afrikaans; the runbook is a `docs/` file (excluded from `copy:scan`). Baseline unchanged.

### Source tree components to touch
- NEW `wp-content/plugins/ink-core/src/Seo/Module.php`, `Api.php`, `SchemaTypes.php`
- NEW `tests/Unit/Seo/SchemaTypesTest.php`
- NEW `docs/seo-rank-math-runbook.md`
- UPDATE `wp-content/plugins/ink-core/ink-core.php` (register module)
- UPDATE `deptrac.yaml` (Seo layer + ruleset)

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 18.1] — AC (sitemaps/meta/breadcrumbs/schema; importer safety net; InkPols OG; Yoast deactivation §14.11).
- [Source: _bmad-output/project-context.md] — Rank Math is a configured platform plugin (do not reimplement); single-source CPT slugs; Brain-Monkey overridable-seam rule; conflation rule; `docs/` excluded from `copy:scan`.
- [Source: wp-content/plugins/ink-core/src/Content/PostTypes.php] — `readableTypes()` = gedig/storie/artikel (single source).
- [Source: wp-content/plugins/ink-core/src/Forms/Module.php] — module bootstrap pattern.

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- `composer test:unit -- --filter=Seo` → 12 passed (23 assertions).
- `composer test:unit` → 1037 passed, 1 skipped.
- `composer stan` (sandbox off) → No errors.
- `composer copy:scan` → 8/8 baseline unchanged.

### Completion Notes List

- `Ink\Seo` is a thin, inert-without-Rank-Math integration: the ONLY code deliverable
  for an otherwise admin-config story. Sitemaps/meta/breadcrumbs/importer/Yoast-swap
  are documented in `docs/seo-rank-math-runbook.md` (need a running site).
- Schema `@type` map reads `PostTypes::readableTypes()` (single source); gedig/storie →
  `CreativeWork`, artikel → `Article`. skryfwerk (non-reader-facing bucket) gets no override.
- WP/Rank-Math seams are protected + overridable (`rankMathActive()`, `currentPostType()`)
  per the Brain-Monkey isolation rule — tests vary them by subclass, not global stubbing.
- Conflation-clean: zero Tiers/Entitlement. New deptrac edge `Seo → Content` only.
- Flagged pre-existing `Kernel\Activation → Content` deptrac debt for the retro (above).

### File List

- NEW `wp-content/plugins/ink-core/src/Seo/Module.php`
- NEW `wp-content/plugins/ink-core/src/Seo/SchemaTypes.php`
- NEW `wp-content/plugins/ink-core/src/Seo/Api.php`
- NEW `tests/Unit/Seo/SchemaTypesTest.php`
- NEW `docs/seo-rank-math-runbook.md`
- UPDATE `wp-content/plugins/ink-core/ink-core.php` (register `seo` module)
- UPDATE `deptrac.yaml` (Seo layer + `Seo → [Kernel, Content]` ruleset)
- UPDATE `_bmad-output/implementation-artifacts/sprint-status.yaml` (18.1 status)
