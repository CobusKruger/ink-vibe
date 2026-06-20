# INK — Comprehensive Feature List

> ## ⛔ SUPERSEDED — companion input only (2026-06-20)
> **This file is no longer the source of record for epics & stories.** The canonical, BMAD-conformant epic/story breakdown now lives at **`_bmad-output/planning-artifacts/epics.md`** (generated 2026-06-20 from the post-Correct-Course PRD/architecture/UX, with this file folded in). Downstream BMAD skills (`bmad-sprint-planning`, `bmad-create-story`, `bmad-check-implementation-readiness`) read `epics.md`, **not** this file.
>
> This document is retained as a **companion input** for its narrative notes and traceability. **Do not edit it as the living epics list** — make epic/story changes in `epics.md` (and reconcile here only if you want the prose to stay aligned). Keeping both as living docs is the two-sources-of-truth trap this demotion exists to prevent.

> **Companion to** `ink-consolidated-spec.md`. **Date:** 2026-06-14 · **Superseded as epics source:** 2026-06-20
> **Generated from:** [`spec-consolidation-brief.md`](./spec-consolidation-brief.md) — the originating brief and its four scope rules. Validate this deliverable against it.
> **Purpose:** A complete, decomposable inventory of features for the new INK site, organised as **epics → features → stories** for ingestion into a spec-driven framework (BMAD recommended). Each feature notes its layer (Theme / `ink-core` / Platform), data sources, and key acceptance criteria.
> **Fidelity rule:** Every feature below traces to a decision in the planning corpus or to a clarification confirmed on 2026-06-14. Items confirmed in clarification are tagged **[C-2026-06-14]**. Items still needing confirmation are tagged **[CONFIRM]** and cross-referenced to `ink-consolidated-spec.md §14`. No features have been invented.
> **Ordering:** Epics are listed in **build/dependency order** — earlier epics are prerequisites for later ones, which is how BMAD shards and sequences work. Cross-cutting concerns (Afrikaans-first, testing) are handled by foundational slices in Epic 1 plus standing acceptance criteria, not by late epics.

**Legend:** Layer — `T` theme · `K` ink-core · `P` platform plugin. Priority — `P0` launch-critical · `P1` launch · `P2` fast-follow.

---

## Epic 1 — Foundation (theme + tokens + ink-core scaffold)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 1.1 | `theme.json` design-token system | T | P0 | All colour/type/spacing/layout/radius/shadow tokens from `theme-tokens.json` mapped per `token-map.md`. No hardcoded values pass review (Gate A). |
| 1.2 | Typography system | T | P0 | Lora (display/heading) + Inter (body/UI); named scale xs–3xl; fluid where appropriate; Afrikaans readability prioritised. |
| 1.3 | Dark mode tokens | T | P1 | Dark palette from token file wired into theme. |
| 1.4 | Global templates & template parts | T | P0 | header, footer, section shells. |
| 1.5 | Core block-pattern library | T | P0 | hero, featured grid, archive intro, CTA bands, profile summaries, card/button/emphasis variants. |
| 1.6 | Block locking strategy | T | P1 | Lock critical editorial structure; content stays editable. |
| 1.7 | `ink-core` plugin scaffold | K | P0 | Plugin bootstrap, `includes/` structure, activation hooks, i18n loading. |
| 1.8 | Comment-disable filters | K | P1 | `comments_open`/`pings_open` → false; replaces Comments Plus after migration. |
| 1.9 | Page archetypes A–D documented & built | T | P1 | Reusable scaffolds for non-mocked pages. |
| 1.10 | Locale `af` + i18n scaffolding & admin-language mechanism | K/T | P0 | **Foundational — established in the Foundation phase, not retrofitted (Principle 3 / §12).** Site locale `af`; all custom strings internationalised (proper gettext); sentence-case Afrikaans headings. Admin-language mechanism: staff roles (editor/administrator) forced to English admin language via per-user WP language in `ink-core` (§14.14); front end stays Afrikaans regardless. `ink-core`'s own admin labels/screens authored in Afrikaans as source with **no English `.mo`** so they render Afrikaans under the English admin locale (§14.15). Localisation *execution* (copy application, residual-plugin translation, leak QA) is **Epic 17**. |
| 1.11 | Test harness scaffold | K | P0 | **Foundational — wired in the Foundation phase so `ink-core` rules ship test-first, not retrofitted (§14.17).** Pest/PHPUnit + Brain Monkey/WP_Mock unit-test setup **and** the `wp-env` integration harness, both wired into CI from the start, so every P0 rule (tier promotion 5.x, submission gate 6.8, follow graph 9.2) lands with its tests. Full suite buildout (E2E journeys, English-leak scan, risk-based depth) is **Epic 18 (18.8)**. |

---

## Epic 2 — Content models & taxonomy

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 2.1 | Register CPTs | K | P0 | `gedig`, `storie`, `artikel`, `skryfwerk`, `biblioteek_item`, `opleiding_artikel`, `uitdaging`, `inkpols_uitgawe`, `borg`. Afrikaans slugs per terms guide. |
| 2.2 | Register taxonomies | K | P0 | `genre`, `vaardigheid`, `uitdagingsrondte`, `ster_gradering`. `genre`/`vaardigheid` shared across bydraes & training for auto-surfacing. |
| 2.3 | User meta | K | P0 | `ink_writer_tier`, `ink_tier_promoted_at`. |
| 2.4 | CPT admin field sets | K | P1 | Per-CPT meta (e.g. InkPols issue date/volume/cover/PDF/teaser; challenge theme/deadline; sponsor link/tier/dates/placement). |
| 2.5 | Term images native | K/T | P2 | Replace WPCustom Category Image; reassign 11 existing images. |

