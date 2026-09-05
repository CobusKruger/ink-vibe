# Theme Visual-Fidelity Rework — Plan

**Date:** 2026-07-19 (original diagnosis) → 2026-08-31 (Post-Epic-19 verification pass) → 2026-09-02
(corrected-method rewrite) → **2026-09-03 (third pass, in progress)**. This file is the running tracking
log; previous versions described work that was later found to be under-verified — twice now (see "How
pages 1–9 got re-audited" below, then "Third pass" below it) — or is now superseded by what's in this
version.

**Status right now: a THIRD pass is underway.** The second pass (below, "corrected-method pass") called
all 16 pages done using `getComputedStyle()` diffing with heuristic element-matching — but the product
owner reviewed the live site against Lovable directly afterward and found real differences still remain.
Root cause: that method never did an actual visual side-by-side, and matched elements by a "find by text,
fewest descendants" heuristic rather than an unambiguous anchor. **Treat every "done" row below as
unverified until re-confirmed under the third-pass method** (identity-anchored `data-audit-id` measurement
+ real screenshots, not heuristic matching) — see the new "Third pass" section immediately below the
historical tracking table. Do not read the table's "done" status at face value.

If you're picking this up fresh: read `docs/theme-fidelity-audit-handoff.md` for the full method history
and orchestration rules — still the operative process reference, itself now on its second correction.

---

## How pages 1–9 got re-audited

A first pass at this table (commits now superseded, still in git history) used screenshot-impression
comparison ("a badge exists on both, roughly similar") instead of pulling real computed styles. A direct
product-owner review of lees-gedig against Lovable found ~17 real, measurable mismatches that method had
missed entirely — colors, alignment, gaps, line-heights, a font silently not loading on the *reference*
site, a submit button that never disabled. `docs/theme-fidelity-audit-handoff.md` was written to correct
the method and hand off a fresh re-audit of everything pages 1–9 had "done" under the old approach.

That corrected re-audit is what the table below reports. Each page: real `getComputedStyle()` diffed
against both DOMs, `document.fonts` checked for actual font-load (not just declared `font-family`), real
hover/click/focus interaction states triggered and re-measured after the interaction ended, then
independently spot-checked by the orchestrating session before being marked done.

---

## Tracking — corrected-method pass

| # | Page | Status | Commit(s) |
|---|---|---|---|
| — | **Sitewide token fixes** (surfaced during the tuisblad pass, fixed once before continuing) | **done** | `541a57a` |
| 1 | tuisblad | **done** | `7407963` |
| 2 | lees-storie | **done** | `ba1a54b` |
| 3 | lees-gedig | **done** — 17 pre-existing findings implemented + re-verified | `7436941` |
| 4 | opleiding | **done** | `a64f98f` (+ small line-height follow-up `3087515`) |
| 5 | biblioteek | **done** | `92b8d97` |
| 6 | uitdagings-single | **done** | `8e5eb60` |
| 7 | uitdagings-list | **done** | `8a6309f` |
| 8 | skryf | **done** | `d380312` |
| 9 | skrywerprofiel | **done** | `f485fca` |
| — | Reaction-bar placement/framing reconciled across all `reading-*` patterns | **done** | `754fc42` |
| — | Highlightable-text + floating action bar feature, lees-storie (real feature gap, now in scope) | **done** | `b6b609a` |
| 10 | my-profiel | **done** (style/interaction pass) — **major structural gap logged, not built, see below** | `58e2f72` |
| 11 | ontdek | **done** — **structural gap (anchor pills → real tabs) judged in-scope and built, see below** | `7884c90` |
| — | Stale test fix (`OntdekTemplateTest` regression from `7884c90`, caught by orchestrator spot-check, not the subagent) | **done** | `0bc59ba` |
| 12 | gemeenskap | **done** | `f7cc536` |
| 13 | lidmaatskap (no live Lovable route — compare vs. spec/closest analog) | **done** — internal design-system-consistency pass | `bf0fc42` |
| 14 | oor-ink (no dedicated Lovable page; sponsors section diffed directly) | **done** | `304f7c5` |
| 15 | kontak (no live Lovable route — compare vs. spec/closest analog) | **done** — internal design-system-consistency pass | `c2e661c` |
| 16 | auth (login/register/forgot-password/reset-password) | **done** — last page, rework complete | `2654ea6` |

Note: text content is expected to differ (Afrikaans on the live site vs. English placeholder copy on
Lovable) — that's correct, not a bug; see `[[afrikaans-is-source-of-truth]]`. Fidelity means
visual/structural/interaction design, not copy.

---

## Third pass — identity-anchored re-audit (2026-09-03, in progress)

Method: Tier 0 (structural component-correspondence, source-to-source, before touching any style value) →
Tier 1 (matching `data-audit-id` attributes added to BOTH the Lovable source and the WP pattern/block
output, so every measurement is an exact lookup, never a heuristic guess) → Tier 2 (real screenshots,
genuinely diffed). Viewport width is verified equal on both sides via `window.innerWidth` after every
resize — never trusted from the resize call's own response, which was found to silently no-op on one tab.
Every WP page load is cache-busted / curl-verified before trusting a browser tab's contents — a stale tab
cost real time earlier in this pass. Full method detail: `docs/theme-fidelity-audit-handoff.md`.

Also found and fixed this session, upstream of any WP work: `ink-lovable`'s own `src/index.css` had its
Google-Fonts `@import` positioned *after* the `@tailwind` directives — invalid per the CSS spec (`@import`
must precede all other rules), so browsers silently discarded it. Lovable's own reference site was
therefore never actually rendering Lora, only its Georgia fallback, for as long as that ordering bug
existed. Fixed by moving font loading into `index.html` as `<link>` tags. This invalidates any prior WP
decision that deliberately replicated "Lovable renders Georgia, not Lora" — those now need re-deciding
against Lovable's corrected rendering (first instance: lees-gedig's title, below).

| # | Page | Third-pass status | Notes |
|---|---|---|---|
| 3 | lees-gedig | **Tier 0–2 done, independently spot-checked** — per-line interaction, legacy-data parser, floating bar all fixed | see below |
| 6 | uitdagings-single | **Tier 0–2 done** — structural reorder + gaps fixed, hover states confirmed live | see below |
| 8 | skryf | **Tier 0–2 done, 2026-09-05** — field reorder + success-screen rebuild, real form submission tested; found+fixed a Local entitlement-config gap that blocked all submissions | see below |
| 9 | skrywerprofiel | **Tier 0–2 done, 2026-09-05** — 3 real interaction bugs found and fixed (follow-sync, share-feedback, half-star rendering) | see below |
| 10 | my-profiel | **Tier 0–2 re-verified, 2026-09-05** — no defects found; structural-gap deferral confirmed still accurate; discovered working DB access (`./tmp/wpcli.sh`) | see below |
| 11 | ontdek | **Tier 0–2 done, re-verified 2026-09-05** — sticky tab bar built + font-size fixed; three real findings surfaced, none guessed at, see below | see below |
| 12 | gemeenskap | **Tier 0–2 done, re-verified 2026-09-05** — stale-decision check resolved (not stale, but mischaracterized — see below); 2 real hover/font bugs found and fixed | see below |
| 16 | auth | **Tier 0–2 done, 2026-09-05** — first real live Lovable comparison this pass (previously unreachable); 4 real CSS bugs fixed; 2 chrome/OAuth gaps flagged; **a real account-credential incident occurred, see below** | see below |
| 3 | lees-gedig | **Fourth pass done, independently re-verified, 2026-09-05** — deur/bullet/spacing/color/heart-box-model/comments all fixed, live-triggered-interaction checked | see "Fourth pass" below |
| 2 | lees-storie | **Fourth pass done, independently re-verified, 2026-09-05** — header background/border added, real text-selection highlight mechanism built and triggered live, comments rebuilt | see "Fourth pass" below |
| — | lees-artikel | **Fourth pass done, independently re-verified, 2026-09-05** — unified onto lees-storie's exact shape (Lovable has no distinct Article render path), confirmed via live badge class/author-card/reaction-variant checks | see "Fourth pass" below |
| 1 | tuisblad | **Fourth pass done, independently re-verified, 2026-09-05** — 15 PO-reported items, all confirmed real and fixed (sticky header, pill colours, button sizing, box-model overflow, spacing, page width, 2 missing fixtures); 1 open item carried forward (FeaturedWinners has no real data source) | see "Fourth pass — tuisblad" below |
| 4, 5, 7, 13–15 | opleiding, biblioteek, uitdagings-list, lidmaatskap, kontak, oor-ink | not yet started under this method | queued — see session todo list |

### Retrospective: why lees-gedig still had substantial differences after four prior passes

Not one root cause — six independent, compounding bugs, each escaping detection for a distinct reason.
Worth reading before auditing any remaining page, since these are method gaps, not one-off mistakes:

