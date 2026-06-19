# INK Migration Plan

## Purpose

This document outlines what is involved in migrating the existing INK site data to the new platform. It covers every data domain, distinguishes between bulk-scriptable work and manual effort, and recommends an order of operations.

The new site introduces several structural differences from the current one:

- custom post types for stories (`storie`), poems (`gedig`), articles (`artikel`), and a catch-all `skryfwerk` type for unclassified content replacing the flat post model
- a dedicated custom post type for InkPols issues
- a dedicated custom post type for challenges and competition results
- a sponsor content type
- training and library content moved to purpose-built CPTs with taxonomy
- writer tier stored as user meta rather than a spreadsheet
- subscription data already in WooCommerce Memberships on the current site — it migrates with the database clone, no import script required

Most of the migration risk sits in the post-to-CPT reclassification, the tier spreadsheet import, and the redirect layer. Everything else is either straightforward scripted work or very low volume.

---

## Decisions captured

The following choices from the planning process are reflected in this document.

- Submission post types: yes, distinguish stories, poems, and articles as separate content types.
- Subscriptions: WooCommerce + WooCommerce Memberships, starting with fixed-term products (no auto-renew initially).
- Writer tiers: custom user meta (`ink_writer_tier`), promoted manually by staff.
- Challenges: site will help administer winners; challenge results should be queryable data, not just text.
- InkPols: stays PDF-based; Flipbook plugin stays; no individual article extraction needed.
- Community: BuddyPress as the underlying relationship engine; custom front-end UX on top.
- Library and training: custom post types with taxonomy, not an LMS.
- Sponsors: structured content type with scheduling.

---

## On the Youzify short-term question

The implementation options document flagged Youzify Frontend Submission as a possible bridge. The reason was phased delivery: if the new site needs to go live before custom submission is finished, Youzify is already there and could carry the load temporarily. Given that the submission requirements are modest — plain text, basic formatting, optional image and audio or video — a custom front-end form is actually straightforward to build and avoids the translation friction entirely. There is no strong reason to keep Youzify beyond the transition window unless timeline pressure makes a bridge necessary.

---

## Pre-migration decisions still needed

Before scripting begins, several classification decisions are required.

### 1. Classify existing posts

Writers already self-classify their work by assigning categories when they post. A poem submitted for the October challenge would be tagged with both `Gedig` and `Oktober 2025 Projek`. The content-type category (`Gedig`, `Verhaal`, `Artikel`) is the source of truth for CPT mapping.

**Mapping approach:**

- Posts with a recognised content-type category → map to the corresponding CPT (`gedig`, `storie`, `artikel`).
- Posts under `/biblioteek/` sub-paths → map to `biblioteek_item` CPT.
- Posts under `/opleiding/` sub-paths → map to `opleiding_artikel` CPT.
- Posts with no recognisable content-type category → assign to a catch-all CPT: `skryfwerk`. Do not attempt to classify these by hand if the volume is high.

The `skryfwerk` type acts as a holding bucket. It preserves the content and keeps it searchable without requiring editorial effort to classify every ambiguous post.

**Script approach:** build a migration script that reads each post's categories, maps to a CPT, then moves the post and records a redirect. Posts with conflicting or missing categories fall through to `skryfwerk` automatically.

### 2. Define new taxonomy structure

The current site uses WordPress categories and tags, but their structure reflects historical editorial decisions rather than a clean taxonomy. Before migration, define:

- what categories become in the new site (genre, form, challenge round, etc.)
- which existing tags are worth preserving
- what new training/library-specific taxonomies are needed

### 3. Confirm challenge data scope

Decide how much historical challenge data to model structurally versus leaving as-is in older posts. Options:

- Migrate all past challenge rounds to the new challenge CPT.
- Start the new challenge CPT from the launch date and leave historical challenge content as flat archive posts.
- Migrate only recent years.

The second option is the lowest-risk starting point.

### 4. Obtain the tier spreadsheet

The tier spreadsheet is required before scripting the tier import. Confirm the format and data quality:

- tier spreadsheet: user identifier (email or username), current tier, promotion history if available

Note: subscription data does not need a separate import. WooCommerce Memberships is already in active use on the current site — the site owner activates memberships manually in the WP admin after receiving EFT payment, and the plugin handles time-based expiry and access suspension. Membership records, start dates, and expiry dates are in the WordPress database and will carry across with the database clone.

