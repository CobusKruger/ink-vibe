# 14 · uitdagings — Stage 4

INK `/uitdaging/` (archive) · Lovable reference for the card: `/` `[data-audit-id="challenge-card"]` (`components/home/ChallengeSection.tsx`).
Scratch captures: `tmp/stage4/uitdaging/cap2/`.

## Live bug S-U1 (`/uitdagings/` → 301 to an unrelated post)

| Link | Status | Fix (file:line) |
|---|---|---|
| Oor INK "Maandelikse uitdagings" | fixed → `/uitdaging/` | `patterns/oor-ink.php:107` |
| Nav IA source (migration rebuilder) | fixed → `/uitdaging/` | `ink-core/src/Migration/NavigationRebuilder.php:121` (+ test `tests/Unit/Migration/NavigationRebuilderTest.php:40`) |
| Homepage demo-winner fixture url | fixed → `/uitdaging/` | `functions.php:1297` |
| Header nav audit-id map | fixed (`/uitdaging/` added beside `/uitdagings`) | `functions.php:479` |
| Leak-scan crawl list; fidelity target | fixed | `tools/leak-scan/scan-rendered.php:36`; `tools/fidelity/targets.json:106` |
| Header nav link | owner/chrome agent (not edited: chrome is out of scope) | `patterns/header-main.php:76` |
| Footer "Maandelikse uitdagings" | owner/chrome agent (not edited) | `patterns/footer-main.php:68` |
| Live `wp_navigation` post, if one was built by the rebuilder | owner (data; re-run the rebuilder or edit the menu) | DB |
| Rename the CPT archive slug to `uitdagings` instead | owner | `ink-core/src/Content/PostTypes.php:190-197` |

## Treatment C remainder — OFF-RECIPE (README §4.6)

| Finding | Status | Fix (file:line) |
|---|---|---|
| S-UC2: band padding-top 16 (recipe `section-band` 64; index pages use the `intro` band) | fixed | `patterns/uitdaging.php:24` shell has no padding; `Archive.php:362-368` emits intro band + `ink-band--step` list band (same shell as Hub/Library) |
| Intro was a bordered div inside the content, not a full-bleed `intro` band (cream/30, border-b, py 64→80, left-aligned 48rem) | fixed | `Archive.php:362`; theme.json `.ink-uitdagings__intro-houer/-binne` |
| S-UC1: h1 36/48/60 | accepted (= recipe `heading h1-index`) | — |
| OFF-TOKEN re-run: h1 line-heights 45/48/60 and container auto-margins of 20px | accepted (h1-index recipe values; centring, not spacing) | — |

## Treatment A region — card

| Finding | Status | Fix (file:line) |
|---|---|---|
| Card surface radius 8 / p 24 / shadow.sm | fixed in Stage 3 (`ink-card--soft`) | — |
| Rhythm: flex gap 12 (Lovable head mb-16, title mb-12) | fixed | theme.json `.ink-uitdagings__item` gap 0, `__item-meta` mb 16, `__titel` mb 12 |
| Meta row 16/24 ink (Lovable 14/20 muted) | fixed | theme.json `.ink-uitdagings__item-voet` |
| S-U2 `<li>`, S-U7 title `<a>` without heading | accepted (MECHANISM/STRUCTURE) | — |
| S-U3 corner tint, S-U4 trophy tile, S-U5 month eyebrow, S-U8 description, S-U10 entry count | owner (MISSING; S-U5/S-U8 need copy/data) | — |
| S-U9 bordered footer, S-U11 hover-reveal "Lees meer" vs filled "Submit" button, S-U12 one card filling the 1368 row | owner (logged ruling S-U11/S-U12) | — |
| S-U6 tema + countdown pills | accepted (SCOPE, disclosed `Archive.php:265-272`) | — |

## Phase 4 re-run (deploy → capture → `verify --sample 30` PASSED, full + scoped)

- 1440: the intro band is 221 high (80+60+80+1) and the list band starts 64 below it. Card deltas remaining: width 1368 vs 668 (S-U12), CTA control (S-U11), footer border (S-U9), head 24 vs 36 (S-U4 tile missing).
- 768: same as 1440.
- 390: same, with the intro py at 64. The title runs to 3 lines (copy).
