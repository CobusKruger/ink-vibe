# INK — PRD Extract (curated from `ink-consolidated-spec.md`)

> Source: `docs/specs/ink-consolidated-spec.md` (dated 2026-06-14). Faithful PRD-oriented extract. Afrikaans domain terms and code IDs preserved exactly.

---

# Vision & Problem

INK is a **community publishing platform for Afrikaans writers, poets, and readers**. The new site replaces an existing WordPress installation holding thousands of published contributions, an active paid membership, and established editorial processes (brownfield rebuild).

The rebuild fixes four structural problems while preserving everything of value:
1. **Preservation** — every existing contribution, member account, subscription record, and historical artifact must survive.
2. **Afrikaans-first UI** — the public and member-facing interface is entirely Afrikaans; no English word should appear to a visitor or member.
3. **Clean architecture** — separate presentation, business logic, and platform concerns into distinct layers (current site embeds business rules in theme glue and mismatched plugins).
4. **Automation** — replace manual EFT payment and spreadsheet-based tier tracking with automated systems; subscription tracking already runs on WooCommerce Memberships, the missing piece is front-end payment collection via PayFast.

**Positioning:** "A literary publishing platform that fosters a supportive community — not a social network." Social features exist to give people reasons to return and read the built-up content library, breaking the current pattern where writers visit only three times a month (new challenge, post work, check results). **Engagement features serve reading, not feed-scrolling.**

---

# Target Users / Personas / Intents

| Role | Afrikaans term | Capability summary |
|---|---|---|
| Visitor | besoeker | Read public writing; no account. |
| Free member | gratis lid | Read, react, comment/respond, access library & training, build a reading list, follow writers. |
| Subscriber (paid) | intekenaar | All free-member rights **plus the right to publish work** (submission entitlement). |
| Writer | skrywer | A member who publishes; carries a writer tier (Brons/Silwer/Goud). |
| Editor / staff | redakteur | WP `editor` role: editorial admin, challenge & winner administration, tier promotion, sponsor management, moderation. |
| Administrator | administrator | Technical control. |

**Reader vs writer intent:** at registration the user chooses reader (`leser`) or writer (`skrywer`) intent, stored as `ink_writer_intent`.

**Writer tiers:** Brons / Silwer / Goud — competition pools, stored as `ink_writer_tier` (`brons`/`silwer`/`goud`).

**Registration lifecycle (confirmed):** create account → choose reader or writer intent (`ink_writer_intent`) → complete profile → if writer, explain tiers and subscription requirement → prompt first social action after signup.

---

# Core Domain Concepts & THE conflation rule

**THE critical rule (must never be conflated in data or code):**
- *Subscription status* (active WooCommerce Membership) controls **submission entitlement**.
- *Writer tier* (`ink_writer_tier`) is a **separate** concept controlling Brons/Silwer/Goud competition pools.
- A paid subscriber at Brons tier is **not** the same as a Brons writer with an expired subscription.

**Custom post types (registered in `ink-core`):**
| Code ID | UI label (Afrikaans) | Purpose |
|---|---|---|
| `gedig` | Gedig | Published poems. |
| `storie` | Storie | Short stories / prose (was `verhaal`). |
| `artikel` | Artikel | Opinion pieces, essays. |
| `skryfwerk` | Skryfwerk | Catch-all bucket for unclassified migrated content. |
| `biblioteek_item` | Biblioteekitem | Curated library content, winning entries. |
| `opleiding_artikel` | Hulpbronartikel | Training / resource content. |
| `uitdaging` | Uitdaging | Monthly challenge (theme, deadline, results). |
| `inkpols_uitgawe` | Uitgawe | InkPols magazine issues (PDF-based). |
| `borg` | Borg | Sponsor content with scheduling fields. |

**User meta (prefix `ink_`):** `ink_writer_tier` (brons/silwer/goud), `ink_tier_promoted_at` (date of most recent promotion), `ink_writer_intent` (leser/skrywer). Tier promotion history stored in a second meta key or custom log table for auditability.

**Taxonomies:** `genre` (on bydraes gedig/storie/artikel; **shared** with training for auto-surfacing); `vaardigheid` (skill area on `opleiding_artikel`: Digkuns, Prosa, Taalgids, Algemene wenke; shared with bydraes); `uitdagingsronde` (links entries/winners to a challenge round); `skrywervlak` (tier as taxonomy term for query/segmentation).

