# 11 · wagwoord-terugstel — primitive observations (Phase 4b)

- **Register:** `maps/_primitive-observations.wagwoord-terugstel.csv`. It has **28 rows**: 4 `lovable`, 15 `ink`, 9 `source`.
- **Inputs:** `run.sh primitives --exclude-chrome` over INK `wp-login.php?action=lostpassword` and Lovable `/reset-password` at 390/768/1440. All 6 captures pass determinism and verify.
- **Build script:** `tmp/fidelity-password/build-register.py`.
- **What I changed from the tool output:**
  - Resolved the tool's `form-field` calls on `<input type=submit>`: `.button-primary` → `button-primary`, and the language switcher's `.button` → `button-outline`.
  - Dropped the 1×1 `.screen-reader-text` h1.
  - Added by hand the core `.notice` (`notice`), the `#lostpasswordform` box (`card`), the `#nav`/`#backtoblog` links (`nav-link`, first `#nav` link only), the Lovable section band and the Lovable `auth-notice-link` (`link-underline`).

## INK instances: all WordPress core

`defined_in` is core (`wp-admin/css/login.min.css`, `forms.min.css`, `buttons.min.css`), not the theme. The theme has no definition in force on this URL.

| Kind | Instance | Key computed values (1440) |
|---|---|---|
| heading | `.wp-login-logo` | 26px/700 system font; its `<a>` paints the WordPress "W" image |
| notice | `.notice.notice-info.message` | white, 4px left rule, 8/12 padding, 13px |
| card | `#lostpasswordform` | white, 1px `#C3C4C7` border, 0 radius, `0 1px 3px rgba(0,0,0,.04)`, 26/24 padding |
| form-label | `label[for=user_login]`; language-switcher label | 14px/400 `#3C434A` |
| form-field | `#user_login` (`.input.ltr`) | **24px** text, 3/5 padding, 2px radius, captured in `:focus` |
| form-field | language `<select>` | 14–16px, 2px radius, `#949494` border |
| button-primary | `.button.button-primary.button-large` | `#3858E9`, 13–14px, 0/14–16 padding, 2px radius |
| button-outline | language switcher "Change" `.button` | transparent, `#3858E9` text and border |
| nav-link | `#nav a`, `#backtoblog a` | `#50575E` 13px |

## Lovable instances (`/reset-password`, not-ready state)

`section-band` (`auth`), `card` (`auth-card`), `heading` (`auth-heading`) and `link-underline` (`auth-notice-link`, terracotta, no underline at rest). Their values match the row 10 Lovable rows (same card, same heading).

## Declaration comparison

- **Theme vs core.** `wp-login-brand.css` restates the card, field, primary button and nav-link recipes for the core screen in **hand-copied literals** (`:20-23`). The button is 44px tall with `0 32px` padding. The theme's other auth button (`auth.css:207`) is 40px with 8/16 padding. The card shadow literal is the `shadow-sm` token value. All of these are **`PRIMITIVE`** duplicates of recipes defined elsewhere, held in literals that will not follow a token change.
- **Core vs the INK auth recipe** (row 10, measured): the field (2px radius vs 6; 24px text vs 14; 3/5 padding vs 8/12) and the button (blue vs `primary`; 2px radius vs 6; system font vs Lora) share no values beyond 1px borders.

## Load-scope cross-check: the live bug

`wp-login-brand.css` loads only when `action ∈ {rp, resetpass}` (`functions.php:531-547`). Its selectors match nodes on `action=lostpassword` and on plain `wp-login.php`, but are **inert** there, so those publicly reachable screens render as unbranded core.

Its one in-scope screen, `rp`/`resetpass`, is reachable only with a valid key. Without one, core redirects to `lostpassword&error=invalidkey`, which `AuthRedirects` forwards to `/wagwoord-herstel/?wagwoord=fout`. In practice the stylesheet is live on exactly one screen, and this audit could not reach that screen without a data write.

`#lostpasswordform` is not named by the sheet's form rule (`:42`), so even a widened gate would leave the lost-password card unstyled. The register records this on every `source` row's `note` as `LOAD-SCOPE … inert`.

## Vocabulary

This page uses the new kind **`notice`** (added in row 10). Core's `.notice` is filed under it. No other new kind was needed.
