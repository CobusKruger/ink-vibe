# INK Website Planning

## Purpose

This folder contains the working planning documents for the new INK website.

The goal of this document set is to move from discovery to implementation with a clear paper trail:

- understand the current site
- identify business-critical rules and content models
- compare implementation options
- make technology and UX decisions deliberately
- keep planning documents easy to find as the set grows

This `README.md` is the entry point and index for the planning work. As new planning documents are added, this index should be updated to include them.

## Current documents

### Entry point

- [initiation.md](./initiation.md)
  Master summary and project initiation document. Synthesises all planning work, indexes every other file, and gives step-by-step guidance for building the site using BMAD 6. **Start here.**

### Core planning set

- [instructions.md](./instructions.md)
  Original planning brief and problem statement for the replacement site.
- [site-structure-audit.md](./site-structure-audit.md)
  Audit of the current WordPress site: structure, features, plugin roles, custom content, risks, and replacement requirements.
- [implementation-options.md](./implementation-options.md)
  Strategy document covering implementation options, plugin triage, architecture recommendations, and feature-specific approaches for all 13 feature areas.
- [migration-plan.md](./migration-plan.md)
  Detailed migration plan covering every data domain: users, tiers, subscriptions, posts, library, training, InkPols, challenges, sponsors, BuddyPress data, media, redirects, and the order of operations.
- [afrikaans-terms.md](./afrikaans-terms.md)
  Official Afrikaans terminology guide. Defines the correct UI terms, code identifiers, action language, status messages, and terms to avoid. The source of truth for all user-facing language on the new site.
- [lovable-block-theme-playbook.md](./lovable-block-theme-playbook.md)
  Implementation playbook for converting Lovable designs into a WordPress block theme system. Defines token standardisation, reusable layout primitives, page archetypes, and quality gates.
- [design-handoff-workflow.md](./design-handoff-workflow.md)
  Operational workflow for exporting Lovable design information into this repo and making it consumable by implementation agents.
- [spec-kit-guidance.md](./spec-kit-guidance.md)
  Lightweight, specification-first delivery guide for this project when BMAD feels too heavy for current scope.
- [bmad-vs-spec-kit-recommendation.md](./bmad-vs-spec-kit-recommendation.md)
  Side-by-side comparison of BMAD and Spec-Kit for this project, with a practical recommendation and adoption path.
- [hybrid-bmad-spec-kit-execution-guide.md](./hybrid-bmad-spec-kit-execution-guide.md)
  Concrete operating guide for using BMAD and Spec-Kit together, including a "start building tonight" action sequence.
- [spec-kit/README.md](./spec-kit/README.md)
  Working folder for Spec-Kit feature packs (spec, technical design, delivery checklist), including a starter `foundation-theme` slice.

### Design handoff

- [design-handoff/README.md](./design-handoff/README.md)
  Design source folder consumed by implementation agents. Contains design tokens (`theme-tokens.json`, `token-map.md`), the page map, per-page mocks, and the agent brief.
- [mockup-readiness-assessment.md](./mockup-readiness-assessment.md)
  Page-by-page assessment of the Lovable mockup. Documents which pages are reference-ready, which are partial, and which are missing. Identifies genuine design gaps vs. assembly-only work.

### Content and copy

- [ui-copy-translations.md](./ui-copy-translations.md)
  Working translation document for all UI strings in the Lovable mockup. Maps English copy to approved Afrikaans equivalents, page by page. The source for copy used during theme implementation.
- [Biblioteek organisasie.md](./Biblioteek%20organisasie.md)
  Content structure planning for the Biblioteek section — archive depth, date browsing, pagination, and author filtering requirements.

### Plugin and infrastructure

- [plugin-transition-guide.md](./plugin-transition-guide.md)
  Survey of all 25 active and 8 inactive plugins on the staging site. Documents which plugins stay, which are replaced, and which are removed.
- [bmad-guidance.md](./bmad-guidance.md)
  Reference notes on the BMAD 6 methodology and suggested role assignments for this project.

## Suggested document flow

The current documents roughly answer these questions:

1. What are we trying to solve?
   See [instructions.md](./instructions.md).
2. What exists today and what must not be lost?
   See [site-structure-audit.md](./site-structure-audit.md).
3. What are the viable implementation paths?
   See [implementation-options.md](./implementation-options.md).
4. How do we migrate existing data to the new structure?
   See [migration-plan.md](./migration-plan.md).
5. What is the correct Afrikaans term for every concept in the site?
   See [afrikaans-terms.md](./afrikaans-terms.md).
6. How do we translate Lovable designs into maintainable block-theme implementation?
  See [lovable-block-theme-playbook.md](./lovable-block-theme-playbook.md).
7. How do we store and present design-system assets for agent implementation?
  See [design-handoff-workflow.md](./design-handoff-workflow.md).
8. What is a lighter alternative to BMAD for this project?
  See [spec-kit-guidance.md](./spec-kit-guidance.md).
9. Which method should we use now: BMAD or Spec-Kit?
  See [bmad-vs-spec-kit-recommendation.md](./bmad-vs-spec-kit-recommendation.md).
10. How do we run BMAD and Spec-Kit together in practice?
  See [hybrid-bmad-spec-kit-execution-guide.md](./hybrid-bmad-spec-kit-execution-guide.md).

## Next likely planning documents

As the planning process continues, useful follow-up documents will likely include:

- data model and content types
- membership and subscription workflow
- submission workflow
- community engagement strategy

## Maintenance note

Whenever a new planning markdown file is added to this folder, update this index so the README remains the canonical starting point.