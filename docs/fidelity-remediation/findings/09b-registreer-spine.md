# 09b · registreer — spine and structural findings

| | |
|---|---|
| **Page slug** | `registreer` |
| **Lovable** | `/auth`, **sign-up tab** (`src/pages/Auth.tsx`), captured at `http://localhost:4189/auth` with `--click '[data-audit-id="auth-tab-signup"]' --state signup` |
| **INK** | `https://nuwe-ink.local/registreer/`, captured anonymous |
| **Date** | 2026-10-13 |
| **Phases** | 0–4b (Stage 1 audit) |
| **Treatment** | A. Reference diff. One Lovable page serves two INK pages: this row is the sign-up tab, and row 09a (`meld-aan`) is the sign-in tab. |

Header and footer belong to row 00. They were verified there and are not re-reported here. The one exception is R1, which is about whether this page should carry them at all.

---

## 1. Phase 0 — pairing

Inventory row 09 (`agent-brief.md` §2.1) pairs `/auth` with `/meld-aan` **and** `/registreer`, and the owner has ratified it ("INK splits it in two"). The sign-up surface is the `signup` TabsContent (`Auth.tsx:102-117`). It is reached by clicking the "Sign up" trigger (`Auth.tsx:78`); Radix activates on mousedown, so the harness's `--click` drives it. `page-registreer.html:5` renders `ink-foundation/auth-register`. **Confirmed, Treatment A.**

## 2. Files read end to end

**Lovable** (`/Users/cobus/Development/ink-lovable`)

- `src/App.tsx`: `/auth` → `<Auth />` (`App.tsx:40`), with no layout wrapper.
- `src/pages/Auth.tsx`
- `src/components/ui/tabs.tsx`, `src/components/ui/input.tsx`, `src/components/ui/label.tsx` and `src/components/ui/button.tsx`
- Not read: `hooks/useAuth`, `integrations/*` and `hooks/use-toast`. These are behaviour only and emit no markup at rest.

**INK** (`/Users/cobus/Development/ink-vibe`)

- `wp-content/themes/ink-foundation/templates/page-registreer.html`
- `parts/header.html` and `parts/footer.html`: **not** read (row 00 scope).
- `wp-content/themes/ink-foundation/patterns/auth-register.php`: the only pattern. It contains no `wp:ink/*` block.
- `wp-content/plugins/ink-core/src/Accounts/RegistrationGuard.php`: the only `register_form` hook. It emits the honeypot and timestamp fields (`:86-88, 134-145`).
- `wp-content/plugins/ink-core/src/Accounts/AuthRedirects.php`: registration outcome redirects (`:113-135`).
- `wp-content/plugins/ink-core/src/Accounts/SocialLogin.php`
- `wp-content/themes/ink-foundation/assets/css/auth.css`
- `functions.php`: **partial**, the same ranges as row 09a:
  - `:1-230`
  - `:1380-1680`
  - `:2260-2354`
- The rendered `<main>` of `https://nuwe-ink.local/registreer/`, from a cache-busted curl. It confirms that `register_form` emits only RegistrationGuard's fields, that the social branch does not render, and that the `voltooi`/`fout` branches are absent at rest.
- `theme.json`: searched for attribution only.

## 3. Section spine

| # | Lovable (sign-up tab) | INK `/registreer` | Status |
|---|---|---|---|
| 0 | No site chrome (`App.tsx:40`, `Auth.tsx:66-143`) | Header + footer template parts (`page-registreer.html:1,9`) | **extra** (R1) |
| 1 | Page wrapper, full-height and centred (`Auth.tsx:67-68`) | `<section>` with s-64/s-24 padding and contentSize 480px (`auth-register.php:33-34`) | present (R3) |
| 2 | Brand lock-up (`Auth.tsx:69-72`) | none | **missing** (R2) |
| 3 | Card (`Auth.tsx:74`) | `.is-style-card.ink-auth-card` (`auth-register.php:35-36`) | present |
| 3a | Tabs, sign-up active (`Auth.tsx:75-81, 102`) | none: a separate page | **missing** (R4, SCOPE) |
| 3b | none | h1 "Skep jou rekening" + intro (`auth-register.php:37-43`) | **extra** (R6, R7) |
| 3c | Sign-up form: e-mail; password + "At least 8 characters" hint; "Create account" (`Auth.tsx:103-116`) | `form.ink-auth-form`: gebruikersnaam + hint; e-pos + hint; "Registreer" (`auth-register.php:58-73`) | present, different fields (R8–R12) |
| 3d | none | Honeypot + timestamp fields via `register_form` (`auth-register.php:69`; `RegistrationGuard.php:134-145`), invisible | **extra** (R15) |
| 3e | "or continue with" divider + Google/Apple (`Auth.tsx:120-136`) | Gated social section (`auth-register.php:76-111`), **not rendered** | **missing at runtime** (R13) |
| 3f | none (the sign-in tab does this job) | "Reeds 'n rekening? Teken in" switch line (`auth-register.php:113-117`) | **extra** (R5, MECHANISM) |
| 4 | "← Back to Inkwell" (`Auth.tsx:139-141`) | none | **missing** (R14) |

