# INK — Features Extract (for PRD)

> Source: `docs/specs/ink-feature-list.md` (2026-06-14). Layer legend: `T` theme · `K` ink-core · `P` platform plugin. Priority: `P0` launch-critical · `P1` launch · `P2` fast-follow. Faithful extract — no invented capabilities.

## Epic / Feature Groups

### Epic 1 — Foundation (theme + tokens + ink-core scaffold)
Design-system, theme templates, and `ink-core` plugin scaffold. **Foundational MVP epic** (mostly P0).
- System provides a `theme.json` design-token system mapping all colour/type/spacing/layout/radius/shadow tokens (Gate A — no hardcoded values). [1.1, P0]
- System provides a typography system: Lora (display/heading) + Inter (body/UI), named scale xs–3xl, Afrikaans readability prioritised. [1.2, P0]
- System provides dark-mode tokens wired into the theme. [1.3, P1]
- System provides global templates & template parts (header, footer, section shells). [1.4, P0]
- System provides a core block-pattern library (hero, featured grid, archive intro, CTA bands, profile summaries, card/button/emphasis variants). [1.5, P0]
- System locks critical editorial structure while content stays editable (block locking). [1.6, P1]
- System provides the `ink-core` plugin scaffold (bootstrap, `includes/`, activation hooks, i18n loading). [1.7, P0]
- System disables comments via `comments_open`/`pings_open` → false (replaces Comments Plus post-migration). [1.8, P1]
- System documents & builds page archetypes A–D for non-mocked pages. [1.9, P1]

### Epic 2 — Content models & taxonomy
Custom post types, taxonomies, and user meta. **Foundational MVP epic** (core CPTs/taxonomies/meta P0).
- System registers CPTs: `gedig`, `storie`, `artikel`, `skryfwerk`, `biblioteek_item`, `opleiding_artikel`, `uitdaging`, `inkpols_uitgawe`, `borg` (Afrikaans slugs). [2.1, P0]
- System registers taxonomies: `genre`, `vaardigheid`, `uitdagingsronde`, `skrywervlak`; `genre`/`vaardigheid` shared across bydraes & training for auto-surfacing. [2.2, P0]
- System stores user meta: `ink_writer_tier`, `ink_tier_promoted_at`, `ink_writer_intent`. [2.3, P0]
- System provides per-CPT admin field sets (InkPols issue date/volume/cover/PDF/teaser; challenge theme/deadline; sponsor link/tier/dates/placement). [2.4, P1]
- System provides native term images (replaces WPCustom Category Image; reassign 11 existing images). [2.5, P2]

### Epic 3 — Membership, access & payment
Subscription products, self-service PayFast purchase, and access enforcement.
- System offers three fixed-term membership products: R60/1mo, R300/6mo, R600/12mo (no auto-renew at launch). [3.1, P0]
- User can buy & self-activate a membership via a front-end PayFast purchase flow in ZAR (removes manual EFT/admin activation). [3.2, P0]
- System enforces access: active WooCommerce Membership = submission entitlement; expiry auto-suspends. [3.3, P0]
- System provides a Lidmaatskap page (plans, benefits, FAQ, CTA; assembly-only). [3.4, P0]
- User can renew via My Profiel → Lidmaatskap tab (choose 1/6/12 months; prices only R60/R300/R600, no discount/savings labels). [3.5, P1]
- System suppresses store UI (cart/catalog/checkout beyond membership purchase). [3.6, P1]
- System shows Afrikaans status messaging ("Jou intekening is aktief…", "Jou intekening het verval…", access-denied). [3.7, P1]
- System supports auto-renew (recurring). **Deferred until after launch** (verify PayFast recurring support first). [3.8, P2]

