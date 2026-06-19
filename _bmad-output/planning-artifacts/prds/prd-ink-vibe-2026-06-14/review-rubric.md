# PRD Quality Review — INK (prd-ink-vibe-2026-06-14)

## Overall verdict

This is a strong, unusually disciplined brownfield PRD: it has a real thesis (developmental wedge, "resonance over reach"), a load-bearing conflation rule enforced from glossary through FRs to tests, honest scope boundaries, and counter-metrics that guard the thesis rather than decorate it. The chief risks are downstream-mechanical, not strategic: a duplicated FR-58 ID with a dangling "FR-58, §14" reference for the deferred auto-renew, and an addendum (§C, §G) that still carries the abandoned "choose intent" signup step that FR-2/OQ-15 explicitly killed — both will mislead the architecture/story workflows that source-extract from these documents. Done-ness is good for the conflation/entitlement core but softens into adjectives ("legible", "subtly", "low effort") in the reading, sponsor, and editorial FRs.

## Decision-readiness — strong

Decisions are stated as decisions and dated, not buried. The "Why now" paragraph (§1) names the actual lever honestly — "PayFast is already installed yet unused… the single change that lifts the manual-operations tax" — and explicitly declines a launch date ("Timing is driven by operational pain and technical debt, not an external deadline"). Trade-offs are surfaced with what was given up: §10 risk 2 admits reclassification will "force a bounded manual bulk-edit pass"; the auto-renew deferral (§14.2) is flagged as "emotionally load-bearing for reducing churn" rather than silently dropped. `[NOTE FOR PM]` callouts land at genuine tensions (POPIA legal posture §8, moderation SLA/owner §8, deletion-vs-attribution behaviour §11), not safe checkpoints. Open Questions are mostly real and carry owners and status (✅/⏸️/⬜). The triage discipline (§16: "8 resolved, 6 deferred, 1 open") is exactly what a decision-maker needs.

One soft spot: a few OQs are resolved tersely in a way that hides the reasoning a future reader would want — e.g. OQ-14 keeps the English meta key `ink_writer_tier` "to avoid a meta-key migration"; the trade-off (an English key inside an Afrikaans-first system) is named but not weighed. Minor.

## Substance over theater — strong

Very little furniture. The JTBD in §2.1 are written as jobs, not persona padding, and each maps to shipped FRs (develop-craft → tiers/challenges/training; be-read-and-resonate → discovery + counter-metric SM-C1). The Vision (§1) is genuinely product-specific — swap in another platform and the "literary *tuiste*, not a social network and not a marketplace… developmental wedge" framing breaks, which is the test it must pass. NFRs carry product-specific thresholds rather than boilerplate: NFR-1 is a "standing operational gate… re-run after ungated core/plugin updates… verified by an automated English-leak scan", and NFR-9 specifies the actual test pyramid and the load-bearing seams. Counter-metrics (SM-C1–C3) are the strongest anti-theater signal: they explicitly tell the build *not* to optimize notification volume or raw reaction counts.

### Findings
- **low** Non-Users section borders on negative-persona theater (§2.2) — four "non-users" are really restatements of Non-Goals (§13: not multilingual, not a marketplace, not an LMS). It does no harm but earns little. *Fix:* leave as-is or fold into §13; not worth churn.

## Strategic coherence — strong

The PRD has a clear thesis and the features serve it. The thesis is stated three ways that reinforce each other: developmental wedge (§1), "resonance over reach" (§1, §6), and "Engagement serves reading, not feed-scrolling" (§4.5 banner). Prioritization follows the thesis and the operational pain, not "what's easy" — P0 is anchored on migration preservation + the payment/entitlement gap + the conflation guardrail, which is exactly the "Why now". Success Metrics validate the thesis rather than measuring activity: SM-4 is "Return-to-read… ≥6×/month (breaking the legacy ~3×/month pattern)" — engagement *quality of return*, not DAU/MAU — and it is explicitly counterbalanced by SM-C1 so that rising return-frequency with falling reading time is named as a regression. The MVP scope kind reads as an experience/platform hybrid with matching scope logic (§14). THE conflation rule is the spine that ties data model, FRs, and tests together.

## Done-ness clarity — adequate

