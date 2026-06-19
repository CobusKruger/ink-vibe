# INK PRD — Addendum

*Technical depth, mechanism decisions, and rationale that the PRD references but does not contain (the PRD states capability-level *what/why*; this holds *how* and *why-not*). Feeds downstream architecture / solution-design / UX work. Source of record for the detail: `_bmad-output/project-context.md`, `docs/specs/ink-consolidated-spec.md`, `docs/migration-plan.md`, `docs/plugin-transition-guide.md`.*

---

## A. Three-layer architecture (mechanism)

| Layer | Artifact | Contents | Repo path |
|---|---|---|---|
| Presentation | `ink-foundation` (block theme, FSE) | `theme.json`, templates, template-parts, patterns, block styles. **Presentation only.** | `wp-content/themes/ink-foundation/` |
| Business logic | `ink-core` (plugin) | CPTs, taxonomies, user meta, tier/challenge/submission/follow/engagement logic, REST, admin tools. | `wp-content/plugins/ink-core/` *(to be created)* |
| Commodity | vetted platform plugins | BuddyPress, WooCommerce (+ Memberships, PayFast), Real3D, Rank Math, Redirection, LiteSpeed, Patchstack. | — |

- Build targets: WordPress 7.0+, PHP 8.3+ (`enum`, `readonly`, constructor promotion, typed constants, `#[\Override]`, first-class callables, named args; `declare(strict_types=1)` in `ink-core`).
- Prefix everything `ink_` / `Ink\`. Fixed value sets modelled as `enum`s in `ink-core` (tier, response type, reaction) — the string is the persisted DB value, never duplicated as a literal. *(No `intent` enum — FR-2 removed the signup intent gate.)*
- Escape-on-output / sanitise-on-input / nonces everywhere; `$wpdb->prepare()` always; custom tables via `dbDelta()` with `$wpdb->prefix` (follow graph, tier-promotion log).
- Integrate with plugins via hooks/filters/template functions only — never edit plugin files or assume internals.

## B. THE conflation rule — enforcement mechanism

- Entitlement is read from WooCommerce Memberships' active-membership API; tier is read from `ink_writer_tier` user meta. No function derives one from the other.
- Unit tests assert independence: changing membership state leaves `ink_writer_tier` untouched; promoting a tier does not alter membership; an expired Goud writer is denied `plaas`.
- Integration seams to cover: *active membership ⇒ can submit*; *expired ⇒ denied*; *tier write ⇒ meta + log*.

## C. Lovable → WordPress translation (mechanism)

Lovable (React + Tailwind + shadcn/ui) is **design intent, not code**. Translation rules:
- Tailwind classes → `theme.json` tokens + block styles (`theme.json` naming is canonical even where Lovable names differ).
- shadcn primitives → core blocks + style variants.
- Client interactivity → Interactivity API or small enqueued JS; **business logic stays in `ink-core`**.
- `react-router` routes → WP templates/permalinks.
- Mock data / `localStorage` → CPTs/meta/migrated DB.
- Never emit JSX, copy Tailwind classes, lift English placeholder copy, or treat mock data as the data model.
- Reference `.tsx` components in the feature list (PoetryReader, Write, Challenge, Profile) point to the design source, not an implementation target.

## D. i18n leak-vector mechanism (why NFR-1 is a standing gate)

- Front end + transactional emails Afrikaans; admin chrome English by decision; **`ink-core` admin labels Afrikaans** via mechanism: author admin strings in Afrikaans as source language and ship **no English `.mo`**, so gettext returns the Afrikaans source even under a staff member's English admin locale.
- Site locale `af`; staff forced to English admin language per-user.
- Irreducible exposure = premium/niche plugins with no community language packs (WooCommerce Memberships, PayFast gateway, Real3D, Report Content) → committed `.mo` is the only defence; re-check after their updates.
- Leak vectors beyond templates: validation/status/error messages, plugin-composed sentences (BuddyPress notifications, Woo order/membership phrasing), transactional emails (Woo order/renewal/expiry, BP, password reset), plugin **JavaScript** strings (separate JS `.json` translations — e.g. Real3D viewer controls), out-of-band outputs (REST/AJAX, redirect-notice query args, feeds).
- Translation workflow: author `.po/.mo` on staging with Loco → commit to version control → production loads from `wp-content/languages/` **without Loco present**. New strings from ungated updates caught by the automated English-leak scan, then authored on staging, committed, redeployed — never hand-edited on production.

## E. Migration scripting order (binding sequence, detail)

Clean DB clone → define CPTs/taxonomies in `ink-core` → users → tiers (CSV → `ink_writer_tier`, default `brons` + flag if missing) → verify subscriptions (no import; ride the clone) → classify posts (categories → CPTs; unclassifiable → `skryfwerk`, no hand-classify at volume) → library/training (keep `/biblioteek/` `/opleiding/` prefixes) → posts + 301 redirects (record old permalink before reassignment) → InkPols → sponsors → rebuild nav → verify redirects/media/BP → smoke-test → DNS cutover.

- Friendships → follow: each **confirmed** BuddyPress friendship becomes **two** mutual follow records (dedup duplicates; skip edges to non-imported accounts; pending requests not converted).
- Don't clone `wp_options` wholesale — carry only deliberate values (site URL/name, `af` locale; SEO set up fresh in Rank Math).
- Youzify removal: extract custom-table profile/social + FES upload data **before** deactivation; re-associate uploads with the new submission model.
- Old BuddyPress activity may be large (consider trimming >2yr); notifications not migrated (regenerate naturally).
- Comments migrate with posts; verify counts before/after.

## F. Plugin decisions & rejected alternatives (rationale)

**Kept (commodity):** BuddyPress (scoped: profiles/directory/notifications only), WooCommerce + Memberships, WooCommerce PayFast Gateway (ZAR), Real3D Flipbook, Rank Math, Redirection, LiteSpeed Cache, Patchstack.

**Retired / rejected (do not reactivate), with reason:**
- **Youzify** → replaced by custom `ink-core` front-end submission + block-theme profiles (business logic must leave the theme/plugin glue).
- **Yoast** → **Rank Math** (consolidated SEO + native CPT schema; verify InkPols OG images before retiring Yoast).
- **WordFence / Loginizer** → layered security via Cloudflare (edge + login rule, origin locked) + staff 2FA + Patchstack + host malware scanning (avoid heavy WAF/plugin sprawl; PayFast off-site keeps PCI scope low).
- **WPBakery/Qode stack** → FSE block patterns (grep `[vc_*]`; strip/convert; none render as raw text).
- **Comments Plus / WP comments** → custom **Gemeenskapsreaksies** (Lof/Insig/Voorstel); comments disabled site-wide.
- **CBX online widget** → removed.
- **WPCustom Category Image** → native term images (reassign 11 existing images).
- **Ultimate Social Media Icons** → theme-native footer/social pattern.
- **PDF Embedder / Invite Anyone** → retired.

**Resolved 2026-06-15 (OQ-4 / OQ-8):** a custom `ink-core` form for **both** the moderation report path and the Kontak form — no Report Content / CF7 / Fluent Forms dependency. Public-form spam / rate-limit / deliverability / validation hardening is a build-time item (§16 OQ-18).

## G. Test harness detail

- Pyramid concentrated in `ink-core`; theme covered by E2E/visual, not unit tests.
- Unit: Pest/PHPUnit + Brain Monkey/WP_Mock — tier promotion, entitlement gate, sponsor scheduling, follow graph.
- Integration: wp-env / wp-browser — membership⇒submit, expired⇒denied, tier⇒meta+log.
- E2E: Playwright + `@wordpress/e2e-test-utils-playwright` — register → buy via **PayFast sandbox** → submit → publish → read/react → renewal/expiry.
- CI per change + staging E2E smoke; automated English-leak scan as a standing gate (re-runs after ungated core/plugin updates). Risk-based depth: smoke for minor/security, full regression for majors. **Never hit live PayFast.**
- `ink-core` rules ship test-first — the harness is Epic 1 (foundational), not deferred.

## H. Pre-launch verification checklist (carried from spec §14 open items)

- Confirm NameHero plan tier runs LSWS (LiteSpeed).
- Verify Real3D file paths/config; verify InkPols OG images before retiring Yoast.
- Verify migrated subscriptions (plan IDs, access rules, expiry/suspension fire on new host).
- Verify media — uploads, audio/video playback, PDFs open.
- Verify redirects by crawl (old URLs → 301).
- Replace org placeholders (`[stigtingsjaar]`, `[regstatus]`, copyright year, live stats) and generated sample content.
