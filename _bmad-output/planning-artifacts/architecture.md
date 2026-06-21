---
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8]
lastStep: 8
status: 'complete'
completedAt: '2026-06-17'
inputDocuments:
  - _bmad-output/planning-artifacts/prds/prd-ink-vibe-2026-06-14/prd.md
  - _bmad-output/planning-artifacts/ux-designs/ux-ink-vibe-2026-06-15/DESIGN.md
  - _bmad-output/planning-artifacts/ux-designs/ux-ink-vibe-2026-06-15/EXPERIENCE.md
  - _bmad-output/project-context.md
  - docs/specs/ink-feature-list.md  # superseded 2026-06-20 by _bmad-output/planning-artifacts/epics.md (source of record)
workflowType: 'architecture'
project_name: 'ink-vibe'
user_name: 'Cobus'
date: '2026-06-16'
---

# Architecture Decision Document

_This document builds collaboratively through step-by-step discovery. Sections are appended as we work through each architectural decision together._

---

## Project Context Analysis

### Requirements Overview

**Functional Requirements:** 70 FRs (FR-1…FR-63 plus sub-items FR-3a/9a/12a/44a/44b/50-R1/50-R2, post the 2026-06-20 scope change) across 13 feature clusters —
Identity & Registration, Membership/Payment/Access, Writer Tiers, Submission &
Publishing, Reading & Engagement, Discovery, Community & Social, Challenges,
Library, Training, InkPols, Sponsors, Organisation & Marketing. Downstream
breakdown: 19 epics / ~120 stories (`epics.md`, source of record — supersedes `ink-feature-list.md`). Architecturally the
FRs cluster into: (a) a custom business-logic plugin (`ink-core`) owning content
models, tiers, submission gate, follow graph, engagement, challenges, sponsors;
(b) a presentation-only FSE block theme (`ink-foundation`); (c) integration seams
to vetted platform plugins for commodity capability.

**Non-Functional Requirements:** 9 cross-cutting NFRs. Defining driver is
**NFR-1 (Afrikaans-first, zero English leakage)** — a standing automated gate, not
a one-time check, spanning every output surface incl. plugin-composed sentences,
transactional emails, plugin JS, and REST/AJAX/feeds. **NFR-9 (test harness)** is
foundational (Epic 1, not deferred), a pyramid concentrated in `ink-core`.
**NFR-2** makes `theme.json` tokens the production source of truth. NFR-3/4/7/8
cover performance/caching, SEO + 301/URL integrity, staging-gated update
governance, and observability (incl. the leak scan as a standing CI/cron gate).

**Scale & Complexity:**
- Primary domain: full-stack WordPress (FSE block theme + custom PHP 8.3 plugin),
  brownfield CMS on WP 7.0+ / PHP 8.3+.
- Complexity level: High — brownfield migration + payment automation + custom
  business-logic plugin + multi-plugin integration seams + a defining i18n gate.
- Estimated architectural components: two first-party artifacts (`ink-foundation`
  theme, `ink-core` plugin) + committed translations, integrating ~8 vetted
  platform plugins, over a cloned brownfield DB.

### Technical Constraints & Dependencies

- **Three-layer separation is non-negotiable**: presentation → theme; INK business
  rules & content models → `ink-core`; commodity capability → vetted plugins. No
  business logic in the theme or `functions.php`.
- **Platform: PHP 8.3+ / WordPress 7.0+**, FSE block theme (not classic), strict
  types + `ink_`/`Ink\` prefixing + enums for fixed value sets in `ink-core`.
- **Brownfield**: existing DB is cloned and reused — members, subscriptions,
  content, and media must survive; never assume a clean install.
- **Plugin integration via hooks/filters/template functions only** — plugins are
  never edited; each is an update-surviving seam (NFR-7). Retired plugins must not
  be reactivated.
- **Design source is intent, not code**: Lovable (React/Tailwind/shadcn) is never
  ported; tokens normalised to `theme.json`; copy from `ui-copy-translations.md`,
  not the English mockup.
- **Deferred decisions leave seams, not scaffolding**: POPIA posture (OQ-3/17),
  auto-renew / PayFast recurring (OQ-9), reclassification accuracy (OQ-16),
  account-lifecycle deletion fan-out (OQ-17), migration/build hardening (OQ-18).

### Cross-Cutting Concerns Identified

1. **Afrikaans-first / zero English leakage (NFR-1)** — defining; standing
   automated gate across all output surfaces; `ink-core` ships no English `.mo`.
2. **Three-layer separation** — the structural spine; governs where every unit of
   logic and presentation lives.
3. **THE conflation rule** — subscription *entitlement* and writer *tier* are two
   separate state machines with no write-path between them; enforced at the data
   layer + guardrail tests (see AD-1 for static-analysis enforcement).
4. **Design-token compliance (NFR-2)** — `theme.json` tokens canonical; light mode
   only at v1.
5. **Brownfield migration & URL/data preservation** — scripted, ordered migration;
   mandatory 301s on every URL change; preserved high-value prefixes.
6. **Foundational test harness (NFR-9)** — pyramid in `ink-core`; PayFast sandbox
   only; leak scan as a standing gate.
7. **Layered security / low PCI scope** — Cloudflare-locked origin, staff 2FA,
   escape/sanitise/nonce everywhere, PayFast off-site.
8. **Plugin-seam stability under update governance** — committed `.po/.mo`;
   staging-gated major updates; standing leak detection after ungated updates.
9. **Editorial low-friction** — shared `genre`/`vaardigheid` taxonomy auto-surfaces
   resources; no feature may depend on per-item manual editorial linking.

---

## Architecture Decisions

### AD-1: `ink-core` is a modular monolith, not a flat plugin and not a plugin fleet

**Status:** Accepted (2026-06-16)

**Context.** `ink-core` carries business logic in most of the 19 epics — content
models, entitlement, tiers, submission, engagement, follow graph, challenges,
sponsors, discovery, plus notifications, custom forms, and migration. Its default
trajectory is a "god plugin": one flat namespace with helpers reaching into each
other via globals and shared mutable state. The decision is *how* to structure it,
on a spectrum from (a) flat plugin → (b) modular monolith → (c) multiple
separately-activatable plugins.

**Decision.** Build `ink-core` as a **modular monolith**: one plugin, internally
partitioned into bounded modules, each exposing a small public `Api`/facade as its
only cross-module surface, over an **explicit, enforced dependency graph**.
Reject (a) and (c).

**Why not a plugin fleet (c).** INK is one site, shipping as one unit, versioned
together — a multi-plugin split buys nothing on deployment or reuse (the usual
justifications). It introduces activation-order fragility and partial-activation
states, and forces cross-domain coupling through *implicit* WP hooks instead of
*explicit*, type-checked PHP calls. WP 7.0's `Requires Plugins` header solves
dependency *declaration*, not the coupling cost. The conflation guardrail and the
foundational test harness are also far easier to enforce inside one codebase.

**Module structure (mirrors the dependency-ordered epics):**

```
Kernel  (enums: Tier {Brons|Silwer|Goud|Meester}/Reaction/ResponseType · bootstrap · PSR-4 autoload ·
         dbDelta schema registry · capabilities · i18n loader)
  └─→ Content  (CPTs · taxonomies · meta)
        ├─→ Entitlement  (WC Memberships seam → can_submit())     ⟂  Tiers
        ├─→ Tiers        (ster gradering · promotion log)          ⟂  Entitlement
        ├─→ Engagement · Social · Sponsors · Discovery
        └─→ Submission (→Entitlement gate, →Challenges link)
            Challenges (reads Tiers for pools; writes promotion via Tiers API)
            Notifications (subscribes to events)
            Forms (contact + report)
