---
stepsCompleted: ['step-01-document-discovery', 'step-02-prd-analysis', 'step-03-epic-coverage-validation', 'step-04-ux-alignment', 'step-05-epic-quality-review', 'step-06-final-assessment']
overallStatus: 'READY'
documentsIncluded:
  - 'prds/prd-ink-vibe-2026-06-14/prd.md'
  - 'architecture.md'
  - 'epics.md'
  - 'ux-designs/ux-ink-vibe-2026-06-15/DESIGN.md'
  - 'ux-designs/ux-ink-vibe-2026-06-15/EXPERIENCE.md'
---

# Implementation Readiness Assessment Report

**Date:** 2026-06-20
**Project:** ink-vibe

## Step 1 — Document Discovery

### Documents selected for assessment

| Type | Format | Path (relative to `planning-artifacts/`) | Size | Last modified |
|---|---|---|---|---|
| **PRD** | Sharded folder | `prds/prd-ink-vibe-2026-06-14/prd.md` | 89.8 KB | 2026-06-20 00:49 |
| **Architecture** | Whole | `architecture.md` | 73.5 KB | 2026-06-20 00:55 |
| **Epics & Stories** | Whole | `epics.md` | 81.5 KB | 2026-06-20 01:37 |
| **UX Design** | Sharded folder | `ux-designs/ux-ink-vibe-2026-06-15/DESIGN.md` + `EXPERIENCE.md` | — | 2026-06-15 |

### Companion / context documents (not the four core artifacts, used as supporting context)

- PRD process records (audit trail, not re-assessed): `extract-*.md`, `reconcile-*.md`, `review-*.md`, `addendum.md`, `validation-report.{md,html}` inside the PRD folder.
- `sprint-change-proposal-2026-06-20.md` — the **MAJOR** approved scope change whose edits were applied to all artifacts above on 2026-06-20.
- Living companions under `docs/`: `afrikaans-terms.md` (glossary, source of truth), `docs/specs/ink-consolidated-spec.md` (full spec), `docs/ui-copy-translations.md`, `docs/design-handoff/`.
- `docs/specs/ink-feature-list.md` — **superseded** (2026-06-20); `epics.md` is the living source of record for epics/stories.

### Issues found

- ✅ **No duplicate formats.** No document type exists as both a whole `.md` and a sharded folder. PRD and UX are sharded-only; Architecture and Epics are whole-only.
- ✅ **All four required documents present.**
- ℹ️ **Note:** The prior file at this report path was an empty stub (frontmatter only) written 2026-06-20 01:09, *before* `epics.md` was finalized at 01:37 — i.e. stale. It has been overwritten by this run. This assessment validates the freshly re-scoped artifacts.

## Step 2 — PRD Analysis

PRD read in full (802 lines, `status: final`, updated 2026-06-20 to absorb the approved Sprint Change Proposal). Requirements are globally numbered with launch priority (**P0** launch-critical · **P1** at-launch · **P2** fast-follow/deferred) and `(FL x.y)` feature-list traceability. The scope-change requirements map as: **R1**=FR-50-R1, **R2**=FR-50-R2, **R3**=FR-12a, **R4**=Biblioteek stub (§4.9), **R5**=FR-9a, **R6**=FR-3a, **R7**=FR-44a, **R8**=FR-44b, **R9**=annual competition (P2, §4.8 note), **G1**=terminology collapse.

### Functional Requirements (70 total)

