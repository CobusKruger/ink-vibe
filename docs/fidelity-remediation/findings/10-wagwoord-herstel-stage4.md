# 10 · wagwoord-herstel — Stage 4

Lovable `/forgot-password` · INK `/wagwoord-herstel/` · 2026-10-23

| Finding | Status | Fix (file:line) |
|---|---|---|
| W3 not centred / not full-height; W4 column 480 vs 448 | fixed | `patterns/auth-forgot-password.php:29-30` (s-48/s-16, `ink-auth`, 448px); `assets/css/auth.css:18-30` |
| drift `auth-heading` mb 0 / intro mt 24 (Lovable h1 `mb-2`) | fixed | `auth.css:35-37` |
| drift `auth-intro` 16/24 vs 14/20 | fixed | `auth-forgot-password.php:37-38` (fontSize sm); `auth.css:39-43` |
| drift `auth-switch` mt 24, left, 21px | fixed (16px, centred, 20px) | `auth-forgot-password.php:68-69`; `auth.css:46-49` |
| drift form rhythm (trailing 16px) | fixed | `auth.css:52-62` |
| W9 switch line inside the form + single muted link vs lead-in prose; `auth-switch` colour ink-text vs muted | owner (copy/structure) | — |
| W6 field hint → field 108 vs 72 | owner (EXTRA, curated copy) | — |
| drift `auth-intro` 40 vs 20 tall (longer Afrikaans line wraps) | accepted (copy is source of truth) | — |
| drift label line-height 14 vs 20 | accepted (LBL-1) | — |
| W15 h1 weight | fixed (Stage 3 `ink-heading-h2-block`, 600; no delta) | — |
| W1 header/footer, W2 lock-up | owner | — |
| W5 e-mail-or-username, W7/W8 wrappers, W10/W11/W13/W14 server-side states | accepted (WP core auth; mechanism) | — |
| W12 echo the address in the sent notice | owner | — |

## Phase 4 re-run (Lovable 4173 HEAD vs INK, deployed 2026-10-23)

- 1440: 38 deltas (was 47), all accepted/owner above; card 370 vs 314 (hint + wrapped intro + switch outside form).
- 768: 38 deltas, same set.
- 390: 35 deltas, same set.
