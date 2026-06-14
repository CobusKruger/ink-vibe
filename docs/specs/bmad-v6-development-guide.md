# INK — Developing with BMAD v6: Setup, Mechanism & Starting Point

> **Companion to** `ink-consolidated-spec.md`, `ink-feature-list.md`, and `framework-recommendation.md` (which selected BMAD).
> **Date:** 2026-06-14 · **Written against BMAD METHOD v6.8.x.**
> **Authority note:** BMAD workflow/command names can shift between minor releases. After install, the **`bmad-help`** skill inspects your installed modules and tells you the exact next command. Treat `bmad-help` and the official docs (<https://docs.bmad-method.org>) as the final word; treat the command names below as the v6.8 baseline.

---

## 0. What this guide is

We already did the planning. The three documents in this folder are a finished Product Brief + PRD foundation + Architecture baseline + epic/story inventory. BMAD's normal job is to *generate* those through its planning agents; **our job is the opposite — feed BMAD what we've already decided so it reformats and decomposes rather than re-discovers.** That distinction drives everything below, and it's the whole reason we consolidated the corpus (per `framework-recommendation.md`: a prompt-driven tool re-deriving a rich pre-existing corpus is exactly what went wrong before).

This guide covers: **(1) installation, (2) how BMAD v6 works, (3) the concrete starting point for INK.**

---

## 1. Installation

### Prerequisites
- **Node.js 20.12+** and **Git**.
- **Claude Code** (your current tool — BMAD supports it as a first-class IDE target).
- A clean working tree on the INK project repo (BMAD writes agent/workflow files into the project).

### Install command
Run in the project root:

```bash
npx bmad-method install
```

This launches an interactive installer. For a scripted, non-interactive install matching our needs:

```bash
npx bmad-method install --yes --modules bmm --tools claude-code
```

- `--modules bmm` — **BMM (BMad Method module)** is the agile planning-and-development core (Analyst → PM → Architect → SM → Dev → QA). It's the only module INK needs. (Other modules: BMB = BMad Builder, CIS = Creative Intelligence Suite, GDS = Game Dev Studio, TEA = Test Architect — none required here, though **TEA** is worth a later look given our test-harness emphasis in Epic 1/18.)
- `--tools claude-code` — wires BMAD's agents/workflows into Claude Code.
- Prerelease channel (newest features, less stable): append `@next` → `npx bmad-method@next install …`. Stick to the stable release for INK.

### What it creates
- A `_bmad/` tree containing agents, workflows, and templates.
- `_bmad/_config/manifest.yaml` — records exactly what's installed (module versions, channels, commit SHAs). Commit this so the team is reproducible.

### First action after install
Invoke the **`bmad-help`** skill. It detects installed modules and routes you to the right starting workflow. Use it whenever you're unsure of the next command — it's state-aware.

---

## 2. How BMAD v6 works (the mechanism)

### Four phases, scale-adaptive
BMAD runs four phases, and the planning depth adapts to project size:

| Track | Scope | Planning outputs |
|---|---|---|
| Quick Flow | 1–15 stories | Tech spec only |
| **BMad Method** ← INK | products/platforms, 10–50+ stories | **PRD + Architecture + UX** |
| Enterprise | compliance systems, 30+ stories | PRD + Architecture + Security + DevOps |

**INK is the "BMad Method" track** — 18 epics / ~100 features across a multi-domain platform. (It brushes Enterprise because of the migration and data-continuity concerns, but those are handled inside our PRD/architecture, not by switching tracks.)

The four phases:
1. **Analysis** *(optional)* — brainstorming, research, product brief. **INK skips this** — §1–§4 of the spec already is the brief, and decisions are made.
2. **Planning** *(required)* — produces the **PRD** (requirements + a decision log).
3. **Solutioning** — produces the **Architecture**, then the **epics & stories** breakdown.
4. **Implementation** — story-by-story dev loop.

### Agents
- **Analyst** — research/context (we largely bypass).
- **PM** — PRD, epics, stories.
- **Architect** — architecture and implementation-readiness.
- **UX Designer** *(optional, post-PRD)* — interface design. For INK this is mostly *done*: the Lovable design + `design-handoff/*` tokens are the UX input (spec §9).
- **Developer** — sprint planning, story implementation, code review.

### Workflows (the commands you actually run — v6.8 baseline)
**Planning/solutioning:**
- `bmad-prd` — create / update / validate the PRD (carries a decision log; the **Update** intent is brownfield-aware — point it at an existing PRD + a change signal and it surfaces conflicts before applying).
- `bmad-create-architecture` — technical decisions, post-PRD.
- `bmad-create-epics-and-stories` — breakdown using both PRD and architecture.
- `bmad-check-implementation-readiness` — validation gate before building.

**Implementation (the core loop):**
- `bmad-sprint-planning` — produces a YAML sprint status across epics.
- Per story: **`bmad-create-story` → `bmad-dev-story` → `bmad-code-review`**.
- Per epic: `bmad-retrospective`.
- Mid-flight scope change: `bmad-correct-course`.

### Two disciplines that matter
1. **Fresh chat per workflow.** Start a new conversation for each workflow run — this prevents context bleed and keeps each agent focused. (BMAD states this explicitly.)
2. **`bmad-help` after each step.** It re-inspects state and recommends the next move, so nobody has to memorise the sequence.

---

## 3. The concrete starting point for INK

The trick for INK is **seeding**, not generating. In each planning workflow, point the agent at our documents and instruct it to *derive/restructure from them*, honouring the decisions, rather than re-eliciting requirements.

### Source-of-truth mapping (our docs → BMAD artifacts)
| BMAD artifact | Feed it from |
|---|---|
| Product Brief / context | `ink-consolidated-spec.md` §1–§4 (purpose, principles, scope, users) |
| **PRD** | `ink-consolidated-spec.md` §5–§13 **+** `ink-feature-list.md` (functional requirements) |
| PRD **decision log** | `ink-consolidated-spec.md` §14 (16 dated, resolved decisions) |
| **Architecture** | `ink-consolidated-spec.md` §6 (three-layer model, CPTs, user meta, taxonomies, follow graph) **+** §10 (plugin integration points) |
| UX input | `ink-consolidated-spec.md` §9 **+** `design-handoff/*` (tokens, page-map, archetypes) |
| **Epics & stories** | `ink-feature-list.md` — the **18 epics in build/dependency order**; each feature row is a story seed with layer, priority, data source, and acceptance hints |

### Runbook (each step = a fresh chat)

**Step 0 — Pre-flight.** Install BMAD (§1). Run `bmad-help`. Confirm the **BMad Method** track. Have the three spec docs open in the repo so agents can read them.

**Step 1 — PRD (`bmad-prd`, Create intent).** Instruct the PM agent: *"Derive the PRD from `ink-consolidated-spec.md` and `ink-feature-list.md`. These decisions are settled — do not re-elicit. Treat §14 as the decision log. Preserve the 18-epic structure and build/dependency order."* Review the generated PRD against our spec; where BMAD and our docs disagree, **our §14 decisions win** (they're dated and reasoned).

**Step 2 — Architecture (`bmad-create-architecture`).** Seed from spec §5 (stack), §6 (three-layer model, data model), §10 (plugin integration points). Key non-negotiables to enforce in the architecture doc: the **theme / `ink-core` / platform** three-layer split (Principle 2), the **tier ≠ subscription** separation (§4), and **Afrikaans-first + test-harness as foundational** (Epic 1 features 1.10/1.11). UX is already provided — hand the architect `design-handoff/*` rather than running a fresh UX pass.

**Step 3 — Epics & stories (`bmad-create-epics-and-stories`).** Feed `ink-feature-list.md` directly. Preserve epic order (it *is* build order) and the `P0/P1/P2` priorities. Each feature row already carries layer + acceptance hints, so stories should map close to 1:1.

**Step 4 — Readiness gate (`bmad-check-implementation-readiness`).** Should pass cleanly — all §14 decisions are resolved. Only two items remain open **by design and non-blocking**: §14.4 (real founding year + SA nonprofit status — a pre-launch content gate) and the Real3D Flipbook config verification (pre-launch). Neither gates the early epics; note them and proceed.

**Step 5 — Sprint planning (`bmad-sprint-planning`).** Generate the sprint status. Sequence by epic order.

**Step 6 — Build, starting with Epic 1.** Per `framework-recommendation.md`, the first build slice is **Epic 1 (Foundation) + Epic 2 (Content models)** — least ambiguity, everything depends on them, and they validate the three-layer architecture before larger epics commit to it.

Run the loop **`bmad-create-story` → `bmad-dev-story` → `bmad-code-review`** on Epic 1's P0 stories, in order:
- **1.1** `theme.json` design-token system · **1.4** global templates · **1.5** block-pattern library · **1.7** `ink-core` plugin scaffold · **1.10** locale `af` + i18n scaffolding · **1.11** test-harness scaffold.
- 1.10 and 1.11 are deliberately P0 in Foundation so Afrikaans-first and test-as-you-build are wired in from the first story, not retrofitted.

Then proceed to Epic 2 (CPTs, taxonomies, user meta), then **Epic 3 (Accounts, registration & auth)** before any user-specific epic. Run `bmad-retrospective` at each epic boundary.

### Migration is a parallel track, not a late epic
Treat **Epic 16 (Migration & redirects)** as a **release-gating track running alongside** the feature epics, following the migration order in spec §11 — not something deferred to the end. It depends on Epic 2 (CPTs) and Epic 9 (follow graph, for friendship→follow), so it can begin once those land.

---

## 4. Practical tips & gotchas

- **Keep our three docs as source of truth.** BMAD's PRD/architecture/stories are *derived* artifacts. If a BMAD agent proposes something that contradicts a §14 decision, fix the BMAD artifact, not the decision — unless you're deliberately changing the decision (in which case update the spec's §14 first, then regenerate downstream).
- **Don't let BMAD re-run discovery.** Skip Phase 1 (Analysis). When a planning agent starts asking elicitation questions that §1–§14 already answer, point it back at the doc.
- **Fresh chat per workflow** — non-negotiable for clean runs.
- **UX is mostly done.** The Lovable mockup is *design intent, not code* (Principle 7); enforce the React→WordPress translation discipline in spec §9.7 — extract layout/tokens, never port React or lift placeholder copy.
- **Afrikaans-first & testing are cross-cutting**, seated in Epic 1 + standing acceptance criteria. Don't let them slip into their high-numbered epics (17/18) as first-touch work.
- **Brownfield reality:** the DB is cloned and reused. Build/test against seed data; run the real migration (Epic 16) as its own gated track per §11.
- **`bmad-correct-course`** is the tool for mid-build scope changes — use it rather than hand-editing stories.

---

## 5. References
- BMAD METHOD — official docs: <https://docs.bmad-method.org> (Getting Started; How to Install)
- BMAD METHOD — repository & releases: <https://github.com/bmad-code-org/BMAD-METHOD>
- npm package: <https://www.npmjs.com/package/bmad-method>
- INK deliverables: `ink-consolidated-spec.md`, `ink-feature-list.md`, `framework-recommendation.md`
