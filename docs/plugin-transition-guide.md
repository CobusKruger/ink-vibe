# INK Plugin Transition Guide

**Site:** ink-staging → new INK platform
**Date:** June 2026
**Plugins surveyed:** 25 active, 8 inactive

---

## Guiding principles

Use plugins for commodity problems. Use `ink-core` for INK-specific rules. Keep the theme focused on presentation. Every plugin that survives should have a named reason tied to a business capability. Every plugin that doesn't survive should have a clear replacement or a confirmation that the capability is simply no longer needed.

---

## Quick reference

| Plugin | Status | Verdict | Notes |
|--------|--------|---------|-------|
| BuddyPress | Active | **Keep (scoped)** | Community engine; components below are individually configured |
| CBX User Online & Last Login | Active | **Retire** | No meaningful user goal |
| Classic Widgets | Active | **Retire** | Block theme removes the need |
| Code Snippets | Active | **Transition tool** | Migrate active snippets to `ink-core`, then retire |
| Comments Plus (Disable Comments) | Active | **Keep → consolidate** | Functional now; move to `ink-core` filter in a future phase |
| Contact Form 7 | Active | **Keep → review** | Works; consider Fluent Forms at theme build time |
| Invite Anyone | Active | **Conditional** | Keep only if BuddyPress Groups are confirmed in use |
| JoinUp Core | Active | **Retire** | Old theme companion; no role in a clean rebuild |
| WPBakery (js_composer) | Active | **Retire** | Block editor replaces it; DB shortcodes need a cleanup pass |
| Loginizer | Active | **Keep** | Login security is a must-preserve operational control |
| Qode Framework | Active | **Retire** | Old theme framework; retire with the old theme |
| Redirection | Active | **Keep** | Critical for migration redirect layer |
| Report Content | Active | **Keep (review)** | Content moderation is a must-preserve requirement |
| Simple CSS | Active | **Transition tool** | Migrate CSS to block theme, then retire |
| Ultimate Social Media Icons | Active | **Retire** | Replace with theme-native footer block pattern |
| WooCommerce | Active | **Keep** | Drives subscription purchases; memberships-only scope |
| WooCommerce Legacy REST API | Active | **Retire** | No known legacy API consumers |
| WooCommerce Memberships | Active | **Keep** | Already in active use; the rebuild adds front-end payment via PayFast |
| WooCommerce PayFast Gateway | Active | **Keep** | South African payment processor; no compelling replacement |
| Yoast SEO | Active | **Keep** | SEO and archive indexability requirement |
| WP Migrate Lite | Active | **Transition tool** | Migration use only; never leave active on production |
| WPCustom Category Image | Active | **Retire** | Native term images suffice in a block theme |
| WPS Bidouille | Active | **Retire** | French developer diagnostic tool; no production value |
| Youzify | Active | **Retire** | Profile skin; replaced by custom BuddyPress templates |
| Youzify Frontend Submission | Active | **Retire** | Replaced by custom submission form in `ink-core` |
| Loco Translate | Inactive | **Keep (reactivate)** | Essential for Afrikaans-first translation management |
| LocoAI for Loco Translate | Inactive | **Retire** | AI-generated Afrikaans is unacceptable for this site |
| Document Embedder | Inactive | **Retire** | No shortcodes in content; no role in new architecture |
| LiteSpeed Cache | Inactive | **Conditional** | Reinstate only if production host is a LiteSpeed server |
| Maintenance | Inactive | **Retire** | Replace with `.maintenance` file or Nginx rule |
| PDF Embedder | Inactive | **Retire** | No shortcodes in content; Real3D Flipbook covers InkPols |
| Real3D Flipbook | Inactive | **Keep (reactivate)** | InkPols stays PDF-based; this is the designated viewer |
| String Locator | Inactive | **Retire** | Developer IDE handles string search |

---

## Detailed entries

### BuddyPress — KEEP (scoped)

**Why it survives:** BuddyPress is the relationship and identity engine. The rebuild decision (implementation-options.md §4) is explicit: BuddyPress provides friendships, the member directory, and notifications. It is the infrastructure layer. Youzify was the UI skin sitting on top of it — Youzify goes, BuddyPress stays.

