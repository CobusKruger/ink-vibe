# Design Handoff Folder

This folder contains all design-source inputs that implementation agents must use.

## Canonical files

- tokens/theme-tokens.json
- tokens/token-map.md
- page-map.csv
- agent-brief.md

_No `pages/` subfolder: per-page `notes.md` and screenshots were removed 2026-06-14. The Lovable source repo + tokens + `page-map.csv` are the design reference; the mockup's English copy/content must not be lifted (see `agent-brief.md`)._

## Changelog

- 2026-06-02: Initial handoff structure created.
- 2026-06-14: Synced to ink-lovable @ `c7d980c`. Gedig reading layout (`PoetryReader.tsx`) and profile Reading/Following/Activity tabs added; `page-map.csv`, `mockup-readiness-assessment.md`, `lovable-repo-analysis.md`, `ui-copy-translations.md`, and `docs/specs/*` updated to match.
- 2026-06-20: Synced to ink-lovable @ `5618f39`. Design refinement only — gedig poem body changed from centred to **left-aligned** in `PoetryReader.tsx` (dedication and resonance-count footer remain centred). No token, copy, terminology, or feature change. Updated `page-map.csv`, `mockup-readiness-assessment.md`, `lovable-repo-analysis.md`, and `ink-feature-list.md` (7.2). Step 8 sweep: no §14 conflicts, no new English-leak surface, no token-discipline breaks.