---

## Migration by data domain

### Users and profiles

**Volume:** unknown, likely hundreds to low thousands.

**Approach:** existing WordPress users migrate automatically if the database is cloned. No scripting needed for the accounts themselves.

**What needs work:**

- BuddyPress extended profile fields may not map cleanly to the new profile model. Review which profile fields are worth keeping and which are noise.
- User roles may need resetting. Current roles likely include subscriber and potentially custom roles added by BuddyPress or Youzify.
- After import, assign a single member base role to each user — **no reader/writer distinction** (the signup intent gate was removed; any member can publish once subscribed — PRD FR-2). Drop legacy Youzify/BuddyPress custom roles.

**Manual vs scripted:** role reassignment and profile field cleanup can be scripted once the field mapping is confirmed.

---

### Writer tiers

**Volume:** number of writers currently in the tier spreadsheet.

**Source:** external spreadsheet.

**Target:** `ink_writer_tier` user meta on each WordPress user.

**Approach:** write a one-time import script or WP-CLI command that reads the spreadsheet (exported as CSV) and updates user meta using email address as the join key.

**Manual vs scripted:** scriptable once the CSV is clean and the user accounts exist.

**Edge cases:**

- Writers in the spreadsheet who do not have a WordPress account yet: flag for manual follow-up.
- Writers with ambiguous or missing tier data: default to `brons` (lowercase, canonical) and flag for review.
- Promotion history: if the spreadsheet contains promotion history, store it in a second meta key or a custom log table.

---

### Subscriptions

**Volume:** all active and historically recorded members in WooCommerce Memberships on the current site.

**Source:** WooCommerce Memberships records already in the WordPress database.

**Target:** same records in the new site.

**Approach:** subscription data migrates automatically with the database clone. No import script is required. WooCommerce Memberships is already in active use — the site owner activates memberships manually in the WP admin after receiving EFT payment, and the plugin handles time-based expiry and access suspension.

**What to verify post-migration:**

- Confirm all active memberships are still active after the database is moved to the new environment.
- Confirm that membership plan IDs and access rules survive the migration without remapping (they should, as the WooCommerce product and plan structure is part of the cloned database).
- **Manually verify**, before cutover, each active membership's state, plan ID, and expiry date (PRD MR-5). Cutover-boundary cases and expiry-cron/timezone reconciliation on the new host are tracked as a migration-build item (PRD §16 OQ-18) — not assumed to "just fire".

**Manual vs scripted:** verification only; no data import needed.

**PayFast note:** the PayFast gateway is installed but not currently used for front-end payment. Enabling PayFast so members can purchase directly is a new feature in the rebuild, not a migration task. It does not depend on any subscription data import.

---

### Posts: stories, poems, and articles

**Volume:** high. The archive contains years of content at 50–300 posts per month. Total estimated at several thousand posts.

**Current state:** all content is standard WordPress posts, mostly in categories like `biblioteek` and `opleiding`, or uncategorised.

**Target state:** three custom post types — `gedig`, `storie`, `artikel` — plus library and training CPTs.

**Approach:**

1. Audit current categories and tags to understand how well they distinguish content types.
2. If categories are reliable, write a migration script that reassigns posts to the appropriate CPT based on category.
3. If categories are unreliable, run a bulk-edit admin pass to classify manually, then script the CPT reassignment.
4. After CPT reassignment, flush rewrite rules and verify single post URLs.

**URL impact:** if the new CPTs use different permalink bases (e.g. `/gedig/` instead of the current URL structure), every moved post needs a redirect. See the Redirects section below.

**What to preserve from each post:**

- title
- content
- author
- publication date
- featured image
- comments
- existing categories and tags (map to new taxonomy)

**What can be dropped:**

- post format metadata that no longer applies
- Youzify FES form metadata
- WPBakery layout metadata
- any meta fields that are artefacts of the old theme or plugins

---

### Library content (`/biblioteek/`)

**Volume:** medium. Multiple pages of archive content, organised under project winner sub-paths.

**Current state:** standard WordPress posts under the `biblioteek` URL path. Sub-paths include `projek-wenners`, `wen-verhale`, `wen-gedigte`, and `prosa`.

**Target state:** a library CPT (e.g. `biblioteek_item`) with taxonomy for content type, challenge round, tier, and date.

