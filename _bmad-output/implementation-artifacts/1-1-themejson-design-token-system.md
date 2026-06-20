# Story 1.1: theme.json design-token system

Status: ready-for-dev

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a theme developer,
I want all design tokens mapped into `theme.json` from the normalized token file,
so that no template or pattern uses hardcoded values and Quality Gate A can pass.

## Acceptance Criteria

1. **Token completeness** — Every token category in `docs/design-handoff/tokens/theme-tokens.json` is defined in `wp-content/themes/ink-foundation/theme.json` under its production (kebab-case) name: **colour** (16 light-mode colours), **type** (4 font families, 7 font sizes, 3 line-heights, 4 font-weights), **spacing** (10 sizes), **layout** (contentSize, wideSize), **radius** (6 values), and **shadow** (3 values). _[Source: epics.md#Story-1.1; theme-tokens.json]_
2. **Production naming honoured** — Slugs follow `token-map.md`: Lovable camelCase → theme.json kebab-case (`surfaceAlt` → `surface-alt`, `mutedText` → `muted-text`, `highlightForeground` → `highlight-foreground`, `goldMuted` → `gold-muted`); spacing slugs prefixed `s-` (`s-4`…`s-96`); font-size slugs unprefixed (`xs`…`3xl`). Token **values** are copied verbatim from `theme-tokens.json`. _[Source: token-map.md#Rules; project-context.md "theme.json naming is the production source of truth"]_
3. **Gate A — zero hardcoded values** — A review of every file under `templates/`, `parts/`, `patterns/`, and `theme.json` `styles` finds **no hardcoded colours (hex/rgb/hsl), no raw spacing values, and no unnamed type sizes**. Every such value resolves to a `theme.json` token (`var:preset|…` or `var:custom|…` / `has-…` class). _[Source: epics.md#Story-1.1; project-context.md Quality Gate A]_
4. **theme.json validity** — `theme.json` declares `"$schema"` and `"version": 3`, parses as valid JSON, and loads cleanly in the WordPress 7.0 Site Editor with all tokens visible in the global-styles UI (colour palette, font sizes, spacing presets, shadow presets). _[Source: architecture.md#Starter-Template; project-context.md WP 7.0+]_

## Tasks / Subtasks

> **Current state:** `wp-content/themes/ink-foundation/theme.json` (156 lines) **already defines** the 16 colours, 10 spacing sizes, layout widths, 4 font families, and 7 font sizes correctly. This story **completes** the token set and runs the Gate A audit — it is **not** a from-scratch build. Do not regress what is already present.

- [ ] **Task 1 — Verify & preserve existing tokens (AC: 1, 2)**
  - [ ] Confirm the 16-colour palette in `theme.json` matches `theme-tokens.json#color` exactly (slug + hex). Do **not** add, rename, or remove colours.
  - [ ] Confirm spacing (`s-4`…`s-96`), layout (`contentSize:768px`, `wideSize:1400px`), font families (`display`/`heading`/`body`/`ui`), and font sizes (`xs`…`3xl`) match the token file. Leave `defaultPalette:false`, `defaultSpacingSizes:false`, `defaultFontSizes:false`, `appearanceTools:true` as-is.
- [ ] **Task 2 — Add shadow tokens as native presets (AC: 1, 4)**
  - [ ] Add `settings.shadow` with `"defaultPresets": false` and a `presets` array for `sm`, `md`, `lg`, using the exact CSS strings from `theme-tokens.json#shadow`. These become `var:preset|shadow|sm|md|lg`.
- [ ] **Task 3 — Add radius tokens (AC: 1, 2)**
  - [ ] theme.json **has no native radius-preset registry**, so define radius under `settings.custom.radius`: `sm:4px`, `md:6px`, `lg:8px`, `xl:12px`, `"2xl":16px`, `full:9999px` (values from `theme-tokens.json#radius`). These emit `--wp--custom--radius--{slug}` and are referenced as `var:custom|radius|sm`.
- [ ] **Task 4 — Add named line-height & font-weight tokens (AC: 1, 3)**
  - [ ] Add `settings.custom.lineHeight`: `tight:1.2`, `normal:1.5`, `relaxed:1.7` (from `theme-tokens.json#typography.lineHeight`). → `var:custom|line-height|tight` etc.
  - [ ] Add `settings.custom.fontWeight`: `regular:400`, `medium:500`, `semibold:600`, `bold:700` (from `#typography.fontWeight`). → `var:custom|font-weight|semibold` etc.
- [ ] **Task 5 — Replace hardcoded line-heights in theme.json `styles` (AC: 3)**
  - [ ] In `styles.elements.heading.typography.lineHeight`, replace `"1.2"` with `"var:custom|line-height|tight"`.
  - [ ] In `styles.typography.lineHeight`, replace `"1.5"` with `"var:custom|line-height|normal"`.
- [ ] **Task 6 — Gate A audit of patterns & templates (AC: 3)**
  - [ ] Grep `patterns/`, `templates/`, `parts/` for hardcoded design values: `#[0-9a-fA-F]{3,6}`, `rgb(`, `hsl(`, and px/rem values used for colour/spacing/font-size that are not token references. Fix any hits to use tokens.
  - [ ] Known acceptable items (do **not** "fix" into invented tokens): `1px` border-**widths** (no border-width token exists), `letterSpacing:"0.08em"` and `textTransform:"uppercase"` treatments (not colour/spacing/size). Inline numeric `fontWeight:"600"/"700"` in block markup correspond to named weights — leave as-is for now (block markup cannot reference custom props; weight token enforcement is a 1.2 styling concern), but note them in Dev Notes.
- [ ] **Task 7 — Validate & verify (AC: 4)**
  - [ ] `theme.json` parses as valid JSON (`php -r 'json_decode(file_get_contents("theme.json")); echo json_last_error_msg();'` or equivalent).
  - [ ] Load the theme in the WP 7.0 Site Editor (wp-env if available) and confirm colour palette, font sizes, spacing sizes, and shadow presets all appear in Global Styles. If no running WP env yet, document this as the verification step deferred to the first env stand-up (1.11) and confirm JSON validity + token presence by inspection.

## Dev Notes

### What this story is (and is not)
- **Is:** complete the `theme.json` token set (add radius, shadow, named line-heights, named weights), de-hardcode the two inline line-heights, and prove Gate A across existing theme files.
- **Is not:** dark-mode wiring (**Story 1.3** — `theme-tokens.json#modes.dark` exists but is explicitly out of scope here; light mode only at v1), the full typography role system / Lora+Inter `fontFace` bundling / sentence-case enforcement (**Story 1.2**), new templates or patterns (**1.4/1.5**), block locking (**1.6**), or any `ink-core` work (**1.7**). _[Source: epics.md Stories 1.2–1.7; architecture.md "light mode only at v1"]_

### theme.json mechanics (WP 7.0 / schema v3)
- **Shadows are first-class presets** in v3 → use `settings.shadow.presets` (referenced `var:preset|shadow|sm`).
- **Radius is NOT a first-class preset** — there is no `settings.border.radius` *scale*. Use `settings.custom.radius.*`; custom keys become CSS vars (`--wp--custom--radius--sm`) and are referenced as `var:custom|radius|sm`. Same pattern for line-height and font-weight tokens.
- **camelCase → kebab in CSS vars:** `settings.custom.lineHeight.tight` emits `--wp--custom--line-height--tight`. Reference it as `var:custom|line-height|tight`.
- Keep `"$schema": "https://schemas.wp.org/trunk/theme.json"` and `"version": 3` (already present and correct).

### ⚠️ Token-source guardrails (prevent disasters)
- **The token source of truth for v1 is `theme-tokens.json` — 16 colours only.** Do **NOT** invent `brons` / `silwer` / `goud` / `meester` tier colours. They are **not** in the token file; writer-tier (Gradering) display styling is **Epic 5's** concern, not Foundation. The existing `gold-muted` (`#C9B88A`) is the only gold-family token at v1. _[Source: theme-tokens.json#color; project-context.md THE conflation rule / Gradering is Epic 5]_
- **Values are copied verbatim** from `theme-tokens.json`; theme.json **slugs** are the production names (kebab-case per `token-map.md`). Never use raw Lovable token names or Tailwind classes in markup. _[Source: token-map.md#Rules; project-context.md]_
- `token-map.md`'s table only enumerates colour/type/spacing/layout mappings; **radius and shadow are not in the table but ARE required by AC-1** — apply the same kebab-case + verbatim-value conventions when adding them, and update `token-map.md` if you extend it (rule 4: "keep this mapping updated").

### Source tree (files this story touches)
```
wp-content/themes/ink-foundation/
├── theme.json          # UPDATE — add shadow/radius/lineHeight/fontWeight tokens; de-hardcode 2 line-heights
├── style.css           # no change (header already declares Requires at least: 7.0 / Requires PHP: 8.3)
├── patterns/           # AUDIT only (header-main.php, footer-main.php) — fix if Gate A hits found
├── templates/          # AUDIT only (front-page.html)
└── template-parts/     # AUDIT only (header.html, footer.html — these just embed patterns)
```
_[Source: architecture.md#`ink-foundation` FSE theme tree, lines 996–1009]_

### Gate A audit findings (pre-analysed — existing files are largely clean)
- All colours in patterns/templates already use slugs (`backgroundColor:"surface-alt"`, `textColor:"text"`, `var:preset|color|border`) — **no hex present**. ✅
- All spacing uses `var:preset|spacing|s-*`; all font sizes use `has-*-font-size` / `fontSize` slugs. ✅
- Only hardcoded value **inside theme.json** is `lineHeight` `"1.2"`/`"1.5"` in `styles` → Task 5 fixes these.
- Acceptable non-token literals (document, don't break): `border…width:"1px"`, `letterSpacing:"0.08em"`, `textTransform:"uppercase"`, inline `fontWeight:"600"/"700"`.

### Project constraints that apply
- **Presentation only:** zero business logic in the theme or `functions.php`. _[project-context.md three-layer rule]_
- **Afrikaans-first, sentence-case headings** — copy already in the patterns is Afrikaans; do not introduce English UI strings or alter curated Afrikaans. _[project-context.md Gate D; memory: Afrikaans is source of truth]_
- **No hardcoded asset URLs / inline styles for assets** (not relevant to this token-only story, but keep in mind).

## Project Structure Notes

- `theme.json` lives at `wp-content/themes/ink-foundation/theme.json` exactly as the architecture tree specifies. No structural deviation.
- The fuller theme scaffold (`functions.php`, `assets/`, `styles/` block-style variations) does **not yet exist** and is **not** created here — those arrive with 1.2/1.4/1.5/1.7 per the epic sequence. This story stays within `theme.json` + an audit of the existing patterns/templates.
- No conflict between epic intent and current repo state: the theme was bootstrapped (Create Block Theme output) with colour/spacing/typography already populated; this story finishes the token set the AC enumerates.

## References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.1 — theme.json design-token system]
- [Source: docs/design-handoff/tokens/theme-tokens.json — canonical token values (16 colours, type, spacing, layout, radius, shadow, modes.dark)]
- [Source: docs/design-handoff/tokens/token-map.md — Lovable→theme.json slug mapping + rules]
- [Source: _bmad-output/planning-artifacts/architecture.md#Starter-Template-Evaluation (Create Block Theme; populate theme.json from token file, NFR-2) and #`ink-foundation` FSE theme tree (lines 996–1009)]
- [Source: _bmad-output/project-context.md — Quality Gate A (tokens only, no hardcoded values), three-layer separation, WP 7.0+/PHP 8.3+, Afrikaans-first]
- [Source: docs/design-handoff/agent-brief.md — convert tokens to theme.json settings/styles, no JSX/Tailwind, no unnamed values]

## Dev Agent Record

### Agent Model Used

_(to be filled by dev agent)_

### Debug Log References

### Completion Notes List

### File List
