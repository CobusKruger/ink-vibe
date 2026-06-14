# INK — Comprehensive Feature List

> **Companion to** `ink-consolidated-spec.md`. **Date:** 2026-06-14
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
| 2.2 | Register taxonomies | K | P0 | `genre`, `vaardigheid`, `uitdagingsronde`, `skrywervlak`. `genre`/`vaardigheid` shared across bydraes & training for auto-surfacing. |
| 2.3 | User meta | K | P0 | `ink_writer_tier`, `ink_tier_promoted_at`, `ink_writer_intent`. |
| 2.4 | CPT admin field sets | K | P1 | Per-CPT meta (e.g. InkPols issue date/volume/cover/PDF/teaser; challenge theme/deadline; sponsor link/tier/dates/placement). |
| 2.5 | Term images native | K/T | P2 | Replace WPCustom Category Image; reassign 11 existing images. |

---

## Epic 3 — Accounts, registration & auth

> **Foundational dependency.** Accounts and the reader/writer-intent choice precede membership purchase (Epic 4), submission (Epic 6), and community features (Epic 9) — nothing user-specific can be built or tested without them. Decomposed from the registration lifecycle confirmed in `ink-consolidated-spec.md §4` and the former Org-pages auth row.

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 3.1 | Authentication pages | T+P | P0 | Registreer / Meld aan / Wagwoord-herstel — Afrikaans auth surfaces. Assembly-only (no mock). |
| 3.2 | Reader/writer intent capture | K | P0 | Capture `ink_writer_intent` (`leser`/`skrywer`) at registration (meta defined in 2.3); drives the downstream journey. |
| 3.3 | Registration lifecycle / onboarding | K/T | P1 | Per §4: create account → choose reader/writer intent → complete profile → if writer, explain tiers (Epic 5) + subscription requirement (Epic 4) → prompt first social action after signup. |

---

## Epic 4 — Membership, access & payment

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 4.1 | Three fixed-term membership products | P | P0 | R60/1mo, R300/6mo, R600/12mo. No auto-renew at launch. |
| 4.2 | **Front-end PayFast purchase flow** | P+T | P0 | Member buys & self-activates membership; removes manual EFT/admin activation. ZAR. |
| 4.3 | Access enforcement | P | P0 | Active WooCommerce Membership = submission entitlement; expiry auto-suspends. |
| 4.4 | Lidmaatskap page | T | P0 | Plans, benefits, FAQ, CTA. Assembly-only (no mock); pricing-table pattern. |
| 4.5 | Renewal UI | T | P1 | On My Profiel → Lidmaatskap tab; choose 1/6/12 months. Show prices only (R60/R300/R600); no discount/savings labels (§14.5). |
| 4.6 | Store-UI suppression | K/P | P1 | Hide cart/catalog/checkout beyond membership purchase. |
| 4.7 | Status messaging (Afrikaans) | K | P1 | "Jou intekening is aktief…", "Jou intekening het verval…", access-denied messages per terms guide. |
| 4.8 | Auto-renew (recurring) | P | P2 | Deferred until after launch (§14.8); verify PayFast recurring support before enabling. |

---

## Epic 5 — Writer tiers (Brons / Silwer / Goud)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 5.1 | Tier data model | K | P0 | `ink_writer_tier` ∈ {brons, silwer, goud}; default brons. |
| 5.2 | Staff promotion admin UI | K | P0 | View tier, promote, record reason, optionally link to a challenge result; writes promotion log. |
| 5.3 | Promotion log / history | K | P1 | Auditable record (meta key or custom table). |
| 5.4 | Tier display on profiles | T | P1 | Brons/Silwer/Goud shown on member & writer profiles. |
| 5.5 | Tier in discovery & winners | K/T | P1 | Filter writers by tier; segment challenge participation; label winners (e.g. "Oktober Goud-wenner"). |
| 5.6 | Tier ≠ subscription guardrails | K | P0 | Code/config keeps tier and subscription strictly separate. |

