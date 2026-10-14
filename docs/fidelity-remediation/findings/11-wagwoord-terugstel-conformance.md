# 11 · wagwoord-terugstel — conformance audit (Treatment C, Stage 1 half)

| | |
|---|---|
| Page slug | `wagwoord-terugstel` (inventory row 11) |
| INK | `https://nuwe-ink.local/wp-login.php?action=lostpassword` (owner ruling: core wp-login flow, branded via `wp-login-brand.css`) |
| Lovable | `/reset-password` (`src/pages/ResetPassword.tsx`). Captured for the register only. Treatment C has **no spine and no diff** against it (README §4.6) |
| Date | 2026-10-13 |
| Phases | 1, 3, 4, 4b, plus the `OFF-TOKEN` check. `OFF-RECIPE` waits for Stage 4 |
| Treatment | **C** |

## 1. What this URL actually renders

This is measured, not inferred. The evidence is a cache-busted `curl` (sandbox off) plus the capture.

| Request | Result |
|---|---|
| `GET /wp-login.php?action=lostpassword` | **200**, WordPress core's own `login_header()` screen: `<body class="login … login-action-lostpassword wp-core-ui">`, title "Verlore Wagwoord ‹ INK — WordPress". **Not intercepted**: no redirect to `/wagwoord-herstel/` or `/meld-aan/` |
| `GET /wp-login.php` (no action) | **200**, core login screen, also not intercepted. README §9 says `wp-login.php` is "intercepted … and sent to `/meld-aan`". For a GET that is not what happens: `AuthRedirects` only hooks `wp_login_failed`, `lost_password` and `login_form_register` (`AuthRedirects.php:58-65`) |
| `GET /wp-login.php?action=rp` / `?action=resetpass` (no valid key) | 302 → `?action=lostpassword&error=invalidkey` → 302 (`AuthRedirects::lostPasswordFailed`, since `lost_password` fires with the error) → **`/wagwoord-herstel/?wagwoord=fout`** |

Consequences:

1. **`wp-login-brand.css` does not load on this URL.** `functions.php:531-547` enqueues it only for `action ∈ {rp, resetpass}`, as `wp-login-brand.css:6-18` intends. The page is therefore **unbranded core**: the WordPress "W" logo, admin blue `#3858E9` and system fonts.
2. **The branded screen (`action=rp` with a valid key) cannot be reached in Stage 1.** It needs a reset key, which means writing `user_activation_key` (a data write, out of bounds). It was **not captured**. Without a valid key, the journey lands on row 10's `fout` state, so row 10's captures cover what a user with a stale link sees.
3. **Copy and links on the core screen**, recorded for the owner and not changed (R2):
   - the nav link reads **"Meld aan"**, the term retired sitewide (`auth-login.php:26-33`), and points to `wp-login.php`, i.e. the core login screen again;
   - "Registreer" points to **`/register/`**, which responds 200 with `<title>Page not found - INK</title>` (a BuddyPress `registration register` body class on `page-id-0`);
   - the language switcher's button reads **"Change"** (English), and the `<select>` lists plugin text-domain entries such as `real3d-flipbook-af` and `woocommerce-memberships-af` as if they were languages.

## 2. Files read end to end

- **INK:**
  - `assets/css/wp-login-brand.css`;
  - `plugins/ink-core/src/Accounts/AuthRedirects.php`;
  - `functions.php:520-560` (enqueue gate + `login_headerurl`), a partial read;
  - the live HTML of `wp-login.php?action=lostpassword` (saved to `tmp/fidelity-password/r11-lost.html`).
  - Core's own `login.min.css`, `forms.min.css` and `buttons.min.css` were **not** read. No source claim rests on them; every value below is measured.
- **Lovable:** `src/pages/ResetPassword.tsx`, `src/components/ui/{button,input,label}.tsx`.

## 3. Join key

The page is WordPress core markup emitted by `wp-login.php`. The theme cannot add attributes to it: there is no render filter for these nodes, and `login_headertext` changes text only. **No `data-audit-id` was added on the INK side.** The capture runs `--mode all` (32 nodes, 0 audit-ids), as the harness allows.

On the Lovable side, `ResetPassword.tsx` received the shared `auth-*` ids (`auth`, `auth-inner`, `auth-logo*`, `auth-card`, `auth-heading`, `auth-intro`, `auth-form`, `auth-password-field/-label/-input`, `auth-field-hint`, `auth-submit`) plus `auth-notice` and the new `auth-notice-link` for the not-ready message. Only the not-ready state renders without a recovery session: 10 ids are captured, and the form ids are not.

## 4. Gates

