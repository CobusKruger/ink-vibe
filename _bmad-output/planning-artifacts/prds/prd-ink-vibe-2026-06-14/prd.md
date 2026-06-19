---
title: INK
status: final
created: 2026-06-14
updated: 2026-06-20
---

# PRD: INK
*A community publishing platform for Afrikaans writers, poets, and readers.*

## 0. Document Purpose

This PRD is the requirements contract for the **INK** rebuild — a brownfield WordPress platform that preserves an existing membership, content library, and editorial process while re-expressing them on a clean three-layer architecture. Its primary audience is the **builder (Cobus) and the AI build agents** that drive the downstream BMad pipeline (UX → architecture → epics/stories → implementation).

It is structured as: Glossary-anchored vocabulary (§3 — every domain noun used verbatim throughout); Features grouped with globally numbered Functional Requirements (FR-N) nested and tagged by launch priority (P0/P1/P2); cross-cutting quality, integration, migration, and compliance clusters; explicit scope boundaries; and an indexed list of every `[ASSUMPTION]` for confirmation.

It **builds on, and does not duplicate**, the upstream planning artifacts, which remain authoritative for detail:
- `docs/specs/ink-consolidated-spec.md` — full narrative spec.
- `docs/specs/ink-feature-list.md` — 17-epic / ~109-story breakdown with priorities. FR traceability references it as `(FL <epic.story>)`.
- `docs/afrikaans-terms.md` — terminology source of truth (the glossary below is a load-bearing subset).
- `docs/ui-copy-translations.md` — approved Afrikaans UI copy and voice.
- `docs/migration-plan.md` — brownfield migration detail.
- `docs/design-handoff/` — Lovable design intent + normalised tokens.
- `_bmad-output/project-context.md` — binding implementation rules for AI agents.

Deep technical *how* (plugin-choice rationale, i18n leak-vector mechanisms, migration scripting order, rejected alternatives) lives in the companion **`addendum.md`**, referenced inline. This PRD states *what* and *why* in capability terms.

---

## 1. Vision

INK is the home for Afrikaans writers to **build their craft** — a literary *tuiste* (sanctuary), not a social network and not a marketplace. There are few places where Afrikaans writers can develop meaningfully: established literary sites serve already-published and academic work, while the practical alternative is a sprawl of Facebook groups with no quality gates and no feedback to help anyone improve. INK fills that gap by combining **structure, craft training, and feedback from academics and professionals**, with real quality gates — making it *indispensable to Afrikaans literary education*.

The product is a brownfield rebuild of an existing, active site that holds thousands of published contributions and a paying membership. The rebuild pursues four goals at once, while preserving everything of value: **preservation** (every contribution, account, membership record, and historical artifact survives), **Afrikaans-first UI** (no English word reaches a visitor or member), **clean three-layer architecture** (presentation, INK business logic, and commodity platform capabilities cleanly separated), and **automation**. Automation spans two co-equal launch pillars: **payment/membership automation** (front-end PayFast self-purchase and entitlement replace manual EFT) and **editorial automation** — challenge adjudication (judge-email collation and results ingestion), automatic **Gradering** (tier) promotion, and lifecycle/receipt notifications — that replaces the work the owner does by hand today. The earlier framing of automation as "front-end PayFast payment and tier tracking" alone understated this: editorial automation is now a launch pillar in its own right.

INK's wedge is *developmental*: it improves working writers through structured expert feedback and progression, distinct from the published-elite framing of existing literary sites and from the ungated, frictionless volume of social groups. Engagement features exist to serve **reading** and craft growth — giving members reasons to return to the built-up library — explicitly *not* feed-scrolling or reach-chasing: resonance over reach.

**Why now.** Membership tracking already runs on WooCommerce Memberships, but payment is still collected by manual EFT and activated by hand, writer **Graderings** live in a spreadsheet, and the entire challenge cycle — collating entries for judges, transcribing results, announcing winners, promoting writers, and sending receipts/expiry warnings — is done manually each month. PayFast is already installed yet unused. The front-end self-purchase flow wired to automated entitlement is one major piece of relief; automating the editorial/challenge cycle and the membership-lifecycle notifications is the co-equal other. Together they lift the manual-operations tax — no single change does it alone. Meanwhile the existing platform's business logic is entangled in theme glue and mismatched plugins, making continued growth fragile. The rebuild closes the payment gap, removes the recurring editorial burden, and puts the platform on a maintainable footing. Timing is driven by operational pain and technical debt, not an external deadline; no fixed launch date is set.

---

## 2. Target User

### 2.1 Jobs To Be Done

**Writers (skrywers) — functional, emotional, social**
- *Develop my craft* with structure and feedback I can't get from a Facebook group — not just a place to post.
- *Receive feedback that helps me improve* — specific praise, gentle suggestions, real insight — from peers and from academics/professionals, not unvetted noise.
- *Progress visibly* through a recognised tier (Brons → Silwer → Goud) and challenge wins, so growth is legible to me and to others.
- *Be read and resonate* — reach a thoughtful Afrikaans readership; *"'n deurdagte leser tel meer as 'n virale oomblik."*
- *Belong* to a warm, Afrikaans literary community that takes my work seriously, whether it's a polished piece or a brave first attempt.

**Readers (lesers) — functional, emotional**
- *Discover and read* quality Afrikaans writing, and come back to a growing library.
- *Encourage writers* lightly (reactions, structured responses) without the burden of "critique."
- *Curate* a personal reading list and follow voices I value.

**Editors / staff (redakteurs) — functional**
- *Run challenges, promote writers, and curate the library* with **low, sustainable editorial effort** — automation and shared taxonomy, never per-item manual linking.
- *Moderate* with a clear, logged report path.

**The organisation (INK)**
- *Replace manual operations* (EFT activation, spreadsheet tiers) with automated, auditable systems.
- *Preserve* the community's history and reputation through the rebuild.

### 2.2 Non-Users (v1)
- **English-language or other-language writing communities** — INK is Afrikaans-first by identity, not a multilingual platform.
- **Writers seeking professional publishing / agenting / paid editorial services** — INK is developmental community + training, not a Reedsy-style marketplace or a publishing house.
- **General e-commerce shoppers** — WooCommerce is used for memberships only; there is no storefront.
- **Learners seeking a formal LMS** — training is a resource hub, not courses/quizzes/certificates.

### 2.3 Key User Journeys

*Named-protagonist journeys the product enables. Numbered UJ-1..UJ-6. FRs reference them inline ("Realizes UJ-3"). Drafted under Fast path from the confirmed registration lifecycle and core flows — `[ASSUMPTION]` on specifics; please correct any beat that misreads how a real INK session goes.*

- **UJ-1. Marlie joins to share her first poem.**
  *Marlie, 34, has written privately for years and finally wants to be read. Unauthenticated, arriving from a Facebook share.*
  - **Path:** lands on a public *gedig* → reads it without an account → hits "Registreer" → completes a simple profile (no "reader or writer?" choice) → is prompted to take a first social action (follow a writer / save a *bydrae* to her *leeslys*).
  - **Climax:** she sees a warm, Afrikaans welcome — no upsell and no forced reader-vs-writer choice; she's simply in.
  - **Resolution:** account created at default **Brons**, no subscription yet; she's nudged toward the *Lidmaatskap* page when she tries to *plaas*. (The first-social-action prompt is a soft, skippable step.)

- **UJ-2. Marlie subscribes and publishes.**
  - **Entry:** authenticated free member, clicks "Plaas" / "Begin skryf".
  - **Path:** publishing is gated → she's shown an Afrikaans explanation + link to plans → buys **R60 / 1 maand** via PayFast (ZAR) → returns to an **active** *lidmaatskap* (now a **betaalde lid**) → opens the *Skryf* form → picks "Gedig" (line+word counters) → writes in a plain-text editor → optionally links the piece to an active *uitdaging* → "Plaas".
  - **Climax:** a success screen invites her to read-and-respond to others.
  - **Resolution:** her *gedig* is published, discoverable in **Ontdek**, and appears to her *volgelinge*. Realizes UJ-1.

- **UJ-3. Pieter reads and gives a writer a meaningful response.**
  *Pieter, a free member who mostly reads.*
  - **Path:** opens a *storie* in **Ontdek** → reads in a legible reading template → highlights a line that landed and taps a **reaksie** (hartjie) → posts a structured **Gemeenskapsreaksie** of type **Lof** ("wat goed gewerk het") → saves the piece to his **leeslys** → follows the writer.
  - **Climax:** he gives encouragement that is structured and kind by design (no free-for-all comment box).
  - **Resolution:** the writer is notified; Pieter's following-feed will surface her next work.

- **UJ-4. Thandi enters the monthly challenge and is judged in her Gradering.**
  *Thandi, a Silwer-Gradering writer, active **betaalde lid**.*
  - **Path:** opens the current **uitdaging** (theme, rules, deadline, resources) → writes an entry → links it to the round (`uitdagingsrondte`) at submission → after the deadline, winners are announced **per Gradering** (Brons/Silwer/Goud pools).
  - **Climax:** she's judged against her peers, not against Goud veterans.
  - **Resolution:** if she places top-3, the win counts toward **automatic** promotion (5 Silwer wins → Goud); her win is queryable ("Oktober Silwer-wenner"), surfaced on her profile, and her My Profiel shows how many more top-3 results she needs.

- **UJ-5. Elsa (editor) runs a challenge and promotes writers — without busywork.**
  *Elsa, redakteur, working in an English WP admin but seeing Afrikaans INK labels.*
  - **Path:** collates entries into an anonymized **judge email** (one click; EntryIDs assigned) → pastes the judges' results back as plain text → the system generates the **wenneraankondiging** post + winner banner, stores **Terugvoer van die moderator**, and **automatically promotes** writers who crossed the win threshold → she may still record a manual **bevordering** (incl. **Meester**) with a reason → training resources auto-surface beside relevant works via shared `genre`/`vaardigheid` terms, with no manual linking.
  - **Resolution:** the monthly editorial cycle is automated; Gradering and **lidmaatskap** stay strictly separate in the data; editorial effort stays low.

- **UJ-6. Migration day — existing member Johan notices nothing broke.**
  *Johan, a long-time paid member with published work and BuddyPress friends.*
  - **Path:** after cutover, his account, his **active lidmaatskap**, his published *bydraes*, his media, and his old URLs all still work (301s) → his old **friendships** are preserved as reciprocal **volg** relationships (each becomes two one-way follows; *new* relationships are asymmetric) → he can read and respond as before.
  - **Climax:** preservation is invisible; nothing of value was lost.
  - **Resolution:** he experiences the new Afrikaans-first UI as continuity, not disruption.

---

## 3. Glossary

*Downstream workflows and readers must use these terms exactly. FRs, UJs, and SMs use these terms verbatim; introducing a synonym is a discipline violation. Full terminology lives in `docs/afrikaans-terms.md` (the source of truth) — a new concept is added there **before** it appears in code or UI.*

