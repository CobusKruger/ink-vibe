---
baseline_commit: 80b12a3
---

# Story 7.5: Contextual prompts after a piece

Status: done

### Review Findings

- [x] [Review][Defer] Prompt copy lives in the `ink-core` domain (verbatim authored, copy:scan clean) rather than a single-source registry key — copy-debt to ratify on the next copy pass. See deferred-work.md.

## Story

As a reader,
I want guided prompts after reading,
so that I'm helped to respond thoughtfully. (FR-30)

## Acceptance Criteria

**Given** a finished piece
**When** the prompt area renders
**Then** contextual guided prompts show (may vary by content type).

1. After the piece (above the Gemeenskapsreaksie form) a guided-prompt area renders on every reading surface (gedig/storie/artikel), nudging the reader to respond thoughtfully — a heading + a short guiding line.
2. The prompt copy is **human-authored Afrikaans, used verbatim** from `docs/ui-copy-translations.md` (lines 286–287: "Reageer met bedoeling" / "Merk 'n sin uit. Los 'n gestruktureerde nota. Sê vir 'n skrywer wat geraak het, in plaas van om verby te blaai.") — NO invented/AI-translated copy.
3. The mechanism **supports varying by content type** (`promptsFor(string $postType)`), satisfying "may vary by content type"; v1 returns the same authored framing for all bydrae types (richer per-type question prompts are a later, authoring-gated addition).
4. Three-layer: a server block (`ink/leesprompte`, AD-7) owns the structure + escaping in `ink-core` (Engagement); the theme owns presentation (tokens, Gate A). Not entitlement-gated; reads only (no write).

## Tasks / Subtasks

- [x] Task 1: `Ink\Engagement\ContextualPrompts` server block (AC: #1, #2, #3, #4)
  - [x] `promptsFor(string $postType): array{heading:string, body:string}` — pure; returns the authored heading + body (verbatim authored `__()` literals, `ink-core` domain). Accepts `$postType` for future per-type variation (v1: same framing for all).
  - [x] `toHtml(array $prompt): string` — pure; renders an `.ink-leesprompte` section (heading + body), escaped.
  - [x] `render(): string` — block callback: `promptsFor( get_post_type() )` → `toHtml()`; empty string when no post.
  - [x] `register()`/`registerBlock()` — `register_block_type('ink/leesprompte', ['render_callback'=>…])` on `init`.
- [x] Task 2: Wire + embed (AC: #1)
  - [x] `Engagement\Module::register()` registers `ContextualPrompts`.
  - [x] Embed `<!-- wp:ink/leesprompte /-->` in `reading-storie.php`, `reading-artikel.php`, `reading-gedig.php`, directly before the `ink/gemeenskapsreaksies` block.
  - [x] `.ink-leesprompte*` CSS in `theme.json` `styles.css` (tokens only).
- [x] Task 3: Tests + gates (AC: all)
  - [x] `tests/Unit/Engagement/ContextualPromptsTest.php`: `promptsFor` returns the authored heading + body for each bydrae type (gedig/storie/artikel); `toHtml` renders both and escapes (mock `__`/`esc_*`); non-vacuous (asserts the actual authored strings present).
  - [x] Extend `ReadingTemplatesTest`: each reading pattern embeds `wp:ink/leesprompte` before `wp:ink/gemeenskapsreaksies`.
  - [x] `composer test:unit` green, `cs`/`stan` clean, `copy:scan` no new debt (verbatim authored copy, no placeholders), `deptrac` unchanged (Engagement → Kernel; the block uses no cross-module class — pure WP + literals).

## Dev Notes

- **Authored copy** [Source: docs/ui-copy-translations.md:286-287]: use verbatim. These guide exactly the thoughtful-response behaviour FR-30 wants (highlight + structured note) and pair naturally with the 7.3 line reactions + 7.4 Gemeenskapsreaksie form rendered just below.
- **No invention** [project-context.md #afrikaans-first; memory afrikaans-is-source-of-truth]: the strings are copied verbatim from the authored UI-copy doc; `copy:scan` only flags `[NEEDS HUMAN AFRIKAANS]` placeholders, so verbatim authored strings pass. Per-type question prompts that don't yet exist are NOT invented here — the mechanism supports them for a later authoring pass.
- **Block pattern** [Source: src/Engagement/GedigBody.php, ResponsesList.php]: same render-only server-block shape; pure `promptsFor`/`toHtml` + thin `render`. No `$wpdb`, no cross-module class — Engagement → Kernel only (actually no Kernel class needed; pure WP + literals).
- **Placement** [Source: patterns/reading-*.php]: embed before `ink/gemeenskapsreaksies` so the prompt introduces the response form.
- **Testing**: mock `__` (returnArg(1)), `esc_html`/`esc_attr` (returnArg(1)), `get_post_type`. Assert the authored strings appear.

### Project Structure Notes

- New ink-core: `src/Engagement/ContextualPrompts.php`; MOD `src/Engagement/Module.php`.
- New theme: MOD `patterns/reading-{storie,artikel,gedig}.php` (embed), `theme.json` (`.ink-leesprompte` styles).
- New tests: `ContextualPromptsTest`; MOD `ReadingTemplatesTest`.
- deptrac: no change (Engagement → Kernel; the block references no other ink module).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 7.5]
- [Source: docs/ui-copy-translations.md:286-287]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-7]
- [Source: _bmad-output/project-context.md#afrikaans-first, #three-layer]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 7)

### Debug Log References

- No surprises; stan run outside the sandbox (parallel-worker TCP bind blocked by the sandbox, as in prior stories).

### Completion Notes List

- `ink/leesprompte` server block renders a guided prompt after the piece, above the Gemeenskapsreaksie form (introducing it). Copy is human-authored Afrikaans used verbatim (ui-copy-translations.md 286–287) — no invention; `copy:scan` clean.
- `promptsFor(string $postType)` accepts the content type so prompts MAY vary by type (AC); v1 returns the same authored framing for all bydrae types — richer per-type question prompts are a later authoring-gated pass (noted, not invented here).
- Pure `promptsFor`/`toHtml` (unit-tested with the exact authored strings asserted, so a silent swap to invented copy would fail); thin `render`. Engagement → Kernel only (the block references no other ink module); deptrac unchanged.
- Embedded before `ink/gemeenskapsreaksies` in all three reading patterns (order asserted in the template test).
- Tests 459→462 (+3); cs/stan clean; copy:scan no new debt; deptrac 0 new violations.

### File List

- `wp-content/plugins/ink-core/src/Engagement/ContextualPrompts.php` (NEW — ink/leesprompte block)
- `wp-content/plugins/ink-core/src/Engagement/Module.php` (MOD — wire ContextualPrompts)
- `wp-content/themes/ink-foundation/patterns/reading-storie.php` (MOD — embed prompt)
- `wp-content/themes/ink-foundation/patterns/reading-artikel.php` (MOD — embed prompt)
- `wp-content/themes/ink-foundation/patterns/reading-gedig.php` (MOD — embed prompt)
- `wp-content/themes/ink-foundation/theme.json` (MOD — `.ink-leesprompte` styles)
- `tests/Unit/Engagement/ContextualPromptsTest.php` (NEW)
- `tests/Unit/Engagement/ReadingTemplatesTest.php` (MOD — prompt-embed + order guard)
- `_bmad-output/implementation-artifacts/7-5-contextual-prompts-after-a-piece.md` (NEW — this story)
