# INK — Consolidated Build Specification

> **Status:** Consolidated for spec-framework ingestion · **Date:** 2026-06-14 · **Amended:** 2026-06-20 (Sprint Change Proposal — administrative burden-reduction scope increase: R1–R9 + G1; see `_bmad-output/planning-artifacts/sprint-change-proposal-2026-06-20.md`)
> **Generated from:** [`spec-consolidation-brief.md`](./spec-consolidation-brief.md) — the originating brief and its four scope rules. Validate this deliverable against it.
> **Scope:** The *new* INK website only. Old-site detail is included only where it is a binding constraint on the new build (database reuse, plugin continuity, migration). All retrospective narrative about the old site is out of scope.
> **Source documents synthesised:** `instructions.md`, `initiation.md`, `implementation-options.md`, `site-structure-audit.md`, `migration-plan.md`, `plugin-transition-guide.md`, `afrikaans-terms.md`, `ui-copy-translations.md`, `lovable-block-theme-playbook.md`, `design-handoff-workflow.md`, `mockup-readiness-assessment.md`, `design-handoff/*` (tokens, page-map, agent-brief, repo analysis).

---

## 0. How to use this document

This is the single authoritative specification for the INK rebuild. It is structured so a spec-driven delivery framework (see `framework-recommendation.md` — **BMAD is recommended**) can ingest it as the Product Brief + PRD foundation + Architecture baseline without re-reading the scattered planning corpus.

- **Section 1–4** = product brief (purpose, principles, scope, users).
- **Section 5–8** = architecture baseline (stack, three-layer model, data model, IA).
- **Section 9** = design-system integration (Lovable → block theme).
- **Section 10** = plugin stack and integration points.
- **Section 11–13** = migration constraints, Afrikaans-first, and non-functional requirements.
- **Section 14** = open decisions to resolve before/during build.
- **Section 15** = epic map (build/dependency order) handing off to the feature list.

The companion document **`ink-feature-list.md`** breaks the product into epics and features for story decomposition.

---

## 1. Project purpose

INK is a community publishing platform for Afrikaans writers, poets, and readers. The new site replaces an existing WordPress installation that holds thousands of published contributions, an active paid membership, and established editorial processes.

The rebuild exists to fix four structural problems in the current site while preserving everything of value:

1. **Preservation** — every existing contribution, member account, subscription record, and historical artifact must survive the rebuild.
2. **Afrikaans-first UI** — the public and member-facing interface is entirely Afrikaans. No English word should appear to a visitor or member. (This applies to the running site, not to these planning/development documents.)
3. **Clean architecture** — the current site embeds business rules in theme glue and mismatched plugins. The new site separates presentation, business logic, and platform concerns into distinct layers.
4. **Automation** — replace the manual EFT payment step and spreadsheet-based tier tracking with automated systems. Subscription tracking already runs on WooCommerce Memberships; the missing piece is front-end payment collection via PayFast.

### Product positioning

INK is a **literary publishing platform that fosters a supportive community — not a social network.** Social features exist to give people reasons to return and read the built-up content library, breaking the current pattern where writers visit only three times a month (to see the new challenge, post their work, and check results). Engagement features serve reading, not feed-scrolling.

---

## 2. Guiding principles (project constitution)

These are fixed constraints. Treat any deviation as requiring an explicit decision.

1. **Use plugins for commodity problems; custom code for INK-specific rules; keep the theme for presentation only.** Commodity = payments, SEO, redirects, baseline community primitives. INK-specific = writer tiers, challenge promotion logic, submission workflow, reading engagement, sponsor rotation.
2. **Three-layer separation is non-negotiable** (theme / `ink-core` plugin / vetted platform plugins). No business logic in the theme.
3. **Afrikaans is designed in from the start, not retrofitted.** Content models, journeys, copy, and admin labels are authored in Afrikaans first; English is the developer fallback, not the reverse.
4. **The terminology guide is the source of truth for concepts and labels** (`afrikaans-terms.md`), subject to the reconciliation noted in §14.
5. **Preservation over convenience.** Data continuity (DB, members, subscriptions, content, media) outranks a clean-slate rebuild. This is a **brownfield** project.
6. **Design tokens are canonical.** No hardcoded colours, spacing, or unnamed type sizes in templates; everything maps to `theme.json` tokens.
7. **The Lovable mockup is design intent, not production code.** It informs visual direction; it is not ported or reused as an implementation.
8. **Editorial effort must stay low.** Features that depend on per-item manual editorial linking will be ignored under workload and must instead rely on shared taxonomy or automation.

---

## 3. Scope

### In scope
- A custom WordPress **block theme** deployed to the existing site.
- An **`ink-core`** companion plugin holding all business logic and content models.
- Front-end membership purchase via **PayFast** (closing the manual-EFT gap).
- Custom **front-end submission** workflow.
- Reading engagement: contextual prompts, line highlighting with simple reactions, suggested next reads, structured community responses.
- **Reader ratings & reviews**, **pinned/selected works**, and a **following-feed** (confirmed 2026-06-14 — see §14).
- Structured content models for library, training, challenges, InkPols, sponsors.
- Writer Gradering system (Brons/Silwer/Goud/Meester) as first-class data, with automatic top-3-win promotion (Brons→Silwer→Goud) and manual-only Meester.
- Full migration of existing data and a redirect layer.

### Out of scope
- Any redesign or documentation of the *old* site beyond binding constraints.
- A general e-commerce storefront (WooCommerce is for memberships only).
- A formal LMS (training is a resource hub, not courses/quizzes/certificates).
- Auto-renewing subscriptions at launch (deferred to a later phase).
- InkPols individual-article extraction (issues stay PDF-based).
- Public passage annotation / public inline commentary on works.
- AI-generated Afrikaans translation.

---

## 4. Users and roles

| Role | Afrikaans term | Capability summary |
|---|---|---|
| Visitor | besoeker | Read public writing; no account. |
| Free member | gratis lid | Read, react, comment/respond, access library & training, build a reading list, follow writers. |
| Paid member | betaalde lid | All free-member rights **plus** the right to publish work (submission entitlement). The membership (`lidmaatskap`) concept is uniformly **lidmaatskap**; the prior `intekenaar`/`intekening`/`intekenlid` vocabulary is retired (G1, 2026-06-20). |
| Writer | skrywer | A member who publishes; carries a Gradering (Brons/Silwer/Goud/Meester). |
| Editor / staff | redakteur | WordPress `editor` role: editorial admin, challenge & winner administration, Gradering promotion, sponsor management, moderation. |
| Administrator | administrator | Technical control. |

