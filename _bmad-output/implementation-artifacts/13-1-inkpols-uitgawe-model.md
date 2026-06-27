---
baseline_commit: 1fa26bb5c4703cdcface7c9e794a38a996a5dcd3
---

# Story 13.1: inkpols_uitgawe model

Status: done

<!-- R13 code review (epic-13-code-review-2026-06-28.md): P1 — Issue.year()/displayDate() now share a checkdate-validated normalisedDate() so a well-shaped-but-invalid date (2026-02-30, 2026-13-01) is treated as undated CONSISTENTLY; P7 — displayDate() anchors the timestamp at noon UTC (no TZ day-shift) + reads date_format via Scalar::asString. 0 unresolved HIGH/MEDIUM. -->


<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a content manager,
I want a structured issue model,
so that issues carry consistent metadata. (FR-57)

## Acceptance Criteria

**Given** `inkpols_uitgawe`
**When** an issue is edited
**Then** it stores issue date, volume, cover image, PDF, and teaser.

Decomposed:

1. A new `Ink\InkPols` module exists (`InkPols\Module` implementing the Kernel `Module` contract) and is registered with the Kernel in `ink-core.php` (mirrors the `library`/`challenges` bootstrap). The module is the home for the Epic-13 InkPols surfaces (archive 13.2, PDF viewing 13.3, migration 13.4).
2. The five FR-57 issue fields are persisted on the `inkpols_uitgawe` CPT — **issue date, volume, cover image, PDF, teaser** — via the EXISTING `Content\FieldSets` registration (Story 2.4): `ink_inkpols_issue_date`, `ink_inkpols_volume`, `ink_inkpols_cover_id`, `ink_inkpols_pdf_id`, `ink_inkpols_teaser`. The story consumes these meta-key constants as the single source — it does **not** re-register the meta or re-type the keys.
3. An `InkPols\Issue` readonly value object is the single read-model for an issue: `Issue::forPost(int|\WP_Post)` reads the five meta values off the authoritative post into typed, default-safe properties (`issueDate:string`, `volume:string`, `coverId:int`, `pdfId:int`, `teaser:string`, plus `postId`/`title`). A missing/empty meta degrades to the typed empty default (`''`/`0`) — never a fatal or a malformed value.
4. The value object exposes derived read accessors the 13.2/13.3 surfaces consume: pure `year(): string` (the 4-digit year parsed from `issueDate`, `''` when absent/malformed — the by-year archive grouping key) and `displayDate(): string` (the issue date localised via `wp_date`, `''` when absent); plus the attachment resolvers `coverUrl(string $size = 'large'): string`, `pdfUrl(): string`, and `hasPdf(): bool` (true only for a positive `pdfId` that resolves to a real attachment URL).
5. An `InkPols\Api` facade is the sole cross-module surface (AD-1): `Api::issueFor(int|\WP_Post): ?Issue` (null for a non-`inkpols_uitgawe` / non-existent post) and `Api::metaKeys(): list<string>` (the five keys, delegating to `FieldSets`). Other modules reach InkPols through the facade, never its internals.
6. Conflation-clean: the module reads only `Ink\Content` (the migration-load-bearing CPT slug + the `FieldSets` meta-key constants) + `Ink\Kernel` (`Scalar`/`Sast`) + WP core — **zero** `Ink\Tiers` / `Ink\Entitlement`. Viewing/modelling an issue is open, never gated. A new deptrac edge `InkPols -> Content` is added (mirrors `Challenges -> Content` 12.1 / `Library -> Content` 10.1); no Tiers/Entitlement edge.

## Tasks / Subtasks

- [x] Task 1: `InkPols\Issue` read-model value object (AC: 3, 4, 6)
  - [x] Subtask 1.1: `final readonly` class with promoted typed props (`postId`, `title`, `issueDate`, `volume`, `coverId`, `pdfId`, `teaser`).
  - [x] Subtask 1.2: `forPost(int|\WP_Post): self` — resolve the post, read each meta via the `FieldSets::INKPOLS_*` constants, coerce with `Kernel\Scalar` (string/int), default-safe.
  - [x] Subtask 1.3: Pure `year(): string` — extract `^\d{4}` from `issueDate` (the FR-57 archive grouping key), `''` when absent/malformed.
  - [x] Subtask 1.4: `displayDate(): string` — `wp_date` of `issueDate` (deterministic `Y-m-d` unit fallback, mirroring `Challenges\SinglePage::formatDeadline`), `''` when no date.
  - [x] Subtask 1.5: `coverUrl(string $size = 'large')`/`pdfUrl()`/`hasPdf()` attachment resolvers (guarded `function_exists`, fail to `''`/`false`).