---

## Epic 3 — Accounts, registration & auth

> **Foundational dependency.** Accounts precede membership purchase (Epic 4), submission (Epic 6), and community features (Epic 9) — nothing user-specific can be built or tested without them. Decomposed from the registration lifecycle confirmed in `ink-consolidated-spec.md §4` and the former Org-pages auth row.

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 3.1 | Authentication pages | T+P | P0 | Registreer / Meld aan / Wagwoord-herstel — Afrikaans auth surfaces. Assembly-only (no mock). |
| 3.2 | ~~Reader/writer intent capture~~ — **removed 2026-06-14** | — | — | Signup intent dropped: no reader/writer choice at registration; any lid can publish once they hold an active lidmaatskap (betaalde lid). See PRD §4.1 FR-2. |
| 3.3 | Registration lifecycle / onboarding | K/T | P1 | Per §4: create account → complete profile (gratis lid) → prompt first social action after signup. No signup intent choice; publishing requires an active lidmaatskap (betaalde lid — Epic 4). |
| 3.4 | **Anti-spam research spike (R6)** | — | P1 | **[2026-06-20 / R6]** Research spike FIRST (owner: "I know nothing about this") — evaluate anti-spam / account-abuse approaches before building. Gates 3.5/3.6. |
| 3.5 | **Social login (R6)** | P | P1 | **[2026-06-20 / R6]** Social-login on, via a vetted platform plugin (hooks, not `ink-core`). Reduces signup friction (UJ-1) while curbing abuse. Auth surface gains social-login buttons. |
| 3.6 | **Optional manual-approval backstop (R6)** | K/P | P1 | **[2026-06-20 / R6 / C8]** Optional, **off-by-default** "pending approval" account state + approval queue (admin screen, UX gap). Layered behind anti-spam + social login; on only if abuse warrants it. |

---

## Epic 4 — Membership, access & payment

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 4.1 | Three fixed-term lidmaatskap products | P | P0 | R60/1mo, R300/6mo, R600/12mo. No auto-renew at launch. Terms remain 1/6/12. |
| 4.2 | **Front-end PayFast purchase flow** | P+T | P0 | Betaalde lid buys & self-activates lidmaatskap; removes manual EFT/admin activation. ZAR. |
| 4.3 | Access enforcement | P | P0 | Active WooCommerce Membership = submission entitlement (betaalde lid); expiry auto-suspends → reverts to gratis lid. |
| 4.4 | Lidmaatskap page | T | P0 | Plans, benefits, FAQ, CTA. Assembly-only (no mock); pricing-table pattern. |
| 4.5 | Renewal UI | T | P1 | On My Profiel → Lidmaatskap tab; choose 1/6/12 months. Show prices only (R60/R300/R600); no discount/savings labels at launch (§14.5). |
| 4.6 | Store-UI suppression | K/P | P1 | Hide cart/catalog/checkout beyond lidmaatskap purchase. |
| 4.7 | Status messaging (Afrikaans) | K | P1 | "Jou lidmaatskap is aktief…", "Jou lidmaatskap het verval…", access-denied messages per terms guide (lid family). |
| 4.8 | **Lidmaatskap lifecycle emails** | K/P | P1 | **[2026-06-20 / R5]** At launch: thank-you on every activation; expiry warnings 1-month-prior (longer terms) + 1-week-prior. Per-term (1/6/12) on/off + form-letter config (consumes 12A.0). Action Scheduler drives the expiry sweeps. |
| 4.9 | Auto-renew (recurring) | P | P2 | **Post-launch (§14.8).** Verify PayFast recurring support before enabling. |
| 4.10 | Recurring-renewal warning variant | P/K | P2 | **Post-launch.** Renewal-warning email variant for recurring lidmaatskappe (depends on 4.9). |
| 4.11 | Recurring-renewal discount | P/K | P2 | **Post-launch (§14.5 amended 2026-06-20).** Genuine recurring discount permitted once recurring (4.9) ships; no vanity "%-off" framing. |

---

## Epic 5 — Writer Gradering (Brons / Silwer / Goud / Meester)