**Identity & access**
- **lid** — a member (person with an account). *Plural: lede.*
- **gratis lid** — a free member: has an account; may read, react, follow, keep a **leeslys**. May **not** *plaas* or use training material.
- **betaalde lid** — a paid member: a **lid** with an active **lidmaatskap**; may additionally *plaas* and use all training material. (Replaces the retired "intekenlid".)
- **besoeker** — an unregistered visitor/reader; can read public work, no account.
- **skrywer** — a writer who publishes work (never "outeur"); any **betaalde lid** who *plaas*. A behavioral role, not a signup choice.
- **leser** — a reader; any **lid** by behavior. There is no stored reader/writer identity and no signup intent flag.
- **redakteur** — editor / staff (WP `editor` role): editorial admin, challenge/winner admin, **Gradering** promotion, sponsor management, moderation.
- **lidmaatskap** — membership: the single term for the access/financial agreement. The free/paid distinction is expressed as **gratis lid** vs **betaalde lid**. *(Owner decision 2026-06-20, G1: the earlier "lidmaatskap" vs "intekening" split is collapsed — **"intekening"/"intekenaar"/"intekenlid" are retired**; use **lidmaatskap** / **betaalde lid** / **gratis lid**.)*
- **aansluitingsopsie** — a **lidmaatskap** plan (price + term).

**Progression**
- **Gradering** — writer tier / progression system; the **primary UI term** (never "tier" in UI; **ster gradering** is the acceptable longer form). Stored as `ink_writer_tier`. Values: **Brons** (`brons`), **Silwer** (`silwer`), **Goud** (`goud`), **Meester** (`meester`); default `brons`.
- **Meester** — the 4th, highest **Gradering**; **manual-only** (a **redakteur** assigns it; never auto-promoted). Rendered in the brand red-orange (`primary #EA4015`), not the Brons/Silwer/Goud colours.
- **bevorder / bevordering** — promote / promotion to a higher *Gradering*. Most-recent promotion date in `ink_tier_promoted_at`. Automatic for Brons→Silwer/Silwer→Goud on win thresholds (FR-12a); Meester is manual-only.
- **win / top-3-uitslag** — a top-3 placement in a **uitdaging** that counts toward automatic promotion. Stored as `ink_tier_win_count` (reset to 0 on promotion).
- **graderingsgeskiedenis** — auditable tier-promotion history.

**Content**
- **bydrae** — a contribution; the collective term for submitted work (never "post"/"plasing"). *Plural: bydraes.*
- **gedig** — a poem (CPT `gedig`).
- **storie** — a short story / prose piece (CPT `storie`; **canonical** — renamed from the legacy `verhaal`).
- **artikel** — an opinion piece / essay (CPT `artikel`).
- **skryfwerk** — migration **holding bucket** CPT for unclassifiable legacy posts. **Not a user-facing term.**
- **biblioteekitem** — a curated Library item / winning work (CPT `biblioteek_item`).
- **hulpbronartikel** — a Training/resource article (CPT `opleiding_artikel`).
- **uitdaging** — the monthly challenge (CPT `uitdaging`; never "kompetisie"/"challenge").
- **EntryID** — the per-type sequence number of an **inskrywing** within a **uitdaging** (`entry_number`; Gedigte/Stories/Artikels numbered separately), **assigned at collation** (not at entry) and stored so pasted results can be matched back. An internal/admin concept — not necessarily member-facing.
- **wenner** — a winning **inskrywing** that placed **2nd or 3rd** (banner: "[Maand] wenner").
- **algehele wenner** — a winning **inskrywing** that placed **1st** (banner: "[Maand] algehele wenner"); gets more prominent placement in the feed than ordinary wenners.
- **wenneraankondiging** — the winners-announcement post, auto-generated from a simple form-letter template, shown in a featured position on the Tuisblad.
- **Terugvoer van die moderator** — the judge/moderator's feedback on an **inskrywing**, stored as a custom structured response (`comment_type = ink_moderator_terugvoer`), **never** an open WP comment; visible on a work only when the writer enables it on My Profiel.
- **uitgawe** — an InkPols magazine issue (CPT `inkpols_uitgawe`; never "issue").
- **borg** — a sponsor (CPT `borg`). *Plural: borge.*
- **plaas** — the act of submitting/publishing work. Status `publish` = **gepubliseer**; `draft` = **konsep**.

**Taxonomies**
- **genre** — genre of a *bydrae* (`genre`); **shared** with training for auto-surfacing.
- **vaardigheid(sarea)** — skill area on training (`vaardigheid`: Digkuns, Prosa, Taalgids, etc.); **shared** with *bydraes*.
- **uitdagingsrondte** — challenge round linking entries and winning work (`uitdagingsrondte`).
- **ster gradering** (taxonomy) — tier as a term for query/segmentation.

**Community & engagement**
- **volg / volg tans** — follow (one-way / asymmetric); replaces the legacy friendship model. Implemented in `ink-core`.
- **volgeling** — a follower (never "volger"). *Plural: volgelinge.*
- **leeslys** — a member's saved reading list.
- **aktiwiteitsvoer** — activity feed (never "stroom"/"feed").
- **reaksie** — a reaction: **hartjie** / **duim op** / **wow** (never "like").
- **hooglignering** — a line highlight on a *bydrae* ("Merk hierdie reël").
- **Gemeenskapsreaksies** — structured community responses; the only feedback path (WP comments disabled). Types: **lof** (praise), **insig** (insight), **voorstel** (suggestion).
- **kennisgewing** — a system notification.
- **ledegids** — the member directory.
- **Ontdek** — the discovery hub (browse/filter/sort/search *bydraes* and *skrywers*).

---

## 4. Features

*Each subsection is a coherent feature: behavioral description first, FRs nested. FRs are globally numbered (FR-1..FR-N) with a launch priority — **P0** launch-critical · **P1** at-launch · **P2** fast-follow — and a `(FL x.y)` reference to the feature-list source for traceability. Glossary terms are used verbatim.*

> **THE conflation rule (binding, spans §4.2 and §4.3).** *Membership status* (active **lidmaatskap** via WooCommerce Membership — i.e. **betaalde lid** vs **gratis lid**) controls **submission entitlement**. *Writer Gradering* (**ster gradering**, `ink_writer_tier`) controls **competition pools**. These are separate concepts and must never be conflated in data or code. *A Brons **betaalde lid** is not the same as a Brons writer whose **lidmaatskap** has lapsed.*

### 4.1 Identity & Registration

**Description:** A person registers and completes a profile. There is **no reader/writer choice at signup** — every **lid** can read, and any **lid** can become a **skrywer** at any time by starting a **lidmaatskap** (becoming a **betaalde lid**; publishing is the only membership-gated action). Authentication is WordPress-native; the experience is entirely Afrikaans. Realizes UJ-1.

**Functional Requirements:**

#### FR-1: Register, log in, reset password (P0, FL 3.1)
A **besoeker** can create an account, log in, and reset a password through Afrikaans-native flows (Registreer / Meld aan / Wagwoord-herstel).
**Consequences (testable):**
- All auth screens and emails render in Afrikaans; no English string appears.
- A new account defaults to **Brons** *Gradering* and is a **gratis lid** (no active *lidmaatskap*).

#### FR-2: Unified account — no signup intent gate (P0, FL 3.2)
Registration does **not** ask the **lid** to choose a reader or writer identity. Every account can read; any account gains **submission entitlement** at any time by starting a **lidmaatskap** (becoming a **betaalde lid**). Realizes UJ-1.
**Consequences:**
- No reader/writer intent flag (e.g. the former `ink_writer_intent`) is captured at signup or stored.
- A **lid** becomes a **skrywer** purely by holding an active **lidmaatskap** (being a **betaalde lid**) and publishing — no separate role or intent switch.
- The reader/writer distinction is **behavioral**, not a stored identity.

#### FR-3: Post-signup first social action prompt (P1, FL 3.3)
After signup, the system prompts the **lid** to take a first social action (follow a **skrywer** or save a **bydrae** to their **leeslys**).
**Consequences:** the prompt is skippable and does not block account completion. Suggested targets come from current discovery surfaces; with a thin catalogue (e.g. immediately post-migration) the prompt degrades gracefully and stays skippable.

#### FR-3a: Account approval, social login & anti-spam (P1 + spike, FL 3.4)
The system layers registration defenses: **social login** (sign up / in via a vetted provider), an **anti-spam** measure against registration-spike abuse, and an **optional, off-by-default manual-approval backstop** a **redakteur** can switch on. (R6)
**Consequences (testable):**
- **Social login** is provided via a vetted platform plugin (hooks only, not reimplemented in `ink-core`).
- An **anti-spam** measure protects the registration endpoint; the specific approach is a **research spike** (owner: *"I know nothing about this"*) feeding a build decision.
- **Manual approval is off by default** — frictionless signup (UJ-1) is preserved; when a **redakteur** enables it, new accounts enter a "pending approval" state and an approval queue exists. Layered so the default experience stays frictionless.
- New registration data feeds the §8 POPIA posture (lawful basis at registration, OQ-3).

### 4.2 Membership, Payment & Access

**Description:** INK sells fixed-term **aansluitingsopsie** products (three at launch; prices and terms configurable) and lets a **lid** buy and self-activate via a front-end **PayFast** flow in ZAR — the new capability that replaces manual EFT activation, making the buyer a **betaalde lid**. An active **lidmaatskap** grants **submission entitlement**; expiry auto-suspends that entitlement (reverting the account to **gratis lid**) while leaving the account, its *Gradering*, and published work intact. Activation, and the approach of expiry, drive **lifecycle emails** (FR-9a). Realizes UJ-2. **This feature governs entitlement only — never Gradering (see THE conflation rule).**

**Functional Requirements:**

#### FR-4: Membership products — configurable price & term (P0, FL 4.1)
The system offers fixed-term **aansluitingsopsie** products — at launch **R60 / 1 maand**, **R300 / 6 maande**, **R600 / 12 maande** (terms stay **1 / 6 / 12 maande**) — with **prices and term lengths configurable by staff** through standard WooCommerce product / Membership settings, no code change required. No auto-renew at launch.
**Consequences (testable):**
- A **redakteur**/admin can change an existing plan's price or term length, or add/retire a plan, via WooCommerce admin — values are not hardcoded.
- Prices are shown as-is; **no** vanity discount/savings framing ("Save 12%/25%") appears anywhere at launch. *(Carve-out — owner decision 2026-06-20: a genuine **recurring-renewal discount** is now permitted, but only as part of recurring billing, which is **post-launch** (FR-63, §14.2); no discount surfaces at launch.)*
- Only currently-configured **aansluitingsopsie** products are purchasable.

#### FR-5: Self-service PayFast purchase (P0, FL 4.2)
A **lid** can buy and self-activate a **lidmaatskap** through a front-end PayFast purchase flow in ZAR, with no staff action required, becoming a **betaalde lid**. Realizes UJ-2.
**Consequences:**
- On successful PayFast return, the **lidmaatskap** activates automatically (no manual EFT/admin step) and a thank-you/activation email is sent (FR-9a).
- Payment is processed off-site by PayFast; INK never stores card data.
- Tests exercise the **PayFast sandbox** only, never the live ZAR gateway.

