# Hybrid BMAD + Spec-Kit execution guide

## Purpose

This guide shows exactly how to use BMAD and Spec-Kit together for this project, with concrete first actions you can take tonight.

Use this as the operating manual for moving from planning docs to implemented code.

## One-line operating model

Use Spec-Kit for day-to-day feature delivery, and pull in BMAD depth only for high-risk decisions.

- Spec-Kit: fast feature specs, technical notes, build loops
- BMAD: deeper Analyst/Architect pass when risk is high or requirements are unclear

## What to do tonight (90-minute start plan)

If you want to start building immediately, do these steps in order.

### 0) Pick one narrow vertical slice (10 minutes)

Choose one of these as your first coding target:

1. Foundation tokens + header/footer block patterns
2. Membership checkout baseline (products + checkout flow skeleton)
3. Submission form skeleton (front-end form + moderation placeholder)

Recommendation for tonight: start with Foundation tokens + core patterns because it unlocks every page.

### 1) Create a working feature spec (15 minutes)

Create a single spec document for tonight's slice:

- `docs/spec-kit/features/foundation-theme/feature-spec.md`

Minimum content to include:

- Goal
- In scope (strictly small)
- Out of scope
- Acceptance criteria (5-8 bullets)
- Afrikaans UI notes referencing [afrikaans-terms.md](./afrikaans-terms.md)

Use [spec-kit-guidance.md](./spec-kit-guidance.md) as the format source.

### 2) Create a technical design note (15 minutes)

Create:

- `docs/spec-kit/features/foundation-theme/technical-design.md`

Must answer:

- What belongs in theme vs `ink-core`
- Which tokens/patterns/templates are touched
- Test checks (visual + functional)
- Rollback note

If you hit uncertainty about architecture boundaries, pause and run a BMAD Architect-style pass for this slice only.

### 3) Build checklist and task split (10 minutes)

Create:

- `docs/spec-kit/features/foundation-theme/delivery-checklist.md`

Break into:

- Build tasks
- QA tasks
- Sign-off checks

Keep each task small enough to complete in one PR.

### 4) Start coding in vertical increments (35 minutes)

Implementation order for foundation slice:

1. Add/align theme tokens from design handoff tokens
2. Implement base typography and spacing scale
3. Create header pattern
4. Create footer pattern
5. Apply to one real template page to prove flow

Do not implement multiple slices in parallel tonight.

### 5) End-of-session quality gate (5 minutes)

Before stopping:

- Verify acceptance criteria status in the checklist
- Record open issues in the feature folder
- Note whether any item requires BMAD escalation tomorrow

## How to feed existing docs and context to agents

Do not feed all documents blindly every time. Feed a focused context pack per task.

### Context pack for most feature work (Spec-Kit default)

Always include these:

1. [instructions.md](./instructions.md)
2. [implementation-options.md](./implementation-options.md)
3. [afrikaans-terms.md](./afrikaans-terms.md)
4. [lovable-block-theme-playbook.md](./lovable-block-theme-playbook.md)
5. [design-handoff-workflow.md](./design-handoff-workflow.md)
6. The current feature spec + technical design note

Include these only when relevant:

- [migration-plan.md](./migration-plan.md) for data-moving work
- [site-structure-audit.md](./site-structure-audit.md) for legacy behavior parity
- [bmad-guidance.md](./bmad-guidance.md) when escalating risk

### Copy/paste kickoff prompt for coding a slice

Use this in your coding agent chat:

```text
Implement the feature defined in docs/spec-kit/features/foundation-theme/feature-spec.md
using docs/spec-kit/features/foundation-theme/technical-design.md as authority.

Constraints you must enforce:
- Afrikaans UI terms from docs/afrikaans-terms.md
- Business logic must remain in ink-core (not in theme)
- Follow design tokens and layout principles from docs/design-handoff and docs/lovable-block-theme-playbook.md

Execution rules:
- Work in small, reviewable commits
- Update delivery checklist status as you complete each item
- If architecture uncertainty appears, stop and propose a BMAD Architect mini-pass for the uncertain part only
```

### Copy/paste prompt for BMAD escalation (targeted)

Use only when needed:

```text
Run a BMAD Architect mini-pass for this feature risk area:
[describe risk in 1-2 lines]

Inputs:
- docs/spec-kit/features/foundation-theme/feature-spec.md
- docs/spec-kit/features/foundation-theme/technical-design.md
- docs/bmad-guidance.md
- docs/implementation-options.md

Output required:
1) clear boundary decisions (theme vs ink-core vs plugin)
2) updated technical design section for the risk area
3) acceptance criteria updates to prevent regressions
Keep output concise and implementation-ready.
```

## How to progress from docs to actual code

Use this lifecycle for each feature slice.

1. Spec (what and why)
   - `feature-spec.md`
2. Design (how)
   - `technical-design.md`
3. Build (code changes)
   - theme and/or `ink-core` updates in small PRs
4. Verify (did it work)
   - checklist and acceptance criteria checks
5. Stabilise (ready to move on)
   - log residual risks and next slice entry point

A slice is not complete until checklist and acceptance criteria are both green.

## Concrete first coding target (recommended)

Start with `foundation-theme` slice and complete this micro-scope:

- Wire token values into theme configuration
- Ship one header pattern
- Ship one footer pattern
- Render correctly on one key template page

This creates an implementation base for all remaining pages and reduces rework later.

## Definition of progress for Week 1

By end of Week 1 you should have:

1. Spec-Kit folder structure created
2. Three feature specs drafted
3. One feature slice fully implemented and validated
4. One risk area escalated through BMAD mini-pass (if needed)

If this is true, your process is working and you can scale feature-by-feature.
