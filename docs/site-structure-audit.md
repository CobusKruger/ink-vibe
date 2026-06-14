# INK Site Structure Audit

## Purpose of the current site

The existing site is not just a blog. It is a hybrid WordPress platform with five overlapping jobs:

1. Public publishing platform for Afrikaans writing.
2. Paid member publishing workflow for authors and poets.
3. Community layer with profiles, activity, messaging, notifications, and member discovery.
4. Free learning/resource hub with training material and library-style content.
5. Editorial and project administration for challenges, winners, announcements, sponsors, and organization pages.

That matters because a replacement should be modeled as a platform with distinct product areas, not as a single homepage with everything mixed together.

## High-confidence structural map

### 1. Public marketing and information pages

These are the public-facing organization pages that explain what INK is and how people join.

- Homepage: currently a static front page (`show_on_front = page`) with a heavy mix of welcome copy, featured content, quick links, sidebars, sponsor areas, archive links, and calls to register.
- About/contact/joining pages: pages such as `lees-meer-oor-ink`, `kontak-ons`, and `aansluitingsopsies` act as informational and conversion pages.
- Sponsors and partner links: visible in footer/sidebar areas and on content pages.

### 2. Main publishing system

This appears to be the real center of the site.

- Standard WordPress posts are the primary publishing model.
- Public readers can browse large volumes of writing by date/archive.
- Logged-in users can comment and interact.
- Paid members can publish work, with a stated posting limit of 3 posts per day.
- Content is heavily archive-driven, with monthly archive pages going back many years.

Evidence:

- The rendered site shows large archive lists and recent-post streams.
- The membership options page explicitly ties paid membership to the right to publish.
- Theme code customizes the front-end post editing flow for member submissions.

### 3. Community and membership layer

This is implemented primarily through BuddyPress plus Youzify.

- BuddyPress provides member profiles, activity streams, notifications, messaging, and other social/community features.
- Youzify sits on top of BuddyPress as the profile/community experience layer.
- Invite Anyone extends BuddyPress with member and group invitations.
- CBX User Online powers the "members online" style widgets and last-login tracking.
- Loginizer hardens login by rate-limiting/brute-force protection and IP controls. It is operational security, not a core membership feature.

This means the current "membership system" is actually two separate concerns:

- Identity/community: BuddyPress + Youzify.
- Access/payment rules: WooCommerce + WooCommerce Memberships + PayFast.

### 4. Subscription and access model

This is more important than it first looked, and also more manual than it looks.

**Published subscription options** (from `/aansluitingsopsies/`):

- Visitor: free reading only.
- Free registered profile: can comment, access library and training.
- Paid subscription: unlocks publishing rights, with a limit of 3 posts per day per member.
  - R60 per month
  - R300 for 6 months
  - R600 for 12 months

**How subscriptions are actually managed:**

- Payment is collected manually via EFT to a FNB business account.
- Members email their proof of payment.
- The site owner activates each membership manually in the WordPress admin via WooCommerce Memberships.
- WooCommerce Memberships then handles time-based enforcement: access is automatically suspended when the membership expires.
- WooCommerce PayFast Gateway is installed but not in active use — there is no payment collection UI on the site front-end.

The current product logic appears to be:

- reading is mostly public
- free registration unlocks some community and training/library access
- paid subscription (manually activated by admin after EFT confirmation) unlocks publishing rights for a time-limited period

**Important replacement implication:** The gap in the current workflow is purely on the payment side. WooCommerce Memberships is already handling subscription tracking and access enforcement correctly. A rebuild that enables front-end payment via PayFast closes the loop — members can purchase and activate their own membership without admin involvement.

### 5. Library and training sections

These are real content sections, not just one-off pages.

- `/biblioteek/` behaves like a large archive/resource hub.
- `/opleiding/` behaves like a structured training article hub.
- Both sections appear to be built on ordinary WordPress content and taxonomy/permalink structure rather than a clean, custom learning system.
- Free registered members are explicitly told they get access to these sections.

Observed examples in training:

- writing craft
- language guides
- dictionaries
- poetry guidance
- general writing advice

Observed examples in library:

- project winners
- prose and poetry collections
- historical archive content

This suggests the replacement should treat these as first-class knowledge/resource products with clearer taxonomy, filtering, and access rules.

### 6. Writer tier system (Bronze, Silver, Gold)