#### FR-6: Access enforcement — entitlement gate (P0, FL 4.3)
The system grants **submission entitlement** if and only if the **lid** has an active WooCommerce Membership; expiry/suspension auto-revokes entitlement.
**Consequences:**
- Active **lidmaatskap** (**betaalde lid**) ⇒ the **lid** may *plaas*; expired/suspended (back to **gratis lid**) ⇒ publishing is denied with an Afrikaans message + link to plans.
- Revoking entitlement does **not** delete the account, change `ink_writer_tier`, or unpublish existing **bydraes**.

#### FR-7: Lidmaatskap page (P0, FL 4.4)
The system provides a **Lidmaatskap** page: plans, benefits, FAQ, and CTA.

#### FR-8: Renew membership from profile (P1, FL 4.5)
A **lid** can renew via *My Profiel → Lidmaatskap*, choosing from the available **aansluitingsopsie** plans (prices shown as configured; no discount labels).

#### FR-9: Afrikaans access/status messaging (P1, FL 4.7)
The system shows Afrikaans status messaging for active, expired, access-denied, and **payment-failed/cancelled** states (e.g. *"Jou lidmaatskap is aktief…"*, *"Jou lidmaatskap het verval…"*, *"Jou betaling het misluk of is gekanselleer…"*).

#### FR-9a: Membership lifecycle emails (P1, FL 4.9)
The system sends Afrikaans **lifecycle emails** around the **lidmaatskap** term, generated from **simple form-letter templates** (plain text + a name-merge in the greeting line, e.g. *"Beste {skrywer}, …"* — **not** a configurable rich-template engine), with **per-term (1 / 6 / 12 maande) on/off configuration** by staff. (R5)
**Consequences (testable):**
- **Thank-you / activation email** on every successful activation (FR-5).
- **Expiry warnings**: a **1-week-prior** warning on every term, plus a **1-month-prior** warning on longer terms; both anchored to the same expiry the FR-44 reminder uses.
- Each lifecycle email type can be toggled on/off per term length via staff config; templates are stored as simple form-letter text and merge only the recipient's name.
- The form-letter/options store is subject to the Afrikaans no-leak gate (NFR-1 — admin-authored copy is in scope; see §8).
- **Recurring billing, the renewal-warning variant, and any recurring-renewal discount are post-launch** (FR-63, §14.2) — only the above auto-activation + expiry-warning lifecycle ships at launch.

#### FR-10: Suppress storefront UI (P1, FL 4.6)
The system suppresses WooCommerce store UI (cart/catalog/checkout) beyond the membership purchase flow.

#### FR-63: Auto-renew / recurring billing (P2, deferred, FL 4.8)
The system *will* let a **lid** opt into automatic renewal so a **lidmaatskap** continues without a manual re-purchase. **Deferred to post-launch** (OQ-9) pending PayFast recurring-billing support + extension compatibility; until then, renewal is the manual FR-8 flow.
**Consequences:** when recurring ships, a **recurring-renewal discount is permitted** (owner decision 2026-06-20, §14.5/§14.2; a genuine discount, not vanity "%-off" framing) — it rides recurring signup and is therefore likewise **post-launch**.

**Feature-specific NFRs:** PayFast off-site → low PCI scope (see §8/§11).

**Notes:** `[NOTE FOR PM]` Auto-renewing/recurring billing is **deferred** (FR-63, §14.2; OQ-9) pending verification of PayFast recurring support + extension compatibility. The recurring-renewal **discount** carve-out (reverses the earlier no-discount ruling) rides recurring and is **post-launch**.

### 4.3 Writer Tiers (Graderings)

**Description:** Every **skrywer** carries a **Gradering** (ster gradering) — Brons/Silwer/Goud/**Meester** — that drives competition pools and progression display. Brons→Silwer/Silwer→Goud promotion is **automatic** on challenge wins (top-3 placements); **Meester is manual-only**. Staff may also set/adjust any tier with a recorded reason and an auditable log. Gradering is **strictly independent** of membership state (THE conflation rule). Realizes UJ-4, UJ-5.

**Functional Requirements:**

#### FR-11: Tier data model (P0, FL 5.1)
The system models `ink_writer_tier` ∈ {`brons`,`silwer`,`goud`,`meester`}, default `brons`, and a win-count user-meta `ink_tier_win_count` (top-3 wins toward the next Gradering; reset to 0 on promotion).
**Consequences:**
- A missing/ambiguous tier on import resolves to `brons` **plus a flag** (never a guessed Silwer/Goud/Meester).
- **Meester** is a terminal, **manual-only** state — never reached by the auto-promotion engine (FR-12a) and never auto-assigned on import.
- `ink_tier_win_count` is maintained alongside `ink_writer_tier` / `ink_tier_promoted_at` and reset by the promotion path.

#### FR-12: Staff set/adjust tier with reason + log (P0, FL 5.2, 5.3)
A **redakteur** can view and **set** a writer's **Gradering** in any direction — promotion (**bevorder**), corrective demotion, or assigning **Meester** — record a reason, optionally link the change to a challenge result, and write an auditable **graderingsgeskiedenis** entry. Realizes UJ-5.
**Consequences:** every tier change (manual or automatic) writes a log record (custom log table or dedicated meta) with actor (system for auto-promotion), date, reason, from→to tier, and optional challenge link. **Meester** can only be assigned/removed here, never by FR-12a.

#### FR-12a: Automatic challenge-driven promotion (P0, FL 5.7)
The system **automatically promotes** a **skrywer** between Brons/Silwer/Goud based on accumulated challenge **wins**, as the final step of results ingestion (FR-50-R2). *(Moved from deferred P2 to P0 — the promotion rules the deferral was waiting on are now defined.)* Realizes UJ-4.
**Consequences (testable):**
- A **win** = any **top-3 placement** in any entry type at the writer's current Gradering. **Multiple placements each count** (including more than one within a single content type / round).
- Thresholds: **Brons → Silwer at 5 wins**; **Silwer → Goud at 15 wins**.
- On any promotion, `ink_tier_win_count` **resets to 0** and a templated **congratulation email** is sent (simple form-letter + name-merge, e.g. *"Baie geluk! Jy is na Silwer bevorder."* — the same options store as FR-9a).
- **Meester is never auto-promoted** (FR-11/FR-12); Goud is the auto-promotion ceiling.
- The engine lives in the Tiers domain and **never reads membership/entitlement state** (THE conflation rule, FR-13).

#### FR-13: Tier ≠ membership guardrail (P0, FL 5.6)
The system keeps **Gradering** and **lidmaatskap** strictly separate in data and code, with guardrails preventing one being derived from the other.
**Consequences:** membership state and Gradering are separated at the data layer — a membership-state change has **no write path** to `ink_writer_tier`, or vice-versa; the auto-promotion engine (FR-12a) reads only win-counts/placements, never entitlement; unit tests assert that each known membership-state transition leaves Gradering unchanged.

#### FR-14: Tier display on profiles (P1, FL 5.4)
The system displays Brons/Silwer/Goud/**Meester** on member and **skrywer** profiles (public **Skrywerprofiel**). **Meester** renders in the brand red-orange (`primary #EA4015`), paired with text/icon (never colour-only). On the **private My Profiel**, the system additionally shows a *"X top 3 uitslae nodig om [volgende Gradering] te bereik"* subtext driven by `ink_tier_win_count` (hidden at Goud/Meester, which have no automatic next step).

#### FR-15: Tier in discovery & winners (P1, FL 5.5)
The system uses **ster gradering** in discovery filters and challenge segmentation, and labels winners by tier (e.g. *"Oktober Goud-wenner"*).

### 4.4 Submission & Publishing (Skryf)

**Description:** A custom `ink-core` front-end submission workflow (the **Skryf** page) replaces the legacy Youzify form. A **skrywer** picks a content type, writes in a light editor, optionally attaches media and links to an **uitdaging**, and saves a **konsep** or *plaas* (publishes). Publishing is gated on active **lidmaatskap** (being a **betaalde lid**). Realizes UJ-2.

**Functional Requirements:**

#### FR-16: Custom front-end submission form (P0, FL 6.1)
A **skrywer** can submit a **gedig**, **storie**, or **artikel** via a custom front-end form with type-appropriate fields and validation.

#### FR-17: Content-type selection with counters (P0, FL 6.2)
A **skrywer** can pick poem/story/article with per-type placeholders and counters (lines **and** words for **gedig**; words for prose).

#### FR-18: Light editor (P0, FL 6.3)
A **skrywer** can write in a light editor (not a full rich-text editor). Allowed marks: **hard line breaks, blank-line/stanza preservation, bold, italic**. Line structure and leading whitespace are **preserved verbatim, not collapsed** (so shaped/concrete poetry survives — see FR-25). **No** headings, tables, inline images/embeds, or font/colour/size controls.

#### FR-19: Publishing gated to active betaalde lede (P0, FL 6.8)
The system permits *plaas* only for **lede** with active **submission entitlement** (FR-6 — i.e. **betaalde lede**); others see an Afrikaans denial + link to plans. Realizes THE conflation rule.
**Consequences:** a lapsed **skrywer** (now a **gratis lid**) with Goud Gradering is denied publishing (Gradering does not grant entitlement). **Entitlement is evaluated at the moment of *plaas* (the publish action)** — not at draft creation; a **konsep** saved while entitled but published after the **lidmaatskap** lapses is denied at publish time (FR-9 message), with the draft preserved.

#### FR-20: Optional featured image (P1, FL 6.4)
A **skrywer** can add an optional featured image to a **bydrae**.

#### FR-21: Optional audio/video attachment (P1, FL 6.5)
A **skrywer** can add an optional audio/video attachment.

#### FR-22: Link a piece to an active challenge (P1, FL 6.6)
A **skrywer** can link a **bydrae** to active **uitdaging(s)** at submission, writing the **uitdagingsrondte** term. Realizes UJ-4.
**Consequences:** linking is allowed only **while the uitdaging is open** (before the SAST deadline, FR-47); no new links after close.

#### FR-23: Save draft / publish with success prompt (P1, FL 6.7)
A **skrywer** can *Stoor konsep* or *Plaas*; on publish, a success screen prompts read-and-respond actions.
**Consequences:** saving a **konsep** is **not** entitlement-gated (drafts are never lost); only *plaas* checks entitlement (FR-19).

### 4.5 Reading & Engagement

**Description:** Reading is first-class: legible, form-aware templates; light **reaksies** on highlighted lines (encouragement, not critique); structured **Gemeenskapsreaksies** (the only feedback path, since WP comments are disabled); contextual prompts; taxonomy-driven suggested reads; and a personal **leeslys**. Realizes UJ-3. **Engagement serves reading, not feed-scrolling.** **Engagement is open to any lid and is not entitlement-gated** — reading, **reaksies**, **Gemeenskapsreaksies**, and reader ratings (FR-42) require only an account (a **gratis lid** suffices); only *plaas* (publishing) needs an active **lidmaatskap** (**betaalde lid**).

**Functional Requirements:**

#### FR-24: Reading templates for prose (P0, FL 7.1)
The system provides a single reading template per CPT for **storie**/**artikel** (content width ~768px; no WP comments). *(Afrikaans legibility is a tone goal — §6 — not an acceptance criterion here.)*

