# Reconciliation: INK PRD ↔ migration-plan.md

*Date: 2026-06-15. Reconciles `prd.md` (§10 Brownfield Migration & Rollout + §11 Data Governance) and `addendum.md` (§E Migration scripting order) against `docs/migration-plan.md`.*

*Recent PRD edits in scope: MR-5 (manual pre-cutover verification of active subscriptions — state/plan/expiry — not "verify expiry logic fires"); MR-8 (convert only CONFIRMED friendships into two follows, dedup duplicates, skip edges to non-imported accounts); new deferred open items OQ-16 (reclassification accuracy + `skryfwerk` slug/URL handling, slugless-until-classified) and OQ-18 (cutover-boundary + expiry-cron/timezone reconciliation; tier-import email-join normalisation).*

---

## A. CONTRADICTIONS (flag first)

### C-1 — Subscription verification: plan still says "verify expiry logic FIRES"; MR-5 was deliberately reframed to manual state/plan/expiry verification

**This is the headline contradiction and it is the one the MR-5 edit was meant to resolve, but the plan was not updated to match.**

- **Migration plan**, §"Subscriptions" → "What to verify post-migration" (lines 137–141) lists three checks, the third being:
  > "Confirm that **expiry and suspension logic continues to fire correctly** on the new host."
- The suggested-order step 5 (line 349) echoes only "confirm active memberships, plan IDs, and access rules are intact" — i.e. it *does* match the state/plan/access-rule half of MR-5, but the data-domain section's third bullet still asserts the **expiry-logic-fires** test as a verification step.
- **PRD MR-5** (line 579) now scopes the binding pre-cutover check to **"state, plan IDs, expiry date"** (static snapshot verification) and explicitly relocates **"Cutover-boundary and expiry-cron/timezone reconciliation"** to a deferred build item (§16 **OQ-18**, line 701, which names EC-C3 "cutover-boundary + expiry-cron/timezone subscription reconciliation").
- The addendum §E (line 48) likewise says only "verify subscriptions (no import; ride the clone)" — no expiry-logic-fires test.

**Verdict:** Direct framing contradiction. The plan mandates an active expiry/suspension-logic firing test as part of migration verification; the PRD has deliberately *demoted* that to deferred build hardening (OQ-18) and keeps only static state/plan/expiry-date verification at the cutover gate. **Recommendation:** update the migration plan's Subscriptions verification bullet to (a) keep the static state/plan/access-rule checks at the cutover gate, and (b) move the "expiry/suspension logic fires correctly" check into the OQ-18 build-hardening checklist (cron/timezone reconciliation), so the plan stops asserting it as a pre-cutover verification step.

### C-2 — Friendship conversion: plan describes a raw BP-table clone, NOT a confirmed-only / deduped / two-follow conversion

- **Migration plan**, §"BuddyPress community data" (lines 257–273):
  > "Friendships: migrate using BuddyPress's own data tables, which survive a database clone."
  and suggested-order step 14 (line 358): "Verify BuddyPress data. Check friendships…". The "Scriptable" list (line 375) calls it "BuddyPress activity and friendship table migration (database operation)."
- This treats friendships as **BP friendship-table rows carried wholesale by the DB clone** — there is **no mention** of: (i) restricting to **confirmed** friendships, (ii) **converting** each into **two** one-way `volg` records, (iii) **dedup** of duplicate rows, (iv) **skipping** edges to non-imported/flagged accounts, or (v) excluding **pending** friend requests.
- **PRD MR-8** (line 582) and **addendum §E** (line 49) both specify exactly that conversion: confirmed-only → two mutual follows, dedup, skip non-imported edges, pending not converted. PRD §4.7 FR-38 and §2.3 UJ-6 (line 107) reinforce that the legacy friendship model is *replaced* by one-way follow and that migration converts each confirmed friendship into two follows.