**Approach:** scriptable based on URL patterns and categories once the taxonomy is defined. The URL structure already partially encodes the metadata needed (winner type, format).

**Special consideration:** winner posts should link back to the challenge that produced them. If historical challenges are modelled structurally, this relationship can be set during migration. If not, the link can be expressed as taxonomy rather than a post relationship.

---

### Training content (`/opleiding/`)

**Volume:** medium. At least 20 pages of archive, with articles going back to 2020.

**Current state:** standard WordPress posts under the `opleiding` URL path. Organised into sub-paths like `skryfkuns`, `taalgidse`, `woordeboeke`, `digkuns`.

**Target state:** a training CPT (e.g. `opleiding_artikel`) with taxonomy for skill area, format, and intended audience.

**Approach:** scriptable based on the existing URL sub-paths, which map reasonably well to skill area taxonomy. Each sub-path becomes a taxonomy term.

---

### InkPols

**Volume:** low. One issue per month if published regularly; back catalogue is small enough to handle manually.

**Current state:** `inkpols` custom post type with month/year meta, PDFs referenced by a fragile naming convention.

**Target state:** `inkpols_issue` CPT with structured metadata: issue date, volume number, cover image, PDF file, and teaser text.

**Approach:** migrate manually or with a small targeted script. The existing PDFs should be retained in the media library and re-linked to the new issue records. The old month/year naming convention can be replaced with proper date and volume metadata.

**Manual vs scripted:** low enough volume for a careful manual migration or a short targeted script.

---

### Challenges and competition results

**Volume:** one challenge per month going back several years. Results posts exist but are not structured data.

**Current state:** `monthly_challenge` custom post type (mostly a placeholder) plus editorial posts announcing results.

**Target state:** a challenge CPT that stores the challenge brief, deadline, theme, and then related winner records per tier.

**Approach:** decide on historical scope first (see Pre-migration decisions). For new challenges after launch, use the new CPT from day one. For historical data, either import recent years manually or leave historical challenges as flat archive posts under a legacy category.

---

### Sponsors

**Volume:** very low. The current site displays a small set of sponsor logos as static images.

**Target state:** a `sponsor` CPT with fields for name, logo, link, tier, campaign dates, and placement preferences.

**Approach:** manual entry. The volume is low enough that creating sponsor records one by one is faster and safer than scripting.

---

### Comments

**Volume:** unknown, but likely moderate given the site is writer-focused and readers have historically been passive.

**Approach:** comments migrate automatically with the posts they belong to. No special treatment needed.

**What to verify:** comment counts should match before and after migration.

---

### BuddyPress community data

**Volume:** varies by feature.

- Activity stream: potentially very large. Includes every post notification, comment, friendship event.
- Friendships: moderate.
- Private messages: low to moderate.
- Notifications: not worth migrating; these are ephemeral.

**Approach:**

- Activity stream: migrate if the new site uses BuddyPress activity. The volume can be trimmed by discarding activity older than a threshold (e.g. 2 years) unless there is a reason to preserve it.
- Friendships → follow: BuddyPress Friend Connections are **off** in the new site, so the cloned friend tables are **not** the live store. **Read** them and **transform** — convert each **confirmed** friendship into **two** one-way `volg` records (A→B and B→A), dedup duplicates, and skip edges to non-imported/flagged accounts; pending friend requests are not converted (PRD MR-8).
- Private messages: migrate with the database clone.
- Notifications: do not migrate; let them regenerate naturally.

**If Youzify is removed:** Youzify stores its own profile and social data in custom tables. Decide which Youzify-specific data is worth extracting before the plugin is deactivated.

---

### Media

**Volume:** high. Years of uploaded post images, audio files, video files, and PDF documents.

**Approach:** the `wp-content/uploads/` directory migrates as-is. Attachment post records in the database migrate with the database clone.

**What to review:**

- Youzify FES member upload directories contain member-uploaded images. These may need to be re-associated with the new submission model.
- InkPols PDFs should be verified as accessible under the new structure.
- Audio and video files should be confirmed as playable after migration.

---

### Navigation and menus

**Volume:** low.

**Approach:** manually rebuild navigation in the new block theme. The current menu structure reflects the old site's information architecture. The new menu should reflect the new IA, so copying it verbatim would be wrong.

What to preserve: any deeply linked nav items that readers may have bookmarked. Handle through redirects rather than menu copying.

