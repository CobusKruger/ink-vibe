# INK Implementation Options

## Purpose

This document expands on implementation strategies for the new INK site. It is not a plugin shopping list. The aim is to identify where a plugin is justified, where a custom implementation is the better long-term choice, and where the current stack should be retired.

The guiding principle should be:

- use plugins for commodity problems
- use custom code for INK-specific rules
- keep the theme focused on presentation

For this project, "commodity problems" means things like payments, SEO, redirects, and baseline community infrastructure. "INK-specific rules" means writer tiers, challenge promotion logic, tailored reading nudges, and the exact publishing/onboarding workflow.

## Current plugin triage

### Likely worth keeping only if deliberately adopted

- BuddyPress
  Useful as the underlying community engine if you want friendships, activity, directories, and a base member model.
- WooCommerce
  Worth keeping if subscriptions, renewals, invoices, or future paid products will be handled in-platform.
- WooCommerce Memberships
  Worth keeping if you stay on WooCommerce for access control.
- WooCommerce PayFast Gateway
  Worth keeping if PayFast remains the payment processor.
- Yoast SEO
  Perfectly serviceable if the team is comfortable with it.
- Loco Translate
  Useful as an admin translation utility, but it should not be the main strategy for Afrikaans-first design.

### Useful purpose, but likely should be replaced

- Youzify
  It solves profile/community UI, but it adds a lot of opinionated surface area and translation friction. A block theme plus BuddyPress templates is a cleaner long-term direction.
- Youzify Frontend Submission
  It proves the need for front-end submission, but it is not necessarily the best long-term workflow tool.
- PDF Embedder / Real3D Flipbook Lite / Document Emberdder
  These solve document display in plugin-heavy ways. InkPols needs a cleaner publication model instead of stacked viewer plugins.
- Redirect plugin
  Redirects matter, but you should standardize on one trusted redirect strategy, either a well-supported plugin or server-level rules.
- Simple CSS / Code Snippets
  These are often signs that site behavior drifted outside version-controlled code.

### Likely duds or removable by default

- CBX User Online & Last Login
  The "members online" widget does not appear to support a meaningful user goal.
- Classic Widgets
  A block theme should remove the need for it.
- WPBakery / `js_composer`
  This does not fit a modern block-theme direction.
- JoinUp Core / Qode Framework
  These are part of the old theme stack and should not define the new architecture.
- Automatic Translator Addon for Loco Translate
  Convenient, but not a foundation.
- production reliance on String Locator, ad hoc snippet plugins, and admin patching tools
  Handy during transition, but not part of the target architecture.

## Recommended architecture shape

The cleanest architecture is:

- a modern custom block theme for presentation
- one custom site plugin, for example `ink-core`, for business rules and content models
- a small number of carefully chosen plugins for community, commerce, SEO, forms, and search

That gives you three clear layers:

1. Theme layer
   Templates, patterns, styles, blocks, interaction design.
2. Site logic layer
   Writer tiers, challenge rules, InkPols data model, submission workflow glue, sponsor rotation logic.
3. Platform layer
   Authentication, commerce, SEO, redirects, search, and possibly community primitives.

## 1. Membership and authentication

### What this should mean

This is the base identity system:

- registration
- login
- password reset
- account settings
- profile basics
- role assignment
- moderation and account status

It should not be confused with writer tiers or paid subscriptions. Those are separate concerns.

### Good options

#### Option A: WordPress core auth plus custom onboarding

Use WordPress itself for authentication and build cleaner registration/account screens in the theme or a small custom plugin.

Pros:

- simplest foundation
- no lock-in to a large membership plugin
- easier to control Afrikaans labels and flows
- aligns well with a custom block-theme build

Cons:

- you must build more profile/account UX yourself

Best when:

- you want tight control over UX
- the team is comfortable with a bit of custom development

#### Option B: BuddyPress for identity plus community baseline

Use BuddyPress not just for community features, but also as the member-profile foundation.

Pros:

- member profiles, directories, activity, friendships, and notifications come together
- avoids duplicating identity and community models

Cons:

