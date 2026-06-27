---
baseline_commit: 548b268
---

# Story 13.2: Issue archive (by year)

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a reader,
I want a by-year issue archive,
so that I can find past issues. (FR-57)

## Acceptance Criteria

**Given** issues
**When** the archive renders
**Then** a clean by-year archive and a robust single-issue page are provided.

Decomposed:

1. A server-rendered `ink/inkpols-argief` block lists published `inkpols_uitgawe` issues **grouped by year, newest year first**, with the issues inside each year ordered newest-first by issue date. It mirrors the `Ink\Library\Archive` house style: pure `queryArgs()` + pure grouping/`toHtml()` + a thin `render()`.
2. The archive query is bounded and defensive: published issues only, capped at a sane maximum (`MAX_ISSUES`, mirroring the 12.1 R12 bound) so a runaway catalogue can never unbound the page; an issue with no/malformed issue date still appears (it is grouped into a trailing undated bucket rendered without a year heading — never silently dropped).
3. Each issue card surfaces the FR-57 model data via the 13.1 `InkPols\Issue` read-model: title → permalink, the localised issue date (`displayDate()`), the volume, the teaser, and the cover image (`coverUrl()`) when present (omitted gracefully when absent). No business logic in the theme — the card is built in the block.
4. The archive renders a graceful empty state when there are no issues (the heading + a `Geen Uitgawes gevind nie.`-style line built from the existing `inkpols_uitgawe_plural` Terms label, not a blank section) — reusing the Biblioteek empty-state pattern (no new copy).
5. A **robust single-issue page** is provided: a `ink/inkpols-besonderhede` server block renders, on a single `inkpols_uitgawe`, the issue metadata — cover image, localised issue date, volume, teaser — gracefully omitting any field that is absent (no malformed/empty rows). The editorial body surfaces through core `post-content`. (The PDF flipbook viewer + direct-PDF a11y fallback are Story 13.3, wired into this single-page pattern.)
6. Theme surfaces (presentation only, three-layer separation): `templates/archive-inkpols_uitgawe.html` + `patterns/inkpols.php` (archive shell embedding `ink/inkpols-argief`); `templates/single-inkpols_uitgawe.html` + `patterns/reading-inkpols.php` (single shell embedding `ink/inkpols-besonderhede` + `post-content`). The section/eyebrow labels read from the terminology registry via the `ink_foundation_term()` bridge (single-source, never bare literals).
7. Terminology: add an `inkpols` Terms key = `InkPols` (the magazine brand — a proper noun, glossary-approved "die naam bly soos is") so the archive heading is single-sourced, not an inline literal. No other new copy (dates/volume/teaser are data; year headings are numbers; the empty state reuses `inkpols_uitgawe_plural`).
8. Conflation-clean: both blocks read only `Ink\InkPols` (the 13.1 read-model/facade) + `Ink\Content` (CPT slug) + the `Terms` registry + `Kernel\ArchiveRender` + WP core — **zero** `Ink\Tiers`/`Ink\Entitlement` (browsing/reading published issues is open, never gated). No new deptrac edge beyond the existing `InkPols -> Content`/`InkPols -> Kernel` (the `Terms`/`PostTypes` reads are already covered; `InkPols -> Kernel` covers `ArchiveRender`).

## Tasks / Subtasks

- [x] Task 1: Terminology (AC: 7) — add `'inkpols' => __( 'InkPols', 'ink-core' )` to `I18n\Terms::map()` (brand proper noun); `TermsTest` stays green.
- [x] Task 2: `InkPols\Archive` server block (AC: 1, 2, 3, 4, 8)
  - [x] Subtask 2.1: `register()` + `registerBlock()` registering `ink/inkpols-argief` (function_exists-guarded), mirroring `Library\Archive`.
  - [x] Subtask 2.2: Pure `queryArgs(int $max): array` — published `inkpols_uitgawe`, `posts_per_page` capped at `MAX_ISSUES`, newest-first by post date (no fragile meta-join — grouping/sort by issue date happens in PHP so dateless issues are never dropped).
  - [x] Subtask 2.3: Pure `groupByYear(list<Issue>): list<array{year:string, issues:list<Issue>}>` — group by `Issue::year()`, years sorted DESC, issues within a year sorted by `issueDate` DESC, the undated (`year===''`) bucket placed last.
  - [x] Subtask 2.4: Pure `toHtml(list groups): string` — heading (`Terms::label('inkpols')`), a `<section>` per year (year `<h2>` heading; undated group headingless) with a card per issue; empty state when no groups.
  - [x] Subtask 2.5: `card(Issue): string` — cover (`coverUrl`, omitted when '') + title→permalink + `displayDate` + volume + teaser, every value escaped.
  - [x] Subtask 2.6: Thin `render()` — query, map posts → `Issue::forPost`, group, compose.
