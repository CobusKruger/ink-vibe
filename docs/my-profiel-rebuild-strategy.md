# My Profiel rebuild — strategy

**Date:** 2026-09-06. **Status:** proposed, awaiting build. **Scope:** `/my-profiel/`
(private, logged-in-only dashboard) only. The public author page (`/author/{slug}/`,
`ink/skrywerprofiel`) is explicitly **not** in scope for a visual rebuild here — see
"Vasgespelde skrywes" below for the one piece of it this work touches.

## 0. This is not a new discovery — it's the deferred item finally getting built

`docs/theme-fidelity-rework-plan.md` already diagnosed this exact gap twice and deferred it
both times, product-owner-confirmed:

> **Major structural gap found, deliberately not built this pass (logged,
> product-owner-confirmed to defer rather than build now):** the live page is a flat stack of
> sections with **no tab shell, no identity strip (avatar/name/tagline/Edit-profile/New-post),
> no "Wie ek volg" following-list, and no Kennisgewings/notifications panel** —
> `EXPERIENCE.md`, `ui-copy-translations.md`, and Lovable's `Profile.tsx` all specify a 7-tab
> design (Oorsig/Bydraes/Leeslys/Wie ek volg/Aktiwiteit/Kennisgewings/Lidmaatskap) that Story
> 9.4 never actually built.

Re-verified a second time (2026-09-05) as still accurate, no defects found otherwise. This
strategy is that dedicated feature-build pass. When it's built, update
`theme-fidelity-rework-plan.md`'s row 10 and its two "structural gap" open-item entries to
point here instead of re-describing the gap.

## 1. What "correct" means here (do not re-litigate)

Per the product owner's standing instruction: every visual element needs the **correct
placement, size, colors, fonts, and layout** — not a checklist confirming a section exists.
[[theme-fidelity-default-match-no-per-element-asks]] governs the default: match Lovable's
`Profile.tsx` exactly on anything cosmetic (spacing, radius, color, type scale, hover states);
only escalate genuine feature-vs-style conflicts (listed in §7) for a decision. Follow the
project's own established verification method (Tier 0 structural correspondence → Tier 1
`data-audit-id`-anchored measurement → Tier 2 live screenshots + real-triggered interaction
states) — the retrospective in `theme-fidelity-rework-plan.md` (§"why lees-gedig still had
substantial differences after four prior passes") is required reading before marking any part
of this "done": a resting-state screenshot alone has hidden real bugs multiple times already
on this project.

## 2. What must NOT be copied verbatim from Lovable

Two deliberate, already-ratified INK departures from `Profile.tsx`'s demo content — copy the
**shape**, never this **data**:

1. **Membership pricing.** Lovable's Membership tab hardcodes fake USD plans ($8/1mo, $42/6mo
   "Save 12%", $72/12mo "Save 25%"). INK's real renewal section
   (`patterns/lidmaatskap-hernu.php`, backed by `MembershipPlans`/`PlanPresenter`/`Api`) already
   reads real ZAR prices from WooCommerce at runtime and deliberately renders **no**
   savings/discount framing — a standing rule (FR-4 / Story 4.1-AC3), not an oversight:
   > GEEN vanity-afslag/besparingsraam … by lansering nie — op die Lidmaatskap-blad óf die
   > hernuwings-UI.
   **Keep this section's data plumbing exactly as-is.** The only work here is visually
   restyling its existing plan cards (selected-state border/tint, radius, spacing, button
   shape) to Lovable's look — see §5.7.
2. **"12 lesings" → readers, not "lectures."** `ReadCountSurface::countLabel()` currently
   renders a per-work count as `_n( '%s lesing', '%s lesings', $n )`. Per the product owner:
   this is wrong — "lesing" reads as a lecture/public-reading event, not a page-view count —
   and should become **"lesers"** (readers). Note this is a deliberate, acknowledged
   simplification (a "leser" count and a "lesing"/read-event count aren't identically the same
   metric) chosen because it's "vastly better," not because it's perfectly precise — don't
   over-engineer a distinct unique-readers calculation to chase perfect accuracy here.

## 3. Vasgespelde skrywes — clarify the feature, don't rebuild the mechanism

**What it actually is** (confirm this framing before touching code): a writer curates a small,
ordered set of their own published bydraes (cap 6, `PinnedWorks`, user-meta
`ink_vasgespelde_werke`) to **feature for readers who land on that writer's public
`/author/{slug}/` page** — not a personal bookmark/favorites list for the writer's own reading.
The management UI lives on My Profiel (`ink/vasgespel-bestuur`, pin/unpin toggle); the payoff
is the "Uitgesoekte werk" section `SkrywerProfiel::pinnedCards()`/`toHtml()` already renders on
the public profile.

