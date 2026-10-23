# 06 · skrywer — Stage 4

Lovable `/writer/w1` (`Writer.tsx`) · INK `/author/cobus/` (anonymous) · 2026-10-23.
Page CSS moved out of `theme.json` `styles.css` (63 `.ink-skrywerprofiel*` rules deleted) into `assets/css/skrywer.css`, enqueued on `is_author()` (`functions.php` `ink_foundation_enqueue_skrywer_deel`).

| Finding | Status | Fix (file:line) |
|---|---|---|
| F1 page architecture (768px column) | fixed | `templates/author.html:3` main `layout:default`, no padding; `SkrywerProfiel.php:437` five sibling `<section>`s: kop (cover full-bleed + `ink-container`), stats band `:518`, oor-prestasies `section.ink-container`, werk band + container `:608`, CTA `section.ink-container` `:676`; `skrywer.css` |
| F2 tagline missing | fixed (renders existing `Tagline` data) | `SkrywerProfiel.php:108,475`; `skrywer.css` `.ink-skrywerprofiel__leuse` |
| F3 location meta | owner | no location field in INK (new data field) |
| F3b meta icons (CalendarDays) | fixed | `SkrywerProfiel.php:487` |
| F4 actions column placement | fixed | `SkrywerProfiel.php:493` (sibling of text column; `md:flex-col items-end` in `skrywer.css`) |
| F5 gradering badge | accepted (SCOPE, INK tier system) | — |
| F6 followers in meta vs stats strip | owner | moving the stat changes INK content layout; Lovable `Writer.tsx:285` |
| F7a rating stat absent | data (0 approved ratings) | — |
| F7b "N reader reviews" line | owner | needs new Afrikaans count copy |
| F8 works breakdown + conditional cells | owner (disclosed `SkrywerProfiel.php` worksBreakdown docblock) | — |
| F9 stats wrapper / rating divider | fixed | `SkrywerProfiel.php:518` band > `ink-container` grid; `md:border-r pr-6` on `__stat--gradering` |
| F10 Prestasies h2 vs h3 | fixed | `SkrywerProfiel.php:573,596` `<aside>` + `h3` |
| F11 accomplishment row shape/icon | fixed (Card > body p-4, Trophy icon); unmeasured (no Gradering history) | `SkrywerProfiel.php:573-592` |
| F12 works header row | fixed (link in head, hidden < 768) | `SkrywerProfiel.php:608-618` |
| F13 BookOpen kicker icon | fixed | `SkrywerProfiel.php:610` |
| F14 whole-card link, `h3` title | fixed | `SkrywerProfiel.php:634-640` |
| F15 read time | fixed (`Discovery\ReadingTime`) | `SkrywerProfiel.php` `pinnedCards()` `readTime`; `:638` |
| F16 Pinned badge | owner (pinned-only list) | — |
| F17 type icon in pill | fixed | `SkrywerProfiel.php` `typeIcon()` |
| F18 glyph icons/stars | fixed (lucide Heart/MessageCircle/Star svgs) | `SkrywerProfiel.php:658,771` |
| F19 Lesergradering section | accepted (SCOPE); now its own `ink-container` section | `SkrywerProfiel.php:797` |
| F20 rating form | accepted (SCOPE); wrapped in `ink-container` (hidden when empty) | `author.html:7` |
| F21 CTA wrapper | fixed (`section.container` > `max-w-3xl` inner) | `SkrywerProfiel.php:676` |
| F22 Follow hidden from guests | owner | — |
| F23 cover `<img>` vs bg-image | accepted (MECHANISM, same box) | — |
| F24 avatar wrapper | accepted (MECHANISM, core `get_avatar`) | — |
| F25 identity text column flex | fixed (block column, margin-spaced) | `skrywer.css` |
| Cover gradient 2-stop vs Lovable 3-stop | fixed | `skrywer.css` `.ink-skrywerprofiel__omslag-skakering` |
| Stats/werk bg cream/40 vs secondary/30 | accepted (SB-3) | — |
| h2 weight 600 vs 400 (oor/werk/cta) | accepted (HR-1) | — |

## Phase 4 re-run (captures `tmp/stage4/profiles/cap/`, verify PASS all 3)
- 1440: every section box matches (cover 1440×256, containers 1400 at x20, identity at 36,225, grid 1368, card 672×222). Remaining: content/data only (bio length, no tagline/rating/followers/prestasies data, Afrikaans text widths, 1 pinned card), primitive `white-space:nowrap` on pills/link.
- 768: same boxes; whole page sits 24px lower because the header is 88px tall at 768 (Lovable 64) — header scope, not this page. Lovable's 4-cell strip vs INK's 2 cells (data).
- 390: section geometry matches; stats/CTA heights differ by data/copy (works breakdown line, longer Afrikaans heading); Share at x16 because Follow is guest-hidden (F22).