- [x] Task 3: `InkPols\SingleIssue` server block (AC: 5, 8)
  - [x] Subtask 3.1: `register()`/`registerBlock()` `ink/inkpols-besonderhede`.
  - [x] Subtask 3.2: Pure `metaHtml(Issue): string` — cover + date + volume + teaser rows, each omitted when its field is empty; '' when the whole set is empty.
  - [x] Subtask 3.3: Thin `render()` — resolve current issue via `InkPols\Api::issueFor`, type-guard, compose.
- [x] Task 4: Module wiring (AC: 1, 5) — `InkPols\Module::register()` now registers both blocks (`Archive`, `SingleIssue`).
- [x] Task 5: Theme surfaces (AC: 6) — `templates/archive-inkpols_uitgawe.html` + `patterns/inkpols.php`; `templates/single-inkpols_uitgawe.html` + `patterns/reading-inkpols.php`. All user-facing pattern strings go through `ink_foundation_term()`/`esc_html_e( …, 'ink-foundation' )`.
- [x] Task 6: Tests — `tests/Unit/InkPols/ArchiveTest.php` (queryArgs bound; groupByYear ordering + undated-last; toHtml year headings + cards + empty state; card cover-omission) + `tests/Unit/InkPols/SingleIssueTest.php` (metaHtml full + per-field omission + empty) + `tests/Unit/InkPols/InkPolsTemplateTest.php` (templates embed the right patterns/blocks).
- [x] Task 7: Gates — `composer test:unit`, `composer cs`, `composer stan`, `composer deptrac`, `composer copy:scan` all green; record counts in Completion Notes.

## Dev Notes

