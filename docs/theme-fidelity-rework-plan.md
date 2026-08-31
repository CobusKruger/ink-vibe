# Theme Visual-Fidelity Rework — Plan

**Date:** 2026-07-19 (original diagnosis) — updated 2026-08-31 after a live-deployment verification pass.
**Status:** Epic 19 stories (19.1–19.5) are implemented, reviewed, and merged into this branch. A
follow-up verification pass against a real deployment (`nuwe-ink.local`) found that **almost none of
that work was ever actually visible on a live site**, due to a chain of unrelated bugs — not gaps in
Epic 19's own work. All are now fixed and live-verified. See "Post-Epic-19 verification pass" below —
**read that section first** if you're picking this up fresh; it's more relevant to what's actually
broken/outstanding right now than the original diagnosis below it.

## Original diagnosis (2026-07-19) — historical context, still accurate

This is **not** a "theme failed to load" problem and **not** primarily a token-drift problem.

- The theme's design tokens in `wp-content/themes/ink-foundation/theme.json` are an **exact match**
  to the normalised handoff in `docs/design-handoff/tokens/theme-tokens.json` (same palette —
  `primary #EA4015`, `surface #F8F6F2`, etc. — same Lora/Inter fonts, same spacing/shadow/radius
  scale). The CSS is loading and applying.
- `ink-lovable` HEAD is `5618f39` — **the exact commit the handoff was last synced to**
  (`docs/design-handoff/README.md` changelog, 2026-06-20). So the design docs are **already
  truthful** to the current Lovable design.

**Conclusion:** the tokens/specs are correct; the **theme implementation does not honor them**.
The composition layer (hero, section patterns, button block styles, story cards) was built to a
lower fidelity than the design and must be reworked. The basic Lovable design has existed since
February; most of the missing elements were in the design from the start.

### Concrete gaps (became Epic 19's acceptance criteria — all addressed)

1. **Buttons** — `theme.json` had no `styles.elements.button` rule → pill shape + wrong text color.
2. **Background** — missing the plus-pattern background texture.
3. **Hero heading** — wrong size/scale, no two-tone highlight.
4. **Hero layout** — should be a two-column split (headline left, weekly-challenge card right).
5. **Badge pill + stats row** — badge missing; stats row **deliberately omitted** (owner decision,
   `theme-fidelity-spec.md` §4 — vanity-metric framing rejected, not a gap).
6. **Story cards** — different type scale/colors/layout, no hover animations.

### Original rework steps (BMAD workflow) — already executed, kept for reference

`/lovable-design-sync` → `create UX specifications` (→ `theme-fidelity-spec.md`) → `propose sprint
change` (→ Epic 19) → `create the epics and stories list` (19-1…19-5) → `run sprint planning` →
`implement the next story…` ×5 → `run code review` (R19) → `run a retrospective`. Full detail
preserved at the bottom of this file under "Original rework steps — full detail".

---

## Post-Epic-19 verification pass (2026-08-31)

### The deployment gap — read this first, it explains everything below

`nuwe-ink.local` (a Local by Flywheel site at `/Users/cobus/Local Sites/nuwe-ink/app/public`) does
**not** read theme/plugin code live from this git repo. It's a separate WordPress install; every
commit made here needs to be manually synced across before it's visible anywhere.

**Use `tools/deploy-to-local.sh --apply`** (dry-run by default — shows what would change; pass
`--apply` to actually sync). It mirrors `wp-content/themes/ink-foundation` and
`wp-content/plugins/ink-core` via `rsync -av --delete`, then best-effort attempts a WP-CLI cache
flush. **Run this after every change to those two directories** — without it, nothing you build here
is visible on that site, no matter how correct the code is. This single gap is why a "finished and
merged" epic still looked completely wrong when actually checked live: essentially none of Epic 19
had ever reached the site before this session.

