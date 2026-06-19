---
name: INK
description: Experience spine for INK — information architecture, behavior, states, interactions, accessibility, and key flows for the Afrikaans community publishing platform.
status: final
sources:
  - {planning_artifacts}/prds/prd-ink-vibe-2026-06-14/prd.md
  - {project_knowledge}/specs/ink-consolidated-spec.md
  - {project_knowledge}/design-handoff/page-map.csv
  - {project_knowledge}/ui-copy-translations.md
  - {project_knowledge}/afrikaans-terms.md
  - {project_knowledge}/mockup-readiness-assessment.md
updated: 2026-06-15
---

# INK — Experience Spine

> How INK works. Owns information architecture, behavior, states, interactions, accessibility, and journeys. Visual identity (color, type, components' appearance) lives in `DESIGN.md` and is referenced here by `{token}` name. This spine wins over any Lovable mock on conflict. All domain nouns are the verbatim Afrikaans terms from `afrikaans-terms.md` — they are load-bearing.

## Foundation

**Form-factor: multi-surface responsive web** (desktop + mobile parity), a public content & reading site. No native app, no PWA, no offline (every `page-map.csv` row carries explicit desktop/mobile responsive notes; nothing in source signals offline).

**UI system — production target:** a custom **WordPress FSE block theme** (`ink-foundation`: templates, template-parts, block patterns, block styles, `theme.json`) for presentation, plus the **`ink-core` plugin** for all business logic and content models. Three-layer separation is non-negotiable: theme = presentation; `ink-core` = INK rules; vetted platform plugins (BuddyPress scoped, WooCommerce + Memberships, PayFast, Real3D Flipbook, Rank Math) = commodity capability.

**Design intent only (not runtime):** the Lovable mockup (React + Tailwind + shadcn/ui) is a layout + visual-system reference. Re-express its intent in WordPress primitives — never port JSX/Tailwind/shadcn/react-router, never treat its mock data/`localStorage` (`readerStore`) as the data model, never lift its English placeholder copy. React interactivity (line resonance, tabs, follow toggle) → WP Interactivity API or small enqueued JS, with business logic in `ink-core`.

**Brownfield:** existing WordPress install + cloned DB. Site locale `af`. Members, subscriptions, content, and media survive migration — never assume a clean install.

## Information Architecture

Top-level public nav (Afrikaans, sentence-case): **Tuis · Ontdek · Opleiding · Uitdagings · Gemeenskap · My profiel · Begin skryf**. Footer adds: Ondersteun ons (Word 'n borg / Skenk / Word 'n vrywilliger), Meer oor INK, Nuusbrief.

`[NOTE]` "Lees" is **not** a nav section — it merged into **Ontdek** (decided 2026-06-14). "Lees" survives only as the reading *action* ("Begin lees") and the single-piece detail pages. Curated/featured reading lives on the Tuisblad. URL prefixes `/biblioteek/` and `/opleiding/` are preserved through migration (301s).

### Public / visitor surfaces

| Surface (`page_slug`) | wp_target | Purpose | Data | Readiness |
|---|---|---|---|---|
| **tuisblad** | `front-page` | Editorial home: hero spotlight, current **uitdaging**, the latest **wenneraankondiging** post in a **featured slot**, featured **bydraes** (Die redakteur se keuse), **borg** strip, CTA band. Featured-feed ordering places **algehele wenner** (1st) before **wenner** (2nd/3rd) — see Component Patterns | featured streams | reference-ready |
| **lees-storie** | `single-storie` | Read a **storie**: prose body (~768px), critique panel (Gemeenskapsreaksies), highlightable text, floating action bar | `storie` | reference-ready |
| **lees-gedig** | `single-gedig` | Read a **gedig**: stanza-aware poetry body, per-line resonance, critique panel | `gedig` | reference-ready |
| **opleiding** | `page-opleiding` | Training hub: query-loop of **opleiding_artikel**, accordion, faceted by **vaardigheid** | `opleiding_artikel` | layout-reference |
| **biblioteek** | `page-biblioteek` | Curated **Biblioteek**: featured strip + category filter + search + grid; date-archive, author-filter, pagination | `biblioteek_item` | partial (gaps) |
| **uitdagings-list** | `page-uitdagings` | Challenges list: query-loop + countdown | `uitdaging` | partial |
| **uitdagings-single** | `single-uitdaging` | A **uitdaging**: prompt, literary devices, rules, prize, deadline, resources, submission list | `uitdaging` | reference-ready |
| **skrywerprofiel** (Skrywerprofiel — **public**) | `single-skrywer` | Public writer profile: cover, works-grid, accomplishments, **Gradering** (Brons/Silwer/Goud and the new **Meester** tier), reader rating, follower stats, pinned works | user + posts | reference-ready |
| **ontdek** | `page-ontdek` | Discovery: tabs (Bydraes / Skrywers), query-loop, filters, sort, search (absorbs former "Lees") | posts + users | reference-ready |
| **gemeenskap** | `page-gemeenskap` | Visitor conversion/marketing page (community features live on profiles): value props, principles, how-it-works, CTAs | page | reference-ready |
| **oor-ink** | `page-oor-ink` | Mission, contact, sponsors (**borg**), org pages | page + borg | assembly-only |
| **kontak** | `page-kontak` | Contact form (custom `ink-core`), map | page | assembly-only |
| **lidmaatskap** | `page-lidmaatskap` | **Lidmaatskap** acquisition: pricing table, plans, benefits, FAQ → PayFast. At launch this is **terminology only** (lidmaatskap / betaalde lid / gratis lid); the **recurring (auto-renew) opt-in is post-launch** — do not surface a recurring toggle at launch | WC Memberships | assembly-only |
| **InkPols** | `archive-inkpols_uitgawe` / `single-inkpols_uitgawe` | Issue archive by year + single-issue flipbook reader (Real3D) | `inkpols_uitgawe` | not-yet-mocked `[ASSUMPTION]` layout |

### Auth-gated / member surfaces

| Surface | wp_target | Purpose | Gating |
|---|---|---|---|
| **auth** | `login` / `register` / `forgot-password` | Registreer / Meld aan / Wagwoord-herstel — single-column forms, built fresh in WP; **social-login buttons** (R6) + an **optional, off-by-default "wag op goedkeuring" (awaiting-approval) pending state** | visitor → lid |
| **skryf** (Begin skryf) | `page-skryf` | Submission: content-type selector (Gedig/Storie/Artikel), light editor, challenge-link, Stoor konsep / Plaas | **Plaas** requires an active paid **lidmaatskap** (**betaalde lid**); **Stoor konsep** is ungated |
| **my-profiel** (My Profiel — **private**) | `page-my-profiel` | Own **private** profile, tabbed: Oorsig · Bydraes · Leeslys · Wie ek volg · Aktiwiteit · Kennisgewings · Lidmaatskap. Surfaces private data: Gradering "wins nodig" subteks (R3), read counts (R8), and the **Terugvoer van die moderator** display toggle per work | lid (own account) |

### Roles (access layers, not separate surfaces)

- **besoeker** (visitor): read public **bydraes**, browse Ontdek, view profiles. Cannot react, respond, save, follow, rate, or publish.
- **gratis lid** (free member): all engagement — read, **reaksie**, **Gemeenskapsreaksie**, **leeslys**, **volg**, reader rating/review. Has an account; **not** entitlement-gated. Cannot *plaas*.
- **betaalde lid** (paid member): free-member rights **plus** submission entitlement (*plaas*) via an active paid **lidmaatskap**.
- **skrywer** (writer): a member who publishes; carries a **Gradering** (Brons/Silwer/Goud/**Meester**). Behavioral, not a signup choice.
- **redakteur** (editor): editorial + challenge/winner admin, **Gradering bevordering** + **graderingsgeskiedenis** log, sponsor mgmt, moderation, plus the new admin surfaces (judge-email collation, results ingestion, lifecycle-email config, account-approval queue, manual Meester promotion — see Admin Surfaces below). Works in **English WP admin chrome** but sees **Afrikaans `ink-core` labels**.
- **administrator**: technical control.

`[NOTE]` There is **no "reader or writer?" choice** at registration (FR-2). One simple account; writing is unlocked by subscribing, not by a role pick.

→ Composition reference: mocks in `mockups/` (rendered at finalize for reference-ready surfaces). Spine wins on conflict.

### Admin surfaces (redakteur) — WP admin chrome (no design-system work)

`[NOTE]` These admin screens live inside the **WordPress admin (wp-admin)** and are rendered with WP's own admin chrome (Settings API / list tables / meta boxes / `@wordpress/components`). They **do not use the front-end design system** — no `theme.json` / Lovable tokens, palette, or mockups apply, so there is **no design-system / visual-design work** here. Every `ink-core` field, label, button, and status string is **Afrikaans** (the i18n boundary holds in admin too — see Concerns); admin chrome itself stays English.

What *does* need pinning down for the multi-step, stateful screens (R1's editable preview; R2's parse → coverage report → confirm gate → irreversible actions) is **interaction flow + states + acceptance criteria**, not visual design. That detail is captured in the **Epic 12A story acceptance criteria** (`ink-feature-list.md`), not here. The table below is a flow reference only.

| Admin surface | Req | Purpose & flow (reference — ACs in Epic 12A) |
|---|---|---|
| **Beoordelaar-e-pos kollasie** (judge-email collation) | R1 | Select an open/closed **uitdaging** → system collates entries and assigns the per-type **EntryID** → an **editable anonymized preview** of the judge email (names stripped, `EntryID`-keyed) → choose recipients → send. Form-letter text with greeting name-merge; no rich template engine. |
| **Uitslae-invoer** (results ingestion) | R2 | **Paste plain text** of the judges' results + commentary (no `.docx` upload) → parse against stored `EntryID`s → a **dekkingsverslag (coverage report)** showing matched / unmatched / "Geen" so the editor can reconcile before committing. On commit: generates the **wenneraankondiging** post, writes **Terugvoer van die moderator** responses, sets winner banners + placement (`algehele wenner` / `wenner`), and triggers Gradering recalculation (R3). |
| **Lewensiklus-e-pos konfigurasie** (lifecycle-email config) | R5 | Per template (thank-you, 1-month-prior warning, 1-week-prior warning), per term (1 / 6 / 12 maande), on/off toggles + the form-letter text. Recurring-renewal variant is post-launch. |
| **Rekening-goedkeuring-tou** (account-approval queue) | R6 | Optional, off-by-default review queue for pending registrations (paired with anti-spam + social login). When on, holds new accounts in the "wag op goedkeuring" state until a redakteur approves/rejects. |
| **Handmatige bevordering** (manual promotion) | R3 | Extends the existing **UJ-5** promotion flow so a redakteur can record a manual **bevordering**, including the manual-only terminal **Meester** Gradering (never auto-promoted). Writes the same auditable **graderingsgeskiedenis** log. |

## Voice and Tone

Microcopy. Brand voice and aesthetic posture live in `DESIGN.md`. **Afrikaans-first — the entire front end and transactional emails are Afrikaans; zero English leakage** (standing automated gate). English in any source is placeholder only. Never lift Lovable copy; never AI-/machine-translate the curated Afrikaans (human-authored only). Address the member as **"jy"**. Terminology discipline from `afrikaans-terms.md` is binding (Biblioteek not "library"; uitdaging not "challenge"; Ontdek not "Browse/Blaai"; reaksie not "like"; volgeling not "volger"; plaas not "submit").

| Do | Don't |
|---|---|
| "342 hartjies" (count, no verb — the icon does the work) | "342 people liked this" |
| "Begin skryf" (sentence case) | "Begin Skryf" / "Start Writing" |
| "Ons prys spesifiek, stel saggies voor, en trap nooit op mense nie." | Harsh or gatekeeping critique framing |
| "Weerklank bo bereik" — resonance over reach | "Save 25%!" / discount / vanity-reach framing |
| "Jou stem hoort hier." — developmental, welcoming | "Upgrade now to unlock!" upsell pressure |

Sample strings (verbatim): Hero *"Stories wat verdien om gelees en gekoester te word"* · motto *"Waar woorde lesers vind"* · CTAs *"Begin lees" / "Deel jou werk" / "Plaas" / "Stoor konsep" / "Skryf in"* · Gemeenskapsreaksie prompt *"Deel 'n deurdagte reaksie — wat jou geraak het, wat jou verras het, of wat nog sterker kon gewees het."* · publish success *"Jou [gedig/storie/artikel] is gepubliseer"* · empty *"Nog niks op hierdie rak nie."*

`[NOTE]` `ui-copy-translations.md:346` (*"Weerklank wen van bereik…"*, and line 353 *"niewinsgericht"*) is **broken Afrikaans flagged for human re-translation** — do NOT treat as final and do NOT AI-fix (OQ-12).

## Component Patterns

Behavioral. Visual specs live in `DESIGN.md.Components`.

| Component | Behavioral rules |
|---|---|
| **Gemeenskapsreaksies** | The ONLY feedback path (WP comments disabled site-wide), with **one sanctioned exception** (see Terugvoer van die moderator below). A **lid** posts a response carrying exactly one type: **Lof** / **Insig** / **Voorstel**. Not entitlement-gated. Replaces free-form comments by design (no free-for-all). |
| **Terugvoer van die moderator** (C5) | The **only sanctioned exception** to "Gemeenskapsreaksies is the ONLY feedback path". A **structured programmatic response** written by adjudication (R2 ingestion) — **not** an open comment box and **not** native WP comments (stored as a custom `ink_moderator_terugvoer` comment type). It appears on a work **only when the writer enables it**, via a per-work **display toggle the writer controls from My Profiel**. |
| **Line highlight + reaksie** | A **lid** selects text on a **bydrae** ("Merk hierdie reël") and attaches a **reaksie**: **hartjie** / **duim op** / **wow**. Highlighting yields *reactions only* — no public inline annotation. On **gedig**, this is per-line "resonance" (heart tap) on content lines only (not blank separators). aria: *"Merk hierdie reël"* / *"Verwyder merk"*; toast *"Jy het [N] reël(s) gemerk"*. |
| **Reaction counts** | Stored; shown as count without verb ("342 hartjies"). Locale-correct plurals via `_n()` for `af` on every count surface (n=1 singular, n≠1 plural, n=0 handled). |
| **Leeslys (reading list)** | Save/remove a **bydrae** with confirmation toasts; surfaced on profile Leeslys tab. Quiet signal of what's worth reading. |
| **Suggested reads** | Next reads by tone/form/topic/tier via shared `genre`/`vaardigheid` taxonomy — NO manual linking. An item sharing no term surfaces nothing. (P2) |
| **Ratings & reviews** | A **lid** gives an aggregate reader rating + written review on a **Skrywerprofiel** ("Lesergradering"). Moderation path + POPIA public-exposure apply. |
| **Volg (follow)** | Asymmetric, one-way; no reciprocity. Toggle **Volg** ↔ **Volg tans**; counts use **volgeling/volgelinge** (never "volger"). Custom in `ink-core` (no native BP follow). Following surfaces a writer's new work in the follower's **Aktiwiteit** tab. |
| **Submission (Skryf)** | Content-type selector → type-appropriate placeholders + counters (lines AND words for gedig; words for prose) → light editor (hard breaks, blank-line/stanza preservation, bold, italic only; line structure preserved verbatim so concrete poetry survives; no headings/tables/images/font controls) → optional featured image + optional audio/video → optional link to an open **uitdaging** → **Stoor konsep** (ungated) or **Plaas** (entitlement-gated at publish moment). |
| **Gradering indicator (ster gradering)** | Brons/Silwer/Goud and the manual-only **Meester** Gradering shown on the public **Skrywerprofiel**, used in discovery filters and winner labels. UI never says "badge"; UI term is **Gradering**, never "tier". Per-rank/per-tier colours are paired with text/icon so rank is never conveyed by colour alone (a11y) — see `DESIGN.md`. **My Profiel (private)** additionally shows the "X top 3 uitslae nodig om [next] te bereik" subteks toward the next Gradering (R3); Meester has no such subteks (manual-only). |
| **Winner banner** (C9) | Already designed on the home page (*The Last Light of Winter* / "Desember-wenner"). Per-rank variants: **"[Maand] algehele wenner"** for 1st-place vs **"[Maand] wenner"** for 2nd/3rd. The `algehele wenner` is ordered ahead of plain `wenner` in the featured feed. Colour tokens (Brons/Silwer/Goud) pair with text/icon — see `DESIGN.md`. |
| **Pinned works** | A **skrywer** curates pinned works on profile (label "Vasgespeld"). |
| **Flipbook (Real3D)** | InkPols **uitgawe** PDFs via Real3D; viewer controls are plugin JS, Afrikaans via JS `.json` translations. Known accepted exception to light-JS and accessibility goals. |
| **Kennisgewings** | "Merk alles as gelees" marks read by **timestamp boundary** (items arriving during the action stay unread — no phantom-unread). Templates: *"[Naam] en nog [N] ander het '[titel]' liefgehad"* · *"[Naam] het terugvoer gelewer op '[titel]'"* · *"[Naam] volg jou nou"* · *"[Uitdaging] sluit oor [N] dae"*. **New (R7):** an **automatic post-receipt trigger** fires a kennisgewing (→ My Profiel) on every new **bydrae**, using a **randomized message** drawn from a stored list (not a single fixed string). |

## State Patterns

| State | Treatment |
|---|---|
| **Empty** | "Nog niks op hierdie rak nie." + "Probeer 'n ander soekterm of blaai deur alle artikels." (Biblioteek/Opleiding); "Jy volg nog niemand nie" + "Volg 'n skrywer om hul nuwe stukke in jou aktiwiteitsvoer te sien." Post-signup first-action prompt degrades gracefully on a thin catalogue and stays skippable. |
| **Loading** | `[ASSUMPTION]` No loading/skeleton copy in source. Proposed: lightweight skeleton matching layout on reading/discovery surfaces; confirm. |
| **Success** | Publish success screen ("Jou [gedig/storie/artikel] is gepubliseer"); confirmation toasts (leeslys, follow, profile update); "Jou bydrae is geplaas."; "Jou inskrywing is ontvang."; "Jou lidmaatskap is aktief. Jy kan nou werk plaas."; promotion "Baie geluk! Jy is na Silwer bevorder." |
| **Error / payment failed** | "Jou betaling het misluk of is gekanselleer." Afrikaans status for active/expired/denied/payment-failed required. |
| **Permission denied (publish)** | "Slegs betaalde lede kan werk plaas. Sien aansluitingsopsies." + link to plans. (Commenting while logged out: "Jy moet aangemeld wees om kommentaar te lewer.") `[NOTE]` Banned-term sweep: replace any lingering "intekenlede"/"intekenopsies" with "betaalde lede"/"aansluitingsopsies". |
| **Pending approval (optional, off by default)** | `[NEW — R6]` When the optional account-approval backstop is enabled, a new registration lands in a "wag op goedkeuring" state until a redakteur approves it. **Off by default** to preserve the frictionless-signup posture (UJ-1). |
| **Expired subscription** | "Jou lidmaatskap het verval. Hernu om werk te plaas." Reminder (no auto-renew at launch): "Jou lidmaatskap verval binnekort." Expiry auto-revokes submission entitlement but does NOT delete account, change `ink_writer_tier`, or unpublish existing **bydraes**. |
| **Pending / editorial review** | After a **uitdaging** deadline, linked entries are frozen for judging — no edits to the judged version until results. A **konsep** saved while entitled but published after lapse is denied at publish, with the draft preserved. |

**THE CONFLATION RULE (binding — two separate state machines):**
- **Lidmaatskap status** (active paid **lidmaatskap** via WC Membership) gates **submission entitlement** (the right to *plaas*).
- **Gradering** (**ster gradering**, `ink_writer_tier`) gates **competition pools** (Brons/Silwer/Goud judging; Meester is manual-only and never a judging pool).
- Never conflated in data or code. *A paid Brons member ≠ a Brons writer with an expired lidmaatskap.* An expired Goud-Gradering **skrywer** is denied publishing. A lidmaatskap-state change has no write path to `ink_writer_tier`, and vice-versa.

**Migration states:** missing/ambiguous tier on import → default **brons** + a flag (never a guessed Silwer/Goud). Sponsor strip: no active sponsor → collapses gracefully; multiple → rotates.

## Interaction Primitives

Core verbs (from `afrikaans-terms.md` + user journeys):

- **Registreer / Meld aan / Meld af** — account lifecycle.
- **Plaas** — publish a **bydrae** (entitlement-gated). **Stoor konsep** — save draft (ungated).
- **Skryf in** — enter a **uitdaging** (writes **uitdagingsrondte**, only while open; ≤3 entries per content type per uitdaging).
- **Inteken** — buy/renew a **lidmaatskap** via PayFast (ZAR). (Recurring auto-renew is post-launch.)
- **Reageer** — post a **Gemeenskapsreaksie** (Lof/Insig/Voorstel).
- **Merk hierdie reël** — highlight a line + attach a **reaksie** (hartjie/duim op/wow).
- **Volg / Volg nie meer nie** (toggle **Volg tans**) — asymmetric follow.
- **Stoor na leeslys** / remove — with toasts.
- **Reader rating / review** — rate a **skrywer**.
- **Ontdek** — browse/filter/sort/search **bydraes** + **skrywers** ("Browse/Blaai" banned).
- **Search is diacritic-insensitive** (ê/ë/ô/î match base letters).

**Banned:** free-form comment boxes; the words "library/challenge/browse/like/volger/badge" in UI; vanity-reach or discount framing.

## Accessibility Floor

Behavioral. Visual contrast lives in `DESIGN.md`.

- **Readability-first floor (NFR-5):** Afrikaans legibility prioritized over decorative type; reading column ~768px; `body-prose` 18px / 1.7.
- aria-labels on interactive reading controls (line-resonance: *"Merk hierdie reël"* / *"Verwyder merk"*).
- **Decision (2026-06-15): readability floor, not a formal WCAG conformance target.** INK commits to the NFR-5 floor — keyboard-operable highlight/react/follow/save controls; visible focus states; semantic heading order; alt text on featured images and **borg** logos; sufficient contrast on the terracotta/cream/sage palette — without claiming a formal WCAG 2.2 AA conformance level for v1.
- **Known accepted exception:** Real3D Flipbook accessibility; a more accessible delivery (e.g. direct PDF) is deferred.

## Responsive & Platform

Multi-surface responsive web, desktop + mobile parity. `[ASSUMPTION]` No numeric breakpoints in source — derive from the Lovable source; the behaviors below are the contract:

| Viewport | Behavior |
|---|---|
| Desktop | Full nav; multi-column grids (wide 1400px); reading column held at 768px regardless of viewport. |
| Tablet | Tab strips reflow (my-profiel, ontdek); grids reduce columns; sections begin to stack. |
| Mobile | Single column; sections stack; nav collapses; logo scales; image crops keep parity with desktop intent; reading column full-width within margins. |

## Inspiration & Anti-patterns

- **Lifted from the Lovable mockup:** layout, hierarchy, spacing rhythm, token system, responsive intent, and interaction patterns (line resonance, tabbed profile, follow toggle) — as *intent*, re-expressed in WP.
- **Rejected — free-form comments:** replaced by structured **Gemeenskapsreaksies** (Lof/Insig/Voorstel). No comment free-for-all.
- **Rejected — friendships / mutual connections:** replaced by asymmetric **volg**. BuddyPress Friends/Groups/Messaging are OFF.
- **Rejected — vanity metrics & reach framing:** counts shown quietly, no verbs, no "viral" framing. Resonance over reach.
- **Rejected — discount/savings framing on membership:** removed by decision (FR-4).
- **Rejected — "reader vs. writer" signup choice:** one account; writing unlocked by subscribing.

## Key Flows

Named-protagonist journeys (UJ-1…UJ-6 from PRD §2.3). `[ASSUMPTION]` Protagonists/journeys are illustrative and pending founder review (OQ-11).

### UJ-1 — Marlie joins to share her first poem
*(Marlie, 34, has written privately for years; arrives unauthenticated from a Facebook share.)*
1. Lands on a public **gedig**; reads it fully as a **besoeker** — no wall.
2. Hits **Registreer**; completes a simple profile — **no "reader or writer?" choice**.
3. Prompted to a soft, skippable first social action (follow a **skrywer** / save a **bydrae** to her **leeslys**).
- **Climax:** a warm Afrikaans welcome — no upsell, no forced role choice. She's simply in.
- Resolution: account at default **Brons** as a **gratis lid**, no paid **lidmaatskap** yet; nudged toward **Lidmaatskap** only when she tries to *plaas*.

### UJ-2 — Marlie subscribes and publishes
1. Clicks **Begin skryf** → publishing is gated → Afrikaans explanation + link to plans.
2. Buys a plan via **PayFast** (ZAR) → returns to an **active lidmaatskap** as a **betaalde lid** ("Jou lidmaatskap is aktief. Jy kan nou werk plaas.").
3. Opens **Skryf**, picks "Gedig" (line + word counters), writes in the plain-text editor, optionally links to an open **uitdaging**, clicks **Plaas**.
- **Climax:** a success screen invites her to read-and-respond to others — *"Gee 'n skrywer vandag 'n hupstoot."*
- Resolution: **gedig** published, discoverable in **Ontdek**, surfaced to her **volgelinge**.

### UJ-3 — Pieter gives a writer a meaningful response
*(Pieter, free **lid**, mostly reads.)*
1. Opens a **storie** from **Ontdek**; reads in the legible reading template.
2. Highlights a line that landed; taps a **reaksie** (hartjie).
3. Posts a structured **Gemeenskapsreaksie** of type **Lof**.
4. Saves the piece to **leeslys**; follows the writer (**Volg**).
- **Climax:** his encouragement is structured and kind *by design* — no free-for-all box to be unkind in.
- Resolution: the writer gets a **kennisgewing**; Pieter's **Aktiwiteit** now surfaces her next work.

### UJ-4 — Thandi enters the monthly challenge, judged in her tier
*(Thandi, **Silwer**-tier, active subscriber.)*
1. Opens the current **uitdaging** (theme, rules, deadline, resources).
2. Writes an entry; links it to the round (**uitdagingsrondte**) at submission.
3. After the deadline (inclusive 23:59:59 SAST), entries freeze for judging; winners announced **per tier**.
- **Climax:** she's judged against her **Silwer** peers, not against **Goud** veterans.
- Resolution: a win is queryable ("Oktober Silwer-wenner") and shown on her profile; staff may record a **bevordering** linked to the result.

### UJ-5 — Elsa (redakteur) promotes a writer without busywork
*(Elsa, editor, in English WP admin seeing Afrikaans INK labels.)*
1. Reviews a challenge result → records a **bevordering** with a reason, optionally linked to the result. `[NEW]` Brons→Silwer (5 wins) and Silwer→Goud (15 wins) now auto-promote (R3); Elsa's manual flow remains for overrides and is the **only** path to the terminal **Meester** Gradering (never auto-promoted).
2. The promotion writes to an auditable **graderingsgeskiedenis** log (actor / date / reason / from→to / optional challenge link).
3. Training resources auto-surface beside relevant works via shared `genre`/`vaardigheid` terms — **no manual linking**.
- **Climax / Resolution:** tier and subscription stay strictly separate in the data; editorial effort stays low.

### UJ-6 — Migration day: Johan notices nothing broke
*(Johan, long-time paid member with published work + BuddyPress friends.)*
1. After cutover, his account, **active lidmaatskap**, published **bydraes**, media, and old URLs all still work (301s).
2. His old friendships are preserved as reciprocal **volg** relationships (each friendship → two one-way follows; *new* relationships are asymmetric).
3. He reads and responds exactly as before.
- **Climax:** preservation is invisible — nothing of value was lost.
- Resolution: the new Afrikaans-first UI reads as continuity, not disruption.

## Concerns

| Concern | Applies | Notes |
|---|---|---|
| **i18n (Afrikaans-first)** | **MAJOR — defining** | Whole front end + transactional emails Afrikaans; standing zero-leakage automated gate; admin chrome English but `ink-core` labels Afrikaans; human-authored only; diacritic-insensitive search; `_n()` plurals; banned-term discipline. |
| **Dark mode** | Deferred (v1) | Light mode only at launch (decided 2026-06-15). Dark tokens kept in DESIGN.md as future-ready scaffolding; full coverage + toggle mechanism out of scope for v1. |
| **Content density** | Yes | Reading column 768px, legibility-first; counts without verbs; responsive reflow per page. |
| **Accessibility** | Floor only (decided) | NFR-5 readability floor + resonance aria-labels; Real3D an accepted exception. No formal WCAG conformance claim for v1. |
| **Motion** | Minimal | Light front-end behavior only (avoid heavy JS where a pattern suffices). `[ASSUMPTION]` reduced-motion handling unspecified. |
| **Notifications** | Yes | BuddyPress Notifications ON (Friends/Groups/Messaging/site-wide Activity OFF); 4 triggers; timestamp-boundary "mark all read". |
| **Regulated/legal language** | Yes — gated | Org details ship as marked placeholders (`[stigtingsjaar]`, `[regstatus]`); "niewinsgerigte gemeenskapsorganisasie", never US "501(c)(3)". POPIA/public-exposure/moderation SLA = `[ASSUMPTION]`/deferred. |
| **Offline** | No | Out of scope. |

## Open Questions & Assumptions (triage at finalize)

- `[ASSUMPTION]` **InkPols** archive/single layouts (flipbook reader) — in IA + CPT model but not yet mocked.
- `[ASSUMPTION]` **Ledegids** (member directory, FR-43) — named but no page-map row or copy.
- `[ASSUMPTION]` **Auth-screen copy** — auth built fresh in WP; only nav verbs exist in `ui-copy-translations.md`.
- `[ASSUMPTION]` **Loading states** — no copy/pattern in source.
- `[ASSUMPTION]` **Moderation/report-form UX**, SLA, escalation — deferred (OQ-4, OQ-17).
- `[NOTE]` **OQ-12** — broken Afrikaans copy at `ui-copy-translations.md:346/353` awaits human re-translation; do not AI-fix.
- `[ASSUMPTION]` **UJ protagonists/journeys** pending founder review (OQ-11).
