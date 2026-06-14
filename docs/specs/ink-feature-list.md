# INK — Comprehensive Feature List

> **Companion to** `ink-consolidated-spec.md`. **Date:** 2026-06-14
> **Generated from:** [`spec-consolidation-brief.md`](./spec-consolidation-brief.md) — the originating brief and its four scope rules. Validate this deliverable against it.
> **Purpose:** A complete, decomposable inventory of features for the new INK site, organised as **epics → features → stories** for ingestion into a spec-driven framework (BMAD recommended). Each feature notes its layer (Theme / `ink-core` / Platform), data sources, and key acceptance criteria.
> **Fidelity rule:** Every feature below traces to a decision in the planning corpus or to a clarification confirmed on 2026-06-14. Items confirmed in clarification are tagged **[C-2026-06-14]**. Items still needing confirmation are tagged **[CONFIRM]** and cross-referenced to `ink-consolidated-spec.md §14`. No features have been invented.

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

---

## Epic 2 — Content models & taxonomy

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 2.1 | Register CPTs | K | P0 | `gedig`, `storie`, `artikel`, `skryfwerk`, `biblioteek_item`, `opleiding_artikel`, `uitdaging`, `inkpols_uitgawe`, `borg`. Afrikaans slugs per terms guide. |
| 2.2 | Register taxonomies | K | P0 | `genre`, `vaardigheid`, `uitdagingsronde`, `skrywervlak`. `genre`/`vaardigheid` shared across bydraes & training for auto-surfacing. |
| 2.3 | User meta | K | P0 | `ink_writer_tier`, `ink_tier_promoted_at`, `ink_writer_intent`. |
| 2.4 | CPT admin field sets | K | P1 | Per-CPT meta (e.g. InkPols issue date/volume/cover/PDF/teaser; challenge theme/deadline; sponsor link/tier/dates/placement). |
| 2.5 | Term images native | K/T | P2 | Replace WPCustom Category Image; reassign 11 existing images. |

---

## Epic 3 — Membership, access & payment

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 3.1 | Three fixed-term membership products | P | P0 | R60/1mo, R300/6mo, R600/12mo. No auto-renew at launch. |
| 3.2 | **Front-end PayFast purchase flow** | P+T | P0 | Member buys & self-activates membership; removes manual EFT/admin activation. ZAR. |
| 3.3 | Access enforcement | P | P0 | Active WooCommerce Membership = submission entitlement; expiry auto-suspends. |
| 3.4 | Lidmaatskap page | T | P0 | Plans, benefits, FAQ, CTA. Assembly-only (no mock); pricing-table pattern. |
| 3.5 | Renewal UI | T | P1 | On My Profiel → Lidmaatskap tab; choose 1/6/12 months. Show prices only (R60/R300/R600); no discount/savings labels (§14.5). |
| 3.6 | Store-UI suppression | K/P | P1 | Hide cart/catalog/checkout beyond membership purchase. |
| 3.7 | Status messaging (Afrikaans) | K | P1 | "Jou intekening is aktief…", "Jou intekening het verval…", access-denied messages per terms guide. |
| 3.8 | Auto-renew (recurring) | P | P2 | Deferred until after launch (§14.8); verify PayFast recurring support before enabling. |

---

## Epic 4 — Writer tiers (Brons / Silwer / Goud)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 4.1 | Tier data model | K | P0 | `ink_writer_tier` ∈ {brons, silwer, goud}; default brons. |
| 4.2 | Staff promotion admin UI | K | P0 | View tier, promote, record reason, optionally link to a challenge result; writes promotion log. |
| 4.3 | Promotion log / history | K | P1 | Auditable record (meta key or custom table). |
| 4.4 | Tier display on profiles | T | P1 | Brons/Silwer/Goud shown on member & writer profiles. |
| 4.5 | Tier in discovery & winners | K/T | P1 | Filter writers by tier; segment challenge participation; label winners (e.g. "Oktober Goud-wenner"). |
| 4.6 | Tier ≠ subscription guardrails | K | P0 | Code/config keeps tier and subscription strictly separate. |

---

