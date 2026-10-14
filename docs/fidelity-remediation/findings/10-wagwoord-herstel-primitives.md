# 10 · wagwoord-herstel — primitive observations (Phase 4b)

- Register: `maps/_primitive-observations.wagwoord-herstel.csv`. It has **26 rows**: 8 `lovable`, 12 `ink`, 6 `source`.
- Inputs: `run.sh primitives --exclude-chrome` over 12 captures. These are Lovable `/forgot-password` and INK `/wagwoord-herstel/` at 390/768/1440, plus INK `?wagwoord=gestuur` and `?wagwoord=fout` at 390/768/1440. All 12 pass determinism (3 runs) and `verify --sample 30`.
- Build script: `tmp/fidelity-password/build-register.py`. It resolves the generic kinds, adds by hand the nodes the classifier cannot detect, and fills the attribution columns.
- The tool produced 24 rows. I dropped the sent/error-state rows identical to the default state (section, card, heading, label, input, submit), so each is recorded once. I resolved 3 generic `button` rows and added 7 rows by hand: the Lovable section band, the Lovable back link, the INK hint, the INK switch link and the INK notices.
- Source rows are added only for definitions that rows 09a/09b had not already registered: `.ink-auth-notice*` and `.ink-auth-secondary*`. The card, heading, label, field, hint, submit and link definitions are already registered as `source` rows in `_primitive-observations.meld-aan.csv` / `.registreer.csv`, and are not duplicated.

## Kinds on this page

| Kind | Lovable | INK | Note |
|---|---|---|---|
| section-band | `auth` (flex, 48/16, bg `#FAF8F5`) | `auth` section (block, 64/24, transparent) | MECHANISM (spine W3) |
| card | `auth-card` | `auth-card` | |
| heading | `auth-heading` 24/600/32 | 24/700/28.8 | weight disclosed, `auth.css:27-36` |
| form-label | 14/500, lh 14 | 14/500, lh 21 | |
| form-field | `auth-email-input` | `auth-email-input` | |
| form-hint | — | `auth-field-hint` | EXTRA (W6) |
| button-primary | `auth-submit` | `auth-submit` (resolved from generic `button`) | |
| button-outline | *(sent state, not captured; see 09a's measured outline rows)* | `auth-secondary-btn` (gestuur) | |
| nav-link / link-underline | back link → `nav-link` (muted, no underline) | back link → `link-underline` (primary, underlined) | the same role lands in two different kinds |
| **notice** *(new kind)* | — (Lovable uses a toast and plain text) | `auth-notice` `--ok` / `--fout` | |

## Declaration comparison

These compare INK definitions against each other and against the measured Lovable instances. They record differences; they do not judge which is right.

- **card.** Two INK definitions stack. The `is-style-card` block style (`functions.php:1431`: surface-alt, border, radius-lg, shadow-sm, **s-24 padding**) is overridden page-side by `.ink-auth-card` (`auth.css:54`: **s-32 padding `!important`**, transition none). `:hover` (`auth.css:59`) cancels the lift.
  - **`PRIMITIVE`**: a card variant defined in a page-scoped sheet (R8).
  - The two shadows share the *name* `shadow-sm` but not the value. Lovable's shadcn `shadow-sm` computes to `0 1px 2px rgba(0,0,0,.05)`. INK's theme token `sm` is `0 2px 12px -2px rgba(24,29,37,.06)`.
  - Background and border colours differ by 1–6 per channel: `253,253,252` vs `253,252,250` and `231,225,218` vs `232,228,220`. That is HSL token rounding on Lovable vs hex on INK.
- **button-primary.** The INK submit is `theme.json` `elements.button` + `.ink-auth-submit button` (`auth.css:207`, page-scoped box: flex, 40px tall, 8/16 padding, medium weight). **`PRIMITIVE`**: the button box is defined per page.
  - Measured against Lovable `literary`: 16px/24px vs 14px/20px; text `surface-alt` (253,252,250) vs `#FFF`; no shadow vs `shadow-soft` (`0 4px 20px -4px rgba(24,29,37,.08)`, which is INK's `shadow-md` value).
  - `border-top-style: outset` (UA default, 0 width) shows that the button carries no border reset.
- **button-outline.** `.ink-auth-secondary` (`auth.css:232`) is a third page-scoped outline-button definition (**`PRIMITIVE`**). It sets `font-family: display` (Lora) at `md` (16px). The measured Lovable `variant="outline"` (row 09a's OAuth buttons, `_primitive-observations.meld-aan.csv`) is **Inter 14px/20px** on `rgb(250,248,245)`. INK is Lora 16px/24px on `rgb(248,246,242)`. The only shared values are padding, radius and border width.
- **form-field.**
  - `auth.css:106`, page-scoped, is the third form-field definition after kontak/skryf per its own comment. That comment was not verified: kontak.css and skryf.css were not read.
  - INK is 14px with `line-height: normal` at every width. Lovable is 16px/24px below 768 (`text-base … md:text-sm`) and 14px/20px from 768 up.
  - Field background differs: `bg-background` (250,248,245) vs `surface` (248,246,242).
- **form-label.** Line-height is 14px on Lovable (`leading-none`) and 21px on INK. Everything else matches.
- **notice.** `.ink-auth-notice` (`auth.css:152`) plus the `--ok`/`--fout` modifiers (`:160`, `:166`): 12/16 padding, radius-md, 14px, 1px success/danger border, 10% `color-mix` tint. Its own comment (`auth.css:149-151`) says it is the "same recipe as `.ink-kontak-vorm__notice` (kontak.css)". That is a second, page-scoped definition of one component (**`PRIMITIVE`**, duplication), recorded from the comment only, since kontak.css was not read. The rendered tint is `color(srgb … / 0.1)`, not a palette value.

## Load-scope cross-check

- `auth.css` loads on `is_page(['meld-aan','registreer','wagwoord-herstel'])` (`functions.php:173`).
- Every `ink-auth-*` class is emitted only by `auth-login.php`, `auth-register.php` and `auth-forgot-password.php`. A search located those three patterns as consumed only by `page-meld-aan.html`, `page-registreer.html` and `page-wagwoord-herstel.html`. Only the last of those was read for this row; 09a/09b cover the other two.
- `load_scope` = `used_in`, so **no inert class on this page.**
- The one load-scope bug in this flow is on row 11 (`wp-login-brand.css`). See `11-wagwoord-terugstel-primitives.md`.

## Vocabulary

New kind **`notice`**, added to README §4.1 ("Added during rows 10/11"). It is an inline status or alert message box: padded, bordered or left-ruled, and tinted by state (success, danger, info). It appears in INK's `.ink-auth-notice--ok/--fout` and in WordPress core's `.notice.notice-info` (row 11). The tool does not classify it, so it is added by hand.

Lovable's "← Back to sign in" muted link is filed under `nav-link`, matching row 09's "← Back to Inkwell". INK's primary underlined "Teken in" link is filed under `link-underline`.

## Not captured

- Lovable sent state (`auth-sent`, `auth-notice`, `auth-secondary-btn`) and the error toast. Both need a form submission.
- Hover, focus and busy states on both sides.
- INK fout and gestuur appear here only because they are GET-reachable query states.