- [x] Task 2: `InkPols\Api` facade (AC: 5, 6) — `issueFor()` (type-guarded → `Issue::forPost` or null) + `metaKeys()` (delegates `FieldSets`).
- [x] Task 3: `InkPols\Module` bootstrap + Kernel wiring (AC: 1) — thin `Module::register()` (no blocks yet; 13.2/13.3 add them); `addModule( 'inkpols', new InkPols\Module() )` in `ink-core.php`.
- [x] Task 4: deptrac edge (AC: 6) — added `InkPols` layer + `InkPols -> Content`, `InkPols -> Kernel` allowed edges; NO Tiers/Entitlement edge.
- [x] Task 5: Tests — `tests/Unit/InkPols/IssueTest.php` (forPost meta read + default-safety; pure `year()` happy/malformed/empty; `displayDate`/url resolvers via stubs; `hasPdf` true/false) + `tests/Unit/InkPols/ApiTest.php` (issueFor type-guard + null path; metaKeys = the five keys).
- [x] Task 6: Gates — `composer test:unit`, `composer cs`, `composer stan`, `composer deptrac` all green; counts in Completion Notes.

## Dev Notes

- **The CPT + meta already exist — this story is the read-model layer.** `inkpols_uitgawe` is registered in `Content\PostTypes` (archive slug `inkpols`, rewrite `inkpols`) and the five issue fields are registered + admin-saved in `Content\FieldSets` (Story 2.4). 13.1 does NOT re-register either — it builds the `Ink\InkPols` module that surfaces the existing data, exactly as Epic 12 built `Ink\Challenges` on the Epic-2 `uitdaging` CPT. [Source: wp-content/plugins/ink-core/src/Content/PostTypes.php:62,199-207; src/Content/FieldSets.php:46-50,246-288]
- **Meta-key single source:** consume `FieldSets::INKPOLS_ISSUE_DATE` / `INKPOLS_VOLUME` / `INKPOLS_COVER_ID` / `INKPOLS_PDF_ID` / `INKPOLS_TEASER` — never inline the `ink_inkpols_*` literals. `cover_id`/`pdf_id` are integer attachment IDs (`absint` sanitised); `issue_date` is `Y-m-d` (`FieldSets::sanitizeDate`); `volume`/`teaser` are sanitised text. [Source: src/Content/FieldSets.php:46-50,253-286]
- **House style (read-model VO + facade):** mirror `Library\Api` (the thin cross-module facade) and the Challenges value-object pattern. Pure derived helpers (`year()`) stay unit-testable; WP-touching reads (`forPost`, url resolvers, `displayDate`) use guarded core calls so the unit suite mocks them. [Source: src/Library/Api.php; src/Challenges/SinglePage.php:formatDeadline]
- **Scalar coercion:** use `Kernel\Scalar::asString()` / `asInt()` for meta reads (the shared helper, Epic-6 debt paydown) — do not hand-roll `(string)`/`(int)` casts on `get_post_meta` returns. [Source: src/Kernel/Scalar.php]
- **`wp_date` localisation + unit fallback:** `displayDate()` must localise via `wp_date()` but fall back to a deterministic `gmdate('Y-m-d')` shape when `wp_date` is absent (unit suite), matching `Challenges\SinglePage`'s deadline formatter. [Source: src/Challenges/SinglePage.php:35]
- **Glossary:** InkPols (brand, unchanged), **uitgawe** = a specific issue (`inkpols_uitgawe`), **lees die uitgawe** = the PDF link button text. No NEW glossary concept is introduced by 13.1 (the model is data-only); `inkpols_uitgawe`/`_plural` labels already exist in `I18n\Terms`. [Source: docs/afrikaans-terms.md:137-143; src/I18n/Terms.php:160-161]
- **Conflation rule:** InkPols is editorial content — there is NO tier/entitlement gate anywhere in the model. Keep the module free of `Ink\Tiers`/`Ink\Entitlement` (the 10.1/12.1 precedent). [Source: _bmad-output/project-context.md "THE conflation rule"]