## Epic 5 — Submission workflow (custom)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 5.1 | Custom front-end submission form | K+T | P0 | Replaces Youzify FES. Serves `gedig`/`storie`/`artikel` with appropriate fields & validation. Skryf page (`Write.tsx` reference). |
| 5.2 | Content-type selector | T | P0 | Poem / story / article; per-type placeholders & counters (lines+words for poems; words for prose). |
| 5.3 | Plain-text + basic formatting editor | K/T | P0 | No full rich-text editor. |
| 5.4 | Optional featured image | K | P1 | |
| 5.5 | Optional audio/video attachment | K | P1 | |
| 5.6 | Challenge linking at submission | K/T | P1 | Tick active challenges the piece responds to (writes `uitdagingsronde`). |
| 5.7 | Draft / publish states | K | P1 | "Stoor konsep" / "Plaas"; success screen with read-&-respond prompts. |
| 5.8 | Submission entitlement gate | K | P0 | Only active subscribers can publish; clear Afrikaans denial + link to plans. |
| 5.9 | Remove legacy edit-link filter | K | P1 | Drop the old `functions.php` `/plaas-nuwe-publikasie` override when Youzify retired. |

---

## Epic 6 — Reading & engagement

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 6.1 | Single reading templates | T | P0 | Detail (Archetype C) for `storie`/`artikel` (reference-ready). |
| 6.2 | **Gedig reading layout** | T | P1 | Designed 2026-06-14 (`PoetryReader.tsx`): stanza-aware, preserves line breaks, Roman-numeral stanza markers, per-line resonance (heart). Reference-ready. |
| 6.3 | Line highlighting + reactions | K+T | P1 | Select text → highlight; reactions hartjie/duim op/wow. Encouragement, not critique. No public annotation. |
| 6.4 | Structured community responses | K+T | P1 | "Gemeenskapsreaksies": types Lof/Insig/Voorstel. Replaces WP comments. |
| 6.5 | Contextual prompts after a piece | K/T | P2 | Guided response prompts (may vary by content type). |
| 6.6 | Suggested next reads | K | P2 | By tone/form/topic/tier via taxonomy. |
| 6.7 | Reading list (leeslys) | K+T | P1 | Save/remove works; toasts; surfaced on profile. |
| 6.8 | Reactions data + counts | K | P1 | "hartjies" count beside ♥ icon. |

---

## Epic 7 — Discovery (Ontdek)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 7.1 | Ontdek section + works archive | T | P0 | Reading/discovery hub for published writing (works); date/archive browse. Single-piece reading lives in Epic 6 (6.1/6.2). |
| 7.2 | Ontdek — bydraes tab | T | P1 | Browse all works; filter by type (Gedigte/Stories/Artikels); sort (Nuut/Opspraakwekkend/Mees geliefd). |
| 7.3 | Ontdek — skrywers tab | T | P1 | Browse writers; genre filter (Digkuns/Prosa/Artikels); sort (Meeste gelees/Nuwe stemme). |
| 7.4 | Search | K/P | P1 | Search works (title/theme) and writers (name/bio/genre). |
| 7.5 | Discovery surfaces | K/T | P2 | "writers like this", new voices, recently active, writers in your tier, unread-by-you. (Custom, not default community screens.) |

---

## Epic 8 — Community & social

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 8.1 | BuddyPress scoped config | P | P0 | Profiles, Directory, Notifications, Messaging on; Friend Connections, site-wide Activity, Groups, Blogs off. |
| 8.2 | **Follow graph (asymmetric)** | K | P0 | **[C-2026-06-14]** One-way follow in `ink-core`; follower/following counts; Volg/Volg tans UI. Replaces friendships. |
| 8.3 | **Following-feed** | K+T | P1 | **[C-2026-06-14]** Profile "Activity" tab = new publications by followed writers. Design exists (`Profile.tsx` Activity tab). |
| 8.4 | Custom profile templates | T | P1 | Block-theme BuddyPress templates (My Profiel + public Skrywerprofiel); tier, bio, stats, pinned works, accomplishments. |
| 8.5 | **Pinned / selected works** | K+T | P1 | **[C-2026-06-14]** Writer curates highlighted pieces on profile. |
| 8.6 | **Reader ratings & reviews** | K+T | P1 | **[C-2026-06-14]** Aggregate reader rating + written reviews on writer profiles. |
| 8.7 | Member directory (ledegids) | P/T | P1 | Writer discovery surface. |
| 8.8 | Private messaging | P+T | — | **Deferred — not in initial launch scope** (§14.7). BP Messaging off at launch; revisit later. |
| 8.9 | Notifications | P+T | P1 | @mentions, challenge announcements, follow/new-work alerts; "Merk alles as gelees". |
| 8.10 | Member online widget | — | — | **Removed** (CBX retired). Replace with engagement signals if any chrome remains. |