**Components to keep ON:**

- **Member Profiles (xprofile)** — base identity for every registered user; will receive custom block-theme front-end templates
- **Friend Connections** — the friendship mechanic is a core community feature
- **Member Directory** — essential for writer discovery surfaces
- **Notifications** — needed for @mentions, challenge announcements, and friend activity alerts
- **Private Messaging** — keep enabled; requires a usable custom front-end interface

**Components to turn OFF:**

- **Activity Streams (site-wide feed)** — INK is a literary publishing platform, not a social feed product. The global activity stream should be disabled. Per-member activity on a profile page may remain if it proves useful during design.
- **Groups** — no confirmed evidence that BuddyPress groups are in active use (site-structure-audit unknowns §3). Disable unless usage is confirmed before launch.
- **Blogs / Site Tracking** — content is modeled as custom post types; BP blog tracking is not needed.

**What changes in the rebuild:**

- The Youzify profile skin is removed entirely. Profile pages are custom block-theme templates that use BuddyPress template hooks.
- BuddyPress default pages (activity, members, groups) are either replaced with purpose-built equivalents or removed from the navigation.
- There is no "community site UX." Profile pages serve a literary publication identity, not a generic social network presentation.

**2026 reality check:** BuddyPress v14.4.0 has 100,000 active installs — low for a plugin of its age. Development is community-run and slow. The commercial alternative, BuddyBoss, is a polished closed-source fork but expensive and more opinionated. No free alternative covers friendships + directory + notifications + messaging in a single self-hosted package. The correct 2026 position is to keep BuddyPress as infrastructure and accept that its default UI will be entirely replaced. Treat it as a data and API layer, not a design system.

---

### CBX User Online & Last Login — RETIRE

**Why:** The members-online widget does not support a meaningful user goal on a literary platform. It is an admin/monitoring tool dressed up as a front-end widget. Implementation-options.md classifies it as a likely dud. The plugin-evaluation.md confirms low risk of removal: its custom database tables store session history but no page content or theme functionality depends on them. Remove the widget from any widget areas before uninstalling.

---

### Classic Widgets — RETIRE

**Why:** Classic Widgets restores the pre-block-editor widget screen. A block theme removes the need for this entirely. Widget areas that currently do structural work — sidebars, sponsor lists, archive links — are replaced by native block patterns and query blocks in the new theme. There is no role for Classic Widgets in a block-theme build.

---

### Code Snippets — TRANSITION TOOL

**Why it exists:** It holds PHP snippets that have drifted outside version-controlled code.

**Action required before retirement:**

1. Audit every active snippet in the plugin.
2. Snippets that encode business rules or INK-specific behavior (Afrikaans label overrides, role checks, submission logic) must be moved to `ink-core/includes/`.
3. Cosmetic tweaks and one-off admin utilities can be dropped.
4. Once all relevant snippets are in `ink-core`, deactivate and uninstall Code Snippets.

Do not retire this plugin until the audit and migration are confirmed complete.

---

### Comments Plus (Disable Comments) — KEEP, then consolidate

**Why it survives now:** Comments are globally disabled on the current site and the rebuild preserves this behavior. The reading experience uses custom contextual prompts instead of standard WordPress comments (implementation-options.md §5). This plugin enforces the global disable.

**2026 reality check:** The plugin is unnecessary overhead in 2026. Two filter lines in `ink-core` do the same job:

```php
add_filter( 'comments_open', '__return_false' );
add_filter( 'pings_open',    '__return_false' );
```

**Recommendation:** Keep the plugin through the initial migration to avoid any gap in comment suppression. Once `ink-core` is in place and the filters are confirmed working, deactivate and uninstall Comments Plus.

---

### Contact Form 7 — KEEP, then review

**Why it survives now:** It is used for contact and enquiry flows on the Oor INK section. The use case is standard and low-risk to keep.