**Critical rule:** *lidmaatskap status* (active WooCommerce Membership — internal code IDs `membership`/`subscription` may remain) controls **submission entitlement**. *Gradering* (`ink_writer_tier`) is a **separate** concept controlling Brons/Silwer/Goud/Meester competition pools. These must never be conflated in data or code — submission entitlement is never gated on Gradering, and Gradering is never gated on lidmaatskap. A betaalde lid at Brons is not the same as a Brons writer with an expired lidmaatskap.

> **UI vocabulary (G1, 2026-06-20):** user-facing copy uses **lidmaatskap** (membership), **betaalde lid** (paid) / **gratis lid** (free), and **Gradering** (the writer tier; "ster gradering" acceptable as the long form). Never "tier" or "intekening" in the UI. Code IDs may remain English/internal.

### Registration lifecycle (confirmed)
create account → complete profile → prompt first social action after signup. (No reader/writer intent choice at signup; any member can publish once they hold an active lidmaatskap.)

---

## 5. Technology stack

| Concern | Technology | Notes |
|---|---|---|
| CMS / platform | WordPress (existing install, brownfield) | Deployed to the existing site; existing database retained. |
| Presentation | **Custom block theme** (FSE) | `theme.json` design tokens; templates, template-parts, patterns, block styles. |
| Business logic | **`ink-core` custom plugin** | CPTs, taxonomies, user meta, tiers, submission, challenge logic, sponsor rotation, follow graph, reading engagement, REST endpoints, admin tools. |
| Community primitives | BuddyPress (scoped) | Profiles, directory, notifications. Private messaging **deferred — off at launch** (§14.7). Friend Connections **off** (follow replaces it — see §10/§14). |
| Commerce / access | WooCommerce + WooCommerce Memberships | Memberships only; three fixed-term products. |
| Payments | WooCommerce PayFast Gateway | ZAR; South African processor. New: front-end purchase flow. |
| PDF display | Real3D Flipbook | InkPols issues. |
| SEO | **Rank Math** | Adopted from the start — negligible *per-post* Yoast data to preserve (§14.11). Native CPT schema + breadcrumbs in the free tier; fits the CPT-heavy site. Replaces Yoast. |
| Redirects | Redirection | Mandatory migration redirect layer. |
| Security (layered) | Cloudflare (edge + login rule) · staff 2FA · Patchstack (CVE alerts) · host malware scanning | Origin locked to Cloudflare. Loginizer retired; no WordFence. See §14.16. |
| Hosting / caching | NameHero (LiteSpeed Web Server) · LiteSpeed Cache · Cloudflare edge cache | Host behind Cloudflare; origin locked. See §14.9. |
| Translation tooling | Loco Translate | `.po`/`.mo` authoring for surviving plugins — **staging only, not on production** (§14.13); committed `.mo` ships with releases. Human-authored only. |
| Moderation | custom `ink-core` form | Logged report path via a **custom `ink-core` report form** (resolved 2026-06-15, PRD OQ-4 — no third-party Report Content). |

**Design-system source values** (from `design-handoff/tokens/theme-tokens.json`): serif **Lora** (display/heading) + sans **Inter** (body/UI); palette terracotta `#EA4015` (primary), cream `#EDE9E0` (secondary), sage `#4D8066` (accent), highlight `#FFE066`, plus muted gold and dark-mode tokens. Spacing scale 4–96; content width 768px, wide 1400px; radius and shadow scales defined; dark mode tokens present.

---

## 6. Architecture

### 6.1 Three-layer model

| Layer | Responsibility | Implementation |
|---|---|---|
| **Theme** | Presentation: templates, patterns, block styles, editor styles, animation, visual identity. | Custom block theme. |
| **Site logic** | Business rules & content models: CPTs, taxonomies, tier logic, challenge rules, submission permissions, sponsor rotation, InkPols model, follow graph, reading engagement, reviews, REST endpoints, admin tools. | `ink-core` plugin. |
| **Platform** | Auth, commerce, SEO, redirects, search, security, community primitives. | Vetted third-party plugins. |

### 6.2 Custom post types (registered in `ink-core`)

| Code ID | UI label (Afrikaans) | Purpose |
|---|---|---|
| `gedig` | Gedig | Published poems. |
| `storie` | Storie | Short stories / prose. Code ID aligned to the UI term (was `verhaal`); `afrikaans-terms.md` updated accordingly (terminology reconciliation, 2026-06-14). |
| `artikel` | Artikel | Opinion pieces, essays. |
| `skryfwerk` | Skryfwerk | Catch-all bucket for unclassified migrated content. |
| `biblioteek_item` | Biblioteekitem | Curated library content, winning entries. **R4 (stub):** winning entries update the writer's Biblioteek; detail deferred with the broader biblioteek analysis (§9.4). |
| `opleiding_artikel` | Hulpbronartikel | Training / resource content. |
| `uitdaging` | Uitdaging | Monthly challenge (theme, deadline, results). |
| `inkpols_uitgawe` | Uitgawe | InkPols magazine issues (PDF-based). |
| `borg` | Borg | Sponsor content with scheduling fields. |

### 6.3 User meta (prefix `ink_`)

| Meta key | Purpose |
|---|---|
| `ink_writer_tier` | Current Gradering: `brons` / `silwer` / `goud` / `meester`. `meester` is **manual-only** (never auto-promoted; rendered in the brand red-orange `primary #EA4015`, distinct from `danger`). |
| `ink_tier_promoted_at` | Date of most recent promotion. |
| `ink_tier_win_count` | Count of top-3 wins toward the next Gradering; **reset to 0 on promotion** (R3). |

Tier-change history (promotion **or** corrective demotion — any-direction set; PRD FR-12) stored in a second meta key or a custom log table for auditability, with actor / date / reason / from→to tier.

#### 6.3.1 Gradering auto-promotion algorithm [R3, decision 2026-06-20]

The promotion engine lives in `Ink\Tiers` (never reads Entitlement — the conflation rule in §4 holds).

- A **win** = any **top-3 placement** (algehele wenner or wenner) in **any entry type** at the writer's *current* Gradering. Multiple placements each count — including multiple placements within one category in a single round.
- Thresholds: **Brons → Silwer at 5 wins**; **Silwer → Goud at 15 wins**. On promotion, `ink_tier_win_count` resets to **0** and counting begins again at the new Gradering.
- **Meester is manual-only** — never reached by the win counter; it has no threshold. Manual promotion *and* corrective demotion by staff remain available at every level (PRD FR-12).
- Promotion fires a **templated congratulation email** (simple form-letter text with a name-merge greeting — see §10.1 / the Notifications surface).
- The private-profile "wins needed" subtext (e.g. "4 top-3-uitslae nodig om Silwer te bereik") is surfaced on **My Profiel** (private), reusing `ink_tier_win_count`. It is never shown on the public Skrywerprofiel.
- Auto-promotion is triggered as the final step of results ingestion (§6.5 / R2), consuming that round's placement records.

### 6.4 Taxonomies

