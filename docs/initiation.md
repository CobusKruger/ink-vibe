# INK Website – Project Initiation

## What this document does

This is the master index and summary document for the INK website rebuild. It synthesises everything discussed so far, references every specialised document in this folder, and gives step-by-step guidance for building the site using **BMAD 6**.

Read this document first. Then move to the specific source document once you start on a given area.

---

## Project overview

### What this project is

INK is a community publishing platform for Afrikaans writers, poets, and readers. The new site replaces an existing WordPress installation with thousands of published contributions, an active membership, and an established set of operational processes.

The four driving objectives of the rebuild are:

- **Preservation**: every existing contribution, member account, and historical record must be retained.
- **Afrikaans-first UI**: the site's interface, system messages, error states, button labels, navigation items, and all user-facing text must be in Afrikaans. Not one English word should appear to a site visitor or member. This applies to the website itself, not to the planning and development documents in this folder.
- **Clean architecture**: the current site embeds business rules in theme glue and mismatched plugins. The new site separates presentation, business logic, and platform concerns into distinct layers.
- **Automation**: the current manual EFT-based payment step and spreadsheet-based tier tracking are replaced by proper automated systems. Subscription tracking is already handled by WooCommerce Memberships; what is missing is front-end payment collection via PayFast.

### The existing site

The existing site (in `/ink-staging/`) is a WordPress installation driven by:

- A custom theme (`ink-v2`) derived from the JoinUp theme
- BuddyPress + Youzify for community and profiles
- WooCommerce + WooCommerce Memberships + PayFast for commerce (WooCommerce Memberships is in active use for subscription tracking and access enforcement; PayFast is installed but not yet connected to a front-end purchase flow — payment is currently collected offline via EFT)
- Youzify Frontend Submission for member content submission
- Various other plugins (WPBakery, Loco Translate, Yoast SEO, etc.)

The site has **five overlapping functional areas**:

1. Public reading and archive experience for Afrikaans writing
2. Paid member publishing workflow
3. Community layer (profiles, activity, friendships, messaging)
4. Library and training resources
5. Editorial project management (challenges, winners, InkPols, sponsors)

---

## Document index

### Core planning documents

| Document | Purpose |
|---|---|
| [instructions.md](./instructions.md) | Original planning brief and problem statement. The starting point for all planning. |
| [site-structure-audit.md](./site-structure-audit.md) | Detailed audit of the existing site: structure, features, plugin roles, content models, risks, and replacement requirements. |
| [implementation-options.md](./implementation-options.md) | Strategy document covering plugin triage, architecture choices, and confirmed decisions across all 13 feature areas. |
| [migration-plan.md](./migration-plan.md) | Migration plan for every data domain: users, tiers, subscriptions, posts, library, training, InkPols, challenges, sponsors, BuddyPress data, media, redirects, and order of operations. |
| [afrikaans-terms.md](./afrikaans-terms.md) | The official Afrikaans terminology guide. The single source of truth for all UI labels, code identifiers, action language, and status messages on the site itself. |
| [lovable-block-theme-playbook.md](./lovable-block-theme-playbook.md) | Practical implementation playbook for converting Lovable designs into a tokenised, reusable WordPress block theme system. |
| [design-handoff-workflow.md](./design-handoff-workflow.md) | Workflow for exporting Lovable design assets into this repo and presenting them to implementation agents consistently. |

### How to use the documents

1. **What are we building and why?** → [instructions.md](./instructions.md)
2. **What exists today and what must not be lost?** → [site-structure-audit.md](./site-structure-audit.md)
3. **What decisions have been made about how to build it?** → [implementation-options.md](./implementation-options.md)
4. **How do we migrate existing data?** → [migration-plan.md](./migration-plan.md)
5. **What is the correct Afrikaans term for a given concept?** → [afrikaans-terms.md](./afrikaans-terms.md)
6. **How do we implement Lovable designs in a maintainable block theme?** -> [lovable-block-theme-playbook.md](./lovable-block-theme-playbook.md)
7. **How do we make design-system information and designed pages available to agents?** -> [design-handoff-workflow.md](./design-handoff-workflow.md)

