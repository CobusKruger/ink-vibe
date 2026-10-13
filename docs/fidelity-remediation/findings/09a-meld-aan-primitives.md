# 09a · meld-aan — primitive observations (Phase 4b)

**Register:** `maps/_primitive-observations.meld-aan.csv` has **51 rows**:

- **19 `lovable`.** These are the shared `/auth` reference rows. They are recorded **once**, here, and they include the sign-up tab's form hint, which is row 09b's reference.
- **14 `ink`.**
- **18 `source`.** These are the `auth.css` / `functions.php` / `theme.json` definitions that this page and `/registreer` share. They are recorded once, here.

**Inputs:**

- `tmp/fidelity-captures/lovable/auth@{390,768,1440}.ndjson`
- `tmp/fidelity-captures/lovable/auth@{390,768,1440}-signup.ndjson`
- `tmp/fidelity-captures/ink/meld-aan@{390,768,1440}.ndjson`

All of these passed the determinism gate and verify. The raw rows come from `run.sh primitives --exclude-chrome`; the hand resolution below was applied on top, and rows that are identical across viewports were collapsed (`390|768|1440`).

## 1. Hand resolution of the raw tool output (T6 class slips)

| Raw tool row | Resolution |
|---|---|
| Lovable TabsTrigger ×2 → generic `button` | `tab`. The note says active or inactive. |
| Lovable TabsList: not emitted | `tab` (the tab-list track), added by hand |
| Lovable divider label wrapper `.relative.flex.justify-center.text-xs.uppercase` → `eyebrow` | dropped; it is the divider label's wrapper, not an eyebrow |
| Lovable divider label span `.bg-card.px-2` → `card` | `divider` (new kind, §4), added with text properties |
| Lovable divider rule `.border-t`: not emitted | `divider`, added by hand |
| Lovable "Forgot?" link: not emitted | `link-underline`, added by hand, plus `text-decoration-line` |
| Lovable "← Back to Inkwell": not emitted | `nav-link`, added by hand |
| Lovable sign-up password hint: not emitted | `form-hint`, added by hand, plus `margin-top` |
| Lovable page wrapper: not emitted | `section-band`, added by hand, for comparison with INK's `auth` section |
| Lovable sign-up-state duplicates of every default-state row | dropped. The tab rows only swap active and inactive, which are the same two variants. |
| INK submit → generic `button` | `button-primary` (raw `button.wp-element-button`) |
| INK remember-me `<label>` → `form-label` | kept. The note says it is a checkbox label. |
| INK forgot link and switch link: not emitted | `link-underline`, added by hand |

Not registered:

- the Lovable wordmark (`auth-logo-text`). It is brand, not a primitive, and the header logo is row 00's.
- the INK h1 intro paragraph, which is body copy.

## 2. Source attribution

| INK selector | defined_in | load_scope | used_in |
|---|---|---|---|
| `.wp-block-group.is-style-card.ink-auth-card` (card) | `functions.php:1431`; `assets/css/auth.css:54`; `assets/css/auth.css:59` | site-wide; `is_page(['meld-aan','registreer','wagwoord-herstel'])` (`functions.php:173`) | `auth-login.php`, `auth-register.php`, `auth-forgot-password.php` |
| `.wp-block-heading.has-xxl-font-size` (heading) | `theme.json` elements.heading + h1; `auth.css:37` | site-wide; the auth gate | the 3 auth patterns |
| `.ink-auth-field label` (form-label) | `auth.css:74` (+ `:92` for the row label) | the auth gate | the 3 auth patterns |
| `.ink-auth-remember label` (form-label) | `auth.css:175` | the auth gate | `auth-login.php` |
| `.ink-auth-form input[…]` (form-field) | `auth.css:106` (+ `:131` focus) | the auth gate | the 3 auth patterns |
| `.ink-auth-remember input[type=checkbox]` (checkbox) | `auth.css:184` | the auth gate | `auth-login.php` |
| `.wp-element-button` in `.ink-auth-submit` (button-primary) | `theme.json` elements.button; `auth.css:207` (+ `:219` hover, `:223` focus) | site-wide; the auth gate | the 3 auth patterns |
| `.ink-auth-field__forgot` (link-underline) | `auth.css:96` (+ `:102` hover); `theme.json` elements.link | the auth gate; site-wide | `auth-login.php` |
| switch-line `a` (link-underline) | `theme.json` elements.link | site-wide | `auth-login.php`, `auth-register.php` |
| `auth` section (section-band) | inline block style, `auth-login.php:36-37` | inline | `auth-login.php` |

`used_in` was established by searching `patterns/` and `ink-core/src/` for every `ink-auth-*` class. Each hit was read. The only emitters are the three auth patterns; no `ink-core` renderer emits an `ink-auth-*` class.

## 3. The two checks

### 3.1 Declaration comparison

Each kind is compared declaration by declaration. Lovable values are the reference; INK values are as measured.