| Slug | Applies to | Purpose |
|---|---|---|
| `genre` | bydraes (`gedig`/`storie`/`artikel`) | Genre/form classification; **shared** with training for automatic surfacing. |
| `vaardigheid` | `opleiding_artikel` | Skill area — canonical seed terms (per `ui-copy-translations.md`): Begin hier, Skryfkuns, Digkuns, Prosa, Stylfigure, Redigeer en hersien, Stem en styl. (The `afrikaans-terms.md` list is illustrative only.) Shared with bydraes for cross-surfacing. |
| `uitdagingsrondte` | inskrywings & wenwerk | Links entries/winners to a challenge round. |
| `ster_gradering` | where applicable | Gradering as a taxonomy term where needed for query/segmentation. |

**Surfacing rule:** training and contributions share `genre`/`vaardigheid` terms so relevant resources appear automatically — **no per-article manual linking** (it would be ignored under workload).

### 6.5 Social graph — Follow (asymmetric) **[decision: 2026-06-14]**

The site uses a **one-way follow** model (follower/following counts), per the Lovable design, **overriding** the terminology guide's earlier rejection of "follow". Implications:
- BuddyPress **Friend Connections off**; follow is **implemented in `ink-core`** (no clean, maintained native BuddyPress follow; a trivial relationship model gives full control and Afrikaans copy alignment).
- A **following-feed** (new publications by followed writers) is a custom `ink-core` feature, surfaced as the profile "activity" tab.
- `afrikaans-terms.md` reconciled 2026-06-14: follow vocabulary added (`Volg` / `Volg tans` / `volgeling`, plural `volgelinge`), friendship terms replaced, and the follow-avoidance rule dropped. (`ui-copy-translations.md` "Volgers" also corrected to "volgelinge".)
- Migration: existing BuddyPress friendships are **converted** to mutual follow records (each friendship → two follows), per §14.10 — see §11.

### 6.6 Challenge adjudication automation **[Epic 12 extension; R1/R2, 2026-06-20]**

This is the editorial-automation pillar that replaces the owner's manual challenge-results workflow. Build order is the hard chain **R1 → R2 → R3** (the EntryID data-model change is the linchpin and must land first).

- **EntryID (`entry_type` + `entry_number` on the `ink_entries` custom table).** A per-type sequence (Gedigte, Stories, Artikels counted separately) within a challenge round, **assigned at collation time** (not at entry time). Stored so pasted results can be matched back to the originating entry. An Afrikaans UI label ("inskrywingsnommer") is authored only if it is ever member-exposed.
- **Judge-email collation tool (R1):** auto-collates a round's entries into an anonymized judge email, assigning and storing each EntryID at collation.
- **Results ingestion + coverage report (R2):** results and commentary are **pasted as plain text** by the editor (explicitly **NO `.docx` parser** — paste-only, eliminating the PhpWord/untrusted-ZIP/XXE attack surface). The tool matches pasted results against stored EntryIDs and produces a **coverage report** (which entries did/did not receive a result). R2's final step triggers the R3 promotion calc (§6.3.1).
- **Winners-announcement post:** auto-generated from a **simple form-letter template** (name-merge greeting only, not a rich-template engine), placed in a **featured home slot**, containing an entry index with links to the winning works.
- **Moderator feedback** is stored as the custom structured `comment_type = ink_moderator_terugvoer` ("Terugvoer van die moderator"), never an open WP comment — see §7.
- **Winner banner + featured-feed ordering:** placement flags drive a per-rank banner and feed order — **algehele wenner (1st)** is ordered before **wenner (2nd–3rd)**. Pair colour with text/icon (no colour-only rank encoding). Brons/Silwer/Goud colour tokens defined in the design handoff; Meester uses brand `primary #EA4015`.

---

## 7. Reading & engagement model (confirmed decisions)

- **WordPress comments are disabled site-wide** — with **one programmatic exception**: moderator feedback is stored as a custom structured response (`comment_type = ink_moderator_terugvoer`, "Terugvoer van die moderator"), written programmatically via `wp_insert_comment`, **never as an open WP comment**. It is visible on a work only when the writer enables it on **My Profiel** (private). The reading surface otherwise uses a custom **structured community response** system ("Gemeenskapsreaksies") with response types **Lof / Insig / Voorstel** (Praise / Insight / Suggestion).
- **Line highlighting** with simple **reactions** (`reaksie`: hartjie / duim op / wow) — discoverable but unobtrusive. Highlighting is **encouragement, not critique**. **No** public passage annotation.
- **Contextual prompts** after each piece; **suggested next reads** (by tone, form, topic, or tier).
- **Reader ratings & reviews** on writer profiles **[confirmed 2026-06-14]** — aggregate reader rating + written reviews.
- **Reading list** (`leeslys`) — members save works to revisit.
- **Reactions** are a lightweight signal, not the centrepiece engagement mechanic.

---

## 8. Information architecture

Top-level navigation (Afrikaans):

| Nav label | Purpose | Primary content |
|---|---|---|
| **Tuisblad** | Clean editorial homepage; a few featured streams. | Hero spotlight, challenge section, featured works, sponsors, CTA. |
| **Ontdek** | The reading/discovery hub: browse, filter, sort, search published writing and writers (absorbs the former separate "Lees" section). | Tabs: **bydraes** (`gedig`/`storie`/`artikel` — filter by type, sort, date/archive browse) + **skrywers** (genre filter, sort: Meeste gelees, Nuwe stemme). Single-piece reading happens on the detail pages (Archetype C). |
| **Opleiding** | Structured resource hub for writing craft. | `opleiding_artikel` with `vaardigheid` taxonomy + faceted search. |
| **Biblioteek** | Curated collection: winners, reference, document-style resources. | `biblioteek_item` with date browsing, pagination, author filter. |
| **Uitdagings** | Monthly challenges: rules, results, per-tier placements (1st–3rd). | `uitdaging` list + single. |
| **Gemeenskap** | Visitor conversion / marketing page (community features live on profiles). | Value props, principles, how-it-works, CTAs. |
| **Lidmaatskap** | Registration, plans, benefits, automated payment, renewal. Terms are 1mo / 6mo / 12mo (no 3-month product). | WooCommerce Memberships purchase flow. |
| **Oor INK** | Mission, contact, sponsors, organisation pages. | Static + `borg` content. |

**Member surfaces:** **My Profiel = the PRIVATE profile** (oorsig, bydraes, vriende→volg, kennisgewings, lidmaatskap, the following-feed activity tab, plus private-only data: Gradering "wins needed" subtext and read counts), **Skrywerprofiel = the PUBLIC writer profile** (shown to others; note the spelling — Skrywer**profiel**, not "Skrywersprofiel"), Skryf (submission), auth flows (registreer / meld aan / wagwoord-herstel).