- still needs careful front-end design to avoid old-school community-site UX
- can tempt the build back into plugin-led UI decisions

Best when:

- community identity is central to the product

### Recommendation

Use WordPress core authentication with BuddyPress as the community/member layer, but do not let BuddyPress dictate the entire front-end. Keep authentication and profiles clean, and design the registration flow around INK's actual lifecycle:

- create account
- choose reader or writer intent
- complete profile
- if writer, explain tiers and subscription requirement
- prompt first social action after signup

### Decision

Follow recommendation. WordPress core authentication with BuddyPress as the community/member layer. The registration flow will be designed around INK's specific writer/reader lifecycle.

## 2. Membership tiers

This is the Bronze/Silver/Gold writer progression system. It is business-specific and should not be delegated to a generic plugin.

### Good implementation model

Store the current tier directly against the user as controlled site data.

Recommended data model:

- user meta: `ink_writer_tier`
- allowed values: `bronze`, `silver`, `gold`
- optional user meta: `ink_writer_tier_promoted_at`
- optional history record: promotion log for auditability

### Good admin workflow

Build a small admin interface in `ink-core` that lets staff:

- view current tier
- promote a writer
- record why they were promoted
- optionally link the promotion to a challenge result

### Good front-end use

The tier should be usable in several places:

- visible on member profiles
- filterable in writer discovery
- used to segment challenge participation
- used to display winners correctly

### Plugin vs custom

This should be custom.

Why:

- the logic is simple
- the business meaning is unique to INK
- generic membership-level plugins will confuse payment tiers with writer tiers

### Recommendation

Implement writer tiers as a custom user attribute with a lightweight admin UI and promotion log. Do not mix this with subscription software.

### Decision

Follow recommendation. Tiers stored as `ink_writer_tier` user meta in `ink-core`. Historical tier data will be imported from the existing spreadsheet during migration. No generic plugin.

## 3. Subscription management

This is separate from writer tier. It answers: who is currently allowed to submit work?

### Option A: WooCommerce + Memberships

This is the most obvious reuse path because the plugins already exist.

Suggested model:

- one product for 1 month
- one product for 6 months
- one product for 12 months
- purchase grants a time-limited membership/access rule
- access rule enables submission privileges

Pros:

- familiar and mature ecosystem
- room for future paid products, donations, workshops, or merch
- easier financial reporting than spreadsheet management

Cons:

- WooCommerce can feel heavy if subscriptions are the only commerce feature
- recurring billing depends on gateway support and product design

#### Important note on recurring payments

If the goal is simply fixed-term access, you may not need full recurring subscriptions. Selling fixed-duration products is often enough. If true auto-renewal matters, verify PayFast recurring support and extension compatibility early.

### Option B: Paid Memberships Pro or similar membership-first plugin

This is attractive if the site does not actually need a general store.

Pros:

- simpler mental model for access control
- often cleaner for member-only content and expiration rules
- less store-oriented admin complexity

Cons:

- less future-proof if INK later wants broader commerce
- may require migration away from the existing WooCommerce setup

### Recommendation

If INK expects any broader paid offerings beyond writer subscriptions, stay with WooCommerce and implement subscriptions properly this time. If subscriptions are the only payment feature, evaluate a leaner membership plugin before committing.

Either way, the rule should be explicit:

- subscription status controls submission entitlement
- writer tier does not control payment status

Those two concepts should meet in the workflow, not in the data model.

### Decision

Option A — WooCommerce + WooCommerce Memberships. Three fixed-term products: 1 month (R60), 6 months (R300), 12 months (R600). No auto-renew initially; recurring billing to be added in a future phase. PayFast remains the payment processor. Subscription status controls submission entitlement; writer tier is a separate concept.

## 4. Community features

The site needs commenting, friendships, likes, discovery, and room for future engagement experiments.

### Option A: BuddyPress as community engine, custom front-end UX

This is the most flexible option.

Keep BuddyPress for:

- friendships
- member directory
- activity stream if desired
- notifications
- messaging if desired

Then build custom front-end components for:

- writer discovery
- engagement prompts
- profile highlights
- calls to interact with other writers

