---
baseline_commit: 81e7e2b695324e4d767af58663e6da19fa971ca9
---

# Story 5.4: Gradering display on profiles

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

> **Build-order note:** the real profile templates (public Skrywerprofiel + private My Profiel) are **Story 9.4** (Epic 9 — custom profile templates, not yet built). Following the Epic-4 precedent (4.5 renewal section + 4.7 status copy ship the reusable presenter/bridge; 9.4 embeds them), this story ships the **reusable Gradering badge** (ink-core presenter + theme bridge + token CSS) and 9.4 places it on the actual profile templates.

## Story

As a reader,
I want to see a writer's Gradering on their profile,
so that I understand their standing. (FR-14)

## Acceptance Criteria

1. **A writer's Gradering (Brons/Silwer/Goud/Meester) is exposed as an accessible badge for both the public Skrywerprofiel and the private My Profiel, with Meester in brand `primary #EA4015` (not `danger`) and never colour-only.** Given a writer, when their Gradering badge renders, then it shows the grade label (from the `Terms` registry, the single source) as TEXT paired with a visual mark — Meester uses the `primary` colour token (`var:preset|color|primary` = #EA4015, the existing theme token; NOT `danger`), and every grade conveys itself by text/icon, never colour alone (a11y / WCAG). The same badge is reusable on the public profile and the private My Profiel (Story 9.4 embeds it on both). _[Source: epics.md#Story-5.4 AC; afrikaans-terms.md line 72 (Meester = `primary #EA4015`, NOT gold/silver/bronze, NOT danger); src/I18n/Terms.php (grade labels `brons`/`silwer`/`goud`/`meester` already registered); wp-content/themes/ink-foundation/theme.json (`primary` #EA4015 + `danger` #EF4444 are distinct presets); architecture.md (Epic-9 owns the profile templates; the presenter/bridge precedent of Stories 4.5/4.7)]_

2. **The display DATA comes from a tested ink-core presenter (reading the typed grade), and the markup from a thin, guarded theme bridge — three-layer clean, conflation-clean.** Given the three-layer rule, when the badge is produced, then `Ink\Tiers\Api::gradingView( int $user_id )` returns a typed `Ink\Tiers\GraderingView` (tier, the `Terms` label, `isMeester`, and a single-source CSS modifier) read via `Api::forUser()` (Story 5.1); and a `function_exists`-guarded theme bridge `ink_foundation_gradering_badge()` renders the accessible markup with token-only styling (Gate A), degrading gracefully when ink-core is inactive. The presenter references only the Kernel `Tier` + `Ink\I18n\Terms` — zero `Ink\Entitlement` (THE conflation rule); the theme carries NO business logic (it asks the Api for the view). _[Source: project-context.md ("No business logic in the theme"; "Controlled-vocabulary UI labels come from the ink-core terminology registry"; "No hardcoded colours … everything maps to theme.json tokens"; conflation rule); wp-content/themes/ink-foundation/functions.php (the `ink_foundation_term`/`ink_foundation_membership_plans` guarded-bridge precedent); src/Tiers/Api.php (5.1 `forUser`); src/Entitlement/PlanPresenter.php (the ink-core presenter precedent)]_

3. **WP-house-rules + escaping + Afrikaans + authored AND PASSING tests.** Given the project rules, when this story is built, then: the new ink-core `.php` keeps strict types / namespace / ABSPATH guard / PascalCase / camelCase; the theme bridge escapes all output (`esc_html`/`esc_attr`) and is `function_exists`-guarded; the badge CSS is token-only (no hardcoded colour) and lives in the theme (theme.json `styles.css` or a registered style); labels come from `Terms` (no bare Afrikaans literals). Pest unit tests for the presenter are authored at `tests/Unit/Tiers/` and **run with `composer test:unit`; the full suite passes before done** (baseline 318 passed / 1 skipped — zero regressions). The theme bridge render is E2E-verified later (Story 9.4 / 18.8), per the 4.4/4.5 bridge precedent. `composer cs` (ink-core + theme) / `stan` / `deptrac` run and recorded; deptrac green, no new `Tiers` edge. _[Source: project-context.md (strict types, escape-on-output, token-only, single-source labels, **testing rule 2026-06-22**; bridges are E2E-tested); architecture.md AD-8; phpcs.xml (scans theme PHP too)]_

## Tasks / Subtasks