---

## Epic 6 — Submission workflow (custom)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 6.1 | Custom front-end submission form | K+T | P0 | Replaces Youzify FES. Serves `gedig`/`storie`/`artikel` with appropriate fields & validation. Skryf page (`Write.tsx` reference). |
| 6.2 | Content-type selector | T | P0 | Poem / story / article; per-type placeholders & counters (lines+words for poems; words for prose). |
| 6.3 | Plain-text + basic formatting editor | K/T | P0 | No full rich-text editor. |
| 6.4 | Optional featured image | K | P1 | |
| 6.5 | Optional audio/video attachment | K | P1 | |
| 6.6 | Challenge linking at submission | K/T | P1 | Tick active challenges the piece responds to (writes `uitdagingsronde`). |
| 6.7 | Draft / publish states | K | P1 | "Stoor konsep" / "Plaas"; success screen with read-&-respond prompts. |
| 6.8 | Submission entitlement gate | K | P0 | Only active subscribers can publish; clear Afrikaans denial + link to plans. |
| 6.9 | Remove legacy edit-link filter | K | P1 | Drop the old `functions.php` `/plaas-nuwe-publikasie` override when Youzify retired. |

---

## Epic 7 — Reading & engagement

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 7.1 | Single reading templates | T | P0 | Detail (Archetype C) for `storie`/`artikel` (reference-ready). |
| 7.2 | **Gedig reading layout** | T | P1 | Designed 2026-06-14 (`PoetryReader.tsx`): stanza-aware, preserves line breaks, Roman-numeral stanza markers, per-line resonance (heart). Reference-ready. |
| 7.3 | Line highlighting + reactions | K+T | P1 | Select text → highlight; reactions hartjie/duim op/wow. Encouragement, not critique. No public annotation. |
| 7.4 | Structured community responses | K+T | P1 | "Gemeenskapsreaksies": types Lof/Insig/Voorstel. Replaces WP comments. |
| 7.5 | Contextual prompts after a piece | K/T | P2 | Guided response prompts (may vary by content type). |
| 7.6 | Suggested next reads | K | P2 | By tone/form/topic/tier via taxonomy. |
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
| 8.5 | Discovery surfaces | K/T | P2 | "writers like this", new voices, recently active, writers in your tier, unread-by-you. (Custom, not default community screens.) |

---

## Epic 9 — Community & social

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 9.1 | BuddyPress scoped config | P | P0 | Profiles, Directory, Notifications **on**; Private Messaging **off at launch** (deferred — see 9.8, §14.7); Friend Connections, site-wide Activity, Groups, Blogs off. |
| 9.2 | **Follow graph (asymmetric)** | K | P0 | **[C-2026-06-14]** One-way follow in `ink-core`; follower/following counts; Volg/Volg tans UI. Replaces friendships. |
| 9.3 | **Following-feed** | K+T | P1 | **[C-2026-06-14]** Profile "Activity" tab = new publications by followed writers. Design exists (`Profile.tsx` Activity tab). |
| 9.4 | Custom profile templates | T | P1 | Block-theme BuddyPress templates (My Profiel + public Skrywerprofiel); tier, bio, stats, pinned works, accomplishments. |
| 9.5 | **Pinned / selected works** | K+T | P1 | **[C-2026-06-14]** Writer curates highlighted pieces on profile. |
| 9.6 | **Reader ratings & reviews** | K+T | P1 | **[C-2026-06-14]** Aggregate reader rating + written reviews on writer profiles. |
| 9.7 | Member directory (ledegids) | P/T | P1 | Writer discovery surface. |
| 9.8 | Private messaging | P+T | — | **Deferred — not in initial launch scope** (§14.7). BP Messaging off at launch; revisit later. |
| 9.9 | Notifications | P+T | P1 | @mentions, challenge announcements, follow/new-work alerts; "Merk alles as gelees". |
| 9.10 | Member online widget | — | — | **Removed** (CBX retired). Replace with engagement signals if any chrome remains. |