INK `wagwoord-terugstel` and Lovable `wagwoord-terugstel` at 390/768/1440 both pass determinism (3 runs) and `verify --sample 30`, 6/6.

Caveat: core autofocuses `#user_login` on load (`r11-lost.html:71`), so the input is captured **in its `:focus` state**: blue border plus a 2px ring. This state is deterministic, and it is how every visitor first sees the field.

## 5. OFF-TOKEN

- **Script:** `tmp/fidelity-password/off-token.py`, run as `python3 -I tmp/fidelity-password/off-token.py <capture> [--scope-path <p>] [--label <l>]`.
- **Token set:** `theme.json` `settings` (palette, fontSizes incl. fluid ranges, fontFamilies, spacingSizes, `custom.radius`, `custom.fontWeight`, shadow presets) ∪ `docs/design-handoff/tokens/theme-tokens.json` (light).
- **What it tests:** every rendered node's `color`, `background-color`, `border-top-color` (only where a border is drawn), font-size, first font-family, font-weight, margins/paddings/gaps, the four radii and `box-shadow`.
- **What it skips:**
  - 0-size nodes;
  - transparent colours;
  - equal non-zero left/right margins (resolved `margin: auto`).
- **Control:** the same script on row 10's INK auth section (`wagwoord-herstel@1440`, subtree `1/1/0`, 13 nodes) reports **0 off-token values**. The checker discriminates; it is not just noisy.

**Headline: every one of the 22 tested nodes carries at least one off-token value (100%).** At 1440 there are 32 distinct off-token values. Nothing on the screen is in the design language:

- family: the system stack, not Inter/Lora;
- base size: 13px, not on the scale;
- text: `#3C434A`, not `ink-text`;
- primary: core blue `#3858E9`, not `primary`;
- radius: 2px, not on the scale;
- surfaces: `#F0F0F1` and `#FFF`;
- shadow: `0 1px 3px rgba(0,0,0,.04)`, not on the scale.

The output below is **generated** (`tmp/fidelity-password/off-token-r11.md`, which holds all three viewports). It is reproduced verbatim for 1440.

### OFF-TOKEN — `wagwoord-terugstel INK @1440`

- Capture: `tmp/fidelity-captures/ink/wagwoord-terugstel@1440.ndjson` — https://nuwe-ink.local/wp-login.php?action=lostpassword @ 1440px, mode `all`
- Nodes tested: **22** (skipped 10 rendered at ≤1px: hidden inputs, `<option>`s, screen-reader text)
- Distinct off-token values: **32**, on 22 nodes — colour 11, font-size 1, font-family 2, spacing 12, shadow 2, radius 4

| # | category | property | computed value | nodes | first node |
|---|---|---|---|---|---|
| OT1 | colour | `color` | `rgb(60, 67, 74)` | 15 | `body.login.js @body` |
| OT2 | colour | `background-color` | `rgb(240, 240, 241)` | 1 | `body.login.js @body` |
| OT3 | font-size | `font-size` | `13px` | 15 | `body.login.js @body` |
| OT4 | font-family | `font-family` | `-apple-system` | 21 | `body.login.js @body` |
| OT5 | spacing | `padding-top` | `72px` | 1 | `div @2` |
| OT6 | colour | `background-color` | `rgb(255, 255, 255)` | 4 | `div.notice.notice-info @2/1` |
| OT7 | spacing | `margin-bottom` | `20px` | 1 | `div.notice.notice-info @2/1` |
| OT8 | spacing | `margin-top` | `6.5px` | 1 | `p @2/1/0` |
| OT9 | spacing | `margin-bottom` | `6.5px` | 1 | `p @2/1/0` |
| OT10 | colour | `border-top-color` | `rgb(195, 196, 199)` | 1 | `form @2/2` |
| OT11 | spacing | `padding-top` | `26px` | 1 | `form @2/2` |
| OT12 | spacing | `padding-bottom` | `26px` | 1 | `form @2/2` |
| OT13 | shadow | `box-shadow` | `rgba(0, 0, 0, 0.04) 0px 1px 3px 0px` | 1 | `form @2/2` |
| OT14 | spacing | `margin-bottom` | `3px` | 2 | `label @2/2/0/0` |
| OT15 | colour | `color` | `rgb(30, 30, 30)` | 2 | `input.input.ltr @2/2/0/1` |
| OT16 | colour | `border-top-color` | `rgb(56, 88, 233)` | 3 | `input.input.ltr @2/2/0/1` |
| OT17 | spacing | `padding-top` | `3px` | 1 | `input.input.ltr @2/2/0/1` |
| OT18 | spacing | `padding-right` | `5px` | 1 | `input.input.ltr @2/2/0/1` |
| OT19 | spacing | `padding-bottom` | `3px` | 1 | `input.input.ltr @2/2/0/1` |
| OT20 | spacing | `padding-left` | `5px` | 1 | `input.input.ltr @2/2/0/1` |
| OT21 | radius | `border-top-left-radius` | `2px` | 4 | `input.input.ltr @2/2/0/1` |
| OT22 | radius | `border-top-right-radius` | `2px` | 4 | `input.input.ltr @2/2/0/1` |
| OT23 | radius | `border-bottom-right-radius` | `2px` | 4 | `input.input.ltr @2/2/0/1` |
| OT24 | radius | `border-bottom-left-radius` | `2px` | 4 | `input.input.ltr @2/2/0/1` |
| OT25 | shadow | `box-shadow` | `rgb(56, 88, 233) 0px 0px 0px 2px` | 1 | `input.input.ltr @2/2/0/1` |
| OT26 | colour | `color` | `rgb(255, 255, 255)` | 1 | `input.button.button-primary @2/2/2/0` |
| OT27 | colour | `background-color` | `rgb(56, 88, 233)` | 1 | `input.button.button-primary @2/2/2/0` |
| OT28 | colour | `color` | `rgb(80, 87, 94)` | 3 | `a.wp-login-log-in @2/3/0` |
| OT29 | spacing | `margin-right` | `3.5px` | 2 | `label @3/0/0` |
| OT30 | font-family | `font-family` | `dashicons` | 1 | `span.dashicons.dashicons-translation @3/0/0/0` |
| OT31 | colour | `border-top-color` | `rgb(148, 148, 148)` | 1 | `select @3/0/1` |
| OT32 | colour | `color` | `rgb(56, 88, 233)` | 1 | `input.button @3/0/3` |

