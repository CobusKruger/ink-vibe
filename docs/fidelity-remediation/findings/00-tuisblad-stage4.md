# 00 — Tuisblad + site chrome · Stage 4

INK `/` ↔ Lovable `/` (`http://localhost:4173`, ink-lovable 71c0847). Re-run 2026-10-22: deploy, capture
390/768/1440 on both sides, determinism gate + verify passed on all three INK captures.
Fixes in `assets/css/home.css` (HC), `functions.php` (FN) and `patterns/footer-main.php` (FM).

## Chrome (header + footer, every page)

| Finding | Status | Fix (file:line) |
|---|---|---|
| Header → `<main>` 24px gap on every page (root blockGap); Lovable's main is flush under the header | fixed | FN:1637 |
| Mobile menu toggle at x=123 vs Lovable x=334 @390 (carried) | fixed (now x=334) | FN:1640-1644 |
| Header wordmark line-height 30 vs 28 | fixed | FN:1639 |
| Header bg on `::before`, not on the group (capture reads transparent) | accepted: MECHANISM, disclosed FN:1566-1582 | — |
| Header "Teken in" ghost button serif vs Lovable Inter | accepted: "Button font" ruling | — |
| Header nav: 6 links vs 5 (F4) | accepted: SCOPE (spine F4) | — |
| Header @768: 6-link nav + actions wrap to a second row (89px vs 65px tall) | owner (H-1) | — |
| Header actions visible @390; Lovable hides them and puts them in the menu | owner (H-2) | — |
| Footer brand column constrained → logo row centred (x=164 vs 36 @1440, 164 vs 16 @390) | fixed | FM:27 |
| Footer side padding 24 vs 16 | fixed | FM:22-23 |
| Footer columns 2–4 pushed down 24px (grid blockGap) | fixed | FN:1714 |
| Footer link lists 16/24 ink vs Lovable 14/20 muted | fixed | FN:1742-1744 |
| Footer blurb line-height 1.7 vs 1.625; wordmark 27 vs 28; copyright / made-with 21 vs 20 | fixed | FN:1729, 1716, 1717 |
| Footer bottom bar @390 not stacked/centred | fixed | FN:1755-1757 |
| Footer social links (F3) | owner (H-3) | — |

## Tuisblad

| Finding | Status | Fix (file:line) |
|---|---|---|
| Hero grid gap 48 vs 32 below 1024 | fixed | HC:103 |
| Hero h1 → paragraph 24 vs 16; paragraph line-height 24 vs 26 | fixed | HC:110-113 |
| Spotlight + feature card body: uniform 16 gap vs Lovable 16/12/24/24 margins | fixed | HC:137-166 |
| Spotlight description line-height (22.75 <768, 24 ≥768) | fixed | HC:161, 197 |
| Feature card CTA full-width @390 (only the spotlight CTA is, in Lovable) | fixed | HC:175 |
| Feature card description / winner quote / featured excerpts / footer blurb line-height 1.7 vs 1.625 | fixed | HC:161, 317, 421 |
| Winner card quote → author and author → button 16 vs 24 | fixed | HC:263-266 |
| 14px names / win label line-height 21 vs 20 | fixed | HC:338, 457 |
| Sponsor intro line-height 27.2 vs 24 | fixed | HC:487 |
| CTA gradient 135° vs `to bottom right` | fixed | HC:536 |
| CTA content capped at 768 vs full panel width | fixed | HC:546-550 |
| CTA buttons @390 wrap side by side vs stacked full-width | fixed | HC:616-626 |
| CTA body line-height 27 vs 28 | fixed | HC:605 |
| CTA body text full cream vs white/80 | accepted: R6, disclosed HC (a11y contrast floor) | — |
| CTA section bottom padding 64 vs 80 | accepted: R6, disclosed `cta-band.php:21` | — |
| Featured card title weight 400 vs 600 | accepted: HR-2 | — |
| Featured card title line-height 27.5 vs 28 | primitive request (P-2) | — |
| Featured pill per-type tone vs Lovable neutral `bg-secondary` | accepted: owner call disclosed `FeaturedStream.php:373-376` | — |
| Meta-item icon gap 6 vs 4 | accepted: NR-4 | — |
| Sponsor chips / sponsor button serif | accepted: recipe (3c) + "Button font" ruling | — |
| Eyebrows (featured, sponsors) 20px box vs Lovable inline span in a 24px line → heading block 4px shorter | primitive request (P-1) | — |
| F1 hero stats row missing | owner (T-1) | — |
| F2 mobile "View all works" link missing | owner (T-2) | — |
| F5 whole-card link vs title link | owner (T-3) | — |
| F6 winner repeater | accepted: SCOPE (spine F6) | — |
| F7 winner "by {author}" line missing | owner (T-4) | — |
| F8, F10, F11, F12 | accepted: MECHANISM, no computed delta | — |
| F9 winner CTA bare `<a>` | fixed in Stage 3 (`ink-btn ink-btn--gold`) | — |
| Sponsor chips in data order (bronze first) vs Lovable gold → silver → bronze | owner (T-5) | — |

## Phase 4 re-run (remaining deltas)

- **390:** copy-length only (Afrikaans wraps: h1, card titles, buttons), the missing F1/F2/F7 nodes, H-2, the 4px eyebrow (P-1). Chrome clean.
- **768:** as 390, plus H-1 (the header wraps, so everything below it sits 24px lower).
- **1440:** copy-length only, F1 (hero 101px shorter), F7, the 4px eyebrow (P-1) and sponsor order (T-5). Chrome clean apart from the F4 nav width.
