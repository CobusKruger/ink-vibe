# Reconciliation: INK PRD ↔ Consolidated Spec

**Date:** 2026-06-15
**Inputs reconciled:**
- `prd.md` (719 lines, with recent edits: SM-8/SM-C4, FR-50 widening, FR-12 any-direction, §14 replacement-parity reframe, new challenge rules, engagement-not-gated)
- `addendum.md` (technical depth companion)
- `docs/specs/ink-consolidated-spec.md` (full narrative spec, dated 2026-06-14)

**Method:** Read all three in full. Focused on whether the spec *contradicts* the recent PRD edits, *omits* material the PRD now depends on, or *contains* material the PRD should reflect. Contradictions are highest priority.

---

## Headline

**No hard contradictions found.** The spec does not say tiers are promotion-only, does not say challenges record only a single winner, and does not say engagement is entitlement-gated. On every recently-edited point the spec is either silent or consistent. The reconciliation issues are **omissions** — the spec under-specifies several things the PRD now treats as load-bearing — plus a few **drift / staleness** items the spec should be updated to reflect (the PRD is now ahead of the 2026-06-14 spec on multiple decisions dated 2026-06-15).

The PRD itself flags the spec as upstream-authoritative-for-detail (§0), so where the PRD is *more* specific that is by design; the items below are where the spec's silence or older framing could mislead a downstream reader who consults the spec directly.

---

## Contradictions (highest priority)

**None.** Detail:

- **Tier direction.** Spec §6.3 / §4 critical-rule / §10.5 describe tier as a stored value and reference "tier promotion" and a "promotion log," but **nowhere assert tiers are promotion-only**. The spec's own §14.2-equivalent language is absent; it never forbids demotion. FR-12's "set in any direction (promotion or corrective demotion)" is an *extension*, not a contradiction. **Severity: none (clear).**
- **Challenge winners.** Spec §6.4 (`uitdagingsrondte` "links entries/winners"), §8 IA ("per-tier winners"), §10.5 ("winner records") all speak of "winners" generically and **never restrict to a single winner per round**. FR-50's 1st/2nd/3rd-per-tier is a *refinement*, not a contradiction. **Severity: none (clear).**
- **Engagement gating.** Spec §4 roles table explicitly grants the **free member (`gratis lid`)** read/react/respond/library/training/reading-list/follow rights, with only *publish* reserved to the paid `intekenaar`. This **affirms** the PRD's "engagement is not entitlement-gated" (FR-23/§4.5). Fully consistent. **Severity: none (clear).**

---

## Omissions — spec under-specifies material the PRD now depends on

1. **Challenge operational rules entirely absent from spec — HIGH.**
   The PRD now carries concrete, testable challenge rules: all times **SAST**; deadline **inclusive through 23:59:59 SAST** (FR-47); **post-deadline judging freeze** (FR-47); **max 3 entries per content type per uitdaging** (FR-48); **tier fixed for the round** / entry-time pool governs (FR-48). The consolidated spec says only "monthly challenges: rules, results, per-tier winners" (§8) and "challenge rules" as an `ink-core` responsibility (§6.1, §10.5) — **no timezone, no cutoff semantics, no freeze, no entry cap, no tier-locking rule.** These are now load-bearing acceptance criteria with no spec backing. The spec should be extended (or the PRD explicitly marked as the source of truth for challenge-round mechanics). **Severity: HIGH** — most material gap; these drive server-cutoff and test behaviour.

2. **SM-8 craft-progression metric has no data-model support in the spec — HIGH.**
   SM-8 (≥30% YoY distinct top-3 Goud/Silwer placers, rolling 12 months) depends on FR-50's **placement records (1st/2nd/3rd per tier per round) being structured and queryable**. The spec's data model (§6.3 user meta = only `ink_writer_tier` + `ink_tier_promoted_at`; §6.4 taxonomy `uitdagingsrondte` = "links entries/winners") provides **no schema for ordered placements per tier**. The spec models "winners" but not *placement rank*, and has no notion of querying distinct placers over a rolling window. Whatever stores placements (custom table? meta? `uitdagingsrondte` + rank meta?) is undefined. The PRD's headline craft metric therefore rests on a data model the spec doesn't describe. **Severity: HIGH.**