**2026 reality check:** CF7 v6.1.6 has 10M+ active installs but its support resolution rate is 38% (28 of 74 tickets resolved in the last two months), which is a concern for a site that is Afrikaans-first and may need plugin string customisation. In 2026, Fluent Forms is the cleaner alternative — it is block-native, has a generous free tier, and handles INK's single contact form use case without the overhead.

**Recommendation:** Keep CF7 through the initial launch. At theme build time, evaluate replacing it with Fluent Forms or a small custom form in `ink-core`. Do not carry CF7 forward as a permanent fixture if a simpler path is available.

---

### Invite Anyone — CONDITIONAL

**Why:** Extends BuddyPress with member and group invitations. Its function depends entirely on whether BuddyPress Groups are enabled.

- If Groups are confirmed in active use and kept ON in BuddyPress: keep Invite Anyone.
- If Groups are disabled (the default recommendation above): Invite Anyone has no remaining function and should be retired alongside Groups.

Resolve this once the BuddyPress Groups question is answered before or during launch.

---

### JoinUp Core — RETIRE

**Why:** This is the companion plugin for the old JoinUp/ink-v2 theme. It registers a portfolio post type and theme-specific shortcodes that have no role in the new architecture. Everything in JoinUp Core that is product-critical must be identified and replaced in `ink-core` before this plugin is removed. The plugin itself has zero forward value once the old theme is gone.

---

### WPBakery Page Builder (js_composer) — RETIRE

**Why:** WPBakery was the layout engine for the old theme. A block theme uses the native block editor. There is no path for WPBakery in the new architecture.

**Migration note:** WPBakery stores its layout data as shortcodes embedded in post content, including `[vc_row]`, `[vc_column]`, and related tags. Before the old theme is deactivated, grep the content database for `[vc_` shortcodes. Decide whether to strip them, convert them to equivalent block markup, or leave them in place on content types that will not be displayed with the new theme. Do not let WPBakery shortcodes appear as raw text in rendered pages.

---

### Loginizer — KEEP

**Why:** Login security is a non-negotiable operational must-preserve requirement (site-structure-audit). Loginizer enforces rate limiting, IP blocking, and brute-force protection at the WordPress login layer. It has 1M+ active installs, was updated one month ago, and carries a 4.8/5 rating.

**2026 reality check:** If the production site is deployed behind Cloudflare, login brute-force protection is already handled at the edge under Cloudflare's free tier, making Loginizer redundant. If there is no Cloudflare layer, Loginizer is the right choice. Confirm the hosting and CDN setup before deciding whether to keep it on the production platform.

---

### Qode Framework — RETIRE

**Why:** The base framework plugin required by the old ink-v2/JoinUp theme. It has no role once the old theme is removed. Retire it at the same time as JoinUp Core and the old theme.

---

### Redirection — KEEP

**Why:** URL redirect management is a must-preserve operational requirement (site-structure-audit). The migration plan confirms that thousands of posts will move to new CPT-based permalink structures, each of which needs a redirect from its old URL. Redirection manages this load and provides logging, 404 tracking, and import/export.

**2026 reality check:** Redirection v5.7.5 has 2M+ active installs, is fully free with no premium tier, and was updated three months ago. Yoast SEO Premium includes a redirect manager — if INK ever upgrades to Yoast Premium, consolidation becomes possible. For now, Redirection is the correct dedicated tool.

---

### Report Content — KEEP (with review)

**Why:** Content moderation is a must-preserve requirement (site-structure-audit). The plugin has 27 active database references confirming it is in use. Community members need a report path for problematic content.

**Review required:** Verify whether the plugin's front-end strings can be translated into Afrikaans via Loco Translate. If they can, configure the translations as part of the Afrikaans-first setup pass. If the plugin resists translation or presents significant friction, replace it with a simple custom report form in `ink-core` — the underlying requirement is a logged report submission, not a specific plugin.

---

### Simple CSS — TRANSITION TOOL

**Why it exists:** It holds custom CSS that has accumulated outside the theme and outside version control.

**Action required before retirement:**