**Verdict:** Genuine contradiction of approach. The plan's friendship handling (clone the BP tables as-is) is incompatible with the PRD's model (BuddyPress Friend Connections are **off** — FR-37 — so the BP friendship tables cannot be the live relationship store; they must be *read* as a migration source and *converted* into `ink-core` follow records). Cloning the friendship tables and leaving them is not just under-specified, it would leave data in a subsystem the PRD turns off. **Recommendation:** rewrite the plan's Friendships bullet to describe the MR-8/§E conversion (confirmed → two `volg` records, dedup, skip non-imported, drop pending), and reclassify it from "database operation" to a scripted transform.

---

## B. GAPS — binding steps in the plan that the PRD's migration section under-documents or omits

### G-1 — "Subscription record creation from CSV" appears in the plan but contradicts the whole no-import stance (and the PRD)

The plan's **"Scriptable"** list (line 372) includes **"Subscription record creation from CSV."** This is a **stray, self-contradictory line**: the same plan's Subscriptions data-domain section (lines 128–145), the Pre-migration note (line 85), and decision capture all state subscriptions ride the DB clone with **no import script**. The PRD (MR-5, §10 "Must survive", addendum §E) is consistent and correct: **no subscription import.** This is a defect *in the plan*, not the PRD — flag it so the plan's Scriptable list is corrected (delete the CSV-subscription line) to avoid a downstream agent scripting a subscription import that must not exist.

### G-2 — Comment-count before/after verification: in plan, not surfaced as a binding MR

- Plan §"Comments" (lines 247–253) and §"Posts → What to preserve" (line 174) require comments to migrate with posts and **comment counts to match before/after** (also addendum §E line 53: "Comments migrate with posts; verify counts before/after").
- PRD §10 "Must survive" lists comments as part of *bydraes* preservation, and SM-1 covers data-preservation, but **no MR step names the comment-count reconciliation check** and MR-10 (verify media) doesn't cover it. Minor gap: the binding count-match verification is in the plan + addendum but not elevated to an MR. Low risk (addendum carries it), but worth a one-line MR or an explicit fold into MR-10/SM-1.

### G-3 — Taxonomy term remapping / new-taxonomy definition step is in the plan but thin in the MR list

- Plan §"Pre-migration decisions" #2 "Define new taxonomy structure" (lines 62–68) and the Scriptable "Taxonomy term remapping" (line 374), plus "existing categories and tags (map to new taxonomy)" (line 174), make taxonomy definition + term remapping a **binding pre-content step**.
- PRD **MR-2** (line 576) covers "Define CPTs/taxonomies/meta in `ink-core` before migrating content" — so taxonomy *definition* is covered. But the **term-remapping** transform (old categories/tags → new genre/vaardigheid/uitdagingsrondte terms) during post migration is **not explicitly stated** in MR-6 (which only covers post→CPT reclassification). Partial gap: the term-remap half of the plan's taxonomy work is not visible in the MR list.

### G-4 — Youzify FES upload re-association ordering ("extract BEFORE deactivation") is in addendum §E + plan, but MR-list reference is implicit

- Plan §"BuddyPress" (line 273) + §"Media" (line 285) + addendum §E (line 51) make it binding: extract Youzify custom-table profile/social + FES upload data **before** deactivation, then re-associate uploads with the new submission model.
- PRD §10 captures this as **Top migration risk #4** (line 591), not as a binding MR step. Acceptable (it is preserved in the risk list and addendum), but it is an *ordering constraint* (must happen before Youzify deactivation), so framing it only as a "risk" slightly understates its binding sequencing nature. Minor.

### G-5 — User role reassignment: plan still frames base roles as "reader or writer"; PRD FR-2 abolishes the reader/writer identity

- Plan §"Users and profiles" (line 102): "assign correct base role for each user: **reader or writer**" and §"Writer tiers" edge case (line 122): default to **`bronze`** (English casing).
- PRD **FR-2** (lines 183–188) is emphatic: **no stored reader/writer identity**, no intent flag; the distinction is behavioral. **MR-3** (line 577) says "reassign roles to reader/writer base roles" — which itself echoes the plan's now-obsolete framing and sits in tension with FR-2.
- Tier default casing: plan says **`bronze`** (line 122); PRD/terms canonical is lowercase **`brons`** (MR-4 line 578, FR-11, OQ-1 RESOLVED line 684). Plan is stale here.

