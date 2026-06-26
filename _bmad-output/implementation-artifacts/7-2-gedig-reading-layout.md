---
baseline_commit: 19611e3
---

# Story 7.2: Gedig reading layout

Status: review

## Story

As a reader,
I want a poetry-aware reading layout,
so that poems display with correct structure. (FR-25)

## Acceptance Criteria

**Given** a gedig
**When** it renders
**Then** the layout is stanza-aware, preserves line breaks and blank-line/stanza spacing and leading whitespace verbatim, styles author-entered Roman-numeral stanza markers, and allows per-line resonance on content lines (not blank separators).

1. A single reading template for the `gedig` CPT — `templates/single-gedig.html` (auto-resolved by the `single-{cpt}.html` hierarchy) — renders at the 768px reading measure with the same Archetype-C header as 7.1 (type eyebrow "Gedig" via the terminology bridge, title, byline) and **no WP comments**.
2. The poem body renders through a **custom server-rendered block** (`ink/gedig-body`) — NOT `core/post-content` — because the body must bypass `wpautop` (which would collapse the verbatim line/stanza structure the 6.3 light editor stored). The block reads the **raw** `post_content` (literal `\n`, leading whitespace, the strict inline marks `<strong>/<b>/<em>/<i>/<br>` from 6.3) and re-sanitises it on output through the **same** inline allowlist.
3. **Verbatim structure** (the core promise — 6.3 stores it, 7.2 must render it): line breaks preserved; **leading whitespace** (indentation for shaped/concrete poetry) preserved exactly (CSS `white-space: pre-wrap` on each line, never HTML whitespace collapse); blank-line / stanza spacing preserved verbatim (each blank physical line produces real vertical space — N blanks → N gaps).
4. **Stanza-aware:** consecutive content lines are grouped into stanza containers; blank physical lines separate stanzas. Author-entered **Roman-numeral stanza markers** (a content line whose trimmed text is a Roman numeral, e.g. `I`, `II`, `IV`, optionally with a trailing period) get a distinct marker class for styling.
5. **Per-line resonance anchors:** every **content** line is emitted as a resonance-able element carrying a stable line identifier (`data-ink-line="{physical-line-index}"`); **blank separators carry no identifier and are not resonance-able.** This identifier is the contract Story 7.3 (line highlighting + reactions) consumes — define it here: the 0-based physical-line index in the stored body. (7.2 only renders the anchors; the reaction UI/store is 7.3.)
6. **6.5 media surfaces in the reading layer** — out of scope as a build here, but the gedig template must not preclude it (the `ink_media_attachment` audio/video block is added with the reaction widget in 7.3/7.8). 7.2 leaves the body clean.
7. Three-layer: the parse + structural HTML (the INK line model + resonance anchors) is `ink-core` (Engagement) — it is dynamic and tied to INK logic (AD-7); the **styling** (pre-wrap, stanza spacing, marker style, line affordance) is the **theme** (Gate A, tokens only). The inline allowlist is shared from `Ink\Kernel` so the write-time (6.3) and read-time allowlists provably cannot drift.

## Tasks / Subtasks