1. Export the current Simple CSS content.
2. Review each rule: keep structural or product-relevant styles; discard legacy or theme-override rules that no longer apply.
3. Move surviving styles into the block theme's `theme.json` design tokens, a dedicated stylesheet, or appropriate block styles.
4. Once the CSS is confirmed in the theme, deactivate and uninstall Simple CSS.

Do not retire this plugin until the CSS audit and theme migration are confirmed complete.

---

### Ultimate Social Media Icons — RETIRE

**Why:** Social media follow and share icons are a commodity UI element that belongs in the block theme as a footer block pattern or a set of hardcoded SVG links. A dedicated plugin is not justified for this. Remove it and build the social links directly into the theme footer.

---

### WooCommerce — KEEP

**Why:** The rebuild decision (implementation-options.md §3) is WooCommerce plus WooCommerce Memberships for subscription management. Three fixed-term products are already configured and in use:

- R60 / 1 month
- R300 / 6 months
- R600 / 12 months

Payment is currently collected manually via EFT, with the site owner activating each membership in the WP admin after confirming payment. The rebuild adds front-end payment via PayFast to remove the manual activation step — the membership management infrastructure is already in place.

**Scope constraint:** WooCommerce is present for subscriptions and membership access control only. There is no general store, no product catalog, and no cart or checkout experience beyond membership purchases. Any WooCommerce configuration that implies a general storefront should be suppressed.

**2026 reality check:** WooCommerce v10.8.1 is actively maintained by Automattic with 7M+ installs. For memberships-only use it is heavyweight. In 2026, MemberPress is the market leader in purpose-built membership plugins and has a PayFast integration. ProfilePress handles profiles plus memberships plus payments in a single plugin. The decision to keep WooCommerce is defensible because: subscription data and membership records are already in WooCommerce on the current site, the PayFast gateway already exists for it, and future commerce expansion — event tickets, merchandise, workshop fees — is straightforward from a WooCommerce base. If INK never expands beyond writer subscriptions, revisit this at the next major rebuild.

---

### WooCommerce Legacy REST API — RETIRE

**Why:** This plugin re-introduces pre-v9.0 WooCommerce REST API endpoints that were removed from WooCommerce core. The plugin-evaluation.md found no confirmed legacy API consumers in the current installation.

**Pre-retirement check:** Before deactivating, check server access logs for requests to `/wp-json/wc/v1`, `/wp-json/wc/v2`, or `/wp-json/wc/v3` in the legacy format. If no external integrations are found, remove the plugin. The rebuild introduces no legacy API consumers.

---

### WooCommerce Memberships — KEEP

**Why:** WooCommerce Memberships is already in active use on the current site. The site owner activates memberships manually in the WP admin after receiving EFT payment, and the plugin handles time-based access enforcement and suspension on expiry. The rebuild does not change how memberships are managed — it adds front-end payment via PayFast to remove the manual activation step.

**Critical configuration note:** Subscription status (active WooCommerce Membership) controls whether a user can submit work. Writer tier (`ink_writer_tier` user meta) is an entirely separate concept that controls Bronze/Silver/Gold competition pools. These two things must not be confused in configuration or in code. A paid subscriber at Bronze tier is not the same thing as a Bronze writer who happens to have an expired subscription.

---

### WooCommerce PayFast Gateway — KEEP

**Why:** PayFast is the South African payment processor for membership purchases. No alternative is proposed. The gateway is actively maintained and has the best WooCommerce integration available for ZAR-denominated transactions in South Africa.

**Recurring billing note:** Auto-renewal is deferred to a future phase (migration-plan.md). The initial launch uses fixed-term products without auto-renewal. Before enabling any auto-renew feature, confirm PayFast's recurring billing support and ensure the product configuration matches the billing model.

---

### Yoast SEO — KEEP

**Why:** Yoast provides XML sitemaps, meta management, structured data, and on-page SEO controls. Archive preservation and search indexability are must-preserve requirements (site-structure-audit). Yoast has years of SEO configuration data already in the database.