---

## Epic 10 — Library (Biblioteek)

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 10.1 | `biblioteek_item` archive + single | T | P1 | Featured strip + category filter + search + card grid (Library layout reference). |
| 10.2 | Date / archive browsing | K+T | P1 | **Gap** vs mockup — detail deferred, non-blocking (§9.4). |
| 10.3 | Pagination | T | P1 | **Gap** vs mockup — detail deferred, non-blocking (§9.4). |
| 10.4 | Author filter | K+T | P1 | **Gap** vs mockup — detail deferred, non-blocking (§9.4). |
| 10.5 | Winner ↔ challenge linkage | K | P2 | Winners link back to producing challenge via `uitdagingsronde` taxonomy (or relationship where modelled). |

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
| 12.4 | Entry capture | K | P1 | `inskrywing` linked to round via `uitdagingsronde`. |
| 12.5 | **Tier-based competition pools** | K | P1 | Brons vs Brons, Silwer vs Silwer, Goud vs Goud. Winners announced per tier. |
| 12.6 | Structured winner records | K | P0 (for admin) | Queryable winner data per tier; surfaces contextually ("Oktober Goud-wenner"). |
| 12.7 | Winner → tier promotion link | K | P1 | Optional link from promotion log to challenge result. |
| 12.8 | Historical challenge migration | K | P1 | Once-off DB update (§14.6): challenge categories → `uitdagingsronde` terms + an `uitdaging` record per round; preserves each piece's challenge linkage. Full brief/deadline only where old data exists. |

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
| 15.4 | Kontak | T+P | P1 | Form page (CF7 / Fluent Forms / `ink-core`). |
| 15.5 | Footer / social links | T | P1 | Theme-native pattern (replaces Ultimate Social Media Icons). |

*(Auth flows moved to Epic 3 — accounts are a foundational prerequisite, not an org-pages concern.)*

---

## Epic 16 — Migration & redirects

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 16.1 | DB clone & sanitise | — | P0 | Strip transients/logs; clean baseline. |
| 16.2 | User import + role reassignment | K | P0 | Reader/writer base roles; profile-field cleanup. |
| 16.3 | Tier CSV import | K | P0 | Email join key; defaults + flags for edge cases. |
| 16.4 | Subscription verification | P | P0 | Confirm memberships/plan IDs/access rules/expiry on new host. No import. |
| 16.5 | Post → CPT reclassification | K | P0 | Category-driven; `skryfwerk` catch-all (**do not hand-classify at volume** — holding bucket); flush rewrite rules. Old-site CPT disposition (§11): `inkpols`→`inkpols_uitgawe` rename; `monthly_challenge` placeholder **not** migrated 1:1 — `uitdaging` CPT records are built from challenge-round categories (16.8/12.8), real `monthly_challenge` data folded in, else dropped. |
| 16.6 | Library/training migration | K | P1 | By URL sub-path → CPT + taxonomy terms. |
| 16.7 | Redirect generation | K+P | P0 | 301s recorded during CPT migration; keep `/biblioteek/`,`/opleiding/` prefixes; verify by crawl. |
| 16.8 | InkPols / sponsors / nav | K/T | P1 | Per §11/§13/IA; nav rebuilt fresh. |
| 16.9 | BuddyPress data + friendship→follow | K | P1 | Convert each friendship → two mutual follows (§14.10); trim old activity; messaging deferred (§14.7). |
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
| 17.3 | Terminology reconciliation | — | ✅ Done 2026-06-14 | `afrikaans-terms.md` updated: follow vocabulary (`Volg`/`Volg tans`/`volgeling`; friendship terms replaced; follow-avoidance dropped); Storie code ID → `storie`; tier values → `brons`/`silwer`/`goud`. `ui-copy-translations.md` "Volgers" → "volgelinge" also done. |
| 17.4 | No-English-leakage QA gate | T | P1 | Gate D on every **front-end** template/pattern + user-facing emails. Scope includes the §12 leak vectors (status/validation messages, transactional emails, plugin JS strings, REST/AJAX/feeds), not just static template copy — pairs with the automated English-leak scan (18.8). Admin excluded — stays English (§14.14). Depends on the foundational i18n scaffolding (1.10). |