The deploy script's cache-purge step currently **fails** (`Error establishing a database
connection`): Local's bundled MySQL runs on a non-standard socket that the system `wp`/`mysql`
binaries on this machine can't find (`DB_HOST` in `wp-config.php` is literally `'localhost'`, which
forces socket resolution over TCP even when a port is known — port `10015` was confirmed at one
point, but a raw TCP connection to `127.0.0.1:10015` just hangs rather than refusing, consistent
with the sandbox silently dropping a non-allowlisted destination even with
`dangerouslyDisableSandbox: true`). This is unresolved but non-fatal — the file sync is what
matters; a stuck WordPress-side cache after a change may need a manual purge from wp-admin
(Appearance → Editor → Styles → Save has resolved at least one such case).

### Bugs found and fixed this session (7 commits, all on `feat/epic-19-theme-fidelity`)

1. **`e745b8e`** — `theme.json`'s color palette had a slug literally named `"text"`, which collided
   with WordPress core's generic `.has-text-color` marker class (emitted on *every* block with *any*
   custom textColor, regardless of which color). Because `text` sat after `primary`/`secondary`/
   `surface-alt`/etc in the palette array, its auto-generated CSS rule was emitted later and won the
   `!important`-vs-`!important` cascade tie by source order, silently overriding every custom
   textColor set to an earlier-declared palette color (found via the CTA band: `textColor:"surface-
   alt"` rendered near-black instead of white). Renamed the slug to `ink-text`.
   **Lesson for future token work:** never name a `settings.color.palette` slug `text` (or anything
   else matching one of WordPress's own generated marker classes) — this class of bug is silent and
   easy to reintroduce.
2. **`e745b8e`** (same commit) — `theme.json`'s `styles.blocks.core/group.spacing.padding` applied a
   blanket 24px padding to *every* `core/group` block site-wide. This silently inflated pure
   layout-wrapper groups that were never meant to have padding (confirmed: the header's logo-lockup
   and nav+button wrapper groups, taking the header from its intended 64px row to ~134px). Removed
   the default entirely; audited all 199 `wp:group` usages in the theme and confirmed every group
   that *needs* padding already owns it explicitly (inline style, or a padding-owning CSS class like
   `.is-style-card`/`.ink-cta-band`), so no markup changes were needed elsewhere.
   **Lesson:** don't add a blanket per-block-type style default in `theme.json` — pad structural
   wrappers is-a-bug-waiting-to-happen; prefer explicit padding on the groups that need it.
3. **`f9e7281`** — the hero badge pill (`.ink-hero-badge` in `home.css`) had an unapproved
   accessibility-contrast darkening (`color-mix(...primary 72%, text...)`) baked in during an earlier
   UX-spec-writing pass, never actually signed off by the product owner (contrast this with the
   stats-row omission, which *is* properly attributed as an owner decision in
   `theme-fidelity-spec.md` §4). Reverted to a plain `color: var(--wp--preset--color--primary)` to
   match the Lovable design's pure primary-orange pill text, per explicit product direction.
4. **`aee1c02`** — the CTA band (`.ink-cta-band` group in `cta-band.php`) had no `"align"` attribute,
   so it fell through to `theme.json`'s narrow `settings.layout.contentSize` (768px) instead of
   `wideSize` (1400px) like every other homepage section. Added `"align":"wide"` (+ matching
   `alignwide` class), matching the convention already used by `hero.php`'s `ink-hero-grid` and
   `front-page.html`'s `ink-feature-grid`.
5. **`e43f1dd`** — `theme.json`'s `settings.custom.radius` had slugs `2xl`/`3xl`. WordPress's
   `_wp_to_kebab_case()` (used internally by `WP_Theme_JSON::compute_theme_vars()` when generating
   `--wp--custom--*` CSS variables from `settings.custom`) inserts a hyphen at **any letter↔digit
   boundary**, so the actually-generated variable was `--wp--custom--radius--3-xl`, never
   `--wp--custom--radius--3xl` as `home.css`'s `var()` references assumed — on any install, cache or
   no cache; this was never a caching bug despite initially looking like one. A first fix attempt
   (`xl2`/`xl3`, removing only the *leading* digit) was still wrong — `xl2` → `xl-2`, same boundary,
   just relocated. Final fix: `xxl`/`xxxl` (zero digits anywhere), verified correct by running
   WordPress's actual `_wp_to_kebab_case()` against the candidate names directly before deploying,
   not assumed.
   **Lesson, general WordPress gotcha, not INK-specific:** never use a `settings.custom.*` slug
   containing a digit adjacent to a letter. This does **not** affect
   `settings.typography.fontSizes` preset slugs (a different code path, `compute_preset_vars()`,
   no kebab-casing involved) — the fontSize `2xl`/`3xl` presets were never affected and weren't
   touched.
6. **`0122347`** — `Ink\Content\FieldSets::register()` computed every meta field's
   `register_post_meta()` default as `'integer' === $field['type'] ? 0 : ''` — too blunt for
   `ink_uitdaging_cadence`, a `string` type constrained by a REST `enum`
   (`CadenceType::values()`), where `''` isn't a member of that enum. WordPress's own schema
   validation rejected the registration every request (confirmed via a live `debug.log` capture
   during the real `init`-time module bootstrap — a `_doing_it_wrong` notice, non-fatal on its own).
   Added an optional per-field `'default'` override (existing type-based logic stays the fallback for
   every other field); `ink_uitdaging_cadence` now explicitly defaults to
   `CadenceType::default()->value` — the same source `CadenceType::fromMeta()` and
   `FieldSets::sanitizeCadence()` already fold invalid input to.
7. **`3c71d44`** — the big one. **34 classes** across
   Forms/InkPols/Training/Challenges/Discovery/Library/Sponsors/Engagement/Social registered their
   WordPress block type via `add_action('init', array(self::class, 'registerBlock'))` inside their
   own `register()` method — but `register()` is itself invoked from **within** `init`'s own active
   dispatch (`Kernel\Plugin::run()` hooks `registerModules()` onto `init`; `registerModules()`
   synchronously calls every `Module::register()`, which synchronously calls `register()` on each
   constituent class — all inside that same `init` firing). **WordPress does not invoke a callback
   added to a hook from within that hook's own currently-executing dispatch.** Confirmed via an
   isolated minimal reproduction on the live install (an `init`-hooked closure at default priority
   10 — matching `registerModules()`'s priority — that itself calls `add_action('init',
   $innerCallback)`; the inner callback never fired), not theory. This is why **none** of the
   homepage's dynamic sections (weekly-challenge card, winner spotlight, featured bydraes, sponsor
   strip) ever rendered, regardless of content — the blocks themselves were never registered on any
   real page load, full stop. Post types and meta fields registered fine throughout, because those
   use direct synchronous calls (`register_post_type()`, `register_post_meta()`), not this nested
   pattern.
   Fix: call `registerBlock()` (or, for `InkPols\Viewer`, just the block half of `register()`)
   directly/synchronously instead of re-deferring onto `init`.
   **This is the single most important architectural fact for anyone extending this codebase**: any
   new class under `Ink\{Module}\...` whose `register()` needs to register a block type (or, per the
   items below, likely anything else) must call the registration directly — `register()` is already
   running at the correct time via the Kernel's dispatch chain. Wrapping it in another
   `add_action('init', ...)` is not just redundant, it's silently fatal to that registration.

### Outstanding — same bug shape, confirmed present, NOT yet fixed

`3c71d44`'s fix was scoped to block-type registration only. The **identical** nested-`init`
structural bug also exists in these 5 places, registering non-block things — left untouched pending
a decision, since impact wasn't individually verified live for these:

- `Ink\Discovery\TrendingScore::register()` → `add_action('init', ..., 'maybeSchedule')` (an Action
  Scheduler cron registration)
- `Ink\Challenges\ModeratorFeedback::register()` → `..., 'registerMeta'`
- `Ink\Accounts\Approval::register()` → `..., 'registerMeta'`
- `Ink\Accounts\Onboarding::register()` → `..., 'registerMeta'`
- `Ink\Entitlement\Module::register()` → `..., 'registerSettings'`

**Next step for a fresh agent:** verify each live the same way `3c71d44` verified the blocks (drop a
throwaway mu-plugin — see debugging notes below — checking whether the meta/setting/cron schedule
actually registers post-`init`), then apply the identical direct-call fix to whichever are confirmed
broken.

### Outstanding — deferred by product owner, not blockers, do not "fix" unasked

- **Footer founding year.** `patterns/footer-main.php` has two literal, unresolved `[stigtingsjaar]`
  placeholder tokens. `patterns/oor-ink.php` (About page) separately carries a **provisional,
  explicitly-unconfirmed** "2018" (Story 17.1) pending founder sign-off — see
  `docs/ui-copy-translations.md:33` and
  `_bmad-output/implementation-artifacts/17-1-apply-approved-ui-copy.md`. Product owner has said:
  leave both as-is for now.
- **Content seeding** — none of the collapsing dynamic sections can be visually fidelity-checked
  without real data; product owner is seeding this personally, on their own timeline. Reference,
  per section:
  - **Weekly-challenge card** (`ink/huidige-uitdaging`, `Ink\Challenges\CurrentChallenge`): one
    published `uitdaging` post, custom field `ink_uitdaging_deadline` = a future `Y-m-d` date (SAST,
    date-only, no time component). A converted OLD/past `uitdaging` is fine for the winner spotlight
    below but will **not** show here — this specific card needs a still-open (future-dated) round.
  - **Winner spotlight** (`ink/wenner-kollig`, `Ink\Challenges\FeaturedWinners` /
    `Ink\Challenges\WinnersPost`): three linked pieces — (1) the `uitdaging` post itself (a past
    deadline is fine/expected here — the challenge closed and a winner was picked), (2) at least one
    entry post linked to it via meta `ink_submission_uitdagings` (a checkbox-array of ticked
    uitdaging IDs) with a Gradering set, plus meta `ink_entry_placement` = 1/2/3, (3) a separate
    *ordinary* `post` (not `uitdaging`), published, carrying meta
    `ink_wenneraankondiging_uitdaging` = the uitdaging's post ID — this is what
    `WinnersPost::featured()` actually looks up first. None of this has a wp-admin UI; it's raw post
    meta, designed to be produced by the (separately tracked) Epic 12A adjudication pipeline.
    **For fast visual-only testing without the real pipeline**: `ink/wenner-kollig` reads its entire
    payload from the `ink_home_featured_winner` WP filter
    (`Ink\Challenges\FeaturedWinners::FEATURED_FILTER`) — a small mu-plugin hooking that filter with
    a hardcoded payload renders a fully real, styled card in seconds (see the payload shape in
    `FeaturedWinners::orderFeed()`'s docblock: `id`, `rank` (1–3, validated by
    `Placements::isValidRank()`), `title`, `url`, plus optional `month`/`author`/`quote`/
    `avatar_url`/`avatar_alt`/`win_label`, each degrading gracefully if omitted). This technique was
    used and verified working this session; the test mu-plugin itself was deleted afterward
    (test-only, never committed) — recreate as needed.
  - **Featured bydraes** (`ink/uitgesoekte-bydraes`, "Editor's picks"): 4–5 published,
    featured-eligible creative works (gedig/storie/artikel), enough to populate the asymmetric grid
    (one spanning "uitgesoek" card + several regular cards).
  - **Sponsor strip** (`ink/borg-strook`): at least one active `borg` post, ideally one per tier
    (goud/silwer/brons) to check all three chip styles.
- **Re-run the full Lovable visual-fidelity comparison once content exists.** Everything checked
  *this* session (header height, badge color, CTA band width/color/radius) now matches Lovable and
  is live-verified; the four dynamic sections above have never been visually checked at all, because
  they've never had anything to render until this session's registration fix landed.

### Local WordPress debugging notes (for any future investigation on this install)

- WP-CLI/`mysql`/raw PHP `mysqli` from this repo's shell environment cannot reach `nuwe-ink.local`'s
  database (see "deployment gap" above) — work around it via `curl`-based HTTP diagnostics and
  temporary debug logging instead of direct DB access.
- To get a real PHP error/notice trail (not silently swallowed — `WP_DEBUG` is off by default on this
  install): add to `wp-config.php`, right after `/* Add any custom values... */`:
  ```php
  define( 'WP_DEBUG', true );
  define( 'WP_DEBUG_LOG', true );
  define( 'WP_DEBUG_DISPLAY', false );
  ```
  This writes to `wp-content/debug.log`. **Revert this and delete the log when done** — it was used
  and fully reverted during this session; don't leave it on.
- `curl -sk "https://nuwe-ink.local/?cachebust=<random>"` is a reliable way to inspect the real
  server-rendered HTML directly, bypassing the browser entirely (useful for ruling browser-side
  caching in/out — confirmed this session that a `curl` response and a browser's rendered DOM
  matched exactly, so server-side is where to look first for any "changes aren't showing up"
  mystery).
- A throwaway mu-plugin dropped in `wp-content/mu-plugins/` (copied to
  `/Users/cobus/Local Sites/nuwe-ink/app/public/wp-content/mu-plugins/` to actually take effect — see
  "deployment gap" above) is the fastest way to run arbitrary diagnostic PHP against a real request:
  no activation needed, WordPress auto-loads every file in that directory. Useful patterns from this
  session: hooking `wp_head` to echo `WP_Block_Type_Registry`/`WP_Theme_JSON`/reflection introspection
  as HTML comments; hooking a domain filter (like `ink_home_featured_winner`) directly to fake data
  for visual testing. **Always mark such files clearly TEST-ONLY, never commit them, and delete both
  copies (repo + deployed site) when done** — this session's probes were fully cleaned up.

---

## Original rework steps — full detail (already executed; kept for reference)

### Phase 0 — Re-establish design truth (cheap, do first)

1. **`/lovable-design-sync`** — runs the sync skill: `git -C ink-lovable pull`, diff
   `5618f39..HEAD`, re-normalise any changed tokens into `docs/design-handoff/tokens/theme-tokens.json`,
   update `page-map.csv`, `mockup-readiness-assessment.md`, specs §9.4/§14, and the changelog.

### Phase 1 — Define what "correct" looks like

2. **`create UX specifications`** → **bmad-ux** (Sally). Produced the theme visual-fidelity spec →
   `_bmad-output/planning-artifacts/ux-designs/ux-ink-vibe-2026-06-15/theme-fidelity-spec.md`.

### Phase 2 — Introduce the rework as governed scope

3. **`propose sprint change`** → **bmad-correct-course**. Framed the fidelity failure as a course
   correction, drafted Epic 19.
4. **`create the epics and stories list`** → stories `19-1` (button/foundation block styles) through
   `19-5` (polish — CTA gradient, 4-col footer, borg chips, animations) in
   `_bmad-output/implementation-artifacts/`.
5. **`run sprint planning`** → added Epic 19 + stories to `sprint-status.yaml`.

### Phase 3 — Rebuild, review, close

6. **`implement the next story in the sprint plan`** (loop) → **bmad-dev-story** (Amelia) per story.
7. **`run code review`** → **bmad-code-review** → R19 review.
8. **`run a retrospective`** → **bmad-retrospective**.

### One-line sequence

`/lovable-design-sync` → `create UX specifications` → `propose sprint change` →
`create the epics and stories list` → `run sprint planning` →
`implement the next story…` (×N) → `run code review` → `run a retrospective`.