**Collective terms:** "bydraes" = contributions (gedig/storie/artikel). Follow vocabulary: `Volg` / `Volg tans` / `volgeling` (plural `volgelinge`).

---

# Features / Capabilities

**Submission / publishing**
- Custom front-end submission workflow (`Skryf`), built in `ink-core` (replaces Youzify Frontend Submission).
- Submission entitlement gated by active membership.

**Reading & engagement**
- Custom **structured community response** system ("Gemeenskapsreaksies") with response types **Lof / Insig / Voorstel** (Praise / Insight / Suggestion). WordPress comments disabled site-wide.
- **Line highlighting** with simple **reactions** (`reaksie`: hartjie / duim op / wow) — discoverable but unobtrusive; encouragement, not critique. No public passage annotation.
- **Contextual prompts** after each piece.
- **Suggested next reads** (by tone, form, topic, or tier).
- **Reading list** (`leeslys`) — members save works to revisit.

**Reader feedback**
- **Reader ratings & reviews** on writer profiles (aggregate reader rating + written reviews).
- **Pinned/selected works**.

**Social**
- **Follow** (one-way / asymmetric; follower/following counts) implemented in `ink-core`. BuddyPress Friend Connections off.
- **Following-feed** — new publications by followed writers, surfaced as the profile "activity" tab.
- BuddyPress profiles, member directory, notifications.

**Challenges (Uitdagings)**
- Monthly challenges (`uitdaging`): rules, results, per-tier winners; winner records.

**Library (Biblioteek)**
- `biblioteek_item`: curated collection — winners, reference, document-style resources; date browsing, pagination, author filter.

**Training (Opleiding)**
- `opleiding_artikel` resource hub with `vaardigheid` taxonomy + faceted search. NOT an LMS (no courses/quizzes/certificates).

**InkPols**
- `inkpols_uitgawe`: PDF-based magazine issues via Real3D Flipbook viewer. No individual-article extraction (issues stay PDF-based).

**Sponsors (Borge)**
- `borg` content with scheduling fields; sponsor rotation/scheduling logic in `ink-core`.

**Discovery (Ontdek)**
- Reading/discovery hub: browse, filter, sort, search published writing and writers. Tabs: **bydraes** (filter by type, sort, date/archive browse) + **skrywers** (genre filter, sort: Meeste gelees, Nuwe stemme).

**Membership / payment**
- Front-end WooCommerce Memberships purchase via PayFast (ZAR).

---

# Functional rules & behaviors worth turning into FRs (with conditions)

- **IF** a member has an active WooCommerce Membership, **THEN** they have submission entitlement (may publish); **IF** expired/suspended, submission is denied.
- Writer tier and subscription status are tracked and enforced independently; never conflated.
- WordPress comments are **disabled site-wide**; the only feedback path is the structured Gemeenskapsreaksies (Lof/Insig/Voorstel).
- Line highlighting yields reactions only (hartjie/duim op/wow); **no** public inline commentary/annotation on works.
- Follow is **one-way**: following someone does not require reciprocity; system maintains follower/following counts.
- Following-feed shows only **new publications by followed writers**.
- Suggested next reads computed by tone, form, topic, or tier.
- Training resources and contributions **auto-surface** via shared `genre`/`vaardigheid` taxonomy terms — **no per-article manual linking** (would be ignored under workload).
- Challenge cadence is **monthly** (mockup "weekly/January" was placeholder).
- Challenges have per-tier winners (Brons/Silwer/Goud pools).
- Three fixed-term membership products only: **R60 / 1 month**, **R300 / 6 months**, **R600 / 12 months**. Prices shown only — **no discount/savings framing** ("Save 12%/25%" dropped).
- Auto-renewing subscriptions are **out of scope at launch** (fixed-term products only).
- Registration: writer-intent users must be shown tier explanation and subscription requirement; all users prompted for a first social action post-signup.
- Org content uses clearly-marked placeholders (e.g. `[stigtingsjaar]`, `[regstatus]`); **do not ship** US "501(c)(3)" wording — confirm real SA nonprofit values before go-live (pre-launch content gate).
- Migration: writer tiers import from external spreadsheet (CSV → `ink_writer_tier`, joined on email); missing/ambiguous → default `brons` + flag.
- Migration: BuddyPress friendships convert to mutual follow records (each friendship → two follow records A→B and B→A).
- Afrikaans casing: sentence case for headings ("Begin skryf", not "Begin Skryf").
- Staff accounts (editor/administrator) forced to **English admin language** via per-user WP language setting; front-end output stays Afrikaans regardless.
- `ink-core` admin surfaces (CPT/taxonomy labels, tier promotion, sponsor scheduling, challenge/winner admin, reports) stay **Afrikaans**.

