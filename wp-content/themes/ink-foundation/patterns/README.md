# INK Foundation — bladsy-patrone en argetipes A–D

This README is the **catalogue and documentation** for the `ink-foundation` block patterns, with a dedicated section for the four **page archetypes A–D** (Story 1.9). It ships in-theme, beside the patterns it describes (the same in-theme-README convention as `assets/fonts/README.md`).

All patterns here are **presentation-only**, **token-only** (every value resolves to a `theme.json` `var:preset|*` / `var:custom|*` token — Gate A), **Afrikaans, sentence case** (Gate D), and emit **no remote URLs**. INK business logic, content models, and dynamic queries live in `ink-core` — never in the theme.

---

## Building-block patterns (Story 1.5)

These are the designed building blocks the archetypes compose. They self-register via their header doc-comment (no `functions.php` edit) and carry their own block locking (Story 1.6).

| Slug | Title | Role |
|---|---|---|
| `ink-foundation/hero` | Held-seksie | Page intro / hero band |
| `ink-foundation/featured-grid` | Uitgesoekte rooster | 3-card featured stream |
| `ink-foundation/archive-intro` | Argief-inleiding | Context intro for archive/discovery pages |
| `ink-foundation/cta-band` | Oproep tot aksie | Tinted call-to-action band |
| `ink-foundation/profile-summary` | Profiel-opsomming | Profile card (avatar + bio + follow) |

Block styles (registered in `functions.php`, Story 1.5): `is-style-card` (group), `is-style-emphasis` (group), `is-style-pill` (button).

---

## Page archetypes A–D (Story 1.9)

When a page has **no dedicated Lovable mock**, build it from one of the four approved layout archetypes. Each archetype ships as an **insertable, page-scoped block pattern**: it declares `Block Types: core/post-content`, so it appears in the **page-creation pattern modal** — a content manager picks the archetype when creating a new Page, then fills in the content. Each is filed under the `ink-foundation` inserter category plus the WP-core `page` category.

The A–D taxonomy (purpose, structure, when-to-use) is taken verbatim from `docs/lovable-block-theme-playbook.md §4` and `docs/specs/ink-consolidated-spec.md §9.3`. Each archetype's structure is **composed from the Story 1.4 shells + Story 1.5 patterns + core blocks** — no new design is invented, and the structural wrappers are locked (move/remove) per the Story 1.6 strategy while content stays editable.

### Archetype A — Editorial landing

- **Pattern:** `ink-foundation/archetype-a-editorial-landing` — *Bladsy-argetipe A — Redaksionele tuisblad*
- **Purpose:** Editorial landing.
- **Section structure:** Intro section · Featured stream · Secondary content bands · CTA to deeper navigation.
- **Composes:** `wp:pattern` refs to `ink-foundation/hero` → `featured-grid` → `archive-intro` → `cta-band`. Pure composition — no new markup.
- **When to use:** Tuisblad, top-level discovery pages.
- **Scope note:** The real Tuisblad is **Epic 15.1**; this is the reusable scaffold it starts from.

### Archetype B — Archive & discovery

- **Pattern:** `ink-foundation/archetype-b-archive-discovery` — *Bladsy-argetipe B — Argief en ontdekking*
- **Purpose:** Archive and discovery.
- **Section structure:** Context intro · Filter or taxonomy controls · Card listing with pagination.
- **Composes:** `wp:pattern` ref to `ink-foundation/archive-intro` (context intro) + a token-only locked **filter strip** (`is-style-pill` / `is-style-outline` placeholder buttons) + a locked `is-style-card` **listing** `columns` (mirrors the `featured-grid` card shape).
- **When to use:** Lees/Ontdek, Opleiding, Biblioteek, Uitdagings lists.
- **Scope note:** The live query-loop, faceted filters, and pagination are owned by **Epics 8 (Ontdek), 10 (Biblioteek), 11 (Opleiding), 12 (Uitdagings)** — this scaffold ships the *layout*, not the dynamic query.

### Archetype C — Detail reading page

- **Pattern:** `ink-foundation/archetype-c-detail-reading` — *Bladsy-argetipe C — Leesbladsy*
- **Purpose:** Detail reading page.
- **Section structure:** Strong title and metadata · Main readable body column · Related items block.
- **Composes:** a locked `section` → `core/heading` (title) + muted metadata paragraph → a **constrained reading-body** `core/group` (`contentSize: 768px` reading-column intent) with placeholder prose → a locked `is-style-card` **related-items** `columns`.
- **When to use:** gedig, storie, artikel, hulpbronartikel, biblioteekitem.
- **Scope note:** The **real single reading templates** (`single-storie` / `single-gedig` at ~768px, no WP comments) are **Epic 7.1 / 7.2**. Archetype C is the documented scaffold those templates conform to; the precise reading-width token is finalised in Epic 7 (here the ~768px intent uses the constrained layout `contentSize`).

### Archetype D — Community utility page

- **Pattern:** `ink-foundation/archetype-d-community-utility` — *Bladsy-argetipe D — Gemeenskapsblad*
- **Purpose:** Community utility page.
- **Section structure:** Clear functional heading · Functional module first · Secondary explanation after action controls.
- **Composes:** a locked `section` → functional `core/heading` → **functional module first** (`wp:pattern` ref to `ink-foundation/profile-summary` as the canonical module) → an `is-style-emphasis` **secondary explanation** group *after* the module.
- **When to use:** profile, notifications, account, member interactions.
- **Scope note:** The real profile/notifications/account surfaces are owned by **Epic 9** + `ink-core`; Kontak/auth utility pages by **Epic 15 / Epic 3**. Where the utility is an account/contact action (not a profile), swap the `profile-summary` ref for a locked `is-style-card` action group — keep the "module first, explanation after" order.

---

## Authoring rules (quality gates)

- **Gate A — tokens only.** No hardcoded colour/spacing/type/font-family. Use `var:preset|*` / `var:custom|*` and the block-style classes.
- **Gate B — layout consistency.** Match the approved mock where one exists; otherwise use the archetype above.
- **Gate C — platform fit.** Stable in the Site Editor; insertable as a page pattern.
- **Gate D — language.** Afrikaans, sentence case; no English UI leakage; no AI-generated Afrikaans; never lift the Lovable mockup's English copy. Real page copy comes from `docs/ui-copy-translations.md` and the migrated DB.
- **Locking (Story 1.6).** Lock structural wrappers against move/remove so the scaffold skeleton survives; never `templateLock:"all"`, never `"lock":{"edit":true}` — content stays editable for non-technical staff (NFR-6).
- **Compose, don't re-pattern.** Reference the 1.5 patterns via `wp:pattern`; only hand-author bands that have no 1.5 pattern (B's filter strip + listing, C's reading body + related, D's action group), built from core blocks + the existing block styles.
