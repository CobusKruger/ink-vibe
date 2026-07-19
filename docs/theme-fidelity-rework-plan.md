# Theme Visual-Fidelity Rework — Plan

**Date:** 2026-07-19
**Status:** Proposed
**Context:** The `ink-foundation` block theme is live on staging (https://nuwe-ink.local/) but does
not visually reflect the INK Lovable design (https://id-preview--f37d6144-97e8-4535-9391-4ddb3b3a7db1.lovable.app/).

## Diagnosis

This is **not** a "theme failed to load" problem and **not** primarily a token-drift problem.

- The theme's design tokens in `wp-content/themes/ink-foundation/theme.json` are an **exact match**
  to the normalised handoff in `docs/design-handoff/tokens/theme-tokens.json` (same palette —
  `primary #EA4015`, `surface #F8F6F2`, etc. — same Lora/Inter fonts, same spacing/shadow/radius
  scale). The CSS is loading and applying.
- `ink-lovable` HEAD is `5618f39` — **the exact commit the handoff was last synced to**
  (`docs/design-handoff/README.md` changelog, 2026-06-20). So the design docs are **already
  truthful** to the current Lovable design.

**Conclusion:** the tokens/specs are correct; the **theme implementation does not honor them**.
The composition layer (hero, section patterns, button block styles, story cards) was built to a
lower fidelity than the design and must be reworked. The basic Lovable design has existed since
February; most of the missing elements were in the design from the start.

### Concrete gaps (to become acceptance criteria)

1. **Buttons** — `theme.json` has **no `styles.elements.button`** rule (only a font-family), so
   radius/fill/text-color fall to core defaults → pill shape + wrong text color. Design wants
   squared (~8px) radius, orange-fill/white-text primary, orange-outline secondary.
2. **Background** — missing the plus-pattern background texture.
3. **Hero heading** — wrong size/scale; design uses a much larger hero headline with a two-tone
   orange highlight. Theme caps at `3xl = 2rem`.
4. **Hero layout** — should be a **two-column split** (headline left, weekly-challenge card right);
   current `front-page.html` renders patterns stacked full-width.
5. **Badge pill + stats row** — missing ("Where Every Word Finds Its Reader" badge; 12K+/48K+/150K+
   stats).
6. **Story cards** — different type scale/colors/layout, and **no hover animations**.

## Rework Steps (exact command sequence)

### Phase 0 — Re-establish design truth (cheap, do first)

1. **`/lovable-design-sync`** — runs the sync skill: `git -C ink-lovable pull`, diff
   `5618f39..HEAD`, re-normalise any changed tokens into `docs/design-handoff/tokens/theme-tokens.json`,
   update `page-map.csv`, `mockup-readiness-assessment.md`, specs §9.4/§14, and the changelog.
   **Expected: little/no change** — the diagnostic proof that the fix is a rebuild, not a re-token.
   If it flags "theme.json must be regenerated," that becomes story 1 of the rebuild.

### Phase 1 — Define what "correct" looks like

2. **`create UX specifications`** → **bmad-ux** (Sally). Produce a **theme visual-fidelity spec**
   turning every gap above into concrete acceptance criteria: hero two-column split, primary/outline
   button block styles, story-card layout + hover animations, plus-pattern background texture, hero
   heading scale/two-tone highlight, badge pill, stats row. Output → `_bmad-output/planning-artifacts/ux-designs/`.
   This is the missing contract the original theme was built without.

### Phase 2 — Introduce the rework as governed scope

3. **`propose sprint change`** → **bmad-correct-course**. Frames the fidelity failure as a course
   correction and drafts a **new epic** (e.g. *Epic 19: Theme visual-fidelity rework*) scoped to
   re-express the Phase-1 UX spec in WP primitives (theme.json `elements.button`, block styles,
   rebuilt `front-page.html` + section patterns, card pattern + animations). Produces
   `sprint-change-proposal-<date>.md`.

4. **`create the epics and stories list`** → **bmad-create-epics-and-stories** (or `create the next
   story` / **bmad-create-story** per story). Turns Epic 19 into story files in
   `_bmad-output/implementation-artifacts/`, e.g.:
   - `19-1-button-block-styles`
   - `19-2-hero-split`
   - `19-3-story-card-layout-animations`
   - `19-4-background-texture-hero-scale`
   - `19-5-section-pattern-rebuild`

   each carrying the UX acceptance criteria + token references.

5. **`run sprint planning`** → **bmad-sprint-planning**. Adds Epic 19 + stories to
   `_bmad-output/implementation-artifacts/sprint-status.yaml` as `backlog`.

### Phase 3 — Rebuild, review, close

6. **`implement the next story in the sprint plan`** (loop) → **bmad-dev-story** (Amelia) per story.
   Rewrites the theme patterns/templates/theme.json against the UX spec, verifying each against the
   live mockup. *(Faster single-pass alternative: **bmad-quick-dev** per story — but per-story
   dev-story keeps acceptance criteria auditable for a rework this size.)*

7. **`run code review`** → **bmad-code-review** → `epic-19-code-review-<date>.md`.

8. **`run a retrospective`** → **bmad-retrospective** → captures *why* the original build drifted
   (likely: built without a visual-fidelity UX spec; unit tests read theme files by path so they
   never caught visual regressions).

## One-line sequence

`/lovable-design-sync` → `create UX specifications` → `propose sprint change` →
`create the epics and stories list` → `run sprint planning` →
`implement the next story…` (×N) → `run code review` → `run a retrospective`.

**Leaner variant** (skip ceremony): `/lovable-design-sync` → `create UX specifications` →
**bmad-quick-dev** straight against the theme → `run code review`. Loses the epic/story audit
trail but moves faster.