> **Current state (read before starting):**
> - **`Api::forUser()` (5.1)** returns the typed grade; **`Ink\I18n\Terms`** already registers the grade labels (`gradering`/`brons`/`silwer`/`goud`/`meester`). Reuse both.
> - **theme.json** already defines `primary` (#EA4015) and a SEPARATE `danger` (#EF4444) — use `primary` for Meester. It is schema v3 (supports a top-level `styles.css` global-CSS string).
> - **The profile templates are Story 9.4 (not built).** This story ships the reusable badge (presenter + bridge + CSS); 9.4 embeds it. Do NOT create profile templates here.
> - **Theme bridges** live in `functions.php`, `function_exists`-guarded, calling the ink-core Api/`ink_foundation_term`. Mirror `ink_foundation_membership_plans`.
> - **Deptrac `Tiers: [Kernel]`**; `Ink\I18n` is uncovered (Terms already used cross-module) — no new tracked edge.
>
> **Scope is the REUSABLE Gradering BADGE only.** Do NOT build: the profile templates (9.4), discovery filters / winner labels (5.5), the wins-needed subtext (5.9), or any My-Profiel page. Ship the presenter + bridge + token CSS.

- [x] **Task 1 — `Ink\Tiers\GraderingView` + `Api::gradingView()` (AC: 1, 2)**
  - [x] New `final` readonly `GraderingView` (tier, label, isMeester) + `cssModifier()` (= `$tier->value`) + `colorToken()` (`'primary'` for Meester, else `$tier->value`).
  - [x] `Api::gradingView()` reads `forUser()` and builds the view with `Terms::label()`.
- [x] **Task 2 — Theme bridge `ink_foundation_gradering_badge()` (AC: 1, 2, 3)**
  - [x] `function_exists`-guarded bridge in `functions.php` renders the accessible badge (text label always present + `aria-hidden` mark `★`), `class_exists`-guarded to `''` when ink-core is inactive; all output escaped.
- [x] **Task 3 — Token-only badge CSS (AC: 1, 3)**
  - [x] Added `.ink-gradering`/`.ink-gradering__mark`/`.ink-gradering--meester` to theme.json `styles.css` — token-only (Meester → `var(--wp--preset--color--primary)`, no hardcoded hex).
- [x] **Task 4 — Author AND run the Pest tests; record the gates (AC: 3)**
  - [x] `tests/Unit/Tiers/GraderingViewTest.php` (7 tests across the dataset + isMeester + colorToken + unset-defaults-Brons).
  - [x] `composer test:unit` → **325 passed / 1 skipped** (1430 assertions), zero regressions. `composer cs` (ink-core + theme) clean. `composer stan` clean (sandbox-off). `composer deptrac` → 3 pre-existing only, no new `Tiers` edge. theme.json validated as JSON.

## Dev Notes

- **Presenter vs bridge:** the TESTED unit is the ink-core `GraderingView`/`Api::gradingView()` (label + Meester-is-special encoded once); the theme bridge is thin markup (E2E-verified at 9.4/18.8, exactly like `ink_foundation_membership_plans`). This keeps the Meester=primary rule single-source in ink-core, not hardcoded in the theme.
- **a11y:** the badge always renders the text label; the visual mark is `aria-hidden`. No grade is distinguished by colour alone — satisfies the AC's "no colour-only encoding".
- **Meester colour:** `primary` (#EA4015), explicitly NOT `danger` (#EF4444) — both exist as distinct theme tokens; the presenter's `colorToken()` returns `'primary'` for Meester so the theme maps it to `var(--wp--preset--color--primary)`.
- **Conflation rule:** the presenter reads only the grade (Kernel `Tier`) + `Terms`; never entitlement. A writer's displayed grade is independent of membership.

### Project Structure Notes

- New: `src/Tiers/GraderingView.php`; test `tests/Unit/Tiers/GraderingViewTest.php`. UPDATE: `src/Tiers/Api.php` (`gradingView()`), `wp-content/themes/ink-foundation/functions.php` (bridge), `wp-content/themes/ink-foundation/theme.json` (badge CSS).
- Profile-template placement is Story 9.4 (deferred, documented).

### References

- [Source: epics.md#Story-5.4]
- [Source: afrikaans-terms.md line 72 (Meester primary #EA4015, not danger)]
- [Source: src/Tiers/Api.php (forUser), src/I18n/Terms.php (grade labels), src/Entitlement/PlanPresenter.php (presenter precedent)]
- [Source: wp-content/themes/ink-foundation/functions.php (bridge precedent), theme.json (primary/danger tokens, v3 styles.css)]
- [Source: project-context.md (three-layer, token-only, single-source labels, conflation, testing rule); deptrac.yaml]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop)

### Debug Log References

- `composer test:unit` → 325 passed / 1 skipped (1430 assertions).
- `composer cs` (GraderingView.php, Api.php, functions.php) → clean.
- `composer stan` → No errors (sandbox-off). theme.json validated as JSON.
- `composer deptrac` → 3 pre-existing `Activation → PostTypes`; no new `Tiers` edge.

### Completion Notes List

- **Reusable badge, not a profile template.** The real Skrywerprofiel / My Profiel templates are Story 9.4 (not built); this ships the badge (tested ink-core presenter + thin theme bridge + token CSS) that 9.4 embeds — the same presenter/bridge split Epic 4 used (4.5/4.7 → 9.4).
- **Meester-is-special encoded once** in `GraderingView::colorToken()` (`'primary'` for Meester, the grade value otherwise) — the theme maps `.ink-gradering--meester` to `var(--wp--preset--color--primary)` (#EA4015, explicitly NOT `danger`). a11y: the text label always renders; the star mark is `aria-hidden` — no colour-only encoding.
- **Three-layer clean:** display DATA in ink-core (tested), markup in a guarded theme bridge (E2E at 9.4/18.8). The theme computes nothing; labels come from `Terms` (no bare literals); CSS is token-only (Gate A).
- **Conflation-clean:** presenter reads only Kernel `Tier` + `Terms`; zero `Ink\Entitlement`.

### File List

- `wp-content/plugins/ink-core/src/Tiers/GraderingView.php` (NEW)
- `wp-content/plugins/ink-core/src/Tiers/Api.php` (UPDATE — `gradingView()`)
- `wp-content/themes/ink-foundation/functions.php` (UPDATE — `ink_foundation_gradering_badge()` bridge)
- `wp-content/themes/ink-foundation/theme.json` (UPDATE — token-only `.ink-gradering` styles.css)
- `tests/Unit/Tiers/GraderingViewTest.php` (NEW)

### Change Log

- 2026-06-26 — Story 5.4 implemented (create-story → dev-story). Reusable Gradering badge: `GraderingView` presenter + `Api::gradingView()` + guarded theme bridge + token CSS (Meester → primary #EA4015, a11y text+mark). Template placement deferred to 9.4. 325 passed / 1 skipped; cs/stan clean; deptrac no new edge. Status → review.
