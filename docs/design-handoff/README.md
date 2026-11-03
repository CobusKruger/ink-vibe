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
- 2026-10 (recorded 2026-11-02): **`71c0847`** was the last commit the fidelity rework synced; it was never recorded here, so the delta started from `04217f7` on paper. Recorded now.
- 2026-11-02: Synced to verse-haven @ **`cbd9d3a`** (range `71c0847..cbd9d3a`; Lovable repo renamed from `ink-lovable`; includes the audit-ids, which this work added, and the Challenges page). New and changed design pages: Uitslae (`ChallengeResults.tsx`), InkPols archive and issue (`Magazine.tsx`, `MagazineIssue.tsx`), Kompetisies (`Competitions.tsx`), Challenges (`Challenges.tsx`), the reading-page winner aside, the home results banner, InkPols and winner sections, and the header. **Done in this sync:** `page-map.csv` (new rows for each, every `wp_target` now an existing template with its linkage: SystemPages, post type or endpoint; the stale `page-biblioteek`, `page-opleiding`, `page-uitdagings` and `single-skrywer` targets corrected; `ink-lovable/` paths renamed `verse-haven/`). Build specs and maps are in `docs/lovable-delta-2026-10/` (specs 01-07, `owner-rulings.md`). Tokens: delta README §1 found no new design tokens; `theme-tokens.json` was not re-checked against `cbd9d3a` in this pass. **Also done in this sync (second pass, 2026-11-02):** `lovable-repo-analysis.md`, `mockup-readiness-assessment.md`, `ink-consolidated-spec.md` §9.4 and §14 (rows 24-37: D1-D13 plus the owner rulings; new features as decisions, not scope), `ink-feature-list.md`. Tokens: none of `DESIGN_TOKENS.md`, `src/index.css` or `tailwind.config.ts` changed in the range (checked with `git diff --stat`), so `theme.json` needs no regeneration. **Step 8 sweep:** one stale §14 item found and amended (row 23, annual competition no longer reuses the challenge machinery); feature 12.5 and 5.8 corrected to Gedigte-only Gradering (D7/D13); no new token breaks; new English-leak surface is covered by the `[NEEDS HUMAN AFRIKAANS]` markers. **Still not done:** the `agent-brief.md` §2.1 / `targets.json` rows (owned by the harness work).
