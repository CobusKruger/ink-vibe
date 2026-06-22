---
baseline_commit: e4b851c
---

# Story 3.5: Social login (R6)

Status: ready-for-dev

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a besoeker,
I want to sign up / in via a vetted social provider,
so that signup friction is reduced while account abuse is curbed — entirely in Afrikaans. (FR-3a, R6)

## Acceptance Criteria

1. **The auth surfaces present a social-login option that degrades gracefully (the seam, not the OAuth).** Given the Story-3.1 auth patterns (`auth-login` / `auth-register`), when a vetted social-login plugin is active, then the auth surface renders the provider buttons (via the plugin's hook/shortcode, NOT reimplemented in `ink-core`); and when the plugin is **absent or inactive**, the social section **renders nothing / a soft state and never errors** — the e-mail auth path remains fully usable. The social-login section is a graceful-degrading **presentation seam** (mirroring the Story-3.3 first-action-prompt pattern). _[Source: epics.md#Story-3.5 AC "a vetted platform plugin (hooks only, not reimplemented in `ink-core`) ... social-login buttons are present and functional"; 3-4-anti-spam-decision.md §5 (3.5 = integrate a vetted plugin via hooks); architecture.md line 650-652 ("social-login (R6) ... integrated via hooks, not `ink-core` code")]_

2. **Every social-auth string is Afrikaans, sentence case, "jy"-voice, glossary-sourced — zero English leakage.** Given the social-login section (the divider / "of"-line, button labels, and the POPIA consent note), when it renders, then all chrome INK owns is Afrikaans (e.g. "Of gaan voort met …", "Meld aan met Google") sourced from `ui-copy-translations.md` / the terminology registry; where exact microcopy is not yet authored it is flagged `[NEEDS HUMAN AFRIKAANS]` (never invented / AI-translated / lifted from a mock). Any English string the **third-party plugin** emits on its buttons is captured for the Epic-17 leak backlog (its `af` labels are configured at integration time). _[Source: project-context.md Gate D, "No AI-generated Afrikaans", sentence case, "jy"; 3-4-anti-spam-decision.md §5 (Afrikaans buttons + POPIA consent line); docs/ui-copy-translations.md (auth copy)]_

3. **A POPIA consent / data-sharing note accompanies social sign-in.** Given social sign-in shares basic profile data with INK, when the social section renders, then a short Afrikaans notice states this and links to the privacy page — satisfying the POPIA posture the 3.4 decision requires. _[Source: 3-4-anti-spam-decision.md §2 (C3 POPIA) + §5 (3.5 consent note); project-context.md POPIA/privacy]_

4. **A socially-registered account lands at Brons / gratis lid via the existing path — THE conflation rule held, no parallel signup.** Given a social signup, when the account is created, then it flows through the SAME `user_register` default path as e-mail signup ({@see \Ink\Accounts\Registration} already stamps `ink_writer_tier = brons` / gratis lid and leaves `ink_onboarding_complete` unset so Story-3.3 onboarding runs); this story adds **no** parallel default-setter, **no** lidmaatskap/entitlement, **no** `ink_writer_tier` re-write, and **no** reader/writer intent flag (Story 3.2 removed it). `src/Accounts/` keeps **zero `Ink\Entitlement`** reference. _[Source: 3-1-authentication-pages.md (`Registration::applyDefaults` on `user_register`); 3-3 (onboarding flag unset ⇒ runs for social signups too); project-context.md THE conflation rule; epics.md#Story-3.2 (no intent flag)]_

5. **R6 stays a vetted-plugin seam wired via hooks — no OAuth in `ink-core`, no business logic in the theme.** Given R6 is a vetted-plugin seam (architecture line 650-652), when this story is implemented, then `ink-core` adds only a thin availability/seam helper (a filterable `isAvailable()` the theme bridge reads to decide whether to render the social section) — it does **not** implement OAuth, store provider tokens, or reimplement a commodity capability; the theme carries only presentation (tokens + escaped output) and a `class_exists`-guarded bridge. The concrete plugin choice + OAuth-app credentials are a **documented deploy-time integration step** (the repo Composer-assembles third-party plugins at build time; they are git-ignored), exactly as the 3.4 decision flagged. _[Source: architecture.md line 650-652; project-context.md "Hook, don't edit", three-layer separation, "Brownfield ... WP core and third-party plugins are Composer-assembled at build time and git-ignored"; 3-4-anti-spam-decision.md §6 (verify plugin at integration)]_

6. **All new `ink-core` logic ships with authored AND PASSING Pest unit tests.** Given the new `Ink\Accounts\SocialLogin` seam, when the story is implemented, then Pest unit tests are authored under `tests/Unit/Accounts/` (Brain Monkey, namespace `Ink\Tests\Unit\Accounts`) **and executed with `composer test:unit`, and the suite passes before the story is marked done**. The rendered theme surface is covered later by E2E (Story 18.8). Current baseline is **93 passed / 1 skipped**; this story's new cases add to that green total. _[Source: project-context.md testing rule 2026-06-22 (author *and run*; suite must pass; defer-to-18.8 precedent retired); 3-3 baseline 93/1]_

## Tasks / Subtasks

> **Current state (read before starting):**
> - **The `Ink\Accounts` module exists with `Registration` (3.1) + `Onboarding` (3.3) collaborators.** `user_register` already stamps Brons / gratis lid for ANY registration (including social) and leaves onboarding to run. **Do NOT add a parallel social default-setter** — verify the existing path covers social signups and add a focused test for it.
> - **The auth surfaces are token-only theme patterns** (`patterns/auth-login.php`, `patterns/auth-register.php`) using WordPress's own auth mechanism. Extend them with a social section — presentation only.
> - **The theme bridge convention** (`functions.php`): `class_exists`-guarded helpers (`ink_foundation_term`, `ink_foundation_onboarding_*`) that read `ink-core` and degrade to a safe default when the plugin is inactive. Add the social bridge the same way.
> - **R6 is a vetted-plugin seam** (architecture line 650-652) and the 3.4 decision recommends a Google+Apple-capable plugin chosen/configured at deploy time. The plugin is NOT in this repo (Composer-assembled, git-ignored). So this story builds the **seam + Afrikaans surface + graceful degradation + tests** — not the OAuth.
>
> **Scope is the social-login SEAM + Afrikaans button/consent surface + graceful degradation + the social-signup-defaults test ONLY.** Do NOT: implement OAuth or store tokens in `ink-core`; install/configure the third-party plugin in the repo; build anti-spam (3.4 decided), the approval queue (3.6), or any Lidmaatskap/Entitlement coupling; re-stamp the tier; add an intent flag.

- [ ] **Task 1 — Add the `Ink\Accounts\SocialLogin` availability seam (AC: 1, 5)**
  - [ ] Create `src/Accounts/SocialLogin.php` — `namespace Ink\Accounts; final class SocialLogin` (strict types + `ABSPATH` guard). Expose `public static function isAvailable(): bool` = `(bool) apply_filters( 'ink_social_login_available', false )` — **default false ⇒ graceful degradation**; a vetted plugin (or a thin deploy-time glue mu-plugin) flips the filter true. Document the filter as the seam contract. NO OAuth, NO token storage, NO entitlement reference.
  - [ ] Wire it from `Module::register()` only if it needs a hook; if it is read-only (theme bridge calls the static), do NOT add an orphan `register()` (Epic-2 retro: avoid gold-plating). Update the `Module` doc-comment to name 3.5's social-login seam as in-scope.

- [ ] **Task 2 — Render the social section in the auth patterns (presentation only) (AC: 1, 2, 3)**
  - [ ] Extend `patterns/auth-login.php` and `patterns/auth-register.php` with a social section: an Afrikaans divider ("Of gaan voort met …"), a hook/bridge point that renders the plugin's buttons when available and **nothing** when not (graceful), and the **POPIA consent** line linking to the privacy page. Tokens only, all output escaped, no business logic.
  - [ ] Add `ink_foundation_social_login_available()` + `ink_foundation_social_login_buttons()` bridges in `functions.php` (`class_exists`/`function_exists`-guarded): the first reads `SocialLogin::isAvailable()`; the second fires the plugin's render hook (e.g. `do_action( 'ink_social_login_buttons' )`) so the active plugin paints its buttons — degrading to no output when absent.
  - [ ] Afrikaans, sentence case, "jy"-voice from `ui-copy-translations.md` / the registry; flag unauthored microcopy `[NEEDS HUMAN AFRIKAANS]`; capture any plugin-emitted English for the Epic-17 backlog.

- [ ] **Task 3 — Verify social signups inherit INK defaults (AC: 4)**
  - [ ] Confirm (and test) that a social registration flows through the existing `user_register` → `Registration::applyDefaults()` path (Brons / gratis lid, onboarding flag unset) with **no** parallel setter, **no** entitlement, **no** tier re-stamp, **no** intent flag. Document the finding; do not duplicate the default-setter.

- [ ] **Task 4 — Author AND RUN the Pest unit tests (AC: 1, 4, 5, 6)**
  - [ ] Create `tests/Unit/Accounts/SocialLoginTest.php` (namespace `Ink\Tests\Unit\Accounts`, Brain Monkey): `isAvailable()` defaults false (graceful) and returns true when the `ink_social_login_available` filter is set true; the seam writes no user-meta and references no `Ink\Entitlement`. Assert (extend `RegistrationTest` or add a focused case) that the social-signup default path is the existing `applyDefaults` (Brons / gratis lid, no extra keys).
  - [ ] Run `composer test:unit` and ensure the suite passes (baseline 93 passed / 1 skipped; new cases add to the green total). Record the command + result in the Dev Agent Record.

- [ ] **Task 5 — Verification & guardrail sweep (AC: 1–6)**
  - [ ] `php -l` + `phpcs` + `phpstan` clean on changed `ink-core` files. strict types + `ABSPATH` guard + `Ink\Accounts` namespace + no closing `?>`; no raw superglobals; `src/Accounts/` has no `use Ink\Entitlement`.
  - [ ] Scope discipline: no OAuth/token code in `ink-core`; no plugin installed in the repo; no approval queue / anti-spam built here; no tier re-stamp; theme carries no business logic and degrades gracefully (renders nothing when the plugin is absent, never errors).
  - [ ] Afrikaans/no-English sweep on the new social chrome; list `[NEEDS HUMAN AFRIKAANS]` gaps and any plugin-emitted English for the Epic-17 backlog. Confirm the POPIA consent note is present and Afrikaans.

## Dev Notes

### What this story is (and is NOT)

- **IS:** the **social-login seam** — a thin `ink-core` availability helper (`SocialLogin::isAvailable()`, filter-driven, default-off) + the **Afrikaans social section** on the auth patterns (divider, plugin-button bridge, POPIA consent) that **degrades gracefully** when the vetted plugin is absent + a test confirming social signups inherit INK's Brons / gratis-lid defaults via the existing path. _[epics.md#Story-3.5; 3-4-anti-spam-decision.md §5; architecture.md line 650-652]_
- **IS NOT:** OAuth implementation or token storage in `ink-core` (the plugin owns that); installing/configuring the third-party plugin in the repo (deploy-time integration step — Composer-assembled, git-ignored); anti-spam (3.4, decided), the approval queue (3.6), Lidmaatskap/Entitlement coupling (Epic 4), a tier re-stamp, or an intent flag (3.2 removed).

### The decided approach (from the 3.4 spike — do not re-litigate)

- R6 social login is a **vetted-plugin seam via hooks** (architecture line 650-652). The 3.4 decision recommends a maintained, Patchstack-clean plugin whose free tier covers **Google + Apple**, chosen/configured at deploy time. This story builds the INK-side seam + Afrikaans surface + graceful degradation; the plugin + OAuth-app credentials are the documented integration step.
- Afrikaans buttons + a POPIA consent line are required (3.4 §5). Socially-registered accounts land Brons / gratis lid via the existing `user_register` path.

### Guardrails

- **Graceful degradation is core:** the social section renders the plugin's buttons when available and **nothing** (no error) when absent — the e-mail path always works. Mirror the Story-3.3 seam pattern. _[architecture line 650-652; EXPERIENCE.md graceful states]_
- **No OAuth / tokens / commodity reimplementation in `ink-core`; hook, don't edit.** _[project-context.md]_
- **EXTEND the existing `Ink\Accounts` module + auth patterns;** no parallel module, no parallel default-setter. _[3-1; 3-3]_
- **No tier re-stamp, no lidmaatskap, no entitlement, no `Ink\Entitlement` reference; no intent flag.** _[conflation rule; 3.2]_
- **Afrikaans-only, zero leakage; never invent/AI-translate;** flag `[NEEDS HUMAN AFRIKAANS]`; capture plugin English for Epic 17. _[Gate D]_
- **strict types + `ABSPATH` guard;** tokens-only escaped theme; tests run and pass before done. _[project-context.md]_

### Previous-story intelligence

- **`Registration::applyDefaults()` (3.1)** already stamps Brons / gratis lid on `user_register` for ANY registration — social signups inherit it; do not duplicate.
- **`Onboarding` (3.3)** leaves `ink_onboarding_complete` unset by default, so onboarding runs for social signups too.
- **Theme bridge house style (3.3):** `class_exists`-guarded `functions.php` helpers that degrade to a safe default. **Terminology registry (2.0):** labels from `ink_foundation_term()` / the registry; a new term goes in the glossary first.
- **Recurring bug classes (Epic-2 retro):** avoid orphan/gold-plated facade methods (only add `isAvailable()` because the theme bridge consumes it); run the tests (do not defer).

### Project Structure Notes

- **New (`ink-core`):** `src/Accounts/SocialLogin.php`. **Edited:** `src/Accounts/Module.php` (doc-comment; wire only if a hook is needed).
- **Edited (theme):** `patterns/auth-login.php`, `patterns/auth-register.php` (social section), `functions.php` (social bridges).
- **New (tests):** `tests/Unit/Accounts/SocialLoginTest.php`; possibly extend `RegistrationTest.php` for the social-defaults assertion.

### Testing standards summary

- Pest runs now (`composer test:unit`, Brain Monkey, no WP/DB). Author the `SocialLogin` tests AND run them; the suite must pass before done (baseline 93/1).
- **Unit scope:** `isAvailable()` graceful default + filter override; the seam writes nothing / no entitlement; social signup uses the existing default path. **Out of unit scope → E2E (18.8):** the rendered social buttons + a real OAuth round-trip (needs the live plugin + creds).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Epic-3 + #Story-3.5 — "As a besoeker, I want to sign up / in via a vetted social provider ... a vetted platform plugin (hooks only, not reimplemented in `ink-core`) ... social-login buttons are present and functional"; #Story-3.4 (the gating spike); #Story-3.6 (approval backstop — separate)]
- [Source: _bmad-output/planning-artifacts/architecture.md — line 650-652 ("social-login (R6) ... integrated via hooks, not `ink-core` code ... R6 → Accounts/Epic 3"); Epic→Location (Epic 3 = WP-native auth + `ink-foundation/templates` + `ink-core`); AD-1 (module = dir + bootstrap + `Api` facade)]
- [Source: _bmad-output/implementation-artifacts/3-4-anti-spam-decision.md — §2 (Turnstile front line; social login as identity assurance; C3 POPIA); §5 (3.5 gating: vetted Google+Apple plugin via hooks, Afrikaans buttons, POPIA consent, Brons/gratis-lid defaults, verify plugin at integration); §6 (verify-at-integration)]
- [Source: _bmad-output/project-context.md — three-layer separation, "Hook, don't edit", "WP core and third-party plugins are Composer-assembled at build time and git-ignored", Gate D / "No AI-generated Afrikaans" / sentence case / "jy", THE conflation rule, POPIA/privacy, testing rule 2026-06-22]
- [Source: _bmad-output/implementation-artifacts/3-1-authentication-pages.md — `Ink\Accounts` module + `Registration::applyDefaults` (Brons/gratis lid on `user_register`); token-only auth patterns using WP's auth mechanism]
- [Source: _bmad-output/implementation-artifacts/3-3-registration-lifecycle-onboarding.md — the graceful-degrading seam pattern + `class_exists`-guarded theme bridges; baseline 93 passed / 1 skipped]
- [Source: docs/ui-copy-translations.md — auth copy ("Skep jou rekening", "Meld aan"); the social divider / button / consent microcopy is the documented `[NEEDS HUMAN AFRIKAANS]` gap until authored]
- [Source: docs/afrikaans-terms.md — a new user-facing term must be glossary-approved before it appears in UI]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m] (Opus 4.8, 1M context)

### Debug Log References

_(to be completed by the dev agent — record `composer test:unit` green, `php -l`, `phpcs`, `phpstan`)_

### Completion Notes List

_(to be completed by the dev agent)_

### File List

_(to be completed by the dev agent)_

## Change Log

| Date | Change |
|---|---|
| 2026-06-22 | Story created (context-engineered) — social-login (R6) as a graceful-degrading SEAM (per the 3.4 decision + architecture line 650-652): a thin `Ink\Accounts\SocialLogin::isAvailable()` filter helper (default-off ⇒ degrades) + an Afrikaans social section on the auth patterns (divider, plugin-button bridge, POPIA consent) + a test that social signups inherit Brons/gratis-lid defaults via the existing `user_register` path. NO OAuth/tokens in `ink-core`, NO plugin install (deploy-time integration step, Composer-assembled/git-ignored), NO approval queue (3.6) / anti-spam (3.4) / Entitlement coupling / tier re-stamp / intent flag. Pest tests authored AND run. Status → ready-for-dev. |