> **On "Lees":** the planning IA's "Lees" section and the mockup's "Ontdek" section were the same browse/search surface under two names. **Resolved 2026-06-14: merged into a single top-level section, "Ontdek".** "Lees" survives only as the reading *action* (the "Begin lees" verb and the single-piece reading/detail pages `lees-storie`, `lees-gedig`), not as a nav section. Curated/featured reading is handled by the Tuisblad.

---

## 9. Lovable design integration

**Principle:** Lovable is a *design source*, not runtime code (Principle 7). The translation model:

- Lovable design language → **theme tokens in `theme.json`**.
- Lovable page compositions → **block patterns and templates**.
- Lovable interaction patterns → **block styles + lightweight front-end behaviour**.

**Reference discipline (read before implementing any page):** the mockup is a **layout + visual-system reference only** — read the source `.tsx` + tokens for structure and styling. **Do not lift copy or content from it** — its text is English placeholder. UI copy comes from `ui-copy-translations.md` and `afrikaans-terms.md`; real content comes from the migrated database. **No screenshots are kept in the handoff, deliberately** — a screenshot is a picture of placeholder content and invites literal copying; the tokens + source are the precise, authoritative reference.

### 9.7 React → WordPress translation (the source is a spec of intent, not code to transpile)

The Lovable source is React + Tailwind + `shadcn/ui`; the build is a WordPress block theme + `ink-core`. **Extract design intent** (layout, hierarchy, spacing/scale, colour/type tokens, responsive breakpoints, interaction behaviour) and re-express it in WordPress primitives. Do **not** port React code.

| Lovable (React) | WordPress target | Do NOT |
|---|---|---|
| Component composition / page structure | Block patterns + templates / template-parts | Emit React/JSX or `.tsx` |
| Tailwind utility classes | Map to `theme.json` tokens + block styles | Copy classes or hardcode their px values |
| `shadcn/ui` primitives (Button, Card, Tabs, Input, Badge) | Core blocks + block-style variants / patterns | Port the component library |
| `useState` / client interactivity (line resonance, tabs, follow toggle) | Interactivity API or small enqueued JS; business logic in `ink-core` | Assume a React runtime |
| `react-router` routes | WP templates + permalinks / CPT rewrites | Build client-side routing |
| `src/data/*.ts` mock data, `localStorage` (`readerStore`) | CPTs, taxonomies, user meta, migrated DB | Treat mock data/localStorage as the data model |
| props / conditional rendering | Block bindings, query loop, block visibility | — |

Tailwind/`tailwind.config.ts` semantic aliases are already normalised into `theme-tokens.json` → use the `theme.json` tokens, not the raw classes. Reading the source is encouraged **with this lens applied**.

### 9.1 Design tokens
Normalised tokens live in `design-handoff/tokens/theme-tokens.json` and map to `theme.json` via `design-handoff/tokens/token-map.md` (colour → `settings.color.palette`, typography → `settings.typography`, spacing → `settings.spacing.spacingSizes`, layout → `settings.layout`). **`theme.json` naming is the production source of truth**, even where Lovable names differ.

### 9.2 Layout primitives
Every designed page is split into reusable primitives — template parts (header, footer, section shells), block patterns (hero, featured grid, archive intro, CTA bands, profile summaries), and block styles (button/card/emphasis variants). Block locking protects critical editorial structure while leaving content editable.

### 9.3 Page archetypes (for pages without a mock)
- **A — Editorial landing:** Tuisblad, top-level discovery.
- **B — Archive & discovery:** Ontdek, Opleiding, Biblioteek, Uitdagings lists.
- **C — Detail reading page:** gedig, storie, artikel, hulpbronartikel, biblioteekitem.
- **D — Community utility page:** profile, notifications, account, member interactions.

### 9.4 Page mapping & readiness
Per-page WordPress targets are in `design-handoff/page-map.csv`. Mockup readiness (`mockup-readiness-assessment.md`):

| Readiness | Pages |
|---|---|
| Reference-ready | Tuisblad, Lees (storie), **Lees (gedig)**, Uitdagings (single), Skryf, Skrywerprofiel (public), Ontdek, Gemeenskap, My Profiel (private) |
| Partial / layout-reference | Biblioteek (gaps: date browsing, pagination, author filter), Opleiding (uses Library layout), Uitdagings (list) |
| Design-missing | — none remaining (gedig layout designed 2026-06-14, `PoetryReader.tsx`) |
| Assembly-only (no new design) | Lidmaatskap, Oor INK, Kontak, Auth flows, Uitdagings list (Archetype B) |

**Genuine design gaps requiring decisions:** (1) Biblioteek organisation/archive depth — **deferred and non-blocking**: to be detailed later; does not gate Foundation, content models, or the other epics. The Biblioteek CPT + base archive can proceed now; date/archive browsing, pagination, and author filter are finalised later. *(Gedig reading layout and profile following-feed resolved 2026-06-14 — `PoetryReader.tsx`, `Profile.tsx` Activity tab.)*

### 9.5 Copy
Approved Afrikaans UI copy is in `ui-copy-translations.md` (full page-by-page coverage). The Lovable mockup copy is English placeholder; all of it is replaced with approved Afrikaans during implementation. Note the document still contains placeholder org details (founding year, legal status) flagged in §14.

### 9.6 Quality gates (release gates per template/pattern)
A: design-system compliance (tokens only). B: layout consistency (mock intent or archetype). C: platform fit (stable in Site Editor; CPT/taxonomy integration works). D: language compliance (correct Afrikaans, no English leakage).

---

## 10. Plugin stack and integration points

**Guiding rule:** every surviving plugin has a named reason tied to a capability; every retired one has a replacement or a confirmation the capability is gone.

### 10.1 Keep (with integration role)

