---
name: lovable-design-sync
description: >-
  Propagate new INK design work from the ink-lovable repo into this repo's design-handoff
  folder, planning docs, and specs. Use whenever the user says they have updated / pushed /
  added new Lovable design work, asks to "sync the Lovable design", refresh the design handoff,
  or update the specs after a design change. Keeps theme-tokens, page-map, page notes,
  mockup-readiness, UI copy, terminology, and the consolidated spec + feature list truthful to
  the latest Lovable design.
---

# Lovable Design Sync

A runbook for re-synchronising INK's documentation after new design work lands in the Lovable
repo. The Lovable repo is the **design source**; this repo holds the normalised handoff, the
planning docs, and the build specs. This skill keeps all three layers truthful to the latest
design.

> **Mental model:** Steps 0–2 keep the *handoff folder* truthful to Lovable · Steps 3–6 keep the
> *planning docs* truthful to the handoff · Step 7 keeps the *specs* truthful to both · Step 8
> catches conflicts.

## Inputs / locations

| What | Path |
|---|---|
| Lovable design source repo (separate git repo) | `/Users/cobus/Development/ink-lovable` |
| Lovable token sources | `ink-lovable/DESIGN_TOKENS.md`, `src/index.css`, `tailwind.config.ts` |
| Lovable pages/components | `ink-lovable/src/pages/*`, `ink-lovable/src/components/*` |
| Normalised tokens | `docs/design-handoff/tokens/theme-tokens.json`, `tokens/token-map.md` |
| Page map | `docs/design-handoff/page-map.csv` |
| Readiness | `docs/mockup-readiness-assessment.md` |
| UI copy | `docs/ui-copy-translations.md` |
| Terminology (source of truth) | `docs/afrikaans-terms.md` |
| Repo analysis + changelog | `docs/design-handoff/lovable-repo-analysis.md`, `docs/design-handoff/README.md` |
| Build specs | `docs/specs/ink-consolidated-spec.md` (§9, §14), `docs/specs/ink-feature-list.md` |

All paths except the Lovable repo are relative to the ink-vibe repo root.

## Procedure

### Step 0 — Pull, then scope the delta (do this first)
- **Pull the latest Lovable work first:** `git -C /Users/cobus/Development/ink-lovable pull` so the
  local clone reflects the newest design pushes. If the pull fails (uncommitted local changes,
  detached HEAD, no remote/upstream, merge conflict), **stop and report** rather than guessing.
- Find the **last-synced commit hash** (recorded in `docs/design-handoff/README.md` changelog).
  If none recorded, treat as a full re-sync.
- Run `git -C /Users/cobus/Development/ink-lovable log --oneline <last>..HEAD` and
  `git -C /Users/cobus/Development/ink-lovable diff --stat <last>..HEAD`.
- From the changed files, decide which of Steps 1–2 apply:
  - token files changed → Step 1
  - `src/pages/*` or `src/components/*` changed → Step 2
- Note the new `HEAD` hash to record in Step 6.

### Step 1 — Tokens *(only if `DESIGN_TOKENS.md` / `index.css` / `tailwind.config.ts` changed)*
- Re-normalise changed values into `docs/design-handoff/tokens/theme-tokens.json`.
- If a **new token category** appeared (new colour role, type step, spacing value, radius, shadow),
  add a row to `tokens/token-map.md`.
- Bump `meta.lastUpdated` in `theme-tokens.json`.
- ⚠️ Flag that **`theme.json` must be regenerated** from the updated tokens (it's the production
  source of truth). Watch for new **one-off values** that break the token discipline.

### Step 2 — Pages / components *(if a page or component was added or changed)*
- Update `docs/design-handoff/page-map.csv`: add/edit the row — `source_mock` path, `wp_target`,
  blocks, and especially the **`status`** column (e.g. `design-missing` → `reference-ready`).
