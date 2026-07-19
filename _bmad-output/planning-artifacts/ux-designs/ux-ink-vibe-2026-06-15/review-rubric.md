# Spine Pair + Fidelity Spec Review — ink-vibe

## Overall verdict

The contract is source-extractable and internally disciplined: DESIGN.md's canonical section order holds, every in-prose `{token}` reference resolves to frontmatter, the glossary and component names are verbatim-consistent across all three files, and UJ-1…UJ-6 are well-formed. The one load-bearing defect is at the DESIGN.md/theme.json boundary: the fidelity spec asserts theme.json "already has `goud`" and tells the builder to map winner "gold" moments to `goud`/`brons`/`silwer`, but none of those tier tokens exist in theme.json (only `gold-muted` does), and no AC adds them — so the winner card (§5) and Gradering indicator have no resolvable colour preset to build against. Secondary issues are an orphan `mockups/` reference in EXPERIENCE.md and a final-spine-cites-draft-spec status incoherence; neither blocks extraction.

## 1. Flow coverage — strong

Every user journey resolves. UJ-1…UJ-6 each carry a named protagonist (Marlie, Marlie, Pieter, Thandi, Elsa, Johan), numbered steps, an explicit **Climax**, and a **Resolution**. Admin multi-step flows (R1 collation, R2 ingestion) are explicitly delegated to Epic 12A story ACs (EXPERIENCE.md:80) — a documented boundary, not a gap. The theme-rebuild work (Epic 19) is a build task with no user journey, correctly carrying none.

### Findings
- **[low]** UJ flows carry no inline failure path; the calibration examples put `Failure:` lines inside each flow, whereas here failures live only in State Patterns. Most load-bearing is UJ-2, which routes through PayFast but never references the "Jou betaling het misluk of is gekanselleer" path (EXPERIENCE.md:205-210 vs State Patterns:134). *Fix:* add a one-line failure branch to UJ-2 cross-referencing the payment-failed state.

## 2. Token completeness — adequate

Every YAML color token in DESIGN.md carries a hex; every `{colors.*}`, `{rounded.*}` reference in DESIGN.md prose resolves to frontmatter (spot-checked `{colors.brons/silwer/goud/primary-light/highlight-foreground}`, `{rounded.md/lg/xl/2xl}` — all present). EXPERIENCE.md defers appearance to DESIGN.md by name and introduces no dangling `{token}` refs. The break is between the spec's stated token universe and the actual production source of truth (theme.json), which contains only: primary, primary-light, secondary, accent, accent-light, surface, surface-alt, text, muted-text, success, warning, danger, border, highlight, highlight-foreground, gold-muted.