---

### Settings and options

**Volume:** low.

**Approach:** do not clone the `wp_options` table wholesale. Set up the new site cleanly with deliberate settings. Only specific option values worth carrying forward should be moved individually:

- site URL and name
- confirmed Afrikaans locale setting
- (SEO is **not** carried from Yoast — it is configured fresh in **Rank Math**; Yoast is retired per PRD §9 / NFR-4)
- any legitimate plugin settings worth keeping

Avoid importing options from deactivated plugins, theme frameworks, or the old BuddyPress/Youzify configuration unless explicitly reviewed.

---

## Redirects

**This is mandatory. It must be planned before launch.**

Any post or page that moves to a new URL needs a redirect from the old URL. Given the volume of content and years of archive, the redirect layer is significant.

### URL patterns that will change

| Old pattern | Likely new pattern | Volume |
|---|---|---|
| `/[slug]/` (flat post) | `/gedig/[slug]/` or `/storie/[slug]/` etc. | Very high |
| `/biblioteek/[slug]/` | `/biblioteek/[slug]/` (may stay) | High |
| `/opleiding/[slug]/` | `/opleiding/[slug]/` (may stay) | Medium |
| `/biblioteek/projek-wenners/[tier]/[slug]/` | new library URL | Medium |
| InkPols post URLs | new InkPols issue URL | Low |
| Challenge post URLs | new challenge URL | Low |

**Recommendation:** keep the `/biblioteek/` and `/opleiding/` slug prefixes unchanged if possible. This preserves the high-value archive URLs and reduces redirect volume significantly.

For posts migrating to typed CPTs, generate redirects automatically as part of the migration script: for each post, record the old permalink before CPT reassignment and write a redirect rule after.

**Tool:** Redirection plugin or server-level rules depending on the hosting environment.

---

## Suggested migration order

Run in this sequence to minimise risk and dependency failures.

1. **Clone and sanitise the database.** Start with a clean copy of production data, stripped of transients and log data.
2. **Define new content types, taxonomies, and data models** in `ink-core`. Do not migrate content until the target structure is stable.
3. **Import users.** Baseline user accounts should exist before any other import.
4. **Import tier data** from the spreadsheet. Requires user accounts.
5. **Verify subscription data** in WooCommerce Memberships. The data migrates with the database clone; confirm active memberships, plan IDs, and access rules are intact.
6. **Classify existing posts.** Run the category/tag audit. Decide on automated vs manual classification. Produce a classification map.
7. **Migrate library and training content** to new CPTs. These have relatively clean URL sub-paths already and are lower-risk to script.
8. **Migrate general posts** (stories, poems, articles) to typed CPTs. Generate redirect rules during this step.
9. **Migrate InkPols.** Manually or via a short script.
10. **Enter sponsor records** manually.
11. **Rebuild navigation** in the new block theme.
12. **Verify redirects.** Crawl the old site URLs and confirm all redirects return 301 to the correct new destination.
13. **Verify media.** Confirm uploads are accessible, audio plays, PDFs open.
14. **Verify BuddyPress data.** Check friendships, activity stream, and profile fields.
15. **Smoke-test community and subscription flows** end to end.
16. **DNS cutover.** Only after all verifications pass.

---

## What can be scripted vs what must be done manually

### Scriptable

- User role reassignment after import
- Tier usermeta import from CSV
- Post reclassification to CPT based on category mapping (unclassifiable posts fall through to `skryfwerk` automatically)
- Library and training post migration based on URL path
- Redirect rule generation during CPT migration
- Taxonomy term remapping
- BuddyPress activity migration (database operation); **friendship→follow is a scripted transform, not a raw table clone** (read confirmed friendships → write two `volg` records — PRD MR-8)

### Must be done manually or needs editorial judgement

- Posts that cannot be reliably classified from categories or tags
- Sponsor records
- Navigation menus
- InkPols back catalogue (low volume, worth doing carefully)
- Historical challenge records (decide scope, then enter or script)
- Profile fields that do not have a clean mapping to the new model
- Any content that was entered inconsistently and needs editorial cleanup before it is worth migrating at all

### Should not be migrated

- Transients and cache data
- WPBakery layout metadata
- Youzify FES form builder records
- Plugin option rows for plugins that will not exist on the new site
- Activity notifications (let these regenerate naturally)