- **No screenshots and no per-page `notes.md`** (both removed 2026-06-14). The Lovable source +
  tokens + `page-map.csv` are the per-page reference. Never lift copy/content from the mockup —
  copy comes from `ui-copy-translations.md`/`afrikaans-terms.md`, content from the migrated DB.

### Step 3 — Readiness (`docs/mockup-readiness-assessment.md`)
- Move the page between **Missing / Partial / Reference-ready** in the status table.
- Update the **"Genuine gaps requiring design decisions"** and **"Assembly-only"** lists
  (e.g. once the **gedig reading layout** is designed, remove it from the gaps list).

### Step 4 — UI copy (`docs/ui-copy-translations.md`)
- Add any **new English UI strings** the design introduced, with Afrikaans equivalents, checked
  against `docs/afrikaans-terms.md`.

### Step 5 — Terminology (`docs/afrikaans-terms.md`)
- If the design introduces a **new concept or label**, add it here **before** it reaches code/UI.
- Watch for anything that conflicts with a resolved decision (don't reintroduce follow/"vriend"
  confusion; "volgeling/volgelinge" not "Volgers"; `storie` not `verhaal`; `brons/silwer/goud`).

### Step 6 — Analysis + changelog
- Update `docs/design-handoff/lovable-repo-analysis.md` ("What is present" / "What is missing" +
  date).
- Add a dated entry to `docs/design-handoff/README.md` changelog **including the new last-synced
  `ink-lovable` commit hash** from Step 0.

### Step 7 — Propagate into the build specs
- `docs/specs/ink-consolidated-spec.md` **§9.4** — update the readiness table and the
  genuine-design-gaps list to match Step 3.
- `docs/specs/ink-feature-list.md` — update any feature whose note references the design status
  (e.g. **6.2 Gedig reading layout** once it exists).
- 🚩 **If the new design introduces a feature that was never decided** (as ratings/pinned-works
  once were): add it to **§14 as a new open decision — do NOT silently fold it into scope.**

### Step 8 — Consistency sweep
- Does anything in the new design **contradict a resolved §14 decision**? (follow-not-friend,
  no discount model, monthly cadence, English WordPress admin, Afrikaans `ink-core` admin labels,
  layered security stack, etc.)
- Any **new English-leak surface** to add to the translation scope?
- Token compliance: no new hardcoded colours/spacing/unnamed type sizes.

## Guardrails
- **Lovable is design intent, not production code** — never port Lovable React/Tailwind as the
  implementation; it informs `theme.json` tokens, block patterns, and block styles only.
- **Never lift copy or content from the mockup** — its text is English placeholder. Copy ←
  `ui-copy-translations.md`/`afrikaans-terms.md`; content ← migrated DB. No screenshots are kept,
  by design.
- **React → WordPress:** the source is React + Tailwind + shadcn/ui; the build is a block theme +
  `ink-core`. Extract design *intent* and re-express in WP primitives (block patterns/templates,
  `theme.json` tokens, block styles, Interactivity API). Never port JSX, Tailwind classes, shadcn
  components, react-router, or mock data/localStorage. Translation map: spec §9.7.
- **Rule 3 — don't invent features.** Anything new in the design that wasn't a prior decision goes
  to §14 as an open item to confirm, not into scope.
- **Afrikaans-first** front end; English WordPress admin chrome; Afrikaans `ink-core` admin labels.
- Keep every layer traceable: handoff ↔ planning docs ↔ specs must not drift.

## Definition of done
- Handoff folder matches the current Lovable repo (tokens, page-map).
- `mockup-readiness-assessment.md`, `ui-copy-translations.md`, and `afrikaans-terms.md` reflect the
  change.
- `lovable-repo-analysis.md` + `design-handoff/README.md` changelog updated, with the synced commit
  hash recorded.
- `ink-consolidated-spec.md` §9.4 (and §14 if a new feature appeared) and `ink-feature-list.md`
  updated.
- Step 8 sweep run; any conflicts surfaced to the user rather than silently resolved.