---

## Epic 9 — Library (Biblioteek)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 9.1 | `biblioteek_item` archive + single | T | P1 | Featured strip + category filter + search + card grid (Library layout reference). |
| 9.2 | Date / archive browsing | K+T | P1 | **Gap** vs mockup — detail deferred, non-blocking (§9.4). |
| 9.3 | Pagination | T | P1 | **Gap** vs mockup — detail deferred, non-blocking (§9.4). |
| 9.4 | Author filter | K+T | P1 | **Gap** vs mockup — detail deferred, non-blocking (§9.4). |
| 9.5 | Winner ↔ challenge linkage | K | P2 | Winners link back to producing challenge via `uitdagingsronde` taxonomy (or relationship where modelled). |

> The `Biblioteek organisasie.md` planning doc is an empty placeholder; biblioteek organisation is a flagged design gap (`ink-consolidated-spec.md §9.4`).

---

## Epic 10 — Training (Opleiding)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 10.1 | `opleiding_artikel` hub | T | P1 | Resource hub (not LMS). Library-layout archetype. |
| 10.2 | `vaardigheid` taxonomy + faceted search | K+T | P1 | Skill areas (Begin hier, Skryfkuns, Digkuns, Prosa, Stylfigure, Redigeer en hersien, Stem en styl). |
| 10.3 | Editor's shelf / curated entry points | T | P2 | "Die redakteur se rak" + empty states. |
| 10.4 | Auto cross-surfacing | K | P2 | Shared `genre`/`vaardigheid` terms surface training under works/challenges automatically — no manual linking. |
| 10.5 | Community contribution CTA | T | P2 | "Plaas 'n stuk" for community-written guides. |

---

## Epic 11 — Challenges (Uitdagings) & winners

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 11.1 | `uitdaging` single page | T | P1 | Prompt, literary devices, submission rules, prize, deadline, resources, entries list (`Challenge.tsx` reference). |
| 11.2 | Uitdagings list page | T | P1 | Archetype B; countdown; partial mock only. |
| 11.3 | Challenge metadata | K | P1 | `challenge_theme`, `challenge_deadline`; **monthly** cadence (resolved §14.3). |
| 11.4 | Entry capture | K | P1 | `inskrywing` linked to round via `uitdagingsronde`. |
| 11.5 | **Tier-based competition pools** | K | P1 | Brons vs Brons, Silwer vs Silwer, Goud vs Goud. Winners announced per tier. |
| 11.6 | Structured winner records | K | P0 (for admin) | Queryable winner data per tier; surfaces contextually ("Oktober Goud-wenner"). |
| 11.7 | Winner → tier promotion link | K | P1 | Optional link from promotion log to challenge result. |
| 11.8 | Historical challenge migration | K | P1 | Once-off DB update (§14.6): challenge categories → `uitdagingsronde` terms + an `uitdaging` record per round; preserves each piece's challenge linkage. Full brief/deadline only where old data exists. |

---

## Epic 12 — InkPols

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 12.1 | `inkpols_uitgawe` model | K | P1 | Structured meta: issue date, volume, cover image, PDF, teaser. |
| 12.2 | Issue archive (by year) | T | P1 | Clean archive + robust single-issue page. |
| 12.3 | PDF viewing | P | P1 | Real3D Flipbook (reactivate). No individual-article extraction. |
| 12.4 | Back-catalogue migration | K | P1 | Re-link existing PDFs; replace month/year naming with date+volume meta. |

---

