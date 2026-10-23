# 11 · wagwoord-terugstel — Stage 4 (Treatment C)

INK `wp-login.php?action=lostpassword` (core screen) · Lovable `/reset-password` (reference only) · 2026-10-23

| Finding | Status | Fix (file:line) |
|---|---|---|
| T2 load scope: `wp-login-brand.css` only for `rp`/`resetpass` | fixed (all `wp-login.php` screens) | `themes/ink-foundation/functions.php:600-616` |
| T2 form selector missed `#lostpasswordform` | fixed | `assets/css/wp-login-brand.css:79-81` |
| T1 OFF-TOKEN: 32 distinct values @1440 (font stack, 13px, admin blue, 2px radius, core shadow) | fixed → 3 left, all sanctioned: accent 10% notice tint (`ink-notice` recipe), focus ring (`ink-field` recipe), `dashicons` icon font | `wp-login-brand.css:26-63` (body 14/20, `#login` 48px top, label 8px), `:148-195` (switcher on `ink-field` / `ink-btn--neutral ink-btn--sm`), `:197-234` (`ink-notice` info/fout), `:119-121` (button selector beats core's mobile `.button-large` padding), `:236-246` (`#nav` 14/20) |
| T4 "Registreer" → BuddyPress `/register/` ("Page not found") | fixed (`register_url` → `/registreer/`, priority 20 after BuddyPress) | `plugins/ink-core/src/Accounts/AuthRedirects.php:65-83`; test `tests/Unit/Accounts/AuthRedirectsTest.php:68-81` |
| OFF-RECIPE: card / field / button / notice / back link | conforms (`ink-card--lg`, `ink-field`, `ink-btn--primary`, `ink-notice`, `ink-nav-link--footer` values) | — |
| select `margin-right` 3.5px @1440 (core rule outranks) | accepted (residual, 1 node) | — |
| T4 "Meld aan" nav label (retired term) → core login screen; English "Change"; plugin text-domains listed as languages | owner (core copy) | — |
| T3 redirect `?action=lostpassword` to `/wagwoord-herstel/` instead of branding it | owner | — |
| T3 branded `action=rp` screen not audited (needs a reset key = data write) | owner | — |
| T5 README §9 "wp-login.php is intercepted" holds for POST only | owner (doc) | — |

## Phase 4 re-run (OFF-TOKEN, `tmp/stage4/auth/off-token.py`, deployed 2026-10-23)

- 1440: 4 distinct (3 sanctioned above + select margin 3.5px).
- 768: 3 distinct, all sanctioned.
- 390: 3 distinct, all sanctioned.
