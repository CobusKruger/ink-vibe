# 00 — Tuisblad (home) · Phase 2 spine + structural findings

**Page:** `tuisblad` · **Lovable:** `/` → `src/pages/Index.tsx` · **INK:**
`templates/front-page.html`
**Produced:** 2026-09-23 · **Phase:** 2 (structural only — no style claims)

---

## 1. Files read end to end

Per rule R1, every claim below is sourced from a complete read of these files.

**Lovable**
- `src/pages/Index.tsx`
- `src/components/home/HeroSpotlight.tsx`
- `src/components/home/ChallengeSection.tsx`
- `src/components/home/FeaturedWorks.tsx`
- `src/components/home/SponsorsSection.tsx`
- `src/components/home/CallToAction.tsx`
- `src/components/layout/Header.tsx`
- `src/components/layout/Footer.tsx`

**INK**
- `wp-content/themes/ink-foundation/templates/front-page.html`
- `wp-content/themes/ink-foundation/parts/header.html`, `parts/footer.html`
- `wp-content/themes/ink-foundation/patterns/header-main.php`, `hero.php`,
  `featured-grid.php`, `borg-strook.php`, `cta-band.php`, `footer-main.php`
- `wp-content/plugins/ink-core/src/Challenges/CurrentChallenge.php`
- `wp-content/plugins/ink-core/src/Challenges/FeaturedWinners.php`
- `wp-content/plugins/ink-core/src/Discovery/FeaturedStream.php` (render half)
- `wp-content/plugins/ink-core/src/Sponsors/HomepageStrip.php` (render half)

`src/components/home/HeroSection.tsx` exists in the Lovable repo but is **not** imported
by `Index.tsx` — it is dead code for this page and is excluded.

---

## 2. Section spine — 7/7 present, same order

| # | Lovable (`Index.tsx` order) | INK | Status |
|---|---|---|---|
| 1 | `<Header/>` — `layout/Header.tsx` | `parts/header.html` → `patterns/header-main.php` | present |
| 2 | `<HeroSpotlight/>` | `patterns/hero.php` + `ink/huidige-uitdaging` **variant=`kompak`** | present |
| 3 | `<ChallengeSection/>` | `front-page.html:9-18` `.ink-feature-band` = `ink/huidige-uitdaging` **variant=`kenmerk`** + `ink/wenner-kollig` | present |
| 4 | `<FeaturedWorks/>` | `patterns/featured-grid.php` → `ink/uitgesoekte-bydraes` (`FeaturedStream`) | present |
| 5 | `<SponsorsSection/>` | `patterns/borg-strook.php` → `ink/borg-strook` (`HomepageStrip`) | present |
| 6 | `<CallToAction/>` | `patterns/cta-band.php` | present |
| 7 | `<Footer/>` | `parts/footer.html` → `patterns/footer-main.php` | present |

**No section is missing.**

Note the intended symmetry on §2/§3: Lovable renders the challenge **twice** — once as the
hero's spotlight card (`HeroSpotlight.tsx:85-128`, fed by `featuredItem`) and once as
`ChallengeSection`'s left card. INK mirrors this exactly with one block in two variants,
`kompak` in the hero aside and `kenmerk` in the feature band
(`CurrentChallenge.php:64-71`). §3's two-column grid of *Current Challenge* + *Recent
Winner* maps to `huidige-uitdaging{kenmerk}` + `wenner-kollig` — a considered 1:1 port.

---

## 3. Structural findings

12 findings. Classes per `README.md` §5. No style claims (R4).