Pros:

- flexible
- avoids full lock-in to a commercial "social network" wrapper
- lets the design stay opinionated and literary instead of generic-social

Cons:

- needs design and implementation effort

### Option B: BuddyBoss-style ecosystem

This gives a more turnkey community product.

Pros:

- polished out of the box
- lots of community features prepackaged

Cons:

- more lock-in
- can push the site toward a product shape you did not ask for
- may still leave you customizing heavily to get the literary tone right

### Comments

Keep WordPress comments, but redesign the interface and prompts.

Suggestions:

- ask better questions below the content
- highlight author replies clearly
- reward thoughtful responses, not just raw volume
- consider curated prompts that change by content type

### Friendships and discovery

Friendships can remain BuddyPress if you want that mechanic.

Discovery should not depend only on search. Build dedicated surfaces for:

- writers like this one
- new voices to discover
- recently active writers
- writers in your tier
- writers you have not read yet

### Likes and reactions

Simple likes are fine, but they should probably not be the main engagement mechanic. They are better as a lightweight signal than as the centerpiece.

### Recommendation

Use BuddyPress as the underlying relationship engine, but design discovery and engagement as custom site features rather than accepting default community screens.

### Decision

Follow recommendation. INK is a literary publishing site that fosters a supportive community — not a social network. BuddyPress provides friendships, the member directory, and notifications. Discovery and engagement surfaces are custom. Social features stay useful and simple. The goal is to give people more reasons to return and read, not to build a feed-based social product.

## 5. The reading experience

This is an area where INK can do something distinctive.

### Baseline

Stories and poems can absolutely remain standard posts, but the reading surface should encourage response.

### Good engagement ideas

- a short contextual prompt after the piece
- "what line stayed with you?"
- "what image or phrase stood out?"
- a one-click way to save or react to a line
- suggested next reads based on tone, form, topic, or tier

### Options for passage highlighting

#### Option A: private reader highlights

Readers can select text and save favorite lines privately.

Pros:

- technically simpler
- avoids moderation issues around public annotations

Cons:

- less social payoff

#### Option B: public favorite-line responses

Readers can select a passage and post a short public response attached to it.

Pros:

- socially richer
- highly aligned with literary reading

Cons:

- more custom work
- moderation becomes more important

#### Option C: guided comment prompts only

Skip inline passage features and focus on making comments more discoverable and less intimidating.

Pros:

- simpler build
- less UX complexity

Cons:

- less differentiated

### Recommendation

Start with guided prompts plus a lightweight favorite-line feature. That is distinctive enough to improve engagement without turning the reading surface into a complicated annotation tool.

### Decision

Yes to contextual comment prompts after each piece. Yes to suggested next reads. Yes to line highlighting with simple reactions (thumbs up, heart, wow) — discoverable but unobtrusive. No to annotation or public passage commentary. The highlight feature is a form of encouragement, not a critique tool.

## 6. The posting experience

The goal is not just submission. It is socialized submission.

### Desired flow

- writer starts a new post easily
- writer understands what kind of piece they are posting
- writer sees any challenge context or tier context if relevant
- writer is encouraged to read and respond to peers
- writer gets prompted toward community activity after publishing

### Option A: keep Youzify Frontend Submission short-term

Pros:

- lowest disruption
- proven in the current site

Cons:

- translation pain
- tied to old plugin assumptions
- not ideal as the long-term author experience

### Option B: replace with a purpose-built front-end submission flow

This can be done with:

- a custom front-end editor interface using WordPress APIs
- or a lighter form-based workflow with structured fields and preview

Pros:

- best fit for INK
- full control over Afrikaans UI and workflow cues
- easiest place to weave in prompts like "comment on two peers this week"

Cons:

- more custom build effort

### Option C: use a different submission plugin

This is viable only if the plugin is well translated, actively maintained, and not overloaded.

### Recommendation

Keep the existing plugin only as a bridge if needed. The better long-term solution is a custom front-end submission flow inside `ink-core`, because this is one of the core product experiences.

### Decision