3. **Tier-change audit log fields under-specified vs FR-12 — MEDIUM.**
   FR-12 requires the `graderingsgeskiedenis` log to capture **actor, date, reason, from→to tier, and optional challenge link**, for changes **in any direction**. The spec (§6.3) says only "Tier promotion history stored in a second meta key or a custom log table for auditability" — no field list, and the word "promotion" implies one-directional. A reader following the spec alone would build a promotion-only log missing `from_tier`/direction and the challenge-link FK. **Severity: MEDIUM.**

4. **SM-C4 (don't shrink the pool) has no anchor in spec — LOW/MEDIUM.**
   The counter-metric guarding SM-8 against gaming (don't reduce challenges/placement slots to concentrate placers) has no equivalent in the spec. Consistent with the spec's "engagement serves reading, not reach" ethos (§1) but not represented. Minor because counter-metrics are inherently PRD-layer. **Severity: LOW.**

---

## Drift / staleness — spec should be updated to reflect PRD decisions

5. **Spec is pre-2026-06-15; several PRD resolutions post-date it — MEDIUM.**
   The spec is dated 2026-06-14 and still lists as *open/review*: Report Content + CF7 as "review/conditional" (§10.1, §14 OQ-4/-8) and "Report Content (or custom)" for moderation (§5). The PRD has **resolved both to a custom `ink-core` form (OQ-4/OQ-8, 2026-06-15)** — no CF7/Fluent Forms/Report Content dependency. Spec §5, §10.1, §13 still reference these as live candidates. This is staleness, not contradiction, but a downstream reader using the spec's plugin table would provision retired tooling. **Severity: MEDIUM.**

6. **§14 "replacement-parity" framing not echoed in spec — LOW.**
   The PRD reframes launch scope as *replacement parity* (no functional regression + new self-serve payment), governed by feature-list P0/P1/P2 tags. The spec's §15 epic map and §3 scope are consistent with this but never state the parity bar explicitly. Informational; the PRD is the right home for scope framing. **Severity: LOW.**

7. **Spec role table uses `intekenaar`/`gratis lid`; PRD glossary omits them — LOW (reverse direction).**
   Spec §4 names roles **`intekenaar`** (paid subscriber) and **`gratis lid`** (free member). The PRD glossary (§3) defines `lid`, `skrywer`, `leser` behaviourally but does not surface `intekenaar`/`gratis lid` as terms. Not a contradiction (PRD's behavioural framing is deliberate and arguably cleaner), but the terms diverge between documents. Worth a glossary note or a spec alignment so `afrikaans-terms.md` stays single-source. **Severity: LOW.**

---

## Confirmed-consistent (spot checks, no action)

- **THE conflation rule** — spec §4 critical-rule matches PRD §4 banner / FR-13 verbatim in intent.
- **Tier default `brons` + flag on ambiguous import** — spec §11 / §6.3 matches FR-11 / MR-4.
- **Friendships → two mutual follows** — spec §6.5 / §11 / §14.10 matches MR-8 / UJ-6.
- **Auto-renew deferred, no discount framing** — spec §14.5/§14.8 matches FR-4 / §14.2.
- **Comments disabled site-wide; Gemeenskapsreaksies lof/insig/voorstel** — spec §7 matches FR-27 / §13 non-goals.
- **`storie` canonical (was `verhaal`)** — spec §6.2 matches glossary / OQ-1.

---

## Recommendation

Spec edits worth making (in priority order): (1) add a challenge-mechanics subsection to spec §7-or-§6 capturing SAST/inclusive-cutoff/freeze/3-entry-cap/tier-lock; (2) extend spec §6.3/§6.4 data model with the placement-record schema SM-8 needs and the full audit-log field list (incl. direction + challenge FK); (3) refresh spec §5/§10.1/§13 to retire Report Content & CF7 per the 2026-06-15 custom-form decision. None block the PRD — the PRD is internally complete and explicitly authoritative for the `what`; these keep the spec from misleading anyone who reads it standalone.
