# R6 — Registration anti-spam / account-abuse: build decision

**Story:** 3.4 (research spike, R6) · **Date:** 2026-06-22 · **Status:** Decided (gates 3.5 + 3.6)
**Audience:** the redakteur / owner (non-technical) + the dev implementing 3.5/3.6/18.10

> **One-line answer.** Use **Cloudflare Turnstile** (an invisible, free, privacy-respecting bot check) as the always-on front-line defense — it slots straight into our existing Cloudflare setup and stays invisible to real people — backed by **email verification** on signup, with **social login (3.5)** as a friction-reducing trusted-identity path and an **off-by-default manual-approval queue (3.6)** as the escalation lever the redakteur can pull only if abuse appears. We add **no anti-spam business logic to `ink-core`**; every piece is a vetted-plugin/edge seam wired through hooks.

---

## 1. Why this spike exists

The owner asked for registration defenses to be **chosen on evidence** ("I know nothing about this"). INK's signup is deliberately **frictionless** — a besoeker joins with no reader/writer gate (UJ-1) and is softly onboarded (Story 3.3, already built). That openness is exactly what bots and abusers exploit: fake-account floods, spam profiles, and credential-stuffing attempts. R6 is the layer that protects the open door **without closing it on real Afrikaans writers**.

This document evaluates the realistic options, scores them against INK's hard constraints, and hands stories **3.5** (social login) and **3.6** (manual-approval backstop) a decided starting point.

## 2. The constraints every option is scored against

| # | Constraint | Why it is binding |
|---|---|---|
| C1 | **Frictionless signup is the launch default (UJ-1)** | The chosen front-line defense must be invisible / low-friction. An interactive "click the traffic lights" wall harms conversion and is the wrong default. |
| C2 | **Afrikaans-first, zero English leakage (Gate D)** | Any user-facing challenge text, error, or button must render in Afrikaans (`af`) **or** be visually text-free. No AI-translated copy. |
| C3 | **POPIA / privacy** | Any third party that sees a registrant's PII or IP must have a privacy-respecting posture (POPIA is South Africa's GDPR analogue). The origin is already Cloudflare-locked. |
| C4 | **Stack fit** | Cloudflare already fronts the origin; **Patchstack** is the security plugin. Prefer what the stack already gives us before adding a dependency. |
| C5 | **Three-layer + "hook, don't edit"** | R6 is a **vetted-plugin / edge seam integrated via hooks — NOT `ink-core` business logic** (architecture.md line 650-652). No commodity reimplementation; no logic in the theme. |
| C6 | **No retired plugins** | Must not resurrect anything on the don't-list (Loginizer, Invite Anyone, Youzify, …). |

## 3. Candidates evaluated