**@768: 33 distinct. @390: 34 distinct.** These are the same set with viewport-dependent spacing:

- the `#login` top padding is 38.39px and 19.5px;
- the submit and "Change" buttons get 14px side padding below 1440;
- at 390, the "Change" button gets a 5px top margin.

Full tables are in `tmp/fidelity-password/off-token-r11.md`.

**Notes.**

- `OT30` (`dashicons`) is an icon font, a legitimate non-token family. It is listed only for completeness.
- `OT16`/`OT25` are the focus treatment (see §4).
- The theme's own fix for this screen family, `wp-login-brand.css`, is written in **hand-copied literals** rather than tokens (Gate A relaxed, `wp-login-brand.css:20-23`). Even where it loads, its button recipe (44px tall, `0 32px` padding) does not match the 40px, 8/16 auth-page submit (`auth.css:207`).

## 6. Findings

| ID | Class | Finding | Evidence |
|---|---|---|---|
| T1 | `OFF-TOKEN` | Every rendered node on `?action=lostpassword` is off-token (§5): system font stack, 13px base, core admin palette, 2px radius, core shadow | `off-token-r11.md` |
| T2 | `PRIMITIVE` (load scope) | `wp-login-brand.css` selectors (`body.login`, `#login h1 a`, `input[type=text]`, `.button-primary`, `#nav a`, `#backtoblog a`) match nodes on this URL, but the sheet is gated to `rp`/`resetpass` (`functions.php:534`). They are inert here. Its form selector also names only `#loginform`/`#resetpassform` (`:42`), not `#lostpasswordform` | register `source` rows |
| T3 | `SCOPE` (owner) | The owner ruling pairs `/reset-password` with "the core wp-login flow". The branded part of that flow (`action=rp` + valid key) needs a data write to reach and was not audited. The unbranded part (`action=lostpassword`, plain `wp-login.php`) is publicly reachable and is not redirected. Owner to decide whether `?action=lostpassword` should redirect to `/wagwoord-herstel/` (the way failures already do) or be branded | §1 |
| T4 | for owner (copy / links, R2) | "Meld aan" (retired term), "Registreer" → `/register/` titled "Page not found", English "Change", and plugin text-domains listed as languages in the switcher | §1 |
| T5 | for owner | The README §9 claim that `wp-login.php` is intercepted to `/meld-aan` does not hold for GETs (observed 200). Harness `login` posts to `/meld-aan`, so captures are unaffected | §1 |

Every component here lacks a `maps/_recipes.csv` entry, because that file does not exist until Stage 2. `OFF-RECIPE` is therefore not assessed (README §4.6). Primitives are in `11-wagwoord-terugstel-primitives.md`.

## 7. Limits

- No claim is made about the `action=rp` screen with a valid key, the reset e-mail, the password-strength meter, or the `wp-login.php?checkemail=confirm` screen (not reachable without a submission).
- Dark mode is not covered.
- The Lovable `/reset-password` capture shows only its not-ready state. The form state needs a Supabase recovery session.