### Epic 4 — Writer tiers (Brons / Silwer / Goud)
Tier data model, staff promotion, and tier display — strictly separate from subscription.
- System models tier `ink_writer_tier` ∈ {brons, silwer, goud}; default brons. [4.1, P0]
- Staff can view tier, promote, record reason, optionally link to a challenge result, and write a promotion log. [4.2, P0]
- System keeps an auditable promotion log / history. [4.3, P1]
- System displays Brons/Silwer/Goud on member & writer profiles. [4.4, P1]
- System uses tier in discovery & winners (filter by tier; segment challenge participation; label winners e.g. "Oktober Goud-wenner"). [4.5, P1]
- System keeps tier ≠ subscription strictly separate (code/config guardrails). [4.6, P0]

### Epic 5 — Submission workflow (custom)
Custom front-end submission replacing Youzify FES.
- User can submit `gedig`/`storie`/`artikel` via a custom front-end form (Skryf page, `Write.tsx` ref) with type-appropriate fields & validation. [5.1, P0]
- User can pick content type (poem/story/article) with per-type placeholders & counters (lines+words for poems; words for prose). [5.2, P0]
- User can write in a plain-text + basic formatting editor (no full rich-text). [5.3, P0]
- User can add an optional featured image. [5.4, P1]
- User can add an optional audio/video attachment. [5.5, P1]
- User can link a piece to active challenges at submission (writes `uitdagingsronde`). [5.6, P1]
- User can save draft / publish ("Stoor konsep" / "Plaas"); success screen with read-&-respond prompts. [5.7, P1]
- System gates publishing to active subscribers only (Afrikaans denial + link to plans). [5.8, P0]
- System removes the legacy `/plaas-nuwe-publikasie` edit-link filter when Youzify retired. [5.9, P1]

### Epic 6 — Reading & engagement
Reading templates, reactions, and structured community responses.
- System provides single reading templates (Archetype C) for `storie`/`artikel`. [6.1, P0]
- System provides a Gedig reading layout (`PoetryReader.tsx`): stanza-aware, preserves line breaks, Roman-numeral stanza markers, per-line resonance (heart). [6.2, P1]
- User can highlight selected text and add reactions hartjie/duim op/wow (encouragement, not critique; no public annotation). [6.3, P1]
- User can post structured community responses "Gemeenskapsreaksies" of types Lof/Insig/Voorstel (replaces WP comments). [6.4, P1]
- System shows contextual guided prompts after a piece. [6.5, P2]
- System suggests next reads by tone/form/topic/tier via taxonomy. [6.6, P2]
- User can save/remove works to a reading list (leeslys) with toasts, surfaced on profile. [6.7, P1]
- System stores reactions data + counts ("hartjies" beside ♥). [6.8, P1]

### Epic 7 — Discovery (Ontdek)
Discovery hub, archives, tabs, and search.
- System provides the Ontdek section + works archive (date/archive browse). [7.1, P0]
- User can browse the bydraes tab: filter by type (Gedigte/Stories/Artikels); sort (Nuut/Opspraakwekkend/Mees geliefd). [7.2, P1]
- User can browse the skrywers tab: genre filter (Digkuns/Prosa/Artikels); sort (Meeste gelees/Nuwe stemme). [7.3, P1]
- User can search works (title/theme) and writers (name/bio/genre). [7.4, P1]
- System provides discovery surfaces ("writers like this", new voices, recently active, writers in your tier, unread-by-you). [7.5, P2]

### Epic 8 — Community & social
BuddyPress-scoped social: asymmetric follow, profiles, ratings.
- System configures BuddyPress scope: Profiles/Directory/Notifications/Messaging on; Friend Connections/site-wide Activity/Groups/Blogs off. [8.1, P0]
- User can follow another writer one-way (asymmetric follow in `ink-core`; follower/following counts; Volg/Volg tans UI; replaces friendships). [8.2, P0, C-2026-06-14]
- User sees a following-feed = Profile "Activity" tab of new publications by followed writers (`Profile.tsx`). [8.3, P1, C-2026-06-14]
- System provides custom block-theme BuddyPress profile templates (My Profiel + public Skrywerprofiel) with tier, bio, stats, pinned works, accomplishments. [8.4, P1]
- User can curate pinned / selected works on profile. [8.5, P1, C-2026-06-14]
- User can give reader ratings & reviews (aggregate rating + written reviews on writer profiles). [8.6, P1, C-2026-06-14]
- System provides a member directory (ledegids). [8.7, P1]
- Private messaging — **Deferred, not in initial launch scope** (BP Messaging off at launch). [8.8, —]
- System sends notifications (@mentions, challenge announcements, follow/new-work alerts; "Merk alles as gelees"). [8.9, P1]
- Member online widget — **Removed** (CBX retired). [8.10, —]