---

## Epic 18 — SEO, security & performance

| # | Feature | Layer | Pri | Notes / acceptance |
|---|---|---|---|---|
| 18.1 | Rank Math config + CPT schema | P | P1 | Adopted from the start. *Per-post* Yoast enrichment is negligible (owner's assessment: only a few InkPols OG images); global Yoast config not carried forward by design. **Deliberate override** of `plugin-transition-guide.md`'s "keep Yoast through migration" (§14.11). Sitemaps, meta, breadcrumbs, native schema for `gedig`/`storie`/`artikel`. Rank Math importer runs **as a safety net regardless**; verify InkPols images, then deactivate Yoast. Templated defaults, not per-post backfill. |
| 18.2 | Redirect integrity | P | P0 | All old URLs → correct 301; 404 tracking. |
| 18.3 | Security stack (layered) | P | P1 | **Resolved (§14.16):** Cloudflare (edge + login rule, origin locked) + staff 2FA + Patchstack (CVE alerts) + staging-gated updates (18.7) + **host-provided malware scanning**. Loginizer retired (Cloudflare covers login); no WordFence. Patchstack new. PayFast off-site → low PCI scope. |
| 18.4 | Moderation/report path | P/K | P1 | Report Content (translated) or `ink-core` form. |
| 18.5 | Caching layer | P | P1 | LiteSpeed Cache (NameHero runs LiteSpeed) + Cloudflare edge caching (§14.9). |
| 18.6 | Production hygiene | — | P0 | No dev/diagnostic/migration plugins active on production. |
| 18.7 | Update governance & i18n resilience | P/K | P1 | Gate major core/plugin updates via staging where possible (regression on custom templates + translation refresh). Rely on auto language packs for core/well-covered w.org plugins; committed `.mo` for premium plugins (Memberships/PayFast/Real3D/Report Content), re-checked after their updates. Run production-side detection for new untranslated strings (English-leak scan); author fixes on staging, commit, and redeploy — **Loco is not installed on production** (§14.13). No-English-leakage is a standing requirement. |
| 18.8 | Full test suite buildout | K | P1 | Decision: §14.17. **Builds on the Foundation-phase scaffold (1.11)** — extends it to the full pyramid: **unit** (Pest/PHPUnit + Brain Monkey/WP_Mock) for `ink-core` rules; **integration** (`wp-env` + WP test lib / wp-browser) for plugin seams (membership⇒submit, expired⇒denied, tier⇒meta+log); **E2E** (Playwright) for critical journeys (register→buy via PayFast sandbox→submit→publish→read→renew). CI per change + E2E smoke on staging deploy. Risk-based depth (smoke for minor, full for major). Includes automated English-leak scan. Concentrate in `ink-core`; theme via E2E/visual. |

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
| 1 Foundation | 7 | 11 |
| 2 Content models | 3 | 5 |
| 3 Accounts & auth | 2 | 3 |
| 4 Membership & payment | 4 | 8 |
| 5 Writer tiers | 3 | 6 |
| 6 Submission | 4 | 9 |
| 7 Reading & engagement | 1 | 8 |
| 8 Discovery | 1 | 5 |
| 9 Community & social | 2 | 10 |
| 10 Library | 0 | 5 |
| 11 Training | 0 | 5 |
| 12 Challenges | 1 | 8 |
| 13 InkPols | 0 | 4 |
| 14 Sponsors | 0 | 4 |
| 15 Org pages | 1 | 5 |
| 16 Migration | 6 | 12 |
| 17 Afrikaans-first | 1 | 4 |
| 18 SEO/security/perf | 2 | 8 |