The core is excellent and the periphery is soft — appropriate weighting, but the soft FRs are exactly where story creation will struggle. Strong, testable FRs: FR-6 ("if and only if the **lid** has an active WooCommerce Membership"), FR-13 ("no code path where changing subscription state mutates `ink_writer_tier`… Verified by unit test"), FR-19 ("an expired **skrywer** with Goud tier is denied publishing"), FR-11 (missing tier on import "resolves to `brons` **plus a flag** (never a guessed Silwer/Goud)"). These carry their own acceptance criteria.

But several FRs lean on adjectives with no bound, and the rubric says be unforgiving here.

### Findings
- **high** Reading-template FRs specify legibility by adjective, not bound (§4.5, FR-24/FR-25) — FR-24 "optimised for Afrikaans legibility (content width ~768px)" gives one number but "legibility"/"legible reading template" is otherwise untestable; FR-25 lists concrete behaviours (stanza-aware, preserves line breaks, Roman-numeral markers, per-line resonance) and is fine. *Fix:* reduce FR-24 to its verifiable consequences (width, no comments, single template per CPT) and drop "optimised for legibility" as an acceptance term, or move it to §6 as tone.
- **medium** Sponsor placement is adjective-bound (§4.12, FR-58) — "placed subtly… one featured/rotating sponsor on the homepage; no logo dumps on content pages". "Subtly" is not testable; the "one on homepage / none on content pages" part is. *Fix:* drop "subtly" as a criterion; keep the count/placement rules, which are the real acceptance.
- **medium** Editorial-effort FRs assert "low/no manual linking" without a check (FR-55, §12 guardrail) — "auto-surfaces… with **no manual linking**" is a genuine constraint but has no stated verification, unlike the conflation rule which says "Verified by unit test". *Fix:* add a consequence — e.g. cross-surfacing is driven solely by shared `genre`/`vaardigheid` terms and an item with no shared term surfaces nothing (assertable).
- **medium** FR-9 / FR-44 list user-facing strings but not states/triggers fully (§4.2, §4.7) — FR-9 covers active/expired/denied (good) but FR-44 enumerates notification types (@mentions, challenge, follow/new-work) without saying which are P0 vs which fire when. *Fix:* table the trigger → notification mapping; low effort, high downstream value for story creation.
- **low** "first-response SLA" referenced but never bounded (§8) — deliberately deferred to OQ-4, acceptable, but flag that no FR can be marked done against it until set.

## Scope honesty — strong

Omissions are explicit and worked, not inferred. §13 Non-Goals does real work (no feed, no marketplace, no LMS, no inline annotation, no WP comments, no symmetric friendship, no AI-Afrikaans, no admin-chrome translation) and the closing line distinguishes deferred-from-non-goal: "*Deferred (not non-goals): auto-renew… private messaging — see §14.*" `[ASSUMPTION]` tags sit on genuine inferences (UJ beats §2.3, POPIA defaults §8, retention §11) and every one round-trips into §17 and an owning OQ. De-scoping is proposed openly: §4.9 Library date-browse/pagination/author-filter "deferred, non-blocking" with the empty-placeholder design gap named. Open-items density is appropriate to launch stakes — the counts are bounded and triaged (§16: 8/6/1; §17: 3 confirmed / 3 deferred), and nothing P0 is left open. This is the model the rubric wants.

## Downstream usability — adequate

This is the dimension carrying the most risk for a chain-top PRD, and it is good on glossary discipline but holed by two ID/contradiction defects that will propagate into the architecture and story workflows.

Glossary (§3) is thorough and used verbatim across FRs, UJs, SMs (intekening/ster gradering/bydrae/Gemeenskapsreaksie hold consistent). UJs each have a named protagonist (Marlie, Pieter, Thandi, Elsa, Johan) and FRs reference them inline ("Realizes UJ-3"). Sections largely stand alone via glossary terms. But:

