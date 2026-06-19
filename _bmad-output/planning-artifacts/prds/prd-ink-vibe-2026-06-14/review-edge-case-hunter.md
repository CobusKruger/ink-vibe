# Edge-Case-Hunter Review — INK PRD (2026-06-14)

*Method: walk every branching path, state transition, and boundary condition implied by the requirements; report ONLY unhandled edge cases — gaps the PRD does not address. Paths the PRD already handles are not listed. Method-driven, not attitude-driven.*

**Documents reviewed:** `prd.md` (full), `addendum.md` (full).
**Scope of hunt:** state transitions (subscription, tier, account lifecycle), boundary conditions (deadlines, counts, empty/zero), concurrency, migration of malformed/edge data, redirect collisions, follow-graph integrity, Afrikaans pluralization/number-agreement, POPIA deletion fan-out.

---

## CRITICAL

### EC-C1 — Published works orphaned when a member is deleted (POPIA deletion fan-out undefined)
**Refs:** §11 Data Governance (Retention), §8 POPIA, FR-19, FR-29, FR-38, FR-42, FR-48, FR-50, OQ-3.
The PRD says POPIA deletion "removes personal data while preserving published **bydraes** in anonymised/attributed form" — but the **fan-out of a deletion across the relational graph is entirely unspecified**, and a deleted member is not a single row. Each of these references the deleted member and has no defined behaviour:
- **volg records** (FR-38): the deleted member as *volgeling* and as *followed skrywer* — are inbound/outbound follow edges deleted? Do follower counts on other profiles decrement? (See also EC-H4.)
- **leeslys entries** (FR-29) pointing at the deleted member's works, and the deleted member's own saved lists.
- **reaksies / Gemeenskapsreaksies** (FR-26–28) authored by the deleted member on others' works — these are personal data (authored content) but also part of others' feedback density (SM-6). Kept attributed? Anonymised? Removed?
- **reader ratings & reviews** (FR-42) written by the deleted member — aggregate ratings on *other* writers' profiles shift if removed.
- **challenge entries and winner records** (FR-48, FR-50) — a deleted member may be the recorded *"Oktober Goud-wenner"*. A winner record is a queryable public artifact; deleting the person breaks the historical claim.
- **graderingsgeskiedenis** (FR-12) — the audit log references the member as subject; can an auditable log be POPIA-deleted, and does that defeat its audit purpose?
"Anonymise the bydrae" is stated; the rest of the graph is silent. This is launch-stakes for a SA platform.

### EC-C2 — "Anonymised/attributed" authorship is self-contradictory and the attribution mechanism is undefined
**Refs:** §11 Retention, OQ-3.
The retention rule says works are preserved "in anonymised/attributed form" — those are mutually exclusive outcomes and the PRD defers the choice to OQ-3 (correctly flagged), but it does **not specify the data mechanism either way**. If anonymised: what author entity owns the orphaned `bydrae` (a sentinel "onttrekte lid" user? null author?) and how do **Ontdek** author filters (FR-34), **Skrywerprofiel** (FR-40), and pinned works (FR-41) behave when the author no longer exists? If attributed: that retains a name post-deletion, which may itself violate the deletion request. No sentinel-author concept, no display fallback, and no Afrikaans label for an orphaned author is defined. Downstream build cannot proceed without this.

### EC-C3 — Migrated subscription with active-but-expiring state at cutover (boundary on the clone)
**Refs:** MR-5, FR-6, UJ-6, addendum §E/§H.
Subscriptions "ride the DB clone" and are verified active on the new host — but the PRD never addresses a subscription whose **expiry falls inside the migration/DNS-cutover window**. Edge states with no handling: (a) a membership that expires *during* the freeze between DB clone and DNS cutover — is it active or expired on first login? (b) a membership whose WooCommerce expiry cron didn't fire on the new host because cron timing/timezone differs post-clone, leaving a *stale-active* membership that should have lapsed (over-granting entitlement); (c) a membership in a pending/on-hold/cancelled Woo state (not just active/expired) — MR-5 only verifies "active state" and the binary active⇄expired model in FR-6 has no slot for Woo's intermediate statuses. SM-1 demands 100% subscription survival but defines no reconciliation for boundary-dated or non-binary states.

