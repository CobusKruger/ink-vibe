# Story 12.1: uitdaging single page

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a skrywer,
I want a rich challenge page,
so that I understand the prompt and rules. (FR-45)

## Acceptance Criteria

**Given** an uitdaging
**When** the single page renders
**Then** it shows prompt, literary devices, submission rules, prize, deadline, resources, and entries list.

Decomposed:

1. A `single-uitdaging` FSE template exists and renders within locked header/footer chrome, embedding a `reading-uitdaging` pattern (mirrors `single-biblioteek_item.html` + `reading-biblioteek.php`).
2. The reading pattern carries the eyebrow **uitdaging** label via the `ink_foundation_term()` bridge (single-source), the post title, and `post-content` — the editorial brief (prompt, literary devices, submission rules, prize, resources) is authored as the uitdaging post body, so all five narrative fields surface through core `post-content`.
3. A server-rendered `ink/uitdaging-besonderhede` block renders, on the single uitdaging, the **sluitingsdatum** (deadline) formatted in SAST plus an **Oop/Gesluit** status derived from the inclusive end-of-day-SAST deadline rule (reuses `Kernel\Sast`).
4. The same block renders the **inskrywings** (entries) list: published bydraes (gedig/storie/artikel) linked to this round via the `uitdagingsrondte` term whose slug is `ChallengeRound::slugFor($id)` — each entry as title → permalink, newest-first.
5. With no linked entries the block renders a graceful "Geen inskrywings nie" empty state (no broken/empty list shell), and with no deadline meta it omits the deadline line rather than rendering a malformed date.
6. Conflation-clean: the block reads only `Ink\Content` (CPT/taxonomy/round-slug single sources) + `Kernel\Sast` + the `Terms` registry + WP core — zero `Ink\Tiers` / `Ink\Entitlement` (viewing a published challenge is open, never gated).

## Tasks / Subtasks

- [x] Task 1: Terminology keys (AC: 3,4) — added `sluitingsdatum`, `inskrywing`, `inskrywing_plural`, plus `uitdaging_oop`/`uitdaging_gesluit` for the status marker, to `I18n\Terms::map()`.
  - [x] Subtask 1.1: Keys added; `TermsTest` still green.
- [x] Task 2: `Challenges\SinglePage` server block (AC: 3,4,5,6)
  - [x] Subtask 2.1: `register()` + `registerBlock()` registering `ink/uitdaging-besonderhede` (function_exists-guarded), mirroring `Library\Archive`.
  - [x] Subtask 2.2: Pure `entriesQueryArgs(int $uitdaging_id)` — published `readableTypes()` + round `tax_query`; non-positive id → `post__in [0]`.
  - [x] Subtask 2.3: Pure `isOpen()` (delegates to `Sast::isThroughEndOfDay`) + `statusHtml()` + impure `parseDeadline()`/`formatDeadline()` (SAST, `wp_date` localised with a deterministic `Y-m-d` unit fallback).
  - [x] Subtask 2.4: Pure `entriesHtml()` (list or empty state) + `toHtml()` section shell.
  - [x] Subtask 2.5: Thin `render()` — resolves current uitdaging id, type-guards, reads deadline meta, queries, composes.
- [x] Task 3: Module wiring (AC: 1,2,3) — `Challenges\Module::register()` delegates to `SinglePage`; `challenges` module registered in `ink-core.php`.
- [x] Task 4: deptrac edge (AC: 6) — `Challenges -> Content` added with documented rationale. No Tiers/Entitlement edge.
- [x] Task 5: Theme surfaces (AC: 1,2) — `templates/single-uitdaging.html` + `patterns/reading-uitdaging.php`.
- [x] Task 6: Tests — `SinglePageTest.php` (11 cases) + `UitdagingTemplateTest.php` (2 cases).
- [x] Task 7: Gates — all green (see Completion Notes).

## Dev Notes

