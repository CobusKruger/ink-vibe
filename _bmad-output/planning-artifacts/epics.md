---
stepsCompleted: ['document-discovery', 'requirements-inventory', 'epic-design', 'story-creation']
inputDocuments:
  - '_bmad-output/planning-artifacts/prds/prd-ink-vibe-2026-06-14/prd.md'
  - '_bmad-output/planning-artifacts/architecture.md'
  - '_bmad-output/planning-artifacts/ux-designs/ux-ink-vibe-2026-06-15/EXPERIENCE.md'
  - '_bmad-output/planning-artifacts/ux-designs/ux-ink-vibe-2026-06-15/DESIGN.md'
  - '_bmad-output/planning-artifacts/sprint-change-proposal-2026-06-20.md'
  - 'docs/specs/ink-feature-list.md (superseded input — content folded in here)'
  - 'docs/specs/ink-consolidated-spec.md (companion detail)'
generatedDate: '2026-06-20'
sourceOfRecord: true
---

# ink-vibe - Epic Breakdown

## Overview

This document is the **canonical epic and story breakdown** for the INK rebuild, decomposing the requirements from the PRD, UX Design, and Architecture into implementable stories. It supersedes `docs/specs/ink-feature-list.md` as the source of record for epics/stories (that file is retained as a marked companion input). It incorporates the **2026-06-20 Correct Course** change (sprint-change-proposal-2026-06-20.md): the editorial-automation launch pillar (R1–R9) and terminology refinements (G1).

**Conventions.** Layer tags — `T` theme · `K` ink-core · `P` platform plugin. Priority — `P0` launch-critical · `P1` at-launch · `P2` fast-follow. Afrikaans terms are glossary-canonical (`docs/afrikaans-terms.md`) and used verbatim; story scaffolding is English by project rule (no AI-generated Afrikaans). Epics are in **build/dependency order**.

**Binding rules that span the whole breakdown:**
- **THE conflation rule** — *lidmaatskap* (active WooCommerce Membership: betaalde lid vs gratis lid) controls **submission entitlement**; *Gradering* (`ink_writer_tier`) controls **competition pools**. Never conflated in data or code (`Ink\Tiers` ⟂ `Ink\Entitlement`).
- **Three-layer separation** — no business logic in the theme.
- **Afrikaans-first, zero English leakage** (NFR-1, Quality Gate D).
- **Hard build order for the editorial-automation pillar:** R1 (EntryID + collation) → R2 (paste ingestion) → R3 (auto-promotion).

---

## Requirements Inventory

### Functional Requirements

| FR | Title | Pri |
|---|---|---|
| FR-1 | Register, log in, reset password | P0 |
| FR-2 | Unified account — no signup intent gate | P0 |
| FR-3 | Post-signup first social action prompt | P1 |
| FR-3a | Account approval, social login & anti-spam (R6) | P1 + spike |
| FR-4 | Membership products — configurable price & term | P0 |
| FR-5 | Self-service PayFast purchase | P0 |
| FR-6 | Access enforcement — entitlement gate | P0 |
| FR-7 | Lidmaatskap page | P0 |
| FR-8 | Renew membership from profile | P1 |
| FR-9 | Afrikaans access/status messaging | P1 |
| FR-9a | Membership lifecycle emails (R5) | P1 |
| FR-10 | Suppress storefront UI | P1 |
| FR-63 | Auto-renew / recurring billing (+ discount carve-out) | P2 (deferred) |
| FR-11 | Tier data model (+ win-count meta) | P0 |
| FR-12 | Staff set/adjust tier with reason + log | P0 |
| FR-12a | Automatic challenge-driven promotion (R3) | P0 |
| FR-13 | Tier ≠ membership guardrail | P0 |
| FR-14 | Tier display on profiles (+ wins-needed subtext) | P1 |
| FR-15 | Tier in discovery & winners | P1 |
| FR-16 | Custom front-end submission form | P0 |
| FR-17 | Content-type selection with counters | P0 |
| FR-18 | Light editor | P0 |
| FR-19 | Publishing gated to active betaalde lede | P0 |
| FR-20 | Optional featured image | P1 |
| FR-21 | Optional audio/video attachment | P1 |
| FR-22 | Link a piece to an active challenge | P1 |
| FR-23 | Save draft / publish with success prompt | P1 |
| FR-24 | Reading templates for prose | P0 |
| FR-25 | Poetry reading layout | P1 |
| FR-26 | Line highlight + reactions | P1 |
| FR-27 | Structured community responses | P1 |
| FR-28 | Reaction storage + counts | P1 |
| FR-29 | Reading list | P1 |
| FR-30 | Contextual guided prompts | P2 |
| FR-31 | Suggested next reads | P2 |
| FR-32 | Discovery hub + works archive | P0 |
| FR-33 | Browse bydraes tab | P1 |
| FR-34 | Browse skrywers tab | P1 |
| FR-35 | Search works and writers | P1 |
| FR-36 | Personalised discovery surfaces | P2 |
| FR-37 | BuddyPress scope configuration | P0 |
| FR-38 | One-way follow | P0 |
| FR-39 | Following-feed | P1 |
| FR-40 | Block-theme profiles (My Profiel / Skrywerprofiel) | P1 |
| FR-41 | Pinned/selected works | P1 |
| FR-42 | Reader ratings & reviews | P1 |
| FR-43 | Member directory | P1 |
| FR-44 | Notifications | P1 |
| FR-44a | Automatic post-receipt notification (R7) | P1 |
| FR-44b | Analytics provider + read counts (R8) | P1 |
| FR-45 | Challenge single page | P1 |
| FR-46 | Challenges list page | P1 |
| FR-47 | Challenge metadata + monthly cadence | P1 |
| FR-48 | Submit a challenge entry | P1 |
| FR-49 | Tier-based competition pools | P1 |
| FR-50 | Queryable placement records | P0 |
| FR-50-R1 | Challenge-entry collation → judge email (R1) | P0 |
| FR-50-R2 | Results ingestion + winners announcement + feedback (R2) | P0 |
| FR-51 | Winner → promotion link | P1 |
| FR-52 | Library archive + single | P1 |
| FR-53 | Link winners ↔ challenge | P2 |
| FR-54 | Training hub + faceted search | P1 |
| FR-55 | Auto cross-surfacing of training | P2 |
| FR-56 | Editor's shelf + community guides | P2 |
| FR-57 | InkPols issue model, archive & PDF viewing | P1 |
| FR-58 | Sponsor model, scheduling & placement | P1 |
| FR-59 | Homepage (Tuisblad) | P0 |
| FR-60 | Marketing & org pages | P1 |
| FR-61 | Contact form | P1 |
| FR-62 | Theme-native footer | P1 |

### NonFunctional Requirements

| NFR | Title |
|---|---|
| NFR-1 | Afrikaans-first, zero English leakage (Quality Gate D) — automated leak scan, standing gate, covers admin-authored form-letter store |
| NFR-2 | Design-token compliance (Quality Gate A) — no hardcoded colours/spacing/type |
| NFR-3 | Performance & caching — LiteSpeed + Cloudflare; light front-end JS |
| NFR-4 | SEO & URL integrity — 301s, Rank Math schema, preserved prefixes |
| NFR-5 | Accessibility & readability — Afrikaans legibility first |
| NFR-6 | Maintainability for non-technical staff — Site Editor stability, block locking |
| NFR-7 | Reliability & update governance — staging-gated majors, committed `.mo` |
| NFR-8 | Observability — 404 logging, Patchstack alerts, leak scan as CI/cron gate |
| NFR-9 | Test harness (foundational, Epic 1) — pyramid concentrated in `ink-core`, PayFast sandbox only |

### Additional Requirements

**2026-06-20 editorial-automation pillar (sprint-change-proposal):**
- **R1** — Auto-collate challenge entries into an anonymized judge email (assigns EntryID). P0.
- **R2** — Ingest pasted judges' results & commentary → winners post, moderator feedback, banners, featuring; triggers R3. P0. **No `.docx` parser (paste-only)**; simple form-letter templates only.
- **R3** — Automatic Gradering calculation & promotion (5/15 thresholds); new manual-only **Meester** tier. P0.
- **R4** — Automatic Biblioteek entry update — **P0 stub/hook only**, body deferred.
- **R5** — Membership lifecycle automation (activation + expiry emails, per-term config). P1. Recurring + discount post-launch.
- **R6** — Account approval & spam detection (research spike → social login + optional backstop). P1.
- **R7** — Automatic post-receipt notification (randomized message). P1.
- **R8** — Analytics provider + private-profile read counts. P1.
- **R9** — Annual competition management (reuses R1/R2/R3). P2.
- **G1** — Terminology refinements (lid family; Gradering; Skrywerprofiel public / My Profiel private; Meester; EntryID; algehele wenner / wenner; Terugvoer van die moderator). Cross-cutting P0.

**Owner decisions (2026-06-20):** recurring billing deferred; recurring discount allowed post-launch (amends §14.5); terms stay 1/6/12; R1+R2 both P0 with paste-only + simple form-letters; auto-promotion accepted at P0; moderator feedback as custom comment type; Skrywerprofiel public / My Profiel private; `lid` family canonical; R6 manual approval optional/off-by-default; Meester colour = brand `primary #EA4015`.

**Cross-cutting acceptance criteria (every epic):** three-layer compliance · token compliance (Gate A) · Afrikaans-first (Gate D) · Gradering ≠ lidmaatskap · editorial low-friction (no per-item manual linking) · Site Editor stability.

### UX Design Requirements

Source: `ux-designs/ux-ink-vibe-2026-06-15/` (EXPERIENCE.md + DESIGN.md). Named-protagonist journeys (PRD §2.3):
- **UJ-1** — A besoeker joins to share a first poem (frictionless signup; no reader/writer gate).
- **UJ-2** — A lid subscribes (PayFast) and publishes (becomes betaalde lid).
- **UJ-3** — A lid gives a writer a meaningful, structured response (Gemeenskapsreaksies; reaksies).
- **UJ-4** — A skrywer enters the monthly uitdaging, judged in their Gradering pool.
- **UJ-5** — A redakteur promotes a writer without busywork (now auto-promotion + manual Meester via **Handmatige bevordering**).
- **UJ-6** — Migration day: an existing member notices nothing broke.

**Design spine:** Brand warm/literary; Lora (display/heading) + Inter (body/UI); content width ~768px; sentence-case Afrikaans headings; counts shown verb-less. Colours/type/spacing via `theme.json` tokens only.