#### FR-25: Poetry reading layout (P1, FL 7.2)
The system provides a **gedig** reading layout that is stanza-aware, preserves line breaks and **blank-line/stanza spacing and leading whitespace verbatim** (no collapsing), supports **author-entered** Roman-numeral stanza markers (the author types them; the system styles/highlights them — they are not auto-generated), and allows per-line resonance (on content lines, not blank separators).

#### FR-26: Line highlight + reactions (P1, FL 7.3)
A **lid** can highlight selected text and add a **reaksie** (hartjie / duim op / wow). Realizes UJ-3.
**Consequences:** highlighting yields reactions only — **no** public inline commentary/annotation on works ("encouragement, not critique").

#### FR-27: Structured community responses (P1, FL 7.4)
A **lid** can post a **Gemeenskapsreaksie** of type **lof**, **insig**, or **voorstel**. Realizes UJ-3.
**Consequences:** WP comments are disabled site-wide; this is the only feedback path. Each response carries its type.

#### FR-28: Reaction storage + counts (P1, FL 7.8)
The system stores **reaksie** data and displays counts (e.g. *"342 hartjies"* — count shown without verb, per voice rules).
**Consequences:** all dynamic count strings render with locale-correct plural forms (gettext `_n()` for `af`): n=1 singular, n≠1 plural, n=0 handled — e.g. *1 hartjie* / *342 hartjies*, *1 volgeling* / *2 volgelinge*, *1 inskrywing* / *4 inskrywings*. Applies to **every** count surface, not only reactions.

#### FR-29: Reading list (P1, FL 7.7)
A **lid** can save/remove a **bydrae** to their **leeslys** (with confirmation toasts), surfaced on their profile. Realizes UJ-3.

#### FR-30: Contextual guided prompts (P2, FL 7.5)
The system shows contextual guided prompts after a piece (to encourage a thoughtful response).

#### FR-31: Suggested next reads (P2, FL 7.6)
The system suggests next reads by tone/form/topic/tier via shared taxonomy (no manual linking).

### 4.6 Discovery (Ontdek)

**Description:** **Ontdek** is the reading/discovery hub: browse, filter, sort, and search published **bydraes** and **skrywers**, plus a works archive.

**Functional Requirements:**

#### FR-32: Discovery hub + works archive (P0, FL 8.1)
The system provides the **Ontdek** section and a works archive with date/archive browsing.

#### FR-33: Browse bydraes tab (P1, FL 8.2)
A **lid** can browse the **bydraes** tab: filter by type (Gedigte/Stories/Artikels); sort (Nuut / Opspraakwekkend / Mees geliefd).

#### FR-34: Browse skrywers tab (P1, FL 8.3)
A **lid** can browse the **skrywers** tab: genre filter (Digkuns/Prosa/Artikels); sort (Meeste gelees / Nuwe stemme).

#### FR-35: Search works and writers (P1, FL 8.4)
A **lid** can search works (title/theme) and **skrywers** (name/bio/genre).
**Consequences:** search is **diacritic-insensitive** (ê/ë/ô/î match their base letters).

#### FR-36: Personalised discovery surfaces (P2, FL 8.5)
The system provides discovery surfaces ("writers like this", new voices, recently active, writers in your tier, unread-by-you).

### 4.7 Community & Social

**Description:** Scoped BuddyPress provides profiles, a directory, and notifications; **follow is custom and one-way** (asymmetric), implemented in `ink-core`. Profiles carry tier, bio, stats, pinned works, and reader ratings. There is no symmetric friendship, no groups, no site-wide activity stream. Realizes UJ-3, UJ-6.

**Functional Requirements:**

#### FR-37: BuddyPress scope configuration (P0, FL 9.1)
The system configures BuddyPress with Profiles, member Directory, and Notifications **on**; Friend Connections, site-wide Activity, Groups, Blogs, and (at launch) Messaging **off**.

#### FR-38: One-way follow (P0, FL 9.2)
A **lid** can **volg** another **skrywer** one-way (asymmetric), with **volgeling**/following counts and Volg / Volg tans UI, implemented in `ink-core`. Realizes UJ-3.
**Consequences:** following does not require reciprocity; replaces the legacy friendship model.

#### FR-39: Following-feed (P1, FL 9.3)
A **lid** sees a following-feed — the profile "Aktiwiteit" tab — of **new publications by followed skrywers** only.

#### FR-40: Block-theme profiles (P1, FL 9.4)
The system provides block-theme BuddyPress profile templates — **private My Profiel** + **public Skrywerprofiel** — showing **Gradering**, bio, stats, pinned works, and accomplishments. Private data (read counts, the "wins needed" subtext) lives on My Profiel only (FR-14, FR-44b).

#### FR-41: Pinned/selected works (P1, FL 9.5)
A **skrywer** can curate pinned/selected works on their profile.

#### FR-42: Reader ratings & reviews (P1, FL 9.6)
A **lid** can give reader ratings & written reviews on **skrywer** profiles (aggregate rating + reviews).
**Feature-specific NFRs:** public reviews are subject to the moderation path (§8) and POPIA public-profile considerations (§11).

#### FR-43: Member directory (P1, FL 9.7)
The system provides a **ledegids** (member directory).

#### FR-44: Notifications (P1, FL 9.9)
The system sends **kennisgewings** with a "Merk alles as gelees" control. Notification triggers:

| Trigger | Notification | Priority |
|---|---|---|
| New **Gemeenskapsreaksie** / @mention on your **bydrae** | response/mention alert | P1 |
| A **skrywer** you **volg** publishes new work | follow / new-work alert | P1 |
| **uitdaging** announcement / deadline approaching | challenge alert | P1 |
| Your **lidmaatskap** expires soon | expiry reminder (*"Jou lidmaatskap verval binnekort"*) | P1 |
| Your **bydrae** crossed a read-count milestone (post-receipt) | read-receipt notification (FR-44a) | P1 |

**Consequences:** "Merk alles as gelees" marks read by a **timestamp boundary** — notifications arriving *during* the action stay unread (no phantom-unread). The expiry reminder matters because there is no auto-renew at launch (FR-4, §14.2); it shares its anchor with the FR-9a lifecycle expiry warnings.

#### FR-44a: Automatic post-receipt notification (P1, FL 9.10)
When a **bydrae** is read (a "receipt" event tied to the analytics read-count, FR-44b/R8), the system sends the author an automatic **kennisgewing** with a **randomized** encouraging message drawn from a configured list, linking back to **My Profiel**. (R7)
**Consequences (testable):**
- The message is selected at random from a staff-configured list (the same simple form-letter/options store as FR-9a; name-merge only, no rich templating).
- The notification deep-links to the author's **private My Profiel** (where read counts live, FR-44b), not the public Skrywerprofiel.
- Triggering is driven by the analytics read-count surface (FR-44b); with analytics absent the trigger is inert (degrades gracefully).

#### FR-44b: Analytics provider + read counts (P1, FL 9.11)
The system integrates an **analytics provider** (INK has none today) and surfaces per-**bydrae** **read counts** on the author's **private My Profiel**, reusing the denormalized read-count store (`_ink_read_count`). (R8)
**Consequences (testable):**
- An analytics provider is selected and wired via vetted-plugin hooks (not reimplemented in `ink-core`).
- Read counts appear on **My Profiel** only (private), not on the public **Skrywerprofiel**; rendered verb-less with locale-correct `_n()` plurals (per §6 voice / FR-28 rule).
- R8 analytics + R5/R9a emails sharpen the deferred **POPIA** posture (§8/§11, OQ-3) — flagged to be addressed sooner.

### 4.8 Challenges (Uitdagings)

**Description:** Monthly **uitdagings** with **Gradering**-based pools and structured, queryable winner records. Entries link to a round via **uitdagingsrondte**; results drive **automatic** tier promotion (FR-12a). This feature also carries the **editorial-automation launch pillar**: collating entries into an anonymized judge email (R1), ingesting pasted results + commentary (R2), generating the **wenneraankondiging** post and winner banner, and storing **Terugvoer van die moderator**. The hard build order is **R1 → R2 → R3** — the **EntryID** data model is the linchpin and lands first. Realizes UJ-4, UJ-5.

**Functional Requirements:**

#### FR-45: Challenge single page (P1, FL 12.1)
The system provides a **uitdaging** single page (prompt, literary devices, rules, prize, deadline, resources, entries).

#### FR-46: Challenges list page (P1, FL 12.2)
The system provides the Uitdagings list page with countdown.

#### FR-47: Challenge metadata + monthly cadence (P1, FL 12.3)
The system stores challenge metadata (theme, deadline) with a **monthly** cadence.
**Consequences:** all challenge times are **SAST**; the deadline is **inclusive through 23:59:59 SAST** on the closing date (the FR-46 countdown and the server cutoff share this anchor). After the deadline, linked entries are **frozen for judging** — no edits to the judged version until results are announced.

#### FR-48: Submit a challenge entry (P1, FL 12.4)
A **skrywer** can submit an entry (**inskrywing**) linked to a round via **uitdagingsrondte**. Realizes UJ-4.
**Consequences:** a writer may submit at most **3 entries of each content type** (gedig/storie/artikel) per **uitdaging**. Tier is fixed for the round (changes apply between rounds, never mid-round), so the entry-time tier pool governs judging.

#### FR-49: Tier-based competition pools (P1, FL 12.5)
The system runs tier-based pools (Brons vs Brons, Silwer vs Silwer, Goud vs Goud); placements (1st–3rd) are announced per tier. Realizes UJ-4, THE conflation rule (tier governs pools).

#### FR-50: Queryable placement records (P0, FL 12.6)
The system keeps structured, queryable **placement records per tier** — **1st/2nd/3rd place** per tier per round, not only the single winner — distinguishing **algehele wenner** (1st) from **wenner** (2nd/3rd), surfacing e.g. *"Oktober Goud-wenner"* and enabling the craft-progression metric (SM-8). *(Admin-facing recording is launch-critical; richer public surfacing of placements may follow.)*

#### FR-50-R1: Challenge-entry collation → judge email (P0, FL 12.8)
At collation, the system assembles a **uitdaging**'s entries into an **anonymized judge email** and assigns each entry its **EntryID** (`entry_number`, per-type sequence — Gedigte/Stories/Artikels numbered separately), storing the EntryID so pasted results can later be matched. (R1)
**Consequences (testable):**
- EntryIDs are **assigned at collation time**, not at entry time, and stored on the entry (`ink_entries`: `entry_type` + `entry_number`).
- The judge email is anonymized (no author identity exposed to judges) and grouped by content type / Gradering pool.
- **EntryID is the linchpin** of the R1 → R2 → R3 chain and must land first.