| Candidate | What it is | C1 friction | C2 Afrikaans | C3 POPIA/privacy | C4 stack fit | C5 hook-seam | Verdict |
|---|---|---|---|---|---|---|---|
| **Cloudflare Turnstile** | Invisible/managed bot challenge from our existing CDN; free, unlimited | ✅ Invisible for real users | ◑ Mostly text-free; widget has a locale param — **verify `af`** | ✅ Privacy-first by design (no ad-tracking, minimal data) | ✅✅ Already on Cloudflare; zero new vendor | ✅ WP plugin/hook on the registration form | **PRIMARY (recommended)** |
| **hCaptcha** | Privacy-focused CAPTCHA, free tier, official WP plugin | ◑ Can be invisible/passive but sometimes interactive | ◑ Localised; verify `af` | ✅ Strong privacy stance | ◑ New vendor (not in stack) | ✅ Official plugin + hooks | Strong fallback if Turnstile `af`/UX disappoints |
| **Google reCAPTCHA v3** | Score-based invisible CAPTCHA | ✅ Invisible | ◑ | ✗ Google data-sharing — weakest POPIA story | ◑ New vendor | ✅ | Rejected on C3 (privacy) |
| **Honeypot + submit-timing** | Hidden field + min-time-to-submit; catches dumb bots | ✅✅ Truly invisible, zero PII | ✅ No user-facing text | ✅✅ No third party at all | ✅ | ✅ Small plugin or theme-form attr | **SUPPLEMENT** — cheap, stack it under Turnstile |
| **Email verification (double opt-in)** | New account must confirm via emailed link before full use | ◑ One extra step, but expected & low-friction | ✅ Email authored in Afrikaans via `ink-core` Notifications (Story 1.12) | ✅ | ✅ WP/BuddyPress hooks | ✅ | **ALWAYS-ON baseline** |
| **Social login** | "Sign in with Google/Facebook/Apple" | ✅✅ *Reduces* friction | ◑ Buttons need `af` labels | ◑ Provider sees the auth; needs a consent note | ◑ New vetted plugin | ✅ Hooks only (Story 3.5) | **Story 3.5** — identity assurance + friction reducer |
| **Manual approval queue** | New accounts held in "wag op goedkeuring" until a redakteur approves | ✗ High friction if always-on | ✅ State label is glossary-approved Afrikaans | ✅ | ✅ | ✅ | **Story 3.6 — OFF by default**, escalation only |
| **Akismet / WP moderation** | Comment-spam tooling | n/a | — | — | ✅ (already reused for moderator-feedback comments) | — | Not a *registration* defense — out of scope for R6 |
| Loginizer / login-security retired plugins | — | — | — | — | — | — | **Excluded (C6 don't-list)** |

## 4. The decision — a layered posture

R6 is **defense in depth**, not a single switch. Build order and posture:

1. **Front line — Cloudflare Turnstile (invisible).** Add the Turnstile widget to the registration form via a vetted WP Turnstile plugin (hook-integrated; no `ink-core` logic). It is free, already-in-stack, privacy-respecting, and invisible to real people → satisfies C1/C3/C4/C5 best. **Verify at integration time:** that the widget's locale can be forced to `af` (or that it renders text-free) for C2; capture any residual English string for the Epic-17 leak backlog.
2. **Baseline — email verification (double opt-in).** Require a confirmed email before the account is fully active. The confirmation email is authored in Afrikaans through the existing `ink-core` Notifications form-letter store (Story 1.12) → C2-clean. Low, expected friction.
3. **Supplement — honeypot + submit-timing.** Free, zero-PII, invisible; stacks under Turnstile to catch the simplest bots. Optional but cheap.
4. **Identity path — social login (Story 3.5).** Not a wall but a *trusted-identity, lower-friction* route; a Google/Apple-authenticated account is inherently less likely to be a throwaway. Hooks-only integration.
5. **Escalation — manual-approval backstop (Story 3.6), OFF by default.** If a real abuse wave appears that the above does not stop, the redakteur flips one setting and new accounts queue in "wag op goedkeuring". Default stays frictionless (UJ-1).

Everything above is a **plugin/edge seam wired via hooks** — consistent with architecture.md line 650-652 and the three-layer rule. THE conflation rule is untouched (none of this reads or writes entitlement/tier).

## 5. Gating guidance for the downstream stories

### → Story 3.5 (social login, R6)
- **Integrate a vetted social-login plugin via its hooks — do NOT reimplement OAuth in `ink-core`** (C5). Recommended default: a well-maintained, widely-installed social-login plugin whose free tier covers **Google + Apple** (Apple matters for iOS users) and which exposes hooks/filters for button placement and the post-login user hook. **Confirm the specific plugin is current/maintained and Patchstack-clean at integration time** (knowledge-cutoff caveat — verify before committing to it).
- **Afrikaans (C2):** the provider buttons must show Afrikaans labels ("Meld aan met Google", etc.) sourced from `ui-copy-translations.md` / the terminology registry — never the plugin's English default. Add to the glossary first if a term is missing.
- **POPIA (C3):** add a short Afrikaans consent/notice line that social sign-in shares basic profile data with INK; link to the privacy page.
- **Account defaults:** a socially-registered account must still land at **Brons / gratis lid** with `ink_onboarding_complete` unset (it flows through the same Story 3.1/3.3 path — verify the `user_register` hook still fires for social signups).

### → Story 3.6 (optional manual-approval backstop, R6)
- **Off by default** — signup stays frictionless (UJ-1, C1). The redakteur enables it via a single `ink-core` setting only if abuse warrants.
- When enabled, new accounts enter the **"wag op goedkeuring"** state (glossary-approved Afrikaans, C2) and appear in an admin approval queue.
- It **composes with** the front-line: Turnstile stops bots at the edge; the approval queue is for *human* abuse the automated layer can't judge. The two are independent toggles.

### → Story 18.10 (registration anti-spam hardening, R6) — deferred
- Rate-limiting / IP-reputation rules at the Cloudflare edge, abuse monitoring/alerting (Patchstack), tuning Turnstile sensitivity, and any analytics on blocked attempts. Not needed for launch; the layered posture above is the launch baseline.

## 6. Items to verify at integration time (knowledge-cutoff honesty)
- Cloudflare Turnstile `af` locale support (or confirm it is text-free enough to pass C2).
- The exact social-login plugin choice for 3.5 — confirm it is currently maintained, Patchstack-clean, and free-tier covers the needed providers.
- Whether email double opt-in is best delivered via a BuddyPress setting, a vetted plugin, or a thin `ink-core` hook on `user_register` (a *hook*, not a re-implementation).

## 7. What this spike did NOT do
No plugin was installed, no `ink-core` code / schema / UI was written, and no tests were authored — R6 is a vetted-plugin seam (C5) and this story's only deliverable is this decision. The unit suite was run once to confirm no regression.