- [x] Task 1: Share the inline allowlist in Kernel (AC: #2, #7)
  - [x] Added `Ink\Kernel\ProseFormat::allowedInlineTags()` returning exactly `strong, b, em, i, br` (no attributes), with the single-source / verbatim-preservation rationale in the docblock.
  - [x] Refactored `Ink\Submission\ProseSanitizer::allowedTags()` to delegate to `ProseFormat::allowedInlineTags()` (write-time + read-time now provably one set). `ProseSanitizerTest` stays green (it asserts the exact key set — preserved).
- [x] Task 2: `Ink\Engagement\GedigBody` parse + render (AC: #2, #3, #4, #5)
  - [x] `tokenize()` — splits on `\n`; `['type'=>'blank']` for empty/whitespace-only lines, else `['type'=>'line','index'=>physicalIndex,'text'=>verbatim,'marker'=>bool]`. No trim of content text.
  - [x] `isRomanNumeralMarker()` — `/^[IVXLCDM]+\.?$/` on a non-empty trimmed line; documented heuristic.
  - [x] `toHtml()` — groups consecutive content lines into `ink-gedig__stanza`; each content line is `<p class="ink-gedig__line[ --marker]" data-ink-line="{index}">{wp_kses($text, allowlist)}</p>`; each blank → `<div class="ink-gedig__sep" aria-hidden="true">` (verbatim spacing). `esc_attr` on the index; `wp_kses` (Kernel allowlist) on the text.
  - [x] `render()` — render_callback: reads the current post's RAW `post_content` (via `get_post()`, NOT `the_content`), returns `toHtml()`; `! $post instanceof \WP_Post` → empty container (graceful).
  - [x] `register()` / `registerBlock()` — `register_block_type( 'ink/gedig-body', [ 'render_callback' => … ] )` on `init` (render-only server block; no block.json needed; `api_version` omitted — unneeded for a server-render block and the stub types it as string).
- [x] Task 3: Wire into the Engagement module (AC: #2)
  - [x] `Engagement\Module::register()` now also wires `GedigBody` (mirrors the `Comments` collaborator).
  - [x] deptrac: Engagement → Kernel only (uses `Ink\Kernel\ProseFormat` + WP core); no Engagement→Content/Submission/Entitlement/Tiers edge. The only new uncovered token is `\WP_Post` (WP core).
- [x] Task 4: Theme — gedig template, pattern, styling (AC: #1, #3, #4, #7)
  - [x] `patterns/reading-gedig.php` — same reading header as `reading-storie.php` (eyebrow via `ink_foundation_term( 'gedig', 'Gedig' )`, post-title, byline); body section embeds `<!-- wp:ink/gedig-body /-->`.
  - [x] `templates/single-gedig.html` — thin template referencing the `reading-gedig` pattern.
  - [x] CSS delivered via `theme.json` `styles.css` (the established `.ink-gradering` pattern — no separate stylesheet/enqueue): `.ink-gedig__line{white-space:pre-wrap}` (verbatim leading whitespace), `.ink-gedig__sep{height:1lh}` (one blank line, verbatim), `.ink-gedig__line--marker` (accent + semibold + uppercase via theme custom props), `.ink-gedig__line:hover` background (the 7.3 resonance affordance seam). Tokens only (Gate A).
- [x] Task 5: Tests + gates (AC: all)
  - [x] `tests/Unit/Kernel/ProseFormatTest.php` (3 tests): allowlist is exactly the five marks, no attributes, excludes block/media/control tags, and `ProseSanitizer::allowedTags()` === the shared set (proves the single-source link).
  - [x] `tests/Unit/Engagement/GedigBodyTest.php` (8 tests): tokenize (blank detection incl. whitespace-only, verbatim leading whitespace, physical index, marker flag); `isRomanNumeralMarker` (I/II/IV/X/I. true; Iets/1/empty/word false); `toHtml` (non-vacuous: content lines DO carry `data-ink-line`, blanks render `ink-gedig__sep` and carry none — `substr_count(...)===2`; marker class; inline marks preserved; leading whitespace preserved; empty body → empty container).
  - [x] Extended `ReadingTemplatesTest`: `single-gedig.html` references `reading-gedig`; the pattern embeds `wp:ink/gedig-body` (and NOT `wp:post-content`), eyebrow via the bridge, 768px, non-vacuous no-comments guard.
  - [x] `composer test:unit` green (413→424, +11), `cs`/`stan` clean, `copy:scan` no new debt, `deptrac` 3 violations (the pre-existing `Activation→PostTypes` baseline — 0 new), 0 errors / 0 warnings.

## Dev Notes

- **THE carry-forward obligation** [Source: epic-7-kickoff-carryforward.md #3; epic-6-retro Action 3]: 6.3 preserves line structure + leading whitespace **in STORAGE only**; 7.2 must actually RENDER it or "the promise dies at the display layer". This story is where that obligation is discharged — hence the dynamic block (bypass `wpautop`) + `white-space: pre-wrap` (leading whitespace) + verbatim blank-line spacing.
- **What 6.3 stores** [Source: src/Submission/ProseSanitizer.php; 6-3-light-editor.md]: the Skryf body is a `<textarea>` submission → literal `\n` newlines (NOT `<br>`), leading spaces intact, run through `wp_kses` with the strict allowlist (`strong/b/em/i/br`, no attributes), NO `trim`/collapse. So the stored `post_content` is plain text + sparse inline marks with significant whitespace. Split on `\n`. (`<br>` is allowlisted defensively for pastes; it stays inline within a physical line.)
- **Why a dynamic block, not a pattern** [Source: architecture.md AD-7]: pattern PHP evaluates at `init` (pattern registration), NOT against the queried post — it cannot read the current poem. A server-rendered block (`render_callback`) runs per-post at render time. AD-7 sanctions custom dynamic blocks "where a component is both dynamic and tied to INK logic — the reading-surface highlight/reaction widget" — the gedig body (with its per-line resonance anchors) is exactly that, and it is the **first** dynamic block in the codebase (establishes the pattern: PHP `register_block_type` + `render_callback`, no block.json needed for a render-only server block).
- **Bypass `the_content`** [critical]: read raw `post_content` directly. `the_content`/`wpautop` would wrap in `<p>`, convert newlines, and collapse leading whitespace — destroying the verbatim structure. Re-sanitise the raw body on output with the Kernel allowlist (defense-in-depth; the value was sanitised at write but output-escaping is the standing rule).
- **Line identity contract for 7.3** [Source: architecture.md AD-5 "Line highlight + reaksie | custom table; one-per-user-per-line"]: `data-ink-line` = 0-based physical-line index in the stored body. Stable + reproducible (split stored body on `\n`, take the i-th element). 7.3's reaction store keys on `(post_id, line_index)`. Blank separators are deliberately NOT keyed.
- **Shared allowlist (single-source + durability)** [project-context.md #shared value sets; #cross-story durability]: extracting the inline allowlist to `Ink\Kernel\ProseFormat` makes the write-time (6.3) and read-time (7.2) allowlists provably one set — a future loosening of one cannot silently diverge from the other. This is the cross-story durability rule (just promoted) applied to the prose-fidelity guarantee.
- **Three-layer** [project-context.md]: parse + anchors = `ink-core`; CSS = theme. The eyebrow term comes from `Ink\I18n\Terms` via `ink_foundation_term()` (single-source), as in 7.1.
- **Reuse 7.1** [Source: patterns/reading-storie.php]: the reading header (section padding, 768px constrained group, eyebrow/title/byline) is identical — copy that header structure into `reading-gedig.php`; only the body block differs.
- **Comments off** [Source: src/Engagement/Comments.php]: as 7.1 — add no comments block; guard non-vacuously in the template test.
- **Testing** [Source: tests/Unit/Submission/ProseSanitizerTest.php]: Pest + Brain Monkey; mock `wp_kses` as identity and `esc_attr` as arg-passthrough to assert `toHtml` structure without WP. `tokenize`/`isRomanNumeralMarker` are pure — test directly.

### Project Structure Notes

- New ink-core: `src/Kernel/ProseFormat.php`, `src/Engagement/GedigBody.php`; MOD `src/Submission/ProseSanitizer.php` (delegate), `src/Engagement/Module.php` (wire GedigBody).
- New theme: `patterns/reading-gedig.php`, `templates/single-gedig.html`, `assets/css/reading-gedig.css`; MOD `functions.php` (enqueue gedig reading CSS on gedig singles).
- New tests: `tests/Unit/Kernel/ProseFormatTest.php`, `tests/Unit/Engagement/GedigBodyTest.php`; MOD `tests/Unit/Engagement/ReadingTemplatesTest.php`.
- deptrac: no change — Engagement → Kernel (already allowed); Submission → Kernel (already allowed). No Engagement→Content/Submission edge introduced.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 7.2]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-7, #AD-5]
- [Source: _bmad-output/implementation-artifacts/6-3-light-editor.md]
- [Source: wp-content/plugins/ink-core/src/Submission/ProseSanitizer.php]
- [Source: wp-content/themes/ink-foundation/patterns/reading-storie.php]
- [Source: _bmad-output/project-context.md#three-layer, #design-tokens-gate-a, #cross-story-durability]
- [Source: memory epic-7-kickoff-carryforward #3]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 7)

### Debug Log References

- phpstan caught two real issues on `GedigBody`: (1) `register_block_type`'s `api_version` is typed `string` in the WP stubs and is unneeded for a render-only server block → removed it; (2) `isset( $post->post_content )` flagged because `WP_Post::$post_content` is non-nullable → replaced the null/isset guard with `! $post instanceof \WP_Post` (handles `get_post()`'s `WP_Post|null` and lets the string flow without a cast). Fixed the causes, no suppressions.
- phpcs: 2 auto-fixable alignment warnings in `GedigBody` (phpcbf).
- phpstan runs hit a sandbox restriction (`Operation not permitted` binding `tcp://127.0.0.1:0` for its parallel workers); ran the gate with the sandbox disabled.

### Completion Notes List

- Discharged the Epic-6-retro carry-forward obligation #3: 6.3's verbatim line/stanza/leading-whitespace storage now actually RENDERS, via the first dynamic server block in the codebase (`ink/gedig-body`, AD-7) that bypasses `wpautop`.
- Shared the inline allowlist into `Ink\Kernel\ProseFormat`; `ProseSanitizer` (6.3) delegates to it — write-time and read-time allowlists are now provably one set (the cross-story durability rule applied to prose fidelity).
- `GedigBody`: pure `tokenize`/`isRomanNumeralMarker`/`toHtml` (heavily unit-tested) + a thin `render` callback reading raw `post_content`. Per-line resonance anchor = 0-based physical-line index in `data-ink-line` — the documented contract Story 7.3 consumes; blank separators carry none.
- Theme: `single-gedig.html` → `reading-gedig.php` (reuses the 7.1 reading header; eyebrow term 'gedig'); CSS via `theme.json styles.css` (tokens only, `white-space:pre-wrap` for leading whitespace, `1lh` sep for verbatim blank spacing, marker styling, hover seam for 7.3).
- Three-layer clean: parse + anchors in ink-core (Engagement→Kernel only); CSS in the theme. Conflation-clean (no Tiers/Entitlement). No WP comments (guarded non-vacuously).
- Tests 413→424 (+11); cs/stan clean; copy:scan no new debt; deptrac 0 new violations (3 pre-existing `Activation→PostTypes` baseline).

### File List

- `wp-content/plugins/ink-core/src/Kernel/ProseFormat.php` (NEW — shared inline allowlist)
- `wp-content/plugins/ink-core/src/Engagement/GedigBody.php` (NEW — `ink/gedig-body` server block)
- `wp-content/plugins/ink-core/src/Engagement/Module.php` (MOD — wire GedigBody)
- `wp-content/plugins/ink-core/src/Submission/ProseSanitizer.php` (MOD — delegate to Kernel allowlist)
- `wp-content/themes/ink-foundation/templates/single-gedig.html` (NEW)
- `wp-content/themes/ink-foundation/patterns/reading-gedig.php` (NEW)
- `wp-content/themes/ink-foundation/theme.json` (MOD — `.ink-gedig*` styles in `styles.css`)
- `tests/Unit/Kernel/ProseFormatTest.php` (NEW)
- `tests/Unit/Engagement/GedigBodyTest.php` (NEW)
- `tests/Unit/Engagement/ReadingTemplatesTest.php` (MOD — gedig template + pattern guards)
- `_bmad-output/implementation-artifacts/7-2-gedig-reading-layout.md` (NEW — this story)