### Findings
- **[high]** theme-fidelity-spec.md token-mapping note #1 (line 257) states "theme.json has `warning`…, `gold-muted`…, and `goud` (= gold-muted)." **theme.json has no `goud` slug** — only `gold-muted`. `goud`, `brons`, and `silwer` are DESIGN.md `[NEW]` frontmatter tokens (Sprint Change C9) that were never added to theme.json, yet the spec also asserts "theme.json tokens … already correct" (line 9) and instructs the builder to "map 'gold' moments to the `goud`/`gold-muted` token family" (§5 AC, line 158). A downstream consumer will find no `goud`/`brons`/`silwer` preset to reference, blocking the winner card (§5) and Gradering indicator. *Fix:* correct note #1 to say only `gold-muted` exists today, and add an AC (in §0 or §5) to register `brons`/`silwer`/`goud` presets in theme.json before §5 can build.
- **[medium]** The spec prescribes `4xl`/`5xl` fontSize presets, a fluid `hero` preset, and the tier-colour presets, none of which exist in theme.json yet. The `4xl`/`5xl`/hero additions are correctly covered by §0.2 ACs, but the tier-colour presets (brons/silwer/goud) have **no "add to theme.json" AC anywhere** — an omission from the fidelity spec's own coverage. *Fix:* add the tier-colour presets to the §0 foundations AC list alongside the fontSize additions.
- **[low]** `gold` is used as a bare token name in §0.3 ("terracotta-light" is fine; §5 target-values prose uses `gold/10 → gold-muted/20`, `gold/20`, eyebrow `gold`) at point-of-use, without the "maps to goud" caveat repeated locally. Note #1 + §5 AC #8 do resolve it, so it is flagged, not unhandled. *Fix:* annotate the first `gold` occurrence in §5 with "(Lovable name → `goud`)".
- **[low]** `brons` (#A6754C) and `silwer` (#9AA3AD) hex are tagged `[ASSUMPTION value]` "confirm against Lovable" (DESIGN.md:37-38). The winner-card and Gradering colour rank rests on unconfirmed values. *Fix:* confirm both against the Lovable source before Epic 19 build.

## 3. Component coverage — adequate

The load-bearing components trace to both spines: **buttons** (DESIGN.md Components + Grounded notes + fidelity §9 / EXPERIENCE Interaction Primitives), **cards** (DESIGN.md `card` + Grounded / fidelity §3,§5,§6), **line-highlight** (DESIGN.md + EXPERIENCE "Line highlight + reaksie"), **Gradering indicator** (DESIGN.md `tier-indicator` + Grounded / EXPERIENCE "Gradering indicator"), and **Winner banner** (DESIGN.md `Winner banner [NEW—C9]` / EXPERIENCE "Winner banner (C9)" / fidelity §5) — the last is a strong three-way trace. Pills, hero, challenge card, story card, CTA band, and footer are compositions/template-parts specced in the fidelity spec and referenced (not duplicated) in the spines, which is appropriate.

### Findings
- **[medium]** The **stats row** (fidelity §4, numbers Lora 24px terracotta + labels) appears only in the fidelity spec — it has no DESIGN.md.Components visual entry and no EXPERIENCE.md Component Patterns behavioral entry, and its data source is undecided (§4 AC + Open Decision #5). A component that exists in only one of three files with an open data question is the weakest trace in the set. *Fix:* either add a one-line stats entry to EXPERIENCE Component Patterns (behavior: static-at-launch vs `ink-core` provider) or explicitly scope it as a fidelity-only static primitive.
- **[low]** The featured **story card** (fidelity §6) has its visual in DESIGN.md ("work cards") but its behavior (counts-without-verbs, `_n()` plurals, read-time) is scattered across EXPERIENCE Reaction counts / Voice rows rather than a single card entry. *Fix:* acceptable as-is; optionally consolidate under a "Werkkaart" Component Patterns row.

## 4. State coverage — strong

State Patterns cover empty, loading, success, error/payment-failed, permission-denied, pending-approval, expired-subscription, and pending/editorial-review, each with verbatim Afrikaans copy. THE CONFLATION RULE is stated as two separate state machines. The fidelity spec adds "collapse gracefully" empty states for every dynamic home section (winner, borg, stats, featured — §5,§6,§7,§11) with an explicit "no placeholder ever reaches production" AC.

### Findings
- **[low]** Loading state remains `[ASSUMPTION]` ("no loading/skeleton copy in source", EXPERIENCE:132) — acceptable and flagged, but still un-resolved after the fidelity pass that resolved #6/#7. *Fix:* none required; carry forward as a known assumption.
- **[low]** Featured bydraes (§6) specifies "collapse / no placeholder" but no explicit copy for a genuinely-empty editor's-choice feed (the "Nog niks op hierdie rak nie" empty is scoped to Biblioteek/Opleiding, EXPERIENCE:131). *Fix:* confirm whether the home featured section hides entirely or shows an empty line when the feed is empty.

## 5. Visual reference coverage — adequate

No `mockups/`, `wireframes/`, `imports/`, or `.working/` directories exist in the run folder (confirmed). The decision log (2026-06-16 + 2026-07-19) states clearly that no mocks were rendered and that the Lovable source + fidelity spec are the authoritative reference; the fidelity spec's sources block names `ink-lovable @ 5618f39` and the two comparison PNGs (`docs/theme-redevelopment/design-lovable.png` + `design-staging.png`, both present). That reference basis is adequately stated in the decision log and fidelity spec.

### Findings
- **[medium]** EXPERIENCE.md:74 still reads "→ Composition reference: mocks in `mockups/` (rendered at finalize for reference-ready surfaces). Spine wins on conflict." — an **orphan reference**: `mockups/` does not exist and no mocks were rendered (contradicting the decision log). The IA "Readiness" column values ("reference-ready", "layout-reference") reinforce the impression of mocks that do not exist. A downstream consumer following that pointer finds nothing. *Fix:* replace the `mockups/` pointer with the real reference basis (Lovable source `@5618f39` + the two theme-redevelopment PNGs + theme-fidelity-spec.md), matching the decision log.

## 6. Bloat & overspecification — strong

The fidelity spec's pixel-level detail is justified, not bloat: it exists precisely because tokens-only fidelity failed (decision log root-cause 2026-07-19), and it consistently expresses values as token slugs / `color-mix()` alpha tints rather than hardcoded hex (token-note #2), keeping theme.json the source of truth. Tables are used where tables belong (§9 button sizes/variants, IA surfaces, State Patterns).

### Findings
- **[low]** Button sizing (sm/default/lg/xl → 36/40/44/48px) and card radii are restated in both DESIGN.md Components "Grounded visual notes" and fidelity §9/§3/§5. Duplication is mild and mitigated by DESIGN.md explicitly delegating exact values to the spec ("exact values in theme-fidelity-spec.md"), so the principle/contract split is deliberate. *Fix:* none required; keep DESIGN.md to principles, let §9 own the numbers.

## 7. Inheritance discipline — strong

Domain glossary is verbatim-consistent across all three files (Gradering; brons/silwer/goud/Meester; uitdaging/bydrae/reaksie/volgeling/plaas). Component names are identical across sections and files (Winner banner, Gradering indicator, Gemeenskapsreaksies, Terugvoer van die moderator). Meester = `{colors.primary}` (never danger) holds across DESIGN.md, EXPERIENCE.md, and the theme.json `css` string (`.ink-gradering--meester{color:…primary}`). EXPERIENCE.md's few appearance references (`body-prose` 18/1.7, content 768) resolve to DESIGN.md frontmatter. Responsive max-width 1400 / gutter 16 reconciles with `spacing.wide`.

### Findings
- **[low]** theme-fidelity-spec.md sources use absolute machine-specific paths (`/Users/cobus/Development/ink-lovable`) rather than the `{planning_artifacts}`/`{project_knowledge}` placeholder convention the spines use. Resolvable for this operator, but not portable to another machine/consumer. *Fix:* express the Lovable source as a repo-relative or placeholder reference plus the pinned commit `@5618f39`.

## 8. Shape fit — strong

DESIGN.md follows the canonical section order (Brand & Style → Colors → Typography → Layout & Spacing → Elevation & Depth → Shapes → Components → Do's & Don'ts), matching the editorial example. EXPERIENCE.md carries all required defaults and earns its extensions (Admin Surfaces, THE CONFLATION RULE, Concerns, Open Questions). The fidelity spec is well-formed (frontmatter + §0 foundations → §11 data gaps + token notes + story slicing) and consistent with the spines — it explicitly names which `[ASSUMPTION]`s it supersedes (#6, #7).

### Findings
- **[medium]** DESIGN.md and EXPERIENCE.md are `status: final` and both list `theme-fidelity-spec.md` in their sources, but the fidelity spec is `status: draft` (pending Cobus sign-off per decision log 2026-07-19). A final spine inheriting load-bearing values from a not-yet-ratified draft means a downstream consumer extracting from the "final" spines may treat draft-authority values (breakpoints, per-component pixels) as ratified. *Fix:* either promote the spec to `final` on sign-off before downstream consumption, or annotate the spine references as "(draft — values provisional until Epic 19 sign-off)".

## Mechanical notes

- theme.json color slugs confirmed present: primary, primary-light, secondary, accent, accent-light, surface, surface-alt, text, muted-text, success, warning, danger, border, highlight, highlight-foreground, gold-muted. **Absent:** gold, goud, brons, silwer, and fontSizes 4xl / 5xl.
- theme.json custom radius scale + `css` string present; the `css` string styles `.ink-gradering` / `.ink-wenner-banier` using `primary` for Meester/algehele and references no brons/silwer/goud colour tokens (consistent with those tokens not yet existing).
- Run folder contains only: `.decision-log.md`, `DESIGN.md`, `EXPERIENCE.md`, `theme-fidelity-spec.md`. No `mockups/`, `wireframes/`, `imports/`, `.working/`.
- Companion files referenced by the fidelity spec that DO resolve: `docs/theme-fidelity-rework-plan.md`, `docs/theme-redevelopment/design-lovable.png`, `docs/theme-redevelopment/design-staging.png`.
- Finding counts: 0 critical · 1 high · 4 medium · 8 low.
