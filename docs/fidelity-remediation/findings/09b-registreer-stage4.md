# 09b · registreer — Stage 4

Lovable `/auth` (sign-up tab, Stage 1 `-signup` capture) · INK `/registreer/` · 2026-10-23

| Finding | Status | Fix (file:line) |
|---|---|---|
| R3 / drift `auth` box (block, 64/24, not centred) and card 480 vs 448 | fixed | `patterns/auth-register.php:33-34` (s-48/s-16, `ink-auth`, 448px); `assets/css/auth.css:18-30` |
| drift form rhythm (trailing 16px) | fixed | `auth.css:52-62` |
| drift `auth-intro` 16/24 → `text-sm`, heading→intro 8px | fixed | `auth-register.php:41-42`; `auth.css:35-43` |
| drift switch line 21px line-height / left-aligned | fixed (Lovable `text-center text-sm`, 16px under form) | `auth-register.php:110-111` (`ink-auth-switch`); `auth.css:39-49` |
| drift label line-height 14 vs 20 | accepted (LBL-1) | — |
| drift `auth-email-field` 92 vs 72 (INK hint under the field) | owner (R10 extra hint) | — |
| drift input/submit display + margins, card auto margins, shadow prefix, `auth` transparent bg | accepted (mechanism; same rendered position/value) | — |
| R1 header/footer, R2 lock-up, R14 back link | owner | — |
| R4 tabs split, R5 switch line, R6 h1, R7 intro | owner | — |
| R8 username field, R9 no password field (WP core e-mails credentials) | owner | — |
| R13 social gated off | owner (deploy seam) | — |
| R11/R12 wrappers, R15 anti-spam fields, R16 inline notices | accepted (mechanism / SCOPE) | — |

## Phase 4 re-run (Lovable signup state vs INK, deployed 2026-10-23)

- 1440: 35 deltas, all accepted/owner above; card 462 vs 474 tall (no password field, extra hint + h1/intro).
- 768: 35 deltas, same set.
- 390: 33 deltas, same set.
