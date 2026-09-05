# Theme Fidelity Audit — Handoff to a Fresh Agent

**Read this file in full before doing anything, then read `docs/theme-fidelity-rework-plan.md` for the
full page-by-page detail (commit hashes, per-page findings).** This file is deliberately short — status
and method only. Don't pad it back out.

## 1. Current status (2026-09-03) — read this part skeptically

All 16 pages in `docs/design-handoff/page-map.csv` have been through a getComputedStyle()-based
audit-and-fix pass and are marked **done** in the plan doc's tracking table, each with an independent
orchestrator spot-check before being marked done.

**Despite that, the product owner reviewed the live site against Lovable directly and believes real
differences still remain, after three days of this effort.** Treat that as true, not as something to
argue with or re-litigate from the property-diff evidence already gathered — a method that took three days
and still doesn't hold up to a human looking at both sites side by side has a real blind spot. The most
likely gap: **every page in this pass was verified through `getComputedStyle()` diffs on individually
matched elements — never through an actual side-by-side visual comparison.** That check can hide:

- Composition/rhythm problems (spacing *between* elements, overall visual balance) that no single
  element's property diff would ever surface.
- Wrong element matched in the first place — the established "find by text, pick the node with fewest
  descendants" heuristic (used throughout this pass, see plan doc) is a guess, not a guarantee, in a React
  app with no stable class names.
- Anything that isn't a CSS property at all: image/icon assets, actual copy layout, real content
  differences.

**Next action: the product owner is providing a document listing every remaining difference they see**
from their own direct comparison of the live site against Lovable. Wait for that document and work from
it — don't restart a from-scratch full-page audit pass in the meantime. Treat every "done" row in the plan
doc as unverified until the PO's list (or your own look) confirms it.

Use the `@browser` tool (not `mcp__claude-in-chrome__*`, which is not the current mechanism) for any
browser-based comparison work. Open both the Lovable reference and the local live site so pages can be
compared side by side — see §5 for URLs.

## 2. Method (what "checking" means here, condensed)

- Pull real `getComputedStyle()` values from **both** the live site and Lovable's live DOM — never
  eyeball a screenshot for a property value.
- Never trust a declared `font-family`. Check `document.fonts` on both sides — Lovable itself has been
  found silently falling back to Georgia despite declaring Lora, because it ships zero `@font-face`
  entries. Verify both sides independently, don't assume the reference renders what it claims.