**Admin surfaces (redakteur) — WP admin chrome, no design-system work:** R1 collation, R2 ingestion, R3 manual promotion, R5 email config, R6 approval queue. Their **interaction flow + states + Afrikaans labels** are specified as acceptance criteria (Epic 12A admin-flow ACs), not visual mockups.

**State patterns:** "Pending approval (wag op goedkeuring)" optional/off-by-default (R6); winner banner per-rank variants (algehele wenner vs wenner) with Brons/Silwer/Goud tokens and Meester = `primary #EA4015`, colour paired with text/icon (a11y, no colour-only encoding).

### FR Coverage Map

| FR | Covered by stories |
|---|---|
| FR-1 | 3.1 |
| FR-2 | 3.2 (removed intent), 3.3 |
| FR-3 | 3.3 |
| FR-3a | 3.4, 3.5, 3.6 (+ 18.10) |
| FR-4 | 4.1 |
| FR-5 | 4.2 |
| FR-6 | 4.3 |
| FR-7 | 4.4 |
| FR-8 | 4.5 |
| FR-9 | 4.7 |
| FR-9a | 4.8 (+ 1.12) |
| FR-10 | 4.6 |
| FR-63 | 4.9, 4.10, 4.11 |
| FR-11 | 5.1, 5.7 |
| FR-12 | 5.2, 5.3 |
| FR-12a | 5.8 (+ 5.7 reset, 5.10 email) |
| FR-13 | 5.6 |
| FR-14 | 5.4, 5.9 |
| FR-15 | 5.5 |
| FR-16 | 6.1 |
| FR-17 | 6.2 |
| FR-18 | 6.3 |
| FR-19 | 6.8 |
| FR-20 | 6.4 |
| FR-21 | 6.5 |
| FR-22 | 6.6 |
| FR-23 | 6.7 |
| FR-24 | 7.1 |
| FR-25 | 7.2 |
| FR-26 | 7.3 |
| FR-27 | 7.4 |
| FR-28 | 7.8 |
| FR-29 | 7.7 |
| FR-30 | 7.5 |
| FR-31 | 7.6 |
| FR-32 | 8.1 |
| FR-33 | 8.2 |
| FR-34 | 8.3 |
| FR-35 | 8.4 |
| FR-36 | 8.5 |
| FR-37 | 9.1 |
| FR-38 | 9.2 |
| FR-39 | 9.3 |
| FR-40 | 9.4 |
| FR-41 | 9.5 |
| FR-42 | 9.6 |
| FR-43 | 9.7 |
| FR-44 | 9.9 |
| FR-44a | 9.11 |
| FR-44b | 9.12 (+ 18.9) |
| FR-45 | 12.1 |
| FR-46 | 12.2 |
| FR-47 | 12.3 |
| FR-48 | 12.4 |
| FR-49 | 12.5 |
| FR-50 | 12.6 |
| FR-50-R1 | 12A.1, 12A.2 |
| FR-50-R2 | 12A.3, 12A.4, 12A.5, 12A.6, 12A.7 (+ 15.6) |
| FR-51 | 12.7 |
| FR-52 | 10.1 |
| FR-53 | 10.5 |
| FR-54 | 11.1, 11.2 |
| FR-55 | 11.4 |
| FR-56 | 11.3, 11.5 |
| FR-57 | 13.1, 13.2, 13.3 |
| FR-58 | 14.1, 14.2, 14.3, 14.4 |
| FR-59 | 15.1 |
| FR-60 | 15.2, 15.3 |
| FR-61 | 15.4 |
| FR-62 | 15.5 |

*Stories without a direct FR (foundation, content models, migration, localisation execution, SEO/security/perf, R4 stub, annual competition) trace to NFRs and Additional Requirements: Epic 1 → NFR-1/2/6/9 (+ 1.12 → R5/R7/R3 shared form-letter store); Epic 2 → data-model substrate for all FRs; 10.6 → R4; 12B → R9; Epic 16 → NFR-4; Epic 17 → NFR-1; Epic 18 → NFR-1/3/4/7/8/9 + R6/R8.*

---

## Epic List

| Epic | Title | P0 stories |
|---|---|---|
| 1 | Foundation (theme + tokens + ink-core scaffold) | 8 |
| 2 | Content models & taxonomy | 3 |
| 3 | Accounts, registration & auth | 2 |
| 4 | Membership, access & payment | 4 |
| 5 | Writer Gradering (Brons / Silwer / Goud / Meester) | 5 |
| 6 | Submission workflow (custom) | 4 |
| 7 | Reading & engagement | 1 |
| 8 | Discovery (Ontdek) | 1 |
| 9 | Community & social | 2 |
| 10 | Library (Biblioteek) | 1 |
| 11 | Training (Opleiding) | 0 |
| 12 | Challenges (Uitdagings) & winners | 1 |
| 12A | Challenge adjudication automation (NEW) | 7 |
| 12B | Annual competition management (NEW, P2) | 0 |
| 13 | InkPols | 0 |
| 14 | Sponsors (Borge) | 0 |
| 15 | Organisation pages & contact | 1 |
| 16 | Migration & redirects | 6 |
| 17 | Afrikaans-first & localisation (execution + QA) | 1 |
| 18 | SEO, security & performance | 2 |

---

## Epic 1: Foundation (theme + tokens + ink-core scaffold)

Establish the block theme, design-token system, and the `ink-core` plugin scaffold — including the foundational i18n/admin-language mechanism and test harness — so every later epic builds on token-compliant, Afrikaans-first, test-first foundations rather than retrofitting them.

### Story 1.1: theme.json design-token system

As a theme developer,
I want all design tokens mapped into `theme.json` from the normalized token file,
So that no template or pattern uses hardcoded values and Quality Gate A can pass.

**Acceptance Criteria:**

**Given** `docs/design-handoff/tokens/theme-tokens.json` and `token-map.md`
**When** the theme is built
**Then** all colour, type, spacing, layout, radius, and shadow tokens are defined in `theme.json` under their production names
**And** a review of templates/patterns finds zero hardcoded colours/spacing/unnamed type sizes (Gate A).

### Story 1.2: Typography system

As a reader,
I want legible, Afrikaans-first typography,
So that reading is comfortable and on-brand.

**Acceptance Criteria:**

**Given** the typography tokens
**When** any heading or body text renders
**Then** Lora is used for display/headings and Inter for body/UI, on a named scale (xs–3xl), fluid where appropriate
**And** headings render in sentence case.

### Story 1.3: Dark mode tokens

As a reader,
I want a dark palette,
So that I can read comfortably in low light.

**Acceptance Criteria:**

**Given** the dark palette in the token file
**When** dark mode is active
**Then** the theme resolves colours from the dark token set with no hardcoded values.

### Story 1.4: Global templates & template parts

As a content manager,
I want global header, footer, and section shells,
So that pages share consistent structure.

**Acceptance Criteria:**

**Given** the FSE theme
**When** templates load
**Then** header, footer, and reusable section shells exist as template parts and render across the site.

### Story 1.5: Core block-pattern library

As a content manager,
I want a library of core block patterns,
So that I can assemble non-mocked pages from approved building blocks.

**Acceptance Criteria:**

**Given** the pattern library
**When** I insert patterns in the Site Editor
**Then** hero, featured grid, archive intro, CTA bands, profile summaries, and card/button/emphasis variants are available and token-compliant.

### Story 1.6: Block locking strategy

As a site owner,
I want critical editorial structure locked while content stays editable,
So that non-technical staff can edit safely without breaking layout.

**Acceptance Criteria:**

**Given** locked patterns/templates
**When** a staff member edits in the Site Editor
**Then** critical structure cannot be deleted/moved but content remains editable.

### Story 1.7: ink-core plugin scaffold

As an ink-core developer,
I want a bootstrapped plugin structure,
So that all business logic has a home with activation hooks and i18n loading.

**Acceptance Criteria:**