**2026 reality check:** Yoast v27.8 has 10M+ active installs and is updated continuously. However, Rank Math (4M+ installs, free tier) is now the developer-preferred alternative. Rank Math's free tier includes native schema for custom post types, breadcrumbs, and local SEO — all of which are premium-only in Yoast. For a CPT-heavy literary platform, Rank Math handles schema for `gedig`, `storie`, and `artikel` post types more cleanly out of the box, without needing premium extensions.

**Recommendation:** Keep Yoast through the migration. Switching SEO plugins mid-migration risks losing historical data and introduces unnecessary risk at the point where redirect integrity is most critical. After launch, at the first content audit, evaluate a migration to Rank Math. Yoast provides a built-in import/export tool and Rank Math can import Yoast data cleanly.

---

### WP Migrate Lite — TRANSITION TOOL (not on production)

**Why it exists:** A developer utility for exporting and importing the database with serialised-data find-and-replace support. Useful during the migration process.

**Security note:** Exposing a database migration tool on a live production site is an unnecessary attack surface. The plugin-evaluation.md flags this explicitly.

**Policy:** Install on the staging environment as needed for migration work. Uninstall immediately after each use. It must not be active on the production site at launch or at any point during normal operation.

---

### WPCustom Category Image — RETIRE

**Why:** Stores custom images against category terms. There are 11 assigned category images in the current installation. A block theme handles term images natively — either through `register_taxonomy` term meta with a featured image field, or through the block editor's taxonomy term editing interface. The 11 existing category images should be noted and manually reassigned within the new taxonomy model.

---

### WPS Bidouille — RETIRE

**Why:** A French WordPress developer diagnostic and optimisation utility. It adds an admin panel with system information but contributes nothing to the site's front-end or editorial workflows. The plugin-evaluation.md confirms it is safe to remove. No unnecessary admin tools should be left on production.

---

### Youzify — RETIRE

**Why:** Youzify is the profile skin and community UI layer sitting on top of BuddyPress. The rebuild decision (implementation-options.md §4) is to use BuddyPress as the underlying engine with custom block-theme templates on top. Youzify's opinionated profile UI, widget system, and translation complexity are exactly what is being replaced.

**What Youzify currently provides and what replaces each part:**

| Youzify feature | Replacement |
|-----------------|-------------|
| Profile page layout and skin | Custom block-theme BuddyPress template |
| Profile sidebar widgets (stats, recent posts, friends list) | Custom block patterns in the theme |
| Member badge and reputation display | `ink_writer_tier` rendered in the custom profile template |
| Activity timeline on profile | BuddyPress native activity, styled by the custom template |
| Admin bar community shortcuts | BuddyPress native + theme navigation |
| Members online widget | Retired with CBX User Online |
| Youzify-specific extended profile fields | Review: keep useful fields as BP xprofile fields; drop vanity fields |

**What is not replaced automatically:** Any extended profile fields created by Youzify — fields beyond the BuddyPress xprofile defaults — need an explicit decision. List every Youzify-specific field, evaluate whether it serves a real purpose on the new platform, and either migrate it to a clean BP xprofile field or drop it.

---

### Youzify Frontend Submission — RETIRE

**Why:** The rebuild decision (implementation-options.md §6, migration-plan.md) is a custom front-end submission form in `ink-core`. The submission requirements are modest: plain text with basic formatting, an optional featured image, and optional audio or video attachment. A purpose-built form avoids Youzify's translation friction and removes the theme coupling that currently overrides WordPress edit-link routing.

**Migration note:** The existing `functions.php` code that filters edit links to redirect to `/plaas-nuwe-publikasie?action=edit&post_id=...` was added specifically to support Youzify Frontend Submission. When this plugin is retired, that filter must also be removed from `functions.php` or, if it was migrated to `ink-core`, from `ink-core` itself.

**New post types to support:** The custom form in `ink-core` will serve three content types — `gedig` (poem), `storie` (story), and `artikel` (article) — each with appropriate field sets and validation.

---

### Loco Translate — KEEP (reactivate)