This is a central game/progression mechanic but it has no code implementation.

- All writers start at **Bronze**.
- Winning a competition at your current tier advances you to the next: Bronze → Silver → Gold.
- Competitions are run within tiers: Bronze writers compete against Bronze writers, Silver against Silver, and so on.
- Winners are announced publicly per tier.
- Tier membership is tracked entirely in a spreadsheet outside WordPress.
- There is no usermeta, options row, plugin, or theme code in the current installation that stores or enforces tier level.

This is one of the most important gaps between the current site and a proper replacement. The tier system is visible to readers in post labels (e.g. "Goud", "Brons" appear in post titles and content), but it is not modeled as data anywhere on the platform.

### 7. Editorial projects and competitions

There is a recurring project/challenge workflow across the site.

- Monthly or themed challenges are a real content domain.
- Winners and results are published as a recurring editorial pattern.
- Project pages, results, and featured entries appear throughout the public site and library.

Theme-defined custom post types:

- `monthly_challenge`
- `inkpols`

These are the only custom post types defined by the theme itself that look site-specific.

### 8. Inkpols section

`inkpols` is a custom post type with month/year metadata.

- The template `page-inkpols.php` assembles PDF and share-image paths from post meta.
- This is a fragile implementation because file naming and template output are tightly coupled to month/year values and theme paths.
- The section likely represents a magazine/newsletter/publication artifact rather than standard article content.

This should probably become a proper publication/document model in the replacement.

### 9. Monthly challenge section

`monthly_challenge` is also a custom post type with month/year metadata.

- The custom type exists.
- The current custom page template is mostly a placeholder.
- The concept is clearly important, but the implementation is thin and incomplete.

This is a sign that business importance and technical implementation are out of sync.

### 10. Front-end author submission workflow

This is an important operational feature.

- Youzify Frontend Submission allows members to submit posts from the front end.
- The theme adds a custom filter so edit links route to `/plaas-nuwe-publikasie?action=edit&post_id=...`.
- This means the author workflow is not just plugin-default behavior; it has theme-specific coupling.

If the replacement keeps front-end publishing, this workflow needs to be explicitly redesigned rather than copied blindly.

### 11. Shared chrome and widget-heavy layout

The current site relies heavily on global sidebars and widget regions.

Common recurring blocks seen across content areas:

- members online
- recent contributions
- sponsors
- archive lists
- external links
- calls to join

The theme registers sidebar and footer widget areas, and the rendered pages show they are doing a lot of structural work. This is one reason the site feels cluttered: global widgets compete with primary content almost everywhere.

## Active plugin picture by responsibility

### Core community and member UX

- BuddyPress
- Youzify
- Invite Anyone
- CBX User Online & Last Login

### Publishing and author workflow

- Youzify Frontend Submission
- PDF Embedder
- Real3D Flipbook Lite
- Document Emberdder

### Commerce and access control

- WooCommerce
- WooCommerce Memberships
- WooCommerce PayFast Gateway

### Theme/framework dependencies

- JoinUp Core
- Qode Framework
- WPBakery (`js_composer`)

### Security and operational controls

- Loginizer
- Report Content

### SEO, redirects, translation, and admin tooling

- Yoast SEO
- Redirect Redirection
- Loco Translate
- Simple CSS
- Code Snippets
- String Locator
- WP Migrate DB

### Likely non-core or support-only plugins

- Classic Widgets
- Webcraftic Disable Comments (`comments-plus`)
- Contact Form 7
- Automatic Translator Addon for Loco Translate
- category image helper plugin

The key planning point is that not every active plugin should survive the rebuild. Some are product-critical, some are implementation baggage, and some only exist because the current theme is doing too much.

## Theme and code dependencies that matter

### Theme identity

- Active theme folder: `ink-v2`
- Theme metadata still identifies it as JoinUp, a BuddyPress community theme.

This confirms `ink-v2` is a customized/renamed derivative rather than a clean original build.

### Important theme couplings

- Afrikaans comment-label customization in `functions.php`
- front-end submission edit-link override in `functions.php`
- custom stylesheets for form/search overrides
- custom post type registration for `monthly_challenge` and `inkpols`
- custom page templates for project-specific content

The practical meaning: several business-critical behaviors currently live in theme code where they do not belong long-term.

## Language and localization