**Current state — already functionally correct, verified:**
- The pin/unpin toggle's REST wiring (`vasgespel.js` → `ink/v1/vasgespel`) was a previously
  found-and-fixed bug (`58e2f72`) and was independently re-verified working both directions via
  live clicks + network-request inspection + a clean DB check (third pass, 2026-09-05).
- The public-side render correctly omits the whole "Uitgesoekte werk" block when a writer has
  no pins (verified live on `/author/sussa/`, which has zero pins and correctly shows nothing)
  — not a bug, the designed empty behavior.

**What's actually missing:** purely the visual polish of the My Profiel *management* list
(currently a bare bordered-pill list, no card treatment, no page shell around it) — folded into
the Bydraes-tab rebuild below, since curating pins is fundamentally a "my own works" action.
**Do not touch `PinnedWorks`, `PinnedWorksManager`'s query/REST logic, or the public
Skrywerprofiel pinned-card render** — those are correct and explicitly out of scope (the public
profile's own visual refinement is a separate, later pass per the product owner).

## 4. Ratified copy already exists for all seven tabs — use it, invent nothing

`docs/ui-copy-translations.md` § "My Profiel-bladsy (`Profile.tsx`)" (lines ~452–535) and
§"My Profiel — Volg- en Aktiwiteit-blaaie" (~745–756) already carry human-authored, approved
Afrikaans for the identity strip, all 7 tab labels, the Oorsig cards, the Bydraes-tab status
badges, the Kennisgewings-tab heading/button/four notification-template strings, and the
Wie-ek-volg tab's heading/intro/empty-state/button. This build should be close to
copy-debt-free. **One gap, now closed:** the Leeslys tab's empty-state heading had no ratified
Afrikaans (Lovable: "Nothing saved yet"). Product owner ratified it 2026-09-06:
**"Nog niks gestoor nie."** Still open: Lovable's accompanying body line ("Tap the bookmark on
any story or poem and it will land here for later.") has no ratified Afrikaans yet — route
through the normal [[afrikaans-copy-debt-process]] before wiring it, don't invent it inline.

## 5. Target page architecture

### 5.1 Identity strip (new)

Lovable: avatar (with camera-edit overlay button) + "YOUR PROFILE" eyebrow + serif H1 name +
italic quoted tagline, with Edit profile / New post / View public page actions at the right.
Ratified copy: "Jou profiel" / "Wysig profiel" / "Nuwe bydrae" / "Sien openbare bladsy".

**Decided 2026-09-06:** the Gradering badge also lives here, next to the name — not as its own
Oorsig-tab section (see §5.3) — since it's read-only for the member (nothing to edit, no
interaction to house elsewhere).

Data mapping:
- Avatar → `get_avatar( get_current_user_id() )` (same source `SkrywerProfiel` already uses).
- Name → `display_name`.
- Gradering badge → reuse `SkrywerProfiel`'s existing `graderingBadge()` markup shape (same
  token-only badge, same `TiersApi::gradingView()` read) placed beside/under the name.
