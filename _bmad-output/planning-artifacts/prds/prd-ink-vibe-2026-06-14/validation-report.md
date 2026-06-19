# Validation Report — INK

- **PRD:** `_bmad-output/planning-artifacts/prds/prd-ink-vibe-2026-06-14/prd.md`
- **Rubric:** `.claude/skills/bmad-prd/assets/prd-validation-checklist.md`
- **Run at:** 2026-06-15T19:33:27Z
- **Grade:** Fair
- **Reviewers:** rubric walker · adversarial-general · edge-case-hunter

## Overall verdict

A strong, unusually disciplined brownfield PRD — real thesis (developmental wedge, "resonance over reach"), a load-bearing conflation rule enforced glossary→FRs→tests, honest triaged scope, and counter-metrics that guard the thesis. All seven rubric dimensions land **strong or adequate**; the rubric lens found no critical findings.

The adversarial and edge-case reviewers materially shift the picture, surfacing **six critical build-readiness gaps** the quality lens did not weight: success metrics measure activity not the developmental thesis (C-1); the migration entitlement lifecycle is verified not time-tested (C-2 / EC-C3); reclassification has no accuracy bar and the `skryfwerk` bucket no exit (C-3); and POPIA member-deletion fan-out across the relational graph is undefined (EC-C1), with a self-contradictory "anonymised/attributed" authorship rule (EC-C2). None invalidate the design, but they should be resolved before greenlighting Phase 3 — hence **Fair** rather than near-Excellent. Two cross-document defects (duplicate FR-58 / dangling auto-renew ref; addendum's stale "choose intent" step) also need fixing before downstream extraction.

## Dimension verdicts

- Decision-readiness — strong
- Substance over theater — strong
- Strategic coherence — strong
- Done-ness clarity — adequate
- Scope honesty — strong
- Downstream usability — adequate
- Shape fit — strong

## Findings by severity

### Critical (6)

**[Adversarial C-1]** — Success metrics measure activity, not the stated thesis (§1; §15 SM-4/SM-6/SM-7)
The Vision stakes everything on writers getting better; no SM measures it. *Fix:* add ≥1 primary SM operationalizing craft growth (revised follow-ups after voorstel/insig; editorial-judged quality sample).

**[Adversarial C-2]** — Migration's load-bearing requirements are "verify," not "build" (§10 MR-5; §15 SM-1; addendum E)
Subscriptions "ride the DB clone" and are only verified; no time-mocked test proves correct expiry/suspension weeks post-cutover. *Fix:* time-mocked entitlement-lifecycle acceptance test in NFR-9 + rollback/reconciliation plan.

**[Adversarial C-3]** — Reclassification has no success bar; fallback bucket no exit (§10 MR-6, Risk 2; skryfwerk)
"Bounded" undefined; no misclassification threshold; SM-1 passes even if the library is gutted into an invisible bucket. *Fix:* promote a classification target to an SM (≤X% in `skryfwerk`; owner + drain timeline); define "bounded".

**[Edge-case EC-C1]** — POPIA member-deletion fan-out undefined across the graph (§11/§8; FR-12/29/38/42/48/50)
Only "anonymise the bydrae" specified; volg edges, leeslys, reaksies, ratings, winner records, audit log silent. *Fix:* define per-relation deletion behaviour before building deletion.

**[Edge-case EC-C2]** — "Anonymised/attributed" authorship self-contradictory; mechanism undefined (§11; OQ-3)
No sentinel-author entity, display fallback, or Afrikaans orphaned-author label. *Fix:* resolve OQ-3 to one outcome; specify the mechanism.

**[Edge-case EC-C3]** — Migrated subscription active-but-expiring at cutover boundary (MR-5, FR-6; addendum E/H)
No handling for expiry in the freeze window, stale-active cron/timezone drift, or non-binary Woo states. *Fix:* post-cutover entitlement reconciliation for boundary-dated/non-binary states (pairs with C-2).

### High (10)

**[Rubric · Done-ness]** — Reading-template FRs specify legibility by adjective, not bound (§4.5, FR-24/FR-25). *Fix:* reduce FR-24 to verifiable consequences; move "legibility" to §6 tone.

**[Rubric · Downstream]** — FR-58 duplicate ID; "FR-58, §14" auto-renew ref dangling (§4.2, §4.12, §14.2). *Fix:* give deferred auto-renew its own FR number, or cite §14.2 + OQ-9 and drop the FR-58 token.

**[Rubric · Downstream]** — Addendum contradicts FR-2 (no signup intent) in §C/§G/§A. *Fix:* delete "choose intent" from §G; remove `intent` from the §A enum.

**[Adversarial H-1]** — "Zero English leakage" is a standing absolute over untranslatable dependencies (§7 NFR-1; §15 SM-2; addendum D). *Fix:* launch gate (zero on enumerated surfaces) + regression SLA; accept the non-zero window.

**[Adversarial H-2]** — Custom forms expanded scope into spam/security/POPIA territory with no requirements (FR-61; §8; OQ-4/OQ-8). *Fix:* add FRs for spam mitigation, rate-limiting, deliverability, validation; block report form behind a moderation SLA/owner.

**[Adversarial H-3]** — Scope exceeds resourcing; "no launch date" removes the forcing function (§14; §0; §1). *Fix:* define a first release around the §1 wedge; demote most P1; set a date or scope cap.

**[Adversarial H-4 / Edge-case EC-H7]** — Friendship→follow conversion contradicts itself (UJ-6/§3 "one-way" vs MR-8 two reciprocal edges). *Fix:* pick one; align the narrative/framing or the migration step. (EC-H7 adds: convert confirmed-only, dedup, skip edges to non-imported users.)

**[Adversarial H-5]** — Several "testable consequences" aren't testable; "done" undefined for key gates (FR-1, FR-4, NFR-6, FR-18, FR-13). *Fix:* convert adjectives to thresholds/capability lists; define leak-scan method + allowlist; enumerate editor marks incl. poetry handling.

**[Edge-case EC-H1]** — Subscription expiry mid-action (draft saved subscribed, published after lapse) (FR-6/19/23). *Fix:* gate-at-publish-time + draft preservation + denial UX.

**[Edge-case EC-H2]** — Challenge deadline boundary, late entries, post-deadline edits (FR-22/46/47/48). *Fix:* pin timezone + inclusivity; lock entries at deadline; define linkable window.

**[Edge-case EC-H3]** — Duplicate/multi-entry per challenge + tier-change mid-round (FR-48/49/12). *Fix:* entry cap; pin governing tier snapshot (entry-time).

**[Edge-case EC-H4]** — Following-feed/counts when a followed skrywer is deleted or lapses (FR-38/39/44). *Fix:* edge cleanup, count integrity, idempotent follow, "quiet writer" empty state.

**[Edge-case EC-H5]** — Redirect collisions: distinct legacy slugs → same CPT permalink; chained re-redirect on reclassification (MR-6/7, NFR-4, OQ-1). *Fix:* collision handling + re-redirect-on-reclassification policy.

**[Edge-case EC-H6]** — Tier-import join edge cases: dup/no email match, no normalisation (MR-3/4, FR-11). *Fix:* normalise emails; flag multi-match and failed-join distinctly.

> Note: rubric counts 3 high; adversarial 5; edge-case 7. The combined high list above merges H-4 with EC-H7 (same defect) — 16 distinct high findings across reviewers.

### Medium (10)

**[Adversarial M-1]** — POPIA/retention/visibility deferred but dependent P0/P1 features ship at launch (§8/§11, OQ-3; FR-1/40/42). *Fix:* require the OQ-3 session before building FR-1/FR-40/FR-42.

**[Adversarial M-2]** — UJs used as FR justification are admittedly assumed/unvalidated (§2.3, OQ-11). *Fix:* validate UJs before treating "Realizes UJ-n" as confidence.

**[Adversarial M-3]** — "100% preservation as launch blocker" is an absolute that will force a bad cutover call (§15 SM-1). *Fix:* "100% of valid records; pre-existing broken data enumerated in a reconciliation log."

**[Adversarial M-4]** — PayFast "installed yet unused" understates the central integration's risk (§1; FR-5; OQ-9). *Fix:* treat activation as a de-risking spike.

**[Adversarial M-5]** — "No auto-renew at launch" collides with the automation premise (FR-4; §14.2; §1). *Fix:* at minimum add expiry-reminder notifications.

**[Adversarial M-6]** — Afrikaans-leak scan mechanism misses its hardest cases (NFR-1/8; addendum D). *Fix:* extend coverage to email/JS/composed strings, or narrow SM-2's claim.

**[Edge-case EC-M1]** — Afrikaans number-agreement in dynamic counts ("1 hartjies" is wrong) (FR-28/38; §3/§6). *Fix:* plural-aware rendering (`_n()`) for every count surface.

**[Edge-case EC-M2]** — Empty/zero states across lists and counts (FR-28/29/32–36/39/50). *Fix:* enumerate zero states; author Afrikaans empty copy.

**[Edge-case EC-M3]** — Tier system promotion-only — no demotion/correction model (FR-11/12). *Fix:* add a redakteur correction/demotion action + audit event type.

**[Edge-case EC-M4]** — Concurrent edits / double-submit, no idempotency (FR-5/12/16–23/41/42). *Fix:* idempotency on publish/purchase + write-collision policy.

**[Edge-case EC-M5]** — PayFast failure/abandonment/partial-return paths unspecified (FR-5/6/9, NFR-9). *Fix:* specify unhappy-path states + ITN/return ordering + idempotent activation.

**[Edge-case EC-M6]** — skryfwerk items reachable but no front-end contract (§3, MR-6, FR-32–35/40). *Fix:* define discovery/profile/search behaviour + reclassification policy.

**[Edge-case EC-M7]** — Author-initiated unpublish/delete of a work carrying interactions (FR-6/26–28/29/42/50). *Fix:* define interaction cascade; confirm engagement isn't entitlement-gated.

**[Edge-case EC-M8]** — Reading-layout boundary cases for gedig (FR-25/17). *Fix:* define line/stanza semantics + wrap + blank-line resonance.

**[Edge-case EC-M9]** — Configurable plan changes vs existing/in-flight subscribers (FR-4/8). *Fix:* define retired-plan renewal flow + grandfathering/messaging.

> Adversarial 6 medium + edge-case 9 medium = 15 medium findings.

### Low (10)

- **[Rubric · Substance]** Non-Users section borders on negative-persona theater (§2.2). *Fix:* leave or fold into §13.
- **[Rubric · Done-ness]** "first-response SLA" referenced but never bounded (§8) — deferred to OQ-4.
- **[Rubric · Downstream]** FR-50 priority label inconsistent in form (§4.8). *Fix:* normalize to "(P0, FL 11.6)".
- **[Rubric · Downstream]** A few "see §8/§11" cross-refs vaguer than glossary-term style (FR-42, §4.2).
- **[Adversarial L-1]** Addendum E2E test contradicts the registration decision (addendum G; FR-2). *Fix:* strike "choose intent" + the enum. (Same defect as Downstream high.)
- **[Adversarial L-2]** FR-50 priority notation ambiguous (FR-50; §14.1). *Fix:* one priority, or split admin-record from public-surface.
- **[Adversarial L-3]** Real3D Flipbook PDF perf/a11y tension unaddressed (FR-57; NFR-3/5). *Fix:* acknowledge tradeoff; define a11y/mobile fallback (direct PDF link).
- **[Adversarial L-4]** "No discount framing" conflicts with 1/6/12-month plan economics (FR-4). *Fix:* tone choice — accept or allow a neutral per-month note.
- **[Edge-case EC-L1]** Roman-numeral stanza markers: overflow + auto-vs-manual source (FR-25).
- **[Edge-case EC-L2]** Search on Afrikaans diacritics + verhaal→storie legacy term (FR-35, OQ-1).
- **[Edge-case EC-L3]** "Merk alles as gelees" with zero/concurrent notifications (FR-44).
- **[Edge-case EC-L4]** Sponsor scheduling boundary + zero-active-sponsor homepage (FR-58/59).
- **[Edge-case EC-L5]** First-social-action prompt targets at cold-start (FR-3, UJ-1).

## Mechanical notes

- **Glossary drift:** minimal. Deliberate dual naming (label "ster gradering" / slug `ster_gradering` vs meta key `ink_writer_tier`) is documented, not drift — but a propagation hazard.
- **ID continuity:** FR-1..FR-62 contiguous except FR-58 used twice in effect (Sponsors §4.12 vs the cited-but-absent auto-renew FR-58). MR/SM/UJ/OQ/NFR ranges contiguous + unique.
- **Broken cross-refs:** "FR-58, §14" (§4.2) resolves to the wrong/absent target. Others resolve.
- **Assumptions Index roundtrip:** clean; §17 self-count matches.
- **UJ protagonist naming:** all six UJs named with inline context.
- **Addendum sync:** §C/§G carry stale "intent" content contradicting FR-2/OQ-15.
- **Required sections:** all present for launch-stakes brownfield.

## Reviewer files

- `review-rubric.md`
- `review-adversarial-general.md`
- `review-edge-case-hunter.md`