## Epic 13 — Sponsors (Borge)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 13.1 | `borg` CPT | K | P1 | Fields: name, logo variants, link, `sponsor_tier`, campaign start/end, placement preferences. |
| 13.2 | Scheduling / rotation logic | K | P1 | Campaign dates drive display; rotation. |
| 13.3 | Homepage sponsor placement | T | P1 | One featured or rotating sponsor; subtle strip. No logo dumps on content pages. |
| 13.4 | Sponsor recognition page | T | P2 | Full recognition on Oor INK. |

---

## Epic 14 — Organisation pages & contact

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 14.1 | Tuisblad | T | P0 | Hero spotlight, challenge section, featured works, sponsors, CTA (reference-ready). |
| 14.2 | Gemeenskap page | T | P1 | Visitor conversion/marketing (value props, principles, how-it-works, CTAs). |
| 14.3 | Oor INK | T | P1 | Mission, contact, sponsors, org pages. Assembly-only. Use placeholders for founding year + SA legal status; pre-launch content gate (§14.4). |
| 14.4 | Kontak | T+P | P1 | Form page (CF7 / Fluent Forms / `ink-core`). |
| 14.5 | Auth flows | T+P | P0 | Registreer / Meld aan / Wagwoord-herstel; reader-or-writer intent; assembly-only. |
| 14.6 | Footer / social links | T | P1 | Theme-native pattern (replaces Ultimate Social Media Icons). |

---

## Epic 15 — Migration & redirects

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 15.1 | DB clone & sanitise | — | P0 | Strip transients/logs; clean baseline. |
| 15.2 | User import + role reassignment | K | P0 | Reader/writer base roles; profile-field cleanup. |
| 15.3 | Tier CSV import | K | P0 | Email join key; defaults + flags for edge cases. |
| 15.4 | Subscription verification | P | P0 | Confirm memberships/plan IDs/access rules/expiry on new host. No import. |
| 15.5 | Post → CPT reclassification | K | P0 | Category-driven; `skryfwerk` catch-all; flush rewrite rules. |
| 15.6 | Library/training migration | K | P1 | By URL sub-path → CPT + taxonomy terms. |
| 15.7 | Redirect generation | K+P | P0 | 301s recorded during CPT migration; keep `/biblioteek/`,`/opleiding/` prefixes; verify by crawl. |
| 15.8 | InkPols / sponsors / nav | K/T | P1 | Per §11/§13/IA; nav rebuilt fresh. |
| 15.9 | BuddyPress data + friendship→follow | K | P1 | Convert each friendship → two mutual follows (§14.10); trim old activity; messaging deferred (§14.7). |
| 15.10 | Media verification | — | P1 | Uploads accessible; audio/video play; PDFs open. |
| 15.11 | Options carry-forward (selective) | — | P1 | No wholesale `wp_options` clone. |
| 15.12 | WPBakery shortcode cleanup | K | P1 | Grep `[vc_*]`; strip/convert; none rendered as raw text. |

---

## Epic 16 — Afrikaans-first & localisation

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 16.1 | Locale `af` + i18n discipline | K/T | P0 | Site locale `af`; all custom strings internationalised; sentence-case headings. **Admin stays English** — staff roles (editor/administrator) forced to English admin language via per-user WP language in `ink-core`; front end stays Afrikaans (§14.14). **`ink-core`'s own admin labels/screens stay Afrikaans** (authored as source, no English `.mo`) so they render Afrikaans under English admin locale (§14.15). |
| 16.2 | Apply approved UI copy | T | P0 | From `ui-copy-translations.md`; resolve placeholder org details. |
| 16.3 | Residual plugin Afrikaans pass | P | P1 | Theme & `ink-core` are Afrikaans-native. Translate only surviving third-party plugin strings (BuddyPress, WC/Memberships/PayFast; + Loginizer/Report Content/CF7 if kept) via Loco as authoring tool; **commit `.mo` to version control**; use w.org language packs where complete. Loco kept active on production as a safety net, fixes reconciled to version control (§14.13). Human-authored. |
| 16.4 | Terminology reconciliation | — | ✅ Done 2026-06-14 | `afrikaans-terms.md` updated: follow vocabulary (`Volg`/`Volg tans`/`volgeling`; friendship terms replaced; follow-avoidance dropped); Storie code ID → `storie`; tier values → `brons`/`silwer`/`goud`. `ui-copy-translations.md` "Volgers" → "volgelinge" also done. |
| 16.5 | No-English-leakage QA gate | T | P1 | Gate D on every **front-end** template/pattern + user-facing emails. Admin excluded — stays English (§14.14). |