| FR | Title | Priority | R-map |
|---|---|---|---|
| FR-1 | Register, log in, reset password | P0 | |
| FR-2 | Unified account — no signup intent gate | P0 | |
| FR-3 | Post-signup first social action prompt | P1 | |
| FR-3a | Account approval, social login & anti-spam | P1 + spike | R6 |
| FR-4 | Membership products — configurable price & term | P0 | |
| FR-5 | Self-service PayFast purchase | P0 | |
| FR-6 | Access enforcement — entitlement gate | P0 | |
| FR-7 | Lidmaatskap page | P0 | |
| FR-8 | Renew membership from profile | P1 | |
| FR-9 | Afrikaans access/status messaging | P1 | |
| FR-9a | Membership lifecycle emails | P1 | R5 |
| FR-10 | Suppress storefront UI | P1 | |
| FR-11 | Tier data model (`ink_writer_tier`, win-count) | P0 | |
| FR-12 | Staff set/adjust tier with reason + log | P0 | |
| FR-12a | Automatic challenge-driven promotion | P0 | R3 |
| FR-13 | Tier ≠ membership guardrail (conflation rule) | P0 | |
| FR-14 | Tier display on profiles (+ My Profiel subtext) | P1 | |
| FR-15 | Tier in discovery & winners | P1 | |
| FR-16 | Custom front-end submission form | P0 | |
| FR-17 | Content-type selection with counters | P0 | |
| FR-18 | Light editor (whitespace-preserving) | P0 | |
| FR-19 | Publishing gated to active betaalde lede | P0 | |
| FR-20 | Optional featured image | P1 | |
| FR-21 | Optional audio/video attachment | P1 | |
| FR-22 | Link a piece to an active challenge | P1 | |
| FR-23 | Save draft / publish with success prompt | P1 | |
| FR-24 | Reading templates for prose | P0 | |
| FR-25 | Poetry reading layout | P1 | |
| FR-26 | Line highlight + reactions | P1 | |
| FR-27 | Structured community responses (lof/insig/voorstel) | P1 | |
| FR-28 | Reaction storage + counts (`_n()` plurals) | P1 | |
| FR-29 | Reading list (leeslys) | P1 | |
| FR-30 | Contextual guided prompts | P2 | |
| FR-31 | Suggested next reads | P2 | |
| FR-32 | Discovery hub + works archive (Ontdek) | P0 | |
| FR-33 | Browse bydraes tab | P1 | |
| FR-34 | Browse skrywers tab | P1 | |
| FR-35 | Search works and writers (diacritic-insensitive) | P1 | |
| FR-36 | Personalised discovery surfaces | P2 | |
| FR-37 | BuddyPress scope configuration | P0 | |
| FR-38 | One-way follow (custom, asymmetric) | P0 | |
| FR-39 | Following-feed | P1 | |
| FR-40 | Block-theme profiles (My Profiel + Skrywerprofiel) | P1 | |
| FR-41 | Pinned/selected works | P1 | |
| FR-42 | Reader ratings & reviews | P1 | |
| FR-43 | Member directory (ledegids) | P1 | |
| FR-44 | Notifications (kennisgewings) | P1 | |
| FR-44a | Automatic post-receipt notification | P1 | R7 |
| FR-44b | Analytics provider + private read counts | P1 | R8 |
| FR-45 | Challenge single page | P1 | |
| FR-46 | Challenges list page (countdown) | P1 | |
| FR-47 | Challenge metadata + monthly cadence (SAST) | P1 | |
| FR-48 | Submit a challenge entry (max 3/type) | P1 | |
| FR-49 | Tier-based competition pools | P1 | |
| FR-50 | Queryable placement records per tier | P0 | |
| FR-50-R1 | Challenge-entry collation → anonymized judge email (EntryID) | P0 | R1 |
| FR-50-R2 | Results ingestion (paste-text) + winners post/banner/feedback | P0 | R2 |
| FR-51 | Winner → promotion link | P1 | |
| FR-52 | Library archive + single | P1 | |
| FR-53 | Link winners ↔ challenge | P2 | |
| FR-54 | Training hub + faceted search | P1 | |
| FR-55 | Auto cross-surfacing of training | P2 | |
| FR-56 | Editor's shelf + community guides | P2 | |
| FR-57 | InkPols issue model, archive & PDF viewing | P1 | |
| FR-58 | Sponsor model, scheduling & placement | P1 | |
| FR-59 | Homepage (Tuisblad) | P0 | |
| FR-60 | Marketing & org pages | P1 | |
| FR-61 | Contact form (custom ink-core) | P1 | |
| FR-62 | Theme-native footer | P1 | |
| FR-63 | Auto-renew / recurring billing | P2 deferred | |