---

## Confirmed architecture decisions

These decisions were settled during planning and should be treated as fixed constraints.

### Three-layer architecture

| Layer | Purpose | Implementation |
|---|---|---|
| Theme layer | Presentation: templates, patterns, block styles | Custom block theme |
| Site logic layer | Business rules, content models, INK-specific workflows | `ink-core` companion plugin |
| Platform layer | Auth, commerce, SEO, redirects, search, community primitives | Vetted third-party plugins |

### Custom post types

| Code ID | UI label (Afrikaans) | Purpose |
|---|---|---|
| `gedig` | Gedig | Published poems by members |
| `verhaal` | Verhaal | Short stories and prose |
| `artikel` | Artikel | Opinion pieces, essays |
| `skryfwerk` | Skryfwerk | Catch-all bucket for unclassified migrated content |
| `biblioteek_item` | Biblioteekitem | Curated library content, winning entries |
| `opleiding_artikel` | Hulpbronartikel | Training and resource content |
| `uitdaging` | Uitdaging | Monthly challenge with theme, deadline, and results |
| `inkpols_uitgawe` | Uitgawe | InkPols magazine issues (PDF-based) |
| `borg` | Borg | Sponsor content with scheduling fields |

### Custom user meta

| Meta key | Purpose |
|---|---|
| `ink_writer_tier` | Current writer tier: `bronze`, `silver`, or `gold` |
| `ink_writer_tier_promoted_at` | Date of most recent tier promotion |

### Confirmed plugin stack

| Plugin | Role |
|---|---|
| BuddyPress | Community primitives: friendships, member directory, activity feed, notifications |
| WooCommerce + WooCommerce Memberships | Subscription management: three fixed-term products (R60/month, R300/6 months, R600/12 months) |
| WooCommerce PayFast Gateway | Payment processing (PayFast remains the processor) |
| Real3D Flipbook | PDF display for InkPols issues |
| Yoast SEO | SEO baseline |
| Loginizer | Login security: brute-force protection, IP controls |

### Plugins to retire

- Youzify and Youzify Frontend Submission — replaced by a custom submission form in `ink-core`
- WPBakery / JoinUp Core / Qode Framework — incompatible with a block theme direction
- CBX User Online — no meaningful user value
- Classic Widgets — not needed in a block theme
- Simple CSS / Code Snippets — behavioural changes belong in version-controlled code

---

## Information architecture (top-level site areas)

| Nav label (Afrikaans) | Purpose |
|---|---|
| Tuisblad | Clean editorial homepage with a small number of featured streams |
| Lees | Main reading and archive experience for published writing |
| Opleiding | Structured resource hub for writing craft |
| Biblioteek | Curated collection: winners, reference material, document-style resources |
| Uitdagings | Monthly challenges, rules, results, and per-tier winners |
| Gemeenskap | Profiles, member directory, activity feed, social interaction |
| Lidmaatskap | Registration, subscription options, benefits, automated payment |
| Oor INK | Mission, contact, sponsors, organisation pages |

---

## Migration summary

The migration is complex due to content volume but is well mapped. The three main risk areas are:

1. **Post-to-CPT reclassification**: thousands of existing posts must be reassigned to the new custom post types. This will be scripted based on existing categories; unclassifiable content falls through to `skryfwerk` automatically.
2. **Redirects**: every post that moves to a new URL pattern requires a 301 redirect rule. Rules are generated automatically as part of the migration script.
3. **Spreadsheet import**: tier data currently lives outside WordPress in a spreadsheet. This must be imported via a WP-CLI script before the old manual workflow can be retired. Subscription data is already in WooCommerce Memberships and will migrate automatically with the database clone.

Full migration detail: [migration-plan.md](./migration-plan.md)

---