**Given** the `ink-core` plugin
**When** it activates
**Then** the bootstrap, `includes/` structure, activation hooks, and text-domain loading are in place (`declare(strict_types=1)`, `Ink\` namespace, `ink_` prefixing).

### Story 1.8: Comment-disable filters

As a site owner,
I want WP comments disabled site-wide,
So that engagement flows only through the sanctioned structured paths.

**Acceptance Criteria:**

**Given** the comment-disable filters
**When** any post type is rendered or queried
**Then** `comments_open`/`pings_open` resolve to false
**And** the moderator-feedback comment type (12A.5) remains the only programmatic exception.

### Story 1.9: Page archetypes A–D documented & built

As a content manager,
I want reusable page archetypes,
So that non-mocked pages have consistent scaffolds.

**Acceptance Criteria:**

**Given** archetypes A–D
**When** building a new page
**Then** a documented archetype scaffold is available and used.

### Story 1.10: Locale af + i18n scaffolding & admin-language mechanism

As an Afrikaans-first product,
I want the locale, i18n scaffolding, and admin-language mechanism established in the Foundation phase,
So that Afrikaans-first is built-in, not retrofitted (Principle 3 / §12).

**Acceptance Criteria:**

**Given** site locale `af`
**When** any custom string is output on the front end
**Then** it is internationalised via gettext with the correct text domain and renders Afrikaans (sentence-case headings).
**Given** an editor/administrator account
**When** they use wp-admin
**Then** their admin language is forced to English via per-user WP language set by `ink-core` (§14.14), while front-end output stays Afrikaans.
**Given** `ink-core`'s own admin labels/screens
**When** rendered under the English admin locale
**Then** they show Afrikaans because they are authored as source language with **no English `.mo`** shipped (§14.15).

### Story 1.11: Test harness scaffold

As an ink-core developer,
I want the unit + integration test harness wired into CI from the start,
So that every P0 rule ships test-first (§14.17).

**Acceptance Criteria:**

**Given** the Foundation phase
**When** CI runs
**Then** Pest/PHPUnit + Brain Monkey/WP_Mock (unit) and the `wp-env` integration harness are wired in
**And** P0 rules (tier promotion 5.x, submission gate 6.8, follow graph 9.2) can land with their tests. (Full pyramid buildout is 18.8.)

### Story 1.12: Form-letter + notification capability (foundation)

As an ink-core developer,
I want the lightweight form-letter + notification capability established in Foundation,
So that its downstream consumers (R2/R3/R5/R7) build on a ready shared template store rather than a forward dependency. (R5/R7/R3 shared foundation; AD-9) *(Relocated from former 12A.0, 2026-06-20 — it is P0-Foundation scope, §14.1, and is consumed by earlier epics 4/5/9.)*

**Acceptance Criteria:**

**Given** the Notifications module (options-based, minimal dependencies — WP options + Kernel only, per AD-9)
**When** the capability is built
**Then** it stores form-letter text (WP options) + name-merge greeting (e.g. "Beste {skrywer}, …") + per-event send on/off toggles + a randomized message list — **not** a rich template engine
**And** it is consumed by 12A.3/12A.4 (R2), 5.10 (R3), 4.8 (R5), and 9.11 (R7) — all of which now depend **backwards** on this Foundation story, not forwards
**And** the option store is in scope for the NFR-1 leak scan (admin-authored text bypasses build-time `.mo`).

---

## Epic 2: Content models & taxonomy

Register the CPTs, taxonomies, and user meta that form the data substrate for every feature — using the exact migration-load-bearing Afrikaans code IDs — so all later epics read and write a stable, shared model.

### Story 2.1: Register CPTs

As an ink-core developer,
I want the INK custom post types registered with Afrikaans slugs,
So that all content has a typed home and migration can map onto it.

**Acceptance Criteria:**

**Given** `ink-core` activation
**When** CPTs register
**Then** `gedig`, `storie`, `artikel`, `skryfwerk`, `biblioteek_item`, `opleiding_artikel`, `uitdaging`, `inkpols_uitgawe`, `borg` exist with Afrikaans slugs per the terms guide and exact code IDs (migration-load-bearing).

### Story 2.2: Register taxonomies

As an ink-core developer,
I want the shared taxonomies registered,
So that training and contributions auto-surface via shared terms (no manual linking).

**Acceptance Criteria:**

**Given** `ink-core` activation
**When** taxonomies register
**Then** `genre`, `vaardigheid`, `uitdagingsrondte`, `ster_gradering` exist
**And** `genre`/`vaardigheid` are shared across bydraes and training for auto-surfacing.

### Story 2.3: User meta

As an ink-core developer,
I want the writer-tier user meta defined,
So that Gradering state has a stable store independent of membership.

**Acceptance Criteria:**

**Given** a user
**When** tier meta is read
**Then** `ink_writer_tier` and `ink_tier_promoted_at` exist (default `brons`); `ink_tier_win_count` is added in 5.7.

### Story 2.4: CPT admin field sets

As a redakteur,
I want per-CPT meta fields in admin,
So that I can manage issue/challenge/sponsor details.

**Acceptance Criteria:**

**Given** the relevant CPT edit screen
**When** I edit an item
**Then** the right meta is available (e.g. InkPols issue date/volume/cover/PDF/teaser; challenge theme/deadline; sponsor link/tier/dates/placement) with Afrikaans `ink-core` labels.

### Story 2.5: Term images native

As a content manager,
I want native term images,
So that the WPCustom Category Image plugin can be retired.

**Acceptance Criteria:**

**Given** the native term-image capability
**When** migration runs
**Then** the 11 existing term images are reassigned and the legacy plugin is no longer required.

---

## Epic 3: Accounts, registration & auth

Provide Afrikaans-native authentication and the unified-account model (no reader/writer intent gate) plus the R6 registration-defense layer — the foundational prerequisite for membership, submission, and community features.

### Story 3.1: Authentication pages

As a besoeker,
I want Afrikaans Registreer / Meld aan / Wagwoord-herstel flows,
So that I can create and access my account entirely in Afrikaans. (FR-1)

**Acceptance Criteria:**

**Given** the auth surfaces (assembly-only, no mock)
**When** I register, log in, or reset a password
**Then** all screens and emails render in Afrikaans with no English string
**And** a new account defaults to **Brons** Gradering and **gratis lid** (no active lidmaatskap).

### Story 3.2: Remove reader/writer intent capture (removed 2026-06-14)

As a product owner,
I want no reader/writer choice at signup,
So that any lid can publish once they hold an active lidmaatskap. (FR-2)

**Acceptance Criteria:**

**Given** registration
**When** a lid signs up
**Then** no reader/writer intent flag (e.g. `ink_writer_intent`) is captured or stored
**And** the reader/writer distinction is behavioral, not a stored identity.

### Story 3.3: Registration lifecycle / onboarding

As a new lid,
I want to complete my profile and be prompted to take a first social action,
So that I am gently onboarded without being blocked. (FR-2, FR-3)

**Acceptance Criteria:**

**Given** a completed signup
**When** onboarding runs
**Then** the lid creates an account → completes profile (gratis lid) → is prompted to follow a skrywer or save a bydrae to leeslys
**And** the prompt is skippable, does not block completion, and degrades gracefully with a thin catalogue.

### Story 3.4: Anti-spam research spike (R6)

As a redakteur,
I want anti-spam approaches evaluated before building,
So that registration defenses are chosen on evidence (owner: "I know nothing about this"). (FR-3a)

**Acceptance Criteria:**

**Given** the spike
**When** it completes
**Then** a documented build decision exists evaluating anti-spam / account-abuse approaches
**And** it gates stories 3.5 and 3.6.

### Story 3.5: Social login (R6)

As a besoeker,
I want to sign up / in via a vetted social provider,
So that signup friction is reduced while abuse is curbed. (FR-3a)

**Acceptance Criteria:**

**Given** a vetted platform plugin (hooks only, not reimplemented in `ink-core`)
**When** I use the auth surface
**Then** social-login buttons are present and functional.

### Story 3.6: Optional manual-approval backstop (R6)

As a redakteur,
I want an off-by-default approval queue,
So that I can require manual approval only if abuse warrants it, without harming the default frictionless signup. (FR-3a, C8)

**Acceptance Criteria:**

**Given** the backstop is **off by default**
**When** a redakteur enables it
**Then** new accounts enter a "wag op goedkeuring" state and an approval queue (admin screen) exists
**And** when off, signup stays frictionless (UJ-1).

---

## Epic 4: Membership, access & payment

Sell fixed-term lidmaatskap products with self-service PayFast activation, enforce submission entitlement strictly (independent of Gradering), and automate the membership lifecycle with Afrikaans emails — replacing manual EFT activation (UJ-2).

### Story 4.1: Three fixed-term lidmaatskap products

As a betaalde lid,
I want to choose a fixed-term lidmaatskap,
So that I can pay for the access term that suits me. (FR-4)

**Acceptance Criteria:**

**Given** WooCommerce products/Memberships
**When** plans are listed
**Then** R60/1mo, R300/6mo, R600/12mo are offered (terms stay 1/6/12), no auto-renew at launch
**And** a redakteur can change price/term or add/retire a plan via WooCommerce admin (no hardcoded values)
**And** no vanity discount/savings framing appears at launch.

### Story 4.2: Front-end PayFast purchase flow

As a lid,
I want to buy and self-activate a lidmaatskap via PayFast in ZAR,
So that I become a betaalde lid with no staff action. (FR-5, UJ-2)

**Acceptance Criteria:**

**Given** a successful PayFast return
**When** payment completes
**Then** the lidmaatskap activates automatically (no manual EFT/admin step) and a thank-you/activation email is sent (4.8)
**And** card data is never stored (PayFast off-site)
**And** tests use the PayFast **sandbox** only.

### Story 4.3: Access enforcement (entitlement gate)

As the system,
I want to grant submission entitlement iff a lid has an active WooCommerce Membership,
So that entitlement tracks lidmaatskap and nothing else. (FR-6)

**Acceptance Criteria:**

**Given** an active lidmaatskap
**When** entitlement is evaluated
**Then** the betaalde lid may plaas; on expiry/suspension entitlement auto-revokes (reverts to gratis lid)
**And** revoking entitlement does not delete the account, change `ink_writer_tier`, or unpublish bydraes.

### Story 4.4: Lidmaatskap page

As a besoeker,
I want a Lidmaatskap page,
So that I can compare plans and start a purchase. (FR-7)

**Acceptance Criteria:**

**Given** the Lidmaatskap page (assembly-only, pricing-table pattern)
**When** it renders
**Then** it shows plans, benefits, FAQ, and CTA.

### Story 4.5: Renewal UI

As a lid,
I want to renew from My Profiel,
So that I can extend access by choosing a term. (FR-8)

**Acceptance Criteria:**

**Given** My Profiel → Lidmaatskap tab
**When** I renew
**Then** I can choose 1/6/12 months with prices shown as configured (R60/R300/R600) and **no discount/savings labels** at launch.

### Story 4.6: Store-UI suppression

As a site owner,
I want WooCommerce storefront UI hidden,
So that the site reads as a community, not a shop. (FR-10)

**Acceptance Criteria:**

**Given** WooCommerce active
**When** a visitor browses
**Then** cart/catalog/checkout are suppressed beyond the lidmaatskap purchase flow.

### Story 4.7: Status messaging (Afrikaans)

As a lid,
I want Afrikaans status messages for my lidmaatskap,
So that I always understand my access state. (FR-9)

**Acceptance Criteria:**

**Given** active / expired / access-denied / payment-failed states
**When** a message renders
**Then** it uses the lid-family Afrikaans copy (e.g. "Jou lidmaatskap is aktief…", "Jou lidmaatskap het verval…", "Jou betaling het misluk of is gekanselleer…").

### Story 4.8: Lidmaatskap lifecycle emails (R5)

As a lid,
I want Afrikaans lifecycle emails around my term,
So that I'm thanked on activation and warned before expiry. (FR-9a)

**Acceptance Criteria:**

**Given** the simple form-letter store (Story 1.12; plain text + name-merge greeting, not a rich engine)
**When** lifecycle events fire
**Then** a thank-you email is sent on every activation; a 1-week-prior warning on every term and a 1-month-prior warning on longer terms (sharing the FR-44 expiry anchor)
**And** each email type is toggleable on/off per term length (1/6/12) by staff
**And** the form-letter copy is in scope for the NFR-1 leak gate
**And** recurring billing, the renewal-warning variant, and recurring discount are post-launch.

### Story 4.9: Auto-renew (recurring) — post-launch

As a lid,
I want optional automatic renewal,
So that my lidmaatskap continues without re-purchase. (FR-63, deferred)

**Acceptance Criteria:**

**Given** post-launch scope (§14.8, OQ-9)
**When** recurring is built
**Then** PayFast recurring support + extension compatibility are verified first; until then renewal is the manual 4.5 flow.

### Story 4.10: Recurring-renewal warning variant — post-launch

As a lid on recurring billing,
I want a renewal-warning email variant,
So that I'm notified before an automatic charge. (FR-63, deferred)

**Acceptance Criteria:**

**Given** recurring shipped (4.9)
**When** a renewal approaches
**Then** the recurring-renewal warning variant is sent. (Post-launch; depends on 4.9.)

### Story 4.11: Recurring-renewal discount — post-launch

As a lid on recurring billing,
I want a genuine recurring discount,
So that auto-renewing is rewarded. (FR-63, §14.5 amended 2026-06-20)

**Acceptance Criteria:**

**Given** recurring shipped (4.9)
**When** the discount applies
**Then** a genuine recurring discount is offered with no vanity "%-off" framing. (Post-launch.)

---

## Epic 5: Writer Gradering (Brons / Silwer / Goud / Meester)

Model and operate the writer Gradering — including the new manual-only **Meester** tier, win-count tracking, and the **automatic promotion engine** (R3) — kept strictly independent of lidmaatskap (THE conflation rule). UI term is always "Gradering", never "tier".

### Story 5.1: Gradering data model

As an ink-core developer,
I want the Gradering enum modelled with Meester,
So that tiers have a typed, persisted store. (FR-11, R3)

**Acceptance Criteria:**

**Given** the Kernel `Tier` enum
**When** a writer's Gradering is read
**Then** `ink_writer_tier` ∈ {brons, silwer, goud, **meester**}, default brons
**And** Meester is a manual-only terminal state, never auto-promoted, rendered in brand red-orange `primary #EA4015` (not `danger`).

### Story 5.2: Staff set/adjust Gradering admin UI

As a redakteur,
I want to set a writer's Gradering in any direction with a reason,
So that I can promote, correct, or assign Meester with an audit trail. (FR-12, UJ-5)

**Acceptance Criteria:**

**Given** the admin UI
**When** I change a Gradering
**Then** I can set it in any direction (incl. Meester — the only path to Meester), record a reason, optionally link a challenge result, and a change-log entry is written.

### Story 5.3: Promotion log / history (graderingsgeskiedenis)

As a redakteur,
I want an auditable Gradering history,
So that every change (manual or auto) is traceable. (FR-12)

**Acceptance Criteria:**

**Given** any Gradering change
**When** it commits
**Then** a log record stores actor (system for auto), date, reason, from→to, and optional challenge link.

### Story 5.4: Gradering display on profiles

As a reader,
I want to see a writer's Gradering on their profile,
So that I understand their standing. (FR-14)

**Acceptance Criteria:**

**Given** a profile
**When** it renders
**Then** Brons/Silwer/Goud/Meester shows on public Skrywerprofiel and private My Profiel
**And** Meester renders in `primary #EA4015`, paired with text/icon (no colour-only encoding, a11y).

### Story 5.5: Gradering in discovery & winners

As a reader,
I want Gradering used in discovery and winner labels,
So that I can filter and understand competition context. (FR-15)

**Acceptance Criteria:**

**Given** discovery filters and challenge results
**When** they render
**Then** writers can be filtered by Gradering, participation is segmented, and winners are labelled (e.g. "Oktober Goud-wenner").

### Story 5.6: Gradering ≠ lidmaatskap guardrails

As an ink-core developer,
I want code-level guardrails separating Gradering and lidmaatskap,
So that the conflation rule cannot be violated. (FR-13)

**Acceptance Criteria:**

**Given** the data layer
**When** any membership-state change occurs
**Then** there is no write path to `ink_writer_tier` (or vice-versa); `Ink\Tiers` never reads Entitlement
**And** unit tests assert each membership-state transition leaves Gradering unchanged (Deptrac AD-8 passes).

### Story 5.7: Win-count meta + reset-on-promotion

As an ink-core developer,
I want a win-count meta that resets on promotion,
So that the auto-promotion engine has a counter. (FR-11, R3)

**Acceptance Criteria:**

**Given** `ink_tier_win_count`
**When** a writer earns top-3 placements
**Then** the count accumulates toward the next Gradering and is reset to 0 by `Tiers::promote()` on every promotion.

### Story 5.8: Automatic promotion engine

As a skrywer,
I want automatic promotion on challenge wins,
So that my Gradering advances without staff busywork. (FR-12a, R3, UJ-4)

**Acceptance Criteria:**

**Given** placement records from R2 ingestion (12A.3)
**When** the engine runs as R2's final step
**Then** a *win* = any top-3 placement, any entry type, at the writer's current Gradering (multiple each count); **Brons→Silwer at 5**, **Silwer→Goud at 15**; Goud/Meester have no auto-threshold
**And** the engine lives in `Ink\Tiers` and never reads Entitlement.

### Story 5.9: My Profiel "wins needed" subtext

As a skrywer,
I want to see how many wins I need for the next Gradering,
So that I understand my progression. (FR-14, R3)

**Acceptance Criteria:**

**Given** the private My Profiel
**When** it renders for a Brons/Silwer writer
**Then** it shows e.g. "4 top 3 uitslae nodig om Silwer te bereik" using `_n()` plurals
**And** the subtext is hidden at Goud/Meester.

### Story 5.10: Promotion congratulation email

As a skrywer,
I want a congratulation email on auto-promotion,
So that I'm recognised when I advance. (FR-12a, R3)

**Acceptance Criteria:**

**Given** an auto-promotion
**When** it commits
**Then** a templated congratulation email is sent via the Story 1.12 form-letter store (e.g. "Baie geluk! Jy is na Silwer bevorder.").

---

## Epic 6: Submission workflow (custom)

Replace the legacy Youzify form with a custom `ink-core` front-end submission workflow (the Skryf page) gated strictly on active lidmaatskap, with type-aware fields, a light editor that preserves poetic structure, drafts, and challenge linking.

### Story 6.1: Custom front-end submission form

As a skrywer,
I want a custom front-end submission form,
So that I can submit a gedig/storie/artikel without the legacy plugin. (FR-16)

**Acceptance Criteria:**

**Given** the Skryf page
**When** I submit
**Then** type-appropriate fields and validation serve gedig/storie/artikel (replacing Youzify FES).

### Story 6.2: Content-type selector with counters

As a skrywer,
I want a content-type selector with per-type counters,
So that I get the right fields and feedback. (FR-17)

**Acceptance Criteria:**

**Given** the selector
**When** I pick poem/story/article
**Then** per-type placeholders and counters show (lines **and** words for gedig; words for prose).

### Story 6.3: Light editor

As a skrywer,
I want a light editor that preserves line structure,
So that shaped/concrete poetry survives. (FR-18)

**Acceptance Criteria:**

**Given** the editor
**When** I write
**Then** allowed marks are hard line breaks, blank-line/stanza preservation, bold, italic; line structure and leading whitespace are preserved verbatim (not collapsed)
**And** no headings, tables, inline images/embeds, or font/colour/size controls exist.

### Story 6.4: Optional featured image

As a skrywer,
I want to add a featured image,
So that my bydrae has a visual. (FR-20)

**Acceptance Criteria:**

**Given** the form
**When** I add a featured image
**Then** it is optional and saved with the bydrae.

### Story 6.5: Optional audio/video attachment

As a skrywer,
I want to attach audio/video,
So that I can share recorded work. (FR-21)

**Acceptance Criteria:**

**Given** the form
**When** I add an audio/video attachment
**Then** it is optional and saved with the bydrae.

### Story 6.6: Challenge linking at submission

As a skrywer,
I want to link my piece to active uitdaging(s),
So that it is entered into the right round. (FR-22, UJ-4)

**Acceptance Criteria:**

**Given** active uitdagings
**When** I tick them at submission
**Then** the `uitdagingsrondte` term is written
**And** linking is allowed only while the uitdaging is open (before the SAST deadline).

### Story 6.7: Draft / publish states

As a skrywer,
I want to save a draft or publish,
So that I can work iteratively and publish when ready. (FR-23)

**Acceptance Criteria:**

**Given** the editor
**When** I act
**Then** "Stoor konsep" saves a draft (not entitlement-gated) and "Plaas" publishes
**And** publishing shows a success screen with read-and-respond prompts.

### Story 6.8: Submission entitlement gate

As the system,
I want to allow plaas only for active betaalde lede,
So that publishing tracks lidmaatskap (THE conflation rule). (FR-19)

**Acceptance Criteria:**

**Given** a publish action
**When** entitlement is evaluated at the moment of plaas
**Then** only lede with active entitlement may publish; others see an Afrikaans denial + link to plans
**And** a konsep saved while entitled but published after lapse is denied at publish time (draft preserved); a lapsed Goud writer is denied (Gradering does not grant entitlement).

### Story 6.9: Remove legacy edit-link filter

As an ink-core developer,
I want the legacy edit-link override removed,
So that no Youzify-era code remains. (FL 6.9)

**Acceptance Criteria:**

**Given** Youzify retired
**When** the old `functions.php` `/plaas-nuwe-publikasie` override is dropped
**Then** submission routing uses only the new Skryf flow.

---

## Epic 7: Reading & engagement

Make reading first-class: legible, form-aware templates (incl. a stanza-aware poetry layout), light line reactions, structured Gemeenskapsreaksies (the only feedback path), suggested reads, and a personal leeslys. Engagement is open to any lid (not entitlement-gated).

### Story 7.1: Single reading templates (prose)

As a reader,
I want clean reading templates for storie/artikel,
So that prose is legible. (FR-24)

**Acceptance Criteria:**

**Given** a storie/artikel
**When** it renders
**Then** a single reading template per CPT shows it at ~768px width with no WP comments (Archetype C, reference-ready).

### Story 7.2: Gedig reading layout

As a reader,
I want a poetry-aware reading layout,
So that poems display with correct structure. (FR-25)

**Acceptance Criteria:**

**Given** a gedig
**When** it renders
**Then** the layout is stanza-aware, preserves line breaks and blank-line/stanza spacing and leading whitespace verbatim, styles author-entered Roman-numeral stanza markers, and allows per-line resonance on content lines (not blank separators).

### Story 7.3: Line highlighting + reactions

As a lid,
I want to highlight a line and react,
So that I can encourage specific moments. (FR-26, UJ-3)

**Acceptance Criteria:**

**Given** a work
**When** I select text
**Then** I can add a reaksie (hartjie / duim op / wow) — reactions only, no public inline commentary/annotation ("encouragement, not critique").

### Story 7.4: Structured community responses

As a lid,
I want to post a typed Gemeenskapsreaksie,
So that feedback is structured and kind. (FR-27, UJ-3)

**Acceptance Criteria:**

**Given** a work (WP comments disabled site-wide)
**When** I respond
**Then** I post a Gemeenskapsreaksie of type **lof**, **insig**, or **voorstel**, each carrying its type — the only feedback path.

### Story 7.5: Contextual prompts after a piece

As a reader,
I want guided prompts after reading,
So that I'm helped to respond thoughtfully. (FR-30)

**Acceptance Criteria:**

**Given** a finished piece
**When** the prompt area renders
**Then** contextual guided prompts show (may vary by content type).

### Story 7.6: Suggested next reads

As a reader,
I want suggested next reads,
So that I discover related work. (FR-31)

**Acceptance Criteria:**

**Given** shared taxonomy terms
**When** suggestions render
**Then** next reads are suggested by tone/form/topic/Gradering via taxonomy (no manual linking).

### Story 7.7: Reading list (leeslys)

As a lid,
I want a personal leeslys,
So that I can save works to read. (FR-29, UJ-3)

**Acceptance Criteria:**

**Given** a work
**When** I save/remove it
**Then** it is added/removed from my leeslys with confirmation toasts and surfaced on my profile.

### Story 7.8: Reactions data + counts

As a reader,
I want to see reaction counts,
So that resonance is visible without vanity framing. (FR-28)

**Acceptance Criteria:**

**Given** reaction data
**When** counts render
**Then** they show verb-less (e.g. "342 hartjies") with locale-correct `_n()` plurals (1 hartjie / 342 hartjies; n=0 handled) on every count surface.

---

## Epic 8: Discovery (Ontdek)

Provide the Ontdek hub — browse, filter, sort, and search published bydraes and skrywers, plus a works archive and (fast-follow) personalised surfaces.

### Story 8.1: Ontdek section + works archive

As a reader,
I want a discovery hub with a works archive,
So that I can browse published writing. (FR-32)

**Acceptance Criteria:**

**Given** the Ontdek section
**When** it renders
**Then** it provides the reading/discovery hub and a works archive with date/archive browse (single-piece reading lives in Epic 7).

### Story 8.2: Ontdek — bydraes tab

As a reader,
I want to browse and sort bydraes,
So that I can find work I like. (FR-33)

**Acceptance Criteria:**

**Given** the bydraes tab
**When** I filter/sort
**Then** I can filter by type (Gedigte/Stories/Artikels) and sort (Nuut / Opspraakwekkend / Mees geliefd).

### Story 8.3: Ontdek — skrywers tab

As a reader,
I want to browse and sort skrywers,
So that I can find writers to follow. (FR-34)

**Acceptance Criteria:**

**Given** the skrywers tab
**When** I filter/sort
**Then** I can filter by genre (Digkuns/Prosa/Artikels) and sort (Meeste gelees / Nuwe stemme).

### Story 8.4: Search

As a reader,
I want to search works and writers,
So that I can find specific content. (FR-35)

**Acceptance Criteria:**

**Given** the search
**When** I query
**Then** it searches works (title/theme) and skrywers (name/bio/genre)
**And** search is diacritic-insensitive (ê/ë/ô/î match base letters).

### Story 8.5: Discovery surfaces

As a reader,
I want personalised discovery surfaces,
So that relevant writers/works surface to me. (FR-36)

**Acceptance Criteria:**

**Given** custom discovery logic
**When** surfaces render
**Then** "writers like this", new voices, recently active, writers in your Gradering, and unread-by-you appear (custom, not default community screens).

---

## Epic 9: Community & social

Provide scoped BuddyPress (profiles, directory, notifications), a custom one-way follow graph, block-theme profiles (public Skrywerprofiel / private My Profiel), pinned works, reader ratings, and the R7/R8 receipt-notification and read-count surfaces.

### Story 9.1: BuddyPress scoped config

As a site owner,
I want BuddyPress scoped to only what INK uses,
So that the community stays focused. (FR-37)

**Acceptance Criteria:**

**Given** BuddyPress
**When** configured
**Then** Profiles, Directory, Notifications are **on**; Private Messaging **off at launch** (§14.7); Friend Connections, site-wide Activity, Groups, Blogs **off**.

### Story 9.2: Follow graph (asymmetric)

As a lid,
I want to follow a skrywer one-way,
So that I can track writers without mutual consent. (FR-38, UJ-3)

**Acceptance Criteria:**

**Given** the `ink-core` follow graph
**When** I volg a skrywer
**Then** follow is asymmetric with volgeling/following counts and Volg / Volg tans UI (replaces friendships, no BuddyPress friend add-on).

### Story 9.3: Following-feed

As a lid,
I want an activity feed of writers I follow,
So that I see their new work. (FR-39)

**Acceptance Criteria:**

**Given** the profile "Aktiwiteit" tab
**When** it renders
**Then** it shows **new publications by followed skrywers only**.

### Story 9.4: Custom profile templates

As a lid,
I want block-theme profiles,
So that public and private profile data are correctly separated. (FR-40)

**Acceptance Criteria:**

**Given** the profile templates
**When** they render
**Then** **private My Profiel** and **public Skrywerprofiel** exist, showing Gradering, bio, stats, pinned works, accomplishments
**And** private data (read counts, "wins needed" subtext) lives on My Profiel only.

### Story 9.5: Pinned / selected works

As a skrywer,
I want to pin selected works,
So that visitors see my best work first. (FR-41)

**Acceptance Criteria:**

**Given** my profile
**When** I curate
**Then** I can pin/select highlighted pieces shown on my profile.

### Story 9.6: Reader ratings & reviews

As a lid,
I want to rate and review a writer,
So that readers can recognise quality. (FR-42)

**Acceptance Criteria:**

**Given** a Skrywerprofiel
**When** I rate/review
**Then** an aggregate rating + written reviews appear
**And** public reviews are subject to the moderation path (Epic 18) and POPIA public-profile considerations.

**Sequence:** the moderation/report path (Story 18.4) should land **before** public reviews are first exposed; until then reviews are created and held for moderation. (Cross-epic dependency flagged 2026-06-20; resolve in sprint ordering.)

### Story 9.7: Member directory (ledegids)

As a lid,
I want a member directory,
So that I can discover writers. (FR-43)

**Acceptance Criteria:**

**Given** the ledegids
**When** it renders
**Then** it provides a writer-discovery surface.

### Story 9.8: Private messaging (deferred)

As a product owner,
I want messaging out of launch scope,
So that we ship focused. (FL 9.8, §14.7)

**Acceptance Criteria:**

**Given** launch scope
**When** BuddyPress is configured
**Then** Messaging is off at launch and revisited later (deferred, non-blocking).

### Story 9.9: Notifications

As a lid,
I want kennisgewings for relevant events,
So that I stay informed. (FR-44)

**Acceptance Criteria:**

**Given** the notification system
**When** events fire
**Then** kennisgewings cover new Gemeenskapsreaksie/@mention, followed-writer new work, uitdaging announcement/deadline, lidmaatskap-expiry reminder, and read-receipt milestone (9.11)
**And** "Merk alles as gelees" marks read by a timestamp boundary (no phantom-unread)
**And** the expiry reminder shares its anchor with the 4.8 lifecycle warnings.

### Story 9.10: Member online widget (removed)

As a product owner,
I want the retired online-widget removed,
So that no CBX dependency remains. (FL 9.10)

**Acceptance Criteria:**

**Given** CBX retired
**When** chrome is reviewed
**Then** the member-online widget is gone (replaced by engagement signals only if needed).

### Story 9.11: Receipt-notification trigger (R7)

As a skrywer,
I want a notification when my work gets engagement,
So that I'm encouraged. (FR-44a, R7)

**Acceptance Criteria:**

**Given** a "receipt" event tied to the analytics read-count (9.12)
**When** it fires
**Then** a kennisgewing is sent with a **randomized** message from the Story 1.12 form-letter list, deep-linking to **private My Profiel**
**And** with analytics absent the trigger is inert (degrades gracefully).

**Sequence:** depends on the analytics provider (Story 18.9) via 9.12 — schedule **18.9 before** 9.11/9.12, **or** ship R7/R8 in a later sprint than the rest of Epic 9 (both degrade gracefully). (Cross-epic dependency flagged 2026-06-20; resolve in sprint ordering.)

### Story 9.12: Read-count surface on My Profiel (R8)

As a skrywer,
I want to see read counts on My Profiel,
So that I know my reach privately. (FR-44b, R8)

**Acceptance Criteria:**

**Given** the analytics provider (18.9) and `_ink_read_count`
**When** My Profiel renders
**Then** per-bydrae read counts show on **My Profiel only** (private), verb-less with `_n()` plurals — not on the public Skrywerprofiel.

---

## Epic 10: Library (Biblioteek)

Provide the Biblioteek archive/single views (URL prefix `/biblioteek/` preserved) and the R4 auto-update **hook** (stub at P0; body deferred with the broader biblioteek analysis). Several sub-features are flagged, deferred, non-blocking gaps.

### Story 10.1: biblioteek_item archive + single

As a reader,
I want a Biblioteek archive and single view,
So that I can browse curated/reference work. (FR-52)

**Acceptance Criteria:**

**Given** `biblioteek_item`
**When** the archive/single render
**Then** a featured strip + category filter + search + card grid (archive) and a single view are provided (Library layout reference).

### Story 10.2: Date / archive browsing (deferred)

As a reader,
I want date/archive browsing,
So that I can navigate by time. (FL 10.2, §9.4 gap)

**Acceptance Criteria:**

**Given** the deferred-gap status
**When** scoped later
**Then** date/archive browsing is implemented (non-blocking).

### Story 10.3: Pagination (deferred)

As a reader,
I want pagination,
So that large libraries are navigable. (FL 10.3, §9.4 gap)

**Acceptance Criteria:**

**Given** the deferred-gap status
**When** scoped later
**Then** pagination is implemented (non-blocking).

### Story 10.4: Author filter (deferred)

As a reader,
I want to filter the library by author,
So that I can find a writer's library items. (FL 10.4, §9.4 gap)

**Acceptance Criteria:**

**Given** the deferred-gap status
**When** scoped later
**Then** an author filter is implemented (non-blocking).

### Story 10.5: Winner ↔ challenge linkage

As a reader,
I want winners linked to their producing challenge,
So that I can trace a winning piece's context. (FR-53)

**Acceptance Criteria:**

**Given** a winning entry
**When** it appears in the Biblioteek
**Then** it links back to the producing challenge via `uitdagingsrondte` (or modelled relationship).

### Story 10.6: Biblioteek auto-update on win (R4 — stub)

As an ink-core developer,
I want a reserved auto-update hook for wins,
So that R2 ingestion can call it later without rework. (R4)

**Acceptance Criteria:**

**Given** R2 ingestion (12A.3)
**When** winners are committed
**Then** a Biblioteek update **hook** exists and is invoked (P0 stub)
**And** the hook **body is deferred** with the broader biblioteek organisation analysis (§9.4).

---

## Epic 11: Training (Opleiding)

Provide the Opleiding resource hub (not an LMS), faceted by `vaardigheid`, that auto-surfaces beside relevant works/challenges via shared taxonomy (never manual linking). URL prefix `/opleiding/` preserved.

### Story 11.1: opleiding_artikel hub

As a reader,
I want a training resource hub,
So that I can find writing guidance. (FR-54)

**Acceptance Criteria:**

**Given** `opleiding_artikel`
**When** the hub renders
**Then** it is a resource hub (Library-layout archetype), not an LMS.

### Story 11.2: vaardigheid taxonomy + faceted search

As a reader,
I want faceted search by skill area,
So that I can find relevant training. (FR-54)

**Acceptance Criteria:**

**Given** the `vaardigheid` taxonomy
**When** I search
**Then** facets include Begin hier, Skryfkuns, Digkuns, Prosa, Stylfigure, Redigeer en hersien, Stem en styl.

### Story 11.3: Editor's shelf / curated entry points

As a reader,
I want curated entry points,
So that I have guided starting places. (FR-56)

**Acceptance Criteria:**

**Given** the hub
**When** it renders
**Then** "Die redakteur se rak" and empty states are provided.

### Story 11.4: Auto cross-surfacing

As a reader,
I want training to surface beside relevant works,
So that guidance appears in context without staff effort. (FR-55)

**Acceptance Criteria:**

**Given** shared `genre`/`vaardigheid` terms
**When** a work/challenge renders
**Then** related training surfaces **solely** by shared terms (an item sharing no term surfaces nothing); no per-item manual linking exists.

### Story 11.5: Community contribution CTA

As a skrywer,
I want to contribute a guide,
So that the community can share craft knowledge. (FR-56)

**Acceptance Criteria:**

**Given** the hub
**When** I act on "Plaas 'n stuk"
**Then** I can contribute a community-written guide.

---

## Epic 12: Challenges (Uitdagings) & winners

Provide the monthly uitdaging surfaces, metadata, entry capture, Gradering-based competition pools, and **structured queryable placement records** (1st/2nd/3rd per tier) that feed R2 ingestion and R3 auto-promotion.

### Story 12.1: uitdaging single page

As a skrywer,
I want a rich challenge page,
So that I understand the prompt and rules. (FR-45)

**Acceptance Criteria:**

**Given** an uitdaging
**When** the single page renders
**Then** it shows prompt, literary devices, submission rules, prize, deadline, resources, and entries list.

### Story 12.2: Uitdagings list page

As a skrywer,
I want a challenges list with countdown,
So that I can find open challenges. (FR-46)

**Acceptance Criteria:**

**Given** the list page (Archetype B)
**When** it renders
**Then** challenges are listed with a countdown.

### Story 12.3: Challenge metadata

As an ink-core developer,
I want challenge metadata with monthly cadence,
So that rounds are well-formed and time-correct. (FR-47)

**Acceptance Criteria:**

**Given** challenge meta
**When** stored
**Then** `challenge_theme` and `challenge_deadline` exist with a **monthly** cadence
**And** all times are SAST; the deadline is inclusive through 23:59:59 SAST; after deadline, entries are frozen for judging.

### Story 12.4: Entry capture

As a skrywer,
I want my entry linked to the round,
So that it is judged in the right context. (FR-48, UJ-4)

**Acceptance Criteria:**

**Given** an open uitdaging
**When** I submit an inskrywing
**Then** it links to the round via `uitdagingsrondte`
**And** at most 3 entries of each content type per uitdaging are allowed; the entry-time Gradering pool governs judging.

### Story 12.5: Gradering-based competition pools

As a skrywer,
I want to compete within my Gradering,
So that judging is fair. (FR-49, UJ-4)

**Acceptance Criteria:**

**Given** entries
**When** judged
**Then** pools are Brons vs Brons, Silwer vs Silwer, Goud vs Goud; placements (1st–3rd) announced per Gradering (tier governs pools — THE conflation rule).

### Story 12.6: Structured placement records

As an ink-core developer,
I want queryable placement records per tier,
So that ingestion and auto-promotion have authoritative data. (FR-50)

**Acceptance Criteria:**

**Given** results
**When** placements are recorded
**Then** 1st/2nd/3rd per Gradering per round are stored (not only the single winner), distinguishing **algehele wenner** (1st) from **wenner** (2nd/3rd)
**And** they feed R2 ingestion (12A.3), R3 auto-promotion (5.8), and the SM-8 metric.

### Story 12.7: Winner → Gradering promotion link

As a redakteur,
I want to link a winner to a promotion,
So that the audit trail connects results to advancement. (FR-51, UJ-5)

**Acceptance Criteria:**

**Given** graderingsgeskiedenis
**When** a promotion is recorded
**Then** it can optionally link to the challenge result.

### Story 12.8: Historical challenge migration

As an ink-core developer,
I want historical challenges migrated,
So that past rounds and their linkages survive. (FL 12.8, §14.6)

**Acceptance Criteria:**

**Given** old challenge categories
**When** the once-off DB update runs
**Then** categories → `uitdagingsrondte` terms + an `uitdaging` record per round, preserving each piece's challenge linkage (full brief/deadline only where old data exists).

---

## Epic 12A: Challenge adjudication automation (NEW — 2026-06-20)

The largest net-new build and the second launch pillar (editorial-burden automation). Reuses `ink_entries`, the Challenges module (judge-email composer + paste-text parser), the Tiers module (5.8), the **form-letter/notification capability (Story 1.12, Foundation)**, and the home featured slot (15.6). **Hard build order (all P0): R1 → R2 → R3.** The EntryID data model (12A.1) is the linchpin and must land first. **No `.docx` parser — results are pasted as plain text** (owner decision; removes the PhpWord/XXE/zip-bomb surface).

> **Note (2026-06-20):** the shared form-letter/notification store was **relocated from 12A.0 to Foundation Story 1.12** so the earlier epics that consume it (4.8, 5.10, 9.11) depend backwards, not forwards. 12A's R2 winners-post generation (12A.4) consumes 1.12.

### Story 12A.1: EntryID data model (R1 linchpin)

As an ink-core developer,
I want a per-type EntryID on entries assigned at collation,
So that pasted results can be matched back to entries. (FR-50-R1, R1)

**Acceptance Criteria:**

**Given** the `ink_entries` table
**When** the model is extended
**Then** `entry_type` + `entry_number` columns exist, numbered per type (Gedigte/Stories/Artikels separately), **assigned at collation time** (not at entry time)
**And** this lands **before** R2; an Afrikaans UI label is human-written only if ever surfaced.

### Story 12A.2: Judge-email collation tool (R1)

As a redakteur,
I want to auto-collate a round into an anonymized judge email,
So that I stop doing it manually. (FR-50-R1, R1)

**Acceptance Criteria:** (see Admin-flow ACs below — 12A.2)

**Given** an uitdaging
**When** I collate
**Then** entries are assembled into an anonymized judge email, EntryIDs assigned + stored (12A.1), names/copyright stripped
**And** it replaces today's manual collation (real artifact: `INK Mei projek inskrywings.eml`).

### Story 12A.3: Paste-text results ingestion + coverage report (R2)

As a redakteur,
I want to paste judges' results as plain text and get a coverage report,
So that I can safely commit results without a parser dependency. (FR-50-R2, R2)

**Acceptance Criteria:** (see Admin-flow ACs below — 12A.3)

**Given** pasted plain text (no `.docx` upload)
**When** I ingest
**Then** the parser matches against stored EntryIDs and produces a **dekkingsverslag** (matched / unmatched / "Geen")
**And** committing is **blocked until I confirm** all categories are accounted for; commit is **idempotent** and its final step triggers R3 auto-promotion (5.8).

### Story 12A.4: Winners-announcement post generation (R2)

As a redakteur,
I want the wenneraankondiging post generated automatically,
So that I don't hand-write it. (FR-50-R2, R2)

**Acceptance Criteria:**

**Given** confirmed results
**When** the post generates
**Then** a wenneraankondiging is created from a **simple form-letter template** (Story 1.12), takes the featured home slot (15.6), and lists entries with links.

### Story 12A.5: Moderator-feedback comment type + writer display toggle (R2)

As a skrywer,
I want moderator feedback stored privately and shown only if I enable it,
So that I control whether critique appears on my work. (FR-50-R2, C5)

**Acceptance Criteria:**

**Given** ingestion
**When** feedback is written
**Then** it is stored as a custom structured `comment_type = ink_moderator_terugvoer` ("Terugvoer van die moderator") via `wp_insert_comment` (not a re-enabled WP comment)
**And** it is visible on a work **only when the writer enables it on My Profiel** (sanctioned exception to the Gemeenskapsreaksies-only rule).

### Story 12A.6: Winner banner — per-rank / per-tier variants

As a reader,
I want clear winner banners,
So that I can see who placed and at what rank/tier. (FR-50-R2, C9)

**Acceptance Criteria:**

**Given** the existing base banner design (home "Desember-wenner")
**When** variants render
**Then** **algehele wenner** (1st) vs **wenner** (2nd/3rd) variants exist with Brons/Silwer/Goud colour tokens and Meester = `primary #EA4015`
**And** colour is paired with text/icon (no colour-only encoding, a11y)
**And** the placement flag extends `ink_entries` placement columns.

### Story 12A.7: Featured-feed ordering

As a reader,
I want the overall winner featured most prominently,
So that the top result leads. (FR-50-R2, R2)

**Acceptance Criteria:**

**Given** committed winners
**When** the featured feed orders
**Then** `algehele wenner` (1st) is placed ahead of ordinary wenners (drives home featured ordering, 15.6).

### Admin-flow acceptance criteria (12A — wp-admin chrome, Afrikaans ink-core labels)

> These are wp-admin screens (Settings API / list table / custom admin page; **no front-end design system**). All `ink-core` labels, buttons, and status strings are **Afrikaans**. The criteria pin down interaction flow + states; visual styling is whatever WP admin provides.

**12A.2 — Judge-email collation (R1)**
1. Lives under the **Uitdagings** admin menu; editor selects an uitdaging from a list in **descending date order**.
2. On select, the system collates all linked entries, **assigns the per-type EntryID** (each type numbered from 1), sorted by **entry type → Gradering (Brons, Silwer, Goud) → EntryID**, and **persists** the EntryID (12A.1).
3. Generates an **editable preview**: the **full challenge body text** first, then entries ordered by type → Gradering → EntryID; each entry shows **EntryID + title (both bold, one line)**, a blank line, then the **full entry text**; the **writer's name and any copyright notice are stripped** (both positions).
4. Editor can **edit the preview inline** before sending.
5. Editor enters one or more **recipient email addresses** and **sends**.
6. **States:** *no entries linked* → empty state, no send; *re-collation of an already-numbered round* → **idempotent**, must **not renumber or burn EntryIDs**; *send success/failure* → clear status. EntryID assignment is **deferred to collation** (re-entry before deadline must not consume a number).

**12A.3 — Results ingestion + coverage report (R2)**
1. Editor selects the same uitdaging, then **pastes the judges' results as plain text** into a textarea. **No `.docx` upload** (explicit owner decision).
2. Parser extracts (a) the **winners block** — top-3 per Gradering and per category, each by EntryID, with **"Geen"** allowed where there is no placement; and (b) **per-entry commentary** — keyed by EntryID + title, then the commentary text.
3. Produces a **dekkingsverslag** by retrieving **all stored EntryIDs** and reconciling: it must indicate **whether all winners were identified** and **whether commentary was resolved for every entry** — listing **matched / unmatched / "Geen"** and any EntryIDs in the document that don't match (and vice versa).
4. **Explicit confirm gate:** committing is **irreversible** (publishes a post, writes comments, promotes Graderings), so it is **blocked until the editor confirms** all categories are accounted for in both the winners list and the commentary.
5. **On confirm, in order:** (1) generate the **wenneraankondiging** post (12A.4); (2) write **Terugvoer van die moderator** per entry (12A.5); (3) Biblioteek update **stub** (R4 / 10.6); (4) mark winners + set banners and placement `algehele wenner`/`wenner` (12A.6); (5) add winners to the **featured feed**, `algehele wenner` first (12A.7); (6) **trigger the Gradering auto-promotion engine** (5.8).
6. **States:** *parse partial/failure* → coverage report shows gaps, commit stays blocked; *EntryID mismatch* → flagged for reconciliation; *re-run after a successful commit* → **idempotent**, must **not double-post, double-comment, or double-promote**.

---

## Epic 12B: Annual competition management (NEW, P2)

Reuse the R1/R2/R3 machinery (Epic 12A + 5.8) on an annual cadence — sequencing/cadence configuration over the existing adjudication pipeline, no new core mechanics. Post-launch.

### Story 12B.1: Annual competition management

As a redakteur,
I want an annual-cadence competition reusing the monthly machinery,
So that the yearly competition is automated too. (R9)

**Acceptance Criteria:**

**Given** the existing adjudication pipeline (12A.1/12A.2/12A.3, 12A.4/12A.6/12A.7, 5.8)
**When** an annual competition runs
**Then** it reuses EntryID collation, paste-text ingestion + coverage report, winners post/banner/featuring, and auto-promotion on an **annual** cadence
**And** no new core mechanics are introduced (post-launch). *(Source doc mislabels this "R8"; it is R9.)*

---

## Epic 13: InkPols

Provide the InkPols periodical model — `inkpols_uitgawe` with a by-year archive and PDF viewing via Real3D Flipbook — plus back-catalogue migration. Issues stay PDF-based (no per-article extraction).

### Story 13.1: inkpols_uitgawe model

As a content manager,
I want a structured issue model,
So that issues carry consistent metadata. (FR-57)

**Acceptance Criteria:**

**Given** `inkpols_uitgawe`
**When** an issue is edited
**Then** it stores issue date, volume, cover image, PDF, and teaser.

### Story 13.2: Issue archive (by year)

As a reader,
I want a by-year issue archive,
So that I can find past issues. (FR-57)

**Acceptance Criteria:**

**Given** issues
**When** the archive renders
**Then** a clean by-year archive and a robust single-issue page are provided.

### Story 13.3: PDF viewing

As a reader,
I want to read issues as a flipbook,
So that I can browse the PDF in-page. (FR-57)

**Acceptance Criteria:**

**Given** Real3D Flipbook (reactivated)
**When** an issue opens
**Then** the PDF renders as a flipbook (no individual-article extraction)
**And** viewer controls are Afrikaans via the plugin's JS translations (accepted exception to NFR-3/NFR-5).

### Story 13.4: Back-catalogue migration

As an ink-core developer,
I want existing PDFs re-linked,
So that the back catalogue survives. (FL 13.4)

**Acceptance Criteria:**

**Given** legacy issues
**When** migration runs
**Then** existing PDFs are re-linked and month/year naming is replaced with date+volume meta.

---

## Epic 14: Sponsors (Borge)

Provide a `borg` CPT with scheduling/rotation — one featured/rotating sponsor on the homepage, no logo dumps on content pages — plus a recognition page.

### Story 14.1: borg CPT

As a redakteur,
I want a sponsor model,
So that I can manage sponsor details. (FR-58)

**Acceptance Criteria:**

**Given** `borg`
**When** edited
**Then** fields include name, logo variants, link, `sponsor_tier`, campaign start/end, and placement preferences.

### Story 14.2: Scheduling / rotation logic

As a redakteur,
I want campaign-date-driven display,
So that sponsors show only in their window. (FR-58)

**Acceptance Criteria:**

**Given** campaign dates
**When** display is computed
**Then** sponsors show within their window with rotation; dates are inclusive of start and end (single-day start==end shows that day).

### Story 14.3: Homepage sponsor placement

As a site owner,
I want a subtle sponsor strip,
So that sponsors are recognised without clutter. (FR-58)

**Acceptance Criteria:**

**Given** the homepage
**When** it renders
**Then** one featured or rotating sponsor shows in a subtle strip; **with no active sponsor the strip collapses gracefully**; with multiple active it rotates; no logo dumps on content pages.

### Story 14.4: Sponsor recognition page

As a sponsor,
I want full recognition on Oor INK,
So that supporters are acknowledged. (FR-58)

**Acceptance Criteria:**

**Given** Oor INK
**When** it renders
**Then** a full sponsor recognition section is provided.

---

## Epic 15: Organisation pages & contact

Provide the public org/marketing surfaces — Tuisblad, Gemeenskap, Oor INK, Kontak — a theme-native footer, and the home featured slot/ordering for the auto-generated wenneraankondiging.

### Story 15.1: Tuisblad

As a besoeker,
I want a welcoming homepage,
So that I understand INK and find featured content. (FR-59)

**Acceptance Criteria:**

**Given** the Tuisblad (reference-ready)
**When** it renders
**Then** it shows hero spotlight, challenge section, featured works, sponsors, and CTA.

### Story 15.2: Gemeenskap page

As a besoeker,
I want a conversion/marketing page,
So that I understand the value of joining. (FR-60)

**Acceptance Criteria:**

**Given** the Gemeenskap page
**When** it renders
**Then** it presents value props, principles, how-it-works, and CTAs.

### Story 15.3: Oor INK

As a besoeker,
I want an about page,
So that I learn INK's mission and contacts. (FR-60)

**Acceptance Criteria:**

**Given** Oor INK (assembly-only)
**When** it renders
**Then** it shows mission, contact, sponsors, and org pages
**And** uses clearly-marked placeholders for founding year + SA legal status (pre-launch content gate; never US "501(c)(3)" wording).

### Story 15.4: Kontak

As a besoeker,
I want a contact form,
So that I can reach INK. (FR-61)

**Acceptance Criteria:**

**Given** the Kontak page
**When** I submit
**Then** a **custom `ink-core` form** handles it (no CF7 / Fluent Forms), with nonces and sanitisation.

### Story 15.5: Footer / social links

As a besoeker,
I want a footer with social links,
So that I can navigate and find INK elsewhere. (FR-62)

**Acceptance Criteria:**

**Given** the footer
**When** it renders
**Then** a theme-native footer/social-links pattern shows (replacing the legacy social-icons plugin).

### Story 15.6: Winners-post featured slot + featured-feed ordering (R2)

As a reader,
I want the winners announcement featured on the home page,
So that the latest results are prominent. (FR-50-R2)

**Acceptance Criteria:**

**Given** a generated wenneraankondiging (12A.4)
**When** the home featured area renders
**Then** the featured slot hosts it and ordering puts **algehele wenner first**, ahead of ordinary wenners (drives/consumes 12A.7).

---

## Epic 16: Migration & redirects

Execute the scripted, ordered brownfield migration — clean DB clone → CPTs/taxonomies → users → tiers → subscription verification → post reclassification → library/training → redirects → InkPols/sponsors/nav → BuddyPress friendship→follow → media verification — with mandatory 301s on every URL change.

### Story 16.1: DB clone & sanitise

As an ink-core developer,
I want a clean DB baseline,
So that migration starts from a sane state. (FL 16.1)

**Acceptance Criteria:**

**Given** the cloned DB
**When** sanitised
**Then** transients/logs are stripped to a clean baseline (members, subscriptions, content, media preserved).

### Story 16.2: User import + role reassignment

As an ink-core developer,
I want a single member base role,
So that legacy reader/writer roles are dropped. (FL 16.2, FR-2)

**Acceptance Criteria:**

**Given** legacy users
**When** imported
**Then** a single member base role applies (no reader/writer distinction), profile fields cleaned, legacy Youzify/BP roles dropped.

### Story 16.3: Tier CSV import

As an ink-core developer,
I want tiers imported from CSV,
So that writers keep their Gradering. (FL 16.3)

**Acceptance Criteria:**

**Given** the tier CSV (email join key)
**When** imported
**Then** `ink_writer_tier` is set; missing/ambiguous → default `brons` **+ flag** (never a guessed Silwer/Goud/Meester).

### Story 16.4: Subscription verification

As an ink-core developer,
I want subscriptions verified (not imported),
So that memberships ride the DB clone correctly. (FL 16.4)

**Acceptance Criteria:**

**Given** the cloned subscriptions
**When** verified on the new host
**Then** memberships, plan IDs, access rules, and expiry are confirmed (no import script).

### Story 16.5: Post → CPT reclassification

As an ink-core developer,
I want posts reclassified to CPTs,
So that content lands in the right model. (FL 16.5)

**Acceptance Criteria:**

**Given** legacy posts
**When** reclassified (category-driven)
**Then** unclassifiable → `skryfwerk` catch-all (**not hand-classified at volume**); rewrite rules flushed
**And** `inkpols`→`inkpols_uitgawe` rename; `monthly_challenge` not migrated 1:1 (uitdaging records built from round categories, real data folded in else dropped).

### Story 16.6: Library/training migration

As an ink-core developer,
I want library/training migrated by URL sub-path,
So that prefixes and taxonomy survive. (FL 16.6)

**Acceptance Criteria:**

**Given** legacy library/training content
**When** migrated by URL sub-path
**Then** items map to CPT + taxonomy terms, keeping `/biblioteek/` and `/opleiding/` prefixes.

### Story 16.7: Redirect generation

As an ink-core developer,
I want 301s for every changed URL,
So that no link breaks (NFR-4). (FL 16.7)

**Acceptance Criteria:**

**Given** any CPT reassignment that changes a URL
**When** migration runs
**Then** the old permalink is recorded before reassignment and a 301 is emitted; redirects verified by crawl; `/biblioteek/`,`/opleiding/` prefixes kept.

### Story 16.8: InkPols / sponsors / nav

As an ink-core developer,
I want InkPols/sponsors/nav rebuilt,
So that structure matches the new IA. (FL 16.8)

**Acceptance Criteria:**

**Given** §11/§13/IA
**When** migration runs
**Then** InkPols and sponsors migrate and navigation is rebuilt fresh.

### Story 16.9: BuddyPress data + friendship→follow

As an ink-core developer,
I want friendships converted to follows,
So that the social graph survives the model change. (FL 16.9, MR-8)

**Acceptance Criteria:**

**Given** legacy BuddyPress data
**When** migrated
**Then** each **confirmed** friendship → **two** mutual follow records (dedup; skip orphaned; pending not converted); old activity trimmed; messaging deferred.

### Story 16.10: Media verification

As an ink-core developer,
I want media verified post-migration,
So that uploads/audio/video/PDFs work. (FL 16.10)

**Acceptance Criteria:**

**Given** migrated media
**When** verified
**Then** uploads are accessible, audio/video play, and PDFs open.

### Story 16.11: Options carry-forward (selective)

As an ink-core developer,
I want only deliberate options carried forward,
So that we don't clone `wp_options` wholesale. (FL 16.11)

**Acceptance Criteria:**

**Given** legacy options
**When** carried forward
**Then** only deliberate values (site URL/name, `af` locale) transfer; SEO config is set up fresh in Rank Math.

### Story 16.12: WPBakery shortcode cleanup

As an ink-core developer,
I want legacy shortcodes stripped,
So that no `[vc_*]` renders as raw text. (FL 16.12)

**Acceptance Criteria:**

**Given** legacy content
**When** cleaned
**Then** `[vc_*]` shortcodes are stripped/converted and none render as raw text.

---

## Epic 17: Afrikaans-first & localisation (execution + QA)

Execute and QA the Afrikaans-first principle across surfaces — apply approved UI copy, translate residual third-party plugin strings, reconcile terminology (reopened by G1), and run the no-English-leakage gate. Foundational enablers live in Epic 1 (1.10); this epic is execution/QA. **High epic number reflects when this completes, not its priority.**

### Story 17.1: Apply approved UI copy

As a content manager,
I want approved Afrikaans UI copy applied,
So that the front end reads correctly. (NFR-1)

**Acceptance Criteria:**

**Given** `ui-copy-translations.md`
**When** copy is applied
**Then** approved Afrikaans copy is used (never lifted from English Lovable placeholders, never AI-generated) and placeholder org details are resolved.

### Story 17.2: Residual plugin Afrikaans pass

As a content manager,
I want surviving third-party plugin strings translated,
So that no English leaks from plugins. (NFR-1, NFR-7)

**Acceptance Criteria:**

**Given** surviving plugins (BuddyPress, WC/Memberships/PayFast; + Report Content/CF7 if kept)
**When** translated on staging with Loco
**Then** `.po/.mo` are **committed to version control** (production loads them without Loco), w.org language packs used where complete
**And** all leak vectors are covered (§12): validation/status/error messages, plugin-composed sentences, **transactional emails**, **plugin JavaScript** strings (e.g. Real3D JS `.json`), and out-of-band outputs (REST/AJAX/feeds) — human-authored.

### Story 17.3: Terminology reconciliation (reopened by G1)

As a content manager,
I want terminology reconciled across all docs and code,
So that the canonical vocabulary is consistent. (G1)

**Acceptance Criteria:**

**Given** `afrikaans-terms.md` updated first as source of truth
**When** corrections propagate
**Then** `intekening`/`intekenaar`/`intekenlid` → **lidmaatskap / betaalde lid / gratis lid**; UI tier term → **Gradering** (never "tier"); **Skrywerprofiel = PUBLIC**, **My Profiel = PRIVATE** (fix "Skrywersprofiel" → "Skrywerprofiel"); new terms EntryID, algehele wenner / wenner, Terugvoer van die moderator, Meester
**And** corrections propagate to **ALL** docs, not just the active file.

### Story 17.4: No-English-leakage QA gate

As a QA engineer,
I want a standing leak gate,
So that no English reaches the front end. (NFR-1)

**Acceptance Criteria:**

**Given** Gate D
**When** the automated English-leak scan runs (crawl key front-end pages + `wp i18n` counts, with the defined allowlist)
**Then** every front-end template/pattern + user-facing email passes; the §12 leak vectors (incl. the 12A.0 form-letter store) are in scope; admin stays English (§14.14)
**And** it re-runs after ungated updates (depends on 1.10 scaffolding).

---

## Epic 18: SEO, security & performance

Adopt Rank Math, the layered security stack, caching, moderation, update governance, the full test-suite buildout, and the R6/R8 additions (anti-spam hardening, analytics provider).

### Story 18.1: Rank Math config + CPT schema

As a site owner,
I want SEO via Rank Math from the start,
So that URLs, schema, and sitemaps are correct. (NFR-4)

**Acceptance Criteria:**

**Given** Rank Math
**When** configured
**Then** sitemaps, meta, breadcrumbs, and native schema for `gedig`/`storie`/`artikel` exist; the Rank Math importer runs as a safety net; InkPols OG images verified, then Yoast deactivated (deliberate override of the transition guide, §14.11).

### Story 18.2: Redirect integrity

As a site owner,
I want all old URLs to resolve,
So that SEO and links survive migration. (NFR-4)

**Acceptance Criteria:**

**Given** migrated URLs
**When** verified
**Then** all old URLs → correct 301 and 404s are tracked.

### Story 18.3: Security stack (layered)

As a site owner,
I want a layered security stack,
So that the site and origin are protected. (§14.16)

**Acceptance Criteria:**

**Given** the resolved stack
**When** deployed
**Then** Cloudflare (edge + login rule, origin locked) + staff 2FA + Patchstack (CVE alerts) + staging-gated updates + host malware scanning are in place; Loginizer/WordFence not used; PayFast off-site keeps PCI scope low.

### Story 18.4: Moderation/report path

As a lid,
I want to report content,
So that abuse can be moderated. (§8)

**Acceptance Criteria:**

**Given** a work/review
**When** I report it
**Then** a **custom `ink-core` report form** handles it (no third-party Report Content).

### Story 18.5: Caching layer

As a site owner,
I want caching,
So that the site is fast. (NFR-3)

**Acceptance Criteria:**

**Given** the host
**When** caching is configured
**Then** LiteSpeed Cache + Cloudflare edge caching are active (§14.9).

### Story 18.6: Production hygiene

As a site owner,
I want no diagnostic/migration tools on production,
So that production stays clean and secure. (NFR-7)

**Acceptance Criteria:**

**Given** production
**When** audited
**Then** no dev/diagnostic/migration plugins (Loco, Code Snippets, WP Migrate Lite, String Locator, etc.) are active.

### Story 18.7: Update governance & i18n resilience

As a site owner,
I want governed updates with i18n resilience,
So that updates don't break overrides or leak English. (NFR-7, NFR-1)

**Acceptance Criteria:**

**Given** updates
**When** applied
**Then** major core/plugin updates are staging-gated (regression on custom templates + translation refresh); language packs used for core/well-covered plugins; committed `.mo` for premium plugins re-checked after their updates; new untranslated strings caught by the leak scan, fixed on staging, redeployed (**Loco not on production**).

### Story 18.8: Full test suite buildout

As an ink-core developer,
I want the full test pyramid,
So that critical rules and journeys are covered. (NFR-9, §14.17)

**Acceptance Criteria:**

**Given** the Epic 1 scaffold (1.11)
**When** extended
**Then** unit (Pest/PHPUnit + Brain Monkey/WP_Mock) for `ink-core` rules; integration (`wp-env`/wp-browser) for seams (membership⇒submit, expired⇒denied, tier⇒meta+log); E2E (Playwright) for register→buy via PayFast sandbox→submit→publish→read→renew
**And** CI per change + E2E smoke on staging; risk-based depth; includes the automated English-leak scan.

### Story 18.9: Analytics-provider selection (R8)

As a site owner,
I want an analytics provider selected,
So that read counts (9.12) have a data source. (FR-44b, R8)

**Acceptance Criteria:**

**Given** no analytics exists today
**When** a provider is chosen
**Then** a vetted-plugin analytics seam is wired (not reimplemented in `ink-core`); read counts surface on My Profiel via 9.12
**And** it sharpens the POPIA question (OQ-3), flagged to be addressed sooner.

### Story 18.10: Registration anti-spam hardening (R6)

As a site owner,
I want the registration endpoint hardened,
So that signup abuse is curbed. (FR-3a, R6)

**Acceptance Criteria:**

**Given** the 3.4 spike outcome
**When** hardening is applied
**Then** the security stack (18.3) gains the registration anti-abuse surface + the optional pending-approval state (3.6).

---

## Cross-cutting acceptance criteria (apply to every epic)

1. **Three-layer compliance** — no business logic in the theme.
2. **Token compliance** — no hardcoded colours/spacing/unnamed type sizes (Gate A / NFR-2).
3. **Afrikaans-first** — correct terms, no English leakage (Gate D / NFR-1).
4. **Gradering ≠ lidmaatskap** (THE conflation rule) — never conflated; `Ink\Tiers` ⟂ `Ink\Entitlement`.
5. **Editorial low-friction** — no mandatory per-item manual linking.
6. **Site Editor stability** — non-technical staff can manage content; critical structure locked.

---

## Provenance

- **Generated:** 2026-06-20 from the post-Correct-Course planning set.
- **Supersedes:** `docs/specs/ink-feature-list.md` as the epics/stories source of record (that file is retained as a marked companion input).
- **Next step:** run `bmad-sprint-planning` to generate `sprint-status.yaml` from this file, then `bmad-check-implementation-readiness` for the full readiness verdict.