| Plugin | Integration point / responsibility |
|---|---|
| **BuddyPress (scoped)** | Member Profiles (xprofile), Member Directory, Notifications **on**. Private Messaging **deferred** — off at launch, revisit later (§14.7). Friend Connections, site-wide Activity, Groups, Blogs **off**. Treated as a data/API layer; UI is custom block-theme templates via BP template hooks. Tier rendered in custom profile template. |
| **WooCommerce** | Memberships only. Suppress general-store UI (cart/catalog/checkout beyond membership purchase). Three fixed-term products: R60/1mo, R300/6mo, R600/12mo. |
| **WooCommerce Memberships** | Lidmaatskap tracking + access enforcement (expiry/suspension). **Active lidmaatskap = submission entitlement.** Already in active use; data carries across with DB clone. **Lifecycle automation (R5, launch):** thank-you email on every activation; expiry-warning emails — **1-month-prior** (longer terms) + **1-week-prior** — via Action Scheduler sweeps; per-term (1mo / 6mo / 12mo) on/off toggle + simple form-letter config. **Post-launch:** recurring payment, the recurring-renewal-warning email variant, and the recurring-renewal discount (§14.5 amendment). |
| **WooCommerce PayFast Gateway** | Front-end ZAR payment for lidmaatskap purchase (**new** flow). **Auto-activation on PayFast success triggers a thank-you email (R5).** Auto-renew deferred post-launch — verify PayFast recurring support before enabling (§14.8). |
| **Real3D Flipbook** (reactivate) | PDF viewer for `inkpols_uitgawe`. Verify existing file paths/config pre-launch. |
| **Rank Math** (replaces Yoast) | Sitemaps, meta, native CPT schema for `gedig`/`storie`/`artikel`, breadcrumbs (all free-tier). Adopted from the start. *Per-post* Yoast enrichment is negligible (owner's first-hand assessment: only a few InkPols OG images); global Yoast *config* is not carried forward by design (§11). This **overrides** `plugin-transition-guide.md`'s "keep Yoast through migration, evaluate Rank Math after launch" recommendation — a deliberate 2026-06-14 decision (§14.11). The Rank Math importer runs regardless as a safety net for any residual Yoast data; verify the InkPols images, then deactivate Yoast. SEO baseline comes from templated defaults + schema, **not** per-post manual backfill. |
| **Redirection** | Migration redirect layer (301s for moved content). 404 logging. |
| **Patchstack** (new) | Vulnerability/CVE alerts for installed plugins/themes; pairs with the staging-gated update routine (§13; `ink-feature-list.md` Epic 18.7). Lightweight — alerts, not a heavy WAF. |
| **Staff 2FA plugin** (new) | Two-factor auth for `editor`/`administrator` accounts (or Cloudflare Access on `/wp-admin`). |
| **Loco Translate** (authoring tool — **staging only, never installed on production** §14.13) | Translates the **residual user-facing strings of surviving third-party plugins only** — chiefly BuddyPress and the WooCommerce/Memberships/PayFast stack. The custom theme and `ink-core` are Afrikaans-native and need no translation files — this is a sharp reduction from the old site's site-wide rescue role. Author Afrikaans `.po/.mo` **on staging**, **commit them to version control** (theme or a `languages` mu-plugin); **production loads them from `wp-content/languages/` without Loco installed** — the committed bundle is the production safety net. New strings from ungated core/host-forced updates are caught by the automated English-leak detection (§13), then authored on staging, committed, and redeployed — **not hand-edited on production**. Prefer complete community language packs where they exist; manual translation for premium plugins (e.g. Memberships). Human-authored only. |
| **Custom `ink-core` report form** | Logged moderation report path (resolved 2026-06-15, PRD OQ-4 — replaces the evaluated Report Content plugin; Afrikaans-native). |
| **Custom `ink-core` contact form** | Contact/enquiry on Oor INK (resolved 2026-06-15, PRD OQ-8 — no CF7 / Fluent Forms). |
| **Comments Plus** (consolidate later) | Enforces global comment disable now; replace with two `ink-core` filters post-migration. The site-wide disable has **one programmatic exception** — the `ink_moderator_terugvoer` structured comment type (§6.6 / §7), written via `wp_insert_comment`; no native WP comments are re-enabled. |
| **LiteSpeed Cache** (reactivate) | Server-side full-page + object caching. NameHero runs LiteSpeed Web Server, so this is the native caching layer (confirm plan tier runs LSWS). Complements Cloudflare edge caching — Cloudflare for static/edge, LiteSpeed for dynamic/logged-in pages. |
| **Analytics provider** (new — R8) | **None selected today.** Select an analytics provider (launch). Read counts surface on **My Profiel** (private) reusing the already-denormalized `_ink_read_count`. POPIA posture (OQ-3) sharpens with this addition. |
| **Social login + anti-spam** (new — R6) | Social login at registration plus an **optional, off-by-default manual-approval backstop** (a possible "pending approval" account state). Preceded by an **anti-spam research spike**. Implemented via vetted platform plugins through hooks, not `ink-core`. |

### 10.2 Retire (replaced or unneeded)
Youzify & Youzify Frontend Submission (→ custom `ink-core` submission + custom BP profile templates), WPBakery / JoinUp Core / Qode Framework (old theme stack; grep & clean `[vc_*]` shortcodes first), CBX User Online, Classic Widgets, Ultimate Social Media Icons (→ theme footer pattern), WooCommerce Legacy REST API, WPCustom Category Image (→ native term meta; reassign 11 images), WPS Bidouille, LocoAI, Document Embedder, PDF Embedder, Maintenance, String Locator, **Yoast SEO** (→ replaced by Rank Math; import the few InkPols OG images first, then deactivate), **Loginizer** (→ login brute-force handled by the Cloudflare edge rule; retire once Cloudflare is in front with a locked origin — §14.9), **Invite Anyone** (→ BuddyPress Groups off, so it has no function — §14.7).

### 10.3 Transition tools (never on production)
Code Snippets (migrate business-rule snippets → `ink-core`, then remove), Simple CSS (migrate CSS → theme, then remove), WP Migrate Lite (staging migration only; uninstall after each use).

### 10.4 Conditional (both resolved 2026-06-14)
- **LiteSpeed Cache** → **adopted** — NameHero runs LiteSpeed (§14.9); see §10.1.
- **Invite Anyone** → **retired** — BuddyPress Groups off (§14.7); see §10.2.

### 10.5 `ink-core` ownership (moved out of theme/snippets)
CPTs, taxonomies, **Gradering logic + admin UI + promotion log + auto-promotion engine (§6.3.1)**, submission permissions & front-end form, challenge rules & winner records, **challenge adjudication automation (EntryID, judge-email collation, paste-text results ingestion + coverage report, winners-post generation, winner banner + featured ordering — §6.6)**, **`ink_moderator_terugvoer` structured comment type + writer display toggle**, sponsor rotation/scheduling, InkPols data model, **follow graph + following-feed**, reading engagement (highlights, reactions, structured responses, prompts, suggested reads, reading list), **reader ratings & reviews**, **pinned works**, comment-disable filters, **a lightweight form-letter / notification capability** (stored form-letter text + name-merge greeting, per-event on/off toggles, randomized message list — consumed by the congratulation email, lifecycle/receipt emails, and winners post), custom REST endpoints and admin tools.

---

## 11. Migration constraints (binding, forward-looking)

The new site **reuses the existing database** (cloned). Migration is mapped in detail in `migration-plan.md`; the binding constraints for the build:

- **Subscriptions migrate automatically** with the DB clone (WooCommerce Memberships already live). Verify active memberships, plan IDs, access rules, and expiry/suspension on the new host. **No import script.** Continuity here is *why* WooCommerce Memberships and PayFast are retained rather than replaced.
- **Writer Gradering** imports from the external spreadsheet (CSV → `ink_writer_tier`, joined on email). Missing/ambiguous → default `brons` + flag; spreadsheet writers without accounts → manual follow-up. (`meester` is never imported by default — it is manual-only.)
- **Posts → CPTs:** scripted reclassification by existing content-type category (old-site categories `Gedig`/`Verhaal`/`Artikel` → CPTs `gedig`/`storie`/`artikel`); `/biblioteek/` and `/opleiding/` sub-paths → respective CPTs; unclassifiable → `skryfwerk` automatically. **Do not hand-classify the `skryfwerk` bucket at volume** (`migration-plan.md`) — it is a holding bucket that preserves and keeps content searchable without per-post editorial effort. Several thousand posts.
- **Old-site CPT disposition:** two old-site CPTs need handling on migration. (a) The legacy `inkpols` CPT is **renamed** to the new `inkpols_uitgawe` CPT (records moved, loose month/year meta re-expressed as structured fields — see InkPols bullet below). (b) The legacy `monthly_challenge` CPT (a near-empty placeholder on the old site) is **not** the source of truth for challenge history and is **not** migrated 1:1. `uitdaging` is itself a CPT (§6.2); the migration **builds its records from the challenge-round categories on existing posts** (§14.6), not from `monthly_challenge`. Fold any real data the old `monthly_challenge` records happen to hold into the matching `uitdaging` record, otherwise drop them. (The old `verhaal`→`storie` rename is covered in §6.2.)
- **Redirects are mandatory.** Generate 301s during CPT migration (record old permalink before reassignment). **Keep `/biblioteek/` and `/opleiding/` prefixes** to preserve high-value archive URLs and cut redirect volume.
- **InkPols:** low volume; migrate back catalogue to `inkpols_uitgawe` with structured meta (date, volume, cover, PDF, teaser); retain PDFs in media.
- **Challenges:** migrate historical challenges structurally in the once-off DB update (§14.6) — challenge categories on existing posts → `uitdagingsrondte` terms + an `uitdaging` record per round; new challenges use the CPT from launch. Historical rounds carry full brief/deadline only where that data exists in old content.
- **Sponsors:** manual entry into `borg`.
- **Media:** `wp-content/uploads/` migrates as-is; verify audio/video playback and InkPols PDFs.
- **BuddyPress data:** profiles survive the DB clone; **friendships → follow:** convert each friendship into two mutual follow records (§14.10, §6.5); messaging is deferred (§14.7) so message data is not needed at launch; trim site-wide activity (off anyway); don't migrate notifications.
- **Options:** do **not** clone `wp_options` wholesale; carry forward only deliberate values (site URL/name, `af` locale). SEO config is not carried forward — Rank Math is set up fresh (import only the few InkPols OG images from Yoast, then retire Yoast).
- **Migration order** (summary): clean DB clone → define CPTs/taxonomies in `ink-core` → users → tiers → verify subscriptions → classify posts → migrate library/training → migrate posts (+redirects) → InkPols → sponsors → rebuild nav → verify redirects/media/BP → smoke-test → DNS cutover.

---

## 12. Afrikaans-first requirement

- **Scope of Afrikaans-first = the front end (visitor/member-facing surfaces) and user-facing transactional emails.** The **WordPress admin interface stays English by decision (§14.14)** — WP-core and third-party plugin admin screens are *not* translated; their Afrikaans translations are poor and English keeps support/documentation findable for staff.
- **Locale mechanism:** site locale `af` (so front-end plugin strings pull Afrikaans language packs / committed `.mo`); **staff accounts (editor/administrator) use English admin language** via the per-user WordPress language setting, enforced for those roles in `ink-core`. Front-end output stays Afrikaans regardless of a staff user's admin language. All custom strings use proper i18n functions.
- **Admin language split:** WP-core and third-party plugin admin chrome = English (§14.14); **all `ink-core` admin surfaces = Afrikaans** (§14.15) — CPT/taxonomy labels and custom admin screens (tier promotion, sponsor scheduling, challenge/winner admin, reports). Mechanism: `ink-core` authors these in Afrikaans as the source language and ships no English `.mo`, so gettext returns the Afrikaans source even under a staff member's English admin locale. The intended result is Afrikaans INK domain terms inside English WP chrome.
- `afrikaans-terms.md` is the glossary source of truth (subject to the follow reconciliation in §14). Code IDs and UI labels follow it; a new concept is added to the guide **before** it appears in code or UI.
- Plugins screened for translation quality before adoption. The custom theme and `ink-core` are Afrikaans-native (no translation files needed). Only the **residual user-facing strings of surviving third-party plugins** (mainly BuddyPress + WooCommerce/Memberships/PayFast) need an authored Afrikaans pass: use Loco Translate as the authoring tool **on staging** and **version-control the resulting `.po/.mo`** (production loads them without Loco installed — §14.13); prefer community language packs where complete (w.org-hosted plugins), translate premium plugins manually. **No AI-generated Afrikaans.**
- No English UI leakage (Quality Gate D).
- **Where third-party strings still surface despite owning the templates:** owning the UI = owning chrome, layout, and static labels — **not** strings plugins generate at runtime or send out of band. Leak vectors to test/translate: (1) error/validation/status messages (payment declines, "membership expired", login throttling); (2) dynamically composed text inside plugin functions (BuddyPress notification sentences, WooCommerce order/membership phrasing); (3) **transactional emails** (Woo order/renewal/expiry, BP notifications, password reset) — often not a template we rebuild; (4) **plugin JavaScript** strings (e.g. Real3D viewer controls) — need the plugin's JS `.json` translations, separate from `.mo`; (5) out-of-band outputs (REST/AJAX payloads, redirect-notice query args, feeds). Replacing a plugin with an Afrikaans-native `ink-core` surface removes its string surface entirely; what is irreducible is the kept *logic* of BuddyPress + the WooCommerce/Memberships/PayFast stack.
- Afrikaans casing convention: sentence case for headings ("Begin skryf", not "Begin Skryf").

---

## 13. Non-functional requirements

- **SEO / indexability:** preserve archive URLs, 301 integrity, sitemaps, CPT schema. Content architecture precedes visual redesign.
- **Security:** edge (Cloudflare) login protection — Loginizer retired (§14.16); no dev/diagnostic/migration tools on production; moderation/report path.
- **Performance:** caching layer appropriate to host; avoid plugin sprawl and heavy front-end JS where a pattern suffices.
- **Accessibility & readability:** Afrikaans readability prioritised over decorative type; reading templates text-legible first (768px content width).
- **Maintainability:** Site Editor stability for non-technical staff; block locking on critical structure; design tokens enforced.
- **Update governance & i18n resilience:** WordPress core and plugins update on a partly **uncontrollable** cadence (security/minor core releases and host-forced updates cannot always be gated), and updates can introduce new English strings. Posture: (1) gate *major* plugin/core updates through staging where possible, running a regression pass on custom template overrides **and** a translation refresh; (2) rely on auto-delivered **community language packs** for well-covered code — WP core's Afrikaans (`af`) coverage is strong and self-updates, as do popular w.org plugins — so these rarely leak; (3) the genuine exposure is **premium/niche plugins with no language packs** (WooCommerce Memberships, PayFast gateway, Real3D), whose committed `.mo` is the only defence and must be re-checked after their updates; (4) because not every update can be gated, run **production-side detection** for untranslated strings (the automated English-leak scan); fixes are then authored on staging, committed to version control, and redeployed — **Loco is not installed on production** (§14.13). No-English-leakage is a standing operational requirement, not a one-time build gate.
- **Testing & QA strategy (to make the staging gate affordable)** *[Decided 2026-06-14 via planning discussion — see §14.17; not in the original planning corpus]*: Test *your own seams, not the plugins themselves* — confirm `ink-core` logic and the theme↔plugin integration points survive an update, don't re-test BuddyPress/WooCommerce. Test pyramid: **many unit tests** for `ink-core` rules (tier promotion, submission-entitlement gate, sponsor scheduling, follow graph) with WP mocked (**Brain Monkey / WP_Mock**, via **Pest** or PHPUnit); **fewer integration tests** booting real WP+DB (**`wp-env`** + WP test library, or **wp-browser/Codeception**) for the seams that matter (*active membership ⇒ can submit*, *expired ⇒ denied*, *tier write ⇒ meta+log*); a **thin E2E layer** (**Playwright** + `@wordpress/e2e-test-utils-playwright`) for critical journeys only (register → buy membership via PayFast **sandbox** → submit → publish → read/react → renewal/expiry). Run unit+integration in CI per change; run the E2E smoke suite automatically on the staging deploy so the update gate is mostly automated. **Risk-based depth:** smoke-only for minor/security updates, full regression for major version bumps. Add an automated **English-leak check** (crawl key front-end pages + scan for English / `wp i18n` untranslated counts) to satisfy the detection requirement above cheaply. Concentrate the suite in `ink-core` (highly unit-testable); cover the block theme via E2E/visual checks rather than unit tests.
- **Editorial low-friction:** automatic surfacing via shared taxonomy; no mandatory per-item manual linking.

---

## 14. Open decisions / items to confirm

| # | Item | Status / default | Action |
|---|---|---|---|
| 1 | Social graph | **Resolved 2026-06-14: Follow (asymmetric).** | `afrikaans-terms.md` updated 2026-06-14 (follow vocabulary; friendship terms replaced). Build: implement follow in `ink-core`; BP Friend Connections off. |
| 2 | Mockup-only features | **Resolved 2026-06-14: include** reader ratings & reviews, pinned/selected works, following-feed (= new works by followed writers). | Model in `ink-core`. |
| 3 | Challenge cadence | **Resolved 2026-06-14: monthly.** (Mockup "weekly/January" was placeholder.) | Build monthly. |
| 4 | Org content placeholders | **Resolved 2026-06-14: use clearly-marked placeholders for now;** actual founding year + SA nonprofit status to be confirmed at a future date. | Build with obvious placeholders (e.g. `[stigtingsjaar]`, `[regstatus]`). Do **not** ship the US "501(c)(3)" wording. **Pre-launch content gate:** confirm real values before go-live. |
| 5 | Membership renewal savings copy | **Resolved 2026-06-14: no discount model.** Show prices only (R60/R300/R600); the mockup's "Save 12%/25%" is dropped. **AMENDED 2026-06-20 (R5 / owner decision):** a *genuine recurring-renewal discount* is **permitted** — but with **no vanity "%-off" framing**. Effective **post-launch**, when recurring billing ships (§14.8). The launch position (no discount on fixed-term products) is unchanged. | Remove all vanity savings framing from the fixed-term plan/renewal UI. When recurring ships, a real recurring-renewal discount may be offered. |
| 6 | Historical challenge scope | **Resolved 2026-06-14: migrate historical challenges structurally via a once-off DB update.** Existing posts already encode content type *and* challenge round as categories. | Migration: content-type categories → CPTs; challenge categories → `uitdagingsrondte` terms (preserve each piece's linkage) + an `uitdaging` record per historical round. Round identity + linked entries always recoverable; full brief/deadline only where old data exists. |
| 7 | BuddyPress Groups & messaging | **Resolved 2026-06-14.** Groups: **OFF** → Invite Anyone **retired**. Messaging: **not in scope for initial launch** (revisit later). | Groups + Private Messaging components off at launch; Invite Anyone retired. |
| 8 | PayFast recurring billing | **Resolved 2026-06-14: deferred until after launch** — not a current feature, so nothing is removed. Launch uses fixed-term products. **UNCHANGED 2026-06-20:** recurring billing remains deferred post-launch (confirmed; the R5 lifecycle work ships auto-activation + lifecycle emails + per-term config only). | When pursued later, verify PayFast recurring support + extension compatibility before enabling auto-renew. |
| 9 | Hosting / CDN | **Resolved 2026-06-14: host = NameHero** (LiteSpeed Web Server) behind Cloudflare. Origin must be locked to Cloudflare-only traffic. Host provides malware scanning (§14.16). | **LiteSpeed Cache adopted** as the server-side caching layer (confirm plan tier runs LSWS — Turbo Cloud and above do) + Cloudflare edge caching. |
| 10 | Friendship→follow migration | **Resolved 2026-06-14: convert.** Each existing BuddyPress friendship → two follow records (A→B and B→A). | Migration script generates mutual follows from the BuddyPress friendship table. |
| 11 | SEO plugin | **Resolved 2026-06-14: Rank Math from the start** (no meaningful Yoast data — only a few InkPols OG images). | Import limited Yoast data via Rank Math importer; verify InkPols images; deactivate Yoast. No per-post backfill. |
| 12 | Loco Translate role | **Resolved 2026-06-14: authoring tool only**, scoped to residual third-party plugin strings; theme/`ink-core` are Afrikaans-native. | Author + version-control `.po/.mo`. |
| 13 | Loco active on production? | **Resolved: no — Loco is a staging/authoring tool only, never installed on production** (consistent with §13 production hygiene). Production loads the committed `.mo` from `wp-content/languages/` without Loco present; the committed bundle is the safety net. | Author translations on staging; **commit `.po/.mo` to version control** and ship them with releases. New strings from ungated core/host-forced updates: the automated English-leak detection (§13) flags them, then they are authored on staging, committed, and redeployed — **not hand-edited on production**. Theme/`ink-core` remain Afrikaans-native. |
| 14 | Admin interface language | **Resolved 2026-06-14: WordPress admin stays English.** Afrikaans-first applies to the front end + user-facing emails only. Rationale: poor WP/plugin Afrikaans admin translations; support/doc findability for staff. | Site locale `af`; staff roles (editor/administrator) forced to English admin language via the per-user WP language setting in `ink-core`. Removes admin chrome from the translation scope entirely. |
| 15 | `ink-core` own admin labels | **Resolved 2026-06-14: Afrikaans.** All `ink-core`-registered CPT/taxonomy labels and custom admin UI (tier promotion, sponsor scheduling, challenge/winner admin, reports) stay Afrikaans per `afrikaans-terms.md` — English would confuse editors working with INK domain concepts. WP-core + third-party plugin admin chrome stays English (§14.14). | `ink-core` authors its admin-facing strings in Afrikaans as the source language and ships **no English translation**, so gettext returns the Afrikaans source even under a staff member's English admin locale. Intended result: Afrikaans INK domain terms within English WP chrome. |
| 16 | Security stack (layered) | **Resolved 2026-06-14:** Cloudflare (edge + login rule) + staff 2FA + **Patchstack** (CVE alerts) + disciplined staging-gated updates (§13; `ink-feature-list.md` Epic 18.7) + host-level malware scanning (**provided by the host**, confirmed 2026-06-14). **No WordFence** — host scanning + Patchstack cover the gap without the overhead. **Patchstack is a new addition** (not in the original corpus). | Login brute-force handled by the Cloudflare rule → **Loginizer retired** (requires the origin locked to Cloudflare — §14.9). Add a lightweight staff-2FA plugin or Cloudflare Access on `/wp-admin`. PayFast off-site → low PCI scope. |
| 17 | Testing & QA strategy | **Resolved 2026-06-14 via planning discussion** (not in the original planning corpus). Pest/PHPUnit + Brain Monkey/WP_Mock unit tests for `ink-core` rules, `wp-env`/wp-browser integration tests for the theme↔plugin seams, a thin Playwright E2E layer for critical journeys, plus an automated English-leak crawl. Risk-based depth (smoke for minor/security updates, full regression for majors). | Rationale: make the staging update-gate (§13, §14.13) affordable by automating regression. Build the suite into CI per change; run E2E smoke on staging deploys. Detailed in §13 + `ink-feature-list.md` Epic 18.8. |
| 18 | Gradering auto-promotion priority | **NEW 2026-06-20 (R3):** tier auto-promotion **moved P2 → P0** — the promotion rules are now defined (§6.3.1: top-3-win counting; Brons→Silwer at 5, Silwer→Goud at 15; reset on promotion; manual-only Meester). Supersedes the earlier deferral (PRD §14.2) that was waiting on these rules. | Build the `Ink\Tiers` promotion engine at launch; triggered as the final step of R2 results ingestion. |
| 19 | Editorial-automation pillar (R1/R2) | **NEW 2026-06-20:** challenge adjudication automation is a **P0 launch pillar** (§6.6) — EntryID at collation, judge-email collation (R1), **paste-text** results ingestion + coverage report (R2, **no `.docx` parser**), winners-announcement post, moderator-feedback comment type, winner banner + featured ordering (algehele wenner before wenner). Hard build chain R1→R2→R3; EntryID is the linchpin. | Add the new epic / Epic 12 extension; resequence per the dependency chain. |
| 20 | Terminology refinements (G1) | **NEW 2026-06-20 (G1):** membership concept uniformly **lidmaatskap** (`intekening`/`intekenaar`/`intekenlid` retired); paid = **betaalde lid**, free = **gratis lid**; tier UI term = **Gradering** (never "tier"); **Skrywerprofiel = public**, **My Profiel = private** (fix "Skrywersprofiel" spelling drift). New nouns: EntryID, algehele wenner / wenner, Terugvoer van die moderator, Meester. | Propagate across **all** docs; `afrikaans-terms.md` is the source of truth. Conflation rule (lidmaatskap ⟂ Gradering) preserved. |
| 21 | Analytics provider + read counts (R8) | **NEW 2026-06-20:** select an analytics provider (currently **none**, launch); surface read counts on **My Profiel** (verb-less count + `_n()` plurals) reusing `_ink_read_count`. Sharpens POPIA (OQ-3). | §10.1 analytics row; My Profiel read-count surface. |
| 22 | Account approval & anti-spam (R6) | **NEW 2026-06-20:** social login + an **optional, off-by-default manual-approval backstop** + an **anti-spam research spike**, under accounts/security. | Vetted platform plugins via hooks (not `ink-core`); spike precedes the social-login + approval-backstop build. |
| 23 | Annual competition (R9) | **NEW 2026-06-20: P2 (deferred).** Annual competition management — **reuses the R1/R2/R3 machinery** on an annual cadence. | Post-launch; no net-new machinery beyond the challenge-adjudication / promotion engine. |

---

## 15. Epic map

The build decomposes into the following epics (detailed in `ink-feature-list.md`), listed in **build/dependency order** — earlier epics are prerequisites for later ones, which is how BMAD shards and sequences work. Cross-cutting concerns (Afrikaans-first, testing) are handled by foundational slices in Epic 1 plus standing acceptance criteria, not by their (high-numbered) execution epics:

1. Foundation — block theme + tokens + `ink-core` scaffold + **locale `af` / i18n scaffolding & admin-language mechanism** (Afrikaans-first is foundational, established here — Principle 3 / §12) + **test harness scaffold** (so `ink-core` rules ship test-first — §14.17)
2. Content models & taxonomy
3. Accounts, registration & auth — auth flows and onboarding lifecycle (**foundational**: accounts precede membership/submission/community — §4)
4. Membership, access & payment (PayFast)
5. Writer Gradering (Brons/Silwer/Goud + manual-only Meester; auto-promotion engine §6.3.1; congratulation email)
6. Submission workflow
7. Reading & engagement
8. Discovery (Ontdek)
9. Community & social (follow, profiles, messaging, notifications)
10. Library (Biblioteek)
11. Training (Opleiding)
12. Challenges (Uitdagings) & winners — incl. **challenge adjudication automation** (EntryID, judge-email collation R1, paste-text results ingestion + coverage report R2, winners post, winner banner + featured ordering — §6.6)
13. InkPols
14. Sponsors (Borge)
15. Organisation pages & contact
16. Migration & redirects
17. Afrikaans-first & localisation (**execution + QA**: copy application, residual-plugin translation, leak gate — foundational enablers are in Epic 1; the principle is also cross-cutting, see Gate D)
18. SEO, security & performance (incl. **full test-suite buildout** — the harness *scaffold* is in Epic 1; this extends it to the full pyramid; plus **R8 analytics provider** and the **R6 registration anti-spam** spike + social-login / optional approval backstop)

A **P2 (deferred)** annual competition (R9) reuses the Epic 12 / Epic 5 machinery on an annual cadence (§14 row 23).
