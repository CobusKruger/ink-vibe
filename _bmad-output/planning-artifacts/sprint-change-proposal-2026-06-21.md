# Sprint Change Proposal — Terminology Management Strategy

- **Date:** 2026-06-21
- **Author:** Cobus (with Dev agent, Correct-Course workflow)
- **Trigger discovered:** Epic 1 code review (2026-06-21)
- **Status:** APPROVED 2026-06-21
- **Decisions:** mechanism = label registry (recommended path); new story = 2.0; architecture decision = AD-10.
- **Mode:** Batch
- **Related:** `sprint-change-proposal-2026-06-20.md` (G1 terminology refinement), `docs/afrikaans-terms.md`, `_bmad-output/planning-artifacts/architecture.md`, `_bmad-output/planning-artifacts/epics.md`, `_bmad-output/project-context.md`

---

## Section 1 — Issue Summary

**Problem.** INK's controlled vocabulary (`docs/afrikaans-terms.md`) is the documented source of truth for every term, with a binding rule: *a concept is added to the glossary before it appears in code or UI.* But that glossary is **human-readable prose, hand-duplicated into code** as scattered string literals. The architecture already enforces single-definition for **code identifiers** (enums for tiers/reactions/response-types; fixed CPT/taxonomy slugs — project-context.md line 42, "never duplicate these literals across the codebase"). It does **not** do the same for **UI display labels** — the Afrikaans words a member actually reads.

Under the decided i18n strategy (gettext with Afrikaans as the *source* string, **no English `.mo`** for `ink-core`), each displayed label is the gettext source literal at its call site. There is therefore **no single place to change a term's label**. Re-deciding a controlled-vocabulary term means:

1. a **code-wide search-and-replace** across theme patterns, block-template HTML, and `ink-core` PHP, **plus**
2. a **DB search-replace** (`wp search-replace`) for the term wherever it lives in page/menu/CPT content, **and**
3. it leaves the glossary and code as two hand-synced copies that drift.

**When/how discovered.** Surfaced during the Epic 1 code-review loop while assessing future maintainability of UI copy.

**Evidence.**
- The controlled-vocabulary terms already appear hardcoded across **~12 code files** (theme patterns + `ink-core` PHP).
- The **11 Epic-1 theme pattern files contain zero `__()`/`esc_html__()` calls** — copy is raw text in block markup, so it is neither gettext-extractable nor centrally editable, and is non-conformant to project-context.md line 45 ("i18n on every user-facing string") and Quality Gate D.
- **G1 (2026-06-20) already executed exactly this class of change** — `intekening → lidmaatskap`, `tier → Gradering`, `Skrywersprofiel → Skrywerprofiel`, plus Meester/EntryID/algehele-wenner. Term re-decisions are a **recurring, owner-driven event**, not a one-off.
- **Timing:** Epic 2 (next) registers all CPT/taxonomy/field labels — the single largest label surface on the site. Deciding the mechanism now means Epic 2 adopts it natively instead of being retrofitted across a built-out site.

---

## Section 2 — Impact Analysis

### Epic Impact
- **Epic 1 (Foundation) — built, in review.** Theme patterns shipped with un-wrapped, hardcoded labels. Bounded remediation needed (route labels through the mechanism / wrap strings). The i18n scaffold story (1.10) and `ink-core` Kernel i18n loader are the natural home for the plugin-side registry.
- **Epic 2 (Content models & taxonomy) — next, not started.** Stories 2.1 (register CPTs), 2.2 (register taxonomies), 2.4 (CPT admin field sets) register Afrikaans labels. **Primary beneficiary** — adopt the registry from day one.
- **Epic 17 (Afrikaans-first & localisation).** The standing English-leak scan (NFR-1) and label QA gain a deterministic surface to inspect; registry becomes part of the leak-scan coverage story.
- **Cross-cutting:** every later epic that renders a glossary label (membership, Gradering, submission, discovery, community) reads the same single source.

