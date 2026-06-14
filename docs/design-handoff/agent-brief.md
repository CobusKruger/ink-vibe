# Agent Brief: Design Source of Truth

Use these files as the canonical design source for theme implementation:

## Tokens
- docs/design-handoff/tokens/theme-tokens.json
- docs/design-handoff/tokens/token-map.md

## Page map
- docs/design-handoff/page-map.csv — maps every page slug to its Lovable source file and WordPress target

## Page design references (Lovable source)
The Lovable repo is the primary visual reference. For each page, use the source file listed in `page-map.csv`:

- ink-lovable/src/pages/Index.tsx + ink-lovable/src/components/home/*
- ink-lovable/src/pages/ReadStory.tsx + ink-lovable/src/components/reading/*
- ink-lovable/src/pages/Browse.tsx
- ink-lovable/src/pages/Challenge.tsx
- ink-lovable/src/pages/Write.tsx
- ink-lovable/src/pages/Writer.tsx
- ink-lovable/src/pages/Profile.tsx
- ink-lovable/src/pages/Community.tsx
- ink-lovable/src/pages/Library.tsx (layout reference for both Biblioteek and Opleiding)

Per-page status, gaps, and data sources live in `docs/mockup-readiness-assessment.md` and `docs/design-handoff/page-map.csv`. (Per-page `notes.md` files were removed 2026-06-14 as redundant; the Lovable source + page-map are the per-page reference.)

## Reference discipline (important)

The Lovable mockup is a **layout + visual-system reference only**. Read the source `.tsx` + tokens for structure and styling. **Do NOT lift copy or content from the mockup** — its text is English placeholder. UI copy comes from `docs/ui-copy-translations.md` and `docs/afrikaans-terms.md`; real content comes from the migrated database. The handoff intentionally keeps **no screenshots** — tokens + source are the authoritative, precise reference.

**React → WordPress:** the source is React + Tailwind + shadcn/ui; the build is a WordPress block theme + `ink-core`. Extract design *intent* (layout, hierarchy, spacing, tokens, responsive behaviour, interactions) and re-express it in WP primitives — block patterns/templates, `theme.json` tokens, block styles, Interactivity API. Do **not** port JSX, Tailwind classes, shadcn components, react-router, or the mock data/localStorage. See the translation map in `docs/specs/ink-consolidated-spec.md` §9.7.

## Terminology
- docs/afrikaans-terms.md
- docs/ui-copy-translations.md — approved Afrikaans translations for all UI strings

## Implementation requirements

1. Convert tokens into theme.json settings and styles.
2. Implement page layouts as templates, template parts, and block patterns.
3. Follow page-map targets for each page slug.
4. Use Afrikaans terminology from docs/afrikaans-terms.md and docs/ui-copy-translations.md.
5. Do not introduce one-off colors, spacing values, or unnamed typography sizes.

## Output expectations

1. Reusable pattern-first implementation.
2. Consistent spacing rhythm from token scale.
3. Mobile and desktop parity derived from Lovable source components.
4. No English UI text leakage.