- The site is explicitly Afrikaans-first.
- SQL options confirm `WPLANG = af`.
- Theme code contains Afrikaans UI customization.
- Loco Translate is active and the site contains Afrikaans translation files.

The replacement should treat Afrikaans as a first-class product requirement, not as a later translation pass.

## SEO and content architecture concerns

Current SEO and information-architecture weaknesses are structural, not cosmetic.

- The homepage mixes too many competing content types.
- Key content domains are not clearly separated in navigation.
- Library and training appear content-rich but structurally under-modeled.
- Widget-heavy sidebars dilute page intent.
- Theme/framework dependence makes content presentation brittle.
- Some important sections appear to rely on post conventions, slugs, and templates instead of explicit content models.

This means the replacement should start from content architecture and user journeys before any visual redesign.

## What absolutely needs to be preserved in the replacement

### Must-preserve business capabilities

1. Public reading of published writing.
2. Member profiles and community identity.
3. Front-end or otherwise simplified author publishing workflow.
4. A distinction between free access and paid publishing rights.
5. Library and training/resource access.
6. Editorial/project/challenge management.
7. Afrikaans-first UI and editorial tone.
8. Bronze/Silver/Gold tier progression tied to competition wins.
9. Tier-based competition pools (writers only compete within their tier).

### Must-preserve content domains

1. General posts and archive history.
2. Training articles.
3. Library/resource collections.
4. Challenge/project announcements and results.
5. Inkpols/publication artifacts.
6. Organization/about/contact/join flows.

### Must-preserve operational concerns

1. Login abuse protection.
2. Payment workflow and membership activation rules.
3. Reporting/moderation path for problematic content.
4. Redirect strategy for old URLs.
5. Search/indexability and archive preservation.

## What should probably not be carried forward as-is

1. The current homepage composition.
2. Theme-owned business logic.
3. Widget-heavy global sidebars on every content surface.
4. Content models that depend on implicit slugs, month/year file naming, or manual editor discipline.
5. Framework/plugin sprawl where a cleaner native WordPress or custom implementation would do.
6. Manual EFT-based payment collection with no front-end payment option.
7. Manual tier tracking via spreadsheet.

## Recommended replacement information architecture

The replacement should likely be designed as these top-level product areas:

1. Home
   A clean editorial homepage with only a few featured streams.
2. Lees / Publikasies
   The main reading and archive experience for public writing.
3. Opleiding
   Structured learning content with categories and search.
4. Biblioteek
   Curated resources, winners, reference material, and document-style collections.
5. Projekte / Uitdagings
   Monthly challenges, competitions, themes, rules, results, and winners — structured around tiers.
6. Gemeenskap
   Profiles, member directory, activity, notifications, and social/community actions.
   Member profiles should visibly display current tier (Bronze/Silver/Gold).
7. Lidmaatskap
   Registration, free-vs-paid benefits, automated payment, renewal, and publishing entitlements.
   Should replace the current manual EFT payment step by enabling front-end purchases via PayFast. Membership tracking and access enforcement are already handled by WooCommerce Memberships.
8. Oor INK
   Mission, contact, sponsors, partners, and organizational pages.

This separation would reflect the actual business better than the current site does.

## Unknowns that still need confirmation before a rebuild

1. The exact data model behind access restrictions for library/training content (currently assumed to be open to free registered users based on membership page copy, but not confirmed in code).
2. WooCommerce Memberships is installed but not driving live subscriptions. Clarify whether it was ever used, partially configured, or is a placeholder for a planned system.
3. Whether any BuddyPress groups are heavily used in practice.
4. Whether direct messaging/notifications are central or incidental to real user behavior.
5. The complete menu structure and any hidden or staff-only admin flows.
6. Whether `document_library` and `r3d` are actively used in production or are legacy tooling.
7. The exact migration rules needed for legacy URLs, taxonomies, and archives.
8. What data needs to be migrated from the tier spreadsheet into the new system — subscription data is already in WooCommerce Memberships and will carry across with the database clone.

## Bottom line

The current INK site is best understood as a community publishing platform with paid publishing rights, free educational resources, and a layer of editorial challenge/project content. Its biggest weakness is not missing functionality. Its biggest weakness is that too many different functions are collapsed into one theme, one homepage, and one cluttered presentation model.

The replacement should separate product areas clearly, keep the business rules intact, and move critical behaviors out of theme glue into a deliberate content and membership architecture.
