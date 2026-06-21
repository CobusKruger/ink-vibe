---
baseline_commit: c5821d806c1700331ae3a352f097d8a0646c6fe8
---

# Story 1.3: Dark mode tokens

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a reader,
I want a dark palette,
so that I can read comfortably in low light.

## Acceptance Criteria

1. **Dark token set wired, not hardcoded** — The dark palette from `docs/design-handoff/tokens/theme-tokens.json#modes.dark` (the 6 overrides: `surface`, `surfaceAlt`, `text`, `primary`, `accent`, `border`) is wired into the theme as a **native WordPress color style variation** at `wp-content/themes/ink-foundation/styles/dark.json`. When that variation is active, the theme resolves every colour from the dark token set — there are **no hardcoded hex/rgb/hsl values** anywhere in the variation's `styles` block; surface/text/link/etc. resolve via `var:preset|color|*` exactly as in light mode. The dark values are copied **verbatim** from the token file. _[Source: epics.md#Story-1.3 "the theme resolves colours from the dark token set with no hardcoded values"; theme-tokens.json#modes.dark; project-context.md Quality Gate A]_
2. **Full palette declared in the variation (no orphan slugs)** — The variation redefines the **complete 16-colour palette** under `settings.color.palette` using the **same slugs** as the base `theme.json` (so every existing `var:preset|color|*` reference keeps resolving): the **6 dark overrides** take the `modes.dark` values; the **other 10** (`primary-light`, `secondary`, `accent-light`, `muted-text`, `success`, `warning`, `danger`, `highlight`, `highlight-foreground`, `gold-muted`) carry forward their **light values verbatim** (the design only specifies 6 dark overrides — do **not** invent dark variants for the rest). No slug is renamed, added, or removed. _[Source: theme-tokens.json#color + #modes.dark; token-map.md#Rules; project-context.md "theme.json naming is the production source of truth"; 1-1 story (16-colour palette, slugs are migration/downstream load-bearing)]_
3. **Additive only — light remains the v1 default, no regression** — The base `theme.json` default rendering is **unchanged**: the variation is an opt-in `styles/*.json` file that WordPress lists in Global Styles → Styles but does **not** apply by default. The 1.1 token set (16 colours, spacing, layout, shadow, radius, line-height, font-weight) and the 1.2 typography additions (`fontFace`, per-level heading sizes, fluid tuning) are **byte-for-byte untouched**. No theme-switcher UI, no `prefers-color-scheme` auto-switch, and no `color-scheme` activation logic are shipped at v1 — activation is explicitly out of scope (architecture: "light mode only at v1"). _[Source: architecture.md#Architecture-Principles (NFR-2 "light mode only at v1") + #Areas-for-Future-Enhancement ("dark mode (deferred v1)"); 1-1 + 1-2 Completion Notes (no-regression discipline)]_
4. **Style-variation mechanics & validity** — `styles/dark.json` is a valid WordPress block style variation: it declares `"$schema": "https://schemas.wp.org/trunk/theme.json"` and `"version": 3`, carries a `"title"` (Afrikaans, sentence case — e.g. `"Donker"`) so it reads correctly in the Site-Editor style picker, parses as valid JSON, and contains **only** `settings.color.palette` + `styles.color` (background/text) — it does **not** redeclare typography, spacing, or other settings (those inherit from base `theme.json`). It loads cleanly in the WP 7.0 Site Editor and appears as a selectable style alongside the default. _[Source: project-context.md WP 7.0+; architecture.md#`ink-foundation` FSE theme tree (line 1007 `styles/ # block style variations`); 1-1 AC-4 validity precedent]_

## Tasks / Subtasks

> **Current state (read before starting):** The theme has **no `styles/` directory yet** — this story creates its first occupant. `theme.json` already holds the complete light token set (16 colours with the exact slugs this variation must reuse) from Story 1.1, plus the 1.2 typography system. The dark palette in `theme-tokens.json#modes.dark` defines only **6 overrides** (`surface #171C1F`, `surfaceAlt #1A1D21`, `text #EAE7DF`, `primary #EE5830`, `accent #6AA88A`, `border #2A3035`). This story **wires that set in as an additive color style variation** — it does **not** activate dark mode, build a toggle, or touch the base `theme.json` colour rendering. **Do not regress the 1.1 token set or the 1.2 typography additions.**

- [x] **Task 1 — Create the `styles/` directory + dark variation file (AC: 1, 4)**
  - [x] Created `wp-content/themes/ink-foundation/styles/` (architecture-sanctioned home for block style variations; first occupant, as expected at the dark-mode story).
  - [x] Created `styles/dark.json` with `"$schema": "https://schemas.wp.org/trunk/theme.json"`, `"version": 3`, and `"title": "Donker"` (Afrikaans, sentence case) for the Site-Editor style picker. Kept minimal — only `settings.color.palette` and `styles.color`.
- [x] **Task 2 — Redefine the full 16-colour palette with dark overrides applied (AC: 1, 2)**
  - [x] `settings.color.palette` lists **all 16 slugs** in the **same order and with the same `slug`/`name`** as base `theme.json`; `defaultPalette: false`.
  - [x] Applied the **6 dark overrides verbatim** from `theme-tokens.json#modes.dark`: `surface → #171C1F`, `surface-alt → #1A1D21`, `text → #EAE7DF`, `primary → #EE5830`, `accent → #6AA88A`, `border → #2A3035`.
  - [x] Carried the **other 10** colours forward with their **light values verbatim** (no invented dark variants): `primary-light #EF6842`, `secondary #EDE9E0`, `accent-light #5C9979`, `muted-text #6B7280`, `success #4D8066`, `warning #D4A418`, `danger #EF4444`, `highlight #FFE066`, `highlight-foreground #1A1D21`, `gold-muted #C9B88A`.
- [x] **Task 3 — Wire surface/text in the variation `styles` via tokens (AC: 1)**
  - [x] `styles.color.background = "var:preset|color|surface"` and `styles.color.text = "var:preset|color|text"` (mirrors base `theme.json styles.color`). Slugs now hold dark values, so these resolve to dark surface/text automatically — **no hardcoded hex in `styles`**.
  - [x] Did **not** redeclare `styles.elements.link`, typography, spacing, or blocks — they inherit from base `theme.json` and re-resolve through `var:preset|color|*` (e.g. `link → primary`, now the dark `#EE5830`).
- [x] **Task 4 — Confirm base theme.json is untouched + no activation logic (AC: 3)**
  - [x] This session made **no edit to `theme.json`** (its only working-tree diff is the pre-existing, uncommitted Story 1.2 `fontFace` work — no colour/palette/styles change from 1.3). The variation is purely additive.
  - [x] **No** theme-switcher block/template, **no** `prefers-color-scheme` media query, **no** `color-scheme`/auto-activation introduced. Activation is deferred (light-only at v1); the variation merely *exists* and is selectable. Scope boundary documented in Dev Notes.
- [x] **Task 5 — Validate & verify (AC: 1, 4)**
  - [x] `styles/dark.json` parses as valid JSON (`python3 -c "import json; json.load(open('.../styles/dark.json'))"` → VALID).
  - [x] **Gate A:** zero hardcoded colours in the variation's `styles` object (grep for `#hex`/`rgb(`/`hsl(` → NONE; the sole grep hit `"color": {` is the `styles.color` *key*, not a value). All `styles` colours are `var:preset|color|*`. Hex appears only in the `settings.color.palette` registry, same as base `theme.json`.
  - [x] Palette completeness: all **16** base slugs present and identically ordered; the **6** overrides equal the `modes.dark` values; the other **10** equal their light values (programmatic diff vs `theme-tokens.json` → PASS).
  - [x] No remote URLs (only `$schema` → `schemas.wp.org`).
  - [x] **Live Site-Editor check (variation appears + applies cleanly) deferred to Story 1.11** — no running WP env in the repo yet; same precedent as 1.1 AC-4 and 1.2 Task 7. Verified statically by JSON validity + `$schema`/`version: 3` + `title` presence + slug/value diff.
  - [x] **No Gate A regression** in base files: `patterns/`/`templates/`/`template-parts/` unchanged this session; `theme.json` colours untouched.
- [x] **Task 6 — Keep `token-map.md` truthful (AC: 2)**
  - [x] Added a "Dark mode" note in `token-map.md` (per rule 4) recording that `modes.dark` maps to `styles/dark.json` (color style variation) with the 6 override slugs + values, and that the variation is additive/opt-in. Existing table left intact.

## Dev Notes

### What this story is (and is not)
- **Is:** wire the dark token set (`theme-tokens.json#modes.dark`) into the theme as a **native WordPress color style variation** (`styles/dark.json`) so the theme can resolve dark colours **from tokens, with zero hardcoded values** — satisfying the epics AC — and record the activation mechanism as the scope boundary.
- **Is not:** *activating* dark mode. No theme-switcher UI, no `prefers-color-scheme` auto-switch, no toggle block, no JS. Architecture is **"light mode only at v1"** / "dark mode (deferred v1)". This story ensures the dark palette is **wired and token-resolved now** (so it is *not hardcoded later*), exactly as the AC demands, while leaving light as the shipped default. Also **not**: new templates/parts (**1.4**), patterns (**1.5**), block locking (**1.6**), `functions.php`/`ink-core` (**1.7**), or any tier/Gradering colours (**Epic 5**). _[Source: epics.md#Story-1.3; architecture.md NFR-2 "light mode only at v1" + "dark mode (deferred v1)"; epics.md Stories 1.4–1.7]_

### ⭐ The core design decision: wire-the-set vs light-only-at-v1
There is an apparent tension between the **epics AC** ("the theme resolves colours from the dark token set with no hardcoded values") and the **architecture** ("light mode only at v1 / dark mode deferred"). They are reconciled by separating **wiring** from **activation**:
- **Wiring (this story, required by the AC):** the dark palette must live in the theme **as tokens**, so that when dark mode is eventually activated, no developer hardcodes `#171C1F` into a template. A **color style variation** is WordPress's canonical, additive way to carry an alternate palette.
- **Activation (deferred per architecture):** no switcher, no auto-detect, no default change. The variation simply *exists* and is *selectable* in the Site Editor; the site still ships light by default.
- **Why a style variation (not `theme.json` `appearanceTools`/`color-scheme`, not duplicate templates):** a `styles/*.json` variation is (1) **additive** — it cannot regress the light default; (2) **token-only** — it redefines palette *slugs*, so every existing `var:preset|color|*` reference (links, surfaces, buttons) inherits the dark value with **no markup changes and no hardcoded hex**; (3) the **architecture-sanctioned** occupant of the `styles/` directory (tree line 1007); (4) **future-proof** — when activation lands, it is a one-line opt-in, not a re-plumbing. This is the lowest-risk way to satisfy the AC without violating the light-only-at-v1 rule.

### Style-variation mechanics (WP 7.0 / schema v3)
- A **block style variation** is a JSON file in the theme's `styles/` directory with the same shape as `theme.json` (`$schema`, `version: 3`, `settings`/`styles`). WordPress auto-discovers it and lists it in **Global Styles → Styles** (Site Editor) under its `"title"`. _[WP theme.json v3 style variations]_
- A variation **merges over** the base `theme.json` when selected — you only need to declare what *differs*. For a colour-only dark variation: redeclare `settings.color.palette` (so the slugs hold dark values) and the top-level `styles.color` (background/text). Everything else (typography, spacing, link colour, blocks) **inherits** and re-resolves through the same `var:preset|color|*` tokens.
- **Why redeclare the full 16-slug palette and not just 6:** when a variation defines `settings.color.palette`, it **replaces** the palette for that variation rather than patching individual entries. Omitting the other 10 slugs would drop them (breaking `var:preset|color|secondary` etc.). So all 16 slugs are listed; 6 take dark values, 10 keep light values verbatim.
- **`title` is Afrikaans, sentence case** (`"Donker"`) — it is a user-facing label in the Site-Editor style picker, so Gate D applies. _[project-context.md Gate D; afrikaans-terms.md]_
- **No `functions.php` needed** — variation discovery is automatic, keeping the theme presentation-only.

### ⚠️ Guardrails (prevent disasters)
- **Do NOT touch base `theme.json`.** The whole point is additive wiring; any edit to the base file risks regressing the 1.1 tokens / 1.2 typography. Verify a clean diff on `theme.json`. _[1-1 + 1-2 Completion Notes]_
- **Verbatim values only.** The 6 dark overrides come **straight from `theme-tokens.json#modes.dark`**; the 10 carried-forward colours come straight from the light palette. **Do not invent** dark variants for slugs the design didn't specify (no guessed dark `secondary`, `border-light`, tier colours, etc.). The design intent is 6 overrides — honour it. _[theme-tokens.json#modes.dark; 1-1 guardrail "do not invent tokens"]_
- **No hardcoded hex in `styles`.** Surface/text in the variation must be `var:preset|color|surface` / `…|text`, never the literal dark hex — that is the entire Gate A point of the AC. Hex lives **only** in the `settings.color.palette` registry (exactly as base `theme.json` does). _[project-context.md Gate A; epics.md#Story-1.3]_
- **No activation logic.** No `prefers-color-scheme`, no toggle, no JS, no default change. Activation is a *future* story; shipping it here violates "light mode only at v1." _[architecture.md NFR-2]_
- **No tier/Gradering colours.** Dark mode is Foundation palette only; `brons`/`silwer`/`goud`/`meester` styling is Epic 5. _[project-context.md THE conflation rule; 1-1 guardrail]_
- **Slugs are load-bearing.** Reuse the exact 16 slugs from base `theme.json` — they are referenced across templates/patterns and depended on by downstream stories. Do not rename. _[1-1 Completion Notes]_

### Source tree (files this story touches)
```
wp-content/themes/ink-foundation/
├── theme.json                 # NO CHANGE — verify byte-for-byte untouched (base light tokens stay default)
├── styles/                    # NEW dir — architecture-sanctioned home for style variations (tree line 1007)
│   └── dark.json              # NEW — color style variation: full 16-slug palette w/ 6 dark overrides; styles.color via tokens
├── patterns/                  # NO CHANGE (audit-only re-confirm: no hex introduced)
├── templates/                 # NO CHANGE
└── template-parts/            # NO CHANGE
docs/design-handoff/tokens/
└── token-map.md               # UPDATE — note modes.dark → styles/dark.json wiring (rule 4: keep map truthful)
```
_[Source: architecture.md#`ink-foundation` FSE theme tree, line 1007 "styles/ # block style variations"]_

### Dark palette source values (copy verbatim — do not retype from memory)
From `theme-tokens.json#modes.dark` (the 6 overrides):
| slug | dark value | (light value, for reference) |
|---|---|---|
| `surface` | `#171C1F` | `#F8F6F2` |
| `surface-alt` | `#1A1D21` | `#FDFCFA` |
| `text` | `#EAE7DF` | `#1A1D21` |
| `primary` | `#EE5830` | `#EA4015` |
| `accent` | `#6AA88A` | `#4D8066` |
| `border` | `#2A3035` | `#E8E4DC` |

The other 10 slugs (`primary-light`, `secondary`, `accent-light`, `muted-text`, `success`, `warning`, `danger`, `highlight`, `highlight-foreground`, `gold-muted`) keep their **light** values verbatim from `theme.json` / `theme-tokens.json#color`.

### Project constraints that apply
- **Presentation only:** zero business logic in the theme / `functions.php` (none created here — variation discovery is automatic). _[project-context.md three-layer rule]_
- **Afrikaans-first, sentence-case:** the variation `title` ("Donker") is the only user-facing string; sentence case, Afrikaans. No English label. _[project-context.md Gate D; memory: Afrikaans is source of truth]_
- **Gate A still holds** — token-only; hex only in the palette registry, never in `styles`. _[project-context.md Quality Gate A]_

### Testing standards summary
- No unit-test harness exists at repo root yet (arrives in **Story 1.11**); the theme is covered by the **Gate A/D static audits + (future) E2E/visual checks**, not PHP unit tests. _[project-context.md "cover the block theme via E2E/visual checks instead of unit tests"; 1-1 + 1-2 Completion Notes]_
- Verification for this story = JSON validity + Gate A grep (no hex in `styles`) + palette slug/value diff vs the token file + no-remote-URL grep + base-`theme.json`-unchanged check, with the live Site-Editor render/selection confirmation **deferred to 1.11** (same precedent as 1.1 AC-4 and 1.2 Task 7).

### Project Structure Notes
- `styles/` is the architecture-sanctioned home for style variations (tree line 1007); creating it here, at the dark-mode story, is expected — it is the directory's first occupant. No structural deviation.
- `functions.php`, the fuller scaffold, and the pattern library remain **not created** here — they arrive with 1.4/1.5/1.7. This story stays within the new `styles/dark.json` + a one-line note in `token-map.md`.
- No conflict between epic intent and repo state: the base `theme.json` already carries the exact light palette slugs the variation reuses; this story adds the dark overlay without disturbing them.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.3 — Dark mode tokens ("the theme resolves colours from the dark token set with no hardcoded values")]
- [Source: docs/design-handoff/tokens/theme-tokens.json#modes.dark — the 6 dark overrides (surface/surfaceAlt/text/primary/accent/border); #color — the 16-slug light palette]
- [Source: docs/design-handoff/tokens/token-map.md#Rules — never use raw Lovable names; generate from normalized tokens; rule 4 keep map updated]
- [Source: _bmad-output/implementation-artifacts/1-1-themejson-design-token-system.md — 16-colour palette + exact slugs (load-bearing); "do not invent tokens" guardrail; deferred Site-Editor verification precedent]
- [Source: _bmad-output/implementation-artifacts/1-2-typography-system.md — additive-only / no-regression discipline; Gate A re-grep pattern; Site-Editor check deferred to 1.11]
- [Source: _bmad-output/planning-artifacts/architecture.md#Architecture-Principles (NFR-2 "light mode only at v1"); #Areas-for-Future-Enhancement ("dark mode (deferred v1)"); #`ink-foundation` FSE theme tree (line 1007 "styles/ # block style variations")]
- [Source: _bmad-output/project-context.md — Quality Gate A (tokens only, no hardcoded values), Gate D (Afrikaans-first, sentence case), three-layer separation, WP 7.0+/PHP 8.3+]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m] (Opus 4.8, 1M context)

### Debug Log References

- JSON validity: `python3 -c "import json; json.load(open('styles/dark.json'))"` → VALID.
- Gate A grep over `styles/dark.json` `styles` block (`#hex`/`rgb(`/`hsl(`) → **NONE**. Both `styles.color` values are `var:preset|color|surface` / `…|text`. Hex present only inside `settings.color.palette` (token registry, expected — identical pattern to base `theme.json`).
- Palette verification (programmatic diff vs `theme-tokens.json`): 16 slugs, **identical and identically ordered** to base `theme.json`; the 6 overrides (`surface`/`surface-alt`/`text`/`primary`/`accent`/`border`) equal `modes.dark` verbatim; the other 10 equal their light values verbatim. Result: **PASS**.
- Minimality check: `settings` keys = `['color']` only; `styles` keys = `['color']` only — no typography/spacing/blocks redeclared (they inherit from base). `title='Donker'`, `version=3`, `$schema` present.
- No-remote-URL grep: only match is `$schema` → `schemas.wp.org`.
- No-regression: `git diff --quiet` on `patterns/`/`templates/`/`template-parts/` → clean (unchanged); `theme.json` working-tree diff is solely the pre-existing uncommitted Story 1.2 `fontFace` work (no colour/palette/styles edit from this story).

### Completion Notes List

- **Core decision — wire vs activate.** The epics AC ("theme resolves colours from the dark token set with no hardcoded values") and the architecture ("light mode only at v1 / dark mode deferred") were reconciled by separating **wiring** (done here) from **activation** (deferred). The dark palette is wired in as a **native WordPress color style variation** (`styles/dark.json`), which is additive, token-only, and the architecture-sanctioned occupant of `styles/`. It is *selectable* in the Site Editor but **not** applied by default — no switcher, no `prefers-color-scheme`, no JS, no default change.
- **Full 16-slug palette redeclared (not just 6).** A variation's `settings.color.palette` *replaces* (not patches) the palette, so all 16 slugs are listed to keep every `var:preset|color|*` reference resolving. 6 take the `modes.dark` values; 10 carry their light values forward verbatim. No dark variants invented for the 10 the design didn't specify.
- **Zero hardcoded hex in `styles`** — the entire Gate A point of the AC. Surface/text are `var:preset|color|*`; because the slugs now hold dark values, the variation re-skins links/surfaces/buttons with **no markup change**.
- **Base `theme.json` not touched by this story.** Additive only — `styles/dark.json` is the sole new theme file; `token-map.md` got a discoverability note (rule 4). The 1.1 token set and 1.2 typography additions are untouched.
- **`title` "Donker"** is the only user-facing string — Afrikaans, sentence case (Gate D).
- **AC-4 live Site-Editor check deferred to Story 1.11** (no running WP env in repo), consistent with the 1.1 and 1.2 precedent. All static verification (JSON validity, Gate A, palette slug/value diff, minimality, no-CDN, no-regression) passed.
- **No test/lint harness at repo root yet** (Story 1.11) — the theme is covered by Gate A/D static audits + future E2E/visual checks per project-context, not PHP unit tests. No existing tests to run or regress.
- **No tier/Gradering colours** — dark mode is Foundation palette only; Gradering display styling remains Epic 5.

### File List

- `wp-content/themes/ink-foundation/styles/dark.json` (new) — color style variation "Donker": full 16-slug palette with the 6 `modes.dark` overrides applied + 10 light colours carried forward; `styles.color` background/text via `var:preset|color|*`. Additive/opt-in; light remains the v1 default.
- `docs/design-handoff/tokens/token-map.md` (modified) — added a "Dark mode" section noting `modes.dark` → `styles/dark.json` (6 override slugs + values; additive/opt-in, activation deferred).

### Review Findings

Code review (2026-06-21): 0 decision-needed, 0 patch, 0 defer, 0 dismissed. All three layers (Blind Hunter, Edge Case Hunter, Acceptance Auditor) ran successfully; no layers failed.

No findings. `styles/dark.json` is valid JSON (`$schema`, `version: 3`, `title: "Donker"`), declares only `settings.color.palette` + `styles.color`. Programmatic verification: all 16 slugs present in identical order/name to base `theme.json`; the 6 `modes.dark` overrides match verbatim; the 10 carry-forward colours match their light values verbatim. Zero hardcoded hex in the `styles` block (both values `var:preset|color|*`). Base `theme.json` colour/palette/styles untouched by this story (working-tree diff contains no colour lines). No activation logic shipped. Note: live Site-Editor render/selection check is explicitly deferred to Story 1.11 per the documented 1.1/1.2 precedent — acknowledged scope deferral, not a defect.

## Change Log

| Date | Change |
|---|---|
| 2026-06-20 | Story created (context-engineered) — dark mode tokens: wire `modes.dark` into the theme as an additive color style variation (`styles/dark.json`) with the full 16-slug palette + 6 dark overrides, token-resolved (no hardcoded values), light remains the v1 default; activation deferred. Status → ready-for-dev. |
| 2026-06-20 | Implemented dark mode tokens (Tasks 1–6): created `styles/dark.json` (color style variation "Donker") — 16-slug palette with 6 `modes.dark` overrides + 10 light carry-forwards, `styles.color` via tokens; added `token-map.md` dark-mode note. Static verification passed (JSON valid, Gate A clean — 0 hex in `styles`, palette slug/value diff PASS, no CDN, no base regression). Live Site-Editor check deferred to 1.11. Status → review. |
