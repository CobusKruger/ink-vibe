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
- 2026-07-19: Synced to ink-lovable @ `04217f7`. **Auth flow designed** — `Auth.tsx` (sign-in/sign-up tabs), `ForgotPassword.tsx`, `ResetPassword.tsx`, plus Google/Apple **social-login** buttons and logged-in/out `Header.tsx` states (avatar dropdown). This *confirms* the already-decided R6 social login (spec §14 #22 / feature 3.5) — no new §14 decision. Auth moves from assembly-only → **reference-ready**. No token change. Updated `page-map.csv`, `mockup-readiness-assessment.md`, `ui-copy-translations.md` (new auth microcopy), `afrikaans-terms.md` (reset + social-login action labels), `lovable-repo-analysis.md`, and specs `ink-consolidated-spec.md` §9.4 + `ink-feature-list.md` 3.1/3.5. Step 8 sweep: no §14 conflicts; Supabase auth in the mock is prototype plumbing (build is WP-native auth); "Inkwell" brand placeholder replaced with INK; no token-discipline breaks.