Option B — custom front-end submission flow in `ink-core`. No Youzify bridge needed; the requirements are modest enough that a custom form is straightforward. Introduce three custom post types to distinguish content: `gedig`, `storie`, and `artikel`. The editor needs plain text with basic formatting only — no full rich-text editor. Supported extras: optional featured image and optional audio or video attachment.

**On the Youzify bridge question:** the short-term option was flagged in case timeline pressure made it necessary. Given the modest submission requirements, building a clean custom form from the start avoids the translation friction and is the better long-term choice.

## 7. Library and training replacement

The current sections look content-rich but structurally underpowered. They feel like archives instead of a real learning and reference experience.

### What a good replacement looks like

Not necessarily an LMS.

A good replacement is probably a structured resource hub with:

- dedicated content types
- strong taxonomies
- filters and search
- curated pathways
- relationship blocks that connect resources to challenges and writing needs

### Recommended content model

- `training_article`
- `resource`
- `resource_collection` or `guide`

Taxonomies might include:

- genre
- skill area
- difficulty
- format
- intended audience

### Good user journeys

- "I want help with poetry"
- "I want help with short stories"
- "I am new and do not know where to start"
- "I am entering this month's challenge and need relevant resources"

### Implementation options

#### Option A: custom post types + taxonomies + faceted search

This is the strongest fit.

Pros:

- flexible
- editorially controllable
- lighter than a full LMS

Cons:

- requires planning and templates

#### Option B: LMS plugin

Use this only if training needs progress tracking, lessons, quizzes, certificates, or cohort learning.

Pros:

- strong for formal education models

Cons:

- likely overkill for INK's current training library

### Recommendation

Build a resource hub, not an LMS, unless the training product becomes much more formal.

### Decision

Option A — custom post types, taxonomies, and faceted search. Training library stays a resource hub and a fringe benefit of membership. No formal learning product needed.

## 8. InkPols

InkPols deserves a proper publication model.

### What a good replacement looks like

- one dedicated custom post type for issues, for example `inkpols_issue`
- structured metadata for issue date, volume, cover image, PDF, teaser, and featured contributors
- a clean archive by year and issue
- a robust single-issue page
- optional embedded reading view plus downloadable PDF

### Optional extension

If InkPols should also surface individual articles inside each issue, model those as related content instead of hiding everything in a PDF.

### Plugin vs custom

The content model should be custom.

If a PDF viewer is needed, add one small well-supported viewer only if native browser PDF display is not sufficient.

### Recommendation

Treat InkPols as a publication system, not a theme template trick.

### Decision

InkPols stays PDF-based. The Real3D Flipbook plugin is retained for display. No individual article extraction — the team's capacity does not support it. Migrate the back catalogue to a clean `inkpols_uitgawe` CPT with structured metadata (issue date, volume, cover image, PDF, teaser).

## 9. Members online

Remove it.

Replace it, if anything, with signals that help actual engagement:

- active discussions
- newly published work
- writers seeking feedback
- featured writer of the week

### Decision

Remove the members-online widget. Replace with engagement-relevant signals if sidebar/footer space is used at all.

## 10. Sponsors

Sponsor recognition should be designed, not dumped.

### Recommended model

Create a `sponsor` content type with fields for:

- name
- logo variants
- link URL
- sponsorship tier
- campaign start/end dates
- placement preferences

### Good display patterns

- one featured sponsor on the homepage
- a subtle scrolling sponsor strip
- sponsor cards in relevant secondary areas
- a proper sponsor page for full recognition

### Why this matters

This gives controlled exposure without turning every page into a logo graveyard.

### Recommendation

Build sponsor display as structured content with reusable blocks and scheduling rules.

### Decision

Follow recommendation. Build a `borg` CPT with scheduling and placement fields. Homepage shows one featured or rotating sponsor. No full sponsor dump on content pages.

## 11. Cleaning business logic out of the theme

This is non-negotiable if the new theme is a modern block theme.

### What should move into `ink-core`

- custom post types
- taxonomies
- user tier logic
- challenge rules
- submission permissions
- sponsor rotation logic
- InkPols data model
- custom REST endpoints or admin tools

