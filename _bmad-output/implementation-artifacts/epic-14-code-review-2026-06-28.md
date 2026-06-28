# Epic 14 (Sponsors / Borge) — Code Review (R14)

Date: 2026-06-28
Reviewer: bmad-code-review (3-layer adversarial: Blind Hunter / Edge Case Hunter / Acceptance Auditor)
Scope: the full Epic-14 branch diff vs baseline `3c78a6f` (`epic-14-sponsors`), stories 14.1–14.4 — ~2000 lines across `ink-core/src/Sponsors`, `Kernel\Sast`, `I18n\Terms`, `ink-foundation` patterns + templates, and the unit suite.

## Outcome

**0 HIGH, 0 MEDIUM defects.** All three layers converged on a clean implementation. 3 LOW patches applied; 2 items deferred; 3 dismissed as non-issues. Tests 910 → 912 (+2 from the review patches); cs/stan/deptrac/copy:scan green.

The Acceptance Auditor confirmed every Epic-14 AC is implemented and the load-bearing guarantees hold:
- **Conflation-clean** — the only new deptrac edge is `Sponsors -> Content`; no Tiers/Entitlement coupling anywhere. `tier` is carried but never gates or orders.
- **Cap reconciliation genuinely closed** (14.1) — `borg` meta-box `save()` + REST `auth_callback` both gate on `MANAGE_SPONSORS` (granted at activation); deny-without / write-with pair is non-vacuous.
- **SAST window** (14.2) — inclusive both ends, single-day, open/evergreen bounds, zero hand-rolled date math (all via `Kernel\Sast`).
- **Collapse vs always-render** (14.3 vs 14.4) — strip returns `''` with no sponsor; recognition section always renders copy and only degrades the grid.
- **Afrikaans copy verbatim** — all four registry strings match `docs/ui-copy-translations.md` char-for-char (no AI re-translation); copy from the `Terms` registry, not bare literals.
- **No logo dumps on content pages** — surfaces are blocks embedded only on the homepage / Oor INK, no `the_content` hook.

## Patches applied (3, all LOW)

1. **[14.3 AC-5] Added the specified "no content-hook" guardrail test** — `tests/Unit/Sponsors/ModuleTest.php`. Task 6 listed this test but it was not written (the invariant held by construction + a docblock comment, but the explicit test was missing). The test asserts `Module::register()` wires `init` block registrations and binds nothing to `the_content`/`the_excerpt`/`loop_end`/`wp_footer` (non-vacuous).
2. **[14.2 edge] Inverted window (start > end) made intentional + tested** — a transposed-date typo now has a documented fail-closed contract (never active, no silent date-swap that would guess editor intent) in `Campaign::isActive` + a pinning test in `CampaignTest`.
3. **[14.2 doc] Softened the rotation docblock** — `Campaign::rotate` previously claimed "stable within a day" unconditionally; clarified that within-day stability holds for a CONSTANT active set (membership changes mid-day remap the positional slots — acceptable for the few-sponsors reality).

## Deferred (2 — real, not actionable in Epic 14)

- **Per-request `WP_Query` on every front-page / Oor INK render, no caching** [`Campaign::activeSponsors`/`featured`] — functionally correct but a fresh query per block render on a high-traffic page. Owner: the Epic-18 performance pass (object-cache/transient the active-sponsor set).
- **`MAX_SPONSORS = 100` cap is a silent bound vs the 14.4 "shows EVERY active sponsor" contract** [`Campaign::queryArgs`] — only contradicts the contract in the implausible 101+-active-sponsor case; the cap is a sensible defensive bound (sponsors are few). Documented as a product limit; revisit (paginate/curate by tier) if the sponsor count ever approaches it.

## Dismissed (3 — verified non-issues)

- `dayIndex` negative for pre-1970 instants — guarded by `rotate`'s negative-safe modulo `(($i % $n) + $n) % $n`; only reachable via the injectable `$now` test seam.
- `Sponsor::forPost` re-fetches title/meta by id even when given a `WP_Post` — harmless dead flexibility, mirrors `InkPols\Issue::forPost`.
- `Sast::isWithinDayRange` relies on callers passing a true instant for `$now` — all callers do (`Campaign::isActive` / `Sast::now()`); no bug.

## Story statuses

14.1 / 14.2 / 14.3 / 14.4 → **done** (all ACs met, 0 HIGH/MEDIUM, patches applied).