### Findings
- **high** FR-58 is a duplicate ID, and the "FR-58, §14" deferred-auto-renew reference is dangling (§4.2 Notes, §4.12, §14.2) — §4.2 Notes says auto-renew "is **deferred** (FR-58, §14)", but FR-58 in §4.12 is the **Sponsor model**, and there is no FR-58 in §14. The auto-renew item has no FR of its own at all (it appears only as prose in §4.2/§14.2 and OQ-9). A downstream agent resolving "FR-58" for auto-renew will land on Sponsors. *Fix:* either give deferred auto-renew its own reserved/deferred FR number and update both references, or change the §4.2 note to cite §14.2 + OQ-9 only and drop the FR-58 token.
- **high** Addendum contradicts FR-2 (no signup intent) in two places (addendum §C, §G) — §G's E2E journey reads "register → **choose intent** → buy via PayFast sandbox", and §A models an `intent` enum ("tier, intent, response type, reaction"), but FR-2 and OQ-15 explicitly removed the intent gate ("No reader/writer intent flag… is captured at signup or stored"). The addendum feeds architecture/UX directly; an agent will scaffold an `intent` enum and an intent step that must not exist. *Fix:* delete "choose intent" from §G's E2E path and remove `intent` from the §A enum list; align with FR-2.
- **low** FR-50 priority label is inconsistent in form (§4.8) — written "(P0 for admin, FL 11.6)" while every other FR uses a bare P0/P1/P2. Readable, but a strict parser keyed on `(P0|P1|P2,` may miss it. *Fix:* normalize to "(P0, FL 11.6)" and note the admin-only scope in the consequence.
- **low** "see §8/§11" style cross-refs appear a few times (FR-42, §4.2 NFR note) — mostly fine since they point to stable section numbers, but §4.2's "(see §8/§11)" is vaguer than the glossary-term style the PRD otherwise uses. Minor.

## Shape fit — strong

The shape matches the product. This is a consumer/community platform with meaningful UX feeding a downstream chain, so named-protagonist UJs are load-bearing — and they are present, named, and journey-shaped (§2.3), including a brownfield-specific one (UJ-6, "Migration day — Johan notices nothing broke") that is exactly right for a preservation-first rebuild. Brownfield obligations are met: existing-code references are concrete (legacy Youzify, BuddyPress friendships → one-way volg, `verhaal`→`storie` rename), retired plugins are enumerated with a "must not be reactivated" list (§9), and new-vs-existing behaviour is distinguished (MR-5 "ride the clone, no import" vs MR-3/MR-4 import scripts). Nothing is over-formalized (UJ count is 6, within bounds; personas are JTBD, not a persona gallery) or under-formalized. The capability-spec split between this PRD (what/why) and the addendum (how/why-not) is the correct shape for an AI-build audience.

## Mechanical notes

- **Glossary drift:** Minimal. Terms hold verbatim across FRs/UJs/SMs. One label-vs-slug case to watch downstream: glossary uses label "ster gradering" with taxonomy slug `ster_gradering` (OQ-13) while the user-meta key stays `ink_writer_tier` (OQ-14) — both are deliberate and documented, not drift, but the dual naming is a known propagation hazard.
- **ID continuity:** FR-1..FR-62 contiguous **except** FR-58 is used twice in effect — the real FR-58 is Sponsors (§4.12), while §4.2/§14.2 cite "FR-58" for deferred auto-renew, which has no FR. See Downstream finding (high). MR-1..MR-11, SM-1..SM-7 + SM-C1..SM-C3, UJ-1..UJ-6, OQ-1..OQ-15, NFR-1..NFR-9 are all contiguous and unique.
- **Broken cross-refs:** "FR-58, §14" (§4.2 Notes) resolves to the wrong/absent target — see above. All other inline cross-refs (FR↔UJ, FR↔SM, FR↔§) resolve.
- **Assumptions Index roundtrip:** Clean. Inline `[ASSUMPTION]` tags (§4 conflation-area UJ note, §5 IA, §8 POPIA/public-profile/email, §11 retention, §15 SM-1/SM-5) all appear in §17, and §17 entries all trace back inline. §17 self-count "3 confirmed, 3 deferred" matches.
- **UJ protagonist naming:** All six UJs carry a named protagonist with inline context. UJ-2..UJ-3 reuse protagonists introduced in UJ-1/the prose, which is fine.
- **Addendum sync:** §C and §G carry stale "intent" content contradicting FR-2/OQ-15 — see Downstream finding (high). §G also reuses the "choose intent" beat in the E2E description that NFR-9 (§7) renders correctly without intent — the PRD body and addendum disagree.
- **Required sections:** All present for launch-stakes brownfield (Vision, Users/JTBD, Glossary, Features/FRs, IA, Tone, NFRs, Compliance, Integration, Migration, Data Governance, Constraints, Non-Goals, MVP Scope, Success Metrics + counters, Open Questions, Assumptions Index).