- **House style (three-layer separation):** business logic in ink-core server block; theme pattern is presentation only and embeds the block. Mirror `Ink\Library\Archive` exactly: `register()` → `registerBlock()` → pure `*QueryArgs()` + pure `toHtml()` + thin `render()`. [Source: wp-content/plugins/ink-core/src/Library/Archive.php]
- **Round join key:** entries carry an `uitdagingsrondte` term slugged `uitdaging-{id}` via `Ink\Content\ChallengeRound::slugFor()`. This is the single source — never inline the literal. [Source: src/Content/ChallengeRound.php:46]
- **SAST deadline rule:** the deadline is inclusive through 23:59:59 SAST; `Kernel\Sast::isThroughEndOfDay($deadline, $now)` is the single source for Oop/Gesluit. Parse the raw meta exactly as `Submission\ChallengeLinking::parseDeadline()` does (datetime-local or `Y-m-d H:i`). [Source: src/Kernel/Sast.php; src/Submission/ChallengeLinking.php:146]
- **Meta keys:** deadline = `FieldSets::UITDAGING_DEADLINE` (`ink_uitdaging_deadline`), theme = `FieldSets::UITDAGING_THEME`. [Source: src/Content/FieldSets.php:52-54]
- **Readable entry types:** `PostTypes::readableTypes()` = gedig/storie/artikel (skryfwerk excluded). [Source: src/Content/PostTypes.php:105]
- **Term bridge:** patterns read labels via `ink_foundation_term( key, fallback )`; templates can't (static HTML). [Source: wp-content/themes/ink-foundation/functions.php:294]
- **Glossary labels:** uitdaging (line 122), tema/`challenge_theme` (123), sluitingsdatum/`challenge_deadline` (124), inskrywing/inskrywings (125). [Source: docs/afrikaans-terms.md]

### Project Structure Notes

- New: `src/Challenges/SinglePage.php`, `templates/single-uitdaging.html`, `patterns/reading-uitdaging.php`, two test files.
- Modified: `src/Challenges/Module.php`, `ink-core.php`, `src/I18n/Terms.php`, `deptrac.yaml`.
- The `uitdaging` CPT (`PostTypes::UITDAGING`), `uitdagingsrondte` taxonomy, and theme+deadline meta already exist from Epic 2 — this story builds the reader surface on top.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12.1]
- [Source: wp-content/plugins/ink-core/src/Library/Archive.php] — archive/single house style
- [Source: wp-content/themes/ink-foundation/patterns/reading-biblioteek.php] — reading pattern precedent

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- Built the `ink/uitdaging-besonderhede` server block (deadline/Oop-Gesluit status + inskrywings list) on the `Library\Archive` house style: pure `entriesQueryArgs`/`isOpen`/`statusHtml`/`entriesHtml`/`toHtml` + thin `render()`. The editorial brief (prompt, devices, rules, prize, resources) surfaces via core `post-content` authored in the uitdaging body.
- Deadline parsing mirrors `Submission\ChallengeLinking::parseDeadline()` (the `Y-m-d[ T]H:i(:s)` shape) but is replicated in-module — deptrac forbids `Challenges -> Submission`; both share the `Kernel\Sast` boundary which is the real single source.
- Conflation-clean: only `Ink\Content` (+ `Kernel\Sast`/`Scalar`, `I18n\Terms`) — zero Tiers/Entitlement. New deptrac edge `Challenges -> Content` only (mirrors Library/Discovery 10.1/8.1).
- **Gates:** `composer test` → 765 passed / 2 skipped (+11, zero regressions); `composer cs` → 0 errors (2 pre-existing slow-query warnings only); `composer stan` → No errors; `composer deptrac` → 3 violations = the documented PRE-EXISTING `Kernel\Activation -> Content\PostTypes` baseline, **no new edge**.

### File List

- `wp-content/plugins/ink-core/src/Challenges/SinglePage.php` (new)
- `wp-content/plugins/ink-core/src/Challenges/Module.php` (modified — wired SinglePage)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (modified — 5 new keys)
- `wp-content/plugins/ink-core/ink-core.php` (modified — registered challenges module)
- `deptrac.yaml` (modified — Challenges -> Content edge)
- `wp-content/themes/ink-foundation/templates/single-uitdaging.html` (new)
- `wp-content/themes/ink-foundation/patterns/reading-uitdaging.php` (new)
- `tests/Unit/Challenges/SinglePageTest.php` (new)
- `tests/Unit/Challenges/UitdagingTemplateTest.php` (new)

### Change Log

- 2026-06-27: Story 12.1 implemented — uitdaging single-page surface (deadline/status + entries list block, theme template + reading pattern). Status → review.
</invoke>