**Verdict:** The plan is stale on (a) reader/writer base roles (FR-2 removed the concept) and (b) `bronze`→`brons` casing (OQ-1 resolved). Note MR-3 partly inherits the stale role framing. **Recommendation:** correct the plan's role bullet to a single base member role (no reader/writer split) and fix `bronze`→`brons`; consider tightening MR-3 wording so it doesn't imply a stored reader/writer role distinction.

---

## C. Items correctly aligned (no action)

- **No subscription import / ride the DB clone** — plan (lines 15, 85, 128–145), addendum §E, PRD MR-5 + §10 "Must survive" all agree. (Except the stray G-1 CSV line.)
- **PayFast is a new feature, not a migration task** — plan §"PayFast note" (line 145) matches PRD FR-5 and the addendum's framing; no migration dependency on subscription import.
- **`skryfwerk` holding bucket, no hand-classify at volume** — plan §1 (lines 46–59) + Scriptable (line 371) match PRD MR-6 (line 580) and addendum §E. **OQ-16** (deferred) correctly captures what the plan leaves open: the plan says "do not classify by hand if volume is high" and "preserves content and keeps it searchable" but sets **no later-reclassification target/drain plan and no slug/URL-collision policy** — exactly what OQ-16 defers to migration build (reclassification accuracy + `skryfwerk` slug handling + slugless-until-classified). No contradiction; OQ-16 is the right home for the plan's open ends. PRD risk #2 (line 589) also points to OQ-16.
- **Keep `/biblioteek/` and `/opleiding/` prefixes** — plan Redirects recommendation (line 333) matches MR-7 + NFR-4.
- **Redirect generation during CPT migration (record old permalink before reassignment)** — plan (line 335) matches MR-7 + addendum §E.
- **Don't clone `wp_options` wholesale** — plan §"Settings and options" (lines 301–312) matches MR-1 + addendum §E. (Note: plan says carry **Yoast** SEO settings (line 309); PRD/addendum retire Yoast for **Rank Math** and set SEO up fresh — minor stale reference, see also addendum §E line 50 "SEO set up fresh in Rank Math".)
- **Activity trimming >2yr; notifications not migrated** — plan §BuddyPress (lines 268–271) matches PRD risk #5 + addendum §E.
- **DNS cutover only after all verifications pass** — plan order step 16 matches MR-11.
- **Historical challenges as flat archive (don't migrate `monthly_challenge` 1:1)** — plan #3 (lines 70–77) + §Challenges (lines 226–233) match OQ-7 (RESOLVED).
- **Tier import joined on email** — plan (line 115) matches MR-4; the plan does **not** over-specify, and **OQ-18** correctly defers the email-join normalisation/double-match/failed-join-vs-default rules (EC-H6) the plan leaves unaddressed. No contradiction.
- **OQ-18 cutover-boundary/cron-timezone reconciliation** — this is the correct home for the expiry-logic concern that C-1 flags as stale in the plan; once the plan bullet is fixed, OQ-18 owns the deferred reconciliation.

---

## D. Net assessment

- The PRD's recent edits (MR-5, MR-8, OQ-16, OQ-18) are internally consistent with the addendum §E and are the **more current** statements.
- The **migration plan lags behind** them in two binding places: **(C-1)** it still mandates an expiry-logic-fires test the PRD demoted to OQ-18, and **(C-2)** it describes friendship migration as a raw table clone rather than the confirmed→two-follow conversion. Both should be corrected **in the plan** so a downstream build agent doesn't follow the stale instructions.
- Secondary plan staleness: **G-1** stray "subscription CSV import" line, **G-5** `bronze` casing + reader/writer base roles, and the Yoast-settings carry-over line — all superseded by resolved PRD decisions (OQ-1, FR-2, Rank Math).
- The PRD's migration section does not *miss* any binding plan step in a way that loses data, but **G-2** (comment-count reconciliation) and **G-3** (taxonomy term-remap transform) are binding plan/addendum steps not elevated into the MR list and could be surfaced as explicit MRs or folded into MR-6/MR-10/SM-1 for traceability.
