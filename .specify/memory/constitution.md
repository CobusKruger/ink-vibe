<!--
SYNC IMPACT REPORT
==================
Version change: (template) → 1.0.0
First ratification: all sections created from template.

Principles established:
  I.   Afrikaans-First UI
  II.  Three-Layer Architecture
  III. Preservation-First Migration
  IV.  Spec-Kit Feature Delivery
  V.   Plugin Discipline

Templates updated:
  ✅ .specify/memory/constitution.md  — filled (this file)
  ✅ .specify/templates/plan-template.md — Constitution Check gates updated

Follow-up TODOs: none
-->

# INK Constitution

## Core Principles

### I. Afrikaans-First UI (NON-NEGOTIABLE)

Every string visible to a site visitor or member MUST be in Afrikaans.
This applies to navigation labels, button text, form labels, error messages,
system notifications, status messages, and all page copy. English MUST NOT
appear anywhere in the live site UI.

- The canonical source of truth for approved terminology is
  `docs/afrikaans-terms.md`. Any new UI string MUST be cross-referenced
  against that document before use.
- Code identifiers (variable names, function names, database keys) MAY be
  in English; only user-facing output is governed by this principle.
- Translated strings MUST be reviewed and approved before a feature is
  considered complete. A feature with placeholder English copy MUST NOT
  be merged.

### II. Three-Layer Architecture

The codebase MUST maintain strict ownership boundaries across three layers:

| Layer | Owner | Responsibility |
|---|---|---|
| Theme layer | Custom block theme | Visual design, layout templates, block styles, design tokens in `theme.json` |
| Site logic layer | `ink-core` plugin | Business rules, custom post types, access control, member workflows, submission handling |
| Platform layer | Vetted third-party plugins | Auth, commerce (WooCommerce), community primitives (BuddyPress), SEO, security |

- Business logic MUST NOT live in the theme. Theme files MUST contain
  only presentation concerns.
- Design decisions (color, spacing, typography) MUST be expressed as
  `theme.json` tokens. Inline CSS overrides and ad hoc plugin CSS
  for visual styling are forbidden.
- `ink-core` MUST NOT duplicate functionality already provided by an
  approved platform plugin.

### III. Preservation-First Migration

No existing member account, published contribution, subscription record, or
historical data may be discarded or silently lost during migration.

- Every migration script MUST be rehearsed on a staging clone of the
  production database before being applied to production.
- A rollback procedure MUST be documented and tested for each migration
  batch.
- The staging environment in `ink-staging/` is the mandatory pre-production
  validation target. Production deployments MUST NOT occur without a
  successful staging rehearsal.
- Tier (`ink_writer_tier`) and Subscription (WooCommerce Memberships) are
  separate data concepts and MUST remain separate in the data model and
  in all business logic. Conflating them is a migration-safety violation.

### IV. Spec-Kit Feature Delivery

Every feature or feature cluster MUST be specified before implementation begins.

- Each feature MUST produce three artefacts before a single line of
  implementation code is written:
  1. **Feature Spec** — goal, user roles, scope, UX/copy notes, data model
     touches, acceptance criteria, and a rollback note.
  2. **Technical Design Note** — ownership boundaries, storage model changes,
     API/integration points, migration impact, and test plan.
  3. **Delivery Checklist** — build tasks, QA tasks, migration rehearsal
     tasks, and sign-off criteria.
- Acceptance criteria MUST be written as independently testable statements.
  Vague criteria (e.g., "it works") MUST NOT be accepted.
- A feature MUST NOT be marked complete unless all acceptance criteria
  have been verified and the delivery checklist is fully signed off.

### V. Plugin Discipline

The plugin stack MUST remain lean and intentional.

- No new plugin may be added without an explicit entry in
  `docs/implementation-options.md` documenting the justification and
  confirming that `ink-core` cannot reasonably cover the requirement.
- Plugins listed under "Plugins to retire" in `docs/initiation.md`
  MUST be removed before the new site goes live. Their functionality MUST
  be covered by `ink-core` or an approved replacement.
- Every retained plugin MUST have a documented role. Plugins retained
  purely for legacy compatibility MUST have a scheduled removal target.

## Confirmed Constraints

These rules derive from architectural decisions made during planning and
MUST be treated as fixed constraints unless an explicit amendment is
ratified.

- Custom post types are fixed in `docs/initiation.md`. New CPTs require
  an amendment to that document and to this constitution.
- The payment processor is PayFast via WooCommerce PayFast Gateway.
  Changing the processor requires a governance amendment.
- Subscription products are limited to three fixed-term plans (R60/month,
  R300/6 months, R600/12 months) unless amended.
- Writer tiers are `bronze`, `silver`, and `gold`. Tier promotion logic
  lives exclusively in `ink-core`.

## Development Workflow

- Specifications precede code. No feature branch may be opened without
  a merged or approved Feature Spec.
- The spec-kit artefacts for active features live in
  `docs/spec-kit/features/<feature-slug>/`.
- Design assets consumed by implementation agents live in
  `docs/design-handoff/` and MUST NOT be modified during implementation.
  Changes to design direction require a new design handoff export.
- Every commit that changes user-facing copy MUST reference the approved
  Afrikaans term from `docs/afrikaans-terms.md`.
- Migration scripts MUST be stored under version control and MUST be
  idempotent where technically feasible.

## Governance

This constitution supersedes all other development practices for the INK
website project. In any conflict between a planning document and this
constitution, this constitution takes precedence.

**Amendment procedure**:
1. Propose the change in writing, referencing the affected principle(s).
2. Document the rationale and any migration or compatibility impact.
3. Increment the version number according to semantic versioning rules
   (MAJOR for removals/redefinitions, MINOR for additions, PATCH for
   clarifications).
4. Update `LAST_AMENDED_DATE` and record the change in a Sync Impact
   Report comment at the top of this file.
5. Propagate changes to affected templates and reference documents.

**Compliance**: All feature specs and pull requests MUST pass a
constitution check before being accepted. The constitution check gates
are defined in `.specify/templates/plan-template.md`.

**Version**: 1.0.0 | **Ratified**: 2026-06-13 | **Last Amended**: 2026-06-13