### Epic 9 — Library (Biblioteek)
`biblioteek_item` archive/single. Several sub-features flagged as gaps vs mockup (deferred, non-blocking).
- System provides `biblioteek_item` archive + single (featured strip + category filter + search + card grid). [9.1, P1]
- System provides date/archive browsing. **Gap vs mockup — deferred, non-blocking.** [9.2, P1]
- System provides pagination. **Gap — deferred, non-blocking.** [9.3, P1]
- System provides author filter. **Gap — deferred, non-blocking.** [9.4, P1]
- System links winners ↔ challenge via `uitdagingsronde`. [9.5, P2]
- Note: `Biblioteek organisasie.md` is an empty placeholder; library organisation is a flagged design gap.

### Epic 10 — Training (Opleiding)
Resource hub (not LMS) with skill taxonomy and auto cross-surfacing. (Mostly P1/P2.)
- System provides the `opleiding_artikel` hub (Library-layout archetype). [10.1, P1]
- User can faceted-search by `vaardigheid` (Begin hier, Skryfkuns, Digkuns, Prosa, Stylfigure, Redigeer en hersien, Stem en styl). [10.2, P1]
- System provides an editor's shelf / curated entry points ("Die redakteur se rak" + empty states). [10.3, P2]
- System auto cross-surfaces training under works/challenges via shared `genre`/`vaardigheid` terms (no manual linking). [10.4, P2]
- User can contribute community-written guides via "Plaas 'n stuk" CTA. [10.5, P2]

### Epic 11 — Challenges (Uitdagings) & winners
Monthly challenges with tier-based pools and winner records.
- System provides a `uitdaging` single page (prompt, literary devices, rules, prize, deadline, resources, entries; `Challenge.tsx`). [11.1, P1]
- System provides the Uitdagings list page (Archetype B; countdown; partial mock). [11.2, P1]
- System stores challenge metadata `challenge_theme`, `challenge_deadline`; **monthly** cadence. [11.3, P1]
- User can submit an entry (`inskrywing`) linked to a round via `uitdagingsronde`. [11.4, P1]
- System runs tier-based competition pools (Brons vs Brons, Silwer vs Silwer, Goud vs Goud); winners announced per tier. [11.5, P1]
- System keeps structured, queryable winner records per tier (surfaces e.g. "Oktober Goud-wenner"). [11.6, P0 for admin]
- System links winner → tier promotion (optional link from promotion log to challenge result). [11.7, P1]
- System migrates historical challenges (once-off DB update: challenge categories → `uitdagingsronde` terms + an `uitdaging` per round; preserves linkage). [11.8, P1]

### Epic 12 — InkPols
Periodical issue model, archive, and PDF viewing.
- System provides the `inkpols_uitgawe` model (issue date, volume, cover, PDF, teaser). [12.1, P1]
- System provides an issue archive (by year) + robust single-issue page. [12.2, P1]
- System provides PDF viewing via Real3D Flipbook (no individual-article extraction). [12.3, P1]
- System migrates the back-catalogue (re-link PDFs; date+volume meta replaces month/year naming). [12.4, P1]

### Epic 13 — Sponsors (Borge)
Sponsor CPT, scheduling/rotation, and placement.
- System provides a `borg` CPT (name, logo variants, link, `sponsor_tier`, campaign start/end, placement prefs). [13.1, P1]
- System schedules/rotates sponsor display by campaign dates. [13.2, P1]
- System places one featured or rotating sponsor on the homepage (subtle strip; no logo dumps on content pages). [13.3, P1]
- System provides a sponsor recognition page on Oor INK. [13.4, P2]