### What should stay in the theme

- templates
- block patterns
- theme styles
- block variations
- editor styles
- animations and visual presentation

### Why this split matters

If business logic stays in the theme:

- redesign becomes risky
- future theme changes become expensive
- content models become brittle

### Recommendation

Build a custom block theme with a deliberately strong visual identity, but put site behavior in a companion plugin. That gives you visual freedom without architecture drift.

### Decision

Follow recommendation. Custom block theme for presentation; `ink-core` plugin for all business logic, content models, and INK-specific rules.

## 12. Afrikaans-first implementation

Afrikaans-first is not just a translation task. It is a product decision.

### What this should mean in practice

- all content model labels start in Afrikaans
- all user journeys are written in Afrikaans first
- all admin labels exposed to editors are considered for Afrikaans clarity
- plugin choices are screened for translation quality before adoption

### Good implementation rules

- set the site locale correctly from day one
- write all custom strings with proper internationalization functions
- define an Afrikaans editorial glossary early
- test every chosen plugin for translatability before committing to it
- avoid feature plugins that hardcode awkward English UX

### Important mindset

If Afrikaans is the primary language, then English should be the fallback for developers, not the other way around.

### Recommendation

Design the information architecture, content model names, onboarding copy, prompts, and admin UI labels in Afrikaans first. Use translation tooling as support, not as rescue.

### Decision

Follow recommendation. Afrikaans is designed in from the start, not retrofitted. An official terms dictionary has been created (`afrikaans-terms.md`) as the source of truth for all UI labels, code identifiers, and action language. Plugin choices will be screened for translation quality before adoption.

## 13. Surfacing training library content

The training content should appear where people need it, not only inside the library section.

### Good surfaces

- homepage entry points for popular themes or beginner pathways
- writer dashboard or submission screen suggestions
- challenge pages with relevant training links
- related resources beneath stories and poems
- profile pages showing topics a writer is learning or contributing to
- seasonal editorial callouts such as "improve your dialogue" or "writing sonnets"

### Strong pattern

Use relationship fields so editors can connect:

- training content to challenges
- training content to genres
- training content to submission screens
- training content to writer tiers

That makes surfacing deliberate instead of algorithmic guesswork.

### Decision

Follow the surfacing approach, but only where it is frictionless for editors. Manual per-article linking to challenges will not be used — it will simply be ignored under workload. Instead, surface training content through shared taxonomy: training articles and bydraes share the same genre and skill-area taxonomy terms, so relevant resources appear automatically without additional editorial effort per article.

## Recommended build path

If the goal is a robust, opinionated, modern rebuild, the most defensible path is:

1. Build a custom block theme.
2. Build an `ink-core` plugin for business rules and content models.
3. Keep BuddyPress only if you want friendships, directories, and baseline community primitives.
4. Replace Youzify and Youzify Frontend Submission over time with custom UX.
5. Choose one payment/membership strategy and implement it fully instead of keeping manual spreadsheets.
6. Model writer tiers as first-class site data.
7. Rebuild library, training, InkPols, and sponsors as structured content systems.

## Strategic decisions

Answers to the questions that drove this planning phase.

**1. Community depth**
INK is a literary publishing site that fosters a supportive community. It is not a social network. Social features should be useful and simple — not a feed-driven product.

**2. Engagement goal**
Comments are not a high priority in themselves, but the site needs to break the pattern of writers visiting only three times a month: to see the new challenge, to post their work, and to check results. The site should give people reasons to return and read the content library that has been built up over the years. Engagement features serve that goal.

**3. Auto-renew**
Start with fixed-term subscription products. Recurring/auto-renew billing to be added in a future phase once PayFast recurring support and extension compatibility is confirmed.

**4. Challenge administration**
Yes — the site should help administer challenge winners. Winner data should be stored as structured, queryable records so it can be surfaced contextually throughout the site (e.g. a poem tagged as "Oktober Goud-wenner").

**5. Training library scope**
The training library is a resource hub and a fringe benefit of membership. No formal learning product, courses, or progress tracking needed.