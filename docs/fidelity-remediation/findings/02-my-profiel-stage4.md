# 02 · my-profiel — Stage 4

Lovable `/profile` (`Profile.tsx`) · INK `/my-profiel/` as `fidelity-capture` (no profile data) · 2026-10-23.

| Finding | Status | Fix (file:line) |
|---|---|---|
| F34 edit overlay clamped to 800×876 (live bug) | fixed — overlay now 1440×900 | `templates/page-my-profiel.html:3` main `layout:default`; `profiel.css:602` guard (`max-width:none; margin:0`) |
| F11 panels clamped to 768 | fixed — all 11 `constrained` layouts removed | `patterns/my-profiel.php:120` (note), panels/cards; `profiel.css` `#oorsig`/`#lidmaatskap` grids (`lg:grid-cols-3`, span 2) |
| Container (band px-24 + alignwide) | fixed — `ink-container` (1400, px-16), py 40/56 identity, 40 tabs | `my-profiel.php:122,201`; `profiel.css:30,108-116` |
| F2 extra `__persoon` wrapper / 390 stacking | fixed — row = avatar \| text \| actions, column < 768 | `my-profiel.php` identity row; `profiel.css:30,695` |
| F3 / F35 avatar camera + "Change photo" | owner (§7.1 vs deferral) | — |
| F4 button icons (Pencil, PenLine, ArrowRight) | fixed | `my-profiel.php:171,235` + new/public buttons |
| F5 nested `wp-block-buttons` | accepted (MECHANISM; gap flattened) | `profiel.css` `.ink-profiel-identiteit__aksies .wp-block-buttons` |
| F6 gradering + wins-needed | accepted (owner §7.5) | — |
| F7 tab shape | fixed in Stage 3 (segmented); tabs now centred like `TabsList` | `profiel.css:100` |
| F8 tab icons | fixed | `my-profiel.php:210-216`; `profiel.css:118` |
| F9 tab count badges | open — unbuilt and unmeasurable (badges render only with data; capture user empty) | — |
| F10 tab ARIA roles | open (a11y, not fidelity) | — |
| F12 card headings h3 → h2 | fixed | `my-profiel.php:233,260,304` |
| F13 Crown + Separator membership line | open — renders only for members (unmeasurable) | — |
| F14 recent-activity row furniture | open — data-only (empty user) | — |
| F15 stat dt/dd order | accepted (MECHANISM); gap removed → 48px like Lovable | `profiel.css` `.ink-profiel-oogopslag__stat` |
| F16 / F29 empty states | accepted (SCOPE, copy-debt) | — |
| F17–F28 repeater/header furniture (posts, reading, following, activity, notifications) | open — populated states unmeasurable; F19/F21/F22/F25/F27 need new Afrikaans copy → owner | — |
| F18 / F32 savings / F33 | accepted (strategy §2/§7) | — |
| F30 membership status card role swap | owner (copy: "Inkwell Member"/"Active subscription" roles) + unmeasurable (members only) | — |
| F31 renew as `Card` | fixed (page-scoped: card surface, p-6, title mb-1, desc 14/20 mb-5, plans `sm:grid-cols-3 gap-3`, no 768 clamp) | `profiel.css:547-590` |
| F36 modal fields | fixed — inline label + `mt-1` input (field 68px), textarea `rows=5` (118px); title h2 kept | `profiel.css:651`; `ProfileEditor.php:158` |
| About bio line-height 27.2 vs 26 | fixed | `profiel.css` `.ink-profiel-oormy__bio` |
| Name / card titles weight 600 vs 400 | accepted (heading primitives, HR-1) — see report | — |
| Serif buttons vs Inter | accepted (button-font ruling) | — |
| Identity bg cream/40 vs secondary/30 | accepted (SB-3) | — |
| `[NEEDS HUMAN AFRIKAANS]` visible (live bug) | owner (copy-debt) | `my-profiel.php:247`; `functions.php:945-946`; `ProfileEditor.php:152,162,163`; `FollowingFeed.php:291`; `BydraesSurface.php:196`; `KennisgewingsSurface.php:306`; `ReadingList.php:122` |
| Renewal plans all R50,00 → `add-to-cart=926` (live bug) | owner (WooCommerce product/plan mapping data) | `lidmaatskap-hernu.php:89-135` renders what `ink_foundation_renewal_plans()` returns |
| Seed `fidelity-capture` with data | owner | — |

## Phase 4 re-run (captures `tmp/stage4/profiles/cap/`, verify PASS)
- 1440: identity row, tablist, overview grid (901 + 435, gap 32), cards, glance stats all match within 2px. Remaining: data (no tagline, empty recent/membership line), Afrikaans text/button widths, tab badges (F9). States: edit overlay 1440×900 and fields match; membership renew spans 1368 (non-member: no status card, data); posts/notifications heads match.
- 768: structure matches; page 24px lower (header 88px at 768 — header scope). Longer Afrikaans button labels squeeze the text column (68 vs 130px).
- 390: matches; button wrap order differs by label width (copy).
