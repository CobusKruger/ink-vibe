---
baseline_commit: (Story 11.4 commit)
---

# Story 11.5: Community contribution CTA

Status: done

## Story

As a skrywer,
I want to contribute a guide,
so that the community can share craft knowledge. (FR-56)

## Acceptance Criteria

**Given** the hub
**When** I act on "Plaas 'n stuk"
**Then** I can contribute a community-written guide.

1. The Opleiding hub carries a closing call-to-action — H2 **"Het jy iets om te deel?"**, the descriptive line **"Die opleidingsafdeling word deur ons gemeenskap geskryf. As jy 'n skryfkunsessay of 'n gids wil bydra, sal ons dit graag wil lees."**, and a **"Plaas 'n stuk"** button.
2. The "Plaas 'n stuk" button links to the contribution entry point — a **filterable** URL (`ink/opleiding_bydra_url`) defaulting to the existing Skryf flow (`/skryf/`), so the destination is a single retarget point as the guide-submission type is wired.
3. The CTA is a server-rendered block (`ink/opleiding-bydra`) embedded on the hub via the theme pattern — three-layer-clean (no logic in the theme). Pure `toHtml( string $url )` so the markup is unit-testable.
4. All copy is the already-authored Afrikaans from `ui-copy-translations.md` (glossary-consistent `__()` source) — copy-debt to ratify; no AI Afrikaans, nothing English leaks. Open, conflation-clean, server-rendered.
5. **Out of scope (documented):** making `opleiding_artikel` a first-class submittable type inside the Epic-6 Skryf pipeline (moderation/publish of community guides). This story delivers the CTA + routing entry point; the guide-authoring pipeline reuses the Epic-6 machinery in a follow-on and is the single filter retarget.

## Tasks / Subtasks

- [x] Task 1: `Training\ContributionCta` server block (AC: #1–#4)
  - [x] `wp-content/plugins/ink-core/src/Training/ContributionCta.php` — `BLOCK = 'ink/opleiding-bydra'`, `URL_FILTER = 'ink/opleiding_bydra_url'`. `contributionUrl()` = `apply_filters( URL_FILTER, home_url( '/skryf/' ) )` (filterable, no Submission dependency). Pure `toHtml( string $url )` (H2 + description + "Plaas 'n stuk" button). `render()` → `toHtml( contributionUrl() )`.
  - [x] Registered in `Training\Module::register()` alongside `Hub` + `RelatedTraining`.
- [x] Task 2: Theme embed (AC: #3)
  - [x] `<!-- wp:ink/opleiding-bydra /-->` added as the closing block in `patterns/opleiding.php` (after the hub block).
- [x] Task 3: Tests (AC: all)
  - [x] `tests/Unit/Training/ContributionCtaTest.php` (4 tests) — `toHtml` heading/description/button + href, esc_url is the escape point; `contributionUrl` `/skryf/` default + honours the `ink/opleiding_bydra_url` filter.
  - [x] `OpleidingTemplateTest` — the hub pattern embeds `wp:ink/opleiding-bydra`.
- [x] Task 4: Gates — `composer test:unit` 751 passed / 1 skipped (+4); `stan` OK; `cs` clean; `copy:scan` 6/6 baseline; `deptrac` 3 pre-existing, 0 new.

## Dev Notes

- **CTA is a routing entry point, not a new submission pipeline** [Source: epics.md#Story 11.5; ui-copy-translations.md:206-208]: the design treats this as a closing "Plaas 'n stuk" button. The button targets a filterable URL defaulting to the Epic-6 Skryf flow (`home_url('/skryf/')`, the established contribution surface — see `SubmissionForm::formUrl`). Wiring `opleiding_artikel` as a first-class Skryf submittable type (so a guide is authored + moderated through the flow) reuses the Epic-6 machinery and is the documented follow-on; this story ships the CTA + the single retarget point.
- **No Submission dependency** [Source: deptrac.yaml]: use `home_url('/skryf/')` + `apply_filters`, NOT `Submission\Api`, so `Training` stays `Kernel + Content` (no new edge). The filter `ink/opleiding_bydra_url` is the seam.
- **Authored copy** [Source: ui-copy-translations.md:206-208]: "Het jy iets om te deel?", the description, and "Plaas 'n stuk" are already in the UI-copy doc — copy-debt to ratify, not new placeholders (copy:scan stays 6/6). Route through `__()` / `esc_html__()` in `ink-core` (server block, not a theme literal).
- **Pure toHtml** [Source: the block house style]: `toHtml( string $url )` takes the resolved URL so the markup is testable without WP; `render()` resolves the URL and delegates.

### Project Structure Notes

- NEW: `wp-content/plugins/ink-core/src/Training/ContributionCta.php`, `tests/Unit/Training/ContributionCtaTest.php`.
- MOD: `Training\Module` (register the block); `patterns/opleiding.php` (embed); `OpleidingTemplateTest.php` (embed guardrail); `sprint-status.yaml`.
- No new deptrac edges; conflation-clean.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 11.5 (FR-56)]
- [Source: docs/ui-copy-translations.md:202-208 (Sluitende oproep tot aksie — opleiding)]
- [Source: wp-content/plugins/ink-core/src/Submission/SubmissionForm.php:419-422 (the /skryf/ contribution URL)]
- [Source: wp-content/themes/ink-foundation/patterns/opleiding.php (hub embed point)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 11)

### Debug Log References

- `composer stan` runs sandbox-disabled (PHPStan TCP socket EPERM); OK.

### Completion Notes List

- **`ink/opleiding-bydra` CTA block** renders the closing "Plaas 'n stuk" call to action (H2 "Het jy iets om te deel?" + the community-written description + the button). `contributionUrl()` resolves a filterable URL (`ink/opleiding_bydra_url`) defaulting to the Epic-6 Skryf flow (`home_url('/skryf/')`) — deliberately `home_url` + a filter rather than `Submission\Api`, so `Training` stays `Kernel + Content` (no new deptrac edge). `toHtml()` is pure (takes the resolved URL).
- **Embedded on the hub** via `patterns/opleiding.php` (after the listing block) — three-layer-clean.
- **Scope note (documented in AC #5):** this delivers the CTA + the single retarget point. Making `opleiding_artikel` a first-class Skryf submittable type (authoring + moderating community guides) reuses the Epic-6 machinery and is the follow-on — flip the `ink/opleiding_bydra_url` filter / extend the Skryf submittable types when that lands.
- **Authored copy** (ui-copy-translations.md) — copy-debt to ratify, not new placeholders (copy:scan 6/6).
- **Tests:** +4 (toHtml + filterable URL). Suite 747→751.

### File List

- `wp-content/plugins/ink-core/src/Training/ContributionCta.php` (NEW — `ink/opleiding-bydra` CTA block)
- `wp-content/plugins/ink-core/src/Training/Module.php` (MOD — register ContributionCta)
- `wp-content/themes/ink-foundation/patterns/opleiding.php` (MOD — embed CTA)
- `tests/Unit/Training/ContributionCtaTest.php` (NEW)
- `tests/Unit/Training/OpleidingTemplateTest.php` (MOD — CTA embed guardrail)
- `_bmad-output/implementation-artifacts/11-5-community-contribution-cta.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 11.5 status)