### Project Structure Notes

- New: `src/InkPols/Issue.php`, `src/InkPols/Api.php`, `src/InkPols/Module.php`, `tests/Unit/InkPols/IssueTest.php`, `tests/Unit/InkPols/ApiTest.php`.
- Modified: `ink-core.php` (register `inkpols` module), `deptrac.yaml` (InkPols layer + edges).
- No theme files in 13.1 (the archive/single/PDF surfaces are 13.2/13.3). No new meta, no new CPT, no new Terms keys.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 13.1]
- [Source: _bmad-output/planning-artifacts/prds/prd-ink-vibe-2026-06-14/prd.md#FR-57] (lines 525-532)
- [Source: wp-content/plugins/ink-core/src/Content/FieldSets.php] — existing InkPols meta (2.4)
- [Source: wp-content/plugins/ink-core/src/Library/Api.php] — facade house style
- [Source: wp-content/plugins/ink-core/src/Library/Module.php] — module bootstrap house style

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- Built the `Ink\InkPols` module — the read-model + facade layer over the EXISTING Epic-2 `inkpols_uitgawe` CPT (2.1) + its five issue fields (2.4). No CPT/meta re-registration: the `Issue` VO reads via the `FieldSets::INKPOLS_*` constants (single source) and `Kernel\Scalar` coercion (default-safe; cover/pdf floored to non-negative ints).
- `Issue` exposes the derived accessors the 13.2/13.3 surfaces consume: pure `year()` (by-year grouping key), `displayDate()` (`wp_date` + deterministic `Y-m-d` fallback, the `Challenges\SinglePage` formatter precedent), and the `coverUrl()`/`pdfUrl()`/`hasPdf()` attachment resolvers (guarded, fail-safe to `''`/`false`).
- `Api` facade: `issueFor()` type-guards (non-positive id → null before `get_post_type`; wrong CPT → null) and `metaKeys()` delegates to `FieldSets`. `Module` is registered as `inkpols` in `ink-core.php`; `register()` is a deliberate no-op at 13.1 (the model/facade are stateless) — 13.2/13.3 add block registrations.
- **Brain-Monkey isolation note:** the `displayDate` fallback is tested via the resolver returning `''` (not via function-absence) because Brain Monkey leaves `wp_date` defined process-wide once any test stubs it — honouring the project-context Brain-Monkey isolation rule.
- Conflation-clean: `InkPols -> Content` + `InkPols -> Kernel` only; zero Tiers/Entitlement (deptrac edge added, mirrors `Challenges -> Content` 12.1 / `Library -> Content` 10.1).
- **Gates:** `composer test:unit` → 828 passed / 1 skipped (+16, zero regressions); `composer cs` → 0 errors on the new files (repo-wide: only the documented pre-existing slow-query WARNINGS); `composer stan` → No errors; `composer deptrac` → 3 violations = the documented PRE-EXISTING `Kernel\Activation -> Content\PostTypes` baseline, **no new edge** (430 allowed); `composer copy:scan` → no new placeholder debt (13.1 adds no user-facing copy).

### File List

- `wp-content/plugins/ink-core/src/InkPols/Issue.php` (new)
- `wp-content/plugins/ink-core/src/InkPols/Api.php` (new)
- `wp-content/plugins/ink-core/src/InkPols/Module.php` (new)
- `wp-content/plugins/ink-core/ink-core.php` (modified — registered the `inkpols` module)
- `deptrac.yaml` (modified — InkPols layer + InkPols->Content edge)
- `tests/Unit/InkPols/IssueTest.php` (new)
- `tests/Unit/InkPols/ApiTest.php` (new)

### Change Log

- 2026-06-27: Story 13.1 implemented — the `Ink\InkPols` module read-model (`Issue` VO + `Api` facade) over the existing Epic-2 `inkpols_uitgawe` CPT/meta. Status → review.