- Tagline → **decided: add a new short tagline meta field**, separate from the longer "Oor my"
  bio (BuddyPress `description` stays the bio; this is new user-meta). Needs: the new meta key,
  wiring it into the real edit form (§ below), and a fallback render (empty tagline → omit the
  italic line entirely, don't show empty quote marks).
- Edit profile → **decided: build a real inline edit modal** (name/tagline/bio/avatar), matching
  Lovable's interaction shape, with a REAL persistence layer behind it — unlike Lovable's
  demo-only modal, this needs an actual save path (new REST route or admin-post handler writing
  `display_name`/the new tagline meta/BuddyPress `description`/avatar). This is real new-feature
  scope, not a restyle — size it as its own piece of work, likely comparable to the Kennisgewings
  read-side (§5.8) rather than a quick wire-up. Avatar upload specifically should reuse whatever
  avatar-handling already exists on the site (check before building a new upload path).
- New post → link to `/skryf/`.
- View public page → `get_author_posts_url( get_current_user_id() )` (same helper
  `SkrywerProfiel::render()` uses for `shareUrl`).

### 5.2 Tab shell (new)

Seven tabs, ratified labels: **Oorsig · Bydraes · Leeslys · Wie ek volg · Aktiwiteit ·
Kennisgewings · Lidmaatskap**. Build the same way `ontdek`'s anchor-pills → real-tabs gap was
closed (`7884c90`): real underline tabs with a small progressive-enhancement JS toggle
(`ontdek-tabs.js` precedent — AD-7-compliant, no REST/AJAX, works with JS off via anchors/
same-page sections), a query-string or hash prefix to preserve the active tab across reload.
Reuse that script's pattern rather than inventing a second tab mechanism sitewide — name it
`profiel-tabs.js` mirroring `ontdek-tabs.js`'s shape (`data-ink-profiel-tab` attributes).

### 5.3 Oorsig (Overview) tab

- **Oor my** card: bio text + "Wysig" edit affordance (2/3-width column, matches Lovable's
  `lg:col-span-2`).
- **In 'n oogopslag** card (1/3-width): three stats — Bydraes / Wie ek volg / Ongelees — plus a
  membership one-liner ("INK-lid · hernieu [datum]"). Bydraes count = published own-works count
  (reuse the same query shape as `PinnedWorksManager`, published + fixture-excluded). Wie ek volg
  count = `Api::followingCount()` (already exists). Ongelees = unread kennisgewing count (needs
  `Kennisgewings::countUnread()`, see §5.8 — this card depends on that work existing first).
- **Onlangse aktiwiteit** card: **decided 2026-09-06 — first 3 Kennisgewings rows** (§5.8), not
  the Aktiwiteit tab's own publish-feed. Sequence this card after the Kennisgewings read-side
  exists; there's nothing to show here before then.
- **Decided 2026-09-06: Gradering moves to the identity strip** (§5.1, badge near the name — it's
  read-only for the member, nothing to edit or interact with here). **Leesgetalle (per-work read
  counts) moves to the Bydraes tab** (§5.4) as a per-post stat rather than its own Oorsig
  section — Oorsig no longer hosts either of these two FR-40 surfaces directly.

### 5.4 Bydraes (Posts) tab

Lovable: "Jou bydraes" H2 + New-post button, then a card per post (type + status badge, title,
date, like/comment counts, Edit/View actions). Ratified badges: Gepubliseer / Konsep.

- The published-post list, edit/view actions, and per-post metadata are buildable now —
  reuse the same own-author `WP_Query` shape `PinnedWorksManager::render()` already uses
  (published, fixture-excluded, current user).
- **Decided 2026-09-06: each post row also shows its private read count** ("[N] lesers" —
  §2's renamed label), moved here from the now-removed Oorsig Leesgetalle section. Reuse
  `ReadCountSurface`'s existing per-post data (`_ink_read_count` meta) and its `countLabel()`
  helper (once relabeled to "leser"/"lesers") — this tab's query can fetch the same own-author
  post set once and attach both the read count and the pin state (§3) per row, rather than
  running three separate own-author queries across the page.
- **Decided 2026-09-06: omit the "Konsep" (draft) badge for now.** No draft-status handling was
  found in the submission flow (`skryf.php`/`SubmissionGate`) during research — ship the tab
  showing published posts only; revisit if/when a real draft concept is added to submission.
- Fold the **Vasgespelde skrywes** pin/unpin list into this tab (see §3) — it's the same "my own
  works" subject as this tab's list, restyled to a matching card, not a separate top-level
  section on a flat page anymore. A single per-post row can now carry: title, type, read count,
  pin/unpin toggle, Edit/View actions — check whether that's too dense for one card or wants two
  visual rows (a design call for whoever builds this, not a data question).

### 5.5 Leeslys (Reading list) tab

The `ink/leeslys` block already exists and already gets embedded on this page — move it as-is
into this tab. **Fix the disclosed bug while here:** `ReadingList::toHtml()` currently renders a
bare empty `<ul class="ink-leeslys__list"></ul>` with no message when the list is empty,
inconsistent with every sibling section on this page (all of which show an authored "Jy het nog
geen …" sentence) and with Lovable's explicit "Nothing saved yet" empty state. Heading now
ratified (§4: "Nog niks gestoor nie."); the body line is still copy-debt — ship the heading with
a `[NEEDS HUMAN AFRIKAANS]`-flagged placeholder body per the standard process rather than
leaving the silent bare-`<ul>` in place, and wire the real body line in once ratified.

### 5.6 Wie ek volg (Following) tab — new component

Lovable: a card grid of followed writers (avatar, name, italic bio, unfollow icon-button), with
a header + "Ontdek skrywers" CTA and a full ratified empty state. Nothing renders this today —
only the Aktiwiteit *feed* (`ink/volg-voer`) exists, which is a different list (followed
writers' *works*, not the followed writers themselves).

Build a new small block, e.g. `ink/volg-lys` (`Ink\Social\FollowingList`), mirroring
`PinnedWorksManager`'s shape:
- Data: `Api::followeeIdsFor( get_current_user_id() )` (already exists) resolved to each
  writer's display name/avatar/bio + an unfollow button.
- Unfollow button: reuse `FollowToggle`'s existing render/REST plumbing (`ink/v1/volg`) — do
  not build a second follow/unfollow endpoint.
- **The client-side removal behavior is already written and just waiting for a place to run:**
  per the third-pass note, `volg.js`'s "unfollow-row-removal logic was already written with
  nowhere to render" — confirm it still matches this new markup's structure before assuming
  it's a drop-in; it was written speculatively without a real DOM to test against.
- Empty state, heading, and CTA copy: fully ratified already (§4) — "Jy volg nog niemand nie" /
  "Volg 'n skrywer om hul nuwe stukke in jou aktiwiteitsvoer te sien." / "Ontdek skrywers".

### 5.7 Aktiwiteit (Activity) tab

`ink/volg-voer` already exists and is already embedded — move it into this tab as-is
structurally, but its current card is a bare text list (type pill + title + author name).
Lovable's activity row is richer: author avatar + name (linked) + "published a new [type] ·
[N]d ago" + title (linked) + italic excerpt + heart/comment counts + a "Read →" link. Restyle/
extend `FollowingFeed::toHtml()`'s card markup to match — the underlying per-post data
(excerpt, hartjie count, response count, published-days-ago) already has precedent elsewhere on
the site (`SkrywerProfiel::pinnedCards()` computes exactly this shape for its own cards) — reuse
those same helpers rather than re-deriving them.

### 5.8 Kennisgewings (Notifications) tab — new component, the biggest real gap

Nothing renders this today. The **backend already exists** (Story 9.9, `Ink\Notifications`):
`Kennisgewings::add()`/`markAllRead()`/`boundaryFor()`/`isUnread()`/`countUnread()` and a typed
`NotificationType` enum (`Reaksie`/`Mention`/`VolgWerk`/`Uitdaging`/`LidmaatskapVerval`/
read-receipt), all writing into BuddyPress's `ink` notifications component. What's missing is
the **read side**: no class today lists a user's actual notifications back out. This is genuine
new build, not wiring:

1. A new read-model (e.g. `Ink\Notifications\KennisgewingsSurface`) querying BuddyPress's
   notifications store for the current user (`bp_notifications_get_notifications_for_user()` /
   `BP_Notifications_Notification::get()`, `function_exists`-guarded the same way every other
   BP-touching class here degrades gracefully), mapping each row's `component_action` back
   through `NotificationType` to one of the four ratified template strings (§4:
   "[Naam] en nog [N] ander het … liefgehad" / "… het terugvoer gelewer op …" / "… volg jou nou"
   / "[Uitdaging] sluit oor [N] dae"), and computing unread via `Kennisgewings::isUnread()`
   against `boundaryFor()`.
2. A "Merk alles as gelees" button — reuse `Api::markAllRead()`, needs a small new REST route
   (`ink/v1/kennisgewings`, mirroring `FollowController`/`PinnedWorksController`'s existing
   shape) and a `kennisgewings.js` client mirroring `vasgespel.js`/`volg.js`'s enqueue pattern.
3. The unread count this tab's badge needs is the same count §5.3's "Ongelees" stat card wants
   — compute it once, share it.

This is the single largest net-new piece of work in this rebuild; size it accordingly and
don't understaff it relative to the other tabs, which are mostly restyle-existing-data.

### 5.9 Lidmaatskap (Membership) tab

Keep `patterns/lidmaatskap-hernu.php` and its real WooCommerce-backed data exactly as-is (§2) —
restyle its plan cards only (selected-state border/tint matching Lovable's
`border-terracotta bg-terracotta/5` treatment, card radius/spacing, button shape).

Add the left-hand status card Lovable shows (INK-lid / Aktiewe lidmaatskap / Status: Aktief /
Hernieu: [datum] / Lid sedert: [datum]) — copy is fully ratified (§4: "INK-lid" / "Aktiewe
lidmaatskap" / "Status" / "Aktief" / "Hernieu" / "Lid sedert"). **Decided 2026-09-06: investigate
and build the small facade addition** rather than ship without the card. `Ink\Entitlement`
currently exposes `MembershipStatus` (the four access-state enum + status message) but no
"renewal date" / "member since" getter was found in `Entitlement\Api`. First step of this task:
confirm whether WooCommerce Memberships already exposes this
(`wc_memberships_get_user_membership()` typically carries start/expiry dates) and, if so, wrap it
in a new `Entitlement\Api` method the same `function_exists`-guarded way `MembershipPlans`
guards `wc_get_product()` — keep it graceful-degrade (card omits the date row, not a fatal, when
WooCommerce Memberships is inactive).

## 6. CSS / implementation approach

No CSS file exists today for any of this — `my-profiel.php`, `SkrywerProfiel`, `PinnedWorks
Manager`, `ReadingList`, `FollowingFeed`, `ReadCountSurface` all render bare block markup
relying only on generic `is-style-card`/theme.json defaults. This is a from-scratch styling
build, not a tweak pass. Create `assets/css/profiel.css`, enqueued only on
`is_page( 'my-profiel' )` (mirroring `reading.css`'s narrow, single-page-scoped enqueue
pattern), and:

- Keep every existing root BEM class (`.ink-vasgespel`, `.ink-leesgetalle`, `.ink-volg-voer`,
  `.ink-leeslys`, …) as the hook — style onto what the PHP already emits rather than renaming
  classes, to avoid an unnecessary PHP+CSS+test co-change for markup that doesn't need to move.
- Every color/spacing/radius/shadow/type value must resolve to a `theme.json` token (Quality
  Gate A, standing rule) — the palette (`primary` `#EC3B13`, `surface`/`surface-alt`, `border`,
  `muted-text`, the `sm`/`md`/`lg` shadow scale) and spacing scale (`s-8` … `s-96`) already
  cover everything Lovable's card/pill/avatar treatment needs; no new token should be required.
- Tab shell + new list components should visually match the **card style already established**
  elsewhere on the fidelity-reworked site (tuisblad's featured-stream cards, ontdek's rebuilt
  work cards) rather than inventing a third card visual language — reuse, don't reinvent.

## 7. Product-owner decisions (resolved 2026-09-06)

All six open items from the original draft of this strategy were decided directly with the
product owner. Recorded here as the standing answer — don't re-litigate:

1. **Edit-profile destination** → **build a real inline edit modal** (name/tagline/bio/avatar,
   with real persistence behind it, not Lovable's demo-only modal). Real new-feature scope. (§5.1)
2. **Tagline field** → **add a new short tagline meta field**, separate from the "Oor my" bio.
   (§5.1)
3. **Bydraes tab "Konsep" (draft) status** → **omit the badge for now**; no real draft state
   exists in the submission flow today. Revisit only if drafts become a real feature. (§5.4)
4. **Oorsig's "Onlangse aktiwiteit" source** → **first 3 Kennisgewings rows** (§5.8), not the
   Aktiwiteit publish-feed. Depends on §5.8 existing first. (§5.3)
5. **Gradering/Leesgetalle placement** → **Gradering moves to the identity strip** as a badge
   near the name (read-only, nothing to edit). **Leesgetalle moves to the Bydraes tab** as a
   per-post read-count stat, replacing the standalone Oorsig section entirely. (§5.1, §5.3, §5.4)
6. **Membership status-card dates** → **investigate and build the small facade addition** on
   `Entitlement\Api` (graceful-degrade if WooCommerce Memberships is inactive), rather than
   shipping the tab without the status card. (§5.9)

## 8. Suggested build order

1. Leeslys empty-state heading fix (ratified §4) + placeholder-flagged body line (copy-debt) —
   small, unblocks §5.5, independent of everything else.
2. Membership status-card facade investigation (§5.9, decision 6) — small, independent,
   unblocks the Lidmaatskap tab's status card.
3. Kennisgewings read-side (§5.8) — biggest unknown, most other tabs' "unread"/"recent activity"
   affordances depend on it (Oorsig's stat card + activity card, the tab's own badge count).
4. Wie ek volg list (§5.6) — small, self-contained, reuses existing `FollowToggle`/`volg.js`.
5. Edit-profile modal + new tagline field (§5.1, decisions 1–2) — sizable, self-contained;
   can run in parallel with 3–4 once the identity-strip shell below exists.
6. Identity strip (with Gradering badge, §5.1/decision 5) + tab shell (§5.2) — the visible
   skeleton everything else slots into.
7. Fold in Oorsig / Bydraes (with relocated read-counts, §5.4/decision 5) / Leeslys / Aktiwiteit
   / Lidmaatskap content per §5.3–5.5, 5.7, 5.9 (mostly restyle + reuse, lower risk once the
   shell + steps 2–3 exist).
8. `profiel.css` full pass + live Tier 0–2 verification against Lovable, tab by tab, with real
   triggered interactions (tab switch, unfollow click, pin/unpin, mark-all-read, edit-modal save)
   — not resting-state screenshots only.