**Why:** The site is Afrikaans-first. Loco Translate is the standard in-admin tool for editing `.po` and `.mo` translation files on active plugins and themes without leaving WordPress admin. It is currently inactive and should be reactivated as part of the build setup.

**Scope of use:** Every plugin that survives the transition and presents user-facing strings should have an Afrikaans translation pass verified or authored using Loco Translate. This includes BuddyPress, WooCommerce, WooCommerce Memberships, Redirection, Report Content, and any other plugin with visible front-end text.

**2026 reality check:** Loco Translate v2.8.5 has 1M+ active installs, a 4.8/5 rating, and was updated one week ago. There is no meaningful alternative for in-admin WordPress translation editing. Keep.

---

### LocoAI – Auto Translate for Loco Translate — RETIRE

**Why:** AI auto-translation of plugin strings is not acceptable for a site where Afrikaans quality is a core product requirement. Afrikaans translations must be deliberately authored by a native speaker or reviewed by one. Machine-generated strings carry tone and vocabulary errors that would be embarrassing on a platform dedicated to Afrikaans literary culture. Remove this add-on and do not replace it with any equivalent.

---

### Document Embedder — RETIRE

**Why:** Inactive. The plugin-evaluation.md confirmed no shortcodes from this plugin appear in any post or page content. There is no role for it in the new architecture.

---

### LiteSpeed Cache — CONDITIONAL

**Why it is inactive:** The current site runs on a Local development environment that does not use a LiteSpeed server. LiteSpeed Cache cannot function without a LiteSpeed-compatible host.

**Decision depends on hosting:** If the production host uses LiteSpeed, reactivate and configure LiteSpeed Cache as the caching and performance layer. If the production host does not use LiteSpeed, use an alternative: host-level caching provided by the hosting plan, WP Super Cache, or W3 Total Cache. Confirm the production hosting environment before making a final decision.

---

### Maintenance — RETIRE

**Why:** A convenience plugin for putting the site into maintenance mode. The same result is achievable without a plugin by placing a `maintenance.php` file in the WordPress root or by returning an HTTP 503 via Nginx configuration. Fewer plugins on production is better. If a quick maintenance mode capability is needed during the launch period, add it as a one-line Nginx rule rather than reinstalling the plugin.

---

### PDF Embedder — RETIRE

**Why:** Inactive. The plugin-evaluation.md confirmed no shortcodes from this plugin appear in any content. InkPols uses Real3D Flipbook for the PDF viewing experience. There is no need for a second PDF embed plugin.

---

### Real3D Flipbook — KEEP (reactivate)

**Why:** The migration plan decision is explicit: InkPols stays PDF-based and the Flipbook plugin stays. Real3D Flipbook is the designated viewer for InkPols publication artifacts. Reactivate it and confirm that the existing InkPols configuration and file paths still work before launch.

**2026 reality check:** The flipbook viewer pattern is dated. Modern alternatives include PDF.js-based embeds or native browser PDF rendering via a full-width iframe. The plugin approach adds frontend JavaScript weight that a simple PDF embed would not. However, InkPols is not a priority redesign item in the current rebuild scope, and the plugin works. Reactivate it now. When InkPols eventually receives a dedicated content model redesign, revisit whether the flipbook presentation is still the right experience or whether a cleaner PDF viewer is better.

---

### String Locator — RETIRE

**Why:** An inactive developer tool that searches plugin and theme source files for text strings from within the WordPress admin. Developers working on the codebase use their IDE for this. The plugin adds no front-end functionality and leaves no meaningful data in the database. Do not leave developer tools on the production site.

---

## Summary counts

| Verdict | Count |
|---------|-------|
| Keep | 9 |
| Keep → consolidate into `ink-core` later | 1 (Comments Plus) |
| Keep → review for replacement | 2 (Contact Form 7, Report Content) |
| Keep (reactivate) | 2 (Real3D Flipbook, Loco Translate) |
| Conditional | 2 (Invite Anyone, LiteSpeed Cache) |
| Transition tool — not on production | 3 (Code Snippets, Simple CSS, WP Migrate Lite) |
| Retire | 14 |
| **Total** | **33** |