- Trigger real hover/focus/click/checked states via the `computer` tool, and **re-measure after the
  interaction ends**, not mid-interaction — a resting-state or mid-hover screenshot has already hidden
  real bugs once in this effort (an icon that should un-reveal on mouse-away, didn't).
- If a component has nothing to test against (empty state), seed real content — a WP post/page titled
  `QA FIXTURE — ...` — rather than reporting "untestable." See `/qa-bloks/` gallery page for the pattern.
- Report findings as a property-diff table (`Property | Lovable | Live`), not prose adjectives.

## 3. Process rules

- **Orchestrator makes no direct code/content edits.** All comparison and fixing work happens in
  subagents (`Agent` tool). There is no blanket rule against running subagents concurrently — but
  subagents must never work on the same code at the same time, so don't dispatch two repo-writing
  subagents whose file footprints overlap. The orchestrator's only direct-write exception is
  `docs/theme-fidelity-rework-plan.md`.
- **Spot-check every subagent's claimed fix yourself**, with a fresh, independent `getComputedStyle()`
  read (and now, per §1, a real screenshot too) before updating a page's status. A subagent's own
  "verified"/"pre-existing failure" claim is not the last word — this rework has already caught one
  subagent mis-report a real test regression as "pre-existing" (see plan doc, `0bc59ba`).
- Don't `git stash` in this repo — `docs/theme-fidelity-rework-plan.md` is always dirty (the
  orchestrator's own live edits) and fights the sandbox's write-deny on `docs/`. Use
  `git worktree add`, or `git diff -- <path> > tmp/patch.diff`.
- After any `wp-content/themes/ink-foundation` or `wp-content/plugins/ink-core` change, run
  `tools/deploy-to-local.sh --apply` — the Local site does not read this repo live. Its dry-run mode uses
  macOS's `openrsync`, which does **not** print a per-file diff list — don't use dry-run output as proof
  nothing changed; diff the actual directories (`diff -rq`) if you need to confirm sync state.
  New `patterns/*.php` files need a throwaway Local-only mu-plugin calling
  `wp_get_theme()->delete_pattern_cache()` (pattern-cache doesn't invalidate on a plain file sync).
- Repo conventions: branch `feat/epic-19-theme-fidelity`; scratch files in repo-local `./tmp/` only;
  `composer stan`/`test`/`deptrac` before every commit (4 pre-existing Integration-suite failures needing
  a `wp-env` DB this sandbox doesn't have are known/expected, everything else is real); commit via
  `tmp/commit-msg.txt` + `git commit -F`, no `Co-Authored-By` trailer; stage only touched files, never
  `git add -A`.

## 4. Known, still-open items (not yet fixed — see plan doc for detail on each)

- BuddyPress intercepts the registration POST before WordPress's own `init` fires — needs a
  `BUDDYPRESS_LATE_LOAD` wp-config change or mu-plugin fix, a product-owner infra call, not a style fix.
- `theme.json`'s `xxl`/`xxxl` font-size tokens are still fluid `clamp()`, not converted to fixed
  breakpoint steps like every other size token — worked around page-scoped on gemeenskap/lidmaatskap only.
- `patterns/lidmaatskap-hernu.php` (`/my-profiel-lidmaatskap/`) has the same fluid-heading/button-height
  bugs already fixed on `lidmaatskap` proper — not yet ported over.
- `.ink-borg-strook__cta` (tuisblad's sponsor-strip CTA) has the same 64px-not-44px `box-sizing` bug
  already fixed on its oor-ink twin (`.ink-borg-erkenning__cta`) — not yet ported over.
- kontak's `map` block (page-map.csv) was never built — no address exists anywhere on the site to plot;
  flagged as a product-owner scope question, not guessed at.
- auth pages' narrow-viewport rendering is unverified (the `resize_window` tool didn't actually shrink
  `window.innerWidth` this session).
- my-profiel's structural gap (no tab shell/identity strip/"Wie ek volg" list/notifications panel) was
  logged and deliberately deferred by product-owner decision, not built.
- **ontdek's works/skrywers search index is populated for almost none of the real production content** (6 of
  10,976 published works, 3 of 322 writers carry the `_ink_soek_indeks`/`ink_skrywer_soek_indeks` post/user
  meta the search block matches on) — `save_post`-hook-only indexing never backfilled for bulk-migrated
  content. Confirmed via `./tmp/wpcli.sh`, not guessed. Needs a one-off backfill migration (Epic 16
  Migration-module-sized), not a style fix — see plan doc's ontdek third-pass write-up.
- ontdek's Bydraes/Skrywers cards render no save-to-reading-list / follow-writer controls at all (unlike
  every Lovable `Browse.tsx` card) — `leeslys.js`/`volg.js` aren't enqueued on this page and neither card
  renderer emits the block markup. A feature-vs-style scope call flagged for product-owner decision, not
  silently built — see plan doc.

## 5. Where things live

- Repo: `/Users/cobus/Development/ink-vibe`, branch `feat/epic-19-theme-fidelity`.
- Tracking doc (orchestrator's direct-edit file, full page-by-page detail): `docs/theme-fidelity-rework-plan.md`.
- Page inventory: `docs/design-handoff/page-map.csv`.
- Live site: `https://nuwe-ink.local/` — filesystem root `/Users/cobus/Local Sites/nuwe-ink/app/public`,
  not read live from this repo, sync via `tools/deploy-to-local.sh --apply`.
- Lovable reference: `https://preview--quill-muse-heart.lovable.app/`.
- QA fixture gallery: `https://nuwe-ink.local/qa-bloks/`.