> **UI term is "Gradering" (never "tier" in UI) — owner decision 2026-06-20 (G1).** Code key `ink_writer_tier` and the Kernel `Tier` enum are unchanged.

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 5.1 | Gradering data model | K | P0 | `ink_writer_tier` ∈ {brons, silwer, goud, **meester**}; default brons. **[2026-06-20 / R3]** Meester added — manual-only terminal state, never auto-promoted; rendered in brand red-orange `primary #EA4015` (not `danger`). |
| 5.2 | Staff set/adjust Gradering admin UI | K | P0 | View and **set** Gradering in any direction (promote / corrective demotion), record reason, optionally link to a challenge result; writes change log. **Covers manual promotion to Meester** (the only path to Meester). (PRD FR-12.) |
| 5.3 | Promotion log / history (graderingsgeskiedenis) | K | P1 | Auditable record (meta key or custom table). |
| 5.4 | Gradering display on profiles | T | P1 | Brons/Silwer/Goud/**Meester** shown on Skrywerprofiel (public) & My Profiel (private). **Meester rendered in brand red-orange `primary #EA4015`**; pair colour with text/icon (no colour-only encoding, a11y). |
| 5.5 | Gradering in discovery & winners | K/T | P1 | Filter writers by Gradering; segment challenge participation; label winners (e.g. "Oktober Goud-wenner"). |
| 5.6 | Gradering ≠ lidmaatskap guardrails | K | P0 | Code/config keeps Gradering and lidmaatskap strictly separate (the conflation rule; `Ink\Tiers` never reads Entitlement). |
| 5.7 | **Win-count meta + reset-on-promotion** | K | P0 | **[2026-06-20 / R3]** New user-meta `ink_tier_win_count` — count of top-3 placements toward the next Gradering; reset to 0 by `Tiers::promote()` on every promotion. |
| 5.8 | **Automatic promotion engine** | K | P0 | **[2026-06-20 / R3]** A *win* = any top-3 placement, any entry type, at the writer's current Gradering; multiple placements (incl. multiple in one category) each count. **Brons → Silwer at 5 wins; Silwer → Goud at 15 wins.** Goud/Meester have no auto-threshold. **Triggered as the final step of R2 (12A.3)**; lives in `Ink\Tiers` (never reads Entitlement). |
| 5.9 | **My Profiel "wins needed" subtext** | T | P1 | **[2026-06-20 / R3]** On My Profiel (private): e.g. "4 top 3 uitslae nodig om Silwer te bereik". Uses `_n()` plurals. |
| 5.10 | **Promotion congratulation email** | K/P | P1 | **[2026-06-20 / R3]** Templated congratulation email on auto-promotion (simple form-letter via 12A.0); e.g. "Baie geluk! Jy is na Silwer bevorder." |

---

## Epic 6 — Submission workflow (custom)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 6.1 | Custom front-end submission form | K+T | P0 | Replaces Youzify FES. Serves `gedig`/`storie`/`artikel` with appropriate fields & validation. Skryf page (`Write.tsx` reference). |
| 6.2 | Content-type selector | T | P0 | Poem / story / article; per-type placeholders & counters (lines+words for poems; words for prose). |
| 6.3 | Plain-text + basic formatting editor | K/T | P0 | No full rich-text editor. |
| 6.4 | Optional featured image | K | P1 | |
| 6.5 | Optional audio/video attachment | K | P1 | |
| 6.6 | Challenge linking at submission | K/T | P1 | Tick active challenges the piece responds to (writes `uitdagingsrondte`). |
| 6.7 | Draft / publish states | K | P1 | "Stoor konsep" / "Plaas"; success screen with read-&-respond prompts. |
| 6.8 | Submission entitlement gate | K | P0 | Only betaalde lede (active lidmaatskap) can publish; clear Afrikaans denial + link to plans. |
| 6.9 | Remove legacy edit-link filter | K | P1 | Drop the old `functions.php` `/plaas-nuwe-publikasie` override when Youzify retired. |

---

## Epic 7 — Reading & engagement

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 7.1 | Single reading templates | T | P0 | Detail (Archetype C) for `storie`/`artikel` (reference-ready). |
| 7.2 | **Gedig reading layout** | T | P1 | Designed 2026-06-14 (`PoetryReader.tsx`): stanza-aware, preserves line breaks, Roman-numeral stanza markers, per-line resonance (heart). Poem body left-aligned (2026-06-20). Reference-ready. |
| 7.3 | Line highlighting + reactions | K+T | P1 | Select text → highlight; reactions hartjie/duim op/wow. Encouragement, not critique. No public annotation. |
| 7.4 | Structured community responses | K+T | P1 | "Gemeenskapsreaksies": types Lof/Insig/Voorstel. Replaces WP comments. |
| 7.5 | Contextual prompts after a piece | K/T | P2 | Guided response prompts (may vary by content type). |
| 7.6 | Suggested next reads | K | P2 | By tone/form/topic/Gradering via taxonomy. |
| 7.7 | Reading list (leeslys) | K+T | P1 | Save/remove works; toasts; surfaced on profile. |
| 7.8 | Reactions data + counts | K | P1 | "hartjies" count beside ♥ icon. |

---

## Epic 8 — Discovery (Ontdek)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 8.1 | Ontdek section + works archive | T | P0 | Reading/discovery hub for published writing (works); date/archive browse. Single-piece reading lives in Epic 7 (7.1/7.2). |
| 8.2 | Ontdek — bydraes tab | T | P1 | Browse all works; filter by type (Gedigte/Stories/Artikels); sort (Nuut/Opspraakwekkend/Mees geliefd). |
| 8.3 | Ontdek — skrywers tab | T | P1 | Browse writers; genre filter (Digkuns/Prosa/Artikels); sort (Meeste gelees/Nuwe stemme). |
| 8.4 | Search | K/P | P1 | Search works (title/theme) and writers (name/bio/genre). |
| 8.5 | Discovery surfaces | K/T | P2 | "writers like this", new voices, recently active, writers in your Gradering, unread-by-you. (Custom, not default community screens.) |

---

## Epic 9 — Community & social

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 9.1 | BuddyPress scoped config | P | P0 | Profiles, Directory, Notifications **on**; Private Messaging **off at launch** (deferred — see 9.8, §14.7); Friend Connections, site-wide Activity, Groups, Blogs off. |
| 9.2 | **Follow graph (asymmetric)** | K | P0 | **[C-2026-06-14]** One-way follow in `ink-core`; follower/following counts; Volg/Volg tans UI. Replaces friendships. |
| 9.3 | **Following-feed** | K+T | P1 | **[C-2026-06-14]** Profile "Activity" tab = new publications by followed writers. Design exists (`Profile.tsx` Activity tab). |
| 9.4 | Custom profile templates | T | P1 | Block-theme BuddyPress templates (My Profiel = private + Skrywerprofiel = public); Gradering, bio, stats, pinned works, accomplishments. |
| 9.5 | **Pinned / selected works** | K+T | P1 | **[C-2026-06-14]** Writer curates highlighted pieces on profile. |
| 9.6 | **Reader ratings & reviews** | K+T | P1 | **[C-2026-06-14]** Aggregate reader rating + written reviews on writer profiles. |
| 9.7 | Member directory (ledegids) | P/T | P1 | Writer discovery surface. |
| 9.8 | Private messaging | P+T | — | **Deferred — not in initial launch scope** (§14.7). BP Messaging off at launch; revisit later. |
| 9.9 | Notifications | P+T | P1 | @mentions, challenge announcements, follow/new-work alerts; "Merk alles as gelees". |
| 9.10 | Member online widget | — | — | **Removed** (CBX retired). Replace with engagement signals if any chrome remains. |
| 9.11 | **Receipt-notification trigger (R7)** | K | P1 | **[2026-06-20 / R7]** When a writer's work receives engagement (a "receipt"), fire a kennisgewing surfaced on My Profiel, drawing a **randomized message** from the form-letter list (12A.0). |
| 9.12 | **Read-count surface on My Profiel (R8)** | K/T | P1 | **[2026-06-20 / R8]** Surface read counts on **My Profiel** (private); verb-less count + `_n()` plurals; reuses the denormalized `_ink_read_count`. Analytics provider chosen in 18.9. |

---

## Epic 10 — Library (Biblioteek)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 10.1 | `biblioteek_item` archive + single | T | P1 | Featured strip + category filter + search + card grid (Library layout reference). |
| 10.2 | Date / archive browsing | K+T | P1 | **Gap** vs mockup — detail deferred, non-blocking (§9.4). |
| 10.3 | Pagination | T | P1 | **Gap** vs mockup — detail deferred, non-blocking (§9.4). |
| 10.4 | Author filter | K+T | P1 | **Gap** vs mockup — detail deferred, non-blocking (§9.4). |
| 10.5 | Winner ↔ challenge linkage | K | P2 | Winners link back to producing challenge via `uitdagingsrondte` taxonomy (or relationship where modelled). |
| 10.6 | **Biblioteek auto-update on win (R4 — stub)** | K | P0 (stub) | **[2026-06-20 / R4]** One-line note: winning entries update the writer's Biblioteek. Update *hook* in scope at P0; **body deferred** with the broader biblioteek organisation analysis (§9.4). |

> The `Biblioteek organisasie.md` planning doc is an empty placeholder; biblioteek organisation is a flagged design gap (`ink-consolidated-spec.md §9.4`).

---

## Epic 11 — Training (Opleiding)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 11.1 | `opleiding_artikel` hub | T | P1 | Resource hub (not LMS). Library-layout archetype. |
| 11.2 | `vaardigheid` taxonomy + faceted search | K+T | P1 | Skill areas (Begin hier, Skryfkuns, Digkuns, Prosa, Stylfigure, Redigeer en hersien, Stem en styl). |
| 11.3 | Editor's shelf / curated entry points | T | P2 | "Die redakteur se rak" + empty states. |
| 11.4 | Auto cross-surfacing | K | P2 | Shared `genre`/`vaardigheid` terms surface training under works/challenges automatically — no manual linking. |
| 11.5 | Community contribution CTA | T | P2 | "Plaas 'n stuk" for community-written guides. |

---

## Epic 12 — Challenges (Uitdagings) & winners

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 12.1 | `uitdaging` single page | T | P1 | Prompt, literary devices, submission rules, prize, deadline, resources, entries list (`Challenge.tsx` reference). |
| 12.2 | Uitdagings list page | T | P1 | Archetype B; countdown; partial mock only. |
| 12.3 | Challenge metadata | K | P1 | `challenge_theme`, `challenge_deadline`; **monthly** cadence (resolved §14.3). |
| 12.4 | Entry capture | K | P1 | `inskrywing` linked to round via `uitdagingsrondte`. |
| 12.5 | **Gradering-based competition pools** | K | P1 | Brons vs Brons, Silwer vs Silwer, Goud vs Goud. Placements (1st–3rd) announced per Gradering. |
| 12.6 | Structured placement records | K | P0 (for admin) | Queryable **placement** records per Gradering — 1st/2nd/3rd, not only the single winner; powers the craft-progression metric (PRD SM-8); surfaces contextually ("Oktober Goud-wenner"). Feeds the R2 ingestion (12A.3) and R3 auto-promotion (5.8). |
| 12.7 | Winner → Gradering promotion link | K | P1 | Optional link from graderingsgeskiedenis to challenge result. |
| 12.8 | Historical challenge migration | K | P1 | Once-off DB update (§14.6): challenge categories → `uitdagingsrondte` terms + an `uitdaging` record per round; preserves each piece's challenge linkage. Full brief/deadline only where old data exists. |

---

## Epic 12A — Challenge adjudication automation

> **NEW epic — sprint change 2026-06-20 (proposal §4.D / §2.2). The largest net-new build; a second launch pillar (editorial-burden automation).** Sub-epic of Epic 12 (Uitdagings). Reuses the `ink_entries` custom table, the Challenges module (judge-email composer + paste-text parser), the Tiers module (5.8), Notifications (12A.0), and the home featured slot (15.6).
>
> **Hard build order (all P0): R1 → R2 → R3.** R1 assigns & stores the per-type `EntryID`; R2 matches pasted results against stored `EntryID`s; R3 (Epic 5.8) auto-promotion is triggered as R2's final step and consumes R2's placement records. **The EntryID data model (12A.1) is the linchpin and must land first.** No `.docx` parser — results are **pasted as plain text** (owner decision; removes the PhpWord/XXE/zip-bomb surface).

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 12A.0 | **Form-letter + notification capability (foundation)** | K | P0 | **[2026-06-20]** Early lightweight foundation — **not** a rich template engine. Stored form-letter text (WP options) + name-merge greeting (e.g. "Beste {skrywer}, …") + per-event send on/off toggles + a randomized message list. Owned by the Notifications module (expands from BP-only to transactional email). **Consumed by R2 (12A.3/12A.4), R3 (5.10), R5 (4.8) and R7 (9.11).** Leak-scan (NFR-1 / 17.x) must extend to this option store — admin-authored text is not covered by build-time `.mo`. |
| 12A.1 | **EntryID data model (R1 linchpin)** | K | P0 | **[2026-06-20 / R1]** New column(s) on the existing `ink_entries` custom table: `entry_type` + `entry_number`. Per-type sequence (Gedigte/Stories/Artikels numbered separately), **assigned at collation time** (not at entry time). Internal/admin concept; an Afrikaans UI label is human-written only if ever surfaced. **Must land before R2.** |
| 12A.2 | **Judge-email collation tool (R1)** | K | P0 | **[2026-06-20 / R1]** Auto-collate a round's entries into an anonymized judge email; assigns + stores the EntryID (12A.1). Replaces today's manual collation (real artifact: `INK Mei projek inskrywings.eml`). wp-admin screen (WP chrome, Afrikaans `ink-core` labels) — flow + states in the **Admin-flow acceptance criteria** below. |
| 12A.3 | **Paste-text results ingestion + coverage report (R2)** | K | P0 | **[2026-06-20 / R2]** User **pastes plain text** of the judges' results & commentary; parser matches against stored EntryIDs (12A.1). **No `.docx` parser.** Produces a **coverage report** (which entries matched / unmatched / "Geen"). Final step **triggers the R3 auto-promotion engine (5.8)**. Input artifact: the real judges' results document. wp-admin screen (WP chrome, Afrikaans labels) — flow + states in the **Admin-flow acceptance criteria** below. |
| 12A.4 | **Winners-announcement post generation (R2)** | K/T | P0 | **[2026-06-20 / R2]** Generates a wenneraankondiging post from a **simple form-letter template** (12A.0), featured home slot, entry index with links. |
| 12A.5 | **Moderator-feedback comment type + writer display toggle (R2)** | K/T | P0 | **[2026-06-20 / R2 / C5]** Custom structured `comment_type = ink_moderator_terugvoer` ("Terugvoer van die moderator") written via `wp_insert_comment`. **Not** a re-enabled WP comment. Visible on a work only when the writer enables it on My Profiel. Sanctioned exception to the Gemeenskapsreaksies "only feedback path" rule. |
| 12A.6 | **Winner banner — per-rank / per-tier variants** | T | P0 | **[2026-06-20 / R2 / C9]** Base design already exists (home page "December Winner" / `Desember-wenner`, `ui-copy-translations.md:80`). Define per-rank variants: **algehele wenner** (1st) vs **wenner** (2nd/3rd). Brons/Silwer/Goud colour tokens; **Meester = brand `primary #EA4015`**. Pair colour with text/icon (no colour-only rank encoding, a11y). Placement flag (`algehele wenner` vs `wenner`) extends `ink_entries` placement columns. |
| 12A.7 | **Featured-feed ordering** | K/T | P0 | **[2026-06-20 / R2]** `algehele wenner` (1st) gets more prominent placement in the featured feed than ordinary `wenner`s. Drives home featured ordering (see 15.6). |

### Admin-flow acceptance criteria

> **[2026-06-20]** These are wp-admin screens (WP chrome — Settings API / list table / custom admin page; **no front-end design system**). All `ink-core` labels, buttons, and status strings are **Afrikaans**. The criteria below pin down interaction flow + states; visual styling is whatever WP admin provides.

**12A.2 — Judge-email collation (R1)**
1. Lives under the **Uitdagings** admin menu. Editor selects an **uitdaging** from a list in **descending date order**.
2. On select, the system collates all entries linked to that round and **assigns the per-type EntryID** (Gedigte / Stories / Artikels each numbered from 1), **sorted by entry type → Gradering (Brons, Silwer, Goud) → EntryID**, and **persists** the EntryID (12A.1).
3. Generates an **editable preview** of the judge email: the **full challenge body text** first, then all entries ordered by type → Gradering → EntryID. Each entry shows **EntryID + title (both bold, on one line)**, a blank line, then the **full entry text**. The **writer's name and any copyright notice are stripped** (names appear above/below titles inconsistently — strip both positions).
4. Editor can **edit the preview text inline** before sending.
5. Editor enters one or more **recipient email addresses** and **sends**.
6. **States:** *no entries linked* → empty state, no send; *re-collation of an already-numbered round* → **idempotent**, must **not renumber or burn EntryIDs**; *send success / failure* → clear status. EntryID assignment is **deferred to collation** (re-entry before deadline must not consume a number).

**12A.3 — Results ingestion + coverage report (R2)**
1. Editor selects the same **uitdaging**, then **pastes the judges' results as plain text** into a textarea. **No `.docx` upload** (explicit owner decision).
2. Parser extracts (a) the **winners block** — top-3 per Gradering (Brons/Silwer/Goud) and per category, each identified by EntryID, with **"Geen"** allowed where there is no placement; and (b) **per-entry commentary** — keyed by EntryID + title, then the commentary text.
3. Produces a **dekkingsverslag (coverage report)** by retrieving **all stored EntryIDs** (12A.1) and reconciling: it must explicitly indicate **whether all winners were identified** and **whether commentary was resolved for every entry** — listing **matched / unmatched / "Geen"** and any **EntryIDs in the document that don't match** (and vice versa).
4. **Explicit confirm gate:** committing is **irreversible** (publishes a post, writes comments, promotes Graderings), so it is **blocked until the editor confirms** all categories are accounted for in both the winners list and the commentary.
5. **On confirm, in order:** (1) generate the **wenneraankondiging** post (12A.4); (2) write **Terugvoer van die moderator** responses per entry (12A.5); (3) Biblioteek update **stub** (R4 / 10.6); (4) mark winners + set banners and placement `algehele wenner`/`wenner` (12A.6); (5) add winners to the **featured feed**, `algehele wenner` first (12A.7); (6) **trigger the Gradering auto-promotion engine** (5.8).
6. **States:** *parse partial/failure* → coverage report shows gaps, commit stays blocked; *EntryID mismatch* (extra or missing) → flagged for reconciliation; *re-run after a successful commit* → **idempotent**, must **not double-post, double-comment, or double-promote**.

---

## Epic 12B — Annual competition management (R9)

> **NEW epic — sprint change 2026-06-20 (proposal §4.D / §2.2). P2 / post-launch.** Reuses the R1/R2/R3 machinery (Epic 12A + Epic 5.8) on an **annual** cadence rather than monthly. No new core mechanics — sequencing/cadence configuration over the existing adjudication pipeline.

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 12B.1 | Annual competition management | K/T | P2 | **[2026-06-20 / R9]** Annual-cadence competition reusing the EntryID collation (12A.1/12A.2), paste-text ingestion + coverage report (12A.3), winners post/banner/featuring (12A.4/12A.6/12A.7) and auto-promotion (5.8). Post-launch. *(Note: the source requirements doc mislabels this "R8"; it is R9.)* |

---

## Epic 13 — InkPols

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 13.1 | `inkpols_uitgawe` model | K | P1 | Structured meta: issue date, volume, cover image, PDF, teaser. |
| 13.2 | Issue archive (by year) | T | P1 | Clean archive + robust single-issue page. |
| 13.3 | PDF viewing | P | P1 | Real3D Flipbook (reactivate). No individual-article extraction. |
| 13.4 | Back-catalogue migration | K | P1 | Re-link existing PDFs; replace month/year naming with date+volume meta. |

---

## Epic 14 — Sponsors (Borge)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 14.1 | `borg` CPT | K | P1 | Fields: name, logo variants, link, `sponsor_tier`, campaign start/end, placement preferences. |
| 14.2 | Scheduling / rotation logic | K | P1 | Campaign dates drive display; rotation. |
| 14.3 | Homepage sponsor placement | T | P1 | One featured or rotating sponsor; subtle strip. No logo dumps on content pages. |
| 14.4 | Sponsor recognition page | T | P2 | Full recognition on Oor INK. |

---

## Epic 15 — Organisation pages & contact

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 15.1 | Tuisblad | T | P0 | Hero spotlight, challenge section, featured works, sponsors, CTA (reference-ready). |
| 15.2 | Gemeenskap page | T | P1 | Visitor conversion/marketing (value props, principles, how-it-works, CTAs). |
| 15.3 | Oor INK | T | P1 | Mission, contact, sponsors, org pages. Assembly-only. Use placeholders for founding year + SA legal status; pre-launch content gate (§14.4). |
| 15.4 | Kontak | T+P | P1 | Form page — **custom `ink-core` form** (resolved; no CF7 / Fluent Forms — PRD OQ-8). |
| 15.5 | Footer / social links | T | P1 | Theme-native pattern (replaces Ultimate Social Media Icons). |
| 15.6 | **Winners-post featured slot + featured-feed ordering (R2)** | T | P1 | **[2026-06-20 / R2]** Home featured slot hosts the auto-generated wenneraankondiging (12A.4); featured-feed ordering puts **algehele wenner first**, ahead of ordinary wenners (drives/consumes 12A.7). |

*(Auth flows moved to Epic 3 — accounts are a foundational prerequisite, not an org-pages concern.)*

---

## Epic 16 — Migration & redirects

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 16.1 | DB clone & sanitise | — | P0 | Strip transients/logs; clean baseline. |
| 16.2 | User import + role reassignment | K | P0 | Single member base role (no reader/writer distinction — PRD FR-2); profile-field cleanup; drop legacy Youzify/BP roles. |
| 16.3 | Tier CSV import | K | P0 | Email join key; defaults + flags for edge cases. |
| 16.4 | Subscription verification | P | P0 | Confirm memberships/plan IDs/access rules/expiry on new host. No import. |
| 16.5 | Post → CPT reclassification | K | P0 | Category-driven; `skryfwerk` catch-all (**do not hand-classify at volume** — holding bucket); flush rewrite rules. Old-site CPT disposition (§11): `inkpols`→`inkpols_uitgawe` rename; `monthly_challenge` placeholder **not** migrated 1:1 — `uitdaging` CPT records are built from challenge-round categories (16.8/12.8), real `monthly_challenge` data folded in, else dropped. |
| 16.6 | Library/training migration | K | P1 | By URL sub-path → CPT + taxonomy terms. |
| 16.7 | Redirect generation | K+P | P0 | 301s recorded during CPT migration; keep `/biblioteek/`,`/opleiding/` prefixes; verify by crawl. |
| 16.8 | InkPols / sponsors / nav | K/T | P1 | Per §11/§13/IA; nav rebuilt fresh. |
| 16.9 | BuddyPress data + friendship→follow | K | P1 | Convert each **confirmed** friendship → two mutual follows (dedup; skip orphaned; pending not converted — PRD MR-8); trim old activity; messaging deferred (§14.7). |
| 16.10 | Media verification | — | P1 | Uploads accessible; audio/video play; PDFs open. |
| 16.11 | Options carry-forward (selective) | — | P1 | No wholesale `wp_options` clone. |
| 16.12 | WPBakery shortcode cleanup | K | P1 | Grep `[vc_*]`; strip/convert; none rendered as raw text. |

---

## Epic 17 — Afrikaans-first & localisation (execution + QA)

> **Afrikaans-first is a foundational, cross-cutting principle, not late-stage work.** Its foundational enablers (locale `af`, i18n scaffolding, admin-language mechanism) are built in **Epic 1 (feature 1.10, P0)**; it is also a standing cross-cutting acceptance criterion (item 3 below) and Quality Gate D on every epic. This epic holds only the *execution and QA* that runs across and after surface-building. **Its high epic number reflects when this execution work completes, not the principle's priority** — the principle binds every epic from Epic 1 onward (see the ordering note at the top of this document).

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 17.1 | Apply approved UI copy | T | P0 | From `ui-copy-translations.md`; resolve placeholder org details. |
| 17.2 | Residual plugin Afrikaans pass | P | P1 | Theme & `ink-core` are Afrikaans-native. Translate only surviving third-party plugin strings (BuddyPress, WC/Memberships/PayFast; + Report Content/CF7 if kept) via Loco as authoring tool **on staging**; **commit `.mo` to version control** (production loads them without Loco installed — the committed bundle is the safety net); use w.org language packs where complete. New strings from ungated updates are caught by the English-leak scan (§13 / 18.8), fixed on staging, and redeployed — **not hand-edited on production** (§14.13). Human-authored. **Cover all leak vectors (§12), not just template chrome:** error/validation/status messages, dynamically composed plugin sentences (BP notifications, WC order/membership phrasing), **transactional emails** (Woo order/renewal/expiry, BP, password reset), **plugin JavaScript strings** (e.g. Real3D viewer controls — needs the plugin's JS `.json` translations, separate from `.mo`), and out-of-band outputs (REST/AJAX, redirect-notice args, feeds). |
| 17.3 | Terminology reconciliation | — | 🔄 Reopened 2026-06-20 (G1) | **Previously ✅ 2026-06-14** (follow vocabulary; Storie code ID → `storie`; tier values → `brons`/`silwer`/`goud`; "Volgers" → "volgelinge"). **REOPENED by owner decision 2026-06-20 (G1):** `intekening`/`intekenaar`/`intekenlid` → **lidmaatskap** / **betaalde lid** / **gratis lid**; UI tier term → **Gradering** (never "tier"); **Skrywerprofiel = PUBLIC**, **My Profiel = PRIVATE** (fix "Skrywersprofiel" → "Skrywerprofiel"); new terms EntryID, algehele wenner / wenner, Terugvoer van die moderator, Meester. **Corrections propagate to ALL docs**, not just the active file (project memory rule). `afrikaans-terms.md` updated first as source of truth. |
| 17.4 | No-English-leakage QA gate | T | P1 | Gate D on every **front-end** template/pattern + user-facing emails. Scope includes the §12 leak vectors (status/validation messages, transactional emails, plugin JS strings, REST/AJAX/feeds), not just static template copy — pairs with the automated English-leak scan (18.8). Admin excluded — stays English (§14.14). Depends on the foundational i18n scaffolding (1.10). |

---

## Epic 18 — SEO, security & performance

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 18.1 | Rank Math config + CPT schema | P | P1 | Adopted from the start. *Per-post* Yoast enrichment is negligible (owner's assessment: only a few InkPols OG images); global Yoast config not carried forward by design. **Deliberate override** of `plugin-transition-guide.md`'s "keep Yoast through migration" (§14.11). Sitemaps, meta, breadcrumbs, native schema for `gedig`/`storie`/`artikel`. Rank Math importer runs **as a safety net regardless**; verify InkPols images, then deactivate Yoast. Templated defaults, not per-post backfill. |
| 18.2 | Redirect integrity | P | P0 | All old URLs → correct 301; 404 tracking. |
| 18.3 | Security stack (layered) | P | P1 | **Resolved (§14.16):** Cloudflare (edge + login rule, origin locked) + staff 2FA + Patchstack (CVE alerts) + staging-gated updates (18.7) + **host-provided malware scanning**. Loginizer retired (Cloudflare covers login); no WordFence. Patchstack new. PayFast off-site → low PCI scope. |
| 18.4 | Moderation/report path | P/K | P1 | **Custom `ink-core` report form** (resolved; no third-party Report Content — PRD OQ-4). |
| 18.5 | Caching layer | P | P1 | LiteSpeed Cache (NameHero runs LiteSpeed) + Cloudflare edge caching (§14.9). |
| 18.6 | Production hygiene | — | P0 | No dev/diagnostic/migration plugins active on production. |
| 18.7 | Update governance & i18n resilience | P/K | P1 | Gate major core/plugin updates via staging where possible (regression on custom templates + translation refresh). Rely on auto language packs for core/well-covered w.org plugins; committed `.mo` for premium plugins (Memberships/PayFast/Real3D/Report Content), re-checked after their updates. Run production-side detection for new untranslated strings (English-leak scan); author fixes on staging, commit, and redeploy — **Loco is not installed on production** (§14.13). No-English-leakage is a standing requirement. |
| 18.8 | Full test suite buildout | K | P1 | Decision: §14.17. **Builds on the Foundation-phase scaffold (1.11)** — extends it to the full pyramid: **unit** (Pest/PHPUnit + Brain Monkey/WP_Mock) for `ink-core` rules; **integration** (`wp-env` + WP test lib / wp-browser) for plugin seams (membership⇒submit, expired⇒denied, tier⇒meta+log); **E2E** (Playwright) for critical journeys (register→buy via PayFast sandbox→submit→publish→read→renew). CI per change + E2E smoke on staging deploy. Risk-based depth (smoke for minor, full for major). Includes automated English-leak scan. Concentrate in `ink-core`; theme via E2E/visual. |
| 18.9 | **Analytics-provider selection (R8)** | P | P1 | **[2026-06-20 / R8]** Select an analytics provider — **none exists today** anywhere in the plan. New vetted-plugin seam. Sharpens the deferred POPIA question (OQ-3); read counts surfaced on My Profiel via 9.12. |
| 18.10 | **Registration anti-spam hardening (R6)** | P/K | P1 | **[2026-06-20 / R6]** Extends the security stack (18.3) with the registration anti-abuse surface from the 3.4 spike + optional pending-approval state (3.6). |

---

## Cross-cutting acceptance criteria (apply to every epic)

1. **Three-layer compliance** — no business logic in the theme.
2. **Token compliance** — no hardcoded colours/spacing/unnamed type sizes (Gate A).
3. **Afrikaans-first** — correct terms, no English leakage (Gate D).
4. **Gradering ≠ lidmaatskap** (THE conflation rule) — never conflated; `Ink\Tiers` ⟂ `Ink\Entitlement`.
5. **Editorial low-friction** — no mandatory per-item manual linking.
6. **Site Editor stability** — non-technical staff can manage content; critical structure locked.

---

## Summary

| Epic | P0 features | Total features |
|---|---|---|
| 1 Foundation | 7 | 11 |
| 2 Content models | 3 | 5 |
| 3 Accounts & auth | 2 | 6 |
| 4 Lidmaatskap & payment | 4 | 11 |
| 5 Writer Gradering | 5 | 10 |
| 6 Submission | 4 | 9 |
| 7 Reading & engagement | 1 | 8 |
| 8 Discovery | 1 | 5 |
| 9 Community & social | 2 | 12 |
| 10 Library | 1 | 6 |
| 11 Training | 0 | 5 |
| 12 Challenges | 1 | 8 |
| 12A Challenge adjudication automation **(NEW)** | 8 | 8 |
| 12B Annual competition **(NEW, P2)** | 0 | 1 |
| 13 InkPols | 0 | 4 |
| 14 Sponsors | 0 | 4 |
| 15 Org pages | 1 | 6 |
| 16 Migration | 6 | 12 |
| 17 Afrikaans-first | 1 | 4 |
| 18 SEO/security/perf | 2 | 10 |
