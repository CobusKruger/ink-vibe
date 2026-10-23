# 09a · meld-aan — Stage 4

Lovable `/auth` (sign-in tab) · INK `/meld-aan/` · 2026-10-23

| Finding | Status | Fix (file:line) |
|---|---|---|
| M3 / drift `auth` box: block, 64/24 padding, no min-height, not centred | fixed | `patterns/auth-login.php:36-37` (s-48/s-16, `ink-auth`, contentSize 448px); `assets/css/auth.css:18-25` (100vh flex column, centred) |
| drift `auth-card` width 480 vs 448 | fixed | `auth-login.php:36` contentSize 448px; `auth.css:27-30` (card `width:100%`, border-box) |
| drift form rhythm: trailing 16px under the submit row (`space-y-4` puts the gap between rows) | fixed | `auth.css:52-62` |
| drift `auth-intro` 16/24 (Lovable auth-card intro recipe is `text-sm`, `ForgotPassword.tsx:39`) | fixed | `auth-login.php:44-45` (fontSize sm); `auth.css:35-43` (heading→intro 8px, 20px line-height) |
| drift submit 16/24, colour, white-space; field colours | fixed (Stage 3 primitives) | `primitives.css` `ink-btn--primary`, `ink-field` |
| drift label line-height 14 vs 20 → field 68 vs 72, row 20 vs 16 | accepted (LBL-1) | — |
| drift input `display`/`margin-top`, submit `margin-top`, card auto margins | accepted (mechanism; same rendered position) | — |
| drift `auth` background transparent | accepted (body is `surface`, same pixel) | — |
| drift card/submit `box-shadow` | accepted (same shadow; Tailwind ring prefix only) | — |
| M1 header/footer on the page | owner | — |
| M2 brand lock-up, M13 back link | owner | — |
| M4 tabs split, M5 switch line | owner | — |
| M6 h1 "Teken in", M7 intro (card is 434 vs 442 tall because of them, minus the tabs) | owner (tied to M4) | — |
| M10 remember-me | owner | — |
| M11/M12 social section gated off | owner (deploy seam) | — |
| M8/M9 field/submit wrappers | accepted (mechanism; values match) | — |
| M14 inline notice vs toast, M15 username-or-e-mail field | accepted (WP core auth, SCOPE) | — |

## Phase 4 re-run (Lovable 4173 HEAD vs INK, deployed 2026-10-23)

- 1440: 49 deltas (was 95), all accepted/owner above. Section 900px tall, centred; card x 496, 448 wide.
- 768: 49 deltas, same set.
- 390: 47 deltas, same set.
