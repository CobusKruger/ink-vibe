# Epic 16 (Migration & redirects) — Code Review (R16)

**Date:** 2026-06-28
**Branch:** `epic-16-migration` vs `main`
**Scope:** all 12 stories (16.1–16.12) — the `Ink\Migration` toolkit (5,382-line diff)
**Method:** 3-layer adversarial review — Blind Hunter (diff only), Edge Case Hunter (diff + repo), Acceptance Auditor (diff + epic/story ACs + project-context). All layers completed (none failed).

## Outcome

**0 HIGH unresolved · 6 patches applied · 0 deferred · 9 dismissed.** The implementation was found "unusually faithful to spec"; every named acceptance target was met. Two HIGH findings were genuine cross-command/runtime robustness bugs (run-order 301 loss; trashed-post redirect target) — both patched. Tests **1011 → 1014** after patches; phpcs 0 errors, phpstan OK, deptrac no new violation, copy:scan no new debt.

## Patches applied (R16)

| # | Sev | Finding | Source | Fix |
|---|-----|---------|--------|-----|
| R16-1 | HIGH | `PostReclassifier` ↔ `LibraryTrainingMigrator` overlap: a `/biblioteek/`·`/opleiding/` `post_type=post` post is in BOTH commands' result sets; whichever runs second re-records `SOURCE_URL_META` with the already-changed permalink → `RedirectGenerator` sees `old==new` → 301 silently lost; run order is unconstrained. | edge | `PostReclassifier` now **skips** flat posts under a kept prefix (`LibraryTrainingMigrator::cptForPath()` ≠ null) so the two commands are disjoint regardless of order; `recordSourceUrl()` made **write-once** in both classes (defence-in-depth against `--force`/sibling overwrite). |
| R16-2 | HIGH | `RedirectGenerator::build()` builds a bogus 301 target from a **trashed/draft** post's `?p=`/trash permalink. | edge | `currentPermalink()` returns `''` for any non-`publish` post → `buildRedirectMap()` drops it (no redirect). |
| R16-3 | MED | `ShortcodeCleanup::stripVcShortcodes` regex `\[\/?vc_[^\]]*\]` truncates a tag at a `]` inside a quoted attribute value (`[vc_btn title="a]b"]`), leaving orphaned text in **persisted** content. | blind+edge | Quote-aware regex `\[\/?vc_(?:[^\]"\']\|"[^"]*"\|'[^']*')*\]` tolerates `]` inside `"…"`/`'…'`. |
| R16-4 | LOW | `RedirectGenerator::maybeRedirect()` could hijack a **live** URL whose path matches a stale map key (no 404 guard). | edge | Added `isMissing()` (`is_404()`) seam; only rescue a request that would otherwise 404. |
| R16-5 | LOW | `OptionsCarryForward::filterCarryForward` casts a non-scalar legacy value to `"Array"` + a PHP notice. | edge | `is_scalar()` guard — non-scalar legacy values dropped. |
| R16-6 | LOW (test) | `DbSanitiserTest` "escapes each prefix" was vacuous on the escaping invariant (stubbed `query` ignored the SQL). | blind | Test now captures the query and asserts the `esc_like`-escaped `\_transient\_%` reaches it. |

New regression tests added for R16-1 (prefix exclusion), R16-3 (nested-bracket attr), R16-4 (live-URL not hijacked), R16-5 (non-scalar dropped), R16-6 (escaped LIKE asserted).

## Dismissed (with rationale)

- **TierImport imports `Meester` from CSV** — the tier spreadsheet is the *authoritative* source of a writer's current grade; an explicit `meester` cell is not a *guess* (the "never guess a higher grade" rule governs the unrecognised→`brons` default path). Importing it is required so Meester writers aren't downgraded. `Tier::isManualOnly` only constrains the *auto-promotion engine*, not a staff-maintained import.
- **TierImport duplicate-email last-write-wins** — the CSV is a staff-curated one-off; dedup-flagging adds complexity without value.
- **NavigationRebuilder matches nav by title** — that *is* the get-or-create key by design; on the fresh migration target there is no differently-titled nav to miss.
- **Partial-failure has no per-item resume** — matches the established once-off pattern (`Challenges\Migration`, `InkPols\Migration`); a full restart is safe (every op is idempotent: `set_role`/`set_post_type`/`update_option`/`FollowStore::follow` dedup/term get-or-create; the destructive deletes/trims re-run harmlessly).
- **`buildRedirectMap` normalise-collision (last wins)** — recorded *permalinks* don't collide on query-string/trailing-slash-only differences.
- **FollowGraphMigration count semantics (pending=friendships, orphaned=pairs) + `--force` recount** — reporting-only; the graph is correct (unique-edge table; `ON DUPLICATE KEY UPDATE`).
- **Raw SQL table-name interpolation without `prepare()`** (`DbSanitiser::deleteOrphanLogs`, `FollowGraphMigration::friendships`) — table names derive from `$wpdb->prefix`, no user input; matches the `FollowStore` house pattern; phpcs-annotated. The rule's intent (no untrusted interpolation) holds.
- **16.4 "access rules" not confirmed by code** — intentionally deferred to manual QA per story 16.4 AC #3 / PRD MR-5.
- **16.1 idempotency-flag note** — documented once-off + `--force` design; no change required.

## Gate results (post-patch)

- `composer test:unit` → **1014 passed**, 1 skipped (3850 assertions)
- `composer cs` → 0 errors
- `composer stan` → No errors
- `composer deptrac` → 3 pre-existing `Kernel\Activation → Content` violations only (untouched); Migration edges (Kernel/Tiers/Content/Social) all declared
- `composer copy:scan` → no new debt (baseline 8)
