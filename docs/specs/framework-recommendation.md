# INK — Spec-Framework Recommendation

> **Date:** 2026-06-14
> **Generated from:** [`spec-consolidation-brief.md`](./spec-consolidation-brief.md) — the originating brief (deliverable 3 of three). Validate this recommendation against it.
> **Question:** Spec-Kit or BMAD — which is better suited to build the INK site from `ink-consolidated-spec.md` + `ink-feature-list.md`?
> **Derivation note:** This recommendation was made from **clean context**. The prior in-repo framework write-ups (`bmad-guidance.md`, `spec-kit-guidance.md`, `bmad-vs-spec-kit-recommendation.md`, `hybrid-bmad-spec-kit-execution-guide.md`) were **deliberately ignored** at the user's instruction. The reasoning below is derived freshly from the project's nature and the two consolidated specs.

## Recommendation

**Use BMAD (v6). Pick one framework — do not run a hybrid.**

---

## What each framework optimises for

**Spec-Kit** is a lightweight, spec-first toolkit. You move `constitution → specify → plan → tasks → implement`, producing a per-feature spec, a technical plan, and a task list driven from a prompt. Its sweet spot is **well-bounded, largely independent features added to a clean/greenfield codebase**, where you want minimal process and to let an AI agent generate each slice from a focused description.

**BMAD** is a heavier, role-based agentic method. A planning phase (Analyst → PM → Architect) produces a **Product Brief, PRD, and Architecture**, which are then **sharded into epics and stories** for a dev/QA loop. Its sweet spot is **large, multi-domain products with cross-cutting concerns and an existing system to respect** — and it has first-class **brownfield** support.

---

## Why BMAD fits INK

The decision turns on four properties of this specific project:

1. **It is brownfield, not greenfield.** The new site is deployed onto an **existing WordPress install**, reuses the **existing database**, and inherits a curated plugin set (`plugin-transition-guide.md`: 33 plugins triaged — most kept for their own capability, the WooCommerce stack specifically retained for live-data continuity). There is a substantial migration + redirect layer over several thousand posts. BMAD models existing-system constraints directly; Spec-Kit's `specify`-from-prompt flow assumes you are describing new behaviour on a clean base and has no native place for "respect this existing data model and these live records."

2. **It is large and multi-domain with cross-cutting concerns.** Seventeen epics span 9 CPTs, commerce, community, a custom submission workflow, reading engagement, migration, and an Afrikaans-first mandate that touches every surface. Concerns like Afrikaans-first, the three-layer architecture, and migration cut *across* features rather than living inside one slice. BMAD's PRD + architecture-then-shard structure is built to hold cross-cutting decisions once and decompose downward; Spec-Kit's per-feature specs tend to duplicate or lose cross-cutting context.

3. **The decisions are already made; the need is decomposition, not discovery.** The planning corpus has settled purpose, principles, stack, data model, IA, plugin triage, and 13+ feature decisions. What's missing is structured breakdown into buildable, sequenced stories with acceptance criteria. That is exactly BMAD's PRD → epic → story pipeline. The consolidated spec and feature list in this folder are already shaped as a Product Brief / PRD / epic inventory — they drop into BMAD with low impedance.

4. **The reported failure mode is intrinsic to Spec-Kit's model.** The original brief noted Spec-Kit "gets confused when there is all this previously-prepared documentation." That is the predictable result of pointing a prompt-driven, greenfield-feature tool at a rich pre-existing corpus. The consolidation in this folder mitigates it, but the underlying mismatch — a from-scratch feature-spec generator vs. a large, decided, brownfield product — remains. BMAD is designed to *consume* a brief/PRD of this kind rather than regenerate it.

### Where Spec-Kit would have won (and why it doesn't here)
Spec-Kit would be the better choice if INK were a handful of discrete, independent features on a clean codebase, or if the team wanted the lightest possible process for a small scope. Neither holds: the scope is broad, the concerns are entangled, and the system is brownfield.

### Why not hybrid
The two specs in this folder give a single coherent PRD/architecture/epic chain. Splitting execution across two frameworks adds tooling overhead and two sources of truth for marginal benefit on a project whose decisions are already settled. One framework, cleanly applied, is the lower-risk path. (Per the user's instruction, a single framework was required.)

---

## Adoption path (feeding these specs into BMAD)

1. **Constitution / principles** → `ink-consolidated-spec.md §2` (guiding principles) + §12 (Afrikaans-first) become the project rules BMAD agents must honour.
2. **Product Brief** → `ink-consolidated-spec.md §1, §3, §4` (purpose, scope, users).
3. **PRD** → `ink-consolidated-spec.md` §5–§8, §11, §13 plus `ink-feature-list.md` (functional requirements as epics/features with acceptance criteria).
4. **Architecture** → `ink-consolidated-spec.md §6` (three-layer model, CPTs, user meta, taxonomies, follow graph) + §10 (plugin integration points).
5. **Design system** → `ink-consolidated-spec.md §9` + `design-handoff/*` (tokens, page-map, archetypes) as the architect's UI/front-end input.
6. **Epic/story sharding** → use `ink-feature-list.md`'s 17 epics directly; each feature row is a story seed with layer, data source, and acceptance hints already attached. Prioritise P0 → P1 → P2.
7. **Resolve open decisions first** → clear `ink-consolidated-spec.md §14` (especially the terminology reconciliation for the follow decision, historical-challenge scope, and hosting/CDN) before the architect locks the relevant epics.
8. **Sequence around migration** → treat Epic 15 (migration & redirects) as a release-gating track running alongside feature epics, per the migration order in §11.

## Suggested first build slice
**Epic 1 (Foundation) + Epic 2 (Content models)** — the block-theme token foundation and `ink-core` CPT/taxonomy registration. Everything else depends on them, they carry the least ambiguity, and they validate the three-layer architecture before the larger epics commit to it.

---

## One-line answer
INK is a large, decided, brownfield WordPress rebuild with heavy cross-cutting concerns and a real migration — **BMAD's brief→PRD→architecture→epics→stories pipeline fits it; Spec-Kit's greenfield, per-feature, prompt-driven model does not.**
