# Epic 12B — Code Review (R12B)

**Date:** 2026-06-29
**Reviewer:** bmad-code-review (3-layer adversarial: Blind Hunter / Edge Case Hunter / Acceptance Auditor)
**Scope:** Epic 12B (Annual competition management) — Story 12B.1, branch `epic-12b-annual-competition` vs `main`
**Diff baseline:** `d99cd0c` (HEAD of main at branch point)
**Spec:** `_bmad-output/implementation-artifacts/12b-1-annual-competition-management.md`

## Method

Three independent adversarial layers ran in parallel with no shared context:
- **Blind Hunter** — diff only, no spec/project access.
- **Edge Case Hunter** — diff + project read access; walks every branch/boundary.
- **Acceptance Auditor** — diff + spec + project-context; checks AC compliance + project rules.

## Verdict

**Two HIGH findings, raised independently by the Edge Case Hunter AND the Acceptance Auditor, were valid and have been FIXED.** Both concerned the same root issue: the cadence was modelled and configurable but **not reachable by any production code path**, so an annual round produced nothing observable, and the test that claimed to prove "pipeline reuse" was vacuous (it wired `Cadence::periodLabel → Tiers\Api::winnerLabel` only inside the test). After the fix the annual cadence is consumed by a real production surface (the winners-announcement title), and the reuse is proven through that production path.

All other findings are dismissed-with-rationale or deferred (one minor REST nicety + the pre-existing R12A D1 read-collapse). **0 unresolved HIGH/MEDIUM.** Status → **done**.

## Findings & triage

### HIGH — FIXED

- **[H1] Annual cadence stranded — no production consumer of the period** (`edge`+`auditor`). `Cadence::periodKey/periodLabel/periodKeyFor/periodLabelFor`/`forUitdaging` and `Tiers\Api::winnerLabel()` had zero callers in `src/`; the winners post titled from the uitdaging title, the banner from the rank label — none read the cadence. Marking a round `jaarliks` changed only stored meta. **The story's central AC ("when an annual competition runs … it reuses … on an annual cadence") was not actually reachable.**
  **Fix (R12B-P1):** `WinnersPost::generate()` now resolves the round's period (`roundPeriod()` → `Deadline::parse` + `Cadence::periodLabelFor`) and folds it into the announcement title via a period-aware `WinnersPost::composeTitle($title, $period)`. A monthly round publishes "Wenners: Desember 2026 — {tema}"; an annual round "Wenners: 2026 — {tema}". The cadence is now observable in the published artifact, and `periodLabelFor` has a real caller. No new authored copy (period is a month-name/numeral; the "Wenners:" frame + em-dash are existing/punctuation). `entriesFrozen` and the adjudication mechanics are untouched — still "no new core mechanics."

- **[H2] The "pipeline-reuse" test was vacuous** (`auditor`). `CadenceTest` "the annual period flows unchanged through the winner-label machinery" stitched `Cadence::periodLabel → Tiers\Api::winnerLabel` only in the test — a wiring no production code performed, so it could not fail.
  **Fix (R12B-P2):** removed that test; added `WinnersPostTest` "generate carries the round cadence period into the announcement title (annual reuse, production path)" which exercises the real `generate()` path and asserts the annual ("2026") vs monthly ("Desember 2026") title — non-vacuous, tests the OUTCOME we emit.

### LOW — FIXED

- **[L1] `select` render marked nothing selected for an out-of-set stored value** (`edge`). A legacy/junk cadence value rendered both options with no `selected`, so the browser silently showed the first option while the stored value disagreed.
  **Fix (R12B-P3):** `FieldSets::renderBox()` select branch now falls back to the first option key when the stored value is outside the option set, so the rendered selection matches the effective (sanitiser-coerced) value. New test covers the junk→`maandeliks` fallback.

### MEDIUM — DEFERRED

- **[M1] REST write path is not enum-validated** (`edge`). `register_post_meta` advertises an unconstrained string; only the `sanitize_callback` (`sanitizeCadence`) enforces the value set. **Correctness is safe** — any REST-written value is coerced to `maandeliks`/`jaarliks` before storage — but the REST schema does not advertise the two valid values. **Deferred:** adding per-field REST `enum`/`schema` is a generic change to the shared `register()` loop, beyond 12B.1; the sanitiser is the enforcement. Tracked for a future FieldSets polish.

### LOW — DEFERRED (pre-existing, confirmed legitimate)

- **[D1] Per-`Gradering` read-collapse vs per-(Gradering × category) winners** (`auditor`, confirms the story's own flag). `Placements::arrange()/forRound()` collapse category winners to one-per-Gradering on the READ side; committed DATA is correct. Independent of cadence; 12B.1 neither worsens nor depends on it. Remains the standing R12A pre-launch follow-up.

### DISMISSED (with rationale)

- **save() cadence test stubs the cap check** (`blind`, MED) — the capability gate is already proven non-vacuously by the existing `FieldSetsTest` pair ("save denies an uitdaging meta write when the per-CPT cap is missing" / "…writes when both held"). The cadence save test verifies the sanitiser routing, not the cap gate; `save()`'s gate was simply not in the diff the Blind Hunter saw.
- **`YYYY` vs `YYYY-MM` key-space collision** (`edge`, MED) — provably disjoint by construction: an annual key matches `^\d{4}$`, a monthly key `^\d{4}-\d{2}$`; the two can never be string-equal. No namespacing needed.
- **`Scalar::asString`+`fromMeta` vs `sanitizeCadence` double-coerce** (`blind`, LOW) — both paths fold junk to monthly; the finding itself confirms current behaviour is safe. Stylistic.
- **render test couples to option insertion order** (`blind`, LOW) — insertion order is deterministic in PHP arrays and the option order is intentional (monthly first).
- **`fromMeta(2026)` int folds to default** (`blind`, LOW) — intended and tested; `mixed` non-string → default by design.
- **`select` with no `options` renders empty** (`edge`, LOW) — the `?? array()` guard already renders safely; only a future mis-declared field would hit it, not this story's well-formed field.
- **`''` vs `maandeliks` indistinguishable after save** (`edge`, LOW) — behaviourally identical (both resolve to monthly); no consumer distinguishes "unset" today.
- **"no new mechanics" framing / "should have been a blocking dependency"** (`auditor`, MED ×2) — both resolved by the H1 fix: there is now a real consumer, so the AC is met without adding adjudication mechanics.

## Gates (post-fix)

- `composer test:unit` — **1201 passed**, 1 skipped (baseline 1184; +17 net across the story incl. review fixes).
- `composer cs` — all changed files clean (3 pre-existing findings remain in untouched `IngestionPage.php`/`ResponseStore.php`/`SuggestedReads.php` — flagged below, not 12B.1).
- `composer stan` — OK.
- `composer deptrac` — 3 pre-existing violations only (`Kernel\Activation`→`Content`); Allowed 595→599 (the new `WinnersPost`→`Cadence`/`Content` edges are permitted). **No new edge.**
- `composer copy:scan` — no new placeholder debt (8 known).

## Note for the epic retro (pre-existing, out of 12B.1 scope)

`composer cs` reports a real pre-existing ERROR at `Challenges/IngestionPage.php:368` (`$_POST['bevestig']` flagged non-sanitised) plus two slow-query warnings (`ResponseStore`/`SuggestedReads`) — all in files byte-identical to `main`. These predate Epic 12B and were not introduced here; recommend a follow-up cs-cleanup pass.