**Embedded requirement not given its own FR number:** **R4 — automatic Biblioteek update** (§4.9): a **P0 stub/hook only** (winning entries update writer's Biblioteek; body deferred). Must appear as a launch-scope hook so FR-50-R2 ingestion can call it. Flagged for epic-coverage check.

### Non-Functional Requirements (9)

| NFR | Title |
|---|---|
| NFR-1 | Afrikaans-first, zero English leakage (Quality Gate D) — standing automated leak-scan; extends to admin-authored form-letter/notification store |
| NFR-2 | Design-token compliance (Quality Gate A) — no hardcoded colours/spacing/type |
| NFR-3 | Performance & caching (LiteSpeed + Cloudflare; light front-end JS) |
| NFR-4 | SEO & URL integrity (301s, Rank Math, preserve `/biblioteek/` `/opleiding/`) |
| NFR-5 | Accessibility & readability (Afrikaans legibility first) |
| NFR-6 | Maintainability for non-technical staff (Site Editor stability, block locking) |
| NFR-7 | Reliability & update governance (staging-gated majors) |
| NFR-8 | Observability (404 logging, Patchstack, leak-scan as CI/cron gate) |
| NFR-9 | Test harness (foundational, Epic 1 — unit/integration/E2E pyramid in ink-core) |

### Additional Requirements & Constraints

- **Binding migration requirements MR-1 … MR-11** (§10): clone/sanitise DB (MR-1), define CPTs/taxonomies before content (MR-2), import users/roles (MR-3), import ster gradering from CSV (MR-4), manually verify lidmaatskappe (MR-5), reclassify posts→CPTs with skryfwerk bucket (MR-6), 301 redirects for every changed URL (MR-7), friendships→two follow records (MR-8), migrate library/training/InkPols/sponsors + rebuild nav (MR-9), verify media + clean WPBakery shortcodes (MR-10), DNS cutover last (MR-11). P0: MR-1–7, MR-11; P1: MR-8–10.
- **THE conflation rule** (binding): subscription entitlement (lidmaatskap) ⟂ writer Gradering (`ink_writer_tier`) — never conflated in data/code (spans FR-6/FR-13/FR-19/FR-49).
- **Three-layer separation** (§12): presentation→theme; INK logic/content models→`ink-core`; commodity→vetted plugins. No business logic in theme.
- **Success Metrics SM-1…SM-8** + counter-metrics SM-C1…SM-C4 (§15) — each cross-references the FRs it validates.
- **A configurable form-letter / notification capability** (§14.1 P0) — the shared options store consumed by FR-9a/FR-12a/FR-44a/FR-50-R2 (stored text + name-merge, per-event toggles, randomized message list). Flagged as a distinct foundational capability for epic-coverage check.

### PRD Completeness Assessment (initial)

The PRD is mature: `status: final`, validated (graded Fair, all findings dispositioned), reconciled against source docs, and freshly updated 2026-06-20 for the MAJOR scope change. Requirements are well-numbered, priority-tagged, and carry testable consequences and `(FL x.y)` traceability. Notable items to verify against epics in Step 3:
- The **R1→R2→R3 hard build order** with **EntryID as the linchpin** (FR-50-R1 must land first).
- The **shared form-letter/notification store** (consumed by 4 FRs) — must be its own epic/story, not duplicated.
- **R4 Biblioteek stub/hook** (P0) — easy to drop since it has no own FR number.
- Several **deferred Open Questions** (OQ-3 POPIA, OQ-16/17/18 migration/lifecycle hardening) are explicitly build-time/founder-gated — expected to be *flagged*, not *resolved*, in epics.

## Step 3 — Epic Coverage Validation

`epics.md` read in full (2218 lines, 19 epics incl. the new **12A** Challenge adjudication automation and **12B** Annual competition; `sourceOfRecord: true`; generated 2026-06-20 from the post-Correct-Course set). The epics doc carries its own **FR Coverage Map** (lines 156–231) and a Requirements Inventory mirroring the PRD. I cross-checked every PRD FR against that map **and verified each cited story actually exists in the body** (not just claimed in the map).

### Coverage Matrix (all 70 FRs)

| FR | Epic / Stories | Status |
|---|---|---|
| FR-1 | 3.1 | ✓ |
| FR-2 | 3.2, 3.3 | ✓ |
| FR-3 | 3.3 | ✓ |
| FR-3a | 3.4, 3.5, 3.6 (+18.10) | ✓ |
| FR-4 | 4.1 | ✓ |
| FR-5 | 4.2 | ✓ |
| FR-6 | 4.3 | ✓ |
| FR-7 | 4.4 | ✓ |
| FR-8 | 4.5 | ✓ |
| FR-9 | 4.7 | ✓ |
| FR-9a | 4.8 (+12A.0) | ✓ |
| FR-10 | 4.6 | ✓ |
| FR-11 | 5.1, 5.7 | ✓ |
| FR-12 | 5.2, 5.3 | ✓ |
| FR-12a | 5.8 (+5.7, 5.10) | ✓ |
| FR-13 | 5.6 | ✓ |
| FR-14 | 5.4, 5.9 | ✓ |
| FR-15 | 5.5 | ✓ |
| FR-16 | 6.1 | ✓ |
| FR-17 | 6.2 | ✓ |
| FR-18 | 6.3 | ✓ |
| FR-19 | 6.8 | ✓ |
| FR-20 | 6.4 | ✓ |
| FR-21 | 6.5 | ✓ |
| FR-22 | 6.6 | ✓ |
| FR-23 | 6.7 | ✓ |
| FR-24 | 7.1 | ✓ |
| FR-25 | 7.2 | ✓ |
| FR-26 | 7.3 | ✓ |
| FR-27 | 7.4 | ✓ |
| FR-28 | 7.8 | ✓ |
| FR-29 | 7.7 | ✓ |
| FR-30 | 7.5 | ✓ |
| FR-31 | 7.6 | ✓ |
| FR-32 | 8.1 | ✓ |
| FR-33 | 8.2 | ✓ |
| FR-34 | 8.3 | ✓ |
| FR-35 | 8.4 | ✓ |
| FR-36 | 8.5 | ✓ |
| FR-37 | 9.1 | ✓ |
| FR-38 | 9.2 | ✓ |
| FR-39 | 9.3 | ✓ |
| FR-40 | 9.4 | ✓ |
| FR-41 | 9.5 | ✓ |
| FR-42 | 9.6 | ✓ |
| FR-43 | 9.7 | ✓ |
| FR-44 | 9.9 | ✓ |
| FR-44a | 9.11 | ✓ |
| FR-44b | 9.12 (+18.9) | ✓ |
| FR-45 | 12.1 | ✓ |
| FR-46 | 12.2 | ✓ |
| FR-47 | 12.3 | ✓ |
| FR-48 | 12.4 | ✓ |
| FR-49 | 12.5 | ✓ |
| FR-50 | 12.6 | ✓ |
| FR-50-R1 | 12A.1, 12A.2 | ✓ |
| FR-50-R2 | 12A.3, 12A.4, 12A.5, 12A.6, 12A.7 (+15.6) | ✓ |
| FR-51 | 12.7 | ✓ |
| FR-52 | 10.1 | ✓ |
| FR-53 | 10.5 | ✓ |
| FR-54 | 11.1, 11.2 | ✓ |
| FR-55 | 11.4 | ✓ |
| FR-56 | 11.3, 11.5 | ✓ |
| FR-57 | 13.1, 13.2, 13.3 | ✓ |
| FR-58 | 14.1, 14.2, 14.3, 14.4 | ✓ |
| FR-59 | 15.1 | ✓ |
| FR-60 | 15.2, 15.3 | ✓ |
| FR-61 | 15.4 | ✓ |
| FR-62 | 15.5 | ✓ |
| FR-63 | 4.9, 4.10, 4.11 | ✓ (deferred, but storied) |

### Non-FR launch requirements (verified covered)

| Requirement | Epic / Story | Status |
|---|---|---|
| **R4 — Biblioteek auto-update stub/hook (P0)** | 10.6 | ✓ (the easy-to-drop one — present) |
| **Shared form-letter / notification capability (P0)** | 12A.0 | ✓ (single store; consumed by 4.8/5.10/9.11/12A.3-4) |
| **R9 — Annual competition (P2)** | 12B.1 | ✓ |
| **Hard build order R1→R2→R3 (EntryID linchpin first)** | 12A epic preamble + 12A.1 sequencing | ✓ explicitly enforced |
| MR-1 … MR-10 | 16.1 / Epic 2 / 16.2 / 16.3 / 16.4 / 16.5 / 16.7 / 16.9 / 16.6+16.8 / 16.10+16.12 | ✓ |
| NFR-1 … NFR-9 | 1.10+17.x / 1.1 / 18.5 / 18.1-18.2+16.7 / 7.x / 1.6 / 18.6-18.7 / 18.2-18.3+17.4 / 1.11+18.8 | ✓ |

### Missing Requirements

**None.** All 70 FRs trace to at least one existing story, and every non-FR launch requirement (R4 stub, the shared form-letter store, all P0 migration steps, all NFRs) is covered. No FR appears in the epics that is absent from the PRD; the "extra" stories (6.9 legacy edit-link removal, 9.8 messaging-deferred, 9.10 online-widget removal, 10.2–10.4 deferred library gaps, 12.8 historical-challenge migration, 13.4 back-catalogue, 16.11 options carry-forward, 2.5 term images, 18.x security/perf/moderation) all trace cleanly to PRD NFRs / MRs / explicit Non-Goals — no orphans, no contradictions.

### Minor observations (not gaps)

- **MR-11 (DNS cutover last)** is expressed as a migration *sequence gate* (Epic 16 ordering) rather than a discrete story — appropriate, but worth a one-line "cutover only after all verifications pass" gate in sprint planning.
- Several P2/deferred items are storied with explicit "post-launch / deferred, non-blocking" ACs (4.9–4.11, 10.2–10.4, 12B.1) — good hygiene; they won't be mistaken for launch work.

### Coverage Statistics

- **Total PRD FRs:** 70
- **FRs covered in epics:** 70
- **Coverage percentage:** **100%**
- Non-FR launch requirements (R4, form-letter store, MR-1–10, NFR-1–9): **all covered**

## Step 4 — UX Alignment

### UX Document Status

**Found.** Sharded under `ux-designs/ux-ink-vibe-2026-06-15/`, both `status: final`:
- **EXPERIENCE.md** — IA, surfaces, roles, component behaviour, state patterns, interaction primitives, accessibility floor, responsive contract, and the 6 named-protagonist journeys (UJ-1…UJ-6). Read in full.
- **DESIGN.md** — visual identity: token frontmatter (colours incl. the new Gradering tokens + Meester, type ramp, spacing, radius, shadow, components), brand posture, dark-mode-deferred decision. Read in full.

I also read **architecture.md** in full (1191 lines, `status: complete`) since this step validates UX↔Architecture support.

### UX ↔ PRD Alignment

**Strong.** EXPERIENCE.md cites PRD FRs throughout and the spine explicitly "wins over any Lovable mock on conflict." Verified:
- **Journeys** UJ-1…UJ-6 match PRD §2.3 beat-for-beat (incl. the post-scope-change R3 auto-promotion + manual Meester in UJ-5).
- **IA / surfaces** match PRD §5 (Tuisblad, Ontdek, Uitdagings, Biblioteek, Opleiding, InkPols, Gemeenskap/Oor INK/Kontak, Lidmaatskap, Skryf, My Profiel / Skrywerprofiel).
- **Scope-change items present in UX:** Skrywerprofiel public / My Profiel private (G1), Meester = `primary #EA4015` (not danger), winner-banner per-rank/per-tier variants with text/icon pairing (C9), the 5 new **Admin surfaces** (R1 collation, R2 ingestion, R3 manual promotion, R5 email config, R6 approval queue), R7 randomized receipt notification, R8 private read counts, paste-only ingestion (no `.docx`).
- **Conflation rule** rendered as "two separate state machines" in State Patterns — consistent with PRD.
- **No UX requirement absent from the PRD** was found; UX `[ASSUMPTION]`s (loading copy, breakpoints, InkPols/ledegids/auth copy) are design-detail gaps the UX itself flags, not PRD contradictions.

### UX ↔ Architecture Alignment

**Strong — architecture was built from the UX** (DESIGN.md + EXPERIENCE.md are in its `inputDocuments`). Each UX need has an explicit architectural home:

| UX need | Architecture support |
|---|---|
| Line resonance, profile/Ontdek tabs, follow toggle, leeslys toggle, "merk alles as gelees" | **AD-7** Interactivity API (`data-wp-*`) backed by `ink/v1` REST |
| `theme.json` tokens only; warm palette; Lora+Inter; 768px reading column | **AD-7 / NFR-2** theme.json canonical; DESIGN.md token names = production source of truth |
| Gemeenskapsreaksies + Terugvoer van die moderator (writer-toggled, private) | **AD-5a** two sanctioned custom comment types (`ink_reaksie`, `ink_moderator_terugvoer`) |
| Follow graph, line highlight+reaksie, leeslys, ratings, challenge entries/EntryID | **AD-5** custom tables (incl. `ink_entries` with `entry_type`+`entry_number` collation columns) |
| Ontdek custom sorts + diacritic-insensitive search | **AD-7** server-rendered discovery, denormalized sort counts, DB-collation accent folding (no search plugin) |
| 5 redakteur admin surfaces (wp-admin chrome, Afrikaans ink-core labels) | **AD-9 + addendum** Notifications/options store, Settings-API screens; Epic 12A admin-flow ACs |
| Randomized receipt message + lifecycle/congrats emails (form-letter, name-merge only) | **AD-9** WP-options form-letter store, name-merge only, Notifications → transactional email |
| Afrikaans empty/success/error/expired state copy | Architecture Process Patterns explicitly: "UI states use the documented Afrikaans copy from EXPERIENCE.md" |
| Dark mode deferred, light only v1 | **AD-7 / NFR-2** "light mode only at v1" — matches DESIGN.md decision |
| Accessibility: rank never colour-only; readability floor (not formal WCAG) | DESIGN.md text+icon pairing; architecture honours NFR-5 readability floor |

The architecture carries its own **Architecture Validation Results** (coherence ✅, requirements coverage ✅, implementation readiness ✅) and self-rates **"READY FOR IMPLEMENTATION — no critical gaps, High confidence."**

### Alignment Issues

**None material.** All three artifacts agree on the conflation rule, the editorial-automation pillar, terminology (G1), light-mode-v1, and the admin-surface flow-not-visual treatment.

### Warnings (minor / cosmetic — non-blocking)

1. **Architecture intro prose is pre-scope-change in two spots.** The *Project Context Analysis → Requirements Overview* still reads "63 FRs (FR-1…FR-63)" and "18 epics / ~109 stories (`ink-feature-list.md`)". The substantive decisions **are** current — **AD-9** and the **2026-06-20 integration/conflation addendum** layer in R1–R9, and the Validation section reflects them — but the headline counts/cross-ref are stale (now 70 FRs, 19 epics, source of record = `epics.md`). Cosmetic; worth a one-line refresh, not a blocker.
2. **Two stale cross-references to the superseded `ink-feature-list.md`:** architecture's input list + EXPERIENCE.md line 79 ("ACs in Epic 12A … `ink-feature-list.md`"). Those ACs now live in **`epics.md` Epic 12A** (verified present in Step 3). Update the pointer when convenient.
3. **UX `[ASSUMPTION]` design-detail gaps** (loading/skeleton copy, numeric breakpoints, InkPols archive/single layout, ledegids copy, auth-screen copy) — all flagged by the UX itself; architecture addresses loading (skeleton matching layout) and breakpoints (behavioral contract). InkPols/ledegids/auth are "assembly-only / not-yet-mocked" surfaces to build fresh — acceptable for launch, no architecture gap.
4. **OQ-12 broken Afrikaans copy** (`ui-copy-translations.md` resonance line) and other `[NEEDS HUMAN AFRIKAANS]` items — human-copy tasks, correctly flagged in both PRD and UX; not an architecture/epic gap.

## Step 5 — Epic Quality Review

Reviewed all 19 epics (incl. 12A/12B) and ~120 stories against the create-epics-and-stories standards: user value, epic independence, no forward dependencies, story sizing, AC quality (Given/When/Then), DB-creation timing, starter-template handling, and brownfield indicators. **Calibration:** this is a brownfield WordPress rebuild on a deliberate three-layer architecture; the architecture explicitly mandates a scaffold-first Epic 1 and a dedicated migration package, and the step's own §5.A/§5.B expect exactly that. Findings are graded with that context.

### Overall verdict

**High quality.** Acceptance criteria are a genuine strength — proper BDD, testable, specific, and rich in error/edge cases (e.g. 6.8 lapsed-Goud denial + draft-preserved; 12A.3 coverage report + irreversible-commit confirm gate + idempotency; 14.3 sponsor collapse/rotate; 16.3 missing-tier → brons + flag). Traceability is complete (every story tagged, FR Coverage Map present). The one substantive finding is **cross-epic forward dependencies on three shared capabilities** — a sprint-sequencing concern, fully documented in the ACs, not a blocker.

### 🔴 Critical Violations

**None.** No technical epic masquerades as a vertical slice without justification; no unmanaged circular dependency; no epic-sized unsizable story.

### 🟠 Major Issues

1. **Shared foundational capability `12A.0` (form-letter / notification store) lives downstream of its consumers.** It is consumed by **4.8** (R5 lifecycle emails, Epic 4), **5.10** (R3 congrat email, Epic 5), and **9.11** (R7 receipt, Epic 9) — all *earlier-numbered* epics depending on a *later* story. Textbook forward dependency ("Epic N requires Epic N+1"). *Mitigation already in place:* each consuming AC explicitly names "(12A.0)", AD-9 gives it a clean code home (Notifications module), and §14.1 lists the form-letter capability as **P0 Foundation** scope. **Recommendation:** in sprint planning, schedule **12A.0 at/near Foundation** (it's small: WP options + name-merge + toggles), ahead of its first consumer — or formally relocate it to Epic 1. Does **not** block Epic 1 start.
2. **Analytics provider `18.9` (R8) is a forward dependency for `9.11`/`9.12`** (R7 receipt + R8 read counts, Epic 9 → Epic 18). Both are P1 and both degrade gracefully when analytics is absent (ACs say so), so the risk is contained — but sprint planning must order **18.9 before 9.11/9.12**, or accept those two ship in a later sprint than the rest of Epic 9.
3. **Moderation/report path `18.4` is referenced by `9.6` (ratings & reviews).** Lower risk: reviews can be created and simply enter the moderation queue once 18.4 lands. Sequence 18.4 no later than the first public-review exposure.

> All three are the natural consequence of a shared-infrastructure design and are **explicitly documented** in the consuming stories — the remediation is ordering, which is precisely the job of the next step (`bmad-sprint-planning`). Hence Major (sequencing), not Critical.

### 🟡 Minor Concerns

4. **Enabler/technical epics (1, 2) carry no standalone end-user value** — Epic 1 "Foundation" (theme/tokens/scaffold/test harness) and Epic 2 "Content models & taxonomy" are pure substrate. Under strict greenfield rules these flag as "Setup/Create Models" epics. **Justified here:** architecture mandates a scaffold-first Epic 1 (step §5.A), MR-2 mandates CPTs-before-content, and three-layer separation makes a substrate layer natural. Epic 1 also carries the foundational test harness (NFR-9, PRD-mandated not-deferred). Accepted as a deliberate, architecture-driven deviation.
5. **R3 engine `5.8` sits in Epic 5 but sequences after Epic 12A** in the R1→R2→R3 chain (12A.1 → 12A.3 → 5.8). Not a true circular dependency: 5.8 builds the `Tiers::promote()` threshold engine as an independently unit-testable function (synthetic placement records), and 12A.3 wires the call as R2's final step. Worth an explicit sprint note so 5.8's *wiring* is scheduled with 12A.3.
6. **Many infra/migration/QA stories use developer/owner personas** ("As an ink-core developer / As a site owner / As a product owner") rather than end-user personas (e.g. 1.1, 1.7, 2.x, 5.1/5.6/5.7, 6.9, 10.6, all of Epic 16, most of Epic 18). Appropriate for enabler/migration/ops work; noted for awareness, not a defect.
7. **A few thin ACs on P1/P2 or not-yet-mocked surfaces:** 9.7 ledegids ("provides a writer-discovery surface" — matches UX's flagged `ledegids` assumption), 11.3/11.5, 14.4, 4.4. Deferred-gap stories (10.2–10.4) use intentional placeholder ACs. Worth fleshing out at story-prep (`bmad-create-story`) time; none are P0.
8. **Project-init work is distributed across Epic 1** (1.1 theme.json, 1.7 ink-core scaffold, 1.10 i18n/admin mechanism, 1.11 test harness) rather than a single explicit "initialize project from scaffold" Story 1.1 (step §5.A pattern). The work is all present and the architecture names it the first priority; purely a packaging nuance.

### DB / entity creation timing

**Correct.** Custom relational tables follow create-when-needed via per-module ownership (AD-5: follow graph in Social/9.x, reactions in Engagement/7.x, `ink_entries` in Challenges/12.x+12A, tier history in Tiers/5.x). CPTs/taxonomies are registered upfront in Epic 2 — which is *correct for WordPress* (CPTs must register early; MR-2 mandates it) and not the anti-pattern the heuristic targets. No violation.

### Per-epic compliance summary

| Epic | User value | Independent (uses only ≤N) | ACs | Notes |
|---|---|---|---|---|
| 1 Foundation | enabler (justified) | ✓ | ✓ | scaffold-first per arch (§5.A) |
| 2 Content models | enabler (justified) | ✓ (needs E1) | ✓ | MR-2 mandated substrate |
| 3 Accounts & auth | ✓ | ✓ | ✓ | R6 spike gates 3.5/3.6 (within-epic, fine) |
| 4 Membership & payment | ✓ | ⚠ 4.8→12A.0 (fwd) | ✓ | see Major #1 |
| 5 Writer Gradering | ✓ | ⚠ 5.10→12A.0, 5.8↔12A.3 | ✓ | see Major #1, Minor #5 |
| 6 Submission | ✓ | ✓ | ✓ (strong) | excellent gate/edge ACs |
| 7 Reading & engagement | ✓ | ✓ | ✓ | |
| 8 Discovery | ✓ | ✓ | ✓ | |
| 9 Community & social | ✓ | ⚠ 9.11→12A.0, 9.12→18.9, 9.6→18.4 | ✓ | see Major #1/#2/#3 |
| 10 Library | ✓ | ✓ | ✓ (10.2–10.4 deferred stubs) | R4 stub 10.6 present |
| 11 Training | ✓ | ✓ | ✓ (11.3/11.5 thin) | |
| 12 Challenges | ✓ | ✓ | ✓ (strong) | |
| 12A Adjudication | ✓ (redakteur) | ✓ (uses 5/12, +12A.0) | ✓ (strong; admin-flow ACs) | R1→R2→R3 order enforced |
| 12B Annual comp | ✓ (P2) | ✓ (reuses 12A) | ✓ | post-launch |
| 13 InkPols | ✓ | ✓ | ✓ | |
| 14 Sponsors | ✓ | ✓ | ✓ (14.4 thin) | |
| 15 Org pages & contact | ✓ | ✓ (15.6 uses 12A.4) | ✓ | |
| 16 Migration | ✓ (preservation/UJ-6) | ✓ | ✓ (strong) | brownfield-correct (§5.B) |
| 17 Afrikaans & localisation | ✓ (defining product property) | ✓ (exec/QA; enablers in E1) | ✓ | late number = completion timing |
| 18 SEO/security/perf | enabler + moderation | ✓ | ✓ | 18.9/18.4 consumed earlier — see Major #2/#3 |

### Best-practices checklist (aggregate)

- [x] Epics deliver user value — *yes, except deliberate enabler epics 1/2 (justified)*
- [~] Epic independence — *3 documented forward deps on shared capabilities (12A.0, 18.9, 18.4) — sequence in sprint planning*
- [x] Stories appropriately sized
- [~] No forward dependencies — *the 3 above; all documented in ACs, none block Epic 1*
- [x] DB tables created when needed (per-module; CPTs upfront is WP-correct)
- [x] Clear acceptance criteria (a strength)
- [x] Traceability to FRs maintained (complete)

## Summary and Recommendations

### Overall Readiness Status

# ✅ READY (proceed to Sprint Planning)

The INK planning set — PRD, UX (DESIGN + EXPERIENCE), Architecture, and Epics/Stories — is **coherent, complete, and mutually aligned**, and all four artifacts have been freshly reconciled (2026-06-20) to absorb the approved MAJOR Sprint Change Proposal (editorial-automation pillar R1–R9 + terminology G1). FR coverage is **100% (70/70)**, every non-FR launch requirement is storied, the architecture self-validates as implementation-ready, and acceptance criteria are high quality. The issues found are **sequencing and cosmetic**, not structural — none block starting Epic 1.

### Critical Issues Requiring Immediate Action

**None.** There are no Critical (🔴) findings in any step. Implementation can begin on Epic 1 (Foundation) immediately.

### Issues to Carry Into Sprint Planning (🟠 Major — sequencing, all documented in ACs)

1. **Schedule `12A.0` (form-letter / notification store) at/near Foundation**, ahead of its consumers `4.8`, `5.10`, `9.11`. It's a small WP-options capability and is already P0-Foundation scope (§14.1); treat it as a Foundation/early story rather than waiting for Epic 12A.
2. **Sequence `18.9` (analytics provider, R8) before `9.11`/`9.12`** (R7 receipt + R8 read counts). Both degrade gracefully without analytics, so they may alternatively ship a sprint later — make the choice explicit.
3. **Sequence `18.4` (moderation/report path) no later than the first public-review exposure** for `9.6` (ratings & reviews).

### Recommended Next Steps

1. **Proceed to `bmad-sprint-planning`** to generate `sprint-status.yaml` from `epics.md` — and while doing so, encode the three sequencing items above plus the R1→R2→R3 hard order (12A.1 → 12A.3 → 5.8) and the MR-11 "DNS cutover only after all verifications pass" gate.
2. **Optional, low-effort artifact polish** (not blocking): refresh the architecture's intro prose (FR count "63"→70, "18 epics / ink-feature-list.md" → "19 epics / epics.md") and the two stale `ink-feature-list.md` cross-refs (architecture inputs + EXPERIENCE.md line 79 → `epics.md`).
3. **Flesh out thin P1/P2 ACs at story-prep time** (`bmad-create-story`): 9.7 ledegids, 11.3/11.5, 14.4, 4.4.
4. **Keep the founder/legal & human-copy items on the pre-launch gate list** (not build blockers): OQ-3 POPIA (sharpened by R8 analytics + R5 emails — bring forward), OQ-12 + `[NEEDS HUMAN AFRIKAANS]` copy, OQ-17 deletion/lapse spec, OQ-18 build-time hardening.

### Final Note

This assessment reviewed 4 artifacts across 6 validation steps and identified **0 Critical, 3 Major (sequencing, all documented), and 5 Minor** issues. **No issue blocks implementation.** The Major items are resolved *by* the next workflow step (sprint sequencing), not before it. The planning set is cleared to proceed to implementation.

---

**Assessment date:** 2026-06-20
**Assessor:** Implementation Readiness workflow (`bmad-check-implementation-readiness`), acting as requirements-traceability PM
**Artifacts assessed:** `prds/prd-ink-vibe-2026-06-14/prd.md` · `architecture.md` · `epics.md` · `ux-designs/ux-ink-vibe-2026-06-15/{DESIGN,EXPERIENCE}.md`
**Verdict:** ✅ READY — proceed to `bmad-sprint-planning`