### Story Impact
- **NEW foundational story** — a glossary-backed terminology/label registry in `ink-core` (**Story 2.0**, an Epic-2 prerequisite).
- **AMEND** Epic 2.1 / 2.2 / 2.4 acceptance criteria — labels registered via the registry, not inline literals.
- **NEW remediation task** — Epic 1 theme patterns route their chrome labels through the mechanism (theme-side bridge) and wrap free-form copy. (Can attach to a 1.x remediation story or fold into Epic 17 execution — PO's call.)

### Artifact Conflicts (documents needing updates *after* approval)
- **`architecture.md`** — add an Architecture Decision (**AD-10 (confirmed)**) for the terminology label registry; cross-reference the existing enum/no-`.mo` decisions (lines 820–839).
- **`project-context.md`** — add a rule under *Afrikaans-first* mandating glossary-backed labels (sibling to the existing enum rule).
- **`afrikaans-terms.md`** — add a maintenance note that a **machine-readable companion** (`terms` registry) is now seeded from it, and identify which column (UI-term) is the canonical label source.
- **`epics.md`** — insert the new story; amend Epic 2.1/2.2/2.4 ACs; note Epic 1 remediation.

### Technical Impact
- A single registry — e.g. `Ink\I18n\Terms` (or `Glossary`) — keyed by glossary concept (`membership`, `gradering`, `bydrae`, …), each value a **literal** `__( 'Lidmaatskap', 'ink-core' )`.
  - **Single-source editing:** change a term in one file; every `ink_term('membership')` / `Terms::label('membership')` call updates.
  - **gettext-compatible:** because the registry holds *literal* `__()` calls, `wp i18n make-pot` still extracts every label — the registry file *is* the extraction surface.
  - **CRITICAL caveat to document:** never wrap `__()` around a *variable* (e.g. `__( $label )`) — `wp i18n make-pot` cannot extract dynamic strings. Literals live only in the registry; everywhere else calls the key.
- **Theme side:** the theme cannot call `ink-core` PHP from static block-template HTML. Options: (a) a small theme bridge that exposes the same labels for PHP patterns; (b) for static `templates/*.html`, the **Block Bindings API** (WP 6.5+) can bind heading/paragraph/button text to a registered `ink/term` source — flagged as the bridge for the otherwise-unreachable HTML templates (adopt only where needed).
- **Out of scope for any code mechanism:** DB content (page bodies, nav menus, migrated posts). Term changes there remain a `wp search-replace` operation — call this out explicitly so it is not assumed covered.

---

## Section 3 — Recommended Approach

**Path: Direct Adjustment** (not rollback, not MVP reduction). The Afrikaans-first / no-`.mo` strategy is sound and stands; this *extends* the existing single-definition discipline from code IDs to UI labels.

**Recommended mechanism: a glossary-backed label registry** (single-source literal `__()` in one file) + keep enums for IDs.
- **Chosen over** full gettext-`.mo` inversion (Afrikaans-as-translation): that contradicts the decided no-`.mo` policy, is heavier tooling, and is better reserved for the *separate, later* "future-English" decision.
- **Block Bindings** noted as the targeted bridge for static block-template HTML, not a blanket adoption.

**Rationale.** It is the smallest change that closes the doc↔code drift, makes a term re-decision a one-file edit for all code-rendered labels, preserves gettext extractability and the leak-scan gate, and is consistent with how the project already treats code IDs.

**Effort / risk / timeline.**
- Effort: ~1 foundational story (S–M) + light Epic 2 AC edits + a bounded Epic 1 pattern remediation.
- Risk: Low–Moderate. Main risks: the `make-pot` "no variables" caveat (mitigated by literals-in-registry), and the theme/plugin split (mitigated by the bridge/Block Bindings note).
- Timeline: **insert before Epic 2.** Small, high-leverage; retrofitting after a built-out site is the expensive scenario this avoids.

---

## Section 4 — Detailed Change Proposals (OLD → NEW)

> Proposals only — applied after approval. Identifiers marked *(proposed)* are the PO's to finalize.

### 4.1 New story — Story 2.0
```
NEW STORY: Terminology label registry (glossary-backed label source)
Layer: K (ink-core)  Priority: P0  Epic: 1 (Foundation) or 2 prerequisite

As an Afrikaans-first product with an owner-maintained controlled vocabulary,
I want every code-rendered UI label sourced from a single glossary-backed registry,
So that re-deciding a term is a one-file edit, not a codebase-wide search.

Acceptance Criteria:
- A registry in ink-core (e.g. Ink\I18n\Terms) maps glossary concept keys to
  literal __('<Afrikaans>', 'ink-core') label definitions, seeded from
  docs/afrikaans-terms.md (UI-term column).
- A helper (ink_term('key') / Terms::label('key')) returns the label; callers
  never inline the literal.
- `wp i18n make-pot` extracts every registry label (literals only; no __($var)).
- Code IDs/slugs/enums remain the existing enum/constant single-source (unchanged).
- A theme-side bridge exposes the same labels to PHP patterns; static
  block-template HTML uses the Block Bindings API where a dynamic term is needed.
- Doc: the registry's relationship to afrikaans-terms.md (glossary remains the
  human source of truth; registry is its machine projection).
```

### 4.2 Epic 2.1 — Register CPTs (AC amendment)
```
OLD: ... gedig, storie, artikel, ... exist with Afrikaans slugs ... (labels inline)
NEW: ... exist with Afrikaans slugs ...; all CPT labels (singular/plural/menu/
     admin) are sourced from the terminology registry (Story 2.0), not inline
     literals.
Rationale: largest label surface; adopt the single-source mechanism natively.
```

### 4.3 Epic 2.2 — Register taxonomies (AC amendment)
```
NEW: taxonomy labels (genre, vaardigheid, uitdagingsrondte, ster_gradering)
     sourced from the terminology registry.
```

### 4.4 Epic 2.4 — CPT admin field sets (AC amendment)
```
NEW: admin field-set labels sourced from the terminology registry where they
     correspond to glossary concepts.
```

### 4.5 Epic 1 — theme pattern remediation *(task)*
```
NEW TASK: Route the Epic-1 theme pattern chrome labels through the terminology
mechanism (theme bridge) and wrap remaining free-form copy in the theme text
domain; bring patterns into Quality-Gate-D / "i18n on every string" conformance.
```

### 4.6 architecture.md — new decision — AD-10 (confirmed)
```
NEW AD-10 — Terminology label registry. ink-core exposes a glossary-backed,
single-source label registry (literal __() definitions) for all code-rendered
UI labels; cross-references the enum/code-ID single-source rule and the no-English-.mo
policy. Block Bindings is the bridge for static block-template HTML. DB content
term changes remain a wp search-replace operation (out of registry scope).
```

### 4.7 project-context.md — new rule (under Afrikaans-first)
```
NEW: "Controlled-vocabulary UI labels come from the ink-core terminology
registry (single-source, glossary-backed), the same way fixed value sets come
from enums. Never inline a glossary label as a bare literal outside the registry."
```

### 4.8 afrikaans-terms.md — maintenance note
```
NEW (Onderhoud section): "Die UI-term-kolom word in 'n masjienleesbare register
(ink-core Terms) geprojekteer; die gids bly die menslike bron van waarheid.
'n Termverandering word een keer hier gemaak en dan in die register weerspieël."
```

---

## Section 5 — Implementation Handoff

**Scope classification: MODERATE** — backlog reorganization + an architecture decision; the Afrikaans-first strategy is unchanged (not Major), but it touches architecture, Epic 2 scope, and already-built Epic 1 (more than Minor).

**Routing.**
- **Architect (Winston):** finalize AD-10 in `architecture.md`.
- **PO / SM:** finalize story numbering, insert the new story, amend Epic 2.1/2.2/2.4 ACs, schedule the Epic 1 remediation.
- **Dev (Amelia):** implement the registry + helper + theme bridge; migrate Epic 2 label registration; remediate Epic 1 patterns.

**Success criteria.**
1. Changing a glossary term's UI label is a **single-file edit** that propagates to all code-rendered labels.
2. `wp i18n make-pot` extracts all registry labels cleanly (no dynamic-string gaps).
3. Epic 2 CPT/taxonomy/field labels register through the registry.
4. NFR-1 English-leak scan stays green; the registry is an inspected surface.
5. Documented, explicitly: DB-content term changes remain a `wp search-replace` step (not covered by the registry).

---

## Checklist (Correct-Course analysis)
- [x] Trigger understood and evidenced
- [x] Epic impact assessed (1, 2, 17, cross-cutting)
- [x] Story impact assessed (new story + AC amendments + remediation)
- [x] Artifact conflicts identified (architecture, project-context, glossary, epics)
- [x] Technical impact assessed (registry, make-pot caveat, theme bridge/Block Bindings, DB-content boundary)
- [x] Path chosen (Direct Adjustment) with rationale
- [x] Scope classified (Moderate) and routed
- [N/A] Rollback of completed work (not warranted)
- [N/A] MVP scope reduction (not warranted)
- [!] Story numbering + AD number to be finalized by PO/Architect on approval
