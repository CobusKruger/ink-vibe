# 09b · registreer — primitive observations (Phase 4b)

**Register:** `maps/_primitive-observations.registreer.csv`, **14 rows**: **13 `ink`** and **1 `source`**.

The register has **no `lovable` rows**. The shared `/auth` reference rows, including this page's reference form hint (`auth-field-hint`, sign-up state), are recorded once, in `maps/_primitive-observations.meld-aan.csv`, so Stage 2 does not count them twice.

The same applies to the `auth.css`/`functions.php`/`theme.json` source rows that this page shares with `/meld-aan`. They live in that register too. The one definition added here is `.ink-auth-hint`, which `/meld-aan` does not use.

**Inputs:**
- `tmp/fidelity-captures/ink/registreer@{390,768,1440}.ndjson`
- for the reference: `tmp/fidelity-captures/lovable/auth@{390,768,1440}-signup.ndjson`

All captures passed the determinism gate and `verify`. Rows that are identical across viewports are collapsed.

## 1. Hand resolution of the raw tool output (T6 class slips)

| Raw tool row | Resolution |
|---|---|
| Unkeyed `form-label` + `form-field` at 16px / 348px wide | **Dropped.** This is RegistrationGuard's honeypot (`<p class="ink-reg-hp" aria-hidden="true" style="position:absolute;left:-9999px">`, `RegistrationGuard.php:136-138`). It sits off-screen and is not a visible primitive instance. Spine R15. |
| INK submit → generic `button` | `button-primary` |
| INK `.ink-auth-hint` ×2: not emitted | `form-hint` ×2, added by hand plus `margin-top`. The first carries `auth-field-hint`; the second has no id (repeater). |
| INK switch-line link: not emitted | `link-underline`, added by hand |

## 2. Source attribution

The attribution matches `/meld-aan` for every shared selector (`findings/09a-meld-aan-primitives.md` §2). This page adds one selector:

| INK selector | defined_in | load_scope | used_in |
|---|---|---|---|
| `.ink-auth-hint` (form-hint) | `assets/css/auth.css:140` | `is_page(['meld-aan','registreer','wagwoord-herstel'])` (`functions.php:173`) | `auth-register.php`, `auth-forgot-password.php` |

On this page, form-field covers two inputs (gebruikersnaam, e-pos) and form-label covers two labels. Both are attributed to `auth.css:106` and `auth.css:74`.

## 3. The two checks

### 3.1 Declaration comparison

- **form-hint.** There is one definition, `auth.css:140`. Against Lovable's sign-up hint:

  | Property | Lovable | INK |
  |---|---|---|
  | size | 12 | 12 |
  | weight | 400 | 400 |
  | font family | Inter | Inter |
  | margin-top | 8 (`space-y-2`) | 4 (`s-4`) |
  | line-height | 16 | 18 |
  | colour | 103,111,126 (`--muted-foreground`) | 107,114,128 (`muted-text`) |

  Size, weight and family match. Both INK hints compute identically, and the INK hint is a `<span display:block>` where Lovable's is a `<p>`.
- **card, heading, form-label, form-field, button-primary, link-underline.** These are the same definitions and the same computed values as on `/meld-aan`. The register rows are identical apart from audit-ids, so the comparison in `09a-meld-aan-primitives.md` §3.1 applies unchanged. Specifically:
  - The submit is the same raw `button.wp-element-button`: font-size 16 vs 14, no shadow.
  - The inputs have no line-height and no 16px step below md.
  - The card carries two definitions plus the dead hover rule.

### 3.2 Load-scope cross-check

`.ink-auth-hint` is emitted only by `auth-register.php` and `auth-forgot-password.php`. Their pages, `/registreer` and `/wagwoord-herstel`, are both inside the `auth.css` gate. **There is no live load-scope bug.**

The latent risk is the one recorded in 09a §3.2: the auth patterns can be inserted from the inserter onto pages outside the gate.

## 4. Vocabulary

There is no new kind on this page. `divider` (README §4.1, rows 09a/09b) comes from the shared `/auth` reference, and its rows are in the meld-aan register.

## 5. Recorded, not fixed (R8)

- All the PRIMITIVE items in `09a-meld-aan-primitives.md` §5 apply here. They are the same definitions.
- **form-hint** has only one definition (`auth.css:140`). It is page-gated rather than site-wide, so it is a candidate for the shared layer, alongside Lovable's `text-xs text-muted-foreground` recipe.
