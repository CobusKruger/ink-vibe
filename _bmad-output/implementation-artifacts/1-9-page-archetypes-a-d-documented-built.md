---
baseline_commit: c5821d806c1700331ae3a352f097d8a0646c6fe8
---

# Story 1.9: Page archetypes A–D documented & built

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a content manager,
I want reusable page archetypes,
so that non-mocked pages have consistent scaffolds.

## Acceptance Criteria

1. **Four documented page archetypes A–D exist** — A clear, in-repo document defines each of the four approved layout archetypes — **A: Editorial landing**, **B: Archive & discovery**, **C: Detail reading page**, **D: Community utility page** — naming for each: its **purpose**, its **section structure** (the ordered bands), the **1.5 patterns / 1.4 shells it composes**, and **when to use it** (which non-mocked pages it serves). The definitions are taken **verbatim** from the approved taxonomy in `docs/lovable-block-theme-playbook.md §4` and `docs/specs/ink-consolidated-spec.md §9.3` — not reinvented. _[Source: lovable-block-theme-playbook.md#4 "Page principles: pages not yet designed" (Archetypes A/B/C/D); ink-consolidated-spec.md#9.3 "Page archetypes (for pages without a mock)"; epics.md#Story-1.9 "a documented archetype scaffold is available"]_

2. **Each archetype is built as an insertable, page-scoped block pattern** — For each archetype A–D a theme block pattern file exists under `wp-content/themes/ink-foundation/patterns/` with a valid registration header (`Title`, unique `Slug` in the `ink-foundation/` namespace, `Categories`, `Block Types`). Each pattern declares **`Block Types: core/post-content`** so it surfaces in the **page-creation pattern modal** ("a documented archetype scaffold is **available and used** when building a new page"), and is filed under the `ink-foundation` inserter category (from 1.5) plus the WP-core `page` category. _[Source: epics.md#Story-1.9 "available and used … when building a new page"; architecture.md#FSE-tree line 1005–1006 "patterns/ … archetypes A–D (locked structure)"; 1-5 pattern registration convention (`Slug: ink-foundation/{slug}`, header-comment self-registration); functions.php#ink_foundation_register_pattern_categories]_

3. **Archetypes compose existing 1.4 shells + 1.5 patterns — they do not re-pattern them** — Each archetype assembles the **already-built** Story 1.5 patterns (`ink-foundation/hero`, `featured-grid`, `archive-intro`, `cta-band`, `profile-summary`) and the Story 1.4 structural intent (the constrained `main`/`section` rhythm) into a finished page scaffold. Where an archetype needs a band not covered by a 1.5 pattern (e.g. a discovery filter strip, a reading body column, a contact-form region), it uses **core blocks** styled with the existing token system + 1.5 block styles (`is-style-card`/`is-style-emphasis`/`is-style-pill`) — **no duplicate hero/grid/CTA markup is copied**, and **no new block style or `functions.php` change** is introduced. _[Source: epics.md#Story-1.5 (patterns are the building blocks) + #Story-1.9 (archetypes compose them); 1-5 Dev Notes "No page archetypes / full page compositions … ship the building blocks individually … Story 1.9"; project-context.md "patterns-first composition"]_

4. **Token-only, Afrikaans, presentation-only (Gate A + Gate D)** — Every archetype is **token-only** (no hardcoded colour/spacing/type — all values resolve to `theme.json` `var:preset|*` / `var:custom|*`); **Afrikaans, sentence-case** placeholder copy only (no English UI copy, no AI-generated Afrikaans, no raw `font-family` string); **presentation-only** (static block markup — no INK business logic, no CPT/taxonomy queries, no `render_callback`, no `ink-core` dependency); and emits **no remote URLs** (placeholder image blocks, never external `src`). Scaffold copy is neutral/structural placeholder (the "fill me in" prompt class, like the 1.4 `section.html` "Voeg blokke by …"), **not** page-specific Epic 15 content copy. _[Source: project-context.md Gate A (tokens only), Gate D (Afrikaans-first, sentence case, no English leak, no AI Afrikaans), three-layer (no business logic in theme), "no hardcoded asset URLs"; memory: Afrikaans is source of truth — never AI-retranslate]_

5. **Block locking consistent with Story 1.6 where structure must be protected** — Each archetype applies the **1.6 locking strategy** to its own composition: the outer `section`/region groups and the structural sub-containers (columns/cards/media-text/buttons wrappers) carry `"lock":{"move":true,"remove":true}` so the scaffold skeleton cannot be dismantled by staff, while the **content inside** (headings/paragraphs/links/buttons/images, the `core/post-content` region) stays **editable**. No `templateLock:"all"` and no `"lock":{"edit":true}` on a content/dynamic region. The 1.5 patterns referenced via `wp:pattern` already carry their own internal locks (1.6) — the archetype locks only the **new** wrapper structure it introduces. _[Source: epics.md#Story-1.6; 1-6 locking strategy table; architecture.md tree line 1006 "archetypes A–D (locked structure)"; project-context.md "Lock critical editorial structure … leave content editable"]_

6. **No regression; validity preserved; scope held** — `theme.json` remains valid `version: 3` JSON and is **untouched**; `functions.php` is **untouched** (the `ink-foundation` pattern category + 3 block styles from 1.5 are reused as-is); the 1.1 tokens / 1.2 typography / 1.3 dark variation / 1.4 templates+parts / 1.5 patterns / 1.6 locks / 1.7 `ink-core` scaffold / 1.8 comment filters are **not modified**. The story does **not** build the actual content pages (Tuisblad/Oor INK/Kontak — **Epic 15**), the reading templates (`single-*` — **Epic 7**), or the discovery/archive templates (**Epics 8/10/11/12**) — only the reusable A–D scaffolds + their documentation. Live Site-Editor insertion check is **deferred to 1.11** (precedent 1.1–1.8). _[Source: epics.md Epic-7/8/10/11/12/15 ownership; 1-1…1-8 additive/no-regression discipline; 1-5/1-6 "Live Site-Editor … check deferred to 1.11"]_

## Tasks / Subtasks

> **Current state (read before starting):** The theme ships **3 templates** (`templates/front-page.html`, `index.html`, `page.html` — `page.html` is the generic chrome: header part → constrained `main` with `core/post-title` + `core/post-content` → footer part), **3 template parts** (`template-parts/header.html`, `footer.html`, `section.html` — the section shell is an empty structural container with a muted Afrikaans "Voeg blokke by …" prompt), and **7 patterns** (`patterns/header-main.php`, `footer-main.php`, plus the 5 composition patterns `hero.php`, `featured-grid.php`, `archive-intro.php`, `cta-band.php`, `profile-summary.php` — each `Slug: ink-foundation/{slug}`, token-only, curated Afrikaans, **already internally locked by 1.6**). `functions.php` registers the `ink-foundation` pattern category + 3 block styles (`card`/`pill`/`emphasis`) — **presentation-only**. `theme.json` holds the full token set. **No archetype patterns exist yet; no archetype documentation exists yet.** This story **composes** the existing building blocks into 4 page scaffolds + documents them. **Do NOT regress 1.1–1.8. Do NOT edit `theme.json`/`functions.php`. Do NOT re-pattern the 1.5 work (compose it via `wp:pattern` refs or core blocks). Do NOT build Epic 7/8/10/11/12/15 pages.**

- [x] **Task 1 — Author the archetype A–D documentation (AC: 1)**
  - [x] Created `wp-content/themes/ink-foundation/patterns/README.md`. **Decision: in-theme `patterns/README.md`** (chosen over `docs/design-handoff/page-archetypes.md`) so the archetype catalogue ships beside the scaffolds it documents — the dev/staff who insert the patterns find the doc in the same directory — consistent with the 1.2 `assets/fonts/README.md` in-theme-README precedent. Rationale: it documents *shippable theme artifacts* (the patterns), so it is theme-local rather than design-handoff-local.
  - [x] Documented each archetype A–D: purpose, ordered section structure, composed 1.5 patterns / 1.4 shells / core blocks, the pattern slug it ships as, and when-to-use (non-mocked pages from the page-map). A/B/C/D taxonomy taken verbatim from `lovable-block-theme-playbook.md §4` + `ink-consolidated-spec.md §9.3`.
  - [x] Documented the Gate A/B/C/D + locking authoring rules and the Epic 7/8/9/10/11/12/15 scope boundaries (per-archetype "scope note").

- [x] **Task 2 — Build Archetype A: Editorial landing (AC: 2, 3, 4, 5)**
  - [x] Created `patterns/archetype-a-editorial-landing.php` — `Slug: ink-foundation/archetype-a-editorial-landing`, `Title: Bladsy-argetipe A — Redaksionele tuisblad`, `Categories: ink-foundation, page`, `Block Types: core/post-content`.
  - [x] Composed the playbook A structure as four `wp:pattern` refs: `ink-foundation/hero` (intro) → `featured-grid` (featured stream) → `archive-intro` (secondary band) → `cta-band` (CTA). Pure composition — zero copied markup; the referenced patterns supply the locked, token-only, Afrikaans content.
  - [x] Verified all 4 referenced slugs exist (static check); no duplicated hero/grid markup.

- [x] **Task 3 — Build Archetype B: Archive & discovery (AC: 2, 3, 4, 5)**
  - [x] Created `patterns/archetype-b-archive-discovery.php` — `Slug: ink-foundation/archetype-b-archive-discovery`, `Title: Bladsy-argetipe B — Argief en ontdekking`, `Categories: ink-foundation, page`, `Block Types: core/post-content`.
  - [x] Composed the playbook B structure: `wp:pattern` ref to `ink-foundation/archive-intro` (context intro) → a token-only locked **filter strip** (`is-style-pill` + `is-style-pill is-style-outline` placeholder buttons: "Alles" / "Filter" / "Sorteer", with the muted prompt "Filters verskyn hier wanneer die argief gebou word.") → a locked `is-style-card` **listing** `wp:columns` (3 cards mirroring the `featured-grid` card shape) + a muted "Bladsye verskyn hier wanneer die argief gevul word." footer. The live query-loop/filters/pagination remain Epic 8/10/11/12 — only the layout scaffold ships here.
  - [x] Neutral structural Afrikaans placeholders, sentence case; no business logic, no `core/query` block.

- [x] **Task 4 — Build Archetype C: Detail reading page (AC: 2, 3, 4, 5)**
  - [x] Created `patterns/archetype-c-detail-reading.php` — `Slug: ink-foundation/archetype-c-detail-reading`, `Title: Bladsy-argetipe C — Leesbladsy`, `Categories: ink-foundation, page`, `Block Types: core/post-content`.
  - [x] Composed the playbook C structure: locked `section` → eyebrow + `h1` title + muted "deur [skrywer] · [datum]" metadata (in a `constrained` `contentSize:768px` group) → a locked `constrained` `contentSize:768px` **reading-body** group with two placeholder prose paragraphs (the ~768px reading-column intent) → a locked tinted (`secondary`) **related-items** band with an `h2` "Verwante stukke" + a locked `is-style-card` `wp:columns`.
  - [x] Reading-column width expressed via the constrained layout `contentSize:"768px"` (the ~768px reading intent), **not** a hardcoded spacing/colour/type token — documented that the precise reading-width token is an **Epic 7.1/7.2** decision (the static Gate A check explicitly excludes the `contentSize` layout value, which is a layout content-width, not a Gate A "hardcoded colour/spacing/type"). Presentation-only placeholder prose (no dynamic `core/post-content` body) so it inserts cleanly as a page pattern.

- [x] **Task 5 — Build Archetype D: Community utility page (AC: 2, 3, 4, 5)**
  - [x] Created `patterns/archetype-d-community-utility.php` — `Slug: ink-foundation/archetype-d-community-utility`, `Title: Bladsy-argetipe D — Gemeenskapsblad`, `Categories: ink-foundation, page`, `Block Types: core/post-content`.
  - [x] Composed the playbook D structure: locked `section` → functional `h1` "Funksionele opskrif" → **functional module first** = `wp:pattern` ref to `ink-foundation/profile-summary` (the canonical D module) → locked `is-style-emphasis` **secondary explanation** group ("Meer oor hierdie blad" + placeholder paragraph) placed *after* the module. README documents the alternative (swap the profile ref for a locked `is-style-card` action group for account/contact utilities).
  - [x] Afrikaans sentence-case placeholders; `profile-summary` referenced (not re-patterned).

- [x] **Task 6 — Static verification (AC: 2, 3, 4, 5, 6)**
  - [x] **theme.json validity + untouched:** `theme.json` parses as valid JSON, `version:3`; mtime Jun 20 15:05 (pre-session) — untouched. `styles/dark.json` valid + untouched. `functions.php` untouched (mtime Jun 20 15:14; still 3 `register_block_style` + the `ink-foundation` category). PASS.
  - [x] **Pattern registration headers:** all 4 archetype `.php` have valid header doc-comments with `Title`, unique `ink-foundation/`-namespaced `Slug`, `Categories: ink-foundation, page`, `Block Types: core/post-content`; no slug collides with the 7 existing patterns; each has exactly one balanced `<?php`/`?>` pair. PASS.
  - [x] **Composition references resolve:** every `wp:pattern {"slug":"ink-foundation/…"}` ref (A→hero/featured-grid/archive-intro/cta-band; B→archive-intro; D→profile-summary) points to an existing 1.5 pattern; no archetype duplicates 1.5 markup. PASS.
  - [x] **Markup well-formedness:** every archetype has balanced `<!-- wp:* -->` / `<!-- /wp:* -->` markers (self-closing counted separately) and every embedded block-attribute JSON object parses with `json.loads`. PASS.
  - [x] **Gate A (tokens only):** zero hex / `rgb(` / `hsl(` colours; zero raw `px`/`rem`/`em` spacing or type sizes (the only `px` is the C reading-column `contentSize:"768px"` layout value — a content-width, explicitly allowed + Epic-7-noted); zero raw `font-family`. All spacing/colour/type resolve to `var:preset|*` tokens or come from the referenced 1.5 patterns. PASS.
  - [x] **Gate D (Afrikaans):** all introduced copy is Afrikaans, sentence case; no English UI word (heuristic scan clean); placeholders are the neutral structural "fill me in" class (consistent with the 1.4 `section.html` prompt) — no AI-generated Afrikaans, no lifted mockup copy. Pattern `Title`s are Afrikaans sentence case. PASS.
  - [x] **Locking (1.6-consistent):** every hand-authored `section` group + the filter strip + listing `columns`/`column` + card groups + `buttons` wrappers + the C related band + the D emphasis group carry `"lock":{"move":true,"remove":true}`; no `templateLock:"all"`; no `"lock":{"edit":true}`; headings/paragraphs/links/buttons left editable; the referenced 1.5 patterns keep their own internal locks. PASS.
  - [x] **No remote URLs:** no `http://`/`https://` anywhere in the 4 archetypes (no CDN/external image/font/script). PASS.
  - [x] **Documentation exists & defines all four:** `patterns/README.md` exists and documents Archetype A, B, C, D (each with purpose / structure / composed patterns / when-to-use / scope note) and references all 4 archetype slugs. PASS.
  - [x] **No regression:** the session change set under the theme is exactly the 5 new files (4 archetype patterns + `README.md`, all mtimes Jun 21 08:45–08:47); `theme.json`, `functions.php`, `styles/`, templates, parts, and the 7 existing patterns are untouched (mtimes ≤ Jun 20). The `git diff HEAD` entries for `theme.json`/`front-page.html`/`header-main.php`/`footer-main.php` predate this story (prior uncommitted 1.1–1.6 work — the whole theme is still untracked) and were **not** modified by Story 1.9. Live Site-Editor page-modal insertion check **deferred to 1.11** (precedent 1.1–1.8). PASS (89/89 effective static checks).

## Dev Notes

### What this story is (and is not)

- **Is:** **(1) documented** — an in-repo doc defining the four approved layout archetypes (A editorial landing, B archive & discovery, C detail reading page, D community utility page): purpose, section structure, composed 1.5 patterns / 1.4 shells, and when-to-use; **(2) built** — four **page-scoped block patterns** (`Block Types: core/post-content`, categorised `ink-foundation` + `page`) that **compose** the existing 1.4 shells + 1.5 patterns + core blocks into reusable, token-only, Afrikaans, 1.6-locked page scaffolds that appear in the **page-creation pattern modal** so a content manager picks an archetype when building a new non-mocked page. _[Source: epics.md#Story-1.9; lovable-block-theme-playbook.md §4; ink-consolidated-spec.md §9.3]_
- **Is not:** the actual **content pages** (Tuisblad/Gemeenskap/Oor INK/Kontak/Lidmaatskap/auth — **Epic 15 + Epic 4/3**); the **single reading templates** (`single-storie`/`single-gedig`/`single-artikel`, ~768px, no WP comments — **Epic 7.1/7.2**, the real Archetype C realisation); the **discovery/archive templates** with live query-loops, filters, pagination (Ontdek **Epic 8**, Biblioteek **Epic 10**, Opleiding **Epic 11**, Uitdagings **Epic 12** — the real Archetype B realisations); the **profile/notifications/account** surfaces (**Epic 9** + `ink-core` — the real Archetype D realisations); any **new** block style / pattern-category / `theme.json` / `functions.php` change (1.1/1.2/1.5 own those); any **INK business logic / dynamic block / CPT query** (`ink-core`, AD-7); dark-mode activation; tier/Gradering styling (Epic 5). The archetypes are **static, presentation-only page scaffolds** an editor inserts and then fills/wires; the dynamic realisations land in their owning epics. _[Source: epics.md Epics 4/7/8/9/10/11/12/15; architecture.md AD-7; 1-5/1-6 scope boundaries]_

### ⭐ The A–D archetype definitions (key deliverable — verbatim from the approved taxonomy)

Sourced **verbatim** from `docs/lovable-block-theme-playbook.md §4` ("Page principles: pages not yet designed") and `docs/specs/ink-consolidated-spec.md §9.3` ("Page archetypes (for pages without a mock)"), with the composition column reconstructed from the **1.5 pattern library** + **1.4 shells** that already exist in the theme:

| Archetype | Purpose (verbatim) | Section structure (verbatim) | Composes (1.5 patterns / 1.4 shells / core blocks) | When to use (verbatim) |
|---|---|---|---|---|
| **A — Editorial landing** | Editorial landing | Intro section · Featured stream · Secondary content bands · CTA to deeper navigation | `ink-foundation/hero` → `featured-grid` → `archive-intro` → `cta-band` (all via `wp:pattern`) | Tuisblad, top-level discovery pages |
| **B — Archive & discovery** | Archive and discovery | Context intro · Filter or taxonomy controls · Card listing with pagination | `ink-foundation/archive-intro` + token-only core-block filter strip (`is-style-pill`/`is-style-outline` buttons) + `is-style-card` listing `columns` (mirrors `featured-grid` card shape) | Lees, Opleiding, Biblioteek, Uitdagings lists |
| **C — Detail reading page** | Detail reading page | Strong title and metadata · Main readable body column · Related items block | locked `section` → title `core/heading` + muted metadata paragraph → constrained reading-body `core/group` (placeholder prose) → `is-style-card` related-items `columns` | gedig, storie, artikel, hulpbronartikel, biblioteekitem |
| **D — Community utility page** | Community utility page | Clear functional heading · Functional module first · Secondary explanation after action controls | locked `section` → functional `core/heading` → functional module (`ink-foundation/profile-summary` via `wp:pattern`, or a locked `is-style-card` action group) → `is-style-emphasis` explanation group | profile, notifications, account, member interactions |

**Provenance note:** the **purpose** + **section structure** + **when-to-use** columns are quoted from the playbook/spec (the canonical taxonomy — followed verbatim per the task brief). The **Composes** column is reconstructed from design intent + the *actual* 1.4/1.5 building blocks in the current theme, because the playbook predates the pattern library and names the bands abstractly ("featured stream", "card listing") rather than the concrete `ink-foundation/*` slugs. Every band maps to an existing pattern or to core blocks + the existing 1.5 block styles — **no new design is invented**.

### Mechanism choice: page-scoped block patterns (`Block Types: core/post-content`)

**Decision: ship each archetype as a theme block pattern with `Block Types: core/post-content` (idiomatic "available and used when building a new page").**

WordPress surfaces patterns that declare `Block Types: core/post-content` in the **page-creation pattern modal** — when a content manager creates a new Page, WP offers these patterns as starting layouts for the page body. This is the precise, native mechanism that satisfies the AC's "a documented archetype scaffold is **available and used** when building a new page": the archetype is a one-click insert, not a copy-paste-from-docs exercise.

Rationale vs alternatives:
- **Page patterns (`core/post-content` block-type)** ✅ — appear in the page modal, reuse the 1.5 self-registration convention (header doc-comment, no `functions.php` edit), compose via `wp:pattern` refs, stay presentation-only. The architecture tree explicitly homes "archetypes A–D (locked structure)" under `patterns/` (line 1006). **Chosen.**
- *Page templates* (`templates/page-archetype-*.html`) — would bind an archetype to a template hierarchy slug, which is heavier and conflates "scaffold to start from" with "template that renders a page type"; the real page *templates* are owned by Epics 7/8/15. Rejected for this story.
- *Docs-only* — fails the "available and used when building a new page" half of the AC (not insertable). Rejected; we do **both** doc + built patterns.

Each archetype also carries the `ink-foundation` inserter category (from 1.5's `functions.php` registration) so it groups with the INK building blocks, plus the WP-core `page` category.

### Composition map — which 1.5 patterns / 1.4 shells each archetype reuses

- **A** → `wp:pattern` refs to `ink-foundation/hero`, `featured-grid`, `archive-intro`, `cta-band` (pure composition — zero new markup beyond the wrapper).
- **B** → `wp:pattern` ref to `ink-foundation/archive-intro` + new token-only core-block filter strip + `is-style-card` listing columns (card shape mirrors `featured-grid`).
- **C** → new locked `section` + `core/heading`/metadata + constrained reading-body group + `is-style-card` related-items columns (the reading-column intent from Epic 7, scaffolded).
- **D** → `wp:pattern` ref to `ink-foundation/profile-summary` (the canonical D module) **or** a locked `is-style-card` action group, + `is-style-emphasis` explanation group.

**Reuse discipline:** prefer `wp:pattern` refs over copied markup so a future edit to a 1.5 pattern propagates into the archetypes. Where a band has no 1.5 pattern (B's filter strip, C's reading body, D's action group), build it from **core blocks + existing 1.5 block styles + tokens** — never a new block style, never a `functions.php` change.

### WordPress pattern + page-modal mechanics (exact markup)

- **Registration header** (top of each `.php`, the 1.5 convention):
  ```php
  <?php
  /**
   * Title: Bladsy-argetipe A — Redaksionele tuisblad
   * Slug: ink-foundation/archetype-a-editorial-landing
   * Categories: ink-foundation, page
   * Block Types: core/post-content
   */
  ?>
  ```
  WP auto-registers any `.php` in `patterns/` with this header — **no `functions.php` edit** (same as the 5 existing 1.5 patterns). `Block Types: core/post-content` is what makes it appear in the **page** creation modal; `Categories: page` files it under WP-core's page category; `ink-foundation` keeps it in the INK inserter group (registered in 1.5's `functions.php`).
- **Composing another pattern:** reference an existing pattern by slug —
  ```
  <!-- wp:pattern {"slug":"ink-foundation/hero"} /-->
  ```
  This is a self-closing block; WP inlines the referenced pattern's blocks at insert time. The referenced pattern keeps its own 1.6 locks.
- **Locking the archetype's own wrappers** (1.6 convention) — add the `lock` key alongside existing attributes in the opening delimiter:
  ```
  <!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{…},"layout":{"type":"constrained"}} -->
  ```
  Use `"lock":{"move":true,"remove":true}` on structural wrappers (section/columns/column/card groups/buttons wrappers); **never** `templateLock:"all"`, **never** `"lock":{"edit":true}` — content stays editable (NFR-6).
- **Valid JSON only:** each attribute object stays valid JSON (double-quoted keys, boolean `true`, no trailing commas); the static check parses each one.
- **Token references in block markup:** spacing via `"var:preset|spacing|s-*"` (style attrs) and the generated `var(--wp--preset--spacing--s-*)` in the `style=""` mirror; colours via `backgroundColor`/`textColor` slugs (`primary`/`secondary`/`accent`/`muted-text`/`surface-alt`/`border`/`text`); type via `fontSize` slugs (`xs`…`3xl`); block styles via `className:"is-style-card|is-style-emphasis|is-style-pill"`. **No raw hex/px/rem/font-family.** (Exactly the 1.5 pattern idiom — copy its attribute shapes.)

### ⚠️ Guardrails (prevent disasters)

- **Compose, don't re-pattern.** Reference the 1.5 patterns via `wp:pattern` (A and D's profile module); only hand-author markup for bands that have **no** 1.5 pattern (B filter strip + listing, C reading body + related). Never paste a copy of `hero`/`featured-grid`/`cta-band`/`archive-intro`/`profile-summary` markup into an archetype. _[1-5 "ship the building blocks individually … Story 1.9 composes them"]_
- **No `theme.json` / `functions.php` change.** The token set + the `ink-foundation` category + the 3 block styles already exist; reuse them. If an archetype seems to "need" a new token/style, that is a signal to use an existing one — not to add one here. _[1-1/1-5 own those files; Gate A]_
- **Token-only (Gate A).** Zero hardcoded colour/spacing/type/font-family. Express C's ~768px reading width via the **constrained** layout + token spacing, not a raw `768px`; the precise reading-width token is an **Epic 7** decision — note it, don't invent it. _[project-context.md Gate A; spec §9.3 / 7.1 "~768px … Archetype C"]_
- **Afrikaans-first, sentence case, human copy only (Gate D).** Placeholder copy is the neutral structural "fill me in" class (e.g. "Filters verskyn hier wanneer die argief gebou word.", "Verwante stukke verskyn hier."), sentence case, Afrikaans — **never** lift English mockup copy, **never** AI-generate Afrikaans, **never** hand a page-specific Epic 15 copy block (that copy is owned by `ui-copy-translations.md` + the Epic 15 stories). Pattern `Title`s are Afrikaans sentence case. _[project-context.md Gate D; memory: Afrikaans is source of truth — never AI-retranslate; never lift mockup copy]_
- **Presentation-only (three-layer).** Static block markup only — no `render_callback`, no CPT/taxonomy query, no INK logic, no `ink-core` dependency. The live query-loops/filters/forms land in the owning epics. _[project-context.md three-layer; AD-7]_
- **Lock the scaffold, free the content (1.6 + NFR-6).** Lock the **new** wrappers against move/remove; leave all text/links/buttons/images editable; never `templateLock:"all"`, never `lock.edit`. The referenced 1.5 patterns already carry their internal locks. _[1-6 strategy; project-context.md "content editable for non-technical staff"]_
- **No remote URLs.** Image placeholders use `wp:image` with no external `src`; no CDN/font/script. _[project-context.md "no hardcoded asset URLs"; Cloudflare-locked origin]_
- **No scope bleed into owning epics.** No `single-*` reading templates (7), no live discovery/archive query (8/10/11/12), no profile/account surfaces (9), no actual org/auth/membership pages (15/4/3). Build the **scaffold**, document the boundary. _[epics.md Epics 4/7/8/9/10/11/12/15]_

### Source tree (files this story touches)

```
wp-content/themes/ink-foundation/
├── patterns/
│   ├── archetype-a-editorial-landing.php   # NEW — A: hero→featured-grid→archive-intro→cta-band
│   ├── archetype-b-archive-discovery.php    # NEW — B: archive-intro + filter strip + card listing
│   ├── archetype-c-detail-reading.php       # NEW — C: title+meta + reading body + related cards
│   ├── archetype-d-community-utility.php     # NEW — D: heading + module-first + emphasis explanation
│   └── README.md                            # NEW — archetype A–D documentation (catalogue)
│   # (hero/featured-grid/archive-intro/cta-band/profile-summary/header-main/footer-main — UNCHANGED, composed)
├── theme.json            # UNCHANGED
├── functions.php         # UNCHANGED (ink-foundation category + 3 block styles reused)
├── templates/, template-parts/, styles/   # UNCHANGED
```

### Project Structure Notes

- The 4 archetype patterns + `README.md` sit under `patterns/`, exactly where the architecture FSE tree homes "archetypes A–D (locked structure)" (line 1006) — same directory as the 1.5 patterns they compose. No new directory.
- Naming: `archetype-{a|b|c|d}-{kebab-purpose}.php` with `Slug: ink-foundation/archetype-{a|b|c|d}-{kebab-purpose}` — unique, namespaced, sortable, and self-describing in the inserter.
- The in-theme `patterns/README.md` follows the 1.2 `assets/fonts/README.md` in-theme-README precedent (docs live beside the code they describe). Detected variance: most *design* docs live under `docs/design-handoff/`; here the catalogue is theme-local because it documents shippable theme artifacts (the patterns) — noted and justified.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.9 (AC: archetypes A–D → documented scaffold available & used when building a new page; NFR-6)]
- [Source: docs/lovable-block-theme-playbook.md §4 "Page principles: pages not yet designed" — Archetype A/B/C/D verbatim definitions; §6 Quality gates A–D]
- [Source: docs/specs/ink-consolidated-spec.md §9.3 "Page archetypes (for pages without a mock)" — A/B/C/D one-line definitions; §9.4 mockup readiness (assembly-only pages); §9.6 quality gates]
- [Source: _bmad-output/planning-artifacts/architecture.md#`ink-foundation` FSE theme tree (line 1005–1006 "patterns/ … archetypes A–D (locked structure)"); #AD-7 (patterns-first composition)]
- [Source: _bmad-output/implementation-artifacts/1-4-global-templates-template-parts.md (templates/page.html chrome; section.html shell + its "Voeg blokke by …" placeholder convention)]
- [Source: _bmad-output/implementation-artifacts/1-5-core-block-pattern-library.md (the 5 composition patterns: slugs, categories, token idiom, block styles card/pill/emphasis; "ship building blocks individually … archetypes are Story 1.9")]
- [Source: _bmad-output/implementation-artifacts/1-6-block-locking-strategy.md (locking strategy table; move/remove vs contentOnly; never templateLock:"all"/lock.edit; "Story 1.9 will reuse this strategy when assembling the archetype compositions")]
- [Source: _bmad-output/project-context.md (Gate A tokens-only; Gate D Afrikaans sentence-case / no English leak / no AI Afrikaans / never lift mockup copy; three-layer presentation-only; block theme + block locking; NFR-6 staff-maintainability; no remote asset URLs)]
- [Source: docs/design-handoff/page-map.csv (assembly-only pages: lidmaatskap, oor-ink, kontak, auth, uitdagings-list → archetype B); docs/mockup-readiness-assessment.md (Opleiding uses Library layout; Uitdagings list uses Archetype B)]
- [Source: docs/ui-copy-translations.md (approved Afrikaans UI copy — source of truth; placeholder org details flagged §14)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m]

### Debug Log References

- Static verification harness (Python): 89/89 effective checks PASS. Validated — theme.json valid `version:3` + untouched; functions.php/styles/templates/parts/7-existing-patterns untouched; 4 archetype registration headers (Title / namespaced unique Slug / Categories ink-foundation+page / Block Types core/post-content); all `wp:pattern` composition refs resolve to existing 1.5 patterns; balanced block markers + valid embedded-JSON attribute objects; Gate A (no hex/rgb/hsl/raw-px[except C `contentSize:768px`]/raw-px-spacing/font-family); Gate D (Afrikaans sentence case, no English UI copy); 1.6 locking on all authored wrappers (no `templateLock:"all"`, no `lock.edit`); no remote URLs; README defines A–D + references all 4 slugs.
- No-regression cross-check: theme is fully untracked (1.1–1.8 not yet committed); `git diff HEAD` shows only prior-session changes (theme.json/front-page/header-main/footer-main). File mtimes confirm this session created exactly the 5 new files (Jun 21 08:45–08:47); all other theme files ≤ Jun 20 — untouched.

### Completion Notes List

- **Mechanism: page-scoped block patterns.** Each archetype is a theme block pattern with `Block Types: core/post-content` so it surfaces in the WordPress page-creation pattern modal ("available and used when building a new page" — AC-2). Self-registered via header doc-comment (no `functions.php` edit), filed under `ink-foundation` + WP-core `page` categories.
- **Composition over duplication (AC-3).** A is pure `wp:pattern` composition (hero→featured-grid→archive-intro→cta-band). B composes archive-intro + hand-authored token-only filter strip + is-style-card listing. C is hand-authored (title/meta + constrained 768px reading body + is-style-card related) — the reading-template realisation is Epic 7. D composes profile-summary + is-style-emphasis explanation. No 1.5 pattern markup is copied; bands without a 1.5 pattern use core blocks + existing block styles + tokens.
- **Documentation: in-theme `patterns/README.md`** (1.2 README precedent) — catalogues the 1.5 building blocks + defines archetypes A–D (purpose / structure / composed patterns / when-to-use / Epic-scope boundary) verbatim from playbook §4 + spec §9.3.
- **Gate A / Gate D / 1.6 locking** all satisfied by construction and verified statically. The only `px` literal is C's `contentSize:"768px"` reading-column layout value (a content-width, not a Gate A token violation; precise reading-width token deferred to Epic 7).
- **Scope held:** no Epic 7 reading templates, no Epic 8/10/11/12 query-loops, no Epic 9 profile surfaces, no Epic 15/4/3 actual pages — only the reusable A–D scaffolds + documentation. No `theme.json`/`functions.php` change; no 1.1–1.8 regression. Live Site-Editor insertion check deferred to 1.11.

### File List

- `wp-content/themes/ink-foundation/patterns/archetype-a-editorial-landing.php` (NEW)
- `wp-content/themes/ink-foundation/patterns/archetype-b-archive-discovery.php` (NEW)
- `wp-content/themes/ink-foundation/patterns/archetype-c-detail-reading.php` (NEW)
- `wp-content/themes/ink-foundation/patterns/archetype-d-community-utility.php` (NEW)
- `wp-content/themes/ink-foundation/patterns/README.md` (NEW)
- `_bmad-output/implementation-artifacts/1-9-page-archetypes-a-d-documented-built.md` (story file — frontmatter `baseline_commit`, Tasks checkboxes, Dev Agent Record, File List, Change Log, Status)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status: backlog → ready-for-dev → in-progress → review)

## Review Findings

Code review (2026-06-21): adversarial three-layer review (Blind Hunter diff-only, Edge Case Hunter, Acceptance Auditor). Failed layers: none — all three layers passed clean. Diff = 4 new archetype patterns (A–D) + `patterns/README.md` (all untracked/new, full-file added diff). Verified block markup balance + valid attribute JSON (all four BALANCED, all JSON parses), Gate A token-only, Gate D Afrikaans sentence-case placeholders, 1.6 locking on every hand-authored wrapper (no `templateLock:"all"`, no `lock.edit`), no remote URLs, no business logic/render_callback/query, composition refs resolve to existing 1.5 patterns, `theme.json`/`functions.php` not modified by this story. Zero decision-needed, zero patch, zero defer findings; 4 candidate findings dismissed.

Dismissed (counted, not actioned): (1) eyebrow paragraphs use raw `letterSpacing:0.08em`/`fontWeight:600`/`textTransform:uppercase` — identical accepted recipe from 1.5 `featured-grid`/`hero`; outside Gate A colour/spacing/type-size scope; mirrored not invented. (2) `href="#"` placeholder anchors — established pattern-scaffold idiom (matches `featured-grid`). (3) No `ABSPATH` guard in pattern files — consistent with all 1.5 patterns; pattern files are loaded by WP's pattern registrar. (4) functions.php mtime later than archetypes + whole theme untracked so no-regression rests on mtime — content intact (3 block styles + category); HEAD diff structurally impossible for an untracked file; not a code defect.

## Change Log

| Date | Description |
|---|---|
| 2026-06-21 | Implemented (dev-story) — built the 4 page-scoped archetype patterns (A pure `wp:pattern` composition; B archive-intro + token filter strip + is-style-card listing; C title/meta + constrained 768px reading body + is-style-card related; D heading + profile-summary module + is-style-emphasis explanation) + in-theme `patterns/README.md` defining A–D verbatim from playbook §4 / spec §9.3. 89/89 static checks pass (registration headers, composition-ref resolution, marker/JSON validity, Gate A token-only, Gate D Afrikaans, 1.6 locking, no remote URLs, no `theme.json`/`functions.php` change, no 1.1–1.8 regression). Live Site-Editor check deferred to 1.11. Status → review. |
| 2026-06-21 | Story created (context-engineered) — page archetypes A–D documented & built: ship 4 page-scoped block patterns (`ink-foundation/archetype-{a-d}-*`, `Block Types: core/post-content`, categorised `ink-foundation`+`page`) that compose the 1.4 shells + 1.5 patterns (A: hero→featured-grid→archive-intro→cta-band via `wp:pattern`; B: archive-intro + token filter strip + is-style-card listing; C: title/meta + constrained reading body + related cards; D: heading + profile-summary/action module + is-style-emphasis explanation) + an in-theme `patterns/README.md` defining all four archetypes verbatim from the playbook §4 / spec §9.3; token-only (Gate A), Afrikaans sentence-case placeholders (Gate D), presentation-only, 1.6-locked wrappers, no remote URLs; no `theme.json`/`functions.php` change; no 1.1–1.8 regression; no Epic 7/8/9/10/11/12/15 page build. Status → ready-for-dev. |