---

## Epic 17 — SEO, security & performance

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 17.1 | Rank Math config + CPT schema | P | P1 | Adopted from the start (replaces Yoast — no meaningful Yoast data). Sitemaps, meta, breadcrumbs, native schema for `gedig`/`storie`/`artikel`. Import InkPols OG images from Yoast, verify, then deactivate Yoast. Templated defaults, not per-post backfill. |
| 17.2 | Redirect integrity | P | P0 | All old URLs → correct 301; 404 tracking. |
| 17.3 | Security stack (layered) | P | P1 | **Resolved (§14.16):** Cloudflare (edge + login rule, origin locked) + staff 2FA + Patchstack (CVE alerts) + staging-gated updates (17.7) + **host-provided malware scanning**. Loginizer retired (Cloudflare covers login); no WordFence. Patchstack new. PayFast off-site → low PCI scope. |
| 17.4 | Moderation/report path | P/K | P1 | Report Content (translated) or `ink-core` form. |
| 17.5 | Caching layer | P | P1 | LiteSpeed Cache (NameHero runs LiteSpeed) + Cloudflare edge caching (§14.9). |
| 17.6 | Production hygiene | — | P0 | No dev/diagnostic/migration plugins active on production. |
| 17.7 | Update governance & i18n resilience | P/K | P1 | Gate major core/plugin updates via staging where possible (regression on custom templates + translation refresh). Rely on auto language packs for core/well-covered w.org plugins; committed `.mo` for premium plugins (Memberships/PayFast/Real3D/Report Content), re-checked after their updates. Keep a production string-fix path + detection for new untranslated strings; reconcile prod fixes to version control. No-English-leakage is a standing requirement (§14.13). |
| 17.8 | Automated test harness | K | P1 | Pyramid: **unit** (Pest/PHPUnit + Brain Monkey/WP_Mock) for `ink-core` rules; **integration** (`wp-env` + WP test lib / wp-browser) for plugin seams (membership⇒submit, expired⇒denied, tier⇒meta+log); **E2E** (Playwright) for critical journeys (register→buy via PayFast sandbox→submit→publish→read→renew). CI per change + E2E smoke on staging deploy. Risk-based depth (smoke for minor, full for major). Includes automated English-leak scan. Concentrate in `ink-core`; theme via E2E/visual. |

---

## Cross-cutting acceptance criteria (apply to every epic)

1. **Three-layer compliance** — no business logic in the theme.
2. **Token compliance** — no hardcoded colours/spacing/unnamed type sizes (Gate A).
3. **Afrikaans-first** — correct terms, no English leakage (Gate D).
4. **Tier ≠ subscription** — never conflated.
5. **Editorial low-friction** — no mandatory per-item manual linking.
6. **Site Editor stability** — non-technical staff can manage content; critical structure locked.

---

## Summary

| Epic | P0 features | Total features |
|---|---|---|
| 1 Foundation | 5 | 9 |
| 2 Content models | 3 | 5 |
| 3 Membership & payment | 4 | 8 |
| 4 Writer tiers | 3 | 6 |
| 5 Submission | 4 | 9 |
| 6 Reading & engagement | 1 | 8 |
| 7 Discovery | 1 | 5 |
| 8 Community & social | 2 | 10 |
| 9 Library | 0 | 5 |
| 10 Training | 0 | 5 |
| 11 Challenges | 2 | 8 |
| 12 InkPols | 0 | 4 |
| 13 Sponsors | 0 | 4 |
| 14 Org pages | 3 | 6 |
| 15 Migration | 5 | 12 |
| 16 Afrikaans-first | 2 | 5 |
| 17 SEO/security/perf | 2 | 6 |
