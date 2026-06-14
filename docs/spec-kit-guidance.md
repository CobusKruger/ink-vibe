# Spec-Kit guidance for INK website project

## Purpose

This guide defines a lighter, specification-first delivery method for this project.

Use this when BMAD feels too process-heavy for the current team size and delivery pace, while still preserving:

- clear requirements
- architecture discipline
- migration safety
- Afrikaans-first UI quality

Spec-Kit in this project means: short, high-signal specs per feature, one technical design per feature cluster, and strict quality gates before merge.

## When to choose Spec-Kit here

Choose Spec-Kit as the default when most of the following are true:

- The team is small (1-3 people) and the same person often fills multiple roles.
- Requirements are mostly known from existing docs and the old site audit.
- You need momentum now and can refine details iteratively.
- You still need hard constraints for migration and language correctness.

Avoid pure ad-hoc execution. Spec-Kit is lightweight, not no-process.

## Core project constraints (non-negotiable)

Every Spec-Kit item must comply with these rules:

1. Afrikaans is the UI language. User-facing strings must follow [afrikaans-terms.md](./afrikaans-terms.md).
2. `ink-core` owns business logic and access rules.
3. Tier and Subscription remain separate concepts.
4. Migration work is validated on staging clone before production.
5. Lovable design direction is implemented via block theme tokens and patterns.

## Spec-Kit artefacts for this project

For each feature or feature cluster, produce only three artefacts.

### 1) One-page Feature Spec

Template sections:

- Goal
- User roles affected
- In scope / out of scope
- UX and Afrikaans copy notes
- Data model touches (`ink-core`, plugin-owned, or both)
- Acceptance criteria (5-10 bullets)
- Risks and rollback note

Target length: 1-2 pages max.

### 2) Technical Design Note

Template sections:

- Proposed implementation approach
- Ownership boundaries (theme vs `ink-core` vs third-party plugin)
- Storage model changes (CPT/meta/taxonomy/options)
- API and integration points
- Migration impact
- Test plan

Target length: 1-3 pages max.

### 3) Delivery Checklist

Template sections:

- Build tasks
- QA tasks
- Migration rehearsal tasks
- Sign-off criteria

Target length: checklist only, no long prose.

## Suggested folder structure

If you adopt Spec-Kit, keep specs in a single predictable location:

- `docs/spec-kit/README.md`
- `docs/spec-kit/features/<feature-slug>/feature-spec.md`
- `docs/spec-kit/features/<feature-slug>/technical-design.md`
- `docs/spec-kit/features/<feature-slug>/delivery-checklist.md`

Use kebab-case feature slugs, for example:

- `membership-checkout`
- `community-feed`
- `library-access`
- `training-hub`

## Recommended work cadence

Use this 5-step loop per feature:

1. Draft Feature Spec.
2. Draft Technical Design Note.
3. Build in small PRs tied to acceptance criteria.
4. Run QA + staging migration rehearsal if data is affected.
5. Merge only when checklist is fully green.

## Scope slicing for this project

Work in vertical slices, not technical layers.

Suggested initial slices:

1. Foundation slice
   - theme tokens, core block patterns, Afrikaans navigation labels, baseline templates
2. Membership slice
   - WooCommerce products, checkout flow, tier sync in `ink-core`
3. Submission slice
   - front-end contribution workflow and moderation path
4. Community slice
   - directory/profile/community engagement surfaces
5. Migration slice
   - users, tiers, subscriptions, content, redirects

## Definition of done (Spec-Kit)

A feature is done only if all checks pass:

- Acceptance criteria all met.
- Afrikaans terminology validated against [afrikaans-terms.md](./afrikaans-terms.md).
- Architecture boundaries respected (`ink-core` logic not leaked into theme).
- Tests added for business rules.
- Migration rehearsal executed when data paths changed.
- Redirect or SEO impact documented when URLs/templates changed.

## Suggested decision rule

Use Spec-Kit as default for this project now.

Escalate a specific feature to BMAD-style deeper process only when at least one trigger appears:

- cross-team coordination grows beyond 3 active contributors
- unclear requirements across multiple departments
- high-risk domain changes (billing, permissions, large migration uncertainty)
- repeated rework from ambiguous specs

This keeps process proportional to risk while preserving delivery quality.
