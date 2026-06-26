---
baseline_commit: d3bc55b
---

# Story 6.3: Light editor

Status: review

## Story

As a skrywer,
I want a light editor that preserves line structure,
So that shaped/concrete poetry survives. (FR-18)

## Acceptance Criteria

**Given** the editor
**When** I write
**Then** allowed marks are hard line breaks, blank-line/stanza preservation, bold, italic; line structure and leading whitespace are preserved verbatim (not collapsed)
**And** no headings, tables, inline images/embeds, or font/colour/size controls exist.

1. The submitted body is sanitised through a strict allowlist: only `<strong>`/`<b>`, `<em>`/`<i>`, and `<br>` survive — **no attributes** (so no inline `style`/`class` → no font/colour/size controls), and headings, tables, images, embeds, lists, blockquotes, links, iframes are stripped (text content kept).
2. Line structure is preserved **verbatim**: newlines and blank lines (stanza separators) are not collapsed, and **leading whitespace** (indentation for shaped/concrete poetry) is preserved exactly.
3. This replaces 6.1's interim permissive `wp_kses_post()` body handling — the Skryf write path now routes the body through the strict `ProseSanitizer`.

## Tasks / Subtasks

- [x] Task 1: `Ink\Submission\ProseSanitizer` (AC: #1, #2)
  - [x] `allowedTags()` = exactly `strong, b, em, i, br`, each with NO attributes; `sanitize($raw)` delegates to `wp_kses($raw, allowedTags())` (battle-tested stripping) WITHOUT trimming/collapsing — leading whitespace + newlines pass through.
- [x] Task 2: route the Skryf body through it (AC: #3)
  - [x] `SubmissionForm::handlePost()` sanitises the body via `ProseSanitizer::sanitize()` instead of `wp_kses_post()`.
- [x] Task 3: tests + gates
  - [x] `ProseSanitizerTest`: allowlist is exactly the five inline marks with no attributes (asserts headings/tables/img/span/a/ul/iframe/style ABSENT — the security-relevant guard); `sanitize()` passes input through `wp_kses` without trimming/collapsing leading whitespace + blank lines. Update `SubmissionFormTest` body mock (`wp_kses`). All gates green.

## Dev Notes

- **Strict allowlist** [Source: epics.md#Story 6.3, FR-18]: the ONLY marks are hard breaks + stanza preservation + bold + italic. Use `wp_kses` (not `wp_kses_post`, which allows headings/lists/images/etc.). Empty attribute arrays strip `style`/`class`/`color`/`size` — closing "no font/colour/size controls". A `<span style>` / `<font>` is not in the allowlist → reduced to its text.
- **Verbatim preservation** [Source: epics.md#Story 6.3]: `wp_kses` operates on tags and leaves text nodes (incl. leading spaces + `\n` + blank lines) untouched; `sanitize()` must NOT add a `trim()` or whitespace normalisation. The reading-side stanza-aware rendering (white-space handling) is Story 7.2 — 6.3 only guarantees the STORED body is verbatim.
- **Integration** [Source: src/Submission/SubmissionForm.php]: 6.1 used `wp_kses_post()` as an interim; this story swaps it for `ProseSanitizer::sanitize()`. The title still uses `sanitize_text_field` (titles are single-line).
- **Testing**: `wp_kses` is a WP function — mock it (identity) to prove `sanitize()` itself does not trim/collapse, and assert it is called with the strict allowlist. The allowlist-content assertion is the non-vacuous guard against a future loosening. Conflation-clean.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 6.3]
- [Source: _bmad-output/project-context.md#escaping]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 6)

### Completion Notes List

- `ProseSanitizer` (strict allowlist: strong/b/em/i/br, no attributes; delegates to `wp_kses`, no trim/collapse) now sanitises the Skryf body in `handlePost()`, replacing 6.1's interim `wp_kses_post`.
- phpcs: the `ProseSanitizer::sanitize` wrapper isn't recognised by `ValidatedSanitizedInput` (same as the Scalar precedent) — added a targeted, justified `phpcs:ignore` on the body read (the value IS sanitised on that line via wp_kses).
- Tests 375→378 (+3): allowlist is exactly the five inline marks with no attributes; headings/tables/media/links/lists/iframes/style ABSENT (regression guard); `sanitize` preserves leading whitespace + blank stanza lines verbatim and calls `wp_kses` with the strict allowlist. Updated the SubmissionForm body mock to `wp_kses`.
- phpcs/phpstan clean; deptrac unchanged (3 pre-existing, Allowed 152); copy:scan no new debt. Conflation-clean.

### File List

- `wp-content/plugins/ink-core/src/Submission/ProseSanitizer.php` (NEW)
- `wp-content/plugins/ink-core/src/Submission/SubmissionForm.php` (MOD — body via ProseSanitizer)
- `tests/Unit/Submission/ProseSanitizerTest.php` (NEW)
- `tests/Unit/Submission/SubmissionFormTest.php` (MOD — body mock wp_kses)
- `_bmad-output/implementation-artifacts/6-3-light-editor.md` (NEW — this story)
