---
baseline_commit: c2ee395
---

# Story 7.1: Single reading templates (prose)

Status: done

<!-- Epic 7 code review (2026-06-26): clean — no findings against this story. -->


## Story

As a reader,
I want clean reading templates for storie/artikel,
so that prose is legible. (FR-24)

## Acceptance Criteria

**Given** a storie/artikel
**When** it renders
**Then** a single reading template per CPT shows it at ~768px width with no WP comments (Archetype C, reference-ready).

1. There is a dedicated single reading template per prose CPT — `templates/single-storie.html` and `templates/single-artikel.html` — that WordPress resolves automatically by the CPT slug (single-`{post_type}`.html convention). Visiting a published `storie`/`artikel` renders through it.
2. The main reading column is constrained to the `contentSize` reading measure (**768px**, already set in `theme.json`); the title + reading column do **not** stretch full-bleed. Prose reads in a single legible column (Archetype C — see `patterns/archetype-c-detail-reading.php`).
3. A reading header renders above the body: a **type eyebrow** (the CPT's Afrikaans singular label — "Storie" / "Artikel"), the **title** (`core/post-title`, h1), and a **byline** ("deur {skrywer} · {datum}") from `core/post-author-name` + `core/post-date`. All chrome strings are Afrikaans via the `ink-foundation` text domain or core blocks (no raw English literals).
4. **No WP comments** render on the reading surface — no comment list, no comment form, no "leave a reply". (Comments are already disabled site-wide by `Ink\Engagement\Comments`, Story 1.8; this story must not add any comments block and must verify the template carries none.)
5. The templates are **reference-ready**: structurally locked editorial chrome (`lock:{move,remove}` on the framing groups, matching the archetype + existing `page.html` convention), tokens-only styling (no hardcoded colours/spacing — Gate A), and they degrade gracefully (a prose CPT with no body still renders header + empty column without error). Engagement widgets (highlight/reaction, responses, leeslys, suggested reads) are **explicitly out of scope** here — 7.1 establishes the legible prose shell that 7.3–7.8 hang onto; gedig is 7.2.

## Tasks / Subtasks

- [x] Task 1: `single-storie.html` reading template (AC: #1, #2, #3, #4, #5)
  - [x] Created `templates/single-storie.html` following the `page.html` shell (header template-part → locked `main` group → footer template-part), referencing the `ink-foundation/reading-storie` pattern (established thin-template→pattern convention, mirrors `page-skryf.html`).
  - [x] Reading header (constrained, 768px) lives in `patterns/reading-storie.php`: type eyebrow paragraph, `core/post-title` (h1, `3xl`), byline flex row using a "deur" paragraph + `core/post-author-name` + "·" separator + `core/post-date`. Reuses archetype-C token classes exactly.
  - [x] Reading body: `core/post-content` constrained to 768px, `md` font size, blockGap `s-24`.
  - [x] Framing groups locked (`"lock":{"move":true,"remove":true}`); content editable. No comments block anywhere.
- [x] Task 2: `single-artikel.html` reading template (AC: #1, #2, #3, #4, #5)
  - [x] Created `templates/single-artikel.html` + `patterns/reading-artikel.php` — same reading shell as storie; differs only in the eyebrow term key ("artikel").
- [x] Task 3: Type eyebrow label source (AC: #3)
  - [x] Eyebrow sources the CPT's Afrikaans singular label from the ink-core terminology registry via `ink_foundation_term( 'storie'/'artikel', … )` (single-source, glossary-backed) — `function_exists`-guarded with an Afrikaans fallback, escaped with `esc_html()`. Byline connective "deur" goes through `esc_html_e( …, 'ink-foundation' )`. No raw English literals (theme-pattern i18n convention).
- [x] Task 4: Theme template registration check (AC: #1)
  - [x] Confirmed FSE auto-resolves `single-{post_type}.html` by the template hierarchy — `theme.json` `customTemplates` is page-only and needs no entry. No PHP registration needed (unlike slug-bound page templates).
- [x] Task 5: Tests + gates (AC: all)
  - [x] Added `tests/Unit/Engagement/ReadingTemplatesTest.php` (5 tests): templates exist + reference the reading pattern; patterns render `wp:post-title`/`wp:post-content` at `"contentSize":"768px"`; **non-vacuous no-WP-comments guard** on both patterns and both templates (asserts positive reading markup is present, THEN asserts all comment-block markers absent); eyebrows source the type label via the terminology bridge.
  - [x] `composer test:unit` green (408→413, +5, 1 pre-existing skip), `composer cs` clean (also fixed 5 pre-existing phpcs errors in `onboarding.php` — see Debug Log), `composer stan` OK, `composer copy:scan` no new debt (6 known gaps), `composer deptrac` 0 errors / 0 warnings (no ink-core source added; exit-1 is the pre-existing "uncovered 60" baseline, unchanged).

## Dev Notes

- **Reading measure already exists** [Source: `wp-content/themes/ink-foundation/theme.json:28-29`]: `contentSize: 768px`, `wideSize: 1400px`. Constrained groups inherit `contentSize`; the archetype overrides to an explicit `"contentSize":"768px"` on the reading column group — match that so the measure is locked even if global settings change.
- **Archetype C is the reference** [Source: `patterns/archetype-c-detail-reading.php`]: type eyebrow (uppercase, `xs`, accent, weight 600, letter-spacing 0.08em) → h1 title (`3xl`) → byline (`sm`, `muted-text`, "deur [skrywer] · [datum]") → reading column (`md`, blockGap `s-24`). 7.1 turns this static archetype into live single templates bound to real post data. Reuse its exact group structure, padding tokens (`s-64`/`s-48`/`s-24`), and class names.
- **Template shell** [Source: `templates/page.html`, `templates/index.html`]: header template-part (locked) → `main` group `alignfull` constrained with `s-64` vertical padding → inner `alignwide` group → footer template-part. Existing singles use `core/post-title` + `core/post-content`. Keep that skeleton; swap `alignwide` for the 768px constrained reading column and add the eyebrow + byline header.
- **Comments are already off** [Source: `src/Engagement/Comments.php:69-119`]: `comments_open`/`pings_open` forced false at `PHP_INT_MAX`, comment post-type support removed. The reading template just must not *add* a comments block. Story 7.4 later writes `ink_reaksie` comments programmatically — that is NOT a comments block on this template. AC #4 is "no WP comments UI", satisfied by omission + the guard test.
- **Three-layer** [project-context.md]: this is pure presentation — FSE templates + core blocks. No business logic in the theme. The only ink-core touchpoint is the Afrikaans type label; the CPT singular labels are already registered Afrikaans in `Ink\Content\PostTypes`. Do NOT add submission/engagement logic here.
- **Gedig is explicitly NOT this story** — 7.2 builds the stanza-aware `single-gedig.html` (preserves verbatim line structure from the 6.3 light editor). Prose (storie/artikel) flows as paragraphs; do not over-engineer line preservation here.
- **Out of scope (build-forward seams for later stories):** highlight/reaction widget (7.3), Gemeenskapsreaksie form/list (7.4), contextual prompts (7.5), suggested reads (7.6), leeslys toggle (7.7), reaction counts (7.8). Leave the reading column clean; later stories insert their dynamic blocks/patterns into this shell.
- **Testing** [Source: `tests/Unit/Submission/ProseSanitizerTest.php`, `tests/Pest.php`]: Pest + Brain Monkey, per-file `Monkey\setUp()/tearDown()`. For a template-structure test, read the `.html` file from disk and assert on its string content (no WP runtime needed). Baseline ~398 tests across 53 files; keep all green.

### Project Structure Notes

- New: `templates/single-storie.html`, `templates/single-artikel.html`; `tests/Unit/Engagement/ReadingTemplatesTest.php` (or matching theme-test location).
- No new ink-core PHP expected; no `deptrac` change. If a label-bridge helper is unavoidable, it stays presentation glue in `functions.php` and reads from `Ink\Content` / `Ink\I18n\Terms` — never new business logic.
- FSE template hierarchy auto-resolves `single-{post_type}.html`; no `register_*` call needed (unlike page templates which bind by slug). Confirm against how `page-skryf.html` is wired.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 7.1]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-7 (patterns-first, server-rendered reads)]
- [Source: wp-content/themes/ink-foundation/patterns/archetype-c-detail-reading.php]
- [Source: wp-content/themes/ink-foundation/templates/page.html]
- [Source: wp-content/themes/ink-foundation/theme.json#layout]
- [Source: wp-content/plugins/ink-core/src/Engagement/Comments.php]
- [Source: _bmad-output/project-context.md#three-layer, #design-tokens-gate-a, #theme-pattern-i18n]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 7)

### Debug Log References

- phpcs surfaced 5 **pre-existing** errors in `patterns/onboarding.php` (introduced in `0d34cbf`, never gated): 4× `EmbeddedPhp` PHP-tag placement (auto-fixed with `phpcbf`) + 1× `MixedOrderedPlaceholdersText` (`%s` mixed with `%2$s` in the leeslys prompt). Fixed the placeholder to `%1$s`/`%2$s` (mechanical i18n-correctness — identical rendered output, no Afrikaans wording change) and updated the translator comment. Folded into this story so the epic-level `composer cs` gate is green; it is genuinely unrelated to 7.1's ACs.
- Pattern PHP evaluates at `init` (pattern registration runs every request), NOT against the queried post — so the per-post data (title/author/date/body) is delivered by core blocks (`wp:post-title`, `wp:post-author-name`, `wp:post-date`, `wp:post-content`) which render against the global post, while the static type eyebrow is resolved in pattern PHP (a constant string, init-safe).

### Completion Notes List

- Built the legible prose reading shell: `single-storie.html` / `single-artikel.html` (thin FSE templates) → `reading-storie.php` / `reading-artikel.php` patterns (the established thin-template→`wp:pattern` convention, mirroring `page-skryf.html`).
- Reading column is constrained to the 768px reading measure (`"contentSize":"768px"` on the header group + post-content), Archetype-C structure (eyebrow → title → byline → body), tokens-only styling (Gate A), framing groups locked (reference-ready).
- No WP comments UI anywhere — comments stay disabled site-wide by `Ink\Engagement\Comments` (1.8); this story adds none, guarded non-vacuously by the test.
- Three-layer clean: pure presentation; the only ink-core touch is the Afrikaans type label via the existing `ink_foundation_term()` bridge → `Ink\I18n\Terms` (single-source). Zero new ink-core PHP, zero deptrac change.
- gedig (7.2), engagement widgets (7.3–7.8) explicitly out of scope — they hang onto this shell later.
- Tests 408→413 (+5), zero regressions; cs/stan clean; copy:scan no new debt; deptrac 0 violations.

### File List

- `wp-content/themes/ink-foundation/templates/single-storie.html` (NEW)
- `wp-content/themes/ink-foundation/templates/single-artikel.html` (NEW)
- `wp-content/themes/ink-foundation/patterns/reading-storie.php` (NEW)
- `wp-content/themes/ink-foundation/patterns/reading-artikel.php` (NEW)
- `wp-content/themes/ink-foundation/patterns/onboarding.php` (MOD — pre-existing phpcs fix, see Debug Log)
- `tests/Unit/Engagement/ReadingTemplatesTest.php` (NEW)
- `_bmad-output/implementation-artifacts/7-1-single-reading-templates-prose.md` (NEW — this story)