- **card.** On INK, **two definitions** sit on one element:
  - `functions.php:1431` sets padding s-24.
  - `auth.css:54` re-declares the padding as s-32 `!important`, because the block style's two-class selector would otherwise win (the disclosure is at `auth.css:47-53`).

  Against Lovable:
  - Padding (32) and radius (8) match.
  - Shadow differs. Lovable's is Tailwind `shadow-sm`, `0 1px 2px rgba(0,0,0,.05)`. INK's `--shadow--sm` is `0 2px 12px -2px rgba(24,29,37,.06)`. The INK token named `sm` is not Lovable's `shadow-sm`.
  - Background (253,253,252 vs 253,252,250) and border colour (231,225,218 vs 232,228,220) differ by token.
  - Width differs: 448 (Lovable `max-w-md`) against 480 (INK contentSize).

  `auth.css:59` (`.ink-auth-card:hover{transform:none; …}`) neutralises a hover lift that **no `.is-style-card` rule declares**. The lift its comment cites (`auth.css:44-46`) does not exist in `theme.json` styles.css, in `functions.php`, or in `assets/css`. Only `is-style-ink-card` (`functions.php:1652`) and Ontdek-scoped selectors lift. The rule is dead weight, and so is the comment.
- **button-primary.** INK's submit draws on **three button-primary definitions** across the theme:
  - `theme.json` elements.button (colours, radius, display font)
  - `functions.php:1488` `is-style-ink-primary` (adds `shadow.md`, hover and focus)
  - `auth.css:207` (size, weight, width; plus hover `:219` and focus `:223`, which restate `functions.php:1497`/`:1500`)

  The auth button uses the first and third, not the registered style. Against Lovable:
  - Padding 8/16, radius 6, background, font family and weight 500 all **match**.
  - **font-size 16 vs 14** and **line-height 24 vs 20**: no definition declares a size.
  - **color 253,252,250 vs 255,255,255**: `surface-alt` against Lovable's `primary-foreground`.
  - **box-shadow none vs `0 4px 20px -4px rgba(24,29,37,.08)`**: Lovable's `shadow-soft` equals INK's `shadow.md`, which `is-style-ink-primary` applies and `auth.css` does not.
- **form-field.** Padding 8/12, radius 6, 1px border, 40px height and font family match. The differences:
  - Background 250,248,245 (Lovable `--background`) vs 248,246,242 (INK `surface`).
  - Border colour.
  - `display` flex vs inline-block.
  - line-height 20 vs `normal`: `auth.css:106` declares none.
  - At **390**, font-size 16 vs 14. Lovable's `text-base md:text-sm` steps up below `md`; INK has no step.
- **form-label.** Size 14 and weight 500 match. Line-height differs: 14 (`leading-none`) vs 21, because `auth.css:74` declares none. `display` differs: inline vs block.
- **link-underline (forgot).** Size 12 and colour match. Line-height 16 vs 18. Lovable underlines **on hover only**; INK is underlined **at rest**, from WordPress core's global-styles link default, because `auth.css:96` declares no `text-decoration`. That makes `auth.css:102`'s `:hover{text-decoration:underline}` a no-op.
- **heading** (INK only). Its size is set in three layers: `theme.json` h1 → xxxl, the `has-xxl-font-size` class, then `auth.css:37`'s `24px !important` pin. The weight of 700 is the UA default (`theme.json` declares none).
- **section-band.** Lovable's wrapper paints `bg-background` (250,248,245) with 48/16 padding. INK's section is transparent with 64/24 padding.
- **Lovable-only kinds with no rendered INK counterpart on this page:** `tab`, `divider`, `button-outline` (OAuth), `nav-link`.
- **INK-only:** `checkbox` and its `form-label`. The checkbox computes `font-family: Arial`, which is the UA default for a native control with no text, so it has no visible effect.

### 3.2 Load-scope cross-check

`auth.css` loads only under `is_page(['meld-aan','registreer','wagwoord-herstel'])` (`functions.php:172-186`). The `ink-auth-*` classes are emitted only by `auth-login.php`, `auth-register.php` and `auth-forgot-password.php`. Those patterns are referenced only by `page-meld-aan.html:5`, `page-registreer.html:5` and `page-wagwoord-herstel.html:5`, exactly the gated pages. **`used_in` ⊆ `load_scope`, so there is no live load-scope bug on this page.**

There is one latent risk. All three patterns are registered (`Categories: ink-foundation`) and can be inserted from the inserter onto any page, where `auth.css` would not load. Every other definition on this page (`is-style-card`, elements.button and elements.link) is site-wide.

## 4. Vocabulary

There is one new kind, added to README §4.1 under "Added during rows 09a/09b":

```
divider              horizontal rule, optionally with a centred label ('or continue with');
                     Lovable: a border-t span with an overlaid bg-card label; INK: core/separator
                     + a paragraph. The tool files the label as card and its wrapper as eyebrow
```

The rest is filed under existing kinds:

- TabsTrigger and TabsList go under `tab`.
- The "Forgot?" link and the INK switch-line links go under `link-underline`.
- The "← Back to Inkwell" link goes under `nav-link`.
- The remember-me label goes under `form-label`, with a note.

## 5. Recorded, not fixed (R8)

- **PRIMITIVE.** card has 2 definitions on one element, with an `!important` override, and a hover-neutraliser for a rule that does not exist (`functions.php:1431`, `auth.css:54`, `auth.css:59`).
- **PRIMITIVE.** button-primary has a third definition (`auth.css:207`) next to `theme.json` and `is-style-ink-primary`. The auth variant lacks the primary shadow and declares no size.
- **PRIMITIVE.** The h1 size is a three-layer stack ending in an `!important` pin (`auth.css:37`), justified by a reference heading that `Auth.tsx` does not have.
- **PRIMITIVE.** The forgot link's hover rule is a no-op, because the underline applies at rest (`auth.css:96-104`).
- **PRIMITIVE.** form-field and form-label declare no line-height, and form-field has no below-md 16px step (`auth.css:74`, `auth.css:106`).