## 4. Structural findings

| id | class | finding | Lovable | INK |
|---|---|---|---|---|
| R1 | `SCOPE` | INK wraps the registration page in the full site header and footer; Lovable's `/auth` has no site chrome. | `App.tsx:40`; `Auth.tsx:66-143` | `templates/page-registreer.html:1,9` |
| R2 | `MISSING` | The brand lock-up above the card has no INK counterpart. | `Auth.tsx:69-72` | `auth-register.php:33-121` (no counterpart) |
| R3 | `MECHANISM` | Lovable centres the card in a `min-h-screen` flex wrapper inside a `max-w-md` column. INK has no column element: the constrained layout (contentSize 480px) sizes the card itself, and the section pads s-64 rather than centring. | `Auth.tsx:67-68` | `auth-register.php:33-34` |
| R4 | `SCOPE` | No tabs. Sign-up is its own page, and the inventory ratifies the split. The owner should confirm SCOPE over STRUCTURE (`agent-brief.md` §3.4). | `Auth.tsx:75-81, 102, 117-118` | `page-registreer.html:5`; `page-meld-aan.html:5` |
| R5 | `MECHANISM` | Route back to sign-in. Lovable uses the "Sign in" tab trigger at the top of the card; INK uses a text line with an inline link at the bottom. | `Auth.tsx:77` | `auth-register.php:113-117` |
| R6 | `EXTRA` | `<h1>` "Skep jou rekening". Lovable's card has no heading. | none | `auth-register.php:37-39` |
| R7 | `EXTRA` | Intro paragraph. Lovable has none. | none | `auth-register.php:41-43` |
| R8 | `SCOPE` | INK's first field is **Gebruikersnaam** (+ hint). Lovable's sign-up has no username field. WordPress core registration (`register_new_user`) requires `user_login`. The pattern discloses that it POSTs to core's own endpoint and that "auth is USED, never reimplemented" (`auth-register.php:8-11`). | none | `auth-register.php:59-63` |
| R9 | `SCOPE` | INK has **no password field** and so no "At least 8 characters" hint. Core registration e-mails the credentials instead, as the success copy states (`auth-register.php:51`). Adding a password field would change the registration mechanism, not the markup. Owner call. | `Auth.tsx:108-111` | `auth-register.php:58-73` (no password input) |
| R10 | `EXTRA` | INK hints **both** fields (`.ink-auth-hint` under gebruikersnaam and under e-pos); Lovable hints only the password. The first INK hint is paired with Lovable's only hint (`auth-field-hint`) by **role**, not by field, so the hint primitive is measured. The second INK hint has no Lovable counterpart. | `Auth.tsx:111` | `auth-register.php:62, 67` |
| R11 | `STRUCTURE` | Field markup. Lovable uses `<div class="space-y-2">` with a Label, an Input and a `<p>` hint. INK uses `<p class="ink-auth-field">` with a label, an input and a `<span>` hint. Field order also differs: Lovable is e-mail then password; INK is gebruikersnaam then e-pos. | `Auth.tsx:104-112` | `auth-register.php:59-68` |
| R12 | `STRUCTURE` | Submit is wrapped in `<p class="ink-auth-submit">` and is a raw `<button class="wp-element-button">` with no block-style wrapper. Lovable has a Button `variant="literary"` as a direct form child. | `Auth.tsx:113-115` | `auth-register.php:70-72` |
| R13 | `SCOPE` | The divider and OAuth buttons are gated off (`SocialLogin::isAvailable()` defaults to false) and do not render. When they are enabled, INK also adds a POPIA consent note (EXTRA, latent) and builds the divider as `<hr>` + paragraph (MECHANISM, latent). | `Auth.tsx:120-136` | `auth-register.php:76-111`; `SocialLogin.php:74-76` |
| R14 | `MISSING` | The "← Back to Inkwell" link has no INK counterpart. Owner call, tied to R1. | `Auth.tsx:139-141` | `auth-register.php:33-121` (no counterpart) |
| R15 | `SCOPE` | Anti-spam fields: an off-screen honeypot `<p class="ink-reg-hp" aria-hidden="true">` and a hidden timestamp, emitted through `register_form` (Story 18.10). They are not visible and must not be removed for fidelity. Recorded so that their nodes in the capture are accounted for. | none | `auth-register.php:69`; `RegistrationGuard.php:134-145` |
| R16 | `MECHANISM` | Outcome feedback. Lovable shows a toast and navigates. INK redirects back with `?registreer=fout` or `voltooi` and renders an inline notice. On success it also renders a "Teken in" `.ink-auth-secondary` button in place of the form. Not captured (no submission). | `Auth.tsx:49-54` | `auth-register.php:47-57`; `AuthRedirects.php:113-135` |

