# BMAD vs Spec-Kit for this project

## Purpose

This document compares BMAD and Spec-Kit specifically for the INK website rebuild and provides a recommendation for current execution.

## Comparison summary

| Dimension | BMAD | Spec-Kit |
|---|---|---|
| Process weight | Higher, role-structured phases and artefact handoffs | Lower, compact specs and direct build loop |
| Best fit | Multi-role teams, high coordination needs, large ambiguity | Small teams, clear direction, need for speed with discipline |
| Upfront documentation | More comprehensive before implementation | Enough documentation to build safely, then iterate |
| Planning-to-code speed | Slower start, often stronger alignment later | Faster start, strong if scope boundaries are clear |
| Change handling | Formal, good for traceability | Flexible, good for evolving scope |
| Risk control | Strong for complex governance-heavy projects | Good if strict quality gates are enforced |
| Agent orchestration | Natural fit for role-based agent pipelines | Natural fit for implementation agents with concise specs |
| Cognitive overhead | Higher | Lower |

## Project-specific pros and cons

### BMAD for INK

Pros:

- Strong structure for unresolved items and cross-domain decisions.
- Clear role outputs can reduce ambiguity in migration-heavy work.
- Good fit when many people need explicit handoff points.

Cons:

- Can slow momentum for a small team where one person fills multiple roles.
- Higher documentation overhead before visible implementation progress.
- Can feel heavyweight while design and page details are still being filled in.

### Spec-Kit for INK

Pros:

- Faster path from requirement to shipped feature.
- Matches current state: strong baseline docs already exist in `docs/`.
- Easier to iterate while Lovable page coverage is still being completed.
- Lower operational overhead for agent-assisted implementation.

Cons:

- Requires discipline to avoid skipping architecture boundaries.
- Can accumulate inconsistency if checklists and quality gates are ignored.
- Less formal traceability unless artefacts are maintained consistently.

## Recommendation

Recommendation: use **Spec-Kit as the default delivery method now**, with targeted BMAD escalation for high-risk areas.

Reasoning:

- The project already has substantial planning artefacts (`site-structure-audit`, `implementation-options`, `migration-plan`, design handoff docs).
- Current challenge is execution throughput and practical implementation cadence.
- You need flexibility while some design references are still partial.
- Quality can remain high if non-negotiable gates stay enforced.

## Hybrid operating model (recommended)

Use Spec-Kit by default for feature delivery, and apply BMAD depth only where risk is high.

Use BMAD-level depth for:

1. Billing/subscription edge cases and renewal behaviour.
2. Permission model and tier access decisions.
3. High-impact migration scripts and rollback design.
4. Any feature where unresolved items block implementation clarity.

Use Spec-Kit for:

1. Theme pattern implementation from Lovable tokens.
2. Page archetype rollout and template work.
3. Community/discovery UX iteration.
4. Most CPT and admin UX increments once architecture boundary is clear.

## Guardrails if you adopt Spec-Kit

Keep these gates mandatory:

1. Afrikaans UI string checks against [afrikaans-terms.md](./afrikaans-terms.md).
2. `ink-core` ownership of business logic.
3. Migration rehearsal on staging clone before production-impact changes.
4. Acceptance criteria and checklist completion before merge.

## 30-day execution suggestion

1. Week 1
   - Stand up Spec-Kit artefact structure and draft top 3 feature specs.
2. Week 2
   - Implement foundation and membership slices with strict checklists.
3. Week 3
   - Implement submission and community slices.
4. Week 4
   - Run migration rehearsal slice and stabilisation pass.

If churn or ambiguity increases, temporarily switch that feature to BMAD Analyst + Architect depth, then return to Spec-Kit execution.