- **Build on 13.1, do not re-read meta.** Map each queried post to an `InkPols\Issue` via `Issue::forPost()` (or `Api::issueFor()`); the card/metadata read the VO accessors (`displayDate`, `volume`, `teaser`, `coverUrl`, `year`). Never re-read `ink_inkpols_*` meta in the archive/single blocks. [Source: src/InkPols/Issue.php; src/InkPols/Api.php]
- **House style:** mirror `Library\Archive` exactly — `register()` → `registerBlock()` → pure `queryArgs()`/`toHtml()` + thin `render()`; reuse `Kernel\ArchiveRender` for any request reads. The single-page block mirrors `Challenges\SinglePage` (`ink/uitdaging-besonderhede`). [Source: src/Library/Archive.php; src/Challenges/SinglePage.php]
- **By-year grouping is in PHP, not the query.** Ordering by the issue-date meta via a `meta_value` orderby forces an INNER JOIN that silently DROPS issues missing the meta — unacceptable for a content surface. Query newest-first by post date (every published issue included), then group/sort by `Issue::year()`/`issueDate` in the pure `groupByYear()`. The undated bucket renders last, headingless (avoids inventing an "Ongedateer" string = no copy debt). [Source: epics.md#Story 13.2; project-context.md Afrikaans-first]
- **Bound the query (12.1 R12 precedent).** Cap `posts_per_page` at a `MAX_ISSUES` constant — a periodical is bounded, but an unbounded `-1` is the exact unbounded-query smell the Epic-12 review flagged; the cap is defensive. [Source: epics.md#Epic 12 review notes; src/Challenges/SinglePage.php MAX_ENTRIES]
- **Empty state reuses the Biblioteek pattern:** `sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'inkpols_uitgawe_plural' ) )` → "Geen Uitgawes gevind nie." — no new copy. [Source: src/Library/Archive.php:301-307]
- **Brand label:** `InkPols` is a trademark proper noun ("die naam bly soos is — dit is 'n handelsmerk"); adding it to the Terms registry single-sources the heading without a translatable-copy debt. [Source: docs/afrikaans-terms.md:137-143]
- **Glossary:** InkPols (brand), uitgawe = a specific issue, **lees die uitgawe** = the PDF button (13.3). [Source: docs/afrikaans-terms.md:137-143]
- **Theme bridge:** patterns read labels via `ink_foundation_term( key, fallback )`; static `.html` templates cannot (they embed the pattern which carries the labels). [Source: wp-content/themes/ink-foundation/functions.php; patterns/biblioteek.php]
- **Conflation rule:** no tier/entitlement read anywhere — reading a published issue is open. [Source: project-context.md "THE conflation rule"]

### Project Structure Notes

- New: `src/InkPols/Archive.php`, `src/InkPols/SingleIssue.php`; `templates/archive-inkpols_uitgawe.html`, `templates/single-inkpols_uitgawe.html`; `patterns/inkpols.php`, `patterns/reading-inkpols.php`; three test files.
- Modified: `src/InkPols/Module.php` (register both blocks), `src/I18n/Terms.php` (+1 brand key).
- No deptrac change (the `InkPols -> Content`/`-> Kernel` edges from 13.1 already cover `PostTypes`/`Terms`/`ArchiveRender`). The PDF flipbook viewer is Story 13.3 (extends `reading-inkpols.php`).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 13.2]
- [Source: _bmad-output/planning-artifacts/prds/prd-ink-vibe-2026-06-14/prd.md#FR-57]
- [Source: wp-content/plugins/ink-core/src/Library/Archive.php] — archive house style
- [Source: wp-content/plugins/ink-core/src/Challenges/SinglePage.php] — single-page besonderhede block precedent
- [Source: wp-content/themes/ink-foundation/patterns/biblioteek.php; reading-biblioteek.php] — theme shell precedents

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- Built the InkPols reader surfaces on the 13.1 read-model: `Archive` (`ink/inkpols-argief`) groups published issues **by year (newest first)** with in-year date-DESC ordering, and `SingleIssue` (`ink/inkpols-besonderhede`) renders the robust single-issue metadata. Both map posts → `Issue` VOs (never re-read `ink_inkpols_*` meta).
- **By-year grouping is in PHP, not a meta-join** (`groupByYear()`): the query is newest-first by post date so no issue is dropped; the pure grouping sorts years DESC, in-year by `issueDate` DESC, and places undated issues in a trailing headingless bucket (never dropped, no invented "Ongedateer" copy).
- **Numeric-key gotcha:** PHP coerces a numeric-string array key (`'2026'`) to an int, so `array_keys` is re-stringified (`array_map('strval', …)`) to keep the declared `year:string` contract; the bucket lookup coerces back transparently.
- Query bounded at `MAX_ISSUES = 500` (the 12.1 R12 unbounded-`-1` smell). Empty state reuses the Biblioteek pattern (`Geen Uitgawes gevind nie.`) — no new copy.
- Single source: added one `inkpols` => `InkPols` brand label to `I18n\Terms` (trademark proper noun); the archive heading reads it via the `ink_foundation_term()` bridge. All four theme files route user-facing strings through the bridge (Gate D).
- Theme: `archive-inkpols_uitgawe.html` + `inkpols.php`; `single-inkpols_uitgawe.html` + `reading-inkpols.php` (embeds the besonderhede block + `post-content`; the PDF flipbook is 13.3).
- **Brain-Monkey isolation:** the render tests stub `wp_date`/`get_option` deterministically (Brain Monkey leaves `wp_date` defined process-wide once stubbed) — honouring the project-context isolation rule.
- Conflation-clean: `InkPols -> Content`/`-> Kernel` only — no new deptrac edge (the 13.1 edges cover `PostTypes`/`Terms`). Zero Tiers/Entitlement.
- **Gates:** `composer test:unit` → 845 passed / 1 skipped (+17, zero regressions); `composer cs` → clean on new files (phpcbf fixed one alignment warning); `composer stan` → No errors; `composer deptrac` → 3 = the documented pre-existing `Kernel\Activation -> Content` baseline, no new edge; `composer copy:scan` → no new placeholder debt.

### File List

- `wp-content/plugins/ink-core/src/InkPols/Archive.php` (new)
- `wp-content/plugins/ink-core/src/InkPols/SingleIssue.php` (new)
- `wp-content/plugins/ink-core/src/InkPols/Module.php` (modified — register both blocks)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (modified — +1 `inkpols` brand key)
- `wp-content/themes/ink-foundation/templates/archive-inkpols_uitgawe.html` (new)
- `wp-content/themes/ink-foundation/templates/single-inkpols_uitgawe.html` (new)
- `wp-content/themes/ink-foundation/patterns/inkpols.php` (new)
- `wp-content/themes/ink-foundation/patterns/reading-inkpols.php` (new)
- `tests/Unit/InkPols/ArchiveTest.php` (new)
- `tests/Unit/InkPols/SingleIssueTest.php` (new)
- `tests/Unit/InkPols/InkPolsTemplateTest.php` (new)

### Change Log

- 2026-06-28: Story 13.2 implemented — InkPols by-year archive (`ink/inkpols-argief`) + robust single-issue metadata page (`ink/inkpols-besonderhede`) + theme surfaces. Status → review.