#### FR-50-R2: Results ingestion + winners announcement + feedback (P0, FL 12.9)
The system ingests the judges' results and commentary **as pasted plain text** (**no `.docx` parser**), matches them to stored **EntryID**s, and produces: a **wenneraankondiging** post, a coverage report, the per-rank **winner banner**, featured-feed ordering, and stored **Terugvoer van die moderator**. As its final step it triggers automatic tier promotion (FR-12a). (R2)
**Consequences (testable):**
- Results are **pasted as plain text** by a **redakteur**; there is **no `.docx`/PhpWord dependency** and none of the untrusted-ZIP/XXE/zip-bomb surface (owner decision 2026-06-20).
- A **coverage report** flags any stored EntryID with no matched result (and vice-versa) before publishing.
- The **wenneraankondiging** post is generated from a **simple form-letter template** (plain text + name-merge greeting, e.g. *"Beste {skrywer}, …"* — not a rich-template engine), takes a featured Tuisblad slot, and lists entries with links.
- The **winner banner** uses per-rank variants — **algehele wenner** (1st) vs **wenner** (2nd/3rd) — and Brons/Silwer/Goud colour tokens, each **paired with text/icon** (no colour-only rank encoding); **Meester is not a competition pool** so has no banner variant.
- **Terugvoer van die moderator** is stored as a **custom structured response** (`comment_type = ink_moderator_terugvoer`) via `wp_insert_comment`, **never** an open WP comment (the sanctioned exception to "Gemeenskapsreaksies is the only feedback path", §13); it is visible on a work **only when the writer enables it on My Profiel**.
- Ingestion's final step invokes the FR-12a auto-promotion engine on the new placement records.

#### FR-51: Winner → promotion link (P1, FL 12.7)
The system can link a winner to a **Gradering** **bevordering** (optional link from the promotion log to the challenge result). Realizes UJ-5.

**Notes:** `[NOTE FOR PM]` **R9 — annual competition management is P2 (post-launch):** an annual-cadence competition that **reuses the R1/R2/R3 machinery** (collation, paste-ingestion, promotion) on a yearly basis. Out of scope for launch; noted here so the monthly machinery is built reusably.

### 4.9 Library (Biblioteek)

**Description:** A curated **Biblioteek** of **biblioteekitem**s — winning and reference work — with archive and single views. URL prefix `/biblioteek/` is preserved through migration. Some sub-features are flagged gaps, deferred and non-blocking.

**Functional Requirements:**

#### FR-52: Library archive + single (P1, FL 10.1)
The system provides a **biblioteek_item** archive and single view (featured strip + category filter + search + card grid).

#### FR-53: Link winners ↔ challenge (P2, FL 10.5)
The system links winners to a challenge via **uitdagingsrondte**.

**Notes:** `[NOTE FOR PM]` Library date-browsing (FL 9.2), pagination (FL 9.3), and author filter (FL 9.4) are **deferred, non-blocking** gaps vs the mockup. Library organisation is an acknowledged **design gap** (`Biblioteek organisasie.md` is an empty placeholder) — see Open Questions. `[NOTE FOR PM]` **R4 (automatic Biblioteek update):** a P0 **stub/hook only** — winning entries will update the writer's **Biblioteek**; the body is **deferred** with the broader biblioteek analysis (the hook is reserved now so R2 ingestion can call it later).

### 4.10 Training (Opleiding)

**Description:** A resource **hub** (not an LMS) of **hulpbronartikel**s, faceted by **vaardigheid**, that auto-surfaces beside relevant works and challenges via shared taxonomy — never manual linking. URL prefix `/opleiding/` preserved.

**Functional Requirements:**

#### FR-54: Training hub + faceted search (P1, FL 11.1, 11.2)
The system provides the **opleiding_artikel** hub with faceted search by **vaardigheid** (Begin hier, Skryfkuns, Digkuns, Prosa, Stylfigure, Redigeer en hersien, Stem en styl).

#### FR-55: Auto cross-surfacing of training (P2, FL 11.4)
The system auto-surfaces training beside works/challenges via shared `genre`/`vaardigheid` terms, with **no manual linking** (Editorial-low-friction constraint, §15).
**Consequences:** cross-surfacing is driven **solely** by shared `genre`/`vaardigheid` terms — an item sharing no term surfaces nothing (assertable); no per-item manual linking exists.

#### FR-56: Editor's shelf + community guides (P2, FL 11.3, 11.5)
The system provides a curated "Die redakteur se rak" entry point, and a **skrywer** can contribute community guides via a "Plaas 'n stuk" CTA.

### 4.11 InkPols

**Description:** A periodical issue model — **inkpols_uitgawe** — with a by-year archive and PDF viewing via Real3D Flipbook. Issues stay PDF-based (no per-article extraction).

**Functional Requirements:**

#### FR-57: InkPols issue model, archive & PDF viewing (P1, FL 13.1–13.3)
The system provides the **inkpols_uitgawe** model (issue date, volume, cover, PDF, teaser), a by-year archive + single-issue page, and PDF viewing via Real3D Flipbook.
**Feature-specific NFRs:** Real3D viewer controls are plugin JS — Afrikaans via the plugin's JS translations, not `.mo` (§8 i18n).
**Note:** the Real3D flipbook is a **known, accepted exception** to the light-front-end-JS (NFR-3) and accessibility (NFR-5) goals; a more accessible delivery mechanism (e.g. a direct PDF link) is **deferred pending resources**.

### 4.12 Sponsors (Borge)

**Description:** A **borg** CPT with scheduling/rotation — one featured/rotating sponsor on the homepage, no logo dumps on content pages — plus a recognition page.

**Functional Requirements:**

#### FR-58: Sponsor model, scheduling & placement (P1, FL 14.1–14.4)
The system provides a **borg** CPT (name, logo variants, link, `sponsor_tier`, campaign start/end, placement prefs), schedules/rotates display by campaign dates, places one featured/rotating sponsor on the homepage, and provides a recognition page on Oor INK.
**Consequences:** with **no** active sponsor in the current window the homepage strip **collapses gracefully** (no empty slot); with **multiple** active, the homepage **rotates** among them; campaign dates are **inclusive** of start and end (a single-day start==end campaign shows that day).

### 4.13 Organisation & Marketing Pages

**Description:** The public org and marketing surfaces — Tuisblad, Gemeenskap, Oor INK, Kontak — plus a theme-native footer. Org legal details are placeholders pending real values (a pre-launch content gate, §11/§16).

**Functional Requirements:**

#### FR-59: Homepage (Tuisblad) (P0, FL 15.1)
The system provides the Tuisblad (hero spotlight, challenge section, featured works, sponsors, CTA).

#### FR-60: Marketing & org pages (P1, FL 15.2, 15.3)
The system provides the Gemeenskap (conversion) page and Oor INK (mission, contact, sponsors, org pages) — with clearly-marked placeholders for founding year and SA legal status (pre-launch content gate).

#### FR-61: Contact form (P1, FL 15.4)
The system provides a **Kontak** form page, built as a **custom `ink-core` form** (decided 2026-06-15, OQ-8 — no CF7 / Fluent Forms dependency).

#### FR-62: Theme-native footer (P1, FL 15.5)
The system provides a theme-native footer / social-links pattern (replacing the legacy social-icons plugin).

---

## 5. Information Architecture

*Top-level public surfaces and the member navigation. Block-theme templates, Afrikaans labels, sentence-case headings.*

- **Tuisblad** (home) → hero, current **uitdaging**, featured **bydraes**, **borg** strip, CTA.
- **Ontdek** (discovery) → tabs: **bydraes** (filter/sort/archive) · **skrywers** (genre/sort).
- **Uitdagings** → list + single challenge.
- **Biblioteek** (`/biblioteek/` preserved) → curated **biblioteekitem**s.
- **Opleiding** (`/opleiding/` preserved) → **hulpbronartikel** hub, faceted by **vaardigheid**.
- **InkPols** → **uitgawe** archive + flipbook reader.
- **Gemeenskap** / **Oor INK** / **Kontak** → org & marketing.
- **Lidmaatskap** → plans + purchase.
- **Skryf** → submission (entitlement-gated).
- **My Profiel** / public **Skrywerprofiel** → profile, **leeslys**, **Lidmaatskap** tab, **Aktiwiteit** (following-feed), **ledegids**.