### Notes on classification

- **R8 / R9** are SCOPE, not MISSING/EXTRA. Both follow from the pattern's documented decision to use WordPress core registration unchanged (`auth-register.php:8-11`). Matching Lovable would mean adding a password-at-signup flow, which is a product decision.
- **R10**: the role pairing of `auth-field-hint` is deliberate. Without it the form-hint primitive would have no measured pair on this page. The map note records that the two hints describe different fields.
- **R15**: the honeypot `<label>` and `<input>` are styled by the form's own `auth.css:106` input rule (they render inside `.ink-auth-form`). `run.sh primitives` filed them as `form-label` and `form-field`. They are excluded from the register as not visually present (`findings/09b-registreer-primitives.md`).

Counts by class: SCOPE 6 (R1, R4, R8, R9, R13, R15); MISSING 2 (R2, R14); EXTRA 3 (R6, R7, R10); MECHANISM 3 (R3, R5, R16); STRUCTURE 2 (R11, R12). **16 total.**

## 5. Join-key coverage

The full table is in `maps/registreer.csv`, and it uses the same `auth-` id set as row 09a.

- **Paired: 8.**
  - `auth`, `auth-card`, `auth-form`
  - `auth-email-field`, `auth-email-label`, `auth-email-input`
  - `auth-field-hint` (role pairing, R10)
  - `auth-submit`

  The generated diff confirms 8 pairs at all three viewports.
- **Lovable-only: 21.**
  - `auth-inner`
  - the 3 logo ids
  - the 6 tab ids
  - `auth-password-field`, `auth-password-label`, `auth-password-input` (R9)
  - the 6 divider and OAuth ids
  - `auth-back`, `auth-back-link`
- **INK-only, rendered: 8.**
  - `auth-heading`, `auth-intro`
  - `auth-username-field`, `auth-username-label`, `auth-username-input`
  - `auth-submit-wrap`
  - `auth-switch`, `auth-switch-link`
- **INK-only, latent** (stamped but not rendered at rest):
  - `auth-notice` (two exclusive branches, `:51`/`:56`) and `auth-secondary-btn`
  - the gated social ids
- **Duplicate-id check (T5):** empty for every capture used. The tab-panel split is described in 09a §5.
- **Gates.** `determinism-check.sh` (3 runs) and `run.sh verify --sample 30` **PASSED** at 390, 768 and 1440 for:
  - `lovable/auth@*-signup` (verify re-drives the recorded click)
  - `ink/registreer@*`

  The hidden `ink_reg_t` timestamp changes on every load but did not break determinism: the capture does not record input values.

## 6. Conclusion and limits

`/registreer` keeps the reference's card and its single-column form rhythm, but it is a different form. INK asks for a **username and an e-mail and no password**, because it delegates to WordPress core registration (R8, R9). Lovable asks for an e-mail and a password with a length hint. Only the e-mail field, one hint (by role), the submit, the card and the section pair.

The framing differences are the same as on row 09a:

- site chrome
- no brand lock-up or back link
- h1 + intro instead of tabs
- a switch line
- gated OAuth

**Phase 4 output:**

- `findings/09b-registreer-drift.md` (1440), `09b-registreer-drift@768.md` and `09b-registreer-drift@390.md`: generated, not hand-edited.
- At 1440 and 768: 70 per-element deltas across 8 paired elements (box 43, colour 13, type 6, micro 8).
- At 390: 69 deltas (box 41, colour 13, type 7, micro 8).

**Phase 4b output:**

- `maps/_primitive-observations.registreer.csv`
- `findings/09b-registreer-primitives.md`

The Lovable `/auth` rows, including the sign-up hint, are recorded once, in the meld-aan register.

**What this document cannot support:**

- No computed-value claims (R4).
- The following were not captured:
  - the `fout` and `voltooi` outcome states (no form was submitted, by instruction)
  - focus and hover states
  - the toast
  - the gated social section
- `parts/header.html` and `parts/footer.html` were not read. R1 rests on the template's `template-part` lines alone.