---

# Monetization

- **WooCommerce + WooCommerce Memberships** — memberships only (NOT a general storefront; suppress cart/catalog/checkout beyond membership purchase).
- Three fixed-term products: **R60/1mo, R300/6mo, R600/12mo**.
- **WooCommerce PayFast Gateway** — front-end ZAR payment (South African processor). This front-end purchase flow is the **new** capability closing the manual-EFT gap.
- **Active membership = submission entitlement** (access enforcement via expiry/suspension).
- **Free vs paid:** free members read/react/respond/library/training/reading-list/follow; paid subscribers add publishing rights.
- Auto-renew deferred until after launch (verify PayFast recurring support + extension compatibility before enabling).
- PayFast is off-site → **low PCI scope**.

---

# Non-Functional Requirements

- **Performance:** caching layer appropriate to host (LiteSpeed Cache for dynamic/logged-in + Cloudflare edge for static); avoid plugin sprawl and heavy front-end JS where a block pattern suffices. Content width 768px, wide 1400px.
- **SEO / indexability:** preserve archive URLs, 301 integrity, sitemaps, CPT schema (Rank Math). Content architecture precedes visual redesign. Keep `/biblioteek/` and `/opleiding/` URL prefixes.
- **Accessibility & readability:** Afrikaans readability prioritised over decorative type; reading templates text-legible first (768px content width).
- **i18n / Afrikaans-first:** front end + user-facing transactional emails are Afrikaans; site locale `af`; all custom strings use proper i18n functions; no English UI leakage (Quality Gate D); no AI-generated Afrikaans (human-authored only).
- **Maintainability:** Site Editor stability for non-technical staff; block locking on critical structure; design tokens enforced (no hardcoded colours/spacing/type).
- **Reliability / update governance:** WP core & plugins update on a partly uncontrollable cadence; gate major updates through staging, run regression on custom overrides + translation refresh; keep a production-side detection + fix path for untranslated strings.
- **Observability:** 404 logging (Redirection); Patchstack CVE alerts; automated English-leak check (crawl key front-end pages, scan for English / `wp i18n` untranslated counts).
- **Testing/QA (to make staging gate affordable):** test own seams not plugins. Pyramid: many unit tests for `ink-core` rules (Brain Monkey/WP_Mock via Pest/PHPUnit), fewer integration tests (`wp-env`/wp-browser) for seams (active membership ⇒ can submit; expired ⇒ denied; tier write ⇒ meta+log), thin E2E (Playwright) for critical journeys (register → choose intent → buy membership via PayFast sandbox → submit → publish → read/react → renewal/expiry). Risk-based depth: smoke for minor/security updates, full regression for majors.

---

# Constraints & Guardrails

- **Three-layer separation is non-negotiable** (theme / `ink-core` plugin / vetted platform plugins). **No business logic in the theme.**
- Plugins for commodity problems; custom code for INK-specific rules; theme for presentation only.
- **Preservation over convenience** — data continuity (DB, members, subscriptions, content, media) outranks clean-slate rebuild (brownfield).
- **Design tokens canonical** — no hardcoded colours/spacing/unnamed type sizes; everything maps to `theme.json`.
- **Lovable mockup is design intent, not production code** — extract design intent, do not port React/JSX/Tailwind. Do not lift English placeholder copy.
- **Editorial effort must stay low** — features depending on per-item manual editorial linking will be ignored; rely on shared taxonomy or automation.
- **Terminology guide is source of truth** (`afrikaans-terms.md`); a new concept is added to the guide **before** appearing in code or UI.
- **Security (layered):** Cloudflare (edge + login rule, origin locked to Cloudflare-only) + staff 2FA (or Cloudflare Access on `/wp-admin`) + Patchstack (CVE alerts) + host malware scanning + disciplined staging-gated updates. **No WordFence.** Loginizer retired. PayFast off-site → low PCI scope.
- **Content moderation:** logged report path (Report Content plugin or custom `ink-core` form); verify Afrikaans translatability.
- **Cost / sprawl:** lightweight tooling (Patchstack = alerts not heavy WAF); no dev/diagnostic/migration tools on production.
- **Privacy:** (POPIA not explicitly named — see Concerns/Gaps).

---