### Epic 14 — Organisation pages & contact
Marketing/org pages, contact, and auth flows.
- System provides the Tuisblad (hero spotlight, challenge section, featured works, sponsors, CTA). [14.1, P0]
- System provides the Gemeenskap page (visitor conversion/marketing). [14.2, P1]
- System provides Oor INK (mission, contact, sponsors, org pages; placeholders for founding year + SA legal status; pre-launch content gate). [14.3, P1]
- System provides a Kontak form page (CF7 / Fluent Forms / `ink-core`). [14.4, P1]
- User can register / log in / reset password (Registreer / Meld aan / Wagwoord-herstel; reader-or-writer intent). [14.5, P0]
- System provides a theme-native footer / social links pattern (replaces Ultimate Social Media Icons). [14.6, P1]

### Epic 15 — Migration & redirects
Brownfield data migration, role reassignment, CPT reclassification, redirects. **Several P0 migration steps.**
- System clones & sanitises the DB (strip transients/logs; clean baseline). [15.1, P0]
- System imports users + reassigns roles (reader/writer base roles; profile-field cleanup). [15.2, P0]
- System imports tier CSV (email join key; defaults + flags for edge cases). [15.3, P0]
- System verifies subscriptions (memberships/plan IDs/access rules/expiry on new host; no import). [15.4, P0]
- System reclassifies posts → CPTs (category-driven; `skryfwerk` catch-all; flush rewrite rules). [15.5, P0]
- System migrates library/training by URL sub-path → CPT + taxonomy terms. [15.6, P1]
- System generates redirects (301s during CPT migration; keep `/biblioteek/`,`/opleiding/` prefixes; verify by crawl). [15.7, P0]
- System migrates InkPols / sponsors / nav (nav rebuilt fresh). [15.8, P1]
- System converts BuddyPress friendships → two mutual follows; trims old activity; messaging deferred. [15.9, P1]
- System verifies media (uploads, audio/video, PDFs). [15.10, P1]
- System carries forward options selectively (no wholesale `wp_options` clone). [15.11, P1]
- System cleans WPBakery shortcodes (grep `[vc_*]`; strip/convert; none rendered as raw text). [15.12, P1]

### Epic 16 — Afrikaans-first & localisation
Locale, i18n discipline, UI copy, terminology, and leakage QA.
- System sets locale `af`, internationalises all custom strings, forces staff admin to English while front end stays Afrikaans; `ink-core` admin labels stay Afrikaans. [16.1, P0]
- System applies approved UI copy from `ui-copy-translations.md`. [16.2, P0]
- System does a residual third-party plugin Afrikaans pass via Loco; commits `.mo` to version control; Loco kept as production safety net. [16.3, P1]
- Terminology reconciliation — **✅ Done 2026-06-14** (follow vocabulary `Volg`/`Volg tans`/`volgeling`; `storie` code ID; tier values `brons`/`silwer`/`goud`; "Volgers"→"volgelinge"). [16.4]
- System enforces a no-English-leakage QA gate (Gate D) on all front-end templates/patterns + user-facing emails (admin excluded). [16.5, P1]

### Epic 17 — SEO, security & performance
SEO config, layered security, caching, update governance, and test harness.
- System configures Rank Math + CPT schema (sitemaps, meta, breadcrumbs, native schema for `gedig`/`storie`/`artikel`; replaces Yoast). [17.1, P1]
- System ensures redirect integrity (all old URLs → 301; 404 tracking). [17.2, P0]
- System provides a layered security stack: Cloudflare (edge + login rule) + staff 2FA + Patchstack + staging-gated updates + host malware scanning (Loginizer/WordFence retired; PayFast off-site → low PCI scope). [17.3, P1]
- System provides a moderation/report path (Report Content translated or `ink-core` form). [17.4, P1]
- System provides a caching layer (LiteSpeed Cache + Cloudflare edge). [17.5, P1]
- System maintains production hygiene (no dev/diagnostic/migration plugins active on production). [17.6, P0]
- System governs updates & i18n resilience (gate major updates via staging; language packs + committed `.mo`; standing no-English-leakage requirement). [17.7, P1]
- System provides an automated test harness: unit (Pest/PHPUnit + Brain Monkey/WP_Mock), integration (`wp-env`/wp-browser for membership⇒submit, expired⇒denied, tier⇒meta+log), E2E (Playwright: register→buy via PayFast sandbox→submit→publish→read→renew); CI per change + staging E2E smoke; automated English-leak scan. [17.8, P1]