1. **Style-diffing can't catch a wrong component, only a wrong value.** The 3-icon reaction picker wasn't a
   bad color or line-height — it was the wrong widget entirely (an aggregate post-level reaction system
   reused as the per-line control, never checked against Lovable's actual per-line design). Property
   diffing assumes the right element already exists; it has no way to notice the element itself is wrong.
   That needs a structural/component-correspondence check (Tier 0), done specifically for the widget in
   question — not assumed covered by a general page-level pass.
2. **Stateful, hover-triggered UI is invisible to a resting-state check.** The per-line heart doesn't exist
   in the page's resting DOM — JS builds it on load, CSS reveals it only on `:hover`. Every Tier-0/Tier-1
   check this session before the product owner flagged it measured static elements only; the interaction
   was never actually triggered and observed. This is the exact failure the original handoff doc already
   warned about ("a resting-state screenshot has already hidden real bugs once") — and it still happened
   again, because avoiding it requires deliberately designing a test around the interaction, not just
   measuring what's already sitting on the page.
3. **Coordinated CSS+markup changes need a final "does the selector match anything real" check.** The first
   position fix wrote correct CSS (`.ink-gedig__line-text{position:relative}`) without confirming the
   markup half actually applied that class to anything in the live DOM. Two independently-correct halves
   of one change, never sanity-checked together.
4. **Code assumptions about real-world data diversity go untested against QA fixtures.** The poem renderer
   assumed one content format because that's all the recent QA/demo content ever exercised. Legacy content
   in a different (also valid) format — 5,585 of 10,742 real posts — was never in any test path until one
   specific post got looked at directly. The bug was in the code's assumption, not the data, but nobody
   would know that without checking real content at scale.
5. **Narrowly-scoped reactive fixes leave the rest of a component's spec unchecked.** Each round fixed
   exactly what was flagged without checking the complete spec of the thing being fixed — floating-bar
   composition, guest visibility, the empty-line edge case each took a separate explicit prompt to surface,
   because fixing "the 3-reaction bug" didn't include re-deriving the bar's full intended contents first.

Takeaway applied going forward: any hover/click/stateful interaction gets an explicit trigger-and-observe
test before a page is called done, not an optional add-on — and any page rendering user-authored content
gets checked against real production content diversity, not just QA fixtures.

### lees-gedig — third-pass findings

Confirmed via fresh `getComputedStyle()` + `document.fonts` + live screenshots (not the subagent's own
claim — independently re-checked: raw `curl` diff of server HTML, live browser JS execution, and a
from-scratch `composer test`/`stan` run, all matching what was reported):

- **Reaction bar showed 3 reaction types (♥ hartjie / 👍 duim-op / ✨ wow) where Lovable's design has a
  single like.** This is the "toolbar with three reactions, should be one" bug flagged at the start of
  this session's audit. Root cause: `ink/reaksie-tellers` (`ReactionTotals.php`) always rendered all three
  `Reaction::cases()`. Fixed via a new `variant` block attribute — `'enkel'` (single hartjie count only,
  lees-gedig) vs. the existing default `'volledig'` (all 3, unchanged on `reading-storie`/`reading-artikel`
  — confirmed live via `curl` that both still show all 3 reaction classes, untouched).
- **Engagement bar was static inline, not floating.** Lovable's is `position: sticky; bottom: 24px` and
  stays reachable while scrolling a long poem. WP's was a plain block after the poem body. Fixed: new
  page-scoped `assets/css/reading.css` (enqueued only on `is_singular('gedig')`), bar restructured to a
  top-level sticky element. Confirmed live: `position: sticky`, `bottom: 24px`, and the pill visibly stays
  in view while scrolling.
- **Title pinned to literal Georgia**, deliberately replicating Lovable's font-loading bug (see above) —
  that bug is now fixed upstream, so the replication is now wrong. Removed the pin; title now resolves
  through `theme.json`'s real Lora heading token, same mechanism every other `wp:post-title` on the site
  uses. Confirmed live: `document.fonts.check('italic 600 36px Lora')` → `true`.
- Confirmed (not assumed) not gaps: `ink/verwante-stukke` and `ink/opleiding-verwant` have no Lovable
  counterpart at all (WP-only additions, nothing to diff against); `ink/leeslys-knoppie` correctly renders
  nothing for logged-out visitors (by design, member-only feature) — not re-verified in the logged-in
  state this session (would require actually authenticating via browser automation, which wasn't done).
- **Two items flagged for a product-owner call, not silently decided:**
  - Lovable's floating bar has 4 actions (like, comment-count → anchor, bookmark, share); WP's now has 2
    (the collapsed like + the reading-list toggle, itself invisible to guests). Approved scope this
    session was narrowly "collapse to one like" + "make it float" — whether to also build a comment-anchor
    and a share affordance is open.
  - Should the reading-list toggle show *something* (e.g. a disabled/sign-in-prompting bookmark) to guests
    instead of nothing, to visually match Lovable's always-visible bookmark icon? Currently by-design
    invisible; that's a product call, not a style fix.
- Minor, out-of-scope-for-this-pass token gaps surfaced during measurement (a few RGB units on
  `ink-text`/`muted-text` vs. Lovable's `foreground`/`muted-foreground`, some container-width deltas) —
  noted, not fixed; pre-existing, unrelated to the 3 items above.

**Follow-up round (same session, product-owner live review caught 3 more real issues the first fix
missed):** the per-line heart was positioned far to the right of each line instead of hugging the text —
root cause: the line's text was never actually wrapped in the inline-block span the CSS expected, so the
absolutely-positioned heart had no correctly-sized anchor. The hover tint was a solid, too-strong gray
(reusing the `secondary` token at full opacity instead of a lighter `muted` tone at 40%). And the floating
bar was missing a comment count and rendering the reading-list toggle as a full text-label button instead
of an icon. All three fixed and independently re-verified against values measured directly from Lovable's
live computed styles (not translated by hand from Tailwind class names, which is where this went wrong the
first time): heart-to-text gap 14px (exact match both sides), hover background `rgba(243,240,237,0.4)`
(exact match), resonant background `rgba(255,234,128,0.3)` (exact match), resonant heart color
`rgb(236,59,19)` (exact match). New `ink/kommentaar-telling` block reuses `ResponseStore::countForPost()`
(no new query); `ink/leeslys-knoppie` is now icon-only with the label preserved as `aria-label` +
visually-hidden text. Both shared with `reading-storie.php`, confirmed live not to have broken it.

**Second follow-up (same session): guest-visible bookmark.** The reading-list icon was invisible to
logged-out visitors (`ReadingListToggle::render()` returned `''` when not logged in — real, by-design,
pre-existing). Product-owner decision: show it to guests too (matching Lovable's always-visible icon), click
routes to `/meld-aan/?redirect_to=<current URL>` instead of silently doing nothing. Implemented, reusing
`Ink\Accounts\AuthRedirects::LOGIN_URL_PATH` and the login form's existing `redirect_to` handling — no new
redirect infrastructure built. Independently verified: anonymous `curl` shows `data-ink-guest="1"` on the
button (proof the render-gate is actually gone, not just the JS); live guest click in the browser landed
on exactly `https://nuwe-ink.local/meld-aan/?redirect_to=<the poem's URL>`. Logged-in behavior unchanged
(verified via a real WP-CLI test-user login, not an auth bypass). Tests 1311→1313 (2 new), same 4
pre-existing failures, stan/deptrac clean.

**The per-line interaction's positioning/colors, the floating bar's comment-count/bookmark-visibility gaps,
and the whole-poem-highlight parser fix are all now fixed and independently verified. lees-gedig is
genuinely done.**

**Correction, then real fix (same session): this was a parser gap, not corrupted data.** Initial diagnosis
wrongly treated the poem this page uses (`die-kamer-sonder-woorde`, post 67912) as having "corrupted"
`post_content` and rewrote it to raw `\n`-per-line text (also done for QA post 67904). **Product-owner
correction: this was wrong** — the Gutenberg-wrapped/`<br>`-joined format is real historical content,
stored that way by an old theme at the point it was captured/migrated. Both posts' original content were
restored (67912 from its WP revision, byte-verified; 67904 had no surviving revision, reconstructed from
this session's own earlier diagnostic capture — a verbatim first-hand transcript, not a re-guess).

`GedigBody::normalizeLegacyMarkup()` (new, called as `tokenize()`'s first step) now reads legacy markup
correctly instead of requiring it be rewritten. Built from evidence, not one generalized example: a
full-database scan found **10,742 published `gedig` posts total (the total independently confirmed via the
REST API's own count)**, of which **~5,585 share some HTML-markup legacy shape** — deep-sampled ~30 real
posts and found five distinct real shapes (Gutenberg-wrapped single-paragraph, bare classic-editor
per-stanza `<p>`/`<br>` — the dominant ~5,578-post shape, a Facebook-paste nested-div shape affecting 7
posts, a one-`<p>`-per-line shape affecting 2, plus the untouched raw-`\n` majority), each with a distinct
real stanza-break signal, handled via a placeholder-token pipeline so intermediate whitespace clean-up can
never eat a meaningful line/stanza break. One disclosed, accepted imperfection remains (7 Facebook-paste
posts can pick up one extra cosmetic blank separator — no content loss).

Independently re-verified, not just trusted: the excerpt for `die-kamer-sonder-woorde` (server-computed
from `post_content` via `wp_trim_excerpt`) still shows the exact run-together-without-spaces artifact
characteristic of `<br>`-tag content — direct proof the restoration is real, not still the earlier rewrite.
Live `curl` of the rendered page shows 4 correct per-line tokens from that restored content. The raw-format
control post (`piet-punte`) still tokenizes correctly — no regression. Full corpus re-scanned for the
original "whole poem glued into one line" symptom: 0 remaining (was the entire complaint). Tests 1313→1319
(+6, covering all five shapes plus the regression case), same 4 pre-existing failures, stan/deptrac clean.

**Third follow-up (same session): empty-after-sanitizing lines were still interactive.** Product-owner
question surfaced a real gap: `tokenize()`'s blank-check runs on RAW pre-sanitization text, so a line whose
raw text is disallowed markup (not whitespace) tokenized as a real `'line'` — but sanitized to nothing
visible once `wp_kses()` ran in `toHtml()`, minting a genuinely-empty yet fully interactive
`<p data-ink-line>` (heart + hover-highlight on a blank row). Fixed in `toHtml()`: compute the sanitized
render first, check `trim(strip_tags(...))` on THAT (not the raw text again — that already missed this
case), and treat an empty result exactly like a `blank` token (emit `.ink-gedig__sep`, no interactive
element) without touching `tokenize()`'s own correctly-raw classification. A full render-level corpus scan
(not a sample this time) found **79 of 10,742 posts** with this pattern — mostly stray `<img>` tags
(including an old tracking-pixel plugin's spy images), `<hr>`, `<pre>`, `<ul>` fragments — all 79 confirmed
fixed by the same change. Independently re-verified: post 58508's empty line is gone from the interactive
set (`data-ink-line` now jumps 19→22, both the artifact and its neighboring separator collapsing into real
`.ink-gedig__sep` gaps); `die-kamer-sonder-woorde` unaffected; a minimal-content/shaped poem
(`in-n-oogwink-goud`, single-word/short lines) still shows all lines correctly interactive — confirms no
over-triggering on real-but-short content. Tests 1319→1322 (+3), same 4 pre-existing failures, stan/deptrac
clean.

Files touched (staged, **not yet committed** — pending a decision on commit cadence for this pass):
`wp-content/plugins/ink-core/src/Engagement/ReactionTotals.php`,
`wp-content/plugins/ink-core/src/Engagement/GedigBody.php`,
`wp-content/plugins/ink-core/src/Social/ReadingAuthorCard.php`,
`wp-content/themes/ink-foundation/patterns/reading-gedig.php`,
`wp-content/themes/ink-foundation/functions.php`,
`wp-content/themes/ink-foundation/assets/css/reading.css` (new),
`tests/Unit/Engagement/ReactionTotalsTest.php` — plus `data-audit-id` instrumentation (kept, harmless) in
`ink-lovable`'s `src/pages/ReadStory.tsx` and `src/components/reading/PoetryReader.tsx` (separate repo,
uncommitted).

### uitdagings-single — third-pass findings

Structural correspondence (Tier 0) mapped `Challenge.tsx` section-by-section against
`reading-uitdaging.php` before measuring any style value, per the lees-gedig
retrospective's lesson #1. Found a real structural bug the corrected-method
(second) pass's property-diffing had no way to catch (the retrospective's exact
warning: "style-diffing can't catch a wrong component/order, only a wrong value"):
the `ink/uitdaging-besonderhede` block was embedded ONCE in the hero, fusing the
sluitingsdatum/status meta row with the entries list — so the "Inskrywings" card
grid rendered ABOVE the prompt/opdrag content, the opposite of Lovable's Hero →
Prompt → Resources → Submissions → CTA order. Confirmed via real screenshots on
both a fresh `curl`-verified live tab and the Lovable reference tab, not inferred
from source alone.

**Fixed this pass:**
- Split `ink/uitdaging-besonderhede` into two embeds via a new `variant` block
  attribute (`kop` in the hero, `inskrywings` in its own section after the opdrag
  content) — the entries list now renders in the same relative position as
  Lovable's "Entries from the community" grid. The `#inskrywings` anchor target
  (used by the hero's "Lees inskrywings" button) migrated to the new section.
- Added the tagline paragraph (Lovable's `challenge.tagline`, between title and
  meta row) — renders only when the uitdaging post has a manually-authored
  excerpt (`has_excerpt()`); no real post has one yet, so this is wired but
  currently invisible — copy-debt to author, not a template bug (rendering the
  auto-generated WP excerpt instead would just duplicate the opdrag text directly
  beneath the title).
- Added the missing "[N] skrywers het ingeskryf" participants meta item (ratified
  copy, `docs/ui-copy-translations.md`) — `SinglePage::entryCount()` was already
  computed elsewhere on this exact page (the CTA subtitle) but never surfaced in
  the hero meta row.
- Reordered the hero to meta-row-then-buttons (was buttons-then-meta-row),
  matching Lovable's exact sequence.
- Added the author avatar to each entry card (`get_avatar_url()`, WP core — no
  new cross-module dependency) — Lovable's submission cards carry one, WP's didn't.

**Flagged, not built (genuine feature/scope questions, not style fixes):**
- Lovable's entire "Learning resources for this challenge" section (a 4-card
  hulpbronne grid with icons, tags, sources, and a hover-reveal `ExternalLink`
  icon) has **NO WP counterpart at all, in any form** — not folded into the
  post-content blob either. Building it needs a new resource-link content model
  (repeater field or CPT); a real feature build, not a fidelity/style fix.
- The Prompt/Literary-devices/Submission-rules/Prize zones are genuinely a single
  authored post-content blob by a pre-existing, documented Story 12.1 decision
  (`reading-uitdaging.php`'s own docblock) — re-confirmed still accurate, not
  re-litigated. Note: the one real uitdaging post's actual body doesn't even
  informally separate devices/rules/prize into distinct paragraphs (they're
  crammed into one dense sentence) — a copy-authoring quality issue, not a
  template bug, out of scope for a style pass.
- Lovable's "Editor's pick" (Trophy icon) meta item has ratified copy ("Die
  redakteur se keuse") but no backing data anywhere — no per-uitdaging "is this
  an editor's pick" flag exists. Deliberately not rendered (would otherwise be a
  permanent, potentially false claim on every challenge page) — needs a
  product-owner decision on whether to build a real curation flag or treat it as
  always-on decoration.
- Entry-card read-time (Clock icon) and Heart/MessageCircle engagement counts
  (Lovable's submission cards carry both) are NOT built — would require new
  `Challenges → Discovery` and `Challenges → Engagement` deptrac edges (currently
  `Challenges` only depends on `Kernel`/`Content`/`Tiers`/`Notifications`). The
  avatar addition above needed no such edge (WP core only); these two would.
  Flagged as a recommended follow-up rather than silently expanding module
  coupling without an explicit decision — mirrors the `ontdek`/`oor-ink` pattern
  of flagging genuine scope questions rather than guessing.

**Hover states confirmed live, not just read from CSS** (per the retrospective's
lesson #2): Lovable's resources-card `ExternalLink` icon reveal was actually
hovered, screenshotted with the icon visible + title tinted terracotta, then
moved away and re-screenshotted with both reverted — real trigger-and-observe,
not a resting-state assumption (though this component itself has no WP
counterpart to compare against — see above). WP's entry-card lift
(`translateY(-4px)` + shadow + title-color change) was hovered and confirmed via
both a screenshot and a fresh `getComputedStyle()` read
(`transform: matrix(1,0,0,1,0,-4)`), matching Lovable's `card-hover:hover` class
value exactly.

**Real content checked, not just the one URL** (lesson #3): the one real
(non-fixture) uitdaging has exactly one real entry; the three `QA FIXTURE —`
uitdaging posts (not wired to any QA-gallery embed for this block — see the
class's own `INCLUDE_FIXTURES_FILTER` docblock) all have zero entries. **No
multi-entry example exists anywhere on this install** — disclosed as a real
testing limitation rather than silently skipped; the grid CSS itself
(`grid-template-columns:repeat(auto-fit,minmax(260px,1fr))`) is unchanged from
the already-verified prior (`8e5eb60`) pass, so risk is judged low, not zero.

**Narrow-viewport rendering unverified** — `resize_window` again failed to
reliably set `window.innerWidth` on both tabs (one tab moved partially, the other
didn't move at all despite a successful-looking response), the same known tool
unreliability recorded elsewhere in this doc. Not chased further; CSS uses the
same relative-width patterns already verified responsive on other pages.

Tests 1322→1328 (Post-Epic-19 baseline carried the working tree's uncommitted
lees-gedig test additions at session start; `SinglePageTest.php` itself grew
11→17 tests), same 4 pre-existing Integration-suite failures, stan clean,
deptrac shows only the pre-existing `Kernel\Activation → Content\PostTypes`
finding (unrelated to this page). Files touched: `wp-content/plugins/ink-core/
src/Challenges/SinglePage.php`, `wp-content/themes/ink-foundation/patterns/
reading-uitdaging.php`, `wp-content/themes/ink-foundation/theme.json`,
`tests/Unit/Challenges/SinglePageTest.php`.

---

### ontdek — third-pass re-verification (2026-09-05)

Re-verified the `7884c90`/`0bc59ba` tab rebuild from scratch per the retrospective's rule ("don't trust
prior git history") — Tier 0 structural walk of `Browse.tsx` against `ontdek.php`/`WorksArchive.php`/
`SkrywersTab.php`/`Search.php`, then all 4 stateful behaviours actually triggered in-browser (not inferred),
then a property-diff on anchored elements. Three real findings surfaced, two fixed in this pass, one is a
significant pre-existing functional bug well outside a style pass's scope — flagged, not guessed at.

**1. Sticky tab bar — confirmed genuinely absent, now built.** Lovable's `<section className="... sticky
top-16 z-30 bg-background/95 backdrop-blur-sm">` around `Browse.tsx`'s `TabButton` row keeps it pinned under
the site header while scrolling. Checked `ontdek-tabs.js` directly (per the task's explicit instruction not
to assume) — it's pure show/hide + `aria-selected` + `#hash` toggling, no sticky/scroll logic at all; and
`theme.json` had zero `position:sticky` anywhere in the whole theme (grepped). Confirmed by scrolling the
live page: the tab bar scrolled away with the rest of the content. Fixed: gave the tab bar's wrapping
`wp:group` a `className:"ink-ontdek-tabbalk"` (`patterns/ontdek.php`) and added
`.ink-ontdek-tabbalk{position:sticky;top:var(--wp-admin--admin-bar--height, 0px);z-index:30;
background-color:color-mix(in srgb, var(--wp--preset--color--surface) 95%, transparent);
backdrop-filter:blur(4px)}` (`theme.json`). WP has no sitewide sticky site-header to pin below like
Lovable's `top-16`, so this sticks directly to the viewport top — except the theme has no sitewide sticky
header at all (a bigger, separate decision, out of this page's scope), so the closest true match achievable
page-locally is implemented. The `var(--wp-admin--admin-bar--height, 0px)` offset was a real bug caught
mid-fix, not a guess: the first cut used a bare `top:0`, which sat correctly for a logged-out visitor but
was rendered fully behind WP's own black admin toolbar for a logged-in user (its top ~13px, where the tab
labels/icons live, invisible — only the bottom border/underline peeked out below the toolbar). Verified via
`getBoundingClientRect()` + a screenshot before and after the admin-bar-height fix.

**2. Search/filter/sort — confirmed a real server round-trip, and that's correct, not a bug.**
`Search.php`/`WorksArchive.php`/`SkrywersTab.php` are all documented AD-7 (no REST/AJAX for discovery) —
every filter pill, sort pill, and the search submit are real `<a href>`/`<form method="get">` full-page GET
reloads, confirmed live (`location.href` changed to `?soek=koei`, `?skrywer_genre=digkuns`, etc., and typing
in the search field alone — no submit — produced no live recompute, matching the documented design). This
is architecturally different from Lovable's instant client-side `useMemo` recompute, but it is the
already-decided, documented WP-native choice for this page, not a regression — no fix needed.

**3. Tab-switch persistence — confirmed still correct.** Set the Skrywers tab active, applied the Digkuns
filter (a real GET reload to `?skrywer_genre=digkuns`), then did a genuine `location.reload()` (not just a
click) — the Skrywers tab stayed active with Digkuns still selected, per `ontdek-tabs.js`'s documented
query-string-prefix-wins `initialTabKey()` logic. No regression since `0bc59ba`.

**4. Save/Follow toggles — not a sync bug, because the feature doesn't exist on this page at all.** The
prompt's premise (check for the cross-instance-sync bug class already found twice this session in
`volg.js`/the pin toggle) doesn't apply: `WorksArchive::cardHtml()` and `SkrywersTab::cardHtml()` render no
`ink/leeslys-knoppie` (save-to-reading-list) or `ink/volg-knoppie` (follow) markup at all, and neither
`leeslys.js` nor `volg.js` is enqueued on `is_page('ontdek')` (`functions.php` gates them to singular
works / `my-profiel` / author archives only). Confirmed by diffing the raw server HTML body for those class
names (zero matches) rather than trusting the theme's sitewide inlined CSS (which *does* reference all three
class names, harmlessly, since `theme.json`'s `styles.css` is emitted globally regardless of which blocks
are actually on a given page — a red herring caught before it became a false "found it" claim). Lovable's
`StoryCard` has a Bookmark toggle on every card and `WriterCard` has a Follow button on every card — a core
interactive affordance on the browse page, structurally absent on WP's equivalent. **Flagged, not built**:
wiring this in means rendering the existing `leeslys`/`volg` block markup inside two different archive
card renderers plus enqueuing their scripts on this page — a feature-vs-style scope call (per the standing
"default to match Lovable" rule, exactly the kind of call that gets escalated rather than silently decided),
sized similarly to my-profiel's deferred structural gap. Needs a product-owner decision on whether to build
it now or continue deferring.

**Also found, unprompted, while verifying #2 (real-content search): the works/skrywers search index is
populated for almost none of the real production content.** Typed a search term that exactly matches a real
(non-fixture) published post's title — `"koei"` against "Oom Jaap en sy koei" — and got "Probeer 'n ander
soekterm of blaai deur alle artikels" (no results). `Search::render()` matches against `_ink_soek_indeks`
post-meta (`SearchIndex::WORKS_META`), which is populated only by a `save_post` hook — so any post whose
row was written directly (bulk migration import) rather than through `wp_insert_post`/`wp_update_post`
since the search feature shipped never got indexed. Verified via `./tmp/wpcli.sh eval` against the real DB
(not guessed): **6 of 10,976** published readable works (`gedig`/`storie`/`artikel`) carry
`_ink_soek_indeks`; **3 of 322** writers carry `ink_skrywer_soek_indeks`. Search on `/ontdek/` effectively
does not work for over 99.9% of real content right now. This is a real (non-fixture) content/data-format
problem per the standing rule — **flagged, not fixed**: it needs a one-off backfill migration (loop every
readable published post through `SearchIndex::onSavePost()`'s index-assembly logic, same for every writer
through `SearchIndex::rebuildSkrywer()`), which is a backend/data operation out of a style-and-behaviour
audit's scope, not a routine CSS fix — sized like the Epic 16 Migration module's one-off WP-CLI commands,
not guessed at or attempted here. Screenshot evidence: `tmp/ontdek-screenshots/03-wp-search-real-content-zero-results.jpg`.

**Also noted, informational only:** WP's `/ontdek/` renders three "Nuwe stemme" / "Onlangs aktief" /
"Skrywers soos jy" discovery-surface rows (`ink/ontdek-vlakke`, `DiscoverySurfaces.php`, Story 8.5/FR-36)
above the Bydraes/Skrywers tabs — real, deliberately-built, tested INK-native functionality with no
equivalent anywhere in Lovable's `Browse.tsx` mock. Not a fidelity gap to close (Lovable's mock never
modelled this surface at all), but flagged so a product owner can confirm it's meant to stay rather than
this being silently assumed correct.

**Property-diff table (anchored elements, both sides measured live via `getComputedStyle()`):**

| Element | Property | Lovable | WP (before) | WP (after) |
|---|---|---|---|---|
| Search input | `font-size` | 14px | 16px | **14px (fixed)** |
| Search input | `height` | 48px | 45.5px | unchanged (minor, not chased) |
| Active tab button | `font-size`/`font-weight`/`border-bottom` | 14px / 500 / 2px `#EC3B13` | identical | identical |
| Card | `border-radius` | 8px | 8px | identical |
| Card | `box-shadow` | `0 1px 2px rgba(0,0,0,.05)` (near-flat) | `0 2px 12px -2px rgba(24,29,37,.06)` (visibly lifted) | unchanged — shared `.is-style-card` convention reused sitewide from the already-fidelity-matched tuisblad card; treated as an established design-system choice, not chased |
| Tab bar | `position` | `sticky` | `static` | **`sticky` (fixed)** |

Screenshots: `tmp/ontdek-screenshots/01-wp-sticky-tabs.jpg` (fixed sticky bar, admin-bar-aware),
`02-lovable-live-search-filter.jpg` (live client recompute), `03-wp-search-real-content-zero-results.jpg`
(the index bug), `04-lovable-writer-card-follow-button.jpg` + `05-wp-skrywer-card-no-follow-button.jpg`
(the missing save/follow affordance).

Tests: `composer test:unit -- --filter=Ontdek` — `OntdekTemplateTest` 2/2 pass (no regression from the
`className` addition). Full `composer test`: 1331 passed, same 4 pre-existing Integration-suite DB failures
(`CommentInsertionTest`, `SubmissionGateTest`, `TierWriteTest` ×2), no new failures. `composer stan`: clean
(207/207). `composer deptrac`: same pre-existing `Kernel\Activation → Content\PostTypes` finding only,
unrelated to this page. Files touched: `wp-content/themes/ink-foundation/patterns/ontdek.php`,
`wp-content/themes/ink-foundation/theme.json`. Deployed via `tools/deploy-to-local.sh --apply`, verified with
`diff -rq` against the Local copy (only stray `.DS_Store` files differed).

---

### gemeenskap — third-pass re-verification (2026-09-05)

**Headline: the stale-decision check.** Read `EXPERIENCE.md`'s page-map row and Story 15.2's AC directly
(not just trusted the `f7cc536` write-up's summary of them), and compared both against `Community.tsx` as it
reads today. Conclusion: **not stale, but mischaracterized.**

- `EXPERIENCE.md`'s page-map row for gemeenskap lists only "value props, principles, how-it-works, and
  CTAs" — it never mentions live stats or a spotlight block at all, in either direction.
- Story 15.2's AC #3 says, verbatim: *"Dynamic surfaces from the Lovable design — live statistics counters
  and the 'Kollig'/spotlight featured writer-reader — are NOT built here; they need live data and belong to
  a future ink-core block. Documented as deferred."* This sentence already, at authorship time (2026-06-28),
  explicitly acknowledges Lovable's design has both sections — the decision was never "Lovable doesn't have
  this," it was "this needs a real data source and a future block, so it's deferred." `Community.tsx`
  having both sections today is therefore not new information; the citation was written with full knowledge
  of them.
- Reinforcing that this is a live, intended-to-be-built feature rather than a closed "doesn't apply to INK"
  call: `docs/ui-copy-translations.md` already carries fully ratified Afrikaans copy for both the
  "Statistieke" section (line ~293, all 4 stat labels) and the "Kollig" section (line ~302, spotlight
  eyebrow/H2/card labels) — copy debt was already cleared for content that has never been built.

So the citation itself holds up — it is not stale, and Lovable's content hasn't changed since it was made.
But the `f7cc536` pattern docblock's phrasing (*"confirmed out of scope by citation, not just asserted"*)
overstates what the citation says: Story 15.2 says **deferred pending a future dynamic-data ink-core block**,
not **out of scope**. "Out of scope" reads as a permanent design decision; "deferred" is an owed feature with
copy already sitting ready. This is the same class of open item as my-profiel's structural gap and ontdek's
missing save/follow affordance — a real feature-vs-style scope call that was correctly not built during a
style pass, but should not be described as closed. Corrected the pattern's docblock wording accordingly
(`patterns/gemeenskap.php`) so a future reader doesn't read "out of scope" as "never build this."

**Tier 0 (structural correspondence).** Full-page scroll-through screenshot comparison at 1400px against
`Community.tsx`/`http://localhost:8080/community` section by section: hero, two-column Vir
skrywers/Vir lesers value cards, Hoe INK werk (numbered steps), Gemeenskapsbeginsels (2×2 left-border cards),
closing inverted CTA. Every section present on WP matches Lovable's structure, order, and (once the two
fixes below landed) near-pixel-identical visual rhythm — the `f7cc536` rebuild held up completely under
fresh scrutiny, unlike lees-gedig's and uitdagings-single's second-pass work. Only the two Story-15.2-deferred
sections (stats strip, spotlight) are absent, as expected.

**Tier 1 (data-audit-id-anchored property diff).** Added matching `data-audit-id` attributes to both
`ink-lovable/src/pages/Community.tsx` (uncommitted, separate repo) and
`wp-content/themes/ink-foundation/patterns/gemeenskap.php` (hero, both hero buttons, the Vir-skrywers card +
icon + H2, Hoe-INK-werk section + H2 + first step `<li>`, Gemeenskapsbeginsels section + H2 + first principle
card, closing CTA section + H2 + both CTA buttons). `getComputedStyle()` diffed live on both sides at
`window.innerWidth` 1400 (verified equal on both tabs). Two real, fixed findings:

| Element | Property | Lovable | WP (before) | WP (after) |
|---|---|---|---|---|
| Hero secondary button ("Sluit aan as leser") | `font-family` | Inter (outline variant has no `font-serif`) | Lora | **Inter (fixed)** |
| Closing-CTA secondary button ("Kyk eers rond") | `font-family` | Inter | Lora | **Inter (fixed)** |
| Hero secondary button, `:hover` | `background-color` / `color` | `rgb(82,122,102)` (accent sage) / `rgb(253,253,252)` | `rgb(237,233,224)` (secondary, unchanged) / unchanged | **`rgb(82,122,102)` / `rgb(253,252,250)` (fixed, exact-token match)** |
| Card icon circle, card H2, step badge, principle card, CTA H2 | font-size/weight/color/radius/bg | — | — | identical, no fix needed |

Root cause of both: `ink_foundation_icon`-wrapped `.wp-block-button__link` inherits the theme's sitewide
button default (`elements.button.typography.fontFamily: display` → Lora) unconditionally, but Lovable's
`Button` component (`src/components/ui/button.tsx`) only applies `font-serif` on its `literary`/
`literary-outline` variants — the plain `outline` variant (used for both of gemeenskap's secondary buttons)
inherits the body sans-serif font instead, and its hover state pulls from Tailwind's shared `hover:bg-accent
hover:text-accent-foreground` classes (`--accent: 150 20% 40%` = INK's own sage-green `accent` token,
`#527A66`) — a real, sitewide Lovable button convention, not a one-off. Fixed page-scoped on
`.ink-gemeenskap-btn-neutral .wp-block-button__link` (font-family + hover bg/color) in `theme.json`'s global
CSS string; the dark-CTA twin (`.ink-gemeenskap-btn-neutral-dark`) already had a correct *custom* hover
(Lovable's CTA band overrides the outline default with its own `hover:bg-background hover:text-foreground`
classes) and only needed the font-family fix, not the hover-color fix.

**Flagged, not built — cross-page consistency note:** this "outline-variant buttons use body font and turn
accent-sage on hover" is Lovable's shared `Button` component behavior, so it likely applies wherever any
other page uses a plain (non-`literary-outline`) secondary button — worth a sweep across already-verified
pages at some point, but out of this page's blast radius to chase now.

**Tier 2 (real hover triggers, re-measured after mouse-away, per the retrospective's lesson #2).** Both
fixed hover states triggered via real mouse `hover`, screenshotted mid-hover (sage-green fill confirmed
visually, not just via computed style), then the mouse moved away and re-measured: hero secondary button
reverts to `rgb(253,252,250)`/`rgb(26,29,33)` (confirmed via both `getComputedStyle()` and
`el.matches(':hover') === false`); primary CTA button's pre-existing terracotta-light hover
(`rgb(236,59,19)` → `rgb(239,104,66)`) also re-verified live (an initial false-negative reading during this
check turned out to be a stale-scroll-position mismeasurement, not a bug — caught by re-testing with the
element in view and `matches(':hover')` before trusting the "no change" result).

**Narrow-viewport rendering unverified** — same known `resize_window` tool unreliability recorded elsewhere
in this doc: `window.innerWidth` stayed at 1400 after repeated resize attempts (500px and 420px both
requested) despite a successful-looking response. Not chased further; the page's CSS uses the same
`@media (min-width:768px)` step pattern already verified responsive on other pages (hero/card padding,
H1/H2 sizes all step down at that breakpoint, matching Lovable's own `md:` Tailwind breakpoint).

Tests: full `composer test` — 1331 passed, same 4 pre-existing Integration-suite DB failures
(`CommentInsertionTest`, `SubmissionGateTest`, `TierWriteTest` ×2), no new failures. `composer stan`: clean
(207/207, sandbox off). `composer deptrac`: same pre-existing `Kernel\Activation → Content\PostTypes`
finding only, unrelated to this page. Files touched:
`wp-content/themes/ink-foundation/patterns/gemeenskap.php` (data-audit-id instrumentation + corrected
docblock wording), `wp-content/themes/ink-foundation/theme.json` (2 button fixes: font-family + hover
color, both on `.ink-gemeenskap-btn-neutral*`) — plus `data-audit-id` instrumentation (kept, harmless) in
`ink-lovable`'s `src/pages/Community.tsx` (separate repo, uncommitted). Deployed via
`tools/deploy-to-local.sh --apply`, verified with `diff -rq` against the Local copy (clean, no diff) after
each change.

### skryf — third-pass findings (2026-09-05)

Tier 0 found the challenges checklist rendering **after** the body textarea instead of Lovable's
type→challenges→title→body order — fixed, with a code comment explaining the field-order contract so it
doesn't drift back. The post-submit success screen had zero CSS (bare heading/text-link buttons) — rebuilt
as a real bordered/padded card with icon, heading, lead paragraph, and two proper `is-style-ink-*` CTA
buttons, matching Lovable's banner structure. Publish-button disabled state (`disabled={!title.trim() ||
!content.trim()}` in Lovable) was **never disabled at all** on WP — the same "button never disabled" bug
class as one of the original 17 lees-gedig findings that started this whole rework — fixed via
`skryf-counter.js`. All 5 stateful behaviors (type-card swap, checkbox checked/unchecked, live word/line
counter, disabled/enabled Publish, and the success screen) verified via real triggers, including a real
end-to-end form submission through a real WP-CLI test user and real membership entitlement — not inferred.

**Real, disclosed WP-only addition, not built:** Lovable's success screen has a 3-card "Lift another writer
today" community-works recommendation grid, sourced from static demo data — no ink-core facade currently
exposes a reusable "N pieces to recommend now" read-model. Flagged in the pattern file itself with a code
comment (needs a product-owner call on the selection rule — trending? recent? excluding own work? — before
it's buildable), not silently invented.

**Separate, significant finding, outside theme scope:** the entitlement gate (`ink_membership_plan_products`
option + per-plan WooCommerce `_product_ids` meta) was completely misconfigured on this Local install — no
membership plan had a linked product, so **no one could ever pass the submission gate**. Fixed on the
Local site's data (not code, doesn't show in any diff) so testing could proceed. **If production has the
same gap, nobody can currently publish anything — worth an explicit pre-launch check independent of this
audit.**

Tests 1328 (unchanged — CSS/JS/markup work, no new PHP unit-testable logic), same 4 pre-existing failures,
stan clean, deptrac unchanged. Files: `patterns/skryf.php`, `assets/css/skryf.css`,
`assets/js/skryf-counter.js`.

### skrywerprofiel — third-pass findings (2026-09-05)

Found the deployed-Local-copy-instead-of-repo-copy mistake mid-task (see the general process note added to
every subagent prompt since), self-corrected, verified byte-identical via `diff` before continuing. Three
real interaction bugs found and fixed, all via actual triggered interaction, not code reading:

- **Follow/Unfollow didn't sync across the two on-page instances** (header button + bottom CTA-band
  button, both referencing the same skrywer) — `volg.js`'s `toggle()` only updated the clicked button.
  Fixed to update every `.ink-volg-knoppie[data-ink-skrywer]` match on the page. Independently re-verified
  live in both directions after the fix, with the test follow-state reverted afterward (net-zero DB
  change).
- **Share button's confirmation silently depended on `navigator.clipboard.writeText()` resolving**, which
  hung/failed in this environment — a genuine no-op on click, not just a missing style. Fixed to always
  show the confirmation (matching Lovable's own unconditional toast) plus an `execCommand('copy')`
  fallback.
- **Rating stars had no half-star logic at all** (`floor()` then all-empty remainder) vs. Lovable's
  `Math.floor` + `>= 0.5` half-star rule. Fixed to match exactly; verified via 3 new PHPUnit cases against
  `toHtml()` directly (the real install's only two writers both have zero approved reviews, so the
  live-rendered case couldn't be forced without going around the moderation gate — flagged as an
  untestable-live limitation, not skipped).

Also fixed: the header/cover gradient was painting over genre-pill content below it (z-index/stacking fix),
and the About+Accomplishments sections were independent stacked blocks instead of Lovable's side-by-side
`lg:grid-cols-3` layout (grid wrapper added). Work-card hover confirmed correct with no fix needed.

Tests 1328→1331 (+3, the star-rendering cases), same 4 pre-existing failures, stan clean, deptrac
unchanged. Files: `SkrywerProfiel.php`, `FollowToggle.php`, `volg.js`, `skrywer-deel.js`, `theme.json`.

### my-profiel — third-pass re-verification (2026-09-05)

Scoped deliberately narrow per the page's already-documented major structural gap (no tab shell, identity
strip, "Wie ek volg" list, or notifications panel — logged, product-owner-confirmed to defer, not
re-litigated here). Re-verified what currently exists: pin/unpin toggle (`vasgespel.js`) confirmed working
correctly both directions via real clicks + `read_network_requests` showing real 200s + a clean DB residue
check; no follow-toggle exists anywhere on this page to test (`FollowingFeed` renders read-only cards, zero
button markup — confirmed via source and live DOM, so the cross-instance-sync bug class just found on
skrywerprofiel has nothing to act on here). One correction to the task's own framing: my-profiel has no
Lovable-equivalent "stats strip" at all (that only exists on the *skrywerprofiel* side, already verified) —
its own private surfaces (Leesgetalle, Gradering) are FR-40/44b features with no Lovable counterpart to
diff against, same treatment as lidmaatskap/kontak.

**New, disclosed, not fixed:** Leeslys renders a bare empty `<ul>` with no message when empty, inconsistent
with its three sibling sections on the same page (all of which show an authored "Jy het nog geen …"
sentence) and with Lovable's own explicit "Nothing saved yet" empty state. No approved Afrikaans copy
exists for this string — copy-debt-process territory, not invented inline.

**Session-significant side effect: found and confirmed working DB access from this shell environment**,
solving a limitation documented since 2026-08-31 — see the new note under "Local WordPress debugging
notes" (`./tmp/wpcli.sh`). No code changes made this round (verification found no defect needing one).
Tests unchanged at 1331, stan clean, deptrac unchanged.

### auth — third-pass re-verification (2026-09-05)

**First time this pass Lovable's live preview was actually reachable for auth** — the second pass
(`2654ea6`) explicitly could not reach `preview--quill-muse-heart.lovable.app` (site-wide 500 error all
session) and reconciled purely from source. Two real, previously-invisible gaps surfaced from finally
seeing it live, both flagged for a product-owner decision, not silently actioned:

- **Lovable's auth pages render with zero site chrome** (no header/nav/CTA) — just a centered logo + card.
  WP keeps the full sitewide header on `/meld-aan/`/`/registreer/`/`/wagwoord-herstel/`. A chrome/focus
  decision, orthogonal to the already-settled separate-URL-vs-tabbed-card question (that verdict still
  holds even seeing it live).
- **Lovable has a working Google/Apple OAuth row; WP renders nothing there.** Not an oversight — a tested
  graceful-degradation seam already exists (`ink_foundation_social_login_available()`, Story 3.5/R6) that
  correctly emits nothing because no vetted social-login plugin is active. The gap is real and now visually
  confirmed, not hypothetical; worth a priority decision.

**4 real CSS bugs found via live `getComputedStyle()` on both sides and fixed:** submit-button
height/padding (was 44px/`0 32px` — a recipe real elsewhere on the site, wrongly generalized onto these
plain-sized buttons that pass no `size` prop — corrected to Lovable's actual 40px/`8px 16px`), input height
(was implicit/font-derived ≈35px, fixed to 40px), card padding (was 24px — a `.is-style-card` block-style
rule was silently winning the cascade over the page's own 32px override, exactly the "CSS looks right but
never verified it applies" bug class the retrospective warned about — fixed with `!important`), and a
duplicate `::before{content:'INK'}` rule on the WP-core reset-password screen rendering a literal
"INKINK" header (removed).

**Confirmed still accurate, not re-litigated:** BuddyPress still intercepts the registration POST before
WP's `init` fires (live-reconfirmed via a direct `curl` POST). `resize_window` now floors at exactly
**500px** regardless of requested target (390/375/320 all landed at 500, verified via JS every time) — a
specific, reproducible number for the next attempt, replacing the previous flat "didn't work."

**Incident, disclosed immediately, not worked around:** while testing WP-core's native reset-password
screen, a stray keypress (meant to dismiss a browser navigation-warning dialog) instead submitted the
form's pre-filled auto-generated password, **changing the real password of the `cobus` administrator
account on the Local dev site** (local-only, not production). The subagent attempted to restore it via
`wp-cli` and correctly stopped when that meant changing account credentials without the product owner's
explicit sign-off — flagged for the user to either supply a new password or reset it themselves via the
site's own forgot-password flow. Not yet resolved as of this write-up.

Tests unchanged at 1331, same 4 pre-existing failures, stan clean, deptrac unchanged. Files:
`assets/css/auth.css`, `assets/css/wp-login-brand.css`.

---

## Fourth pass — lees-gedig/lees-storie reopened, lees-artikel added (2026-09-05)

**The product owner reviewed the reading pages live against Lovable again and found substantial,
structural differences on both lees-gedig and lees-storie, despite both being marked "Tier 0–2 done" in
the third pass.** Their exact report, verbatim, because prose summaries have already once hidden real
scope in this rework:

**Poetry** (comparing Lovable's `/read/s` against `https://nuwe-ink.local/gedig/laat-ek-jou-vertel-goud/`
and `.../vier-susters-van-4de-straat/`):
1. The word "deur" between the author image and name should not be there.
2. The bullet separator between author name and date is a different character than Lovable's.
3. Line and paragraph spacing is both wrong and inconsistent between different poems.
4. The poem body text color is clearly wrong.
5. On hover and once resonant, the heart sometimes renders outside the hover/selection background.
6. The comment block ("Responses to this poem"/Gemeenskapsreaksies) is entirely different: existing
   comments show above the compose box instead of below it, the submit button is a pill instead of
   Lovable's shape, the card backgrounds are wrong, and a "Reply" affordance lets a visitor open what looks
   like a new top-level comment instead of a real reply.

**Story** (comparing Lovable's `/read/s1` against `https://nuwe-ink.local/storie/die-wenteltrap-na-die-lig/`):
1. Missing the header-section background Lovable has behind the title/author/meta block — wanted on
   **both** gedig and storie.
2. The "Storie" pill sits too far from the top and is left-aligned (Lovable centers it).
3. The "Kies enige teks..." hint pill has the wrong background/foreground.
4. No border under the header section, and its background matches the body section (no visual separation).
5. **The poetry and story interaction models have been conflated.** Poetry: hover a line → gray background
   + outlined heart; click the heart → it fills and the line stays highlighted. Story: select text with
   normal browser text selection → a "Highlight" tooltip appears; confirming it adds to a highlight
   counter/passage count on the side. There is no hovered heart/thumbs-up/wow on story — that's the
   poetry-only mechanism. Same highlight *color* token on both, entirely different *mechanism*.
6. Same comment-section defects as poetry (shared component, see below).

**Articles use a completely different design from stories, and that is itself the bug**: in Lovable,
`Short Story` and `Article` are both handled by the exact same non-poetry render branch — there is no
Article-specific layout anywhere in the reference. WP built `reading-artikel.php` as an entirely separate,
bespoke pattern instead of reusing `reading-storie.php`'s shape.

**New feature request, not present in Lovable, explicitly asked for on top of fidelity**: auto-detect URLs
in prose body text (storie/artikel) and turn them into anchors — open in a new tab, styled the same as the
footer's links (traditional underline added on top), with an "open in new tab" icon appended to the link
text.

### Ground truth pulled directly from Lovable source (not re-derived from screenshots)

`src/pages/ReadStory.tsx` is the **single shared component for Poetry, Short Story, and Article** —
confirmed against `src/data/works.ts` (`WorkType = "Poetry" | "Short Story" | "Article"`). The only branch
in the whole file is `isPoetry = work.type === "Poetry"`; every other line of logic (badge color/copy,
title size, hint pill, floating engagement bar, Author Section, Critiques section) applies identically to
Short Story and Article. There is no third branch anywhere. This means `reading-artikel.php` should be
near-identical to `reading-storie.php` — same terracotta/primary badge pill (not artikel's own uppercase
eyebrow), same title sizing, same "Kies enige teks..." hint pill, same sticky `enkel`-variant engagement
bar as a direct child of `<main>`, same Author Section band, same Critiques/Gemeenskapsreaksies section —
with only the Afrikaans type label text differing, exactly the way gedig differs from storie only by its
`isPoetry` ternaries.

Confirmed structural facts from `ReadStory.tsx` (order matters, this is the literal JSX order):
`<Header/>` → highlights panel (prose only, not relevant to WP which uses a different highlight UI) →
**Story Header** section (`bg-cream/50` for poetry / `bg-secondary/30` for prose, `border-b border-border`,
centered: badge pill → title → `avatar + name` (as one `<Link>`, **no "deur"/"by" word anywhere between
them**) → `<span>•</span>` (a real bullet, U+2022, not middot) → `Clock icon + work.readTime` → hint pill)
→ **Body** (`PoetryReader` or `HighlightableText`) → **Floating Action Bar**, `sticky bottom-6`, a **direct
child of `<main>`**, unconditionally shared between poetry/prose (heart+count, `MessageCircle`+comment
count as an `<a href="#critiques">`, bookmark, share) → **Author Section** (separate gray band,
`bg-secondary/30 border-y`, 96px avatar, name, bio, Follow + "View all works" buttons) → **Critiques
Section**: heading is a **plain static string** ("Responses to this poem" / "Community Responses" — no
count prefixed into the heading text, unlike WP's current `"N Gemeenskapsreaksies"`), then the **compose
card FIRST** (intro line → 3 prompt-type pill buttons → textarea → right-aligned "Share Response" button,
`variant="literary"`, disabled while empty), then the **existing-critiques list SECOND**, each card:
avatar, author name, a type badge (Insight/Praise/Suggestion — sage/gold/terracotta tinted pill with icon),
relative timestamp, body text, then an upvote count + a **`Reply` button that does nothing at all** — it is
decorative in Lovable, not wired to any handler, not even a focus-scroll.

`src/components/reading/PoetryReader.tsx` — the exact per-line/per-stanza layout, as fixed numbers, not
values derived from `line-height`: stanzas wrapped in `space-y-10 md:space-y-12` (40px/48px gap **between**
stanzas), each stanza's lines in `space-y-2` (**8px** gap between lines within a stanza) — a large,
deliberate, fixed ratio between the two, not incidental. The heart is `absolute -right-7` (28px) relative
to a `<span className="relative inline-block">` that wraps **only the line's own text**, itself nested
inside a `block w-full` `<button>` that owns the hover/resonant background — so the background always
spans the full row width regardless of how far right the heart sits; if WP's heart is instead positioned
relative to a span that isn't nested inside a matching full-width background element, or if the
`.ink-gedig__line`'s own box (not a `w-full` ancestor) is what carries the background, long lines will push
the heart past that box's own right edge — visible-outside-background is a probable **box-model mismatch,
not a color/position value problem**, needs live DOM inspection to confirm on the two example poems given.

`src/components/engagement/ResponsesList.php`-equivalent behavior (WP's actual `ResponsesList::toHtml()`,
read directly): confirmed it renders `<ul>` (existing responses) **then** `formHtml()` (compose form) —
the literal inverse of Lovable's compose-first order. This is exactly PO finding poetry-#6/story-#6's
"comments showing above the new comment block."

### Scope of the fourth pass (not yet started)

This reopens **lees-gedig** and **lees-storie** to a fourth Tier 0–2 round, and adds **lees-artikel** to
the tracked page set for the first time (it was never a numbered row — there is no dedicated Lovable route
for it, but `works.ts`'s `Article` type combined with `ReadStory.tsx`'s branching is authoritative ground
truth that it must share storie's exact shape). Planned fix set, in one coordinated round since all three
patterns share the same underlying blocks/CSS:

- Remove the invented "deur" paragraph from all three `reading-*.php` patterns' author line; fix the
  separator character to a real bullet (•) to match Lovable exactly.
- Rebuild `reading-artikel.php` onto `reading-storie.php`'s shape (badge/title/hint/engagement-bar/
  author-card/critiques all unified) — an Article gets `reading-storie.php`'s exact layout with only its
  own type label swapped in, per the `isPoetry`-only branch confirmed above.
- Fix poem line/stanza spacing to the fixed 8px/40–48px constants (not `1lh`-derived), and re-verify against
  both example poems (`laat-ek-jou-vertel-goud`, `vier-susters-van-4de-straat`) since the legacy-markup
  parser (`GedigBody::normalizeLegacyMarkup()`) is a likely source of the *inconsistency* between poems even
  once the CSS constants are fixed — needs checking against both examples' actual stored markup shape, not
  assumed uniform.
- Fix poem body text color against a fresh `getComputedStyle()` read on both sides.
- Investigate and fix the heart-overflow-outside-background bug per the box-model hypothesis above, via
  live triggered-hover DOM inspection (per the retrospective's rule: stateful UI needs an explicit
  trigger-and-observe check, not a resting read).
- Rebuild `ResponsesList::toHtml()` to compose-before-list order, plain (non-count-prefixed) heading copy,
  matching card/pill/button shapes, and make `Reply` either genuinely decorative (matching Lovable exactly)
  or flag a real threaded-reply feature decision to the product owner rather than the current
  focus-the-top-level-form behavior, which the PO has now flagged as actively misleading.
- Add the storie/artikel header-section background + border-under-header + hint-pill color fixes.
- Implement the real text-selection → "Highlight" tooltip → highlight-counter mechanism for storie/artikel
  (this was flagged as an open PO decision after the third pass and is now confirmed, not optional — the
  third pass's poetry-style hover/heart treatment must **not** leak onto storie/artikel).
- New feature (PO-requested, not a fidelity fix): auto-linkify bare URLs in storie/artikel prose body into
  `target="_blank" rel="noopener"` anchors, styled like the footer's link recipe plus an underline and a
  trailing "opens in new tab" icon glyph.

Dispatched to a subagent (interrupted once by a session-limit error mid-round, resumed from transcript
with no work lost — confirmed via `git diff` before and after resuming). Full fix list implemented; see
subagent's own file list above the retrospective note below.

### Fourth pass — orchestrator independent verification (2026-09-05)

Every item below was re-checked fresh by the orchestrator, not accepted on the subagent's own report:

- `git status` confirms the exact file list the subagent claimed, no more, no less.
- `composer test` (1333 passed, same 4 pre-existing Integration failures), `composer stan` (clean, sandbox
  disabled for the known TCP-listen gotcha), `composer deptrac` (same pre-existing 3 Kernel→Content
  violations, 0 new) — all independently re-run, not trusted from the subagent's own numbers.
- Fresh `curl` on both example poems and the storie page: "deur" gone, separator is a real `•` (confirmed
  byte-for-byte, not `&middot;`/`·`).
- `artikel` confirmed unified onto storie's shape: same `.ink-lees-tipe has-primary-color` badge class, an
  `.ink-outeur-kaart-band` author-card section now present, no leftover full-`'volledig'`-variant reaction
  markup, `ink-reading-main` class present on `<main>` (needed for the sticky bar's containing block).
- **Real triggered interaction, poem heart**: hovered a line, screenshotted, clicked it, confirmed via
  `getComputedStyle`-adjacent DOM read that `has-reaksie`/`is-active` actually applied, then a fresh
  screenshot showed the heart filled red *inside* the full-width highlighted row — the box-model fix holds
  on a real long line, not just in theory. Toggled back off afterward to leave state clean.
- **Poem-spacing "inconsistency" investigated at the data level, not just re-measured in CSS**: pulled
  `laat-ek-jou-vertel-goud` (67798) and `vier-susters-van-4de-straat` (67847)'s raw `post_content` directly
  via `./tmp/wpcli.sh post get`. Confirmed independently: `vier-susters` really is stored with a full blank
  line between every single line (one-line-per-stanza, by the original author/legacy capture, not a parser
  misclassification) — the subagent's "no parser bug, genuinely different real structures" conclusion holds
  under direct inspection of the exact two posts the product owner named, not a substitute pair.
- **Real triggered interaction, story highlight**: selected a text span on `die-wenteltrap-na-die-lig`,
  watched the "Merk uit" tooltip appear at the selection, clicked it, confirmed the highlight persisted
  (yellow mark) and the side panel's count badge went from empty to `1`, opened the panel and confirmed the
  quoted-passage card ("Jou uitgeligte gedeeltes"), then removed it via the panel's own control and
  confirmed the DOM returned to zero marks/zero panel items — the full cycle, not just the tooltip
  appearing. (One false alarm during this check: the panel initially looked clipped off the right edge of
  a screenshot — turned out to be the screenshot tool's own pixel-to-CSS-pixel scale factor the subagent
  had already flagged in their report; `getBoundingClientRect()` confirmed the panel sits fully inside the
  real viewport.)
- **`ResponsesList` rebuild confirmed live**: compose card renders above the existing-response list (not
  below), the heading is the plain "Gemeenskapsreaksies" string with no count prefix, the submit button is
  a rounded-rectangle "Plaas" button in its disabled tint (not a pill), and the "Antwoord" (Reply) button
  has no `data-ink-reply-target` and no click handler at all — clicking it live does nothing observable,
  confirming it's genuinely inert like Lovable's, not silently still wired to focus the compose box.
- **Header background/border confirmed via computed style**, not eyeballed: the storie header section
  computes to a real `color(srgb .93 .91 .88 / 0.3)` tint with a `1px` bottom border, while the body section
  beneath it is fully transparent with no border — real, measurable separation, matching the PO's complaint.
- Read (not live-fixture-tested, since the subagent's own QA fixture was already cleaned up) the new
  `ink_foundation_autolink_prose_urls()` implementation in `functions.php` directly: gated to
  `is_singular(['storie','artikel'])` only, skips text already inside an existing `<a>` via a split-and
  -rejoin on anchor tags, peels trailing sentence punctuation off the URL before linking it, and emits
  `target="_blank" rel="noopener noreferrer"` plus the trailing icon — logic reads correct on inspection.

No discrepancies found between the subagent's report and independent re-verification. **lees-gedig,
lees-storie, and lees-artikel are now marked done for this fourth pass.** Outstanding, not fixed by this
round (flagged by the subagent, not silently dropped): the new highlight-feature Afrikaans copy hasn't been
through the formal copy-debt translation-sheet pipeline yet (flagged as copy-debt, same process as every
other page); the storie/artikel header background/border reuse the sitewide `secondary`/`border` tokens
rather than hand-matching Lovable's precise RGB values, which were found to drift a few units from this
theme's existing tokens — a pre-existing, out-of-scope sitewide precision gap, not touched this round.

### Two direct follow-up removals, same day (2026-09-05), orchestrator-authored not subagent-dispatched

Two more product-owner calls came in immediately after the fourth-pass verification above, both small
enough to fix directly rather than round-trip through a subagent:

1. **The response-card "Reply"/"Antwoord" button was removed outright, not just left inert.** The fourth
   pass had made it a genuine no-op to match Lovable's own dead `<button>Reply</button>` (no `onClick`
   anywhere in `ReadStory.tsx`). Told directly: "What would the point of a decorative link be?" — correct;
   matching Lovable's fidelity doesn't extend to reproducing a control with no effect when activated.
   Removed from `Ink\Engagement\ResponsesList::toHtml()` (button markup gone, `.ink-reaksies__footer` now
   holds only the upvote count), the `antwoord` term retired from `Ink\I18n\Terms`, the dead
   `.ink-reaksies__reply` CSS removed from `theme.json`, the stale explanatory comment in
   `assets/js/gemeenskapsreaksie.js` rewritten, and the test renamed to assert the button doesn't render at
   all (was asserting it rendered-but-unwired). `composer test` unaffected beyond the assertion change (same
   4 pre-existing Integration failures), `stan`/`deptrac` clean, confirmed removed live via a fresh curl.
2. **The `ink/leesprompte` ("Reageer met bedoeling") panel was deleted entirely, class and all** — not
   merely unembedded. It predates this rework (Story 7.5/FR-30) and sat between the body and the
   Gemeenskapsreaksies form on storie/artikel only (gedig already excluded it by an earlier product-owner
   call). Told directly it read as broken Afrikaans, matched nothing in Lovable, and served no purpose.
   Removed the block embeds from `patterns/reading-storie.php`/`reading-artikel.php`, deleted
   `Ink\Engagement\ContextualPrompts` (the class) and its registration in `Engagement\Module`, deleted
   `tests/Unit/Engagement/ContextualPromptsTest.php`, removed the `.ink-leesprompte*` CSS from `theme.json`,
   and collapsed `ReadingTemplatesTest.php`'s two prompt-specific tests (one asserting it was present on
   storie/artikel, one asserting gedig deliberately omitted it) into a single test asserting no reading
   pattern embeds it any more. `composer test` (1330 passed, same 4 pre-existing failures — 3 fewer than
   before from the deleted test file, 2 further fewer from the Reply-test rewrite), `stan` (206 files now,
   was 207, clean), `deptrac` (same 3 pre-existing violations) all re-run and confirmed clean. Deployed via
   `deploy-to-local.sh --apply` (sandbox disabled for the known rsync-permission gotcha) and confirmed
   removed live via a fresh curl (0 matches for `leesprompte`/"Reageer met bedoeling") and a screenshot —
   the author-card band now flows directly into "Gemeenskapsreaksies" with nothing between them.
3. **The reading-page byline (avatar+author-name) was rendering brand-red and underlined** — reported by
   the product owner, who guessed it was caused by the new URL-autolink feature. The actual root cause,
   confirmed via `git diff`, was a different change from the SAME fourth-pass round: `isLink:true` was
   added to both `wp:avatar` and `wp:post-author-name` (the subagent's own disclosed judgment call #1,
   approximating Lovable's single `<Link>` wrapping both). Before that, neither block rendered as an `<a>`
   at all, so the sitewide `elements.link` style (`primary` color, `accent` on hover — confirmed unchanged
   since before this whole rework via `git show HEAD:theme.json`) never touched it; making them real links
   let that sitewide style bleed through, overriding the block's own explicit `textColor:"ink-text"`
   (a real WP core quirk: the color attribute lands on a wrapping element, not the inner
   `.wp-block-post-author-name__link`/`.wp-block-avatar__link` anchor itself). Checked against Lovable's
   actual source (`ReadStory.tsx`'s `<Link to=... className="... hover:text-foreground">`) to confirm the
   right fix: Lovable's own byline link carries no special link color/underline at all — it deliberately
   looks like plain text, matching what "reset to what it was" should mean. Fixed with two new CSS rules
   in `theme.json` targeting `.wp-block-post-author-name__link`/`.wp-block-avatar__link` (`ink-text`, no
   underline, unchanged on hover) — kept the link itself (a genuine Lovable-matching fix, not reverted)
   but suppressed the sitewide link styling that made it look like a normal hyperlink.
4. **Same investigation surfaced the prose auto-link color was also wrong**, for the same underlying
   reason: `.ink-prose-link` never set its own `color`, so it silently inherited the same sitewide
   `primary`/brand-red instead of the footer's actual link color, which the original feature docblock had
   *assumed* was "primary colour, inherited" without checking. Checked live via `getComputedStyle()` on a
   real bottom-of-page footer nav link (not the "INK" site-title link, which is a separate dark/`ink-text`
   treatment): footer nav links render in `muted-text` (`#6B7280`), a genuine gray, never brand-red. Fixed
   `.ink-prose-link` to set `color:muted-text` explicitly (keeping the underline, per the original request
   — "same color as footer... except with the traditional link underline added"). Verified end-to-end with
   a temporary QA-fixture storie post containing two URLs (one bare, one parenthesized-with-trailing-
   punctuation): both rendered gray, underlined, with the trailing icon, punctuation correctly excluded
   from the link — fixture deleted after. `composer stan` (206 files, clean), `composer test` (1330 passed,
   same 4 pre-existing failures) both re-run, no regression from the CSS-only change.
5. **The Artikel type-pill was deliberately recoloured to gray, diverging from Lovable on purpose** — a
   direct product-owner request, not a bug fix: "Poetry is sage, Story is the brand orange... let's try
   matching shades of gray for the articles," specifically so Article reads as visually distinct from Story
   at a glance. This is a genuine, disclosed exception to the fourth pass's own "Article gets Short Story's
   exact shape" finding (`ReadStory.tsx`'s only branch is `isPoetry` — Lovable itself renders Article in the
   identical terracotta pill as Short Story, confirmed earlier this pass) — the badge's `textColor` in
   `patterns/reading-artikel.php` changed from `primary` to `muted-text`; no new CSS needed since
   `.ink-lees-tipe`'s background is `color-mix(in srgb, currentColor 10%, transparent)`, so it auto-tints to
   whatever text color is applied. `data-audit-id="storie-badge"` was deliberately left unchanged (it
   anchors which Lovable branch the element structurally corresponds to, not the colour rendered).
   Everything else on the artikel page (title sizing, hint pill, engagement bar, author-card band) stays
   identical to storie's shape — only the pill's colour diverges. Verified live on a real published artikel
   post: gray pill, clearly distinct from storie's orange and gedig's sage. `composer stan` clean (206
   files), `composer test` unaffected (no test asserted the prior `has-primary-color` class on this pill).

---

## Fourth pass — tuisblad (2026-09-05)

The product owner reviewed the live homepage against Lovable directly and reported 15 items verbatim.
Dispatched as a single fourth-pass fix round (mirroring the reading-page method above), with the two
trickiest root causes pre-diagnosed by the orchestrator before dispatch (both confirmed live via
`getComputedStyle()`/`getBoundingClientRect()` before handing off): the sticky header genuinely wasn't
sticking (at scrollY 1200 its `top` read a large negative value, not 0), and the "Uitdaging" pill's text
colour measured `#B13317`, not the brand `primary` Lovable's "Weekly Challenge" pill uses. Every item was
independently re-verified by the orchestrator afterward — live computed-style/rect checks on the deployed
site, a fresh `git diff` read of every changed file, and an independent re-run of `composer test`/`stan`/
`deptrac` — before being marked done here; nothing below is taken on the subagent's word alone.

**A live Lovable preview was reachable this pass** (`https://preview--quill-muse-heart.lovable.app/`) —
past sessions' "unreachable, 500 error" notes are now stale; real Tier 2 screenshot/computed-style diffing
against the actual reference was possible for the first time on this page.

1. **Button sizes (hero "Begin lees"/"Deel jou werk", challenge-card "Skryf in") — real, fixed.**
   `.ink-btn-lg`/`sm`/`xl` set `min-height` with WordPress core's own `.wp-block-button__link` vertical
   padding left in place, so the floor never actually clamped anything — live-measured 49.34px/53.34px
   against a 44px target. Lovable's shadcn sizes are a *fixed* `height` with horizontal-only padding,
   flex-centred. Changed to fixed `height`, zeroed vertical padding, moved `inline-flex`/`align-items:center`
   onto the size classes themselves (not borrowed from `.ink-btn-icon`), fixed `xl`'s padding (48px→40px,
   a pre-existing mis-mapped token vs Lovable's `px-10`). `ink-btn-lg` is used only in `hero.php`;
   `sm`/`xl` are used nowhere else in the theme — no sitewide blast radius. Live-confirmed: hero primary
   44px, challenge-card CTA 40px.
2. **"Uitdaging" pill colour — real, fixed.** Root cause exactly as pre-diagnosed: a one-off a11y-motivated
   `color-mix(primary 72%, ink-text)` override on `.ink-huidige-uitdaging__kenteken`, inconsistent with the
   identical primary-on-primary/10% treatment used undarkened everywhere else on the site (reading-page
   `.ink-lees-tipe`, `.ink-hero-badge`, the featured-grid eyebrow). Removed on direct PO instruction
   ("compare with Weekly Challenge"). Live-confirmed: `rgb(236, 59, 19)`, exact match.
3. **No winner-card fixture — real, and a genuine stranded-capability gap surfaced.** `FeaturedWinners`
   sources its whole payload from one filter (`ink_home_featured_winner`) that nothing in production ever
   hooks — its own docblock still says "Epic 12A is unbuilt", which is stale (12A shipped and merged;
   see [[epic-12a-carryforward]]). Confirmed there is not even latent data: zero `ink_entry_placement` meta
   rows in the Local DB. Added `ink_foundation_homepage_demo_winner()`, a *separate* filter hook (deliberately
   not a widening of the existing QA-gallery-gated fixture — conflating those two is exactly the mistake
   already fixed once for sponsors, see #12), gated to `is_front_page()` only and yielding to any real
   payload. Documented in-code as demo content to delete the moment Challenges wires the real query.
   **Open item, not closed by this pass:** wiring `ink_home_featured_winner` to real `Placements` data is
   real Challenges feature work, not a theme fix — needs its own follow-up story/decision, tracked here.
4. **Heading copy "Hierdie week se uitgesoektes" → "In die kollig" — direct PO copy correction, applied.**
   Eyebrow "Die redakteur se keuse" left untouched as instructed.
5. **"werke" → "skrywes" — 4 real sitewide occurrences found and fixed**, all verbatim UI copy (not code
   identifiers): `Terms::sien_alle_werke`'s value (key name left alone, it's an internal identifier), the
   bare-literal duplicates in `FeaturedStream.php` and `SkrywerProfiel.php` (also re-routed through the
   `Terms` registry instead of independently duplicating the literal — a pre-existing house-convention
   violation, fixed opportunistically), and `PinnedWorksManager`'s "Vasgespelde werke". A direct PO
   terminology instruction, not AI-retranslation — applied without going through the copy-debt process.
6. **Featured-grid type-pill colours (Gedig/Storie/Artikel → sage/orange/gray) — deliberate, disclosed
   divergence, implemented.** Confirmed Lovable's own `FeaturedWorks.tsx` uses one flat `bg-secondary`
   badge for every type (live-verified: identical `rgb(240,235,230)`/`rgb(24,29,37)` on all four sample
   cards) — this is the PO explicitly asking to diverge, mirroring the Artikel-pill precedent above.
   `FeaturedStream::metaTopHtml()` now emits a `__pil--{gedig|storie|artikel}` modifier keyed on the post
   TYPE (not the pill's visible text, which can be a free-text genre term), styled with the exact tokens/
   percentages already established on the reading pages' `.ink-lees-tipe` badge, so the two surfaces can't
   drift apart. Live-confirmed: Gedig `rgb(82,122,102)` (sage), Storie `rgb(236,59,19)` (primary).
7 + 8. **"Oktober-uitdaging" box: too much internal bottom whitespace / no gap below it — one root cause,
   fixed.** None of the `.ink-*` block markup gets `box-sizing:border-box` from WP core (core only covers
   its own `.wp-block-*` classes), so every rule pairing a `height`/`min-height` with `padding` painted a
   box bigger than declared — the kenmerk card computed height 370px but painted 436px, overflowing its own
   grid row and eating the feature band's entire bottom padding. Fixed with explicit (not wildcard)
   `box-sizing:border-box` on the five affected `.ink-huidige-uitdaging*`/`.ink-wenner-kollig*`/
   `.ink-borg-strook__cta` rules. Live-confirmed: card box now exactly fills its grid area, 0px overflow.
9. **Featured-grid card internal spacing (pill→heading→body→author) — real, fixed.** Was one flat 8px flex
   `gap` for every pair; Lovable uses a *different* margin after each element (measured live: 12px pill→
   title both card types, 12px/8px title→excerpt featured/standard, 16px excerpt→footer), plus a different
   set again for the spanning featured card. Replaced the flat gap with explicit per-element margins
   matching Lovable's measured values; also dropped `margin-top:auto` on the author row (Lovable does not
   bottom-pin it — pinning it is exactly what made the gap vary card-to-card, the PO's "inconsistent" report).
10. **Sticky header — real, fixed; root cause exactly as pre-diagnosed.** `position:sticky` was applied to
    the inner `.is-style-ink-header` div, whose own containing block (the semantic `<header>` wrapper) is
    sized to exactly its height — zero room to stick, so the whole thing scrolled away with the page
    (live-confirmed pre-fix: `top` read a large negative value at scrollY 1200 despite `position`/`top`
    computing correctly). Moved the positioning to `header.wp-block-template-part`, whose own parent
    (`.wp-site-blocks`) spans the full page. This also surfaced a second real bug once it actually stuck:
    it slid under the WP admin bar for signed-in users — added a `body.admin-bar` offset (32px above 782px,
    0 below, matching the admin bar's own responsive behaviour). Global fix (site-wide header) — re-verified
    on `/ontdek/` and a storie reading page in addition to the homepage. Live-confirmed: `top:32px` at
    scrollY 1200 while logged in, header visibly present at every scroll depth.
11. **"Redakteur se keuse" outer spacing bigger than the design — real, fixed.** The section's own `s-64`
    padding already matched Lovable numerically; the extra came from WordPress's default 24px `blockGap` on
    `<main>`, silently inserted between every pair of homepage sections (and between the header and the
    first section) — Lovable's `<main>` stacks its sections edge-to-edge with 0 gap. Pinned `blockGap:"0"`
    on the front-page `<main>` group; every section now carries its own, and only its own, padding. Also
    fixed the section header's `margin-bottom` (32px → 40px, Lovable's measured `mb-10`) found in the same
    investigation.
12. **No sponsors-section fixture — real, fixed with real data.** Root cause: every `borg` post in the DB
    was `QA FIXTURE — ` titled, and `HomepageStrip` correctly excludes those from production (a previous
    fidelity-pass fix) — so after exclusion, zero sponsors remained and the section legitimately collapsed.
    Created 6 real, non-"QA FIXTURE"-titled `borg` posts (IDs 67926–67931) via the real production path,
    full active-campaign meta set, spread across goud/silwer/brons — left in place, not deleted. Making the
    section actually render exposed two further real bugs, both fixed: the block's plain `<section>` sat
    inside a *constrained* group, silently capped at 768px instead of the full 1368px wide width (six chips
    that sit on one row in Lovable were wrapping onto three); and a flat 16px gap where Lovable uses several
    different measured margins (`mt-2`/`mb-4`/`mb-10`/`gap-8 md:gap-12`). Live-confirmed: all 6 chips on one
    row at 1680px viewport.
13. **"Jou woorde verdien lesers" (CTA band) spacing wrong throughout — real, fixed.** One uniform 24px
    `blockGap` for heading/paragraph/buttons, and a 1.2 line-height heading; Lovable uses `mb-4`(16px) after
    the heading, `mb-8`(32px) after the paragraph, and a `line-height:1` 48px heading — all three measured
    live and matched exactly.
14. **Homepage overall wider than Lovable — real, fixed.** Measured at 1680px viewport: Ink content box
    1400px wide vs Lovable's 1368px — a flat 32px (16px/side) too wide, with zero horizontal overflow
    anywhere (ruled out an overflowing element). Root cause: Lovable's `container` max-width (1400px)
    *includes* its own `px-4` side padding, so its actual content width is 1368px; Ink's `wideSize` (1400px)
    was being treated as content width with the section's `s-24` padding applied on top of it. Fixed at the
    single source — `theme.json`'s `wideSize` 1400px → 1368px — and re-verified on `/ontdek/`, `/oor-ink/`
    and a storie reading page (a global token change) with zero regression. Live-confirmed: content box now
    exactly 1368px on the homepage.
15. **Huge, asymmetric spacing below the CTA band — real, PO override applied literally.** Measured both
    sides: 160px below (80 CTA-band bottom padding + 80 footer top margin) vs 144px above (64 borg-strook
    bottom + 80 CTA-band top) — Ink already matched Lovable's own asymmetry exactly, which is why the PO
    called it out as "matches Lovable, but isn't right." Applied the override as stated: CTA-band bottom
    padding `s-80`→`s-64`, so below now equals the same 144px as above. Disclosed as an intentional
    divergence from the reference in the pattern's own comment.

**Gates, independently re-run by the orchestrator (not just accepted from the subagent's report):**
`composer test` 1331 passed / 4 failed (the same pre-existing `TierWriteTest.php` failures as every other
check this whole rework — `wp_create_user()` undefined, no wp-env DB; one more test now than before, from
the new `FeaturedStreamTest` pill-modifier coverage), `composer stan` 206 files clean, `composer deptrac`
same 3 pre-existing `Activation → PostTypes` violations, 0 new. Deployed via `deploy-to-local.sh --apply`
and confirmed live (not just via the subagent's report): sticky header holds at every scroll depth tested,
pill colours exact-match Lovable's tokens, button heights exactly 44/40px, page content box exactly 1368px
wide with zero horizontal overflow, 6 sponsor chips on one row, the winner card renders real Afrikaans demo
content ("Desember se wenners" / "Desember algehele wenner"), and the CTA-band-to-footer gap measures
exactly 144px, equal to the borg-strook-to-CTA-band gap above it.

**Open item carried forward, not resolved by this pass:** the `FeaturedWinners` home-page slot has no real
production data source (see #3 above) — Epic 12A shipped the adjudication backend but never wired it to
this filter. Needs a decision on whether it becomes its own follow-up story before launch.

### Same-day follow-up: 6 shortcomings in the December-winner demo fixture (2026-09-05)

The product owner reviewed the demo winner card added above and reported 6 more items, all confirmed real
and fixed, three of them genuine pre-existing bugs surfaced only once the card actually had content to look
at (it had rendered empty on every page load before this pass):

1. **The "DESEMBER SE WENNERS" heading above the card was not supposed to be there.** Real: Lovable's
   `ChallengeSection.tsx` has no section-level heading at all — each card carries its own "[Month] Winner"
   eyebrow already. Removed from `FeaturedWinners::toHtml()` entirely (not just hidden), and the collapse
   gate moved from "is there a title" to "is there at least one valid winner" — a more meaningful check now
   that the title has no visible role. `title`/`url` are simply no longer read.
2. **Winner card and challenge card must match height — real, and it was the same underlying cause as
   #1.** The extraneous heading pushed the visible card down inside its grid cell without the wrapper
   stretching to compensate, so the two cards' visible boxes were ~51px apart even though their grid CELLS
   already matched (CSS Grid was already stretching those correctly — the mismatch was entirely inside the
   winner wrapper). Removing the heading closes most of the gap by itself; added `height:100%` on
   `.ink-wenner-kollig__kaarte` plus `flex:1` on the card when it's the wrapper's only child (deliberately
   scoped to the single-card case — a future multi-winner feed must keep each card's own natural height,
   not be squashed into equal slices). Live-confirmed: both cards exactly 381.38px.
3. **Background and text colour of "Desember algehele wenner" were wrong — the text colour was real, no
   separate background bug found.** `.ink-wenner-kollig__rang` rendered in `ink-text` (near-black) on a
   deliberate prior a11y assumption ("gold text on gold ground ≈ 1.8:1") that didn't match what the card
   actually paints (a 10-20%-opacity tint, not solid gold) — the exact same category of stale one-off
   darkening as the Uitdaging-pill fix earlier this pass. Lovable's own "December Winner" label is
   `rgb(232,177,48)` (confirmed live) with no background box behind the text at all — checked, and no CSS
   rule adds one on the Ink side either, so "background" is read here as describing the badge as a whole
   (icon tile + text), whose tile background already matched Lovable exactly before this fix. Corrected the
   text colour to `gold`; live-confirmed exact match.
4. **"Desember algehele wenner" should read "Desember wenner" — a real pre-existing bug, not a rewording.**
   `Ink\Challenges\Placements`'s own class docblock documents the glossary convention as SPACE-joined for
   both cases ("[Maand] algehele wenner" / "[Maand] wenner"), but `FeaturedWinners::cardHtml()` hyphen-joined
   the ordinary case ("Desember-wenner") — a genuine inconsistency between the class's own documentation and
   its implementation, with no test locking in the hyphenated form. Fixed the join to always use a space;
   changed the demo entry's rank from 1 (algehele) to 2 (ordinary) so it actually exercises that path,
   mirroring Lovable's own demo card (which is an ordinary win, not an overall-winner spotlight).
5. **No author avatar — real, fixed.** The demo payload never set `avatar_url`. Added Lovable's own
   placeholder photo (the same Unsplash URL `ChallengeSection.tsx` hardcodes for this exact card) rather
   than inventing a new one.
6. **"3de uitdagingwen" should be "3de wen" — direct wording correction, applied.** Notably, the *existing*
   `FeaturedWinnersTest.php` fixture already used "3de wen" as its example value — the demo's invented
   "3de uitdagingswen" was a one-off deviation from that established convention, not the other way around.

New test coverage: `FeaturedWinnersTest.php`'s collapse test now asserts the new "no valid winners" gate
(a title alone no longer renders anything); the render test asserts no heading/`<h2>` renders at all; a new
test locks in the space-join fix for ordinary winners specifically so the hyphenated form can't regress.
`composer test` 1332 passed (same 4 pre-existing failures, one more test than before), `stan` clean (206
files). Deployed and re-verified live: heading gone, both cards exactly 381.38px, eyebrow text
`rgb(232,177,48)` reading "Desember wenner", avatar rendering from `images.unsplash.com`, win label
"3de wen".

### Same-day follow-up: header sign-in/join buttons + "Meld aan" → "Teken in" sitewide (2026-09-05)

Direct product-owner report: every visitor, logged in or not, saw only "Begin skryf" in the header — Lovable
shows "Sign in" (ghost) + "Join Inkwell" (primary) when logged out, "Start Writing" only when logged in.
Separately, "Meld aan" (a competing Afrikaans label for the same sign-in concept, used inconsistently across
the auth pages, the write-page gate and the membership-renewal fallback) was reported as needing to become
"Teken in" everywhere.

**Header (`patterns/header-main.php`) — real bug, fixed.** The header pattern had no auth-state branching at
all — a static, unconditional "Begin skryf" button regardless of who was viewing. WordPress natively executes
`patterns/*.php` server-side (the Pattern File Header convention), so a plain `is_user_logged_in()` PHP
conditional inside the pattern is the correct, idiomatic fix — the same mechanism `skryf.php` and
`lidmaatskap-hernu.php` already use for their own logged-in gates. Logged out now renders a new
`is-style-ink-ghost` button ("Teken in", linking to the unchanged `/meld-aan` URL) + the existing
`is-style-ink-primary` style ("Sluit aan", `/registreer`); logged in renders "Begin skryf" exactly as before,
byte-identical markup, just now correctly gated. The new ghost style (`functions.php`) was measured directly
against Lovable's live "Sign in" button (`rgb(24,29,37)` text, transparent background, 36×~px, 6px radius —
confirmed via `getComputedStyle()`) and its hover state maps Lovable's `--accent`/`--accent-foreground`
custom properties (a sage tone, confirmed in `ink-lovable/src/index.css`) onto INK's own `accent`/`surface-alt`
tokens.

**Terminology (`Ink\I18n\Terms`) — new `teken_in`/`sluit_aan` registry keys, "Meld aan" retired sitewide.**
Grepped the whole repo for every real occurrence: the login page's own H1/intro/submit button
(`auth-login.php`), the "back to sign in" links on `auth-register.php` and `auth-forgot-password.php`, the
write-page logged-out gate (`skryf.php`), and the membership-renewal logged-out fallback
(`lidmaatskap-hernu.php`) — all switched to read `teken_in` from the registry (or the equivalent literal where
a full sentence needed the word inline) instead of independent "Meld aan" literals. Also updated: the real WP
`page` post's title for `/meld-aan/` (id 67875, was "Meld aan", now "Teken in" — slug unchanged), the
pattern's own admin-facing "Title:" header, `theme.json`'s `customTemplates` display title, and the curated
source docs (`docs/afrikaans-terms.md`'s glossary row, `docs/ui-copy-translations.md`'s translation tables) —
both explicitly documented "Meld aan" as the deliberate, curated decision for this concept, so both needed an
explicit revision note (not just a silent overwrite) recording that a direct product-owner instruction
superseded the prior decision on 2026-09-05. The `/meld-aan` URL path and the `meld_aan` notice query-arg
(`Ink\Accounts\AuthRedirects`) are deliberately UNCHANGED — this is a display-label fix, not a routing change
(the same AC-4 boundary the registry itself documents).

Verification: independent-request checks (`curl`, no session cookie) against the live homepage and all 5
affected pages confirm zero remaining "Meld aan" occurrences and the new labels rendering correctly; the
homepage's logged-out header shows "Teken in" + "Sluit aan" with no "Begin skryf" (confirmed both via a
cookie-less request and, unintentionally, via a real logged-out browser session — see note below). Ghost
button hover confirmed live (sage background, white text). `composer test` 1333 passed (same 4 pre-existing
failures, one more test than before), `stan` clean (206 files), `deptrac` same 3 pre-existing violations.

**Incident note:** while verifying the logged-out header state, following WordPress's own logout-confirmation
link (to avoid guessing at cookie manipulation) ended up logging the orchestrator's own admin browser session
out, and there are no stored credentials to log back in as that QA account. No password was reset or touched —
this is a plain session end, not a credential incident — but it means the LOGGED-IN "Begin skryf" branch could
not be re-verified live in-browser this round (only by code review: the branch is byte-identical to the
pattern's prior unconditional markup, now just correctly gated behind `is_user_logged_in()`). Flagged for the
user to re-establish a logged-in session if live re-verification of that branch is wanted.

---

### What each done row actually fixed

**Sitewide tokens (`541a57a`).** Root-caused during the tuisblad pass: WordPress kebab-cases a
digit-adjacent preset slug when generating CSS custom-property/class names (`2xl` → `--...-2-xl`, `.has-2-
xl-font-size`, etc. — see "WordPress digit-adjacent-slug gotcha" below for the general rule). Renamed
`theme.json` font-size slugs `2xl/3xl/4xl/5xl` → `xxl/xxxl/xxxxl/xxxxxl` (added `xxxxxxl` for opleiding's
60px H1), fixed every `var()`/block-attribute reference across the theme (patterns, templates, CSS). Also,
by product-owner decision: brand primary `#EA4015 → #EC3B13`, accent `#4D8066 → #527A66` (match Lovable
exactly, no citation existed for the old values); hero H1 switched from a fluid `clamp()` to Lovable's
actual fixed breakpoint steps (30/36/48px); added a missing `s-6` spacing token.

**tuisblad (`7407963`).** Found and fixed the first instances of a bug class that recurred on nearly
every page after this: `QA FIXTURE — ` titled posts (real seeded test content, not git-tracked) leaking
onto real pages because their queries had no exclusion filter — including the already-known sponsor-strip
leak, now closed. Fix pattern: `Ink\Kernel\QaFixture::isFixtureTitle()`, scoped `posts_where` exclusion,
fixture content gated back on only via an `INCLUDE_FIXTURES_FILTER`-style seam on `/qa-bloks/`. That same
pattern got reused on opleiding, biblioteek, uitdagings-single, uitdagings-list, skryf, and skrywerprofiel
(3 separate query sites) — it was the single most common defect this pass.

**lees-storie (`ba1a54b`).** Seeded the site's first real published `storie` post (none existed —
`docs/design-handoff` had never had one). Fixed button/input font-inheritance (rendering Arial instead of
Inter — recurred on 5 more pages after this), badge line-height, byline emphasis, response-card
padding/radius, and added missing hover states. First occurrence of the input/button font-inheritance bug
in this pass.

**lees-gedig (`7436941`).** Implemented all 17 items from the prior product-owner-reviewed audit. Two
needed explicit decisions, both applied: title font matches Lovable's *actual rendered* Georgia (Lovable
itself declares Lora but has zero `@font-face` entries for it anywhere and silently falls back — the
decision was to replicate that rendered reality, not "fix" the reference); the `.ink-leesprompte`
"Reageer met bedoeling" panel (a real, deliberately-built INK component with no Lovable equivalent) was
cut to match Lovable exactly, cutting it only from `reading-gedig.php` (kept on storie/artikel, which
still use it). Also: new author card component, response-card avatar/per-type-color/relative-timestamps/
upvote+reply, a genuine functional bug fix (submit button never disabled on an empty textarea), and 3
items (whole-poem reaction bar structure, two reaction-icon interaction bugs) deliberately deferred — see
the reaction-bar reconciliation row below.

**opleiding (`a64f98f` + `3087515`).** Same fixture-leak bug fixed for `Ink\Training\Hub`. Added the
missing intro eyebrow/H1/subheading (curated Afrikaans, not invented). A later cross-page consistency
check (during the biblioteek pass) found this page's H1 `line-height:1` didn't match a fresh Lovable
remeasurement (`1.25`, breakpoint-dependent — flat 1.25 chosen to match the sibling biblioteek heading,
which is imprecise above 768px on the Lovable side but consistent with the established sibling pattern);
fixed in the small follow-up commit.

**biblioteek (`92b8d97`).** Same fixture-leak bug fixed for `Ink\Library\Archive`. Card
title/genre-badge/heading weight and line-height corrected to match the same "Library-layout archetype"
values already verified on opleiding. Flagged, not fixed (no real Lovable counterpart exists to diff
against): missing intro copy (copy-debt process territory, not a style fix), missing contribute-CTA
(a real feature, Story 11.5, not to be improvised here), image-crop parity (Lovable's Library.tsx cards
carry no images at all — nothing to diff against, current treatment left as-is on its own merits).

**uitdagings-single (`8e5eb60`).** Same fixture-leak bug, plus found the *identical* bug latent in
`CurrentChallenge::entryCount()` (tuisblad's card) which had never excluded fixtures either — fixed by
sharing one `entryCount()` helper. Wired in a previously-authored-but-never-rendered CTA subtitle. Fixed 6
"not a themed ratio" line-height bugs, 3 fluid-token-instead-of-fixed-step heading bugs, and a button
height/padding inconsistency (`theme.json`'s `elements.button` defines no padding/height, so buttons
without explicit sizing fell back to inconsistent em-based defaults — fixed page-scoped, not touched
sitewide since that's out of this page's blast radius).

**uitdagings-list (`8a6309f`).** Confirmed the real live URL is `/uitdaging/` (singular), not
`/uitdagings/` as the page-map assumed — no dedicated `page-uitdagings` page/template exists; the real
target is `archive-uitdaging.html`. Same fixture-leak bug (this page never got it in the prior pass, the
one page that still had it). A prior commit's card-grid build (structurally sound) had diverged from its
own cited sibling recipe on radius/hover-lift/line-height/pill-sizing — all corrected to match.

**skryf (`d380312`).** A prior pass's claimed fixes (bordered challenges box, styled draft button) never
actually rendered — both silently defeated by a more-specific CSS reset winning the cascade
(`.ink-skryf-form fieldset` zeroing the challenges box; `.ink-skryf-actions button` overriding the draft
button's font back to serif) — requalified the selectors. Fixed the same fixture-leak bug in
`ChallengeLinking::publishedChallenges()`. Added a genuinely-missing checked-state style on challenge
checkboxes (ticked looked identical to unticked before this).

**skrywerprofiel (`f485fca`).** Same fixture-leak bug, in 3 separate query sites on this one page
(pinned-work cards, works-breakdown stat, hartjie total) — refactored to share one
`publishedWorkCountsByType()` helper rather than patch each site individually. A prior pass's structural
work (cover image, stats strip, genre pills, accomplishments) held up completely; every one of its
CSS/token value claims did not survive fresh measurement — all corrected. Minor DB-hygiene note, not a
code bug: the real dev/test account used as this page's test subject (`https://nuwe-ink.local/author/
cobus/`) has a literal leftover `"QA FIXTURE bio — ..."` string in its bio field from earlier testing;
harmless on a Local-only install, worth clearing next time someone's in wp-admin for that account.

**Reaction-bar reconciliation (`754fc42`).** An earlier subagent (auditing lees-storie) had cited
"AD-5a" as the reason the hartjie/duim_op/wow three-reaction system was in tension with a Lovable
mismatch. **That citation was wrong** — AD-5a (`architecture.md` ~line 383) governs an unrelated feature
(Gemeenskapsreaksie/moderator-feedback storage as WP comment types). The real citation is stronger: PRD
glossary, `EXPERIENCE.md`, and Story 7.3's `Ink\Kernel\Reaction` enum all ratify hartjie/duim_op/wow as
deliberate INK product design, different from Lovable's heart-only system on purpose — so a prior audit's
recommendation to "drop to heart-only" was correctly *not* actioned. What did get fixed: the whole-piece
reaction bar's placement (moved below content, framed/bordered/centered to match Lovable's container
language, on `reading-gedig.php`/`reading-storie.php`/`reading-artikel.php` — the only 3 patterns that
carry it), a selected-icon-scoping bug (a clicked button retaining focus kept a `:focus-within` reveal
rule active for all 3 icons, not just the selected one — root-caused via real click-then-mouse-away
testing, fixed with an explicit `blur()`), and a line-highlight-persistence bug (only responded to live
`:hover`, now persists via a `.has-reaksie` class once a line has an active reaction).

**my-profiel (`58e2f72`).** Confirmed and fixed the previously-flagged pin-toggle bug: `vasgespel.js`
(the pin/unpin client, mirroring `leeslys.js`) had been written by an earlier interrupted session but
never committed — its `functions.php` enqueue had already landed on its own, so the button silently fired
no request at all. Also found and committed `volg.js` (the follow-toggle client for
skrywerprofiel/my-profiel), similarly already enqueued but pointing at a file that didn't exist — a live
404 until this pass. Fixed the usual fixture-leak bug (4 sites: read-count surface, pinned-works manager,
following-activity feed, reading list) and the input/button font-inheritance bug on the pin button.
**Major structural gap found, deliberately not built this pass (logged, product-owner-confirmed to defer
rather than build now):** the live page is a flat stack of sections with **no tab shell, no identity
strip (avatar/name/tagline/Edit-profile/New-post), no "Wie ek volg" following-list, and no Kennisgewings/
notifications panel** — `EXPERIENCE.md`, `ui-copy-translations.md`, and Lovable's `Profile.tsx` all specify
a 7-tab design (Oorsig/Bydraes/Leeslys/Wie ek volg/Aktiwiteit/Kennisgewings/Lidmaatskap) that Story 9.4
never actually built. No citation documents this as a deliberate simplification. This needs a dedicated
feature-build pass, sized similarly to the lees-storie highlightable-text work below — not a quick style
fix. `volg.js`'s unfollow-row-removal logic was already written with nowhere to render (no "Wie ek volg"
list exists yet).

**ontdek (`7884c90`).** Fixture-leak bug in the Bydraes works archive (4 unfiltered `QA FIXTURE` posts;
`WorksArchive::runQuery()` gained the standard exclusion + `INCLUDE_FIXTURES_FILTER` seam) plus a leak in
Search's results. Fixed the fluid-token-instead-of-fixed-step bug on the archive-intro H1 (Lovable steps
36/48/60px at base/768/1024, weight 600 — was flat fluid "xxxxl"), the input/button font-inheritance bug on
the search field, and a missing eyebrow/search-field icon. **Structural gap judged in-scope and built**
(distinct from my-profiel's judgment call above): "Bydraes"/"Skrywers" were anchor-jump pills, not real
tabs — Lovable's `Browse.tsx` toggles one visible panel. Rebuilt as real underline tabs
(`ink-ontdek-tabs__knoppie`/`data-ink-ontdek-tab`) with a small progressive-enhancement JS toggle
(`ontdek-tabs.js`, AD-7-compliant — no REST/AJAX, anchors still work with JS off); a query-string prefix
determines the active tab on reload so an in-panel filter/sort click doesn't snap back to Bydraes. Cards
were a bare unstyled text list — rebuilt to the same card shape as the tuisblad featured-stream (Story
19.4): excerpt, read-time, author avatar, Heart/MessageCircle counts, reusing existing helpers/data, no new
capability. Filter-vs-sort pill visual language matched to Lovable exactly. Also cleared the same leftover
`"QA FIXTURE bio — "` DB-hygiene string (flagged on skrywerprofiel) since it was rendering on this page's
Skrywers card too. Not fixed, logged only: the Search-results list keeps a lighter card treatment — no
Lovable structural equivalent exists to diff against (Lovable's search is inline client-side filtering of
the same cards, not a separate results view).

**Stale test fix (`0bc59ba`).** The ontdek subagent's own `composer test` run mis-reported
`OntdekTemplateTest`'s failure as "pre-existing, unrelated" — it wasn't. Its own tab rebuild removed the
`is-style-pill` CSS class the test asserted on. Caught independently by the orchestrating session (not the
subagent) via a targeted `composer test:unit -- --filter=OntdekTemplateTest` re-run plus `git log`/`grep`
tracing the regression to `7884c90`. Fixed by pointing the assertion at the real current markup
(`ink-ontdek-tabs__knoppie`/`data-ink-ontdek-tab`). The gemeenskap subagent's report (dispatched before this
was caught) repeated the same incorrect "pre-existing" characterization of the same failure — harmless
since the fix landed here regardless, but a reminder that a subagent's own "pre-existing" claim needs
independent verification, not just trust.

**gemeenskap (`f7cc536`).** Diverged from Lovable on nearly every section. Hero: was left-aligned, not
centered in a ~768px column; H1 was a flat 32px "xxxxl" preset instead of Lovable's fixed 36px/60px
(base/768) breakpoint steps at weight 600 not 700; eyebrow was muted-grey/14px/0.08em instead of
primary/12px/0.2em; section padding was flat 64px instead of 80px (112px for the hero specifically).
"Vir skrywers"/"Vir lesers" was a standalone H2 + 4-card grid with no icons — rebuilt to Lovable's actual
one-bordered-card-per-audience structure (icon circle + heading + intro + icon-led benefit list). "Hoe INK
werk" was a plain bullet list — rebuilt as CSS-counter numbered circle badges. "Gemeenskapsbeginsels" cards
used the generic `is-style-card` and rendered 4-across instead of Lovable's left-border-accent 2×2 grid. The
closing CTA band had its background/text colors backwards (light bg + dark text, should be inverted dark
bg + light text) — the largest single defect on the page. Buttons: added missing icons, fixed
height/padding/weight to the established 44px/`s-32`/medium recipe, added two new neutral button treatments
for light- and dark-section contexts (Lovable's secondary buttons here are neutral-bordered, not terracotta
outline). Found one more live instance of the **"xxl"/"xxxl" tokens are still fluid `clamp()`, not yet
converted to fixed steps** gap (flagged as open in the sitewide-tokens paragraph above) on the value-card
headings — pinned page-scoped to 24px, but the token-level fix itself remains open (see "Known outstanding
items"). Confirmed via citation (`EXPERIENCE.md`'s page-map row + Story 15.2's AC) that Lovable's live
statistics-counter strip and "This Month's Spotlight" block are legitimately out of scope for this page, not
an unconfirmed simplification — not built.

**lidmaatskap (`bf0fc42`).** First page in this rework with no live Lovable route to diff against
(`MISSING_IN_CURRENT_LOVABLE_REPO`, assembly-only); fidelity target was internal design-system consistency
with the 12 already-verified pages. Confirmed the **"xxl"/"xxxl" still-fluid** gap present on every heading/
price using those slugs (H1, CTA-band H2, benefits H2, FAQ H2, per-plan price) — pinned page-scoped to each
token's own flat non-fluid size (24px/32px), corroborated against `ink-lovable`'s `Profile.tsx`
membership-renewal card (price at `text-2xl` = 24px) as the closest real analog. Fixed the recurring
button-height/padding bug (3 plan-card CTAs at odd em-derived values → pinned to 44px/`s-32`/medium).
Confirmed button-font-family was NOT a bug here (unlike other pages) — this page's CTAs are all
`<a>`-based `.wp-block-button__link`, deliberately Lora sitewide; no raw form elements exist on this
presentation-only page. Found and fixed a real accessibility bug: the disabled "Binnekort beskikbaar" plan
buttons had `aria-disabled="true"` but no `href`, yet still rendered `cursor:pointer` and stayed in tab
order — added `tabindex="-1"` and `cursor:not-allowed`. Built a full interaction treatment for the FAQ
accordion (`wp:details`, the only page using this block) from scratch, since no sitewide accordion
precedent existed to copy: hidden native marker, rotating chevron, hover/focus-visible states matching the
sitewide vocabulary. **Flagged, not fixed** (out of this page's declared scope):
`patterns/lidmaatskap-hernu.php` (the My Profiel → Lidmaatskap renewal section at
`/my-profiel-lidmaatskap/`, Story 4.5) is a structural twin of this page's plan cards with the identical
fluid-heading and button-height bugs just fixed here — needs its own follow-up so the two don't drift out
of sync.

**oor-ink (`304f7c5`).** Never previously started. No dedicated Lovable page exists (assembly-only per
page-map.csv); only the sponsors/logos section maps to a real Lovable component
(`SponsorsSection.tsx`, diffed directly) — everything else brought to internal design-system consistency
with the 13 already-verified pages. Fixed the recurring fixture-leak bug in `Sponsors\RecognitionSection`
(the "Ons borge" block had zero exclusion at all, unlike the homepage strip which already excluded fixtures
from the tuisblad pass) — 3 real `QA FIXTURE — {tier} Borg` posts were rendering unfiltered on the live
page; added the standard `QaFixture`/`INCLUDE_FIXTURES_FILTER` gate and a new qa-bloks.php section 9 so the
fixtures still render on the QA gallery. Built the entire `.ink-borg-erkenning__*` CSS recipe from scratch
(zero rules existed; content rendered as a bare `<ul>` of links) by reusing the already-Lovable-verified
`.ink-borg-strook__*` tokens 1:1, plus new tier-tinted (`goud`/`silwer`/`brons`) chip modifiers and a heart
icon on the CTA, matching Lovable's sage-eyebrow/tier-chip/sage-outline-CTA structure. Fixed the CTA's
height (was 64px, not 44px — `box-sizing:content-box` on a themeless button; added `border-box`) and
**flagged, not fixed**, the identical latent 64px-not-44px bug on the already-shipped `.ink-borg-strook__cta`
from page 1/tuisblad — same recipe, same defect, out of this page's declared scope. Pinned the H1 (xxxxl,
32px) and 3 H2s (xxl, 24px) against the narrow-viewport fluid-clamp gap, and matched the hero eyebrow to
gemeenskap's already-Lovable-verified plain-eyebrow recipe (12px/primary/500/0.2em — was
14px/muted-grey/600/0.08em with no citation). Fixed a real Brain-Monkey cross-suite test-isolation
flakiness its own new unit test tripped over (order-dependent `get_permalink` process-wide patching),
restructured to avoid the shared-infra fragility rather than patch it (that fragility is itself a known
open item). No structural/feature gaps found — the page's 5-section structure matches `EXPERIENCE.md`'s
page-map row exactly. Orchestrator spot-check (live `getComputedStyle()` on both `/oor-ink/` and
`/qa-bloks/`) confirmed every claimed fix: fixture leak gone on the live page, present correctly on the QA
gallery; CTA 44px/border-box; eyebrow 12px/primary/500/2.4px letter-spacing; H1 32px; the 3 pinned H2s 24px;
Kontak-ons button 44px/`0 32px`/500.

**kontak (`c2e661c`).** No live Lovable route to diff against (`MISSING_IN_CURRENT_LOVABLE_REPO`,
assembly-only, same treatment as lidmaatskap) — fidelity target was internal design-system consistency.
The contact form had **zero CSS anywhere**: inputs/textarea rendered in bare browser-default Arial (the
input/button font-inheritance bug, in its most extreme form yet — nothing at all had been styled, not even
partially), the submit button sat at browser-default height/padding/weight instead of the established
44px/`0 32px`/500 recipe, and the "Boodskap"/hint label ran together on one line
("BoodskapHoe ons kan help?") with no `display:block` separation. Fixed all of the above; new page-scoped
`assets/css/kontak.css`, enqueued only on this page (mirrors the Skryf/Ontdek `is_page()` gating pattern).
Also fixed the H1's still-open **"xxxl" fluid-clamp() bug** (pinned page-scoped to 32px, same pattern as
lidmaatskap/oor-ink) and matched the eyebrow to the sitewide plain-eyebrow recipe (12px/primary/500/0.2em).
Styled the previously-unstyled success/error submission notices off the `success`/`danger` color tokens.
Real interaction testing: two genuine form submissions run end-to-end (one valid → real `wp_mail()`
success redirect; one with `required` attributes stripped via JS to force the server-side validation path
→ real error redirect), plus real focus-visible ring and hover-state checks. QA-fixture-leak bug class
confirmed not applicable — the form runs no `WP_Query`. **Flagged, not built** (genuine feature-vs-scope
question, not a style fix): page-map.csv/`EXPERIENCE.md` mention a `map` block, but Story 15.4's own
shipped AC never included one and no physical address exists anywhere on the site to plot — unlike
lidmaatskap's disabled-CTA precedent (a real priced slot with data pending), there's no existing slot or
data here to gracefully degrade into a placeholder; building one would mean inventing a location. Left for
product-owner input rather than guessed. Orchestrator spot-check (live `getComputedStyle()` against the
real `.ink-kontak-vorm` form, after an initial false alarm where a first-pass selector accidentally matched
the WP admin-bar's own search form instead) confirmed every claim: H1 32px, eyebrow 12px/primary/2.4px
letter-spacing, Naam input `Inter, system-ui, sans-serif`, submit button 44px/`0 32px`/weight 500/Lora.

**auth (`2654ea6`) — last page, rework complete.** Real structural bugs, not style: a brownfield BuddyPress
install had its own Register directory-page mapping pointed at the theme's own `/registreer/` slug, so the
theme's own `auth-register.php` pattern was **never reachable** on GET — BuddyPress's raw, un-translated
legacy signup screen rendered instead every time. Fixed at two levels: `Ink\Social\BuddyPress::excludeAuthPages()`
(`bp_core_get_directory_page_ids` filter) plus a new idempotent, self-healing `Ink\Social\AuthPageRelease`
(renames the stray `buddypress`-post-type object off the slug, creates a real `page` object there, flushes
rewrite rules once). WordPress core's own failed-login/failed-lost-password fell through to `wp-login.php`'s
raw admin-styled English screens with no hook available — new `Ink\Accounts\AuthRedirects` (`wp_login_failed`,
`lost_password`) redirects back to the theme's own styled `/meld-aan/`/`/wagwoord-herstel/` pages with a
notice-slug query arg, the same convention as `Ink\Forms\ContactForm`. **Registration POST specifically is
flagged, not fixed**: BuddyPress's own bootstrap intercepts it before WordPress's `init` ever fires (confirmed
via live hook tracing down to `plugins_loaded` priority 999) — the `AuthRedirects::registerSubmission()` code
is written and unit-tested but doesn't take effect on this install; needs a `BUDDYPRESS_LATE_LOAD` wp-config
change or an mu-plugin-level fix, a product-owner-level infra decision, not a theme-layer style fix. Style
fixes: `auth-login.php` replaced core's `wp:loginout` block (raw `wp_login_form()`, no hook for theme classes)
with the same hand-authored `ink-auth-*` markup the other two patterns already used; new `assets/css/auth.css`
fixes the input/button font-inheritance bug across all three forms, the 44px/`0 32px`/weight-500/full-width
button recipe, card padding (Lovable's 32px vs. the sitewide `is-style-card` default of 24px), an unwanted
hover-lift on a static card, and the still-open "xxl" fluid-clamp() bug. WP-core's native password-reset
screen was deliberately left un-rebuilt (it carries real password-strength-meter/generate-password JS with
no cheap equivalent) but given a **brand skin** — new `assets/css/wp-login-brand.css`, colors/fonts/card
shape only, enqueued via `login_enqueue_scripts` gated to that action. A real bug the subagent's own fix
introduced was caught and fixed during its own verification (a `box-sizing:content-box` overflow on the
"Terug na aanmeld" secondary button). **Flagged, not built** (genuine architecture question, not a style
call): whether to rebuild as Lovable's single tabbed-card `Auth.tsx` UI vs. keep this theme's established
separate-URL pattern — judged NOT ambiguous, since both existing patterns' own docblocks already document
separate-URL as the deliberate WordPress-native choice ("auth is used, never reimplemented"); left as-is.
No live Lovable preview was reachable this session (`preview--quill-muse-heart.lovable.app` returned a
site-wide Internal Server Error throughout) — measurements sourced from `ink-lovable`'s `Auth.tsx`/
`ForgotPassword.tsx`/`ResetPassword.tsx` source reconciled against this theme's own already-live-verified
sitewide tokens. **Narrow-viewport rendering on this specific page is unverified** — the browser tool's
`resize_window` didn't actually shrink `window.innerWidth` this session (a known-unreliable tool, see the
orchestration notes elsewhere); the CSS itself only uses the same relative/percentage-width pattern already
verified responsive on every other page, but that's inference, not a fresh measurement. Orchestrator
spot-check: confirmed `/registreer/` now serves the theme's own Afrikaans form (not BuddyPress's raw screen);
confirmed login-form Inter font-inheritance and the 44px/`0 32px`/weight-500 button recipe live; the
failed-login flow initially looked broken on a browser-automation click (no notice appeared) — traced via a
direct `curl` POST to `wp-login.php` bypassing the browser entirely, which confirmed the server-side redirect,
query arg, and rendered notice (styled danger-tinted, pre-filled username) all work exactly as claimed — the
browser click was a false negative (the same click-didn't-register class of spot-check mistake as kontak's
false alarm), not a real defect.

**Highlightable-text feature, lees-storie (`b6b609a`).** `EXPERIENCE.md`'s page-map row for lees-storie
lists "highlightable text, floating action bar" as expected; nothing had ever built it. Confirmed
in-scope (real, ratified spec — not scope creep) and built: select text in a storie's body → a floating
bar appears (styling measured live off Lovable's actual `HighlightableText.tsx` behavior) → pick
hartjie/duim_op/wow (same enum as gedig, no new reaction types) → the containing paragraph tints and the
tint persists across reloads for every visitor (gedig's own line-reactions do *not* persist across reload
— this closes that gap independently for storie without touching gedig). Anchor granularity is
paragraph-level, not character-range — a deliberate coarsening to match the existing gedig per-line
precedent rather than building full arbitrary-range selection. New `Ink\Engagement\ProseBody` mirrors
`GedigBody`'s tokenizer; the REST write path (`ink/v1/reaksie`) already had a `storie`-permitting gate
from the start, extended to validate the new anchor type.

---

## Post-Epic-19 verification pass (2026-08-31) — still-relevant operational reference

This section predates the Phase-2 corrected-method pass above and covers infrastructure facts that are
still true and still worth knowing, not page-fidelity findings (those are all superseded by the table
above).

### The deployment gap

`nuwe-ink.local` (Local by Flywheel, `/Users/cobus/Local Sites/nuwe-ink/app/public`) does **not** read
theme/plugin code live from this git repo — it's a separate WordPress install. **Run
`tools/deploy-to-local.sh --apply`** after every change to `wp-content/themes/ink-foundation` or
`wp-content/plugins/ink-core`, or nothing you build here is visible on that site.

The script's cache-purge step still fails (`wp cache flush`/`wp litespeed-purge` can't reach Local's
MySQL — non-standard socket, `DB_HOST` forces TCP resolution that hangs rather than refusing under the
sandbox). Confirmed still the case as of the 2026-09-02 pass — every subagent that needed to inspect DB
state worked around it via live `getComputedStyle()`/REST calls instead of `wp eval`, never by fixing the
script itself. `litespeed-cache` is inactive, so this doesn't currently mask anything; the file-sync half
of the script is what matters and works fine.

### WordPress digit-adjacent-slug gotcha (general rule, hit 3 separate ways so far)

Never give a `theme.json` preset/custom slug a digit immediately adjacent to a letter (`2xl`, `3xl`, …).
WordPress's internal kebab-casing (`_wp_to_kebab_case()` and equivalent preset-class generation) inserts a
hyphen at *any* letter↔digit boundary, silently producing a different string than what your CSS/block
attributes reference. Confirmed in three independent code paths so far:

1. `settings.custom.radius` slugs `2xl`/`3xl` → generated as `--wp--custom--radius--2-xl` (fixed
   2026-08-31, `e43f1dd`, renamed to `xxl`/`xxxl`).
2. `settings.typography.fontSizes` slugs `2xl`/`3xl`/`4xl`/`5xl` → generated `var()` custom-property
   names hyphenated the same way (fixed 2026-09-02, `541a57a`, renamed to `xxl`/`xxxl`/`xxxxl`/`xxxxxl`).
3. The *class-name* generation path for the same font-size slugs is a **separate** bug from #2, not the
   same fix: a block using `{"fontSize":"2xl"}` renders `class="has-2xl-font-size"`, but the generated CSS
   rule is `.has-2-xl-font-size` — same root cause, different code path, needed its own rename (also
   folded into `541a57a`).

If you ever add a new `theme.json` preset/custom-token slug, don't use a digit-leading or digit-adjacent
form at all — the theme's convention going forward is spelled-out repetition (`xxl`, `xxxl`, …), matching
what the radius scale already used before this was understood as a general rule.

### Local WordPress debugging notes

- **UPDATE (2026-09-05): wp-cli DB access is solved.** The bare `wp` binary can't reach Local's MySQL
  because `DB_HOST` forces TCP resolution, which hangs under the sandbox — but Local's MySQL is reachable
  directly via its actual Unix socket. `./tmp/wpcli.sh` (gitignored, reusable) wraps this:
  ```sh
  #!/bin/zsh
  SOCK="/Users/cobus/Library/Application Support/Local/run/UQ1ASIydU/mysql/mysqld.sock"
  exec php -d memory_limit=512M -d mysqli.default_socket="$SOCK" -d pdo_mysql.default_socket="$SOCK" \
    "$(which wp)" --path="/Users/cobus/Local Sites/nuwe-ink/app/public" "$@"
  ```
  Confirmed working independently (not just by the subagent that found it): `./tmp/wpcli.sh post list
  --post_type=gedig --post_status=publish --format=count` returns `10742`, matching the REST-API-confirmed
  total exactly. The socket path is specific to this Local install and may change if Local regenerates its
  site ID — if the script stops working, find the current path via Local's UI (site → "Open site shell" or
  the site's `conf/mysql/` folder) and update the constant. This unblocks real DB-level verification
  (`wp user meta get`, `wp post get --field=...`, revision inspection, etc.) for every future page in this
  pass — prefer it over REST-API/browser-only checks where DB truth matters (e.g. confirming a toggle left
  no residue, checking real post meta, restoring from revision history).
- Older workaround, superseded by the above but kept for context: verify state via live
  `getComputedStyle()`/REST calls through the browser tools when `wpcli.sh` isn't applicable (e.g. actual
  rendered CSS, which the DB can't tell you).
- Real PHP error/notice trail: `WP_DEBUG`/`WP_DEBUG_LOG` in `wp-config.php`, writes to
  `wp-content/debug.log` — revert and delete the log when done, don't leave it on.
- `curl -sk "https://nuwe-ink.local/?cachebust=<random>"` inspects real server-rendered HTML directly,
  bypassing the browser — confirmed to match the browser's rendered DOM exactly, so server-side is where
  to look first for any "changes aren't showing up" mystery.
- A throwaway mu-plugin dropped in `wp-content/mu-plugins/` (copy to
  `/Users/cobus/Local Sites/nuwe-ink/app/public/wp-content/mu-plugins/` to take effect) is the fastest way
  to run arbitrary diagnostic PHP against a real request — no activation needed. **Always mark such files
  TEST-ONLY, never commit, delete both copies (repo + deployed site) when done.**
- Pattern-cache gotcha: the theme's scanned block-pattern list is cached in a site transient that a plain
  `rsync` deploy does **not** invalidate — a newly added `patterns/*.php` file can silently fail to
  register even though the file synced correctly. Workaround: a one-off throwaway mu-plugin (Local-site
  only, never committed) calling `wp_get_theme()->delete_pattern_cache()`. Expect to hit this again any
  time a fidelity-pass agent adds a *new* pattern file.

### Nested-`init` registration bug — fully resolved

All 6 known instances of this bug class (a class calling `add_action('init', ...)` from *within* another
class's own `init`-time dispatch, which WordPress silently never fires) are fixed and live-verified:
block-type registration (`3c71d44`, the original find — broke every homepage dynamic section) plus the 5
non-block instances (`TrendingScore`, `ModeratorFeedback`, `Approval`, `Onboarding`, `Entitlement\Module`
— workstream A, `4b07e22`/`3c2f3d3`/`7ab1fa4`/`6bd7282`/`bc9c68d`). **Still the single most important
architectural fact for anyone extending this codebase**: any `Ink\{Module}\...::register()` that needs to
register something at `init` time must call it directly — `register()` is already running at the correct
moment via the Kernel's own dispatch chain; wrapping it in another `add_action('init', ...)` is silently
fatal to that registration, not redundant.

### `git stash` gotcha

`docs/theme-fidelity-rework-plan.md` (this file) is essentially always dirty in a subagent's working tree
(the orchestrator's own live tracking edits). `git stash`/`git stash pop` on a tree that includes it fights
with the sandbox's shell-write deny on `docs/`+`_bmad-output/` paths. **Don't `git stash` in this repo** —
use `git worktree add` for a scratch checkout, or `git diff -- <path> > tmp/patch.diff` + inspect. Hit and
recovered from twice pre-2026-09-02; the corrected-method pass avoided it entirely by following this rule.

### QA gallery page (`/qa-bloks/`)

Template `templates/page-qa-bloks.html`, content/pattern `patterns/qa-bloks.php`, fixture wiring in
`functions.php`. Sections now cover: sponsor strip, current-challenge card, featured stream, opleiding hub,
biblioteek archive, uitdagings-list card grid — each added as its page got re-audited and gained a
fixture-exclusion filter (see the tracking table above). Check the target block's class for a `*_FILTER`
data seam first; only fall back to real seeded `QA FIXTURE — ` titled WP content when no seam exists, and
gate any filter so fixture data can never leak off the gallery page itself — that gating is exactly the bug
class fixed repeatedly in the table above.

---

## Known outstanding items, not yet resolved

All 16 pages are done. What's left is flagged follow-up work, not incomplete rework:

- **Registration POST is still intercepted by BuddyPress** before WordPress's `init` ever fires — the
  fix code (`AuthRedirects::registerSubmission()`) is written and unit-tested but inert on this install.
  Needs a `BUDDYPRESS_LATE_LOAD` wp-config change or an mu-plugin-level fix — a product-owner infra
  decision, not a theme-layer style fix.
- **auth page narrow-viewport rendering is unverified** — `resize_window` didn't actually shrink the
  viewport this session (see the tool's known unreliability, noted elsewhere in this doc); CSS uses the
  same relative-width pattern already verified responsive elsewhere, but that's inference, not measurement.
- **kontak's `map` block** — flagged as a genuine feature-vs-scope question (no address exists anywhere on
  the site to plot), not built. Needs product-owner input, not a style-pass guess.
- **A recommended final full-sitewide sanity pass** was suggested by the auth (page 16) subagent but not
  attempted by it or scheduled yet — worth doing once, now that all 16 pages are individually done, to
  catch anything that only shows up in cross-page navigation rather than a single-page audit.
- **My-profiel structural gap** — no tab shell, identity strip, "Wie ek volg" following-list, or
  Kennisgewings/notifications panel. Logged, product-owner-confirmed to defer rather than build now (see
  page 10's write-up above). Needs a dedicated feature-build pass when scheduled.
- **`patterns/lidmaatskap-hernu.php`** (the My Profiel → Lidmaatskap renewal section at
  `/my-profiel-lidmaatskap/`, Story 4.5) has the identical fluid-heading and button-height bugs fixed on
  page 13/lidmaatskap proper — flagged during that pass, not yet fixed, out of that page's declared scope.
- **"xxl"/"xxxl" font-size tokens are still fluid `clamp()`, not fixed steps** — the sitewide-tokens fix
  (`541a57a`) converted xs/sm/md/lg/xl and xxxxl+ to fixed breakpoint steps but left these two still fluid.
  Worked around page-scoped (`!important` pins) on gemeenskap and lidmaatskap so far; the token-level
  conversion itself remains open and will keep recurring on any page not yet re-audited.
- **`.ink-borg-strook__cta`** (page 1/tuisblad's sponsor-strip CTA) has the same 64px-not-44px
  `box-sizing:content-box` bug fixed on its oor-ink twin (`.ink-borg-erkenning__cta`, page 14) — flagged
  during the oor-ink pass, not yet fixed, out of that page's declared scope.
- **ontdek's Search-results list** keeps a lighter-weight card treatment than the Bydraes/Skrywers cards —
  no Lovable structural equivalent exists to diff against (Lovable's search is inline client-side filtering
  of the same cards, not a separate results view). Flagged, not a confirmed defect.
- **My-profiel pin-toggle button** reported broken (no REST request fires) — **fixed** as part of page 10's
  pass (`58e2f72`, `vasgespel.js` was written but never committed).
- **`uitdaging`/`inkpols_uitgawe`/`borg` post types** are missing `'custom-fields'` support, so REST/
  Gutenberg-panel meta writes silently no-op for all 3 (classic meta-box form + `update_post_meta()`
  unaffected). Known since 2026-08-31, still open.
- **Challenge-linking picker on `skryf`** can't show deadlines — the data bridge
  (`ChallengeLinking::openChallenges()`) only exposes id+title. Known, still open, plugin-layer change out
  of scope for a style pass.
- **Footer founding year** — `patterns/footer-main.php` has two literal unresolved `[stigtingsjaar]`
  placeholders; `patterns/oor-ink.php` separately carries a provisional, unconfirmed "2018". Product owner
  has said leave both as-is pending founder sign-off.
- **gedig response-card upvote count** is a real, truthful zero rather than interactive — the upvote
  backend itself is deferred, not built as part of the lees-gedig fix pass.
- **Minor copy-debt flags** (not invented, but not fully polished) from this pass: a REST validation error
  string reused verbatim for a new paragraph-anchor case (internal `WP_Error` text, not user-facing UI
  copy); a couple of new single-word Terms entries added as low-risk glossary additions. Not tracked
  individually elsewhere — mentioned here so they're not lost.

---

## Original diagnosis (2026-07-19) and Epic 19 rebuild — historical, compressed

Epic 19 (stories 19.1–19.5) rebuilt the theme's composition layer (buttons, background texture, hero
scale/layout, badge/stats row, story-card hover states) against `theme-fidelity-spec.md`, going through
the standard BMAD sequence (`/lovable-design-sync` → UX spec → sprint-change proposal → epics/stories →
sprint planning → dev-story loop → code review → retrospective). Reviewed, merged. The 2026-08-31 pass
above found this work had barely ever reached the live site due to the deployment-gap and nested-`init`
bugs documented above — not gaps in Epic 19's own implementation. All resolved.