# Concerns this product carries

- **Integration density:** heavy dependence on BuddyPress (scoped) + WooCommerce + WooCommerce Memberships + WooCommerce PayFast Gateway + Real3D Flipbook + Rank Math + Redirection — many integration seams that must survive updates.
- **i18n leak surface:** third-party plugins emit runtime strings the theme can't own — error/validation/status messages (payment declines, "membership expired", login throttling), BuddyPress notification sentences, WooCommerce order/membership phrasing, transactional emails (Woo order/renewal/expiry, BP notifications, password reset), plugin JavaScript strings (e.g. Real3D viewer — separate JS `.json` translations), out-of-band outputs (REST/AJAX, redirect-notice query args, feeds). Irreducible exposure = premium/niche plugins with no community language packs (WooCommerce Memberships, PayFast gateway, Real3D, Report Content).
- **Operational:** uncontrollable WP core/host-forced update cadence can introduce new English strings; needs standing production detection + version-control reconciliation.
- **Data governance / migration:** several thousand posts to reclassify; `skryfwerk` holding bucket must not be hand-classified at volume; subscriptions migrate via DB clone with no import script (must verify memberships/plan IDs/access rules/expiry); tier import from spreadsheet (missing → default brons + flag); do not clone `wp_options` wholesale.
- **Public surfaces:** writer profiles with ratings/reviews, public skrywerprofiel, discovery hub, published works — public reputation and content exposure.
- **Compliance:** PCI scope kept low via off-site PayFast; (no explicit POPIA/privacy compliance plan stated — gap).

---

# Success Metrics / goals

- **No quantified success metrics, KPIs, or targets are stated in the spec.**
- Stated qualitative goal: break the pattern where writers visit only ~3×/month; give reasons to return and read the built-up library; foster supportive community engagement that serves reading (not feed-scrolling).
- Implicit success conditions: full data preservation, zero English leakage, automated payment replacing manual EFT.

---

# Explicit Non-Goals / Out of scope

- Any redesign/documentation of the *old* site beyond binding constraints.
- A general e-commerce storefront (WooCommerce = memberships only).
- A formal LMS (training is a resource hub, not courses/quizzes/certificates).
- Auto-renewing subscriptions at launch (deferred to a later phase).
- InkPols individual-article extraction (issues stay PDF-based).
- Public passage annotation / public inline commentary on works.
- AI-generated Afrikaans translation.
- BuddyPress Groups (off), Private Messaging (deferred / not in scope at launch), site-wide Activity, Blogs, Friend Connections (off).
- WordPress comments (disabled site-wide).
- Translating the WordPress admin interface (stays English by decision).

---

# Open Questions / unresolved decisions

Most §14 decisions were **resolved 2026-06-14**. Items still genuinely open / deferred:
- **Org content placeholders:** actual founding year + SA nonprofit legal status TBC at a future date (pre-launch content gate — must confirm before go-live).
- **PayFast recurring billing:** deferred post-launch; when pursued, verify PayFast recurring support + extension compatibility.
- **Private Messaging:** deferred; revisit later.
- **Biblioteek organisation/archive depth:** deferred and non-blocking — date/archive browsing, pagination, author filter finalised later.
- **Report Content plugin** Afrikaans translatability: verify or replace with `ink-core` form (unresolved review item).
- **Contact Form 7 vs Fluent Forms vs small `ink-core` form:** evaluate at build time.
- Pre-launch verifications: confirm NameHero plan tier runs LSWS; verify Real3D file paths/config; verify InkPols OG images before retiring Yoast; verify migrated subscriptions/media/audio-video playback.

---

# Notable quotes worth preserving (tone/voice/vision)

- "INK is a **literary publishing platform that fosters a supportive community — not a social network.**"
- "Engagement features serve reading, not feed-scrolling."
- "No English word should appear to a visitor or member."
- "Highlighting is **encouragement, not critique.**"
- "**Preservation over convenience.**"
- "**Three-layer separation is non-negotiable** … No business logic in the theme."
- "**Afrikaans is designed in from the start, not retrofitted.**"
- "The Lovable mockup is design intent, not production code."
- "**Editorial effort must stay low.** Features that depend on per-item manual editorial linking will be ignored under workload."
- "A paid subscriber at Brons tier is not the same as a Brons writer with an expired subscription."
- "No-English-leakage is a standing operational requirement, not a one-time build gate."
- Afrikaans sentence-case convention: "Begin skryf", not "Begin Skryf".