## Cross-cutting items
- **Three-layer compliance** — no business logic in the theme.
- **i18n / Afrikaans-first** — locale `af`, correct terms, no English leakage (Gate D); Epic 16 spans all front-end work; admin stays English.
- **Design tokens** — no hardcoded colours/spacing/unnamed type sizes (Gate A); Epic 1 `theme.json` spans every template/pattern.
- **Migration** — Epic 15 (and migration sub-tasks embedded in Epics 11.8, 12.4, 8/15.9) span the brownfield rebuild.
- **Testing** — automated test harness (17.8) concentrated in `ink-core`, theme via E2E/visual; includes automated English-leak scan.
- **Tier ≠ subscription** — never conflated (guardrail across Epics 3 & 4).
- **Editorial low-friction** — no mandatory per-item manual linking (auto cross-surfacing via shared `genre`/`vaardigheid`).
- **Site Editor stability** — non-technical staff manage content; critical structure locked.

## Sequencing / dependencies (as stated)
- **Epic 1 (Foundation)** and **Epic 2 (Content models)** are prerequisites — design tokens, templates, `ink-core` scaffold, CPTs/taxonomies/user meta underpin all later epics (mostly P0).
- **Epic 15 (Migration)** depends on CPTs/taxonomies (Epic 2): post→CPT reclassification, role reassignment, tier CSV import, redirect generation.
- Submission gate (5.8) and access enforcement (3.3) depend on membership entitlement (Epic 3).
- Tier features (Epic 4) depend on `ink_writer_tier` meta (2.3); challenge tier pools (11.5) and winner→promotion link (11.7) depend on Epic 4.
- Comment-disable (1.8), legacy edit-link removal (5.9), and follow conversion (15.9) are sequenced **after** Youzify/Comments Plus retirement / migration.
- Auto-renew (3.8) deferred until after launch, pending PayFast recurring verification.
- Historical challenge migration (11.8), InkPols back-catalogue (12.4), and Yoast→Rank Math (17.1) are once-off migration steps.

## MVP signal (core vs deferred)
- **Priority tags drive MVP:** `P0` = launch-critical, `P1` = launch, `P2` = fast-follow. Per the file's summary table, ~33 features are P0.
- **Core/foundational P0-heavy epics:** Epic 1 Foundation (5 P0), Epic 2 Content models (3 P0), Epic 3 Membership & payment (4 P0), Epic 5 Submission (4 P0), Epic 15 Migration (5 P0), Epic 14 Org pages (3 P0), Epic 4 Tiers (3 P0), Epic 11 (2), Epic 8 (2), Epic 16 (2), Epic 17 (2).
- **Explicitly deferred / out of initial launch scope:**
  - 3.8 Auto-renew (recurring) — deferred until after launch.
  - 8.8 Private messaging — deferred, not in initial launch scope (BP Messaging off).
- **Removed:** 8.10 Member online widget (CBX retired).
- **Flagged gaps (deferred, non-blocking):** 9.2/9.3/9.4 Library date-browse/pagination/author-filter; library organisation design gap (empty `Biblioteek organisasie.md`).
- **Lowest-priority epics (no P0):** Epic 9 Library, Epic 10 Training, Epic 12 InkPols, Epic 13 Sponsors are entirely P1/P2 — fast-follow leaning.
- **Already done:** 16.4 Terminology reconciliation (✅ 2026-06-14).
- **Confirmation tags:** 8.2/8.3/8.5/8.6 carry **[C-2026-06-14]** (clarification-confirmed); the file states other items may still carry **[CONFIRM]** cross-referenced to spec §14.
