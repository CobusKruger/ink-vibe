---
baseline_commit: 8f71224
---

# Story 3.4: Anti-spam research spike (R6)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a redakteur,
I want anti-spam / account-abuse approaches evaluated **before building**,
so that INK's registration defenses are chosen on evidence rather than guesswork — the owner has said "I know nothing about this" — and stories 3.5 (social login) and 3.6 (optional manual-approval backstop) build on a deliberate, documented decision. (FR-3a, R6)

## Acceptance Criteria

1. **A written, evidence-based build decision exists** — the spike's sole deliverable is a decision document (NOT code) that evaluates the realistic anti-spam / account-abuse approaches for an Afrikaans-first, brownfield WordPress community, weighs each against INK's constraints, and lands a recommendation. _[Source: epics.md#Story-3.4 AC "a documented build decision exists evaluating anti-spam / account-abuse approaches"; project-context.md "registration defenses are chosen on evidence (owner: 'I know nothing about this')"]_

2. **The decision explicitly gates 3.5 and 3.6** — it names what story 3.5 (social login) should integrate (which vetted provider/plugin, integrated via hooks not reimplemented in `ink-core`) and the role/posture of story 3.6 (the optional, off-by-default manual-approval backstop), so both downstream stories have a concrete, decided starting point. _[Source: epics.md#Story-3.4 AC "it gates stories 3.5 and 3.6"; #Story-3.5 (social login — vetted platform plugin, hooks only); #Story-3.6 (off-by-default approval queue)]_

3. **The evaluation respects INK's hard constraints** and states, for each candidate, how it scores against them:
   - **Frictionless signup is the launch default (UJ-1)** — the recommended primary defense must not harm the default no-gate signup conversion. An invisible / low-friction challenge is preferred over an interactive CAPTCHA wall.
   - **Afrikaans-first / zero English leakage (Gate D)** — any user-facing challenge text, error, or button must be available in Afrikaans (`af`) or be visually text-free; the doc records each candidate's `af`-localisation status (and flags gaps for the Epic-17 leak backlog).
   - **POPIA / privacy** — any third-party that processes registrant PII or IP is assessed for a privacy-respecting posture (the South African POPIA analogue of GDPR), since the origin is already Cloudflare-locked.
   - **Stack fit** — Cloudflare already fronts the origin and **Patchstack** is the security plugin; the doc considers what the existing stack already provides before adding a new dependency.
   - **Retired-plugin guardrail** — the recommendation must NOT resurrect a retired plugin (Loginizer, Invite Anyone, Youzify, etc., per the project don't-list) and must NOT reimplement a commodity capability in `ink-core`.
   _[Source: project-context.md "frictionless signup", Gate D, "Cloudflare-locked origin", "Patchstack", retired-plugin list; EXPERIENCE.md UJ-1; architecture.md line 650-652 (R6 = vetted-plugin seam via hooks, not ink-core code)]_

4. **The decision states the three-layer / architecture posture** — R6 is a **vetted-plugin seam integrated via hooks, NOT `ink-core` business logic** (architecture line 650-652); the doc confirms the chosen approach honours this (the seam lands in Epic 3 Accounts; deeper hardening is Story 18.10) and does not violate THE conflation rule or three-layer separation. _[Source: architecture.md line 650-652 ("social-login (R6) ... integrated via hooks, not ink-core code ... R6 → Accounts/Epic 3 + an anti-spam research spike"); epics.md FR-3a coverage (3.4, 3.5, 3.6 + 18.10); project-context.md three-layer separation]_

5. **No production code, no schema, no tests** — being a spike, the story ships ONLY the decision document. The unit suite is run once to confirm the spike introduced no regression (it should remain green at the 3.3 baseline), and that result is recorded; but no new `ink-core` logic or Pest tests are authored (there is nothing testable to author). _[Source: project-context.md testing rule (run the suite); the spike produces a decision, not code]_

## Tasks / Subtasks

> **This is a RESEARCH SPIKE. The deliverable is a decision document, not code.** Do NOT install a plugin, write `ink-core` logic, add a schema, or build the social-login / approval-queue UI — those are stories 3.5 and 3.6, which this spike *gates*. Keep `git status` to the single new decision file + the story/sprint-status updates.

- [x] **Task 1 — Survey the realistic candidate approaches (AC: 1, 3)**
  - [x] Enumerate the realistic registration-defense options for a brownfield Afrikaans WordPress community, e.g.: an invisible/low-friction challenge (**Cloudflare Turnstile** — natural fit given the Cloudflare-locked origin; **hCaptcha**; reCAPTCHA v3), **honeypot / timing** field techniques, **email-verification / double opt-in**, what **Patchstack** and Cloudflare already provide at the edge, and **social login as an identity-assurance + friction-reducer** (the 3.5 seam). For each, note `af` localisation status, privacy/POPIA posture, friction cost, and whether it is a hook-only seam.
  - [x] Explicitly exclude / flag retired or reimplementation options (Loginizer et al.; no `ink-core` re-build of a commodity capability).

- [x] **Task 2 — Score candidates against INK's constraints and recommend (AC: 1, 3, 4)**
  - [x] Produce a short comparison (table or bulleted matrix) scoring each candidate on: friction (UJ-1), Afrikaans/Gate-D, POPIA/privacy, stack fit (Cloudflare/Patchstack), three-layer/hook-seam compliance, and cost.
  - [x] Land a **primary recommendation** (the low-friction default defense) plus the **layered posture** (what runs always-on vs. what is the escalation backstop), with the rationale tied to the constraints.

- [x] **Task 3 — Write the gating guidance for 3.5 and 3.6 (AC: 2, 4)**
  - [x] **For 3.5 (social login):** name the recommended vetted provider/plugin (or shortlist with a default), confirm it is integrated via hooks (not reimplemented in `ink-core`), and note the Afrikaans-button / `af`-localisation requirement and any POPIA consent note.
  - [x] **For 3.6 (manual-approval backstop):** confirm it stays **off by default** (frictionless signup preserved, UJ-1), describe when a redakteur would enable it, and how it composes with the primary defense from Task 2.
  - [x] Note what is deferred to **Story 18.10** (registration anti-spam hardening) vs. what lands in Epic 3.

- [x] **Task 4 — Land the decision document + run the regression check (AC: 1, 5)**
  - [x] Write the decision to `_bmad-output/implementation-artifacts/3-4-anti-spam-decision.md` (or an equivalent clearly-named artifact), self-contained and readable by a non-technical owner.
  - [x] Run `composer test:unit` once to confirm the spike caused **no regression** (still green: 93 passed / 1 skipped). Recorded in the Dev Agent Record. No new tests authored (nothing testable).

- [x] **Task 5 — Verification sweep (AC: 1–5)**
  - [x] Confirm scope discipline: no plugin installed, no `ink-core` code, no schema, no UI — only the decision doc + tracking updates.
  - [x] Confirm the decision answers all of AC-3's constraints per candidate and that AC-2's 3.5/3.6 gating guidance is concrete.
  - [x] Confirm no retired plugin is recommended and the chosen seam is hook-only (three-layer compliant).

## Dev Notes

### What this story is (and is NOT)

- **IS:** a time-boxed **research spike** whose only artifact is an evidence-based **build-decision document** evaluating anti-spam / account-abuse approaches, scoring them against INK's constraints, recommending a layered posture, and giving concrete gating guidance to stories 3.5 and 3.6. _[epics.md#Story-3.4]_
- **IS NOT:** the social-login integration (Story 3.5), the manual-approval backstop (Story 3.6), or the deeper registration hardening (Story 18.10). NOT any `ink-core` code, plugin install, schema, or UI. R6 is a **vetted-plugin seam via hooks, not `ink-core` logic** — this spike only decides *which* seam.

### Decided constraints (do not re-litigate — score against them)

- **Frictionless signup is the launch default** (UJ-1); the primary defense must be invisible/low-friction. _[EXPERIENCE.md UJ-1; epics.md Epic 3]_
- **R6 = vetted-plugin seam, hook-integrated, in Accounts/Epic 3; not reimplemented in `ink-core`.** _[architecture.md line 650-652]_
- **Afrikaans-first / Gate D** on any user-facing challenge text; **POPIA** posture for any PII/IP processor; **Cloudflare + Patchstack already in the stack**; **no retired plugins**, no commodity reimplementation. _[project-context.md]_
- **Owner is non-expert** ("I know nothing about this") — the doc must be readable and decisive, not a literature dump.

### Guardrails

- Spike ⇒ **no code, no plugin install, no schema, no tests authored**; the single deliverable is the decision doc. Run the suite once for regression confirmation only.
- Recommend a **hook-only seam**; never resurrect a retired plugin; never put R6 logic in the theme or reimplement a commodity capability in `ink-core`.
- Any candidate that shows a user-facing string must have an `af` localisation path or be text-free — record gaps for the Epic-17 leak backlog.

### Project Structure Notes

- **New file:** `_bmad-output/implementation-artifacts/3-4-anti-spam-decision.md` (the decision artifact). No source/theme/test files change.
- This decision is an input to the **3.5** and **3.6** create-story steps and to **Story 18.10**.

### Testing standards summary

- No `ink-core` logic ⇒ **no new Pest tests** (nothing testable). Run `composer test:unit` once to confirm the spike introduced no regression (baseline: 93 passed / 1 skipped after Story 3.3). Record the command + result.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Epic-3 + #Story-3.4 — "As a redakteur, I want anti-spam approaches evaluated before building ... a documented build decision exists ... And it gates stories 3.5 and 3.6"; #Story-3.5 (social login — vetted platform plugin, hooks only, not reimplemented in `ink-core`); #Story-3.6 (off-by-default "wag op goedkeuring" approval queue); FR-3a coverage map (3.4, 3.5, 3.6 + 18.10)]
- [Source: _bmad-output/planning-artifacts/architecture.md — line 650-652 ("Analytics provider (R8) and social-login (R6) are new vetted-plugin seams integrated via hooks, not `ink-core` code ... R6 → Accounts/Epic 3 + an anti-spam research spike"); line 400 (WP moderation/Akismet-compatible spam tooling reused, not reimplemented); Epic→Location map (Epic 3 = WP-native auth + `ink-foundation` templates + `ink-core` first-action prompt)]
- [Source: _bmad-output/project-context.md — frictionless signup / UJ-1; Afrikaans-first Gate D ("No English word reaches a visitor"; "No AI-generated Afrikaans"); "Cloudflare-locked origin (no direct origin traffic)"; Patchstack in the security stack; retired-plugin don't-list (Loginizer, Invite Anyone, Youzify, …); three-layer separation; "Hook, don't edit" (integrate via plugin hooks, never reimplement); THE conflation rule]
- [Source: _bmad-output/planning-artifacts/ux-designs/ux-ink-vibe-2026-06-15/EXPERIENCE.md — UJ-1 (frictionless besoeker signup, no reader/writer gate); the post-signup soft first-action prompt (Story 3.3, already built)]
- [Source: _bmad-output/implementation-artifacts/3-3-registration-lifecycle-onboarding.md — the onboarding surface this defense protects; baseline 93 passed / 1 skipped]
- [Source: docs/afrikaans-terms.md — "wag op goedkeuring" (pending-approval state, Story 3.6); a new user-facing term must be glossary-approved before it appears in UI]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m] (Opus 4.8, 1M context)

### Debug Log References

- `composer test:unit` → **93 passed / 1 skipped (565 assertions)** — unchanged from the post-3.3 baseline, confirming the spike introduced no regression. No new tests authored (a research spike has no testable `ink-core` logic).

### Completion Notes List

- **Deliverable:** `_bmad-output/implementation-artifacts/3-4-anti-spam-decision.md` — an owner-readable, evidence-based build decision.
- **Decision (layered posture):** (1) **Cloudflare Turnstile** as the always-on, invisible front line — free, privacy-respecting, already in the Cloudflare-locked stack, hook-integrated; (2) **email double opt-in** baseline (Afrikaans email via the Story-1.12 Notifications store); (3) **honeypot + submit-timing** cheap supplement; (4) **social login (3.5)** as a trusted-identity friction reducer; (5) **manual-approval queue (3.6) OFF by default** as the redakteur's escalation lever. R6 stays a vetted-plugin/edge seam via hooks — **no `ink-core` anti-spam logic** (architecture.md line 650-652), conflation rule untouched.
- **Gates 3.5:** integrate a vetted social-login plugin via hooks (Google + Apple, free tier), Afrikaans buttons from the terminology registry, POPIA consent line, socially-registered accounts still land Brons / gratis lid via the existing `user_register` path. **Confirm the specific plugin is maintained + Patchstack-clean at integration (knowledge-cutoff caveat).**
- **Gates 3.6:** off by default (UJ-1 preserved); "wag op goedkeuring" glossary-approved state; composes independently with Turnstile (edge bots vs. human abuse).
- **Deferred to 18.10:** edge rate-limiting / IP reputation, abuse monitoring, Turnstile tuning.
- **Verify-at-integration flags (recorded in the doc):** Turnstile `af` locale / text-free confirmation for Gate D; the exact 3.5 plugin choice; double-opt-in delivery mechanism (hook vs. plugin).
- **Scope discipline held:** no plugin installed, no code/schema/UI, no retired-plugin recommendation; only the decision doc + tracking updates.

### File List

**New (decision artifact):**
- `_bmad-output/implementation-artifacts/3-4-anti-spam-decision.md`

**Modified (tracking):**
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `_bmad-output/implementation-artifacts/3-4-anti-spam-research-spike-r6.md` (this story file — record + checkboxes)

## Change Log

| Date | Change |
|---|---|
| 2026-06-22 | Story created (context-engineered) — R6 anti-spam research **spike**: the sole deliverable is an evidence-based build-decision document evaluating registration-defense approaches (invisible challenge à la Cloudflare Turnstile / hCaptcha, honeypot/timing, email verification, edge/Patchstack capabilities, social login as identity assurance) scored against INK's constraints (frictionless UJ-1 signup, Afrikaans/Gate-D, POPIA, Cloudflare+Patchstack stack fit, hook-only/three-layer, no retired plugins), landing a layered recommendation and concrete gating guidance for Story 3.5 (social login) and Story 3.6 (off-by-default manual-approval backstop). NO code / plugin install / schema / tests — R6 is a vetted-plugin seam via hooks (architecture line 650-652), not `ink-core` logic. Status → ready-for-dev. |
