# Epic 13 — Code Review (InkPols)

**Date:** 2026-06-28
**Scope:** `main..epic-13-inkpols` — all 4 stories (13.1–13.4). New `Ink\InkPols` module (Issue, Api, Module, Archive, SingleIssue, Viewer, Migration) + `I18n\Terms` (2 brand/button keys), `ink-core.php` (module registration), `deptrac.yaml` (InkPols layer + edge), theme templates/patterns (archive + single + reading + viewer).
**Method:** 3-layer adversarial review — Blind Hunter (diff-only), Edge Case Hunter (diff + project read), Acceptance Auditor (diff vs the four story ACs + project-context rules).

## Outcome

Acceptance Auditor: **all 4 stories' core ACs met.** THE conflation rule preserved throughout — InkPols carries zero `Ink\Tiers`/`Ink\Entitlement`; the only deptrac edges are `InkPols → Kernel` + `InkPols → Content` (slug/meta registry reads, mirroring Library/Challenges→Content). Three-layer separation clean (theme is presentation-only; all logic in the `ink/inkpols-*` server blocks), i18n + escape-on-output clean, Afrikaans labels single-sourced from the glossary (no AI Afrikaans, no new copy debt).

The three layers converged: no HIGH defects survived in production behaviour, and the one HIGH the Edge hunter raised (invalid-but-well-shaped dates handled inconsistently) was real and is fixed. **7 patches applied (R13); items deferred to Epic 16/17; remainder dismissed as by-design/noise.**

## Patches applied (R13)

- **P1 (High) — well-shaped-but-invalid dates were handled inconsistently.** `Issue::year()` matched digit positions only, so `2026-02-30` / `2026-13-01` (both pass `FieldSets::sanitizeDate`, which checks shape only) grouped under a real year while `displayDate()`'s `strtotime` either rolled the day over or returned ''. Fixed: a single `Issue::normalisedDate()` trust point validates the calendar date with `checkdate()`; `year()` and `displayDate()` both consume it, so an invalid date is treated as undated **consistently**.
- **P2 (Medium) — `Migration` overcounted `created` on a `--force` reconcile.** `run()` did `++$created` for every non-zero id, so a reconcile (existing issue matched by the source marker) was reported as "geskep". Fixed: `ensureIssue()` now returns `{id, created}`; `run()` counts `created` (new inserts) vs `reconciled` separately, and the CLI summary reports both — truthful per Story 13.4 AC4. (Note: the 12.8 `Challenges\Migration` has the same latent over-count — carried to the retro as a cross-module follow-up.)
- **P3 (Medium) — `issueDateFromName` parser was fragile.** Substring month matching (false positives), first-in-array-order month wins (not first-by-position), and any 4-digit run treated as a year (a volume/issue number misread as a year). Fixed: whole-word month match (`\b…\b`), pick the month appearing FIRST by string position, and require a plausible `19xx`/`20xx` year.
- **P4 (Medium) — same-month issues sorted unstably.** Migration sets every issue to a `Y-m-01` date, so same-month issues collide; `Archive::groupByYear`'s in-year `usort` had no tiebreak. Added a deterministic post-id-DESC secondary key.
- **P5 (Low) — `wp_set_script_translations` lacked the `$path` argument.** Without it the flipbook `.json` would only load from the default plugin path, not the committed `wp-content/languages/` (the Story 13.3 AC4 location). Added `WP_LANG_DIR` as the path.
- **P6 (Low) — `Viewer::shortcodeFor` used `esc_url` for a shortcode attribute.** `esc_url` entity-encodes `&` → `&#038;`, mangling a PDF URL with query args before the plugin's shortcode parser sees it. Switched to `esc_url_raw` (the correct non-HTML-output escaper).
- **P7 (Low) — `displayDate` timezone drift + unguarded `date_format`.** `strtotime` on a date-only string yielded server-local midnight, which `wp_date` could shift across a month boundary under a TZ offset; `(string) get_option('date_format')` could stringify a corrupt array option to `"Array"`. Fixed: anchor the timestamp at noon UTC, and read the format via `Scalar::asString`.

7 review-regression tests were added (IssueTest invalid-date consistency; ArchiveTest same-date stable sort; ViewerTest query-arg URL fidelity; MigrationTest plausible-year + whole-word + first-by-position + reconcile-count).

## Deferred (see deferred-work.md → "code review of Epic 13")

- **Real3D Flipbook `SHORTCODE_TAG`/`SCRIPT_HANDLE` reconciliation** against the actually-installed plugin version (the plugin is assembled at build time, not in-repo) — Story 13.3 already recorded this.
- **Flipbook JS `.json` Afrikaans authoring** on staging + commit to `wp-content/languages/` — the standing translation workflow (Epic 17). The guarded `wp_set_script_translations` wiring is in place.
- **`registerScriptTranslations` timing** — hooked at `wp_enqueue_scripts` priority 20 and gated on the handle already being registered; if the plugin enqueues its script later (e.g. during `do_shortcode`), the wiring is skipped. Cannot be resolved without the real plugin's handle/timing — reconcile alongside the deferred item above.
- **Legacy migration site-specific seams** (`legacyIssues()`/`pdfIdFor()`/`volumeFor()`) supplied at migration time — Epic 16 (the binding migration sequence runs InkPols after posts+redirects). Story 13.4 already recorded this.
- **`findIssueForLegacy` real `get_posts`/meta-query path** is unit-stubbed (all `--force` tests override the seam) — integration coverage belongs to the wp-env layer (Epic 18.8).

## Dismissed (by design / not defects)

- **`volumeFor` defaults to the legacy name when no explicit volume exists.** The Auditor flagged this as re-introducing the month/year naming into a meta field. It is a documented, intentional fallback (Story 13.4 dev notes): a meaningful volume label is better than an empty one, and the structured *date* still replaces the naming for ordering/grouping.
- **Non-`WP_Post` query results are silently skipped** in `Archive::render`. The `instanceof WP_Post` guard is correct defensive behaviour; a filter that changes `WP_Query`'s return shape is out of scope.
- **`do_shortcode` output is concatenated unescaped** in `Viewer` — it is trusted first-party plugin HTML by design (the commodity-plugin "hook, don't reimplement" rule).
- **Output escaping, capability/nonce, SQL-injection, attachment-resolver guards, numeric-string array-key round-trip, plugin-absent fallback** — all clean. The only mutating path (`wp ink migrate-inkpols`) is correctly WP-CLI-gated and never registered on web requests.

## Gates (post-patch)

`composer test:unit` → 865 passed / 1 skipped (InkPols suite 54 tests); `composer cs` → 0 errors on the new files (repo-wide: only the documented pre-existing slow-query warnings); `composer stan` → No errors; `composer deptrac` → 3 PRE-EXISTING `Kernel\Activation → Content\PostTypes` violations only, no new edge; `composer copy:scan` → no new placeholder debt.
