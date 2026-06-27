# Epic 11 (Training / Opleiding) — Code Review

Date: 2026-06-27
Reviewer: adversarial 3-layer review (Blind Hunter · Edge Case Hunter · Acceptance Auditor) over the full epic diff `3e8a24c..HEAD`.
Scope: Stories 11.1–11.5 + the Epic-10 carry-forward `ArchiveRender` extraction (and the Library/Discovery refactor it touched).

## Layer verdicts

- **Blind Hunter (correctness):** No bugs. The `ArchiveRender` refactor is behaviour-preserving (no leftover calls to deleted privates, no double-escape — every `pill()` call site now passes a RAW url and `pill()` self-escapes); WP_Query arg shapes, the OR `tax_query` relation logic, escaping, and the featured-strip gating all verified correct.
- **Acceptance Auditor:** All 5 stories' ACs MET; all project invariants held — THE conflation rule (zero Tiers/Entitlement in `Training`), three-layer separation (no logic in theme), Afrikaans-first (authored `__()` copy, bridge labels, copy:scan 6/6), shared-taxonomy surfacing not manual linking (FR-55), CPT/taxonomy code-id discipline. No overclaims in the story completion notes.
- **Edge Case Hunter:** 3 actionable hardening items (below) + several already-handled / safe-degrade cases (array GET inputs → safe absint(0)→clamp; WP_Error from get_terms → is_array guard; post_id≤0 guard; etc.).

## Triage → patches applied (R11)

| ID | Sev | Finding | Fix |
|----|-----|---------|-----|
| R11-1 | HIGH (latent) | `RelatedTraining::queryArgs([],[],…)` built no `tax_query` → a direct caller would match ALL `opleiding_artikel`, violating FR-55 in the pure layer. (Safe today — `relatedFor` guards before calling.) | When no clauses, set `post__in => [0]` (match nothing). The "shares no term → surfaces nothing" invariant now holds in BOTH layers. + test. |
| R11-2 | MED | `ContributionCta::contributionUrl()` did `(string) apply_filters(...)` — a misbehaving filter returning null/empty → broken `href=""`; an object w/o `__toString` → fatal. | Validate the filter result is a non-empty string, else fall back to the `/skryf/` default. + test (null + empty). |
| R11-3 | LOW | `ArchiveRender::pagination()` didn't clamp `paged ≤ max_pages` — a hand-typed `?…_bladsy=999` rendered a prev link beyond range (inherited from the old Library/Discovery archives; fixing benefits all three). | Clamp `$paged = min($paged, $max_pages)` after the `>1` gate. + test. |

### Not patched (assessed, no action)
- `requestInt` array GET input → `absint([])`=0 → caller clamps to 1 (safe degrade); inherited idiom, unchanged.
- `requestKey`/`requestText` non-string → existing `is_string` guard returns '' (safe).
- `vaardigheidTerms`/`filterHtml` mixed/`WP_Error` term arrays → `is_array` + `instanceof WP_Term` guards already drop bad entries.

## Gates after patches

- `composer test:unit` — **754 passed / 1 skipped** (+3 R11 patch tests; 680→714 was Epic 10, 714→754 this epic = +40).
- `composer stan` — OK (sandbox-disabled; PHPStan TCP-socket EPERM).
- `composer cs` — clean.
- `composer copy:scan` — 6/6 baseline, no new placeholder debt.
- `composer deptrac` — 3 PRE-EXISTING `Kernel\Activation→Content` violations, **0 new** (`Training → Kernel,Content` allowed; `ArchiveRender` in Kernel adds no edge; Entitlement⟂Tiers untouched).

## Outcome

3 patches, 0 HIGH bugs shipped. Epic 11 is acceptance-ready.
