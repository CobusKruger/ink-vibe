## BMAD 6 guidance

[BMAD](https://github.com/bmad-agile/bmad-method) is a structured, agent-based development methodology designed for projects built with AI assistants. BMAD 6 organises work around specialised roles — each role produces specific artefacts before the next phase begins.

### Suggested BMAD role assignments for this project

| BMAD role | Responsibility for this project |
|---|---|
| **Analyst** | Refine and formalise requirements from this document into a structured PRD. Resolve open questions before architecture begins. |
| **Architect** | Translate the confirmed PRD into a technical architecture document: database schema, plugin integration boundaries, API surface, theme structure, `ink-core` plugin architecture. |
| **Product Manager** | Break the architecture into epics, stories, and a prioritised backlog. Identify MVP scope versus later phases. |
| **Developer** | Implement stories per sprint. Use the technical architecture as authority. Use `afrikaans-terms.md` as the source of truth for all UI strings. |
| **QA** | Verify each story against the PRD and the Afrikaans terminology guide. Test migration steps and redirect rules independently against a clone of `ink-staging`. |

### Recommended BMAD phase sequence

#### Phase 1: Discovery and PRD (Analyst)

The Analyst must produce a PRD that covers:

- Full list of user stories per role type (visitor, free member, subscribing member, writer, editor/admin)
- Confirmed content models and custom post type definitions
- Access control matrix (who can read, comment, publish, and manage)
- Acceptance criteria for the Afrikaans-first UI requirement
- Resolution of open questions (see "Unresolved items" below)

**Inputs for this phase**: all documents in this `/docs/` folder.

#### Phase 2: Architecture (Architect)

The Architect must produce:

- `ink-core` plugin architecture: CPT registration, taxonomies, user meta, custom roles, REST endpoints, admin UI for tier promotion
- Block theme structure: template hierarchy, block patterns, theme components
- Plugin integration boundaries: exactly what data each third-party plugin owns versus what `ink-core` owns
- Migration script architecture: sequence, data flow, rollback strategy
- Redirect strategy: URL pattern map and implementation approach

**Inputs for this phase**: PRD from Phase 1, [implementation-options.md](./implementation-options.md), [migration-plan.md](./migration-plan.md).

#### Phase 3: Backlog (Product Manager)

The PM must produce a prioritised backlog covering:

**MVP:**
1. `ink-core` plugin with CPT registration and tier meta
2. Block theme framework with Afrikaans-first UI strings
3. Subscription workflow via WooCommerce (three products, PayFast)
4. Custom front-end submission form
5. BuddyPress integration for profiles and member directory
6. Migration scripts: users, tiers, subscriptions, content, redirects
7. Yoast SEO baseline configuration

**Phase 2 (post-MVP):**
1. InkPols issue archive with Real3D Flipbook
2. Challenge CPT with per-tier winner records
3. Library CPT with taxonomy and filters
4. Training hub CPT with taxonomy and faceted skill-area search
5. Sponsor CPT with scheduling and placement fields
6. Enhanced community features (discovery surfaces, reading nudges)
7. Line highlights and contextual reaction prompts on reading pages

#### Phase 4: Implementation (Developer + QA)

Each story is delivered with:

- Implementation against the architecture specification
- All UI strings referenced from [afrikaans-terms.md](./afrikaans-terms.md) — no English in any user-facing output
- Unit tests for business logic in `ink-core`
- QA sign-off against acceptance criteria
- Migration tests run against a clone of `ink-staging` before any production changes

### Standing rules for all BMAD agents

1. **Afrikaans is the site's UI language.** No agent may generate English labels, button text, error messages, or navigation items for the site itself. Before writing any user-facing string, consult [afrikaans-terms.md](./afrikaans-terms.md). Planning documents like this one are written in English.
2. **`ink-core` owns business logic.** No rules in the theme. No rules in third-party plugin hooks that reference theme state.
3. **Tier ≠ Subscription.** These two concepts use separate data stores and must never be conflated in the same piece of logic.
4. **Existing content is inviolable.** Every migration script must be tested against the staging clone before touching production. No production data is altered until the redirect layer is verified.
5. **Lovable sets the visual direction.** The block theme implements the Lovable design. The theme does not invent a new visual direction — it translates the Lovable design into WordPress block patterns and theme styles.

---

## Unresolved items (must be settled before the PRD)

These questions were identified during planning but not yet given a final answer. The Analyst must resolve them during the PRD phase.

| # | Question | Relevant document |
|---|---|---|
| 1 | Which BuddyPress extended profile fields are worth keeping? Which are noise? | [migration-plan.md](./migration-plan.md) |
| 2 | What are the exact access rules for library and training content? (Assumed: free registered member. Confirm.) | [site-structure-audit.md](./site-structure-audit.md) |
| 3 | Was WooCommerce Memberships ever actively used, or is it an unconfigured placeholder? | [site-structure-audit.md](./site-structure-audit.md) |
| 4 | How much historical challenge data should be modelled structurally versus left as flat archive posts? | [migration-plan.md](./migration-plan.md) |
| 5 | What is the exact format and data quality of the tier spreadsheet and the subscription spreadsheet? | [migration-plan.md](./migration-plan.md) |
| 6 | Will auto-renewal be offered for subscriptions? (Requires confirming PayFast recurring support.) | [implementation-options.md](./implementation-options.md) |
| 7 | Are BuddyPress groups actively used in practice, or can they be ignored in the new build? | [site-structure-audit.md](./site-structure-audit.md) |
| 8 | What Youzify-specific profile data must be extracted from the old database before the plugin is deactivated? | [migration-plan.md](./migration-plan.md) |

---

## Next step

Begin the BMAD Analyst phase. The Analyst should read this document and all other files in `/docs/` and produce a full PRD that:

- lists all user stories per role type
- formalises the access control matrix
- resolves the unresolved items above, or flags them as open questions with acceptable defaults
- defines acceptance criteria for the Afrikaans-first UI requirement
- confirms MVP scope versus later phases

The PRD then becomes the single source of truth that drives all subsequent phases.