| ID | Class | Finding | Lovable | INK |
|---|---|---|---|---|
| **F1** | `MISSING` | Hero stats row — three stat pairs (12K+ Active Writers / 48K+ Published Works / 150K+ Thoughtful Critiques) in a `mt-12 pt-8 border-t` band below the hero grid | `HeroSpotlight.tsx:134-147` | no counterpart; `hero.php` ends after the two-column grid (`hero.php:62-64`) |
| **F2** | `MISSING` | Mobile-only bottom "View All Works →" link (`md:hidden`) | `FeaturedWorks.tsx:131-133` | only the header link (`__alles`) is emitted — `FeaturedStream.php:305-306` |
| **F3** | `EXTRA` | Footer social-links block (facebook / instagram / x) | none | `footer-main.php:44-52` |
| **F4** | `SCOPE` | Primary nav carries 6 links vs Lovable's 5 — INK adds *Opleiding* and *My profiel* | `Header.tsx:41-55` | `header-main.php:73-78` |
| **F5** | `STRUCTURE` | Featured-work card: whole card is a link vs `<article>` with the link on the title only | `FeaturedWorks.tsx:72-79` | `FeaturedStream.php:349`, `:345-347` |
| **F6** | `STRUCTURE` | Winner card is a repeater — `<section>` › `__kaarte` › N × `<article>` — vs a single inline card | `ChallengeSection.tsx:48-85` | `FeaturedWinners.php:249-256` |
| **F7** | `MISSING` | "by {author}" line directly under the winner title | `ChallengeSection.tsx:63` | author appears only in the footer block (`FeaturedWinners.php:326-357`) |
| **F8** | `STRUCTURE` | Winner quote is `<p className="italic font-serif">` vs `<blockquote>` | `ChallengeSection.tsx:65-68` | `FeaturedWinners.php:306` |
| **F9** | `STRUCTURE` | Winner CTA is `<Button variant="outline">` vs a bare `<a>` | `ChallengeSection.tsx:82-84` | `FeaturedWinners.php:312-313` |
| **F10** | `MECHANISM` | Hero background texture is a DOM node with an inline SVG data-URI vs a CSS class on the section | `HeroSpotlight.tsx:48-50` | `.ink-hero-texture` on `hero.php:26` |
| **F11** | `MECHANISM` | Sponsor chips are flex-wrapped `<div>`s vs `<ul>`/`<li>` | `SponsorsSection.tsx:29-48` | `HomepageStrip.php:176-193` |
| **F12** | `STRUCTURE` | `kenmerk` card adds an `__inhoud` wrapper the Lovable card lacks (its children are direct) | `ChallengeSection.tsx:10-45` | `CurrentChallenge.php:326` |

### Notes on individual findings

- **F4, F6** are `SCOPE`, not defects. INK has an Opleiding section and a real winners
  feed; the mockup has neither. They are listed so the divergence is on the record and is
  never silently "corrected" (R6).
- **F7** — Lovable prints the author name twice (the `by …` line *and* the author row at
  `:71-80`). INK prints it once. Arguably an improvement; owner call.
- **F10, F11** are `MECHANISM`: same visual intent, different implementation. Neither is
  actionable until Phase 4 confirms the computed output differs.

---

## 4. Join-key coverage

`data-audit-id` is present on **5** homepage elements, all in the hero's left column, and
they already pair 1:1 across the two repos:

| audit-id | Lovable | INK |
|---|---|---|
| `hero-badge` | `HeroSpotlight.tsx:56` | `hero.php:32` |
| `hero-h1` | `HeroSpotlight.tsx:60` | `hero.php:36` |
| `hero-paragraph` | `HeroSpotlight.tsx:65` | `hero.php:40` |
| `hero-btn-primary` | `HeroSpotlight.tsx:70` | `hero.php:46` |
| `hero-btn-outline` | `HeroSpotlight.tsx:76` | `hero.php:50` |

The hero aside card and all of §3–§7, plus header and footer, carry none. Phase 3 for
this page is therefore: extend coverage to the remaining ~110 visually meaningful
elements, following the same naming convention.

---

## 5. Method note — the error this finding corrects

An earlier pass claimed *"`ChallengeSection` has no counterpart — a whole section is
missing."* That claim was produced by:

```
grep -oE 'pattern[^ ]*|"slug":"[^"]*"' front-page.html | head -20
```

The regex matches only `wp:pattern` and `wp:template-part` slugs. The challenge section is
an inline `wp:group` containing `wp:ink/*` dynamic blocks, which carry no `"slug"` in that
form, so it was invisible to the filter — and the filtered output was then truncated by
`head`. Absence in the output was read as absence in the file. The file is 30 lines long.

The same shortcut produced a second false claim: a "3:1 structural under-implementation"
ratio derived from comparing Lovable JSX line counts against *pattern files only*, while
the section's actual markup lives in `CurrentChallenge.php` (434 lines) and
`FeaturedWinners.php` (371). Both claims are retracted.

This is the origin of rule **R1** and of the resolution chain documented in `README.md`
§3.

---

## 6. Conclusion and limits

**Conclusion.** §2, §3, §4, §6 and §7 are element-for-element ports: INK's
`__hoek` / `__inhoud` / `__kop` / `__titel` / `__uittreksel` / `__meta` / `__aksie` line
up one-to-one against Lovable's corner div / `relative z-10` / badge row / `h2` / `p` /
meta row / Button. Twelve structural deltas across the whole page, most of them minor,
and four of them non-defects.

The visible dissimilarity on this page is therefore **overwhelmingly styling, not missing
markup**. A node-level map is buildable, and Phase 4 is the instrument that will close the
gap.

**Limits.** This document contains no value claims. It says nothing about size, colour,
spacing, weight, radius or shadow, and it cannot: `wp:navigation`, `wp:site-title`, the
block-library defaults, the `is-style-ink-*` block styles in `functions.php`, and what
`home.css` actually paints are not knowable from the source chain alone (R4). F10 in
particular is unresolvable until rendered capture. All of that is Phase 4.
