---
baseline_commit: d3bc55b
---

# Story 6.2: Content-type selector with counters

Status: review

## Story

As a skrywer,
I want a content-type selector with per-type counters,
So that I get the right fields and feedback. (FR-17)

## Acceptance Criteria

**Given** the selector
**When** I pick poem/story/article
**Then** per-type placeholders and counters show (lines **and** words for gedig; words for prose).

1. The Skryf type selector (gedig/storie/artikel from 6.1) drives **per-type placeholder** text in the body field.
2. The counter is **type-aware**: a **gedig** shows **lines AND words**; a **storie / artikel** (prose) shows **words only**.
3. The counting rules are defined ONCE in `ink-core` (tested), not duplicated in the theme: word count = non-whitespace tokens; line count = non-blank lines (so blank stanza separators do not inflate the verse-line count). The browser counter is a thin progressive-enhancement mirror of those rules.

## Tasks / Subtasks

- [x] Task 1: `Ink\Submission\ContentType` — counter mode per type (AC: #2)
  - [x] `MODE_LINES_AND_WORDS` / `MODE_WORDS`; `counterMode($type)` = lines+words for gedig, words otherwise; `countsLines($type)` helper.
- [x] Task 2: `Ink\Submission\Counters` — the counting single source (AC: #3)
  - [x] `words($text)` (UTF-8 non-whitespace tokens), `lines($text)` (non-blank lines), `forType($type,$text)` → `{lines:int|null, words:int}`.
- [x] Task 3: surface the counter mode in the view-model (AC: #1, #2)
  - [x] `Api::formModel()` types carry `counter_mode`; per-type placeholder copy rendered in the theme (ui-copy "Teksblok-plekhouers").
- [x] Task 4: theme — per-type placeholders + live counter (AC: #1, #2)
  - [x] Counter display element + `assets/js/skryf-counter.js` (enqueued, guarded) that swaps placeholder + recomputes lines/words per the selected type's `counter_mode` — mirroring the PHP rules.
- [x] Task 5: tests + gates
  - [x] `CountersTest` (words/lines incl. blank-line handling + UTF-8; `forType` lines null for prose), `ContentTypeTest` (mode per type), `ApiTest` extended (counter_mode in model). `test:unit`/`cs`/`stan`/`deptrac`/`copy:scan` green.

## Dev Notes

- **Counter rules** [Source: epics.md#Story 6.2, ui-copy-translations.md:386-387]: "[N] reëls · [N] woorde" (gedig) / "[N] woorde" (prose). Word = non-whitespace token (`preg_match_all('/\S+/u', …)` — UTF-8 so Afrikaans diacritics count as part of a word). Line = non-blank line (blank lines separate stanzas; they are structure, not verse lines — the structure itself is preserved by the 6.3 editor).
- **Placeholders** [Source: ui-copy-translations.md:391-397]: per-type, theme `ink-foundation` copy keyed by slug (mirrors the 6.1 per-type descriptions). Counter MODE (a content rule) lives in `ink-core`; the placeholder TEXT (copy) lives in the theme.
- **Three-layer / single source** [project-context.md:52]: the JS counter must not re-define the rules — it mirrors `Counters`. Conflation-clean: no `Ink\Tiers`.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 6.2]
- [Source: docs/ui-copy-translations.md#Skryf-bladsy]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 6)

### Completion Notes List

- `ContentType` (counter mode per type) + `Counters` (words = UTF-8 `\S+` tokens; lines = non-blank lines incl. CRLF) are the single source; `Api::formModel()` types now carry `counter_mode`.
- Theme: per-type body placeholders (ui-copy "Teksblok-plekhouers"), an `aria-live` counter element, and `assets/js/skryf-counter.js` (enqueued only on the Skryf page) that swaps placeholder + recomputes per the selected type's mode — a thin mirror of the PHP rules, graceful with JS off.
- phpcbf fixed alignment; replaced a short ternary (`?:`) in `Counters::lines` with an explicit `false ===` guard (project disallows short ternary).
- Tests 370→375 (+5). phpcs/phpstan clean; deptrac only the 3 pre-existing `Activation→PostTypes` (new Counters/ContentType→Content edges Allowed); copy:scan no new debt. Conflation-clean.

### File List

- `wp-content/plugins/ink-core/src/Submission/ContentType.php` (NEW)
- `wp-content/plugins/ink-core/src/Submission/Counters.php` (NEW)
- `wp-content/plugins/ink-core/src/Submission/Api.php` (MOD — counter_mode in model)
- `wp-content/themes/ink-foundation/functions.php` (MOD — enqueue skryf-counter on Skryf page)
- `wp-content/themes/ink-foundation/patterns/skryf.php` (MOD — per-type placeholders, data attrs, counter element)
- `wp-content/themes/ink-foundation/assets/js/skryf-counter.js` (NEW)
- `tests/Unit/Submission/CountersTest.php` (NEW)
- `tests/Unit/Submission/ContentTypeTest.php` (NEW)
- `tests/Unit/Submission/ApiTest.php` (MOD — counter_mode assertion)
- `_bmad-output/implementation-artifacts/6-2-content-type-selector-with-counters.md` (NEW — this story)