---

## HIGH

### EC-H1 — Subscription expiry mid-action (draft saved while subscribed, published after lapse)
**Refs:** FR-6, FR-19, FR-23, §4.4.
A **skrywer** saves a **konsep** while subscribed (FR-23 permits draft save), then their **intekening** lapses, then they hit **Plaas**. FR-19 gates *plaas* on active entitlement at publish time — good — but the PRD never states *when* the gate is evaluated for a long-lived draft, nor what happens to an in-flight publish: does the draft persist (it should, per "expiry doesn't unpublish")? Is the member shown the Afrikaans denial (FR-9) *at the publish click* with the half-written piece preserved? The mid-session lapse (subscribed at form-open, expired at submit) and the challenge-linked draft (FR-22) whose challenge also closes meanwhile are unhandled.

### EC-H2 — Challenge deadline boundary, late entries, and post-deadline edits
**Refs:** FR-22, FR-47, FR-48, FR-46 (countdown), UJ-4.
"After the deadline, winners are announced" — but the **deadline boundary itself is undefined**: (a) is an entry submitted at exactly the deadline timestamp in or out? (b) what timezone anchors the deadline (SAST assumed but unstated) — the FR-46 countdown and the server cutoff must agree or members racing the clock get inconsistent results; (c) can a **skrywer** *edit* a linked entry after the deadline (the piece is a normal `bydrae` they own and can presumably edit via the submission form), retroactively changing a judged entry? (d) what blocks linking a `bydrae` to an **uitdaging** whose deadline has already passed (FR-22 says "active uitdaging" but doesn't define when a challenge stops being linkable vs. merely closed-to-winners)?

### EC-H3 — Duplicate / multi-entry per challenge and tier-change mid-round
**Refs:** FR-48, FR-49, UJ-4.
No rule on **how many entries one skrywer may submit to one uitdaging**. Can a writer flood a round with ten entries (all judged in their tier pool)? Nothing caps it. Compounding: a **skrywer promoted from Silwer→Goud after entering but before judging** (FR-12 promotions can happen any time) — is their entry judged in the Silwer pool they entered or the Goud pool they now occupy? FR-49 says pools are by tier but never pins *which* tier snapshot (entry-time vs. judging-time) governs. UJ-4's "judged against her peers" assumes a fixed pool that the promotion model can move out from under her.

### EC-H4 — Following-feed and counts when a followed skrywer is deleted/loses entitlement
**Refs:** FR-38, FR-39, FR-44, §13.
**volg** is asymmetric and the feed surfaces "new publications by followed skrywers only." Unhandled: (a) following a **skrywer** who is later **deleted** — does the follow edge dangle, does the **volgeling** count on the (now-gone) profile error, do **kennisgewings** (FR-44) fire for a deleted target? (b) a followed writer whose **intekening lapses** — they publish nothing new (publishing gated), so the feed silently goes empty with no "this writer is quiet" state; the follow relationship persists pointing at a writer who can no longer produce feed items. (c) following yourself / re-following after unfollow (idempotency of the follow toggle) — no rule that prevents a duplicate edge, which matters because **MR-8 itself creates edges programmatically** (see EC-H7).

### EC-H5 — Redirect collisions when distinct legacy slugs map to the same new CPT permalink
**Refs:** MR-6, MR-7, NFR-4, OQ-1.
Migration moves flat `/[slug]/` URLs onto typed CPT bases (`/storie/<slug>/`, `/gedig/<slug>/`, etc.). Two collision classes are unaddressed: (a) **slug collision across types** — if a legacy `gedig` and a legacy `storie` shared the same flat slug (or two posts reclassified into the same CPT had colliding slugs), WordPress will append `-2`, silently breaking the 1:1 301 mapping and the SM-1 "100% of moved URLs return a 301" claim; (b) **the skryfwerk holding bucket** (MR-6) — unclassifiable posts get a `skryfwerk` URL; when later reclassified (the "defined later-reclassification policy" risk #2 admits is undefined), the URL changes *again*, requiring a *second* redirect (chained 301: old flat → skryfwerk → final CPT). Redirect chaining and the re-redirect on reclassification are not specified.

### EC-H6 — Stale-active over-grant: tier spreadsheet vs. account that no longer exists / duplicate emails
**Refs:** MR-3, MR-4, FR-11.
Tier import joins CSV→`ink_writer_tier` **on email**. Edge cases with no rule: (a) a spreadsheet row whose email matches **no** WP account is "flagged for manual follow-up" (handled) — but a spreadsheet email matching **two** WP accounts (legacy dup registrations, email reuse) has no tie-break; (b) **case/whitespace/diacritic normalisation** on the email join (Afrikaans names, trailing spaces in a hand-maintained spreadsheet) — an unnormalised join silently drops Goud/Silwer writers to default Brons; (c) a WP account with **no corresponding spreadsheet row** defaults to Brons (probably fine) but is indistinguishable from a *failed-join* Brons — the FR-11 "flag" covers ambiguous tier values, not failed-email-joins.

### EC-H7 — Friendship→follow conversion: non-mutual, self, or one-sided legacy data
**Refs:** MR-8, FR-38.
MR-8 converts each BuddyPress friendship into **two** mutual **volg** records. Edge cases: (a) a friendship referencing a user who **wasn't imported** (failed MR-3, flagged account, or a deleted legacy user) — creates a follow edge to a non-existent member (dangling edge, ties back to EC-H4a); (b) **duplicate friendship rows** in legacy data → duplicate follow edges (no dedup rule); (c) a legacy *pending* (not accepted) friend request — is it a friendship to convert or not? BuddyPress stores pending and confirmed friendships; MR-8 says "each friendship" without qualifying confirmed-only.

---

## MEDIUM

### EC-M1 — Afrikaans pluralization / number-agreement in dynamic count strings
**Refs:** FR-28 ("342 hartjies"), FR-38 (volgeling/following counts), §3 glossary plurals, §6 voice.
The glossary fixes plurals (lid→lede, bydrae→bydraes, volgeling→volgelinge, borg→borge) but **dynamic count strings have no singular/plural agreement rule**. "342 hartjies" is shown verb-less — but **"1 hartjies"** is wrong Afrikaans; it must be "1 hartjie." Every count surface needs n=1 vs n≠1 (and n=0, see EC-M2) forms: 1 volgeling / 2 volgelinge, 1 reaksie / 5 reaksies, 1 bydrae / 9 bydraes, 1 inskrywing / 4 inskrywings. No FR or copy rule requires plural-aware rendering, and gettext `_n()` plural handling for the `af` locale is not mentioned in the i18n mechanism (addendum §D), which only covers leak vectors, not plural forms.

### EC-M2 — Empty / zero states across every list and count surface
**Refs:** FR-28, FR-29, FR-32–36, FR-39, FR-43, FR-50, FR-52, FR-54.
No zero-state copy or behaviour is specified for: a profile with **0 volgelinge / 0 bydraes / 0 reaksies** ("0 hartjies" vs. hide the count — ties to EC-M1); an **empty leeslys**; an **empty following-feed** (new member follows no one, or follows only quiet writers — EC-H4b); **Ontdek** with zero results for a filter/search (FR-33–35); a **uitdaging with zero entries** at deadline (who wins? is a winner forced? FR-50 winner record); a tier pool with **only one entrant** (FR-49 "Brons vs Brons" with a pool of one); the **ledegids** before/at migration; the **Biblioteek** before any winner exists. Afrikaans-first means each needs human-authored empty copy (§6, OQ-12), none of which exists.

### EC-M3 — Tier demotion / correction has no model (system is promotion-only)
**Refs:** FR-12 (bevorder = promote), FR-11, graderingsgeskiedenis.
The tier system is explicitly **promotion-only** ("bevorder them"). Unhandled reverse/lateral transitions: an **erroneous promotion** (wrong writer, wrong tier) — can a **redakteur** demote/correct, and does graderingsgeskiedenis record a reversal or just append-only promotions? An import that set someone to Goud in error (no demotion path to fix it without raw DB edit). The audit-log is described as promotion history only; a correction/reversal event type is absent. Not launch-blocking but creates an operational dead-end the first time staff fat-finger a tier.

### EC-M4 — Concurrent edits and double-submit
**Refs:** FR-16–23, FR-12, FR-41, FR-42.
No optimistic-locking, idempotency, or last-writer-wins policy anywhere. Concrete cases: (a) **double-click Plaas** / slow PayFast return → duplicate publish or duplicate membership purchase (FR-5 success return + impatient retry); (b) two **redakteurs** promoting the same writer simultaneously → two graderingsgeskiedenis rows, racing `ink_tier_promoted_at` (FR-12); (c) a member editing their **leeslys** or **pinned works** (FR-41) from two devices; (d) two members posting the **last allowed** challenge entry at the deadline edge (ties to EC-H2). The PRD's only concurrency mention is unit-testing the follow graph, not write-collision policy.

### EC-M5 — PayFast failure / abandonment / partial-return paths
**Refs:** FR-5, FR-6, FR-9, NFR-9.
FR-5 specifies the **success** return ("on successful PayFast return, membership activates"). The unhappy paths are unspecified: payment **declined/cancelled** at PayFast (member returns with no entitlement — what Afrikaans state, FR-9 only lists active/expired/denied, not "payment failed/cancelled"); **abandoned** checkout (member closes the PayFast tab — orphaned pending Woo order); **ITN/IPN arriving without the browser return** or vice-versa (PayFast's async notification vs. the redirect can arrive in either order or one may not arrive) — entitlement timing is undefined; **duplicate ITN** (idempotency, ties EC-M4a). The E2E journey (NFR-9) tests only the happy "buy → publish" path.

### EC-M6 — "Skryfwerk" holding-bucket items are reachable but have no front-end contract
**Refs:** §3 (skryfwerk "not a user-facing term"), MR-6, FR-32–35.
Unclassifiable legacy posts land in `skryfwerk` and keep a live (redirected) URL (EC-H5b). But `skryfwerk` is declared **not user-facing**, yet the items are published content with authors and inbound 301s. Unhandled: do these appear in **Ontdek** (FR-33 filters by Gedigte/Stories/Artikels — `skryfwerk` is none of those, so it's *invisible to discovery but reachable by direct URL*), in the author's **Skrywerprofiel** work list (FR-40), in author filters (FR-34), in search (FR-35)? A logged-in author seeing a published work that appears nowhere in their profile is a silent-content gap. The "defined later-reclassification policy" (risk #2) is admitted-undefined.

### EC-M7 — Reactions / responses / ratings on works whose author lapses or whose work is later unpublished
**Refs:** FR-6 ("expiry does not unpublish"), FR-26–28, FR-42, §13.
Entitlement expiry leaves works published (good), but reverse interactions on a **konsep that was once published then... ** — actually the gap is: can a **skrywer** themselves **unpublish/delete** a `bydrae` that already carries reaksies, Gemeenskapsreaksies, leeslys saves, and challenge-winner status? FR-50 winner records and FR-29 leeslys entries pointing at a now-unpublished/deleted work have no cascade rule (parallel to EC-C1 but triggered by the author, not POPIA). Also: can a **lid without entitlement** still react/respond/rate (FR-26–28, FR-42 say "a lid", not "a subscriber") — presumably yes (engagement isn't gated), but a lapsed *skrywer* reacting on their own gated content is an untested mix.

### EC-M8 — Reading-layout boundary cases for gedig (FR-25)
**Refs:** FR-25, FR-17 (line+word counters).
The **gedig** layout is "stanza-aware, preserves line breaks, supports Roman-numeral stanza markers, per-line resonance." Boundary cases with no defined rendering/limit: an extremely long single line (no wrap policy at 768px content width, §6); a poem with **no stanza breaks** (one block — does per-line resonance still segment correctly?); a poem pasted with mixed/irregular line endings or trailing blank lines (the line counter in FR-17 and the per-line highlight in FR-26 must agree on what a "line" is); concrete/shaped poetry where whitespace is meaningful vs. the plain-text editor (FR-18) that may collapse it. Per-line **reaksie** (FR-26) on a blank/whitespace line is undefined.

### EC-M9 — Configurable plan changes vs. existing/in-flight subscribers
**Refs:** FR-4 (staff can change/retire plans), FR-8 (renew from profile), §13.
Staff can re-price, re-term, **add or retire** a plan with no code change (FR-4). Unhandled state interactions: a plan **retired** while members hold an active **intekening** on it (their record references a now-unpurchasable plan — does renewal FR-8 still offer it, or force-migrate them?); a **price change** mid-term (does it affect the active subscriber at renewal only?); renewal (FR-8) when the member's **original plan no longer exists** (FR-8 says "choosing from the available plans" — so their old plan may be gone, but no copy/flow handles "your previous plan is retired"). FR-4's "only currently-configured products are purchasable" implicitly strands holders of retired plans at renewal time with no defined message.

---

## LOW

### EC-L1 — Roman-numeral stanza markers vs. Afrikaans/locale numbering and very high counts
**Refs:** FR-25.
Roman-numeral stanza markers are supported, but there's no rule for a poem with **more stanzas than tidy Roman numerals** (stanza L, C…) or whether the marker is author-entered text vs. system-generated (collision with a poem that legitimately *uses* "I." as content). Minor, but the auto-vs-manual marker source is ambiguous.

### EC-L2 — Search behaviour on Afrikaans diacritics and the verhaal→storie rename
**Refs:** FR-35, OQ-1, MR-6.
Search over works/writers (FR-35) has no rule for **diacritic-insensitive** matching (ê, ë, ô, î common in Afrikaans) — a search for "gedroom" vs "gedrôom" type variance, or names with diacritics. Also a reader searching the **legacy term "verhaal"** (now `storie`) finds nothing despite the content existing — no synonym/legacy-term search aid, though this is cosmetic.

### EC-L3 — "Merk alles as gelees" with zero or concurrently-arriving notifications
**Refs:** FR-44.
"Mark all as read" against an **empty** notification set (no-op state) and against notifications **arriving during** the mark-all operation (a new follow-alert lands mid-click and is left unread, appearing as a phantom unread immediately after "mark all read"). Trivial but a common polish miss.

### EC-L4 — Sponsor scheduling boundary and zero-active-sponsor homepage
**Refs:** FR-58, FR-59.
Sponsor display rotates "by campaign dates" (start/end). Unhandled: **no active sponsor** in the current window — does the homepage **borg** strip (FR-59) collapse gracefully or show an empty slot? Overlapping campaigns where **more than one** sponsor is active but the homepage shows "one featured/rotating" — the rotation tie-break / ordering among simultaneously-active sponsors is unspecified. A campaign with **start == end** date (single-day) boundary inclusivity is undefined.

### EC-L5 — First-social-action prompt targets when the platform is freshly migrated/empty
**Refs:** FR-3, UJ-1.
The post-signup prompt invites following a **skrywer** or saving a **bydrae** — fine at steady state, but at the **migration/cold-start boundary** (or for the very first new members), if discovery surfaces are thin the prompt may have weak targets. Minor; the prompt is skippable (FR-3 handles the skip), so impact is low, but "who/what is suggested" is undefined.

---

## Summary of counts
- **Critical:** 3 (EC-C1, EC-C2, EC-C3)
- **High:** 7 (EC-H1–EC-H7)
- **Medium:** 9 (EC-M1–EC-M9)
- **Low:** 5 (EC-L1–EC-L5)
- **Total unhandled edge cases:** 24

## Method note
Paths the PRD already handles were excluded by design, e.g.: entitlement expiry not deleting account/tier/works (FR-6); missing/ambiguous tier on import → brons + flag (FR-11, MR-4); tier⇄subscription non-conflation (FR-13, THE conflation rule); expired Goud denied plaas (FR-19); spreadsheet email with no WP account → flagged (MR-4); skippable first-social-action prompt (FR-3); friendship→two follow edges as the conversion shape (MR-8, though its data-quality edges are flagged in EC-H7); preserved `/biblioteek/` `/opleiding/` prefixes to cut redirect volume (MR-7). These were verified present and are not counted as gaps.