*[ASSUMPTION: this IA mirrors the Lovable page-map; confirm against `docs/design-handoff/` page-map for any surface I've mislabelled.]*

---

## 6. Aesthetic & Tone

*Qualitative product character the FR structure must not flatten. Source: `docs/ui-copy-translations.md` + `docs/design-handoff/`.*

- **Afrikaans-first and warm**, addressing the member as **"jy"**. INK is a literary **tuiste** (home/sanctuary), a community **"nie 'n markplek nie"** — quiet, human, non-commercial.
- **Developmental, not gatekeeping.** Feedback is a gift: *"Ons prys spesifiek, stel saggies voor, en trep nooit op mense nie"* / *"Begin met wat werk."* Critique is structured into **lof / insig / voorstel**, never raw judgement. CTAs invite: *"Of dit 'n verfynde konsep is of 'n dapper eerste poging — jou stem hoort hier."*
- **Quiet over loud.** Resonance over reach — a thoughtful reader matters more than a viral moment. Counts are shown without verbs (*"342 hartjies"*). No vanity-metric or reach-chasing framing. `[NOTE FOR PM: the approved Afrikaans rendering of this line in ui-copy-translations.md:344 ("Weerklank wen van bereik…") reads as broken/meaningless — flag for human re-translation; do not auto-translate (human-authored Afrikaans only).]`
- **Sentence-case headings** — Afrikaans uses fewer capitals than English (*"Begin skryf"*, not *"Begin Skryf"*).
- **Legibility first** — reading templates prioritise Afrikaans readability over decorative type; Lora (display/heading) + Inter (body/UI); content width ~768px.
- **Terminology discipline** — all UI copy follows `docs/afrikaans-terms.md`; banned-term list and English loanwords avoided (**Biblioteek** not "library", **uitdaging** not "challenge"). Copy is human-authored; **never** lifted from the English Lovable placeholders and **never** AI-generated Afrikaans.

---

## 7. Cross-Cutting NFRs

*System-wide non-functional requirements not tied to a single feature. Verification approaches noted where they shape the test harness.*

- **NFR-1 — Afrikaans-first, zero English leakage (Quality Gate D).** Front end + user-facing transactional emails are entirely Afrikaans (site locale `af`); admin chrome stays English by decision, but all `ink-core` admin labels are Afrikaans. No English word reaches a visitor/member. **This is a standing operational gate**, re-run after ungated core/plugin updates — verified by an **automated English-leak scan** (crawl key front-end pages + `wp i18n` untranslated counts), not a one-time manual pass. Leak vectors explicitly in scope: validation/status/error messages, plugin-composed sentences (BuddyPress, Woo), transactional emails, plugin **JavaScript** strings (separate JS `.json` translations, e.g. Real3D), out-of-band outputs (REST/AJAX/feeds), and **admin-authored form-letter / notification template copy** (the FR-9a/FR-44a/FR-50-R2 options store — enforced at the admin boundary or scanned, since it bypasses the build-time `.mo`). **Detection uses a defined allowlist** of permitted shared tokens (brand names — INK, PayFast; technical tokens — PDF, URL; proper nouns), with **member-supplied content out of scope** — giving the scan a deterministic pass/fail. The gate is *zero on the enumerated/inspected surfaces*; regressions after ungated updates are remediated under the SM-2 SLA (the detect→remediate window is non-zero and accepted).
- **NFR-2 — Design-token compliance (Quality Gate A).** No hardcoded colours, spacing, or unnamed type sizes in templates/patterns/styles; everything maps to `theme.json` tokens (the production source of truth).
- **NFR-3 — Performance & caching.** LiteSpeed Cache (dynamic/logged-in) + Cloudflare edge (static); avoid plugin sprawl and heavy front-end JS where a block pattern suffices.
- **NFR-4 — SEO & URL integrity.** Preserve archive URLs and 301 integrity; sitemaps, CPT schema, breadcrumbs via Rank Math (Yoast retired). Content architecture precedes visual redesign; keep `/biblioteek/` and `/opleiding/` prefixes.
- **NFR-5 — Accessibility & readability.** Afrikaans legibility prioritised over decorative type; reading templates text-legible first.
- **NFR-6 — Maintainability for non-technical staff.** Site Editor stability; block locking on critical editorial structure while content stays editable.
- **NFR-7 — Reliability & update governance.** Gate major core/plugin updates through staging with a regression pass on custom overrides + translation refresh; minor/security/host-forced updates rely on the standing English-leak detection (NFR-1) + committed `.mo` as the defence for premium/niche plugins.
- **NFR-8 — Observability.** 404 logging (Redirection); Patchstack CVE alerts; the automated English-leak scan as a standing CI/cron gate.
- **NFR-9 — Test harness (foundational, Epic 1 not deferred).** Pyramid concentrated in `ink-core`: many unit tests (Pest/PHPUnit + Brain Monkey/WP_Mock) for tier promotion, the entitlement gate, sponsor scheduling, follow graph; fewer integration tests (wp-env/wp-browser) for the load-bearing seams — *active membership ⇒ can submit*, *expired ⇒ denied*, *tier write ⇒ meta + log*; a thin E2E layer (Playwright + `@wordpress/e2e-test-utils-playwright`) for the critical journey *register → buy via PayFast sandbox → submit → publish → read/react → renewal/expiry*. Risk-based depth: smoke for minor/security updates, full regression for majors. **PayFast sandbox only** in tests.

---

## 8. Compliance, Privacy & Moderation

*Launch-grade. All POPIA/moderation specifics below are `[ASSUMPTION]`/`[NOTE FOR PM]` defaults requiring confirmation against INK's actual legal posture before go-live (a pre-launch gate). The spec addresses PCI but not POPIA; these defaults close that gap.*

- **PCI.** PayFast is off-site; INK never stores or processes card data → PCI scope is minimised. (From spec.)
- **POPIA — data subject rights.** `[ASSUMPTION]` INK, as a South African platform processing members' personal information, complies with POPIA: lawful-basis/consent capture at registration, the ability for a **lid** to access, correct, and request deletion of their personal data, and a defined retention posture. `[NOTE FOR PM: confirm INK's information-officer designation and lawful basis with legal before launch.]`
- **Public-profile exposure.** `[ASSUMPTION]` **Skrywerprofiel**, ratings/reviews (FR-42), and published **bydraes** are public by design; members are informed of this at the point of publishing/profile creation, and a reasonable opt-out / visibility control exists for profile fields. `[NOTE FOR PM: confirm how much profile data is public by default.]`
- **Content moderation.** A logged report path exists via a **custom `ink-core` report form** (decided 2026-06-15, OQ-4 — not a third-party plugin), with a defined editorial review/escalation flow and a target first-response SLA. `[NOTE FOR PM: moderation SLA and escalation owner still to be confirmed by the founder (OQ-4 policy remainder).]` Reviews/responses are subject to this path.
- **Admin-authored template copy (leak-scan extension).** The form-letter / notification options store (FR-9a, FR-44a, FR-50-R2, FR-12a congratulation email) holds **admin-authored Afrikaans copy** not covered by the build-time `.mo` + page-crawl leak scan. The NFR-1 no-leak gate **extends to this store** — Afrikaans is enforced at the admin boundary or the option store is scanned.
- **Analytics & email (POPIA).** R8 analytics (FR-44b) and the R5/R9a lifecycle + R7 receipt emails sharpen the deferred **POPIA** posture (OQ-3) — flagged to be addressed sooner than originally scheduled.
- **Organisational legal disclosure.** Org content ships with clearly-marked placeholders (`[stigtingsjaar]`, `[regstatus]`); INK's real founding year, copyright year, and SA nonprofit/registration status (*"niewinsgerigte gemeenskapsorganisasie"*, **never** US "501(c)(3)" wording) are confirmed and inserted before go-live (pre-launch content gate, §16).
- **Email compliance.** `[ASSUMPTION]` Transactional emails (Afrikaans) only; any future marketing email requires explicit opt-in.

---

## 9. Integration & Dependencies

*INK depends on a set of vetted platform plugins for commodity capabilities — these are not reimplemented (`project-context.md`). Integration is via documented hooks/filters/template functions only; plugin files are never edited. Each is a seam that must survive updates (NFR-7).*

- **BuddyPress** (scoped) — profiles, member directory, notifications. Friends/Groups/Messaging/site-wide Activity off. (FR-37)
- **WooCommerce + WooCommerce Memberships** — membership products + entitlement state. Memberships only, not a storefront. (FR-4, FR-6)
- **WooCommerce PayFast Gateway (ZAR)** — front-end payment; sandbox in tests. (FR-5)
- **Real3D Flipbook** — InkPols PDF viewing; viewer is plugin JS (i18n via JS translations). (FR-57)
- **Rank Math** — SEO/schema/sitemaps (Yoast retired). (NFR-4)
- **Redirection** — 301s + 404 logging. (§10, NFR-8)
- **LiteSpeed Cache** — caching (with Cloudflare edge). (NFR-3)
- **Patchstack** — CVE alerts (no WordFence). (§12)
- **Custom `ink-core` report form** — moderation path (decided 2026-06-15, OQ-4); Afrikaans-native, no third-party Report Content plugin. (§8)

**Retired / must not be reactivated:** Youzify, WPBakery/Qode stack, Yoast, Loginizer, Invite Anyone, PDF Embedder, Comments Plus, CBX online widget, WPCustom Category Image, Ultimate Social Media Icons. *Mechanism, version pins, and per-plugin rationale: see `addendum.md`.*

---

## 10. Brownfield Migration & Rollout

*INK is a clone-and-restructure of a live site. **Preservation over convenience** — data continuity outranks a clean-slate rebuild. Full scripted sequence and edge-case handling: `docs/migration-plan.md` + `addendum.md`. The PRD captures the binding requirements and risks.*

**Must survive:** members/accounts, **lidmaatskappe** (WooCommerce Memberships records, plan IDs, access rules, expiry — ride the DB clone, **no import script**), all **bydraes** (title/content/author/date/featured image/comments), media (`wp-content/uploads/`), **every moved URL (301)**, and BuddyPress friendships (converted to follow). Realizes UJ-6.

**Binding migration requirements:**
- **MR-1 (P0).** Clone & sanitise the production DB (strip transients/logs) as a clean baseline; **do not** clone `wp_options` wholesale (carry only deliberate values: site URL/name, `af` locale).
- **MR-2 (P0).** Define CPTs/taxonomies/meta in `ink-core` **before** migrating content (Epic 2 precedes Epic 15).
- **MR-3 (P0).** Import users; reassign roles to reader/writer base roles.
- **MR-4 (P0).** Import **ster gradering** from spreadsheet (CSV → `ink_writer_tier`, joined on email); missing/ambiguous → default **brons** + flag; writers with no WP account flagged for manual follow-up.
- **MR-5 (P0).** **Manually verify** active **lidmaatskappe** before cutover (state, plan IDs, expiry date) — they ride the DB clone, not via import. *(Cutover-boundary and expiry-cron/timezone reconciliation is tracked as a migration-build item — §16 OQ-18.)*
- **MR-6 (P0).** Reclassify posts → CPTs from existing categories; unclassifiable posts land in the **skryfwerk** holding bucket — **never hand-classified at volume**.
- **MR-7 (P0).** Generate **301 redirects** for every CPT reassignment that changes a URL; verify by crawl. Keep `/biblioteek/` and `/opleiding/` prefixes to cut redirect volume.
- **MR-8 (P1).** Convert each **confirmed** BuddyPress friendship into **two** mutual **volg** records (A→B and B→A); **dedup** duplicate rows and **skip** edges to non-imported/flagged accounts (no dangling follows). Pending friend requests are not converted.
- **MR-9 (P1).** Migrate library/training (by URL sub-path), InkPols back-catalogue (re-link PDFs; date+volume meta), sponsors (manual), and rebuild navigation fresh.
- **MR-10 (P1).** Verify media (uploads, audio/video, PDFs); clean legacy WPBakery shortcodes (none render as raw text).
- **MR-11 (P0 sequence).** DNS cutover only after all verifications pass.

**Top migration risks (flagged):**
1. **Slug reconciliation** — `storie` is canonical (project-context); stale `verhaal` references in migration prose reconciled 2026-06-15, and tier casing confirmed lowercase `brons` (not `bronze`). *(OQ-1 — RESOLVED.)*
2. **Reclassification reliability** — several thousand posts (50–300/month over years) classified from writers' self-assigned categories; unreliable categories force a bounded manual bulk-edit pass; **skryfwerk** fallback must have a defined later-reclassification policy (deferred — §16 OQ-16).
3. **Redirect volume** — high; flat `/[slug]/` → typed CPT bases. Mitigated by preserving high-value prefixes.
4. **Legacy custom-table data** — if Youzify is removed, extract its profile/social + FES upload data **before** deactivation and re-associate uploads with the new submission model.
5. **Activity/notifications** — old BuddyPress activity may be large (consider trimming >2yr); notifications are **not** migrated (regenerate naturally).

---

## 11. Data Governance

- **Source of truth on migration:** the live DB clone for members/subscriptions/content/media; the tier spreadsheet for **ster gradering** (email-joined). No wholesale `wp_options` clone.
- **Auditability:** **graderingsgeskiedenis** (tier promotions) is logged with actor/date/reason (FR-12). PayFast transactions are recorded by WooCommerce; INK stores no card data.
- **Retention:** `[ASSUMPTION]` member personal data retained for the life of the account; deletion on POPIA request removes personal data while preserving published **bydraes** in anonymised/attributed form per editorial policy. `[NOTE FOR PM: confirm whether deletion unpublishes a member's works or retains them attributed.]`
- **Classification:** public (published **bydraes**, profiles, reviews) vs. private (account/payment/subscription data). Private data is never exposed via REST/AJAX/feeds.

---

## 12. Constraints & Guardrails

- **Three-layer separation is non-negotiable.** Presentation → theme; INK business rules & content models → `ink-core`; commodity capabilities → vetted plugins. **No business logic in the theme.**
- **THE conflation rule** (§4 banner) — subscription entitlement vs **ster gradering** never conflated.
- **Preservation over convenience** — data continuity outranks clean-slate rebuild.
- **Design tokens canonical** — no hardcoded colours/spacing/type (NFR-2).
- **Editorial effort must stay low** — no feature may depend on per-item manual editorial linking; rely on shared taxonomy/automation.
- **Terminology guide is source of truth** — a new concept enters `afrikaans-terms.md` before code/UI.
- **Lovable is design intent, not code** — never port JSX/Tailwind, never lift English placeholder copy, never treat mock data/`localStorage` as the data model.
- **Security (layered):** Cloudflare-locked origin (no direct origin traffic) + staff 2FA on editor/administrator (or Cloudflare Access on `/wp-admin`) + Patchstack CVE alerts + host malware scanning + staging-gated updates. No WordFence; Loginizer retired. PayFast off-site → low PCI scope.
- **Production hygiene** — no dev/diagnostic/migration tooling (Loco, Code Snippets, WP Migrate Lite, String Locator, etc.) installed or active on production; translation `.po/.mo` authored on staging and committed to version control.
- **Cost / sprawl** — lightweight tooling (Patchstack = alerts, not a heavy WAF); avoid plugin sprawl.

---

## 13. Non-Goals (Explicit)

- INK is **not** a social network, a feed, or a marketplace.
- **No general e-commerce storefront** — WooCommerce is memberships only.
- **No LMS** — training is a resource hub (no courses/quizzes/certificates).
- **No public passage annotation / inline commentary** on works — line highlights yield **reaksies** only.
- **No WordPress comments** — disabled site-wide; **Gemeenskapsreaksies** is the only *member* feedback path. **One programmatic exception:** **Terugvoer van die moderator** is stored as a custom structured response (`comment_type = ink_moderator_terugvoer`, FR-50-R2), never an open WP comment, and visible only when the writer enables it on My Profiel.
- **No symmetric friendships, Groups, site-wide Activity, or Blogs** (BuddyPress scoped down).
- **No InkPols per-article extraction** — issues stay PDF-based.
- **No AI-generated Afrikaans** — human-authored translations only.
- **No translation of the WordPress admin chrome** — admin stays English by decision (INK's own admin labels excepted — those are Afrikaans).
- **No multilingual / non-Afrikaans content surfaces.**
- *Deferred (not non-goals): auto-renew (FR-63), private messaging — see §14.*

---

## 14. Launch Scope (replacement parity + editorial-automation pillar)

*This is a **replacement-parity** scope, not a minimal experiment: INK replaces a live, in-use site, so the launch bar is **no functional regression from the current site, plus two automation pillars** — (1) the self-serve payment/entitlement capability and (2) **editorial automation** (challenge adjudication R1/R2, automatic Gradering promotion R3, lifecycle/receipt notifications) (§1). **MVP re-scope note (2026-06-20):** adding R1–R4 at P0 expands the launch contract from "replacement-parity + PayFast" to "replacement-parity + PayFast + editorial-automation pillar," roughly doubling the challenge-admin MVP — accepted via the approved Sprint Change Proposal (scope classification MAJOR; PRD MVP review). Scope is governed by the feature-list priority tags; no launch date is recorded (per scoping).*

### 14.1 In Scope (Launch = P0 + P1)
- **P0 (launch-critical):** Foundation (theme/tokens/`ink-core` scaffold, test harness), **a configurable form-letter / notification capability** (stored form-letter text + name-merge, per-event on/off toggles, randomized message list — consumed by R2/R3/R5/R7), Content models (CPTs/taxonomies/meta), Membership + PayFast purchase + entitlement gate (FR-4–7), Writer Graderings + Meester + **automatic challenge-driven promotion** + conflation guardrail (FR-11–13, **FR-12a**), Submission + publish gate (FR-16–19), **Challenge adjudication automation — EntryID + judge-email collation (FR-50-R1) + paste-text results ingestion + winners post/banner/moderator-feedback (FR-50-R2)**, Reading templates (FR-24), Discovery hub (FR-32), BuddyPress scope + one-way follow (FR-37–38), queryable placement records (FR-50), **R4 Biblioteek-update stub/hook**, Homepage (FR-59), Auth (FR-1–2), the binding migration steps (MR-1–7, MR-11), Afrikaans-first locale/copy + no-leakage gate (extended to the template store).
- **P1 (at-launch):** renewal, status messaging, **membership lifecycle emails (FR-9a)**, store suppression, Gradering display/use (incl. private My Profiel "wins needed" subtext, FR-14), optional media, draft/publish prompts, poetry layout, highlights/reactions, Gemeenskapsreaksies, reading list, discovery tabs/search, following-feed, profiles, pinned works, ratings/reviews, directory, notifications, **post-receipt notification (FR-44a)**, **analytics provider + private read counts (FR-44b)**, **account approval / social login / anti-spam (FR-3a, with the anti-spam research spike)**, full challenges UX, library archive, training hub, InkPols, sponsors, org/marketing pages, contact, footer, remaining migration (MR-8–10), SEO/security/caching, residual plugin Afrikaans pass.

*Dated decision rows (2026-06-20, per approved Sprint Change Proposal):*
- **Automatic, challenge-driven Gradering promotion — moved P2 → P0** (now **FR-12a**). The promotion rules the earlier deferral was waiting on are defined: top-3 = a win; Brons→Silwer @5, Silwer→Goud @15; reset on promotion; multiple wins/category count; Meester manual-only. *(Supersedes the prior "deferred to P2" ruling.)*
- **No-discount ruling — amended:** a genuine **recurring-renewal discount is now permitted** (no vanity "%-off" framing). Because it rides recurring billing it is **post-launch** (see auto-renew below). *(Supersedes the 2026-06-14 no-discount ruling for the recurring case only; at-launch fixed-term pricing still shows no discount, FR-4.)*
- **Recurring (auto-renew) billing — unchanged:** remains deferred post-launch.

### 14.2 Out of Scope for Launch (P2 fast-follow / deferred)
- **Auto-renew / recurring billing** (and its permitted recurring-renewal discount) — deferred until after launch; verify PayFast recurring support + extension compatibility first. `[NOTE FOR PM: emotionally load-bearing for reducing churn — revisit early post-launch.]`
- **Private messaging** — deferred; BuddyPress Messaging off at launch.
- **Contextual prompts (FR-30), suggested next reads (FR-31), personalised discovery surfaces (FR-36), auto cross-surfacing of training (FR-55), editor's shelf + community guides (FR-56), winners↔challenge link (FR-53)** — P2 fast-follow.
- **Library date-browse / pagination / author-filter (FL 9.2–9.4)** — deferred, non-blocking.
- **R4 automatic Biblioteek update — body** deferred (the P0 stub/hook ships at launch; the full update lands with the broader biblioteek analysis).
- **R9 annual competition management** — P2 post-launch (reuses the R1/R2/R3 machinery on an annual cadence).
- **Member online widget** — removed (CBX retired).
- **Native term images (FL 2.5)** — P2.
- **Retired-plan renewal handling** (a member on a retired **aansluitingsopsie** at renewal) — deferred to build-time when plan retirement is wired (EC-M9, §16 OQ-18).

---

## 15. Success Metrics

*Targets give the PRD teeth and are tuned against real baselines; SM-3/SM-4/SM-6 are confirmed provisional launch targets (2026-06-15), SM-5 remains TBD pending an analytics baseline (OQ-5). Each SM cross-references the FR(s) it validates. Counter-metrics are as load-bearing as primaries — they keep the build from optimising the wrong thing (the spec is explicit that engagement serves reading, not reach).*

**Primary**
- **SM-1 — Data preservation (migration integrity).** 100% of **valid** members, active **lidmaatskappe**, **bydraes**, and media survive cutover; 100% of moved URLs return a 301. Pre-existing broken/orphaned legacy data is **enumerated and accepted in a reconciliation log**, not a launch blocker. Validates MR-1–11. *"Survive" means the record is preserved — **not** that a **bydrae** is correctly typed or publicly addressable; classification accuracy + `skryfwerk`/URL handling are tracked separately (§16 OQ-16).* `[ASSUMPTION: 100% of valid records is the bar.]`
- **SM-2 — Zero English leakage.** The automated English-leak scan (NFR-1) reports **zero** English strings on the enumerated key surfaces + user-facing emails **at launch** (the launch gate). After ungated core/plugin updates, any new leak is **remediated within `[ASSUMPTION: N business days — to be set]`** (the leak→authored→redeployed window is non-zero and accepted). Validates NFR-1.
- **SM-3 — Payment automation.** ≥ **95%** of new/renewing memberships are self-activated via PayFast with no manual staff step within 30 days of launch. Validates FR-5, FR-6. `[Provisional target — confirmed 2026-06-15: 95%; tune against post-launch baseline.]`
- **SM-4 — Return-to-read (the core behaviour change).** Median active member returns **≥ 6×/month** within 3 months (breaking the legacy ~3×/month pattern). Validates FR-24–36, FR-39. `[Provisional target — confirmed 2026-06-15: 6×/month; tune against the analytics baseline once available.]`
- **SM-8 — Craft progression (community competitiveness).** The number of **distinct writers** placing in the **top 3 of the Goud or Silwer tier** pools over a rolling 12 months grows **≥30% year-over-year**. Rationale: more *different* top-3 placers signals tougher competition — a proxy for craft rising across the active writer base (Brons excluded — entry tier). Validates FR-48, FR-49, FR-50. `[Baseline established in year 1; ≥30% YoY is a year-2+ target. The only craft-outcome metric pullable from existing data.]`

**Secondary**
- **SM-5 — Free→paid conversion.** ≥ **X%** of **gratis lede** start a **lidmaatskap** (become **betaalde lede**) within 60 days. Validates FR-5, FR-19. `[ASSUMPTION: target TBD — needs a baseline; placeholder pending Cobus's numbers.]`
- **SM-6 — Feedback density.** ≥ **50%** of published **bydraes** receive at least one **Gemeenskapsreaksie** within 14 days. Validates FR-27. `[Provisional target — confirmed 2026-06-15: 50%; tune against baseline.]`
- **SM-7 — Challenge participation.** Each monthly **uitdaging** draws entries across all three tiers. Validates FR-48, FR-49.

**Counter-metrics (do not optimize)**
- **SM-C1 — Notification/feed volume.** Do **not** maximise notifications or feed activity; if return-frequency (SM-4) rises while average reading time per visit falls, that is a regression (reach beating resonance). Counterbalances SM-4.
- **SM-C2 — Response quality, not quantity.** Do **not** optimise raw **reaksie**/response counts at the expense of structured, kind feedback; a spike in **reaksies** with falling **voorstel**/**insig** share is undesirable. Counterbalances SM-6.
- **SM-C3 — Editorial load.** Do **not** add features that raise per-item manual editorial effort to chase any metric. Counterbalances all engagement metrics.
- **SM-C4 — Don't shrink the pool to flatter SM-8.** Do **not** reduce challenges or placement slots to concentrate placers; SM-8 must rise from *more distinct writers competing*, not fewer slots. Counterbalances SM-8.

---

## 16. Open Questions

*Status: ✅ resolved (decided & actionable) · ⏸️ deferred (needs founder/legal/analytics input, or post-launch) · ⬜ open (action still outstanding). Triage round 2026-06-15 — **8 resolved, 9 deferred, 1 open**. OQ-16–18 were added from the PRD validation triage (2026-06-15); see `validation-report.md` for the full findings set those deferrals fold in.*

1. ✅ **OQ-1 (P0 — pre-scripting). [RESOLVED 2026-06-15]** `storie` is canonical; tier-default casing is lowercase `brons` (per `afrikaans-terms.md`). Stale `verhaal` CPT-slug references reconciled across migration/spec prose (`migration-plan.md`, `plugin-transition-guide.md`, `implementation-options.md`, `initiation.md`, `lovable-block-theme-playbook.md`, `design-handoff/page-map.csv`). No SEO tradeoff: the old site uses flat `/[slug]/` URLs (not `/verhaal/`), so the new `/storie/` base needs the same redirects either way. Migration scripting unblocked. *(`kortverhaal` and Lovable source filenames left unchanged — not slugs.)*
2. ⏸️ **OQ-2 (pre-launch content gate). [DEFERRED — founder]** Interim values set: copyright year **2026**, generic **"niewinsgerigte gemeenskapsorganisasie"** (US "501(c)(3)" removed). Founding-year placeholder retained (mockup shows 2018, unconfirmed). INK's real **founding year** and **SA nonprofit/registration status** to be confirmed with the founder before go-live. Non-blocking for build.
3. ⏸️ **OQ-3. [DEFERRED — founder + legal; bring forward]** POPIA posture (lawful basis, information-officer designation, data-deletion behaviour for published works, default public-profile visibility, §8/§11) needs a legal/product session. **2026-06-20: bring forward** — R8 analytics (FR-44b) + R5/R9a lifecycle/receipt emails (FR-9a, FR-44a) add personal-data processing that sharpens this question. Owner: founder + legal.
4. ✅ **OQ-4. [RESOLVED 2026-06-15 (tooling) / DEFERRED (policy)]** Tooling: a **custom `ink-core` report form** (not a third-party plugin). Still outstanding: moderation **SLA** and **escalation owner** — a founder decision.
5. ⏸️ **OQ-5. [DEFERRED — analytics]** Free→paid conversion target (SM-5) needs a baseline from current analytics. Owner: founder/analytics.
6. ⏸️ **OQ-6. [DEFERRED — non-blocking]** Library organisation/archive design (empty `Biblioteek organisasie.md`).
7. ✅ **OQ-7. [RESOLVED 2026-06-15]** Historical challenges stay a **flat archive** initially: build **uitdaging** records from existing challenge-round categories (§14.6); do **not** migrate the near-empty `monthly_challenge` CPT 1:1.
8. ✅ **OQ-8. [RESOLVED 2026-06-15]** Contact (and report) forms use a **custom form in `ink-core`** — no CF7 / Fluent Forms dependency; Afrikaans-native.
9. ⏸️ **OQ-9 (post-launch). [DEFERRED — post-launch tech]** PayFast recurring-billing support + extension compatibility before enabling auto-renew (FR-63). The **recurring-renewal discount** (now permitted, §14.2 dated row, reverses the 2026-06-14 no-discount ruling for the recurring case) rides this and is likewise post-launch.
10. ✅ **OQ-10. [RESOLVED 2026-06-15]** Challenge submission CTA = **"Skryf in"** (terminology-guide-consistent: action "Skryf in", noun "inskrywing", confirmation "Jou inskrywing is ontvang"). Supersedes both originally-listed options ("Plaas jou bydrae" / "Dien jou inskrywing in"; "indien" is on the avoid-list). Applied in `ui-copy-translations.md`.
11. ⏸️ **OQ-11. [DEFERRED — founder review]** Validate the User Journeys (§2.3) against real INK sessions — several beats are `[ASSUMPTION]`.
12. ⬜ **OQ-12 (copy quality). [OPEN — human copy]** `ui-copy-translations.md:344` (*"Weerklank wen van bereik…"*) needs a **human-authored** Afrikaans replacement. Principle confirmed: INK's curated Afrikaans is the source of truth and must **never** be replaced by an AI/literal translation of the English mockup text. A broader human copy-quality pass on the UI copy doc is worthwhile. Owner: human Afrikaans copywriter.
13. ✅ **OQ-13 (terminology — propagated). [RESOLVED]** Term corrections applied across the PRD **and** source docs on 2026-06-14: `uitdagingsronde`→`uitdagingsrondte`; `intekenplan`→`aansluitingsopsie`; `vlakgeskiedenis`→`graderingsgeskiedenis`; `skrywervlak`→**"ster gradering"** (label) with taxonomy slug `` `skrywervlak` ``→`` `ster_gradering` ``. Added 2026-06-15: "Browse"→**"Ontdek"** locked into `afrikaans-terms.md`. Remaining: future `ink-core` code + migration scripts must use the new slugs (low-risk — new taxonomies, no legacy-slug/redirect impact).
14. ✅ **OQ-14 (`ink_writer_tier` alignment). [RESOLVED 2026-06-15]** **Keep** the user-meta key `ink_writer_tier` as-is (English, load-bearing; matches the `ink_` convention). Enum values `brons`/`silwer`/`goud` unchanged. No rename — avoids a meta-key migration.
15. ✅ **OQ-15 (design — drop signup intent). [RESOLVED 2026-06-15]** `lovable-design-sync` run 2026-06-15 — nothing new to propagate. No signup-intent step exists to remove: the auth/registration flow is **not in the current Lovable repo** (`page-map.csv` marks `auth` as assembly-only, to be built fresh in WP). The Community page's "Sluit aan as skrywer / leser" buttons are marketing entry CTAs that both route to a single registration — consistent with FR-2 (no intent gate).
16. ⏸️ **OQ-16 (reclassification + URLs). [DEFERRED — migration build]** Reclassification accuracy + `skryfwerk` URL handling (merges adversarial C-3 + edge-case EC-H5). Once migration code runs against real data: measure `skryfwerk` residue and set a classification target + drain plan/owner; run a per-CPT slug-uniqueness check and resolve collisions; if volume is low enough, hold `skryfwerk` items **slugless until classified** to avoid chained 301s (a knowingly-bounded gap vs SM-1's URL-301 line). **Owner:** Cobus (migration build).
17. ⏸️ **OQ-17 (account-lifecycle & POPIA spec). [DEFERRED — founder + legal, then build]** A dedicated spec for member **deletion** and **lapse** fan-out across the relational graph — extends OQ-3. Covers: deletion across volg / leeslys / reaksies / ratings / winner records / audit log (EC-C1); anonymise-vs-attribute decision + sentinel-author mechanism (EC-C2); follow edges/counts, lapsed-followee empty feed, follow idempotency (EC-H4); author-initiated unpublish/delete cascade (EC-M7); and the moderation-report intake half of the custom forms (H-2). **Owner:** founder + legal for the POPIA decisions (pre-launch), then an implementation spec before deletion/lapse features ship. *(Per standing rule: any delete-a-writer item lands here.)*
18. ⏸️ **OQ-18 (migration & build hardening). [DEFERRED — build]** A migration/build-time hardening checklist: cutover-boundary + expiry-cron/timezone subscription reconciliation (EC-C3); tier-import email-join rules — normalisation, double-match tie-break, failed-join vs default-Brons (EC-H6); write concurrency/idempotency on publish/purchase (EC-M4); `skryfwerk` front-end discovery contract (EC-M6); PayFast unhappy-path ITN ordering/idempotency (EC-M5 remainder) + a PayFast activation de-risking spike (M-4); public-form spam / rate-limit / deliverability / validation (H-2 build half); and retired-plan renewal handling (EC-M9). **Owner:** build-time.

---

## 17. Assumptions Index

*Every `[ASSUMPTION]` in the document, surfaced for confirmation. Status: ✅ confirmed/resolved · ⏸️ deferred (founder/legal/analytics). Reviewed 2026-06-15 — **3 confirmed, 3 deferred**.*

- ✅ **§4.13 / FR-61** — Contact-form tooling resolved → custom `ink-core` form (OQ-8, 2026-06-15).
- ✅ **§5** — IA verified against `docs/design-handoff/page-map.csv` (2026-06-15): all surface labels match. *InkPols* is in the IA but not yet in the Lovable repo (assembly-only surface, like Lidmaatskap / Kontak / Auth) — a not-yet-mocked surface, not a mislabel.
- ✅ **§15** — Quantitative targets **SM-3** (95% payment automation), **SM-4** (≥6 visits/month) and **SM-6** (50% feedback density) confirmed as **provisional launch targets** (2026-06-15), to be tuned against real baselines. ⏸️ **SM-5** (free→paid %) stays TBD pending an analytics baseline → OQ-5.
- ⏸️ **§2.3** — User Journeys UJ-1..UJ-6 drafted (Fast path) from the registration lifecycle + core flows; specific beats inferred. → OQ-11 (founder review).
- ⏸️ **§8** — POPIA compliance defaults (consent/lawful basis, access/correct/delete rights, retention); public-profile exposure with opt-out; first-response SLA; transactional-email-only. → OQ-3 (POPIA legal), OQ-4 (moderation SLA/owner), OQ-17 (deletion fan-out + lifecycle spec). *Moderation tooling resolved → custom `ink-core` form (OQ-4).*
- ⏸️ **§11** — Retention: personal data for account life; POPIA deletion preserves published **bydraes** attributed/anonymised (behaviour to confirm). → OQ-3 (legal + POPIA), OQ-17 (deletion fan-out + sentinel-author mechanism).

---

*Status: **final** (2026-06-15; **scope-increase update 2026-06-20** per the approved Sprint Change Proposal — editorial-automation launch pillar added: R1/R2 challenge adjudication, R3 automatic Gradering promotion + Meester tier, R5 lifecycle emails, R6 account approval/social-login, R7 receipt notification, R8 analytics + read counts, R9 annual competition noted P2; G1 terminology collapse — lidmaatskap/gratis lid/betaalde lid, Gradering, Skrywerprofiel public / My Profiel private). Validated (graded Fair → all findings dispositioned), reconciled against source docs (incl. the 2026-06-20 `afrikaans-terms.md`), regression-checked, and finalized. Next: `bmad-create-architecture` → `bmad-create-epics-and-stories`. Deferred items tracked in §16 (OQ-3/5/6/9/11/16/17/18) + §17; one open copy item (OQ-12).*