```

~10 runtime modules. Each module = a directory with its own bootstrap registering
its own hooks; the plugin main file loads Kernel, then each module's bootstrap.
Cross-module calls go through facades, never into internals.

**Consequences:**
- **THE conflation rule (FR-13) becomes an architectural invariant.** The absence
  of the `Entitlement ⟷ Tiers` edge is the conflation rule. Enforce it in CI with
  a dependency-rule checker (Deptrac / PHPArkitect / a PHPStan rule): `Ink\Entitlement`
  may not reference `Ink\Tiers` and vice-versa. This promotes the system's most
  important invariant from a runtime unit test to a build-time/CI one that fails on
  the first illicit `use` statement. This is the decision's strongest payoff.
- **Cross-module communication:** prefer direct facade calls for synchronous,
  in-request work; reserve namespaced WP events (`ink/submission/published`) for
  genuinely cross-cutting reactions (notifications, audit log) to avoid untraceable
  action-at-a-distance.
- **Do not wrap WordPress in hexagonal/ports-and-adapters.** WP is the framework —
  use CPTs, meta, `$wpdb`, and hooks directly inside each module. The boundary lives
  at module edges, not around every WP call. Over-abstraction is the more likely
  failure mode here than under-modularizing.
- **Keep module count bounded (~8–12).** Notifications/Forms may fold into adjacent
  modules if they stay thin.
- The shared `genre`/`vaardigheid` coupling across Content/Discovery/Training/
  Engagement is **intentional** (editorial-low-friction, FR-55) — not a smell to
  decouple.
- **Cost is almost entirely upfront** (directory conventions + one CI rule),
  established in Epic 1. Retrofitting after 15 epics ship is the expensive path —
  hence this is decided now.

**Related decision — migration is carved out of the runtime.** Epic 16 (migration)
does **not** live in the production `ink-core` runtime. It is one-time +
reconciliation work, and the project's production-hygiene rule bans
migration/diagnostic tooling on production. Deliver it as a **separate WP-CLI command
package (or mu-plugin)** used on staging and removed at cutover. This is the single
place a true split (separate distributable) is justified — by the production-hygiene
constraint, not by modularity for its own sake.

### AD-2: Entitlement gate evaluates the membership end date in SAST, at the publish moment

**Status:** Accepted (2026-06-16)

**Context.** FR-19 requires submission entitlement to be checked at the moment of
*plaas* (publish), not at draft creation. The policy is: a **lidmaatskap** is valid
through **end of day SAST** on its expiry date. WooCommerce Memberships flips a
membership's stored status to `expired` via a scheduled action (Action Scheduler on
wp-cron), so the *stored status flag* lags the true expiry instant by however long
until cron next runs. This raised a concern that a to-the-second cutoff is
impossible in WordPress.

**Decision.** The Entitlement module's `can_submit()` evaluates against the
membership **end date compared to current time**, **not** the cron-flipped status
flag. This is deterministic and cron-independent — it can honour "valid through end
of day SAST" exactly, with no lag window, because no scheduled job needs to have run
for the gate to return the correct answer.

- **Granularity:** "end of day SAST" is a deliberately more generous policy than
  WooCommerce's default precise end *datetime*. The gate treats the end date's
  calendar day as valid through **23:59:59 SAST = 21:59:59 UTC**. Times are stored in
  UTC and compared in SAST.
- **Single SAST helper:** the same SAST boundary helper serves both this gate and the
  challenge deadline / entry-freeze (FR-47, inclusive 23:59:59 SAST). One source of
  truth for "end of day SAST," reused — not two implementations.
- **Status flag is for passive reads only:** the cron-flipped `expired` status may
  still drive admin lists and UI badges, where brief lag is harmless. It must not be
  the authority for the publish gate.

**Consequences:**
- **Failure direction is benign.** Any residual leakage is *over-permissive* toward a
  member who was paying moments earlier (an occasional post slipping through), never
  *over-restrictive* against a valid member. With end-date evaluation this window is
  effectively eliminated; even relying on the status flag, the residual is bounded and
  accepted — not a launch risk.
- **Draft vs publish:** saving a **konsep** is ungated (FR-23); only *plaas* checks
  entitlement. A draft saved while entitled but published after lapse is denied at the
  publish moment (FR-9 message), draft preserved.
- **Reconciliation seam:** cutover-boundary + expiry-cron/timezone reconciliation is
  tracked at build-time under **OQ-18 / EC-C3** — this decision sets the runtime gate
  semantics; the migration/cron reconciliation rides the same SAST discipline.
- This is the Submission↔Entitlement edge of the triangle; the Submission↔Challenges
  ordering/freeze edges are addressed in AD-3.

### AD-3: Challenge entry is delegated to the Challenges module, captured at entry, judged on a deadline-bounded snapshot

**Status:** Accepted (2026-06-16)

**Context.** This completes the Submission ↔ Entitlement ↔ Challenges triangle. The
remaining edges are **Submission → Challenges** (a writer links a piece to an open
**uitdaging** at *plaas*, FR-22) and **Challenges → Tiers** (tier-based pools FR-49;
winner→promotion FR-51). The governing FRs: linking only while the round is open
(FR-22), inclusive 23:59:59 SAST deadline + post-deadline freeze (FR-47), ≤3 entries
per content type per uitdaging and tier fixed for the round (FR-48), per-tier pools
and placement records (FR-49/FR-50). Write concurrency/idempotency on the publish/entry
path is the EC-M4 / OQ-18 concern.

**Decisions.**

1. **Submission delegates entry; it does not own entry rules.** At the publish moment,
   if a challenge link is requested, Submission calls `Challenges::enter(post, round,
   user)`. The Challenges module owns and enforces, in one place: the open-window check,
   the per-type cap, tier capture, and the term + entry write. Submission never writes
   the `uitdagingsrondte` term or knows the cap (AD-1 facade discipline). The
   `uitdagingsrondte` *taxonomy* is registered by Content; the *act* of attaching it
   under challenge rules is Challenges' job.

2. **Entry is itself entitlement-gated, because entry happens via *plaas*.** The AD-2
   gate runs first; a writer whose **lidmaatskap** has lapsed cannot enter (cannot
   publish). Re-entering an *already-published* work into a new round is treated as an
   entitlement-gated action too — confirmed by founder 2026-06-16: entering a competition
   is closer to publishing than to engagement, so it requires active entitlement.

3. **Open-window check reuses the single SAST boundary helper (AD-2).** "Round is open"
   = `now ≤ deadline (23:59:59 SAST)`, evaluated **server-side at the publish moment** —
   never trusting the client-rendered countdown. No new links after close (FR-22).

4. **Tier is captured onto the entry at entry time; pools judge the snapshot.** The
   **inskrywing** snapshots the writer's **ster gradering** (via the Tiers read API) at
   the moment of entry. Pool segmentation (FR-49) reads the snapshot, never the live
   tier. A mid-round **bevordering** does not move an existing entry between pools —
   "tier is fixed for the round" (FR-48). This is the same capture-at-the-moment pattern
   as AD-2's entitlement gate. This is the Challenges → Tiers **read** edge.

5. **The freeze applies to the *judged version*, not the living work.** After the
   deadline the bydrae remains editable by its author (it is a living published work in
   the library); what freezes is the *content judged for the competition*. The judged
   version is the content **as of the deadline boundary**, resolved deterministically.
   The living **bydrae** is never locked. `[BUILD]` Mechanism — deadline-bounded post
   revision vs. a stored snapshot — is a build-time choice (OQ-18); if revisions are
   used, `ink-core` must guarantee revision retention for entered posts.

6. **Promotions write only through `Tiers::promote()`.** Winner→promotion (FR-51) is the
   Challenges → Tiers **write** edge and goes solely through the Tiers API, which writes
   the **graderingsgeskiedenis** log (actor, date, reason, from→to, optional challenge
   link). Challenges never writes `ink_writer_tier` directly. `Tiers::promote()` remains the
   **SOLE write path** for the tier field and the win-count reset.
   **(R3, 2026-06-20) Automatic challenge-driven promotion is now IN SCOPE** (was deferred
   P2, §14.2). The **win-counting / threshold engine lives entirely in `Ink\Tiers`**: a
   *win* = any top-3 placement at the writer's current gradering (each placement counts,
   incl. multiple in one category), accumulated in `ink_tier_win_count`; **Brons → Silwer at
   5 wins, Silwer → Goud at 15 wins**; on promotion the count resets to 0. As R2's final
   step, Challenges hands the placement records to `Tiers::promote()` (the facade), which
   runs the threshold check and the write — Challenges still never touches `ink_writer_tier`
   or the threshold logic. **Meester is manual-only** (no threshold, terminal state); staff
   manual promotion/demotion (FR-12) remains. Promotion fires a templated congratulation
   email (new ADR below).

7. **Entry is one atomic, idempotent operation.** Open-check → cap-check → tier-capture →
   term + entry write execute atomically with a deterministic dedup key (user + round +
   post). A concurrent or replayed submit must not create a 4th entry, a duplicate
   inskrywing, or a published post missing its term (EC-M4). Re-linking the same post to
   the same round before the deadline updates the entry in place (and may refresh the
   judged-version pointer) rather than consuming a second slot.

**Consequences:**
- **Module edges stay clean:** Submission → Challenges (entry, at publish moment);
  Challenges → Tiers (read at entry, write at result, both via API); Submission →
  Entitlement (AD-2). `Entitlement ⟂ Tiers` is still preserved (AD-1) — the competition
  path touches tier, the entitlement path does not.
- **Capture-at-the-moment is now the consistent pattern** across the triangle:
  entitlement evaluated at *plaas* (AD-2), tier snapshotted at entry (AD-3). Neither
  depends on a cron-flipped flag.
- **Open items folded to build:** freeze mechanism `[BUILD]` (decision 5, OQ-18);
  entry-path concurrency/idempotency (decision 7, EC-M4 / OQ-18). Re-entry gating
  (decision 2) is confirmed, not open.

### AD-4: Composer-managed WordPress with the standard `wp-content` layout (not Bedrock)

**Status:** Accepted (2026-06-17)

**Context.** INK is a **new codebase over cloned data**: the production *database* and
`wp-content/uploads` media are cloned and must survive (NFR-4 — URL/301 integrity,
media preservation), but the site's code and webroot are built fresh. The build
topology is therefore a free choice. Three options were weighed: (a) no whole-site
dependency management (Composer scoped to `ink-core` only); (b) **Composer-managed
standard layout** — Composer + `composer/installers` + wpackagist installing core and
free plugins into conventional `wp-content/...` paths, `.env` for secrets; (c) full
**Bedrock (Roots)** — webroot at `web/`, core in `web/wp/`, `wp-content` renamed and
relocated to `web/app/`.

**Decision.** Adopt **(b) Composer-managed standard layout** for the whole site.
Reject (a) — it forgoes reproducible dependency pinning the project explicitly needs;
reject (c) — Bedrock's relocated content dir collides with the cloned data without
enough offsetting benefit here.

**Why not Bedrock (c).** Bedrock serves uploads from `/app/uploads/`, but the cloned
DB and migrated media reference `/wp-content/uploads/...`. Adopting Bedrock means
either reconfiguring the uploads path to stay stable or accepting changed media URLs —
friction in the exact area NFR-4 most protects. It also moves admin to `/wp/wp-admin`
(re-tune the Cloudflare login rule), requires the host docroot to point at `web/` (a
NameHero deploy-process change on shared LiteSpeed hosting), and a few premium/niche
plugins (Real3D, PayFast gateway) occasionally assume standard paths. The hardening
Bedrock adds is achievable by config without these costs. (The usual argument *against*
Bedrock — disrupting an existing site's files — does **not** apply here, since the code
is new; the residual objection is purely the cloned-data URL collision above.)

**Consequences:**
- **Reproducible, pinned dependency set (serves NFR-7).** WP core + every free plugin
  (WooCommerce, BuddyPress, Rank Math, Redirection, LiteSpeed) pinned in
  `composer.lock`; identical set across wp-env / staging / prod / CI; version pins and
  staging-gated updates become auditable.
- **Clean repo.** Only `ink-core` + `ink-foundation` are committed; core and
  third-party plugins are fetched at build, not vendored into VCS. (Committed
  translation `.po/.mo` for surviving third-party plugins remain committed, per
  project-context.)
- **`.env` config** keeps PayFast keys, DB creds, and environment flags out of code and
  per-environment — fits the existing staging/prod split.
- **`/wp-content/uploads/` URLs stay intact** for migrated media — no reconciliation
  against NFR-4.
- **Premium plugins need a private source.** WooCommerce Memberships, PayFast gateway,
  Real3D, Patchstack are not on wpackagist; manage them via a private Composer repo
  (Satispress / `vcs` / `path`) or commit them with rationale. `[BUILD]` mechanism TBD.
- **Production hygiene preserved (NFR-7/§12):** dev/diagnostic/migration tooling is a
  `require-dev` / environment-gated concern, never shipped to the production build.

### AD-5: Persistence model — WP-native for content, custom tables for relational engagement, platform store where one exists

**Status:** Accepted (2026-06-17)

**Context.** `ink-core` owns a mix of content, classification, identity, and
high-volume relational engagement/competition data. Storing relational, queried-by-
relationship data in post-meta or the comments table scales badly; building custom
tables for everything ignores capabilities WordPress and the platform plugins already
provide. The decision is the per-entity storage model, with each store owned and
migrated (`dbDelta`) by its module (AD-1).

**Decision — storage per entity:**

| Entity | Store |
|---|---|
| bydraes, biblioteek_item, uitdaging, inkpols_uitgawe, borg, opleiding_artikel | **CPTs** (Epic 2) |
| genre, vaardigheid, uitdagingsrondte, ster_gradering | **taxonomies** |
| `ink_writer_tier`, `ink_tier_promoted_at`, `ink_tier_win_count` | **user-meta** (OQ-14) — `ink_writer_tier` enum incl. `meester` (manual-only terminal state, no threshold; AD-3 dec.6); `ink_tier_win_count` holds top-3 wins toward the next gradering and is **reset to 0 by the `Tiers::promote()` path** (R3) |
| Follow graph (volg) | **custom table** — asymmetric edges, bidirectional indexed queries |
| Tier history (graderingsgeskiedenis) | **custom table** — append-only audit log (FR-12) |
| Line highlight + reaksie | **custom table** (consolidated — a highlight always carries a reaction); one-per-user-per-line, counted (FR-26/28) |
| Challenge entry (inskrywing) + placement | **custom table** `ink_entries` — tier snapshot + judged-version pointer + queryable placements per tier/round (FR-50); the `uitdagingsrondte` term stays for discovery, the table is the authoritative competition record. **(R1/R2) Adds EntryID columns `entry_type` + `entry_number`** (per-type sequence — Gedigte/Stories/Artikels numbered separately) **assigned at *collation time*, not at entry time** — numbering depends on the closed field sorted by gradering + type, so it cannot be allocated when the entry is first written; it is the R2 match key against pasted results. Placement/winner columns (`algehele wenner` 1st vs `wenner` 2nd–3rd) drive the winner banner + featured ordering. |
| Ratings & reviews | **custom table** — moderation status + aggregation (FR-42) |
| **Leeslys (reading list)** | **custom table** `ink_reading_list(user_id, post_id, created_at)` — indexed, supports reverse "who saved this", ordering, dedup; avoids an unbounded serialized user-meta array |
| **Gemeenskapsreaksies (Lof/Insig/Voorstel)** + **Terugvoer van die moderator** | **WP comments infrastructure** — two sanctioned programmatic custom comment types (`ink_reaksie`, `ink_moderator_terugvoer`); see AD-5a |
| Kennisgewings (notifications) | **BuddyPress notifications store** (register custom `ink` types) — BP Notifications is ON (FR-37/44); no parallel table |

**Counts** ("342 hartjies", follower/response counts) go through the **object cache**
with write-time invalidation; denormalize into counters only if measured hot.

#### AD-5a: Gemeenskapsreaksies (and moderator feedback) reuse the WP comment infrastructure

**Decision.** Store structured responses as WP comments with a single custom
`comment_type = 'ink_reaksie'` plus comment-meta `ink_response_type ∈
{lof, insig, voorstel}` (the enum) — **not** three comment types and **not** a custom
table. **(R2) Moderator feedback** (the judge's "Terugvoer van die moderator" on an
inskrywing) is stored as a **second** custom comment type
`comment_type = 'ink_moderator_terugvoer'`, written programmatically via
`wp_insert_comment` (never the public form), with a **writer-controlled display toggle**
(comment-meta or user-meta) — the feedback is visible on a work only when the writer
enables it on **My Profiel** (private). This is the **same sanctioned programmatic
custom-type pattern as `ink_reaksie`**, not open commenting: it does not re-enable native
WP comments and does not violate the "no WP comments" non-goal (§13), which is about the
free-form UX, not the storage substrate. It is also the **sanctioned exception** to the
"Gemeenskapsreaksie is the ONLY feedback path" rule — moderator feedback is structured and
programmatic, not free-form member commenting.

**Rationale.** Reuses WP's moderation, spam (Akismet-compatible), and admin tooling
"for free," which a custom table would re-implement.

**Guardrails (so reuse doesn't leak):**
- **Default commenting stays disabled site-wide** (feature 1.8: `comments_open` /
  `pings_open` → false). Only programmatic `wp_insert_comment` with the custom type
  writes these rows. The **"no WP comments" non-goal (§13) is about the free-form
  UX**, not the storage substrate — reusing the comments table does not violate it,
  and this nuance is recorded so a future reader doesn't read it as a contradiction.
- **Write path is an `ink/v1` REST endpoint** in `ink-core` (nonce + capability +
  Afrikaans validation, type-enum enforced), which calls `wp_insert_comment` under the
  hood. Not the public comment form.
- **Moderation rides the core Comments admin screen** — English chrome, acceptable per
  the admin-language split (WP-core chrome stays English; `ink-core`'s own labels are
  Afrikaans).
- **FR-44 notification fires off the comment-insert hook** (`wp_insert_comment` /
  `comment_post`) → emits the BP kennisgewing.
- **Flat, not threaded** for v1 (no reply tree in the spec).
- **Custom-type rows must not inflate the displayed post comment count** — the response
  count surfaced in UI is a filtered count of `comment_type='ink_reaksie'` (cached),
  managed independently of WP's default `comment_count` handling.
- Engagement module owns this behind its facade (AD-1); the comment substrate is an
  implementation detail, not a cross-module surface.

**Consequences:**
- Each custom table is created/owned/migrated by its module (AD-1); migration (Epic 16)
  populates them via the WP-CLI package (AD-1 carve-out), never the runtime.
- Reactions (custom table) and responses (comments) are **separate** stores with
  separate counts; "342 hartjies" ≠ response count.
- Object-cache layer is a cross-cutting Kernel concern with per-module invalidation.

### AD-6: API & communication — `ink/v1` REST write path, runtime entitlement gate, facade + event + Action Scheduler comms

**Status:** Accepted (2026-06-17)

**Context.** A WordPress build needs explicit conventions for: how the front end
performs state changes, how permissions are modeled, and how the AD-1 modules talk to
each other. Left implicit, these drift into inconsistent admin-ajax handlers, ad-hoc
capability checks, and tangled cross-module calls.

**Decisions.**

1. **Front-end write path is a single REST namespace `ink/v1`.** Every state-changing
   front-end action (reaksie, volg, save-to-leeslys, post a Gemeenskapsreaksie,
   submission save/publish, challenge entry, rating) goes through an `ink/v1` endpoint
   with a uniform contract: `X-WP-Nonce` + capability check + input sanitisation +
   `WP_Error` with `ink_`-coded **Afrikaans** messages (NFR-1 — error/validation strings
   are a leak vector, so messages are i18n'd and centrally constructed). REST over
   admin-ajax (testable, typed). The Gemeenskapsreaksie endpoint calls
   `wp_insert_comment` under the hood (AD-5a). **Reads stay server-rendered** (query
   blocks / `pre_get_posts`); REST is used for reads only where genuinely interactive
   (live filters, infinite scroll).

2. **Entitlement is a runtime gate, not a WP capability.**
   - Engagement actions (react, respond, save, follow, rate) are gated by
     `is_user_logged_in()` + nonce only — **not** entitlement-gated (per FR-24–42).
   - `plaas` eligibility is evaluated at runtime via the Entitlement module
     (AD-2 `can_submit()`), **not** modeled as a `current_user_can` capability —
     subscription state is dynamic, and routing it through `current_user_can` /
     `user_has_cap` would fire a membership lookup on every capability check and split
     AD-2's single explicit gate. (Considered and rejected: a uniform dynamic
     `ink_publish` capability via the `user_has_cap` filter — cleaner conceptually,
     worse in practice.)
   - Editorial actions **are** static custom capabilities — `ink_manage_tiers`,
     `ink_manage_challenges`, `ink_manage_sponsors`, `ink_moderate` — granted to the
     `editor` role (redakteur) at activation.

3. **Inter-module communication.**
   - **Synchronous, returns a value → direct facade call** (`Tiers::promote()`,
     `Entitlement::can_submit()`).
   - **Fire-and-react cross-cutting → namespaced WP actions** (`do_action('ink/submission/published', …)`,
     `ink/tier/promoted`, etc.) so producers don't know their consumers (notifications,
     audit log, cache invalidation subscribe). These run in-request (WP actions are
     synchronous) but decouple producer from consumer.
   - **Deferred / background work → Action Scheduler** (already present, bundled with
     WooCommerce) — bulk notification fan-out, expiry sweeps, etc. No separate job queue
     is added.

**Consequences:**
- One write-path contract to test and to secure; nonce + capability + Afrikaans error
  handling are uniform, not per-endpoint reinvention.
- AD-2's entitlement gate stays the single source of truth for publish eligibility;
  capability checks stay cheap and static.
- The event surface (`ink/...` actions) is the seam notifications/audit/cache hang off;
  it must be documented as it grows (AD-1 caution against untraceable action-at-a-
  distance — events for cross-cutting reactions only, facades for everything else).
- Action Scheduler reuse keeps the dependency footprint flat (no new queue infra).

### AD-7: Frontend architecture — patterns-first, Interactivity API, server-rendered discovery with denormalized sort counts

**Status:** Accepted (2026-06-17)

**Context.** `ink-foundation` is an FSE block theme; NFR-3 wants no heavy JS where a
pattern suffices. The discovery surfaces (Ontdek) need custom sorts and diacritic-
insensitive search that core blocks don't express out of the box. Verified June 2026:
the **Interactivity API is stable 1.0 in WordPress 7.0** (released 2026-05-20), the
target platform; `utf8mb4_unicode_ci` / `_ai_ci` fold accents at the DB level.

**Decisions.**

1. **Patterns-first composition.** Block patterns + core blocks + block styles are the
   default; **custom *dynamic* blocks** (server-rendered via `render_callback` in
   `ink-core`) only where a component is both dynamic and tied to INK logic — the Skryf
   submission form, the reading-surface highlight/reaction widget, follow toggle, leeslys
   save, the Gemeenskapsreaksie form/list, reaction counts. Static/marketing surfaces are
   patterns with locked structure; listing surfaces use core **Query Loop** extended via
   `pre_get_posts` / custom query vars where it can express the query.

2. **Client interactivity = Interactivity API by default.** Line resonance, profile/Ontdek
   tabs, follow toggle, leeslys toggle, "merk alles as gelees" → `data-wp-*` directives
   backed by `ink/v1` REST (AD-6); business logic stays server-side, the client reflects
   state. Small enqueued vanilla JS only where the Interactivity API is awkward.
   **Real3D Flipbook is the known JS exception** (plugin JS, accessibility exception).

3. **Discovery (Ontdek) construction.**
   - **Skrywers tab is server-rendered** (custom block backed by `WP_User_Query` — tier
     from user-meta, genre via the writer's published works), because Query Loop cannot
     query users. The bydraes tab uses extended Query Loop.
   - **Sort-driving counts are denormalized from day one** into indexed post-meta, updated
     transactionally on reaction/read write, so "Mees geliefd" / "Meeste gelees" are cheap
     `WP_Query` orderby (a JOINed COUNT against the reactions table is expensive and
     uncacheable). **This refines AD-5**: the discovery *sort* requirement + SM-4
     (return-to-read) make these counts hot by design, so they are denormalized up front,
     not deferred to "if measured hot."
   - **"Opspraakwekkend" (trending) is a stored, recomputed score** (`ink_trending_score`)
     refreshed on an **Action Scheduler** job (AD-6), ordered via meta — not computed live.
     "Nuwe stemme" = writers by first-publish recency.
   - **Diacritic-insensitive search (FR-35) leans on DB collation, no search plugin.** Fold
     accents via `utf8mb4_unicode_ci`/`_ai_ci` + extended `WP_Query` (`posts_search` for
     works; `WP_User_Query`/usermeta/taxonomy for skrywers). Avoids SearchWP/Relevanssi
     (plugin sprawl, NFR-3) — search scope is bounded (title/theme + name/bio/genre).
     `[BUILD]`: verify the **cloned brownfield DB's actual collation**; if it does not
     accent-fold (or Afrikaans edge cases slip), fall back to a normalized accent-stripped
     index column maintained on save.

**Consequences:**
- Theme stays presentation-only (three-layer rule); all dynamic blocks render from
  `ink-core` via its module facades + `ink/v1`.
- AD-5's counts column/meta now has a firm rule: sort-driving counts are denormalized
  eagerly; non-sort counts may still be object-cached on read.
- Discovery sorts and search are testable against `WP_Query`/`WP_User_Query` without a
  third-party search dependency; the collation assumption is an explicit build-time check.

### AD-8: Infrastructure & deployment — CI-built artifact deploy, host LiteSpeed object cache, leak scan as standing gate

**Status:** Accepted (2026-06-17)

**Context.** Host stack is fixed (NameHero/LiteSpeed, Cloudflare-locked origin,
LiteSpeed + Cloudflare caching, Patchstack, staff 2FA, staging/prod). AD-4 has Composer
assembling core + plugins and `.env` per environment. Open: the CI/CD pipeline shape and
the object-cache backend, plus where the standing English-leak scan (NFR-1/NFR-8) runs.

**Decisions.**

1. **CI pipeline (GitHub Actions or equivalent — vendor secondary, stages are the
   decision).** On PR/push: `composer install` → PHPStan → PHPCS (WPCS) →
   **Deptrac/PHPArkitect** (AD-1 conflation-rule check — `Ink\Entitlement ⟂ Ink\Tiers`
   fails the build) → Pest unit → wp-env integration → Playwright E2E. Risk-based depth
   (NFR-9): full suite on PRs to `main`, smoke elsewhere. PayFast **sandbox** only in
   tests.

2. **CD / deploy = CI-built artifact, rsync to staging over SSH, gated manual promote to
   production.** Composer assembles the tree **in CI** (`composer install --no-dev`); the
   artifact is rsynced to the host. **Do not run Composer on the shared host**
   (memory/timeout fragility) and keep build tooling off the server (production hygiene).
   The deploy **syncs code only**: `wp-content/uploads` (cloned/migrated media) and the
   database are persistent host state, excluded from the sync (uploads as a symlinked
   shared dir), so deploys never clobber migrated media. The WP-CLI migration package
   (AD-1 carve-out) runs on staging via CLI, never as part of a normal deploy. The
   manual staging→prod promotion **is** the NFR-7 major-update gate. (Considered: git-pull
   + composer-on-server — rejected for shared-host fragility + hygiene.)

3. **Object cache = the host LiteSpeed stack (LSMCD, or Redis if offered) via an
   `object-cache.php` drop-in.** Logged-in pages bypass full-page cache, and AD-7's
   denormalized counts + trending + AD-6's Action Scheduler want a persistent object
   cache. Reuse host-provided cache rather than external infra. The drop-in is environment
   config, not committed business logic; invalidation stays the Kernel concern (AD-5).
   `[BUILD]`: confirm NameHero's actual LSMCD/Redis availability; fall back accordingly.

4. **English-leak scan runs in two places (NFR-1/NFR-8 standing gate).** In **CI** on PRs
   (crawl key pages on the wp-env build + `wp i18n` untranslated counts against the
   allowlist) and as a **scheduled cron on staging/prod** to catch post-ungated-update
   regressions, remediated under the SM-2 SLA.

**Consequences:**
- Environment parity: `composer.lock` pins an identical set across wp-env / CI / staging /
  prod (AD-4); secrets (PayFast live vs sandbox) are per-`.env`, sandbox in CI/staging.
- Deploys are code-only and idempotent; migrated media + DB are never in the artifact.
- Observability (NFR-8): Redirection 404 logging + Patchstack CVE alerts + the standing
  leak scan together form the operational gate set.

### AD-9: Form-letter / notification template store — WP options, name-merge only (NOT a template engine); Notifications expands to transactional email

**Status:** Accepted (2026-06-20)

**Context.** The burden-reduction scope (Sprint Change Proposal 2026-06-20, §4.E) adds
owner-authored, repeatedly-sent messages: the winners-announcement post (R2), the
promotion congratulation email (R3), the membership lifecycle emails (R5), and the
post-receipt notification incl. a randomized message list (R7). These need a single,
simple place to store editable message text — but explicitly **not** a configurable rich
template engine (owner decision §2.1: "simple form-letter text with a name-merge in the
greeting line", e.g. `Beste {skrywer}, …`).

**Decision.** Add a **lightweight form-letter / notification template store** owned by the
**Notifications** module. Implementation:

- **WP `options`-based** stored text per template/event (the `ink_` prefix), with
  per-event **send on/off toggles** and the **randomized message list** for R7. No new
  custom table — these are low-volume, admin-edited config values, a natural fit for the
  options API.
- **Name-merge placeholder ONLY** — a single greeting-line merge token (e.g. `{skrywer}`),
  resolved at send time. **Not** Twig/Blade/Mustache, not conditionals, not loops, not a
  WYSIWYG template builder. The deliberate constraint is the decision; anything richer is
  out of scope.
- **Notifications expands from BP-only to transactional email.** It was the BuddyPress
  kennisgewing subscriber (AD-5/AD-6); it now also composes and dispatches transactional
  email via `wp_mail` (Woo/BP templates remain the platform's). It stays a **downstream
  event consumer** (AD-6 `ink/...` events + Action Scheduler fan-out), not a cross-domain
  write path — see the conflation note in AD-3/Consequences below.
- **Consumed by:** R2 (winners post body), R3 (congratulation email), R5 (lifecycle
  emails), R7 (receipt messages + randomized list). One store, four consumers.

**Consequences:**
- **(NFR-1 leak-scan extension — item 6.)** Admin-authored template text is a **new
  Afrikaans-leak vector not covered today**: the build-time `.mo` + page-crawl scan (AD-8)
  sees neither the options store nor an unsent email. The standing leak-scan is therefore
  **extended to cover the template/options store** — either scan the stored option values
  on the staging/prod cron, **or** enforce Afrikaans at the admin authoring boundary (or
  both). This is now part of the NFR-1 standing gate, not a one-time check.
- **No `.docx` / PhpWord parser — explicitly OUT OF SCOPE (item 7, owner decision §2.1).**
  Results ingestion (R2) is **paste-text only**: the user pastes plain text. The PhpWord /
  PhpOffice Composer dependency, and with it the entire untrusted-ZIP / **XXE / zip-bomb**
  attack surface and its security NFR, are **removed from scope and must not be added**.
  This note exists so no one reintroduces a document parser "for convenience."
- **POPIA (OQ-3) may need addressing sooner (item 10).** R8 analytics + R5 transactional
  emails introduce personal-data processing surfaces that sharpen the deferred POPIA
  question; flagged here as a posture item that may move ahead of its prior deferral.

### AD-3 / AD-9 addendum — integration points & conflation confirmation (2026-06-20)

The burden-reduction services attach to existing modules (respecting the ~8–12 module cap,
AD-1) and introduce these integration points:

- **WC Memberships lifecycle → Action Scheduler (already bundled, AD-6).** Activation and
  the 1-month / 1-week pre-expiry sweeps ride Action Scheduler for the R5 lifecycle emails.
  The **Entitlement module gains a notify responsibility** (it emits the lifecycle events
  Notifications consumes) but the **publish gate stays end-date-based (AD-2)** — the notify
  path does not change `can_submit()` semantics.
- **PayFast recurring stays a documented SEAM, NOT built** (deferred post-launch, §2.1).
  The renewal-discount carve-out rides the same deferral.
- **Analytics provider (R8) and social-login (R6) are new vetted-plugin seams** integrated
  via hooks, not `ink-core` code (R8 → Discovery/Epic 18, R6 → Accounts/Epic 3 + an
  anti-spam research spike). **Private-profile read counts reuse the already-denormalized
  `_ink_read_count`** (AD-7) — no new counter.
- **THE conflation rule (AD-1) holds and the new services must pass Deptrac (AD-8):** the
  R3 promotion/threshold engine in **`Ink\Tiers` must not reference `Ink\Entitlement`**; the
  R5 membership lifecycle logic in **`Ink\Entitlement` must not reference `Ink\Tiers`**;
  **Notifications is the only shared downstream event consumer** (it subscribes to
  `ink/...` events from both domains — it is not a cross-domain *write* path). New code
  fails the build on the first illicit `use` exactly as today.

### AD-10: Terminology label registry — single-source, glossary-backed UI labels

**Status:** Accepted (2026-06-21)

**Context.** `docs/afrikaans-terms.md` is the documented source of truth for INK's
controlled vocabulary, with a binding rule: a concept is added to the glossary before it
appears in code or UI. But the glossary is human-readable prose, hand-duplicated into code
as scattered string literals. The architecture already enforces single-definition for
**code identifiers** — enums for tiers/reactions/response-types and fixed CPT/taxonomy
slugs (AD-1; never duplicate those literals across the codebase). It does **not** do the
same for **UI display labels** — the Afrikaans words a member actually reads. Under the
decided i18n strategy (gettext with Afrikaans as the *source* string, **no English `.mo`**
for `ink-core`), each displayed label is the gettext source literal at its call site, so
there is no single place to re-decide a term's label. The 2026-06-20 G1 terminology change
(e.g. `intekening → lidmaatskap`, `tier → Gradering`) showed this is a recurring,
owner-driven event, and Epic 2 is about to register the largest label surface on the site.

**Decision.** `ink-core` exposes a **glossary-backed, single-source label registry** for all
code-rendered UI labels, extending the AD-1 enum/code-ID single-source discipline from code
identifiers to display labels.

- A registry (e.g. `Ink\I18n\Terms`) maps glossary concept keys to **literal**
  `__( '<Afrikaans>', 'ink-core' )` label definitions, seeded from the `afrikaans-terms.md`
  UI-term column; a helper (`ink_term('key')` / `Terms::label('key')`) returns the label and
  callers never inline the literal. Re-deciding a term is a one-file edit.
- **gettext-compatible:** because the registry holds *literal* `__()` calls, `wp i18n
  make-pot` still extracts every label — the registry file *is* the extraction surface. The
  literals-only caveat is load-bearing: never wrap `__()` around a *variable* (`__( $label )`),
  which `make-pot` cannot extract; literals live only in the registry, everywhere else calls
  the key.
- **Complements, does not replace, the existing single-source rules.** Code IDs, slugs, and
  enums remain the enum/constant single-source (AD-1); the **no-English-`.mo`** policy is
  unchanged (gettext returns the Afrikaans source). The registry is the label analogue of
  the enum rule.
- **Theme bridge + Block Bindings.** The theme cannot call `ink-core` PHP from static
  block-template HTML; a small theme-side bridge exposes the same labels to PHP patterns, and
  the **Block Bindings API** (WP 6.5+) is the targeted bridge for static `templates/*.html`
  where a dynamic term is needed (adopt only where needed, not a blanket binding).
- **Out of scope.** DB content (page bodies, nav menus, migrated posts) is not covered; term
  changes there remain a `wp search-replace` operation, called out explicitly so it is not
  assumed covered by the registry.

**Consequences:**
- The registry is a deterministic surface for the NFR-1 English-leak scan (AD-8) and label QA
  — it joins the inspected set alongside the AD-9 template/options store.
- The glossary stays the human source of truth; the registry is its machine projection. A
  term change is made once in `afrikaans-terms.md`, then reflected in the registry (Story 2.0).

---

## Starter Template Evaluation

### Primary Technology Domain

New-codebase **WordPress** (WP 7.0+ / PHP 8.3+) over cloned data: a custom FSE block
theme (`ink-foundation`) + a custom business-logic plugin (`ink-core`). The production
DB + `wp-content/uploads` media are cloned and must survive; the code and webroot are
built fresh. No greenfield web/full-stack starter applies; the stack is fixed by the
PRD + project-context and the three-layer rule.

### Starter Options Considered

- **`wp scaffold plugin` (WP-CLI)** — rejected as the `ink-core` base: flat,
  procedural, no Composer/PSR-4/strict-types, no module layout. Conflicts with AD-1.
- **`@wordpress/create-block`** — block-bundle focused; not a fit for INK's
  business logic. Retain only if a bespoke custom block is later required.
- **Third-party plugin boilerplates (WPPB / Underpin / etc.)** — opinionated,
  largely pre-PSR-4 conventions; net negative against AD-1's module structure.
- **Create Block Theme (official WP plugin)** — selected for `ink-foundation`.
- **Hand-rolled Composer/PSR-4 scaffold** — selected for `ink-core`.

### Selected Approach: composed foundation (no monolithic starter)

**Rationale.** Brownfield-data + three-layer separation + the AD-1 modular monolith
mean no off-the-shelf starter models the required structure. Composing current
official tools (verified June 2026) gives best-practice scaffolding without fighting a
boilerplate's opinions.

1. **`ink-core` — hand-rolled Composer/PSR-4 plugin scaffold.**
   - Composer autoload `Ink\` → `src/`; `declare(strict_types=1)`; module
     directories per AD-1, each with its own bootstrap + public `Api` facade.
   - Quality gates: **szepeviktor/phpstan-wordpress v2.0.3** (+ wordpress-stubs),
     **WPCS 3.3.0** via PHP_CodeSniffer, and a dependency-rule checker
     (**Deptrac / PHPArkitect**) enforcing the AD-1 graph — incl. `Ink\Entitlement ⟂
     Ink\Tiers` (THE conflation rule as a CI invariant, FR-13).
   - Maps to feature 1.7 (`ink-core` scaffold).

2. **`ink-foundation` — Create Block Theme (official plugin).**
   - Generate the boilerplate "empty" block theme, then populate `theme.json`
     **from `docs/design-handoff/tokens/theme-tokens.json`** per `token-map.md`
     (NFR-2 — tokens canonical; light mode only at v1). Templates/patterns authored
     in the Site Editor and exported via the plugin; block locking on critical
     editorial structure (1.6).
   - Maps to features 1.1–1.5, 1.10 (theme half of i18n scaffolding).

3. **Local env + integration harness — `@wordpress/env` (wp-env 11.8.0).**
   - Docker-based local WordPress; bundles WP's PHPUnit test files. Doubles as the
     **integration test harness** (NFR-9). One `.wp-env.json` mounts `ink-core` +
     `ink-foundation` + the vetted platform plugins.

4. **Test stack (NFR-9 pyramid; feature 1.11 scaffold → 18.8 buildout).**
   - **Unit:** Pest/PHPUnit + Brain Monkey / WP_Mock (WP fully mocked) — tier
     promotion, entitlement gate, follow graph, sponsor scheduling.
   - **Integration:** wp-env + WP test library (or **lucatume/wp-browser**) for the
     load-bearing seams (active⇒submit, expired⇒denied, tier⇒meta+log).
   - **E2E:** Playwright + `@wordpress/e2e-test-utils-playwright` for the critical
     journey (register → PayFast **sandbox** → submit → publish → react → renew).
   - CI wires PHPStan + WPCS + Deptrac + Pest from the start (test-first, not
     retrofitted).

### Site Build Topology & Dependency Management

Per **AD-4**: Composer-managed WordPress with the **standard `wp-content` layout**
(not Bedrock). Composer + `composer/installers` + wpackagist install core + free
plugins into conventional paths; premium plugins via a private Composer source or
committed; `.env` for secrets; `/wp-content/uploads/` URLs preserved for migrated
media (NFR-4); pinned `composer.lock` gives a reproducible set across
wp-env / staging / prod / CI (NFR-7). `ink-core` and `ink-foundation` are the only
committed code artifacts.

**Note:** Project initialization — scaffold the `ink-core` Composer/PSR-4 layout,
generate `ink-foundation` via Create Block Theme, author `theme.json` from the token
file, write `composer.json` (standard-layout install paths) + `.wp-env.json`, and
stand up CI (PHPStan/WPCS/Deptrac/Pest) — is the first implementation story
(Epic 1: 1.7, 1.10, 1.11).

---

## Core Architectural Decisions

The decisions below were made collaboratively and are recorded in full as **AD-1…AD-10**
under *Architecture Decisions* above (AD-9 + the integration/conflation addendum added by
the 2026-06-20 burden-reduction scope change; AD-10 added by the 2026-06-21 terminology
correct-course). This section indexes them by category and flags priority and open
build-time items.

### Decision Priority Analysis

**Critical (block implementation):**
- **AD-1** — `ink-core` as a modular monolith with an enforced dependency graph
  (conflation rule = a CI dependency invariant). Migration carved out to a WP-CLI package.
- **AD-2** — Entitlement gate evaluates the membership end date in SAST at the publish
  moment (cron-independent).
- **AD-3** — Challenge entry delegated to the Challenges module; tier captured at entry;
  judged on a deadline-bounded snapshot; promotions only via the Tiers API.
- **AD-4** — Composer-managed WordPress, standard `wp-content` layout (not Bedrock).
- **AD-5 / AD-5a** — Persistence model: WP-native for content/classification/identity,
  custom tables for relational engagement/competition data, BuddyPress store for
  notifications; Gemeenskapsreaksies reuse the comment infrastructure.

**Important (shape architecture):**
- **AD-6** — `ink/v1` REST write path; entitlement as a runtime gate (not a capability);
  facade + namespaced-event + Action Scheduler inter-module comms.
- **AD-7** — Patterns-first FSE; Interactivity API; server-rendered discovery with
  denormalized sort counts; collation-based diacritic search (no search plugin).
- **AD-8** — CI-built artifact deploy (no composer-on-host); host LiteSpeed object cache;
  English-leak scan as a standing CI + cron gate.
- **AD-9** — Form-letter/notification template store (WP options, name-merge only — not a
  template engine), owned by Notifications (now also transactional email); leak-scan
  extended to the template store; `.docx`/PhpWord parser explicitly out of scope (paste-only);
  R5/R7/R8/R6 integration seams; conflation rule reconfirmed for the new services.

### Decisions by Category

- **Data architecture:** AD-5, AD-5a (storage model, comment-infra responses incl.
  `ink_moderator_terugvoer`); refined by AD-7 (eager denormalization of sort-driving counts)
  and AD-3 (inskrywing table + EntryID collation columns); AD-9 (form-letter options store).
- **Authentication & security:** AD-6 (runtime entitlement gate, static editorial caps,
  nonce/capability on every `ink/v1` write); platform-fixed: WP-native auth, PayFast
  off-site, Cloudflare-locked origin + 2FA + Patchstack.
- **API & communication:** AD-6 (`ink/v1`, facades, `ink/...` events, Action Scheduler).
- **Frontend architecture:** AD-7 (patterns/custom-block split, Interactivity API,
  discovery construction, diacritic search).
- **Infrastructure & deployment:** AD-8 (CI/CD, object cache, leak-scan placement — extended
  to the AD-9 template store); AD-4 (build topology, `.env`, dependency pinning).
- **Notifications & messaging:** AD-9 (form-letter/template options store, name-merge only,
  BP + transactional email; consumed by R2/R3/R5/R7); integration-point/conflation addendum
  (Action Scheduler lifecycle sweeps, PayFast-recurring seam, R8/R6 plugin seams).
- **Terminology & i18n labels:** AD-10 (glossary-backed single-source label registry; literal __(); complements the AD-5 enum/code-ID rule and the no-English-.mo policy).

### Open Build-Time Items (carried, non-blocking)

- `[BUILD]` Premium-plugin Composer source — private repo vs commit (AD-4).
- `[BUILD]` Challenge freeze mechanism — deadline-bounded revision vs snapshot (AD-3, OQ-18).
- `[BUILD]` Entry-path concurrency/idempotency (AD-3, EC-M4 / OQ-18).
- `[BUILD]` Cloned-DB collation verification for diacritic search; normalized-index
  fallback (AD-7).
- `[BUILD]` NameHero LSMCD/Redis availability confirmation (AD-8).

### Cross-Component Dependencies

The "capture-at-the-moment" pattern unifies AD-2 (entitlement at publish) and AD-3 (tier
at entry). The AD-1 module graph is enforced in CI by AD-8's pipeline. AD-5's counts feed
AD-7's discovery sorts; AD-6's events drive notifications and AD-5's cache invalidation;
AD-4's dependency pinning underpins AD-8's reproducible deploys.

---

## Implementation Patterns & Consistency Rules

Conventions every AI build agent MUST follow so independently-built modules stay
compatible. Many are already binding from `project-context.md` and AD-1…AD-9; this
section consolidates them and pins the gaps where agents could otherwise diverge.

### Carried-forward MUSTs (from project-context + ADs)

- Prefix `ink_` / `Ink\` on everything (functions, hooks, options, meta, CPT/taxonomy IDs).
- Escape-on-output + sanitise-on-input + **nonce on every state change**; `$wpdb->prepare()`
  always.
- i18n on every user-facing string; `_n()` for all counts; **`ink-core` ships no English
  `.mo`** (gettext returns the Afrikaans source).
- WPCS (tabs, Yoda where linted); `declare(strict_types=1)` in all `ink-core` PHP.
- Enums for fixed value sets; the persisted DB value is the lowercase Afrikaans string.
- REST namespace `ink/v1` (AD-6); events `ink/{module}/{event}` (AD-6); persistence per AD-5;
  UTC-store / SAST-present via the single boundary helper (AD-2/AD-3).

### Naming Patterns

- **Custom tables:** `{$wpdb->prefix}ink_{plural}` → `ink_follows`, `ink_tier_history`,
  `ink_line_reactions`, `ink_entries`, `ink_ratings`, `ink_reading_list`. snake_case
  columns; PK `id`; FKs `user_id` / `followee_id`; UTC `created_at`; indexes `{table}_{cols}`.
- **Meta keys:** hidden/internal meta = leading underscore + prefix (`_ink_hartjie_count`,
  `_ink_read_count`, `_ink_trending_score`); public-ish meta = `ink_` without underscore.
- **PHP code:** `Ink\{Module}\...`; classes **PascalCase**; methods **camelCase**; enum cases
  PascalCase backed by lowercase Afrikaans value (`Tier::Brons => 'brons'`). `ink_`
  snake_case is reserved for the **global/procedural WP surface only** — hooks, template
  tags, global functions. The WPCS ruleset is configured to exempt class methods from its
  snake_case rule. (Decided 2026-06-17; builder deferred to the standard PSR-4 convention.)
- **REST routes use Afrikaans domain nouns** (one vocabulary end-to-end with the glossary /
  CPT slugs / enums): `/ink/v1/volg`, `/ink/v1/leeslys`, `/ink/v1/reaksie`,
  `/ink/v1/gemeenskapsreaksie`, `/ink/v1/inskrywing`. Routes are internal API, not
  user-facing copy, but glossary consistency outweighs REST-English convention. (Decided
  2026-06-17.)

### Structure Patterns

- **Canonical module skeleton** — every module is identical: `src/{Module}/Module.php`
  (bootstrap + hook registration) · `Api.php` (the *only* public facade) · `Rest/` ·
  `Repository/` (custom-table access) · `Entity/` or value objects. Shared enums + value
  objects + cache + schema live in `Kernel`.
- **Tests:** top-level `tests/Unit/{Module}`, `tests/Integration/{Module}`, `tests/e2e/`
  (Playwright), mirroring `src/` — not co-located.
- **Theme:** standard FSE (`templates/`, `parts/`, `patterns/`, `styles/`, `theme.json`);
  pattern slugs `ink-foundation/{slug}`.

### Format Patterns

- **No custom response envelope** — return the resource / `WP_REST_Response`. JSON fields
  **snake_case** (matches WP core REST).
- **Dates:** persist UTC `DATETIME`; emit ISO-8601; display SAST via the boundary helper.

### Communication Patterns

- **Events** are past-tense (`ink/submission/published`, `ink/tier/promoted`); payload =
  primary ID + documented keys; used for **cross-cutting reactions only** (notifications,
  audit, cache invalidation), never as a substitute for a facade call.
- **Three-tier permission check:** editorial → `current_user_can('ink_{cap}')`; engagement →
  `is_user_logged_in()` + nonce; publish eligibility → `Entitlement::can_submit()` (never a
  capability) — per AD-6.

### Process Patterns — Error Handling

- **Inside a module** (Repository → service → `Api` facade): use **typed exceptions** for
  failure paths. Keeps domain logic free of WordPress types and easy to unit-test (NFR-9).
- **At every boundary where WordPress is the caller** (REST callbacks, form handlers,
  inspected hook/filter callbacks, `ink/...` event subscribers): **never let a raw exception
  escape.** The boundary is a translation layer — catch the module's typed exceptions and
  return a `WP_Error('ink_{module}_{reason}', __('Afrikaans message','ink-core'),
  ['status'=>4xx])`. Event subscribers catch-and-log.
- **Rationale:** an uncaught exception in a REST callback becomes a 500 with a leaked stack
  trace and an English fatal message — both a UX failure and an NFR-1 (Afrikaans/no-leakage)
  violation. A returned `WP_Error` becomes a clean JSON error with the right status and an
  Afrikaans message. Conversely, `WP_Error` everywhere internally would force `is_wp_error()`
  checks on every call and couple domain logic to WP.

```php
// Inside the module — throws typed exceptions
public function enter( WP_Post $post, int $round, int $user ): Entry {
    if ( $this->round->isClosed( $round ) ) {
        throw new ChallengeClosedException();
    }
    // ...
}

// At the REST boundary — catches them, returns WP_Error
public function create( WP_REST_Request $req ): WP_REST_Response|WP_Error {
    try {
        $entry = $this->challenges->enter( $post, $round, $user );
    } catch ( ChallengeClosedException ) {
        return new WP_Error( 'ink_challenge_closed',
            __( 'Hierdie uitdaging is reeds gesluit.', 'ink-core' ), [ 'status' => 403 ] );
    } catch ( EntryLimitReachedException ) {
        return new WP_Error( 'ink_entry_limit',
            __( 'Jy het reeds die maksimum inskrywings vir hierdie tipe ingedien.', 'ink-core' ),
            [ 'status' => 422 ] );
    }
    return rest_ensure_response( $entry->toArray() );
}
```

### Process Patterns — Validation & States

- **Validation timing:** sanitise at the input boundary → validate via value objects/enums →
  escape at output. Nonce + the three-tier permission check precede every state change.
- **UI states** use the documented Afrikaans empty/success/error/expired copy from
  `EXPERIENCE.md`; loading = a skeleton matching the target layout.

### Enforcement

- **All agents MUST:** follow the module skeleton; route cross-module calls through `Api`
  facades only; signal failure with typed exceptions internally and `WP_Error` at WP
  boundaries; author all strings in Afrikaans via the `ink-core`/`ink-foundation` text
  domains.
- **Verified in CI (AD-8):** PHPStan + WPCS (method-name exemption configured) + Deptrac/
  PHPArkitect (module-graph + `Entitlement ⟂ Tiers`) + Pest + the English-leak scan.

---

## Project Structure & Boundaries

### Repository Layout (committed vs. composer-managed)

Per AD-4 (Composer-managed standard `wp-content` layout): WP core and third-party plugins
are fetched by Composer and **git-ignored**; only first-party code, config, tests, committed
translations, and the migration package are committed.

```
ink-vibe/
├── composer.json              # assembles WP core + plugins (wpackagist) into standard
│                              #   wp-content paths; PSR-4 Ink\ → ink-core/src
├── composer.lock              # pinned set (AD-4 / NFR-7)
├── .wp-env.json               # local env: mounts ink-core, ink-foundation, platform plugins
├── .env.example               # PayFast (sandbox), DB, flags — real .env per env, NOT committed
├── phpstan.neon               # szepeviktor/phpstan-wordpress v2.0.3
├── phpcs.xml                  # WPCS 3.3.0 (class-method snake_case exemption configured)
├── deptrac.yaml               # AD-1 module graph + Entitlement ⟂ Tiers
├── phpunit.xml / Pest         # unit + integration config
├── playwright.config.ts       # E2E
├── .github/workflows/ci.yml   # AD-8 pipeline
├── tools/
│   └── leak-scan/             # English-leak scan (NFR-1/8): page crawl + wp i18n counts
├── packages/
│   └── ink-migrate/           # AD-1 carve-out: WP-CLI migration commands; staging-only,
│       └── src/Commands/      #   excluded from the production artifact
├── wp-content/
│   ├── plugins/
│   │   ├── ink-core/          # COMMITTED (first-party)
│   │   └── {woocommerce,woocommerce-memberships,buddypress,...}/   # composer, git-ignored
│   ├── themes/
│   │   └── ink-foundation/    # COMMITTED (first-party)
│   ├── languages/             # COMMITTED .po/.mo for surviving third-party plugins
│   └── uploads/               # host state — git-ignored, excluded from deploy (cloned media)
└── tests/
    ├── Unit/{Module}/         # Pest + Brain Monkey / WP_Mock
    ├── Integration/{Module}/  # wp-env + WP test lib / wp-browser
    └── e2e/                   # Playwright + @wordpress/e2e-test-utils-playwright
```

### `ink-core` — module tree (AD-1 graph + Step 5 skeleton)

```
ink-core/
├── ink-core.php               # bootstrap: load Kernel, then each module's Module.php
├── composer.json              # PSR-4 Ink\ → src/
└── src/
    ├── Kernel/                # depends on nothing; everything depends on it
    │   ├── Enum/              # Tier (Brons|Silwer|Goud|Meester), ReactionType, ResponseType, SponsorTier
    │   ├── Schema/            # custom-table registry + dbDelta
    │   ├── Cache/             # object-cache facade + per-module invalidation (AD-5/AD-8)
    │   ├── Time/              # SAST boundary helper (AD-2/AD-3)
    │   └── Capabilities.php   # ink_manage_tiers/challenges/sponsors/moderate
    ├── Content/               # Epic 2 — CPTs, taxonomies, meta
    ├── Entitlement/           # Epic 4 logic — WC Memberships seam, can_submit()   ⟂ Tiers
    ├── Tiers/                 # Epic 5 — ster gradering (Brons|Silwer|Goud|Meester), promotion log, win-count engine (5/15), Tiers::promote() (sole write path)  ⟂ Entitlement
    ├── Submission/            # Epic 6 — Skryf dynamic block, draft/publish, gate call
    ├── Engagement/            # Epic 7 — highlights+reactions, Gemeenskapsreaksies (comments), leeslys, counts
    ├── Social/                # Epic 9 — follow graph, following-feed, pinned works, ratings, BP glue
    ├── Challenges/            # Epic 12 — entry, pools, placements, EntryID collation, R1 judge-email composer, R2 paste-text results parser + winners post, winner→promotion (via Tiers Api)
    ├── Discovery/             # Epic 8 + 11.2/11.4 — search, faceted queries, cross-surfacing, trending job
    ├── Sponsors/              # Epic 14 — borg scheduling/rotation
    ├── Notifications/         # Epic 9.9 — BP notification types + ink/... event subscribers; form-letter/template options store + transactional email (AD-9)
    └── Forms/                 # 15.4 + 18.4 — contact + report forms
        # each module: Module.php (bootstrap) · Api.php (sole facade) · Rest/ · Repository/ · Entity/
```

### `ink-foundation` — FSE theme tree (presentation only)

```
ink-foundation/
├── style.css · theme.json     # theme.json tokens from design-handoff/tokens (NFR-2)
├── functions.php              # enqueue, register patterns/block styles ONLY — no business logic
├── templates/                 # front-page, single-{storie,gedig,uitdaging}, single-skrywer,
│                              #   archive-*, page-{ontdek,opleiding,biblioteek,lidmaatskap,skryf,...}
├── parts/                     # header, footer, section shells
├── patterns/                  # ink-foundation/{slug}: hero, featured grid, CTA, profile summary,
│                              #   pricing table, archetypes A–D (locked structure)
├── styles/                    # block style variations
└── assets/                    # Interactivity API view scripts; Lora + Inter fonts
```

### Architectural Boundaries

- **Three-layer:** theme = presentation; `ink-core` = INK logic + models; platform plugins =
  commodity, integrated via hooks only.
- **Module graph (AD-1):** Kernel → Content → {Entitlement, Tiers, Engagement, Social,
  Sponsors, Discovery} → {Submission, Challenges, Notifications, Forms}. Cross-module calls
  go through `Api` facades only; `Entitlement ⟂ Tiers` enforced by Deptrac.
- **REST boundary:** `ink/v1` with Afrikaans nouns (`/volg`, `/leeslys`, `/reaksie`,
  `/gemeenskapsreaksie`, `/inskrywing`); `WP_Error` translation at the callback (Step 5).
- **Data boundary:** each module owns its custom tables/meta; Kernel owns the schema registry
  + object cache; migration populates tables via `ink-migrate` (never the runtime).
- **Event boundary:** `ink/{module}/{event}` for cross-cutting reactions; Action Scheduler for
  deferred work.

### Epic → Location Mapping

| Epic | Primary location |
|---|---|
| 1 Foundation | repo-root config · `ink-core/src/Kernel` · `ink-foundation` scaffold · `tests/` · CI |
| 2 Content models | `ink-core/src/Content` |
| 3 Accounts & auth | WP-native auth · `ink-foundation/templates` (login/register) · `ink-core` (first-action prompt) |
| 4 Membership & payment | `ink-core/src/Entitlement` · WooCommerce+Memberships+PayFast (platform) · `page-lidmaatskap` |
| 5 Writer tiers | `ink-core/src/Tiers` |
| 6 Submission | `ink-core/src/Submission` (+ Skryf dynamic block) · `page-skryf` |
| 7 Reading & engagement | `ink-core/src/Engagement` · `single-storie` / `single-gedig` |
| 8 Discovery | `ink-core/src/Discovery` · `page-ontdek` |
| 9 Community & social | `ink-core/src/Social` + `Notifications` (BP + transactional email + form-letter store, AD-9) · BuddyPress (platform) · profile templates |
| 10 Library | `ink-core/src/Content` (biblioteek_item) · `archive/single-biblioteek` |
| 11 Training | `ink-core/src/Content` (opleiding_artikel) + `Discovery` cross-surfacing · `page-opleiding` |
| 12 Challenges | `ink-core/src/Challenges` (entry, pools, placements, EntryID collation, R1 judge-email composer, R2 paste-text results parser → winners post) · `Tiers` (R3 auto-promotion) · uitdaging templates |
| 13 InkPols | `ink-core/src/Content` (inkpols_uitgawe) · Real3D (platform) · inkpols templates |
| 14 Sponsors | `ink-core/src/Sponsors` · homepage strip |
| 15 Org pages & contact | `ink-foundation/templates` · `ink-core/src/Forms` (kontak) |
| 16 Migration & redirects | `packages/ink-migrate` (WP-CLI, staging-only) |
| 17 Afrikaans & localisation | cross-cutting: `ink-core` (no English `.mo`) · theme copy · `wp-content/languages` · `tools/leak-scan` |
| 18 SEO/security/perf | platform-plugin config (Rank Math/Redirection/LiteSpeed/Patchstack) · `ink-core/src/Forms` (report) · `tests` (18.8) · `tools/leak-scan` · CI |

### Data Flow (representative — challenge entry, AD-3)

`page-skryf` (theme) → Submission dynamic block → `POST /ink/v1/inskrywing` →
`Submission\Api` checks `Entitlement::can_submit()` (AD-2) → delegates to
`Challenges\Api::enter()` (open-window + cap + tier snapshot via `Tiers` read + atomic
write) → fires `ink/challenge/entered` → `Notifications` subscriber emits a BP kennisgewing.

---

## Architecture Validation Results

### Coherence Validation ✅

**Decision Compatibility:** AD-1…AD-9 are mutually consistent. Tensions explicitly
reconciled: AD-5's "denormalize only if hot" is superseded for sort-driving counts by AD-7
(eager denormalization); AD-5a's comment-infrastructure reuse — for **both** `ink_reaksie`
**and** the new `ink_moderator_terugvoer` (R2) — is reconciled with the §13 "no WP comments"
non-goal (storage substrate vs free-form UX; default comments stay disabled; both types are
programmatic, not open commenting), and the moderator type is the sanctioned exception to
the "Gemeenskapsreaksie is the ONLY feedback path" rule; AD-9's transactional-email template
store is reconciled with NFR-1 via the extended leak-scan. No contradictory decisions remain.

**Pattern Consistency:** Step 5 patterns support the decisions — `ink/v1` Afrikaans-noun
routes, typed-exception-internal / `WP_Error`-at-boundary, three-tier permission check, and
the canonical module skeleton all align with AD-1/AD-6.

**Structure Alignment:** The Step 6 tree realizes the AD-1 module graph, AD-4 topology, and
AD-5 persistence; boundaries (three-layer, facades, REST, data, events) are reflected in
directories and enforced in CI (Deptrac).

### Requirements Coverage Validation ✅

**Epic Coverage:** All 19 epics mapped to specific locations (see Epic → Location Mapping; the 2026-06-20 epics 12A/12B fold into the Challenges/Tiers rows).

**Functional Requirements:** FR clusters have architectural homes — entitlement (AD-2),
tiers incl. auto-promotion engine + Meester + win-count (AD-3 dec.6/AD-5), submission/
challenges incl. EntryID collation + winners (AD-3/AD-5), engagement incl. moderator
feedback (AD-5/AD-5a), discovery + analytics/read-count (AD-7), social/notifications +
form-letter store (AD-5/AD-6/AD-9). THE conflation rule is triple-enforced (AD-1 Deptrac +
AD-2 + AD-6) and reconfirmed for the new R3/R5 services (AD-3/AD-9 addendum).

**Non-Functional Requirements:** NFR-1 (AD-8 leak scan — now extended to the AD-9
form-letter/template options store — + no English `.mo` + Afrikaans `WP_Error`/routes),
NFR-2 (AD-7 + theme.json tokens), NFR-3 (AD-8 object cache + LiteSpeed/
Cloudflare + patterns-first), NFR-4 (AD-4 standard layout + Rank Math/Redirection + Epic 16
301s), NFR-5 (theme readability floor), NFR-6 (block locking + patterns-first), NFR-7 (AD-4
pinning + AD-8 staging gate + committed `.mo`), NFR-8 (AD-8 observability set), NFR-9
(Step 3 stack + AD-8 CI).

### Implementation Readiness Validation ✅

**Decision Completeness:** AD-1…AD-9 documented; tool versions verified June 2026.
**Structure Completeness:** Complete repo + `ink-core` + `ink-foundation` trees; boundaries
and integration points specified.
**Pattern Completeness:** Naming, structure, format, communication, and process (error
handling, validation, states) patterns defined with examples and CI enforcement.

### Gap Analysis Results

**Critical:** none — nothing blocks starting Epic 1.

**Important (architecturally patterned; specifics set at build):**
- Front-end submission media upload (FR-20/21) — WP media library via `ink/v1` + nonce +
  capability; mime/size limits at build (overlaps OQ-18 H-2).
- Transactional-email Afrikaans-ization (FR-9/NFR-1) — rides plugin email templates +
  committed `.mo` + leak scan; executed in Epic 17 (Woo order/renewal/expiry, BP, core reset).
- Public-form spam / rate-limit / deliverability (OQ-18 H-2).
- LiteSpeed full-page-cache exclusions for personalized surfaces (following-feed, leeslys,
  kennisgewings) — config alongside the AD-8 object cache.

**Carried build-time items (from ADs):** premium-plugin Composer source (AD-4); challenge
freeze mechanism (AD-3); entry concurrency/idempotency (AD-3/EC-M4); cloned-DB collation
verification + normalized-index fallback (AD-7); LSMCD/Redis availability (AD-8); PayFast
ITN idempotency (EC-M5). All tracked under OQ-18.

**Deferred (founder/legal — clean seams left):** account deletion/lapse fan-out (OQ-17);
POPIA posture (OQ-3) — **may need addressing sooner** given the 2026-06-20 scope's R8
analytics + R5 transactional emails (new personal-data surfaces; AD-9); auto-renew/PayFast
recurring (OQ-9) — remains a documented seam, not built (R5/recurring deferred post-launch);
library organisation (OQ-6); conversion target (OQ-5); UJ validation (OQ-11); copy quality
(OQ-12).

**Delegated:** migration sequencing detail remains authoritative in `migration-plan.md`
(architecture fixed only *where* migration lives — AD-1 carve-out).

### Architecture Completeness Checklist

**Requirements Analysis**
- [x] Project context thoroughly analyzed
- [x] Scale and complexity assessed
- [x] Technical constraints identified
- [x] Cross-cutting concerns mapped

**Architectural Decisions**
- [x] Critical decisions documented with versions
- [x] Technology stack fully specified
- [x] Integration patterns defined
- [x] Performance considerations addressed

**Implementation Patterns**
- [x] Naming conventions established
- [x] Structure patterns defined
- [x] Communication patterns specified
- [x] Process patterns documented

**Project Structure**
- [x] Complete directory structure defined
- [x] Component boundaries established
- [x] Integration points mapped
- [x] Requirements to structure mapping complete

### Architecture Readiness Assessment

**Overall Status:** READY FOR IMPLEMENTATION — all 16 checklist items confirmed, no critical
gaps. The important/build-time/deferred items above are implementation-phase decisions with
clean architectural seams, not missing architecture, and are tracked under OQ-16/17/18.

**Confidence Level:** High.

**Key Strengths:**
- THE conflation rule promoted to a CI-enforced architectural invariant (Deptrac), not just
  a runtime test.
- A unifying "capture-at-the-moment" pattern (entitlement at publish, tier at entry) that is
  cron-independent and deterministic.
- A modular monolith with an enforced dependency graph — blast radius contained without a
  fragile plugin fleet.
- Brownfield-preserving topology (AD-4) that keeps migrated media URLs intact.

**Areas for Future Enhancement:** dark mode (deferred v1); auto-renew; personalized discovery
surfaces (P2); richer placement surfacing; formal WCAG conformance beyond the v1 floor.

### Implementation Handoff

**AI Agent Guidelines:**
- Follow AD-1…AD-9 and the Implementation Patterns exactly.
- Route cross-module calls through `Api` facades only; signal failure with typed exceptions
  internally and `WP_Error` at WP boundaries.
- Respect the three-layer separation and the module dependency graph (CI-enforced).
- Treat this document as authoritative; defer migration detail to `migration-plan.md`.

**First Implementation Priority:** Epic 1 — scaffold the `ink-core` Composer/PSR-4 layout,
generate `ink-foundation` via Create Block Theme, author `theme.json` from the token file,
write `composer.json` + `.wp-env.json`, and stand up CI (PHPStan/WPCS/Deptrac/Pest).
