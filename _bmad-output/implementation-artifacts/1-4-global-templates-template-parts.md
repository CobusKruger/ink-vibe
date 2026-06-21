---
baseline_commit: c5821d806c1700331ae3a352f097d8a0646c6fe8
---

# Story 1.4: Global templates & template parts

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a content manager,
I want global header, footer, and reusable section shells,
so that every page shares consistent structure that renders site-wide.

## Acceptance Criteria

1. **Global header & footer render site-wide via template parts** — The `header` and `footer` template parts exist as files under `wp-content/themes/ink-foundation/template-parts/` and are **registered in `theme.json` `templateParts`** with their correct `area` (`header`/`footer`). Every site-wide template (`index`, `page`, and the existing `front-page`) embeds them via `<!-- wp:template-part … area:"header"/"footer" -->`, so the global chrome appears on **all** front-end views — not only the home page. _[Source: epics.md#Story-1.4 "header, footer … exist as template parts and render across the site"; architecture.md#`ink-foundation` FSE theme tree line 1004 "parts/ # header, footer, section shells"]_
2. **Mandatory base templates exist so the theme is a valid block theme** — `templates/index.html` exists (the **required** fallback every block theme must ship; without it WordPress treats the theme as incomplete and falls back unpredictably) and a generic `templates/page.html` exists for the WordPress **Page** post type. Both follow the header → `main` (post/page content) → footer structure and resolve content through core blocks (`core/post-title`, `core/post-content`), not hardcoded copy. The existing `front-page.html` is preserved and continues to win for the home view. _[Source: epics.md#Story-1.4 "Given the FSE theme, When templates load, Then …"; architecture.md tree line 1002 "templates/ # front-page, …"; WordPress block-theme template hierarchy]_
3. **Reusable section shells exist as template parts** — At least one **structural section shell** template part exists under `template-parts/` and is registered in `theme.json` `templateParts` with `area: "uncategorized"`, giving content managers a consistent, insertable container (a full-width constrained `main`-region section wrapper with token-driven vertical rhythm) for assembling pages. These shells are **structural containers only** — empty/placeholder-prompt content, not finished hero/featured-grid/CTA designs (those are the **Story 1.5** pattern library) and not the documented page archetypes A–D (those are **Story 1.9**). _[Source: epics.md#Story-1.4 "reusable section shells exist as template parts"; epics.md#Story-1.5 (pattern library) + #Story-1.9 (archetypes) — scope boundaries; architecture.md tree line 1004 "section shells"]_
4. **Afrikaans-first, sentence-case part/template titles (Gate D)** — Every `templateParts` entry `title` and every block-theme template `title`/`area` label that surfaces in the Site Editor is **Afrikaans, sentence case** (e.g. `"Kopstuk"`, `"Voetstuk"`, `"Seksie-omhulsel"`) — no English UI strings reach the editor or front end. The existing curated Afrikaans nav/footer copy in the header/footer parts is **not** altered or re-translated. _[Source: project-context.md Gate D "Afrikaans-first … sentence case"; memory: Afrikaans is source of truth; ui-copy-translations.md (nav + footer copy already curated)]_
5. **Token-only & presentation-only (Gate A + three-layer), no regression** — All new templates/parts use **only `theme.json` tokens** (`var:preset|*` / `var:custom|*` / `has-…` classes) — no hardcoded hex/rgb/hsl, no untokenised spacing or unnamed type sizes. No PHP business logic and no `functions.php` is introduced (template-part + template discovery is automatic; `ink-core` is Story 1.7). The 1.1 token set, 1.2 typography system, and 1.3 dark variation are **byte-for-byte untouched**. _[Source: project-context.md Quality Gate A + three-layer separation ("no business logic in the theme"); 1-1/1-2/1-3 no-regression discipline]_
6. **Validity & verification** — `theme.json` parses as valid JSON and keeps `"version": 3`; every `templateParts` entry references a part `.html` file that exists; every `<!-- wp:template-part -->` reference in the templates resolves to a registered part; no remote URLs are emitted. Templates/parts load cleanly in the WP 7.0 Site Editor (header/footer visible on all views, section shell insertable). **Live Site-Editor render confirmation is deferred to Story 1.11** (no running WP env in the repo yet) — same precedent as 1.1 AC-4, 1.2 Task 7, 1.3 Task 5. _[Source: architecture.md#Starter-Template NFR-2; project-context.md WP 7.0+; 1-1/1-2/1-3 deferred-Site-Editor-check precedent]_

## Tasks / Subtasks

> **Current state (read before starting):** The theme already has working **header** and **footer** template parts (`template-parts/header.html`, `template-parts/footer.html`) that each embed a pattern (`ink-foundation/header-main`, `ink-foundation/footer-main`); both parts are already registered in `theme.json` `templateParts` — **but their `title`s are English ("Header"/"Footer"), which violates Gate D.** The only template present is `templates/front-page.html` (home view), which already wires header + `main` + footer. There is **no `index.html`** (a block theme is technically invalid without it), **no `page.html`**, and **no section-shell part**. The curated Afrikaans nav/footer copy in the patterns is correct (verified against `ui-copy-translations.md`) — **do not touch it.** This story formalizes the GLOBAL template + part set so the chrome renders site-wide and adds structural section shells. **Do NOT regress the 1.1 tokens / 1.2 typography / 1.3 dark variation, and do NOT build the 1.5 pattern library or 1.9 archetypes here.**

- [x] **Task 1 — Add the mandatory `index.html` base template (AC: 1, 2, 5)**
  - [x] Created `templates/index.html` wiring **header part → `main` (constrained `core/post-title` + `core/post-content`) → footer part**, mirroring `front-page.html` (same `area:"header"/"footer"` template-part refs, same `main` group with token padding `s-64`/`s-24`, `layout:{type:"constrained"}`, `alignwide` inner group with `blockGap:s-24`).
  - [x] Used **core blocks** for content (`core/post-title` level 1, `3xl`; `core/post-content` constrained) — no hardcoded body copy, no business logic. Minimal valid fallback; richer single/archive templates remain later epics.
  - [x] Token-only: every spacing/size is a `var:preset|*` / `has-…` reference (no hex, no raw px/rem).

- [x] **Task 2 — Add a generic `page.html` template (AC: 1, 2, 5)**
  - [x] Created `templates/page.html` for the WordPress **Page** post type: header part → constrained `main` with `core/post-title` + `core/post-content` → footer part. This is the chrome the assembly-only org pages (Epic 15) and archetype pages (1.9) build on.
  - [x] Same token-driven `main` padding/width conventions as `front-page.html` / `index.html`. No hardcoded values, no PHP.

- [x] **Task 3 — Create reusable section-shell template part(s) (AC: 3, 4, 5)**
  - [x] Created `template-parts/section.html` — a **structural** full-width, constrained `section` group (`align:"full"`, `layout:{type:"constrained"}`) with token vertical padding (`s-48` top/bottom, `s-24` sides) wrapping an `alignwide` inner group with `blockGap:s-24`. Content is a **single muted Afrikaans placeholder prompt** ("Voeg blokke by om hierdie seksie te bou.") — NOT a finished hero/grid/CTA (that is 1.5).
  - [x] Kept it a generic shell — no page-specific layouts, no A–D archetypes (1.9). One reusable container any template/page can drop in.
  - [x] Token-only (`var:preset|spacing|*`, `sm` font size, `muted-text` colour) + Afrikaans sentence-case placeholder; no English.

- [x] **Task 4 — Register parts/templates in `theme.json` and fix Gate D titles (AC: 1, 3, 4, 5, 6)**
  - [x] In `theme.json` `templateParts`: renamed `header` title `"Header"` → `"Kopstuk"` and `footer` title `"Footer"` → `"Voetstuk"` (Afrikaans, sentence case). `area`/`name` unchanged (load-bearing).
  - [x] Added the section-shell entry: `{ "area": "uncategorized", "name": "section", "title": "Seksie-omhulsel" }`.
  - [x] **Decision: no `customTemplates` needed.** `index`/`page`/`front-page` are core block-theme hierarchy slugs auto-discovered by WordPress; `customTemplates` is only for *additional* selectable page templates (none in this story). Left auto-discovered.
  - [x] Made **no other change** to `theme.json` — the 1.4 diff is exclusively the `templateParts` block. (The fontFace/heading-sizes/core/button hunks visible in `git diff` against HEAD are the pre-existing **uncommitted Story 1.2** work — same situation Story 1.3 documented — not edits from this story.)

- [x] **Task 5 — Confirm header/footer parts unchanged + Afrikaans copy preserved (AC: 4, 5)**
  - [x] Verified `template-parts/header.html`, `template-parts/footer.html`, `patterns/header-main.php`, `patterns/footer-main.php` are **not modified** (`git diff --stat` empty). Curated Afrikaans nav (`Tuis`/`Ontdek`/`Opleiding`/`Uitdagings`/`Gemeenskap`/`My profiel`/`Begin skryf`) + footer copy preserved per `ui-copy-translations.md`. No English UI string and no `text-transform`-forced heading casing introduced in new files.
  - [x] Confirmed **no `functions.php`** created and no PHP logic added — part/template discovery is automatic (presentation-only). `ink-core` remains a Story 1.7 concern.

- [x] **Task 6 — Static verification (AC: 5, 6)**
  - [x] `theme.json` parses as valid JSON, `version:3` intact.
  - [x] **`templateParts` ↔ files:** all 3 `templateParts[].name` (`header`/`footer`/`section`) have a matching `template-parts/{name}.html`; no orphan files; no registered-without-file. PASS.
  - [x] **Template ↔ parts:** all 6 `wp:template-part` refs across `front-page`/`index`/`page` resolve to registered parts. PASS.
  - [x] **Gate A grep** over `templates/`, `template-parts/`, `patterns/`: no `#hex`/`rgb(`/`hsl(`; no raw px/rem/em literals in the 3 new files. PASS.
  - [x] **Gate D grep:** no English UI strings in new files; titles `Kopstuk`/`Voetstuk`/`Seksie-omhulsel` sentence case; no raw font-family strings in markup. PASS.
  - [x] **No remote URLs** — only `$schema` → `schemas.wp.org`. PASS.
  - [x] **No regression:** `theme.json` 1.4 change is exclusively `templateParts`; header/footer parts + patterns + 1.3 `styles/dark.json` + `front-page.html` unchanged (`git diff --stat` empty). Live Site-Editor render confirmation **deferred to 1.11**.

## Dev Notes

### What this story is (and is not)
- **Is:** formalize the **global** template + template-part set so the header/footer chrome and a reusable section shell render **site-wide** — add the mandatory `index.html` fallback and a generic `page.html`, create a structural `section` shell part, register it in `theme.json`, and fix the two English part titles to Afrikaans sentence case. Token-only, presentation-only, additive.
- **Is not:** the **pattern library** (hero, featured grid, archive intro, CTA bands, profile summaries, card/button/emphasis variants — **Story 1.5**); the documented **page archetypes A–D** (**Story 1.9**); **block locking** of structure (**Story 1.6**); single/archive **reading/discovery templates** (Epics 7/8/10/11/12); `functions.php` / `ink-core` (**Story 1.7**); any dark-mode **activation** (deferred); tier/Gradering styling (**Epic 5**). Section "shells" here are **structural container parts**, deliberately empty of finished design — the catalogue of designed building blocks is 1.5. _[Source: epics.md Stories 1.5/1.6/1.7/1.9; architecture.md Epic→Location map]_

### ⭐ Why `index.html` is the load-bearing addition
A WordPress **block theme must ship `templates/index.html`** — it is the universal fallback at the bottom of the template hierarchy. The repo currently has only `front-page.html`, so any non-home view (a single post, a page, search, 404) has **no theme-provided template** and WordPress falls back to a generic/empty render. Adding `index.html` (plus `page.html` for the Page type) is what makes the global header/footer actually render **across the site**, which is the explicit AC. This is structural plumbing, not design. _[Source: WordPress block-theme template hierarchy; epics.md#Story-1.4 AC]_

### Section-shell scope guardrail (distinct from 1.5 / 1.9)
- A **section shell** = a generic, reusable **structural container** (full-width constrained group with token vertical rhythm + an `alignwide` inner region) that an editor inserts to start a page section. Its content is an **Afrikaans placeholder prompt only**.
- It is **not** a designed pattern. The 1.5 pattern library supplies the *finished* hero/featured-grid/archive-intro/CTA-band/profile-summary patterns and the card/button/emphasis style variants. The 1.9 archetypes A–D are *documented page scaffolds* assembled from those patterns. Keep this story to the empty structural container so 1.5/1.9 are not pre-empted.
- Register the shell as `area:"uncategorized"` (general-purpose, insertable anywhere) — header/footer areas are reserved for the chrome parts.

### theme.json template/part mechanics (WP 7.0 / schema v3)
- **`templateParts`** registers each part with `area` (`header`/`footer`/`uncategorized`), `name` (matches the `template-parts/{name}.html` filename), and `title` (the Site-Editor label — **Gate D applies**). WordPress auto-discovers the `.html` files; the registry supplies the area + label. _[theme.json v3 `templateParts`]_
- **Templates** (`templates/*.html`) are auto-discovered by the block-theme template hierarchy — `index`, `front-page`, `page`, `single`, `archive`, etc. are **core** template slugs and need **no** `customTemplates` registration; `customTemplates` is only for *additional* selectable page templates (none needed here).
- A **template part is embedded** via `<!-- wp:template-part {"slug":"header","theme":"ink-foundation","area":"header"} /-->` — exactly the form already in `front-page.html`. Reuse that form verbatim in `index.html`/`page.html`.
- **No `functions.php` enqueue or `register_block_pattern` is needed** for parts/templates — discovery is automatic. (Patterns under `patterns/*.php` self-register via their header comment, as the existing header/footer patterns already do.) Keeping zero PHP preserves the presentation-only rule.

### ⚠️ Guardrails (prevent disasters)
- **Do NOT alter the curated Afrikaans copy** in `header.html`/`footer.html`/`header-main.php`/`footer-main.php`. The nav labels (`Tuis`, `Ontdek`, `Opleiding`, `Uitdagings`, `Gemeenskap`, `My profiel`, `Begin skryf`) and footer copy are already verified against `ui-copy-translations.md`. Never AI-retranslate or "tidy" curated Afrikaans. _[memory: Afrikaans is source of truth; ui-copy-translations.md]_
- **Token-only in every new file.** No hex/rgb/hsl, no raw spacing/size. Mirror the `var:preset|spacing|s-*` + `layout:{type:"constrained"}` conventions in `front-page.html`. _[project-context.md Gate A]_
- **Presentation-only.** No `functions.php`, no PHP business logic, no `ink-core` (that is Story 1.7). Template/part discovery is automatic. _[project-context.md three-layer separation]_
- **Additive, no regression.** Touch `theme.json` only for the two title fixes + the `section` `templateParts` entry. Do **not** edit colours/typography/spacing/`styles` or the 1.3 `styles/dark.json`. Verify the diff. _[1-1/1-2/1-3 Completion Notes]_
- **No scope bleed.** No designed patterns (1.5), no archetypes (1.9), no block locking (1.6). Section shells are empty structural containers. _[epics.md Stories 1.5/1.6/1.9]_
- **Sentence case, no CSS transform.** Any new heading/label is Afrikaans sentence case authored as text; never enforce casing with `text-transform`. _[project-context.md Gate D; 1-2 Task 6]_

### Source tree (files this story touches)
```
wp-content/themes/ink-foundation/
├── theme.json                 # UPDATE — templateParts: "Header"→"Kopstuk", "Footer"→"Voetstuk";
│                              #   add { area:"uncategorized", name:"section", title:"Seksie-omhulsel" }.
│                              #   NO other change (tokens/typography/styles byte-for-byte intact)
├── templates/
│   ├── front-page.html        # NO CHANGE — already wires header + main + footer (home view)
│   ├── index.html             # NEW — mandatory fallback: header → main (post-title/post-content) → footer
│   └── page.html              # NEW — Page post type: header → main (post-title/post-content) → footer
├── template-parts/
│   ├── header.html            # NO CHANGE (curated Afrikaans nav — verified)
│   ├── footer.html            # NO CHANGE (curated Afrikaans footer — verified)
│   └── section.html           # NEW — structural section shell (constrained group, token rhythm, AF placeholder)
├── patterns/
│   ├── header-main.php        # NO CHANGE
│   └── footer-main.php        # NO CHANGE
└── styles/dark.json           # NO CHANGE (1.3)
```
_[Source: architecture.md#`ink-foundation` FSE theme tree lines 999–1009 — `templates/` (front-page + …), `parts/` (header, footer, section shells)]_

### ⚠️ Directory-naming variance (documented, intentional)
The architecture tree labels the parts directory `parts/` (lines 853, 1004); the repo was bootstrapped (Create Block Theme) with `template-parts/`, and the existing header/footer parts + `front-page.html` references live there. **`template-parts/` is a WordPress-valid part directory name** (WP recognises both `parts/` and `template-parts/`), and `theme.json` `templateParts` + the `wp:template-part` `area`/`slug` refs resolve regardless of which of the two sanctioned names is used. To avoid regressing the existing 1.1–1.3 work and reference paths, this story **keeps `template-parts/`** and adds the new shell there. This is a benign naming variance from the architecture diagram, not a structural deviation — flagged here per the project's "note detected conflicts/variances with rationale" rule.

### Current markup facts (verified by inspection)
- `front-page.html`: `template-part area:"header"` → `main` group (token padding `s-64`/`s-24`, constrained) → `template-part area:"footer"`. This is the exact structure to reuse for `index.html`/`page.html`. _[front-page.html]_
- `header.html` / `footer.html` each contain a single `<!-- wp:pattern {"slug":"ink-foundation/header-main|footer-main"} /-->` — the parts delegate to the patterns. Leave as-is.
- `theme.json` `templateParts` currently: `header`/title "Header", `footer`/title "Footer" — the **only** Gate D defect to fix; everything else is token-clean. _[theme.json lines 231–242]_
- Existing patterns are already Gate A clean (1.1 audit) and Gate D clean except they are user-facing Afrikaans already; no `text-transform` on headings (1.2 Task 6).

### Project constraints that apply
- **Presentation only:** zero business logic in the theme / `functions.php` (none created here — discovery is automatic). _[project-context.md three-layer rule]_
- **Afrikaans-first, sentence case:** new titles (`Kopstuk`/`Voetstuk`/`Seksie-omhulsel`) and any placeholder string are Afrikaans, sentence case; no English UI. Curated copy untouched. _[project-context.md Gate D; memory: Afrikaans is source of truth]_
- **Gate A still holds** — every new value is token-referenced. _[project-context.md Quality Gate A]_
- **Block theme, not classic** — FSE templates/parts; structure-locking comes in 1.6 (do not add `lock`/`templateLock` here). _[project-context.md "Block theme"; epics.md#Story-1.6]_

### Testing standards summary
- No unit-test harness exists at the repo root yet (arrives in **Story 1.11**); the block theme is covered by the **Gate A/D static audits + (future) E2E/visual checks**, not PHP unit tests. _[project-context.md "cover the block theme via E2E/visual checks instead of unit tests"; 1-1/1-2/1-3 Completion Notes]_
- Verification for this story = JSON validity + `templateParts↔files` + `template↔parts` resolution + Gate A grep + Gate D grep + no-remote-URL grep + no-regression diff, with the live Site-Editor render confirmation **deferred to 1.11** (same precedent as 1.1 AC-4 / 1.2 Task 7 / 1.3 Task 5).

### Project Structure Notes
- `templates/index.html` + `templates/page.html` are core block-theme hierarchy templates — expected occupants of `templates/` per the architecture tree (line 1002). `template-parts/section.html` is the "section shells" entry from line 1004. No structural deviation beyond the documented `parts/` vs `template-parts/` naming variance above.
- `functions.php`, the pattern library, archetypes, and `ink-core` remain **not created** here — they arrive with 1.5/1.7/1.9 per the epic sequence. This story stays within new templates + one section part + the `theme.json` `templateParts` edit.
- No conflict between epic intent and repo state: the chrome parts already exist; this story makes them render site-wide via the base templates and adds the structural shell.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.4 — Global templates & template parts ("header, footer, and reusable section shells exist as template parts and render across the site")]
- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.5 (pattern library) + #Story-1.6 (block locking) + #Story-1.9 (page archetypes A–D) — downstream scope boundaries]
- [Source: _bmad-output/planning-artifacts/architecture.md#`ink-foundation` FSE theme tree (lines 999–1009: templates/, parts/ "header, footer, section shells", patterns/, styles/); line 853 "standard FSE (templates/, parts/, patterns/, styles/, theme.json)"; #Epic→Location map (Epic 1 = ink-foundation scaffold)]
- [Source: _bmad-output/implementation-artifacts/1-1-themejson-design-token-system.md — Gate A token discipline; established theme.json conventions; deferred Site-Editor verification precedent]
- [Source: _bmad-output/implementation-artifacts/1-2-typography-system.md — sentence-case authoring rule (no text-transform); additive/no-regression discipline; Gate A re-grep pattern]
- [Source: _bmad-output/implementation-artifacts/1-3-dark-mode-tokens.md — additive-only style file (styles/dark.json); do-not-touch-base-theme.json discipline; Site-Editor check deferred to 1.11]
- [Source: docs/design-handoff/page-map.csv — front-page/page targets; org pages assembled from template+patterns; docs/design-handoff/agent-brief.md — pattern-first, Afrikaans copy from ui-copy-translations.md, no copy lifted from mockup]
- [Source: docs/ui-copy-translations.md — curated nav labels (Tuis/Ontdek/Opleiding/Uitdagings/Gemeenskap/My profiel/Begin skryf) + footer copy (already in header/footer parts; do not re-translate)]
- [Source: _bmad-output/project-context.md — Quality Gate A (tokens only), Gate D (Afrikaans-first, sentence case), three-layer separation (no theme business logic), Block theme/FSE, WP 7.0+/PHP 8.3+]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m] (Opus 4.8, 1M context)

### Debug Log References

- JSON validity: `python3 json.load(theme.json)` → VALID; `version = 3`.
- templateParts↔files: `header`/`footer`/`section` each map to `template-parts/{name}.html`; 0 registered-without-file, 0 orphan-file → PASS.
- template↔parts: 6 `wp:template-part` refs (`front-page`×2, `index`×2, `page`×2), all `slug`/`area` resolve to registered parts → PASS.
- Gate A grep (`#hex`/`rgb(`/`hsl(`) over `templates/`/`template-parts/`/`patterns/` → no hits; raw `px/rem/em` literal scan of the 3 new files → none → PASS.
- Gate D: `templateParts` titles = `Kopstuk`/`Voetstuk`/`Seksie-omhulsel` (Afrikaans, sentence case); no English markers, no raw `Lora`/`Inter` family strings, no `text-transform` in new files → PASS.
- No-remote-URL grep on `theme.json`: only `https://schemas.wp.org/trunk/theme.json` ($schema) → PASS.
- No-regression: `git diff --stat` on `header.html`/`footer.html`/`header-main.php`/`footer-main.php`/`styles/dark.json`/`front-page.html` → empty (unchanged). `functions.php` absent (presentation-only preserved).

### Completion Notes List

- **`index.html` is the load-bearing addition.** Before this story the theme shipped only `front-page.html`, so any non-home view had no theme-provided template — the global header/footer did not render site-wide. Adding the mandatory `index.html` fallback + a generic `page.html` (both header → token-driven constrained `main` with `core/post-title`/`core/post-content` → footer) makes the chrome render across the site, satisfying the epics AC. Structural plumbing, no design.
- **Section shell is a structural container, deliberately distinct from 1.5/1.9.** `template-parts/section.html` is a full-width constrained `section` group with token vertical rhythm (`s-48`/`s-24`) and an `alignwide` inner region holding a single muted Afrikaans placeholder prompt ("Voeg blokke by om hierdie seksie te bou."). It carries **no finished design** — the hero/featured-grid/archive-intro/CTA-band/profile-summary patterns + card/button/emphasis variants are the **Story 1.5** library; the documented page archetypes A–D are **Story 1.9**. Registered `area:"uncategorized"` so it is general-purpose-insertable (header/footer areas stay reserved for the chrome).
- **Gate D titles fixed.** The two `templateParts` titles were English (`"Header"`/`"Footer"`) — a standing Gate D defect. Renamed to `Kopstuk`/`Voetstuk` (sentence case); added `Seksie-omhulsel` for the shell. `area`/`name` left unchanged (load-bearing for part resolution).
- **Curated Afrikaans untouched.** The nav labels and footer copy in the existing header/footer parts/patterns are already correct per `ui-copy-translations.md`; they were verified unchanged, not re-translated. The only new user-facing strings are the three Afrikaans titles + the placeholder prompt.
- **Additive, token-only, presentation-only.** The 1.4 `theme.json` change is exclusively the `templateParts` block. No `functions.php`, no PHP, no business logic — template/part discovery is automatic; `ink-core` is Story 1.7. The 1.1 tokens, 1.2 typography, and 1.3 dark variation are untouched by this story.
- **Working-tree note (same as 1.3):** `git diff` of `theme.json` against HEAD (`c5821d8`) shows the 1.2 `fontFace`/heading-sizes/`core/button` hunks because **Story 1.2 is committed-pending (`review`), its work still uncommitted in the tree** — exactly the situation Story 1.3 documented. Those hunks are **not** edits from Story 1.4; my 1.4 edit is solely the `templateParts` hunk.
- **Directory naming:** kept `template-parts/` (a WordPress-valid part directory name, alongside `parts/`) rather than the architecture diagram's `parts/`, to avoid regressing the existing 1.1–1.3 references. Benign naming variance, documented in Dev Notes; part resolution is unaffected.
- **No test/lint harness at repo root yet** (Story 1.11) — the block theme is covered by Gate A/D static audits + future E2E/visual checks per project-context, not PHP unit tests. No existing tests to run or regress.
- **AC-6 live Site-Editor check deferred to Story 1.11** (no running WP env in the repo), consistent with the 1.1/1.2/1.3 precedent. All static verification (JSON validity, templateParts↔files, template↔parts resolution, Gate A, Gate D, no-CDN, no-regression) passed.

### File List

- `wp-content/themes/ink-foundation/templates/index.html` (new) — mandatory block-theme fallback template: header part → token-driven constrained `main` (`core/post-title` 3xl + `core/post-content`) → footer part.
- `wp-content/themes/ink-foundation/templates/page.html` (new) — generic WordPress Page template: same header → `main` → footer chrome.
- `wp-content/themes/ink-foundation/template-parts/section.html` (new) — structural reusable section shell: full-width constrained `section` group, token vertical rhythm, `alignwide` inner region, single muted Afrikaans placeholder prompt.
- `wp-content/themes/ink-foundation/theme.json` (modified) — `templateParts`: titles `"Header"`→`"Kopstuk"`, `"Footer"`→`"Voetstuk"`; added `{ area:"uncategorized", name:"section", title:"Seksie-omhulsel" }`. No other 1.4 change.

## Change Log

| Date | Change |
|---|---|
| 2026-06-20 | Story created (context-engineered) — global templates & template parts: add mandatory `index.html` + generic `page.html`, create structural `section` shell part, register it in `theme.json` and fix the two English part titles to Afrikaans sentence case (`Kopstuk`/`Voetstuk`/`Seksie-omhulsel`); token-only, presentation-only, additive (no 1.1/1.2/1.3 regression; no 1.5 patterns / 1.9 archetypes / 1.6 locking). Status → ready-for-dev. |
| 2026-06-20 | Implemented global templates & template parts (Tasks 1–6): added `templates/index.html` + `templates/page.html` (header → token `main` post-title/post-content → footer); created `template-parts/section.html` structural shell; fixed `theme.json` `templateParts` titles to Afrikaans sentence case + registered `section`. Static verification passed (JSON valid; templateParts↔files PASS; 6 template-part refs resolve; Gate A clean; Gate D clean; no CDN; no regression — header/footer/patterns/dark.json/front-page unchanged, no functions.php). Live Site-Editor check deferred to 1.11. Status → review. |

## Review Findings

Code review (2026-06-21): PASS — 1.4's own deliverables satisfy AC1–AC6; 0 decision, 0 patch, 1 defer, 3 dismissed. No layers failed. Blind Hunter: new markup grammar valid, all template-part refs resolve, Gate A/JSON clean. Edge Case Hunter: no unhandled edges in the 1.4 files. Acceptance Auditor: ACs met; the only discrepancy is cross-story working-tree overlap from Story 1.6 (block locking), documented below and out of 1.4's scope.

- [x] [Review][Defer] Stale "git diff --stat empty" verification claims for front-page/header-main/footer-main [_bmad-output/implementation-artifacts/1-4-global-templates-template-parts.md:51] — deferred, pre-existing. Task 5/6 and the Debug Log assert `front-page.html`, `header-main.php`, `footer-main.php` are unchanged (`git diff --stat` empty), but they now show diffs. Those deltas are exclusively `lock:{move,remove}` / `templateLock:"contentOnly"` additions owned by **Story 1.6 (block locking, status `review`)**, whose File List explicitly names all six files. Same class of cross-story working-tree overlap that 1.4 already documents for the uncommitted Story 1.2 `theme.json` hunks. Documentation accuracy only; no 1.4 code defect. Resolves when 1.2/1.4/1.6 are committed and the tree is reconciled.

### Review discrepancies (informational, not actionable for 1.4)
- **Block-locking attributes present in 1.4-owned files** — `templates/index.html`, `templates/page.html`, `template-parts/section.html` (the three NEW 1.4 deliverables) plus the modified `front-page.html` / `header-main.php` / `footer-main.php` all carry `lock`/`templateLock`. 1.4's Guardrails say "do not add `lock`/`templateLock` here (Story 1.6)." Verified these attributes are **Story 1.6's deliverable** (1.6 File List lines 204–209 name exactly these files; 1.6 was implemented 2026-06-20 after 1.4, editing 1.4's working-tree files). Dismissed as a 1.4 finding: not 1.4's contribution, analogous to the documented 1.2 `theme.json` overlap. Flagged so the 1.6 reviewer owns it.
- **`section.html` renders `<section>` with no accessible name** (no heading / `aria-label`) — LOW a11y nit; dismissed: intentional empty structural placeholder shell; finished content/headings arrive with the Story 1.5 pattern library.
- **`index.html` and `page.html` are byte-identical** — acceptable; both are the minimal header → `main` → footer fallback per AC2. Dismissed.
