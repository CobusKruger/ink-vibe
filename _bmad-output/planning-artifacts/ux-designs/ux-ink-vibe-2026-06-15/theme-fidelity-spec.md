---
name: INK — Theme Visual-Fidelity Spec (Tuisblad + shared primitives)
description: Concrete, buildable acceptance criteria for making the ink-foundation FSE theme a high-fidelity match to the Lovable design. Closes DESIGN.md [ASSUMPTION #7] (per-component visual specs) and EXPERIENCE.md [ASSUMPTION #6] (breakpoints).
status: final
version: 2
updated: 2026-07-19
revision: "v2 — applied reviewer-gate findings (rubric/fidelity/buildability/accessibility) + owner decisions: add bright gold + tier tokens; OMIT vanity stats row; read-time computed in ink-core; brons/silwer working-default hex; empty featured feed hides entirely. All open decisions resolved; no pre-build gates remain. Promoted to final 2026-07-19."
scope: Home page (Tuisblad) as anchor + shared primitives (buttons, cards, pills/badges, background texture, animations) specced once for reuse.
sources:
  - ink-lovable @ 5618f39 (design source of truth — exact values lifted from React/Tailwind; repo at /Users/cobus/Development/ink-lovable)
  - wp-content/themes/ink-foundation/theme.json (current tokens)
  - wp-content/plugins/ink-core/ (dynamic blocks + data providers)
  - DESIGN.md / EXPERIENCE.md (this run)
  - docs/theme-fidelity-rework-plan.md (companion diagnosis — this spec is its Phase 1 output)
  - docs/theme-redevelopment/design-lovable.png (target) / design-staging.png (current)
  - validation-report.md + review-{rubric,fidelity,buildability,accessibility}.md (this run — findings applied in v2)
---

# INK — Theme Visual-Fidelity Spec

> **Purpose.** The `ink-foundation` theme was built to the *tokens* but never to a concrete *composition contract*, so the rendered Tuisblad is a plain single-column stack instead of the rich editorial layout. This document is that missing contract: every home-page section and shared primitive, with **exact target values lifted from the Lovable source**, the **current gap**, the **WordPress build target**, and **acceptance criteria**. It supersedes the `[ASSUMPTION]` deferrals in DESIGN.md/EXPERIENCE.md for the surfaces it covers.
>
> **v2 status.** Revised after a four-lens reviewer gate (rubric · fidelity · buildability · accessibility) — full findings in `validation-report.md`. Fidelity confirmed ~70 values correct; the fixes below close the token-layer, cross-layer, mechanism, and a11y gaps the review found. **`status: final`** (Cobus sign-off 2026-07-19) — all open decisions resolved, no pre-build gates remain.
>
> **How to read values.** Colours reference `theme.json` token slugs (production source of truth) — never hardcode hex. **Alpha tints** (e.g. `terracotta/10`) are expressed as `color-mix(in srgb, var(--wp--preset--color--primary) 10%, transparent)` with an opaque fallback declared first — **not** `hsl(var/a)` (the palette tokens are opaque hex, so channel-splitting doesn't work). "White" is the token **`surface-alt`** (#FDFCFA — the existing `button-primary.color` convention), never literal `#fff`. Sizes are resolved Tailwind values (px/rem). Afrikaans copy is authoritative — English strings are Lovable placeholders, replaced from `ui-copy-translations.md` / migrated DB. Visual/structural spec; copy + dynamic-data wiring are cross-referenced, not re-specified.
>
> **Three-layer rule (binding).** The theme holds **no business logic and performs no computation** (no read-time math, no count aggregation, no per-challenge queries in patterns/templates). All dynamic values come from `ink-core` (dynamic blocks / block bindings). Where a section needs data, the AC names the `ink-core` seam.

---

## §0 — Global foundations (fix first; everything depends on them). → Epic 19 story 19-1.

### 0.1 Button element styles — **the single highest-impact fix**
**Current gap:** `theme.json` has **no `styles.elements.button`** rule (only a font-family), so buttons fall to WP core defaults (wrong radius/colour/typeface). *(Confirm the exact staging pill symptom's source during build — core's default is a slight round, not a 9999px pill; the lozenge may come from a pattern-level class.)*

**Target (from `button.tsx`):** base radius `rounded-md` = **6px**; `xl` size uses `rounded-lg` = **8px**. Primary ("literary") = terracotta fill / `surface-alt` text / **`shadow.md`** (Lovable `shadow-soft` = theme.json `md`, *not* `sm`) / **Lora**; secondary ("literary-outline") = **2px** terracotta border / terracotta text / Lora, invert to terracotta-fill + `surface-alt`-text on hover.

**WP target & acceptance criteria:**
- [ ] Add `styles.elements.button` to theme.json: `border.radius: var(--wp--custom--radius--md)` (6px), background `var(--wp--preset--color--primary)`, text `var(--wp--preset--color--surface-alt)`, `fontFamily` Lora. Default (no style) buttons must **not** render as pills — radius ≤ 8px.
- [ ] Register block styles on `core/button`: **`is-style-ink-primary`** (terracotta fill, `surface-alt` text, `shadow.md`, hover → `primary-light`) and **`is-style-ink-outline`** (transparent, 2px `primary` border, `primary` text, hover → fill `primary` + `surface-alt` text). Both Lora. Register `-sage` / `-sage-outline` likewise (used by §7).
- [ ] **Button SIZE is NOT a block style.** `register_block_style` is a single radio axis, so size × variant can't combine, and "height" is not a button property. Bake **size per-instance** in the locked patterns via `core/button` spacing/typography supports referencing tokens: sm/default/lg/xl → padding `px-3/px-4/px-8/px-10` (12/16/32/40px) + font-size 14/14/16/18px + `min-height` 36/40/44/48px. `xl` uses `{rounded.lg}` 8px. (Optional `ink-btn-sm/lg/xl` utility classes in `home.css` for later reuse.)
- [ ] **Focus (a11y — was missing):** visible `:focus-visible` ring on every button — 2px `primary` outline + 2px offset (Lovable `focus-visible:ring-2 ring-ring ring-offset-2`). Applies to nav + card links too (§1, §6).
- [ ] Transition `all .15s ease` on hover; disabled = 50% opacity.

### 0.2 Type ramp extension (hero scale)
**Current gap:** theme.json `fontSizes` cap at `3xl = 2rem` (32px); hero + CTA need up to **48px**. Note `3xl`=32px vs Lovable `text-3xl`=30px — a 2px low-end drift.
**Target:** hero `h1` = 30 → 36 → 48px, weight **600**, line-height **1.25**; CTA `h2` same; section titles 30→36px.
**AC:**
- [ ] Add fontSize presets `4xl` (36px) and `5xl` (48px). theme.json v3 already has `typography.fluid:true` — supported since WP 6.1.
- [ ] Make hero + CTA headline **fluid** via a `hero` preset: `clamp(1.875rem, 1.2rem + 3vw, 3rem)` (30→48px). Add the `hero-display` role = Lora / that fluid size / 600 / 1.25 (in WP: a fontSize preset + an element/block-style applying Lora — there is no first-class "role").
- [ ] Section titles: accept `3xl`=32px as the low end, OR add a true 30px step. Document the 2px choice; don't silently render 32 where 30 is specced.

### 0.3 Two-tone gradient heading accent
**Target (`.text-gradient-primary`):** accent phrase = `linear-gradient(135deg, primary, primary-light)` clipped to text.
**AC:** [ ] In the **locked hero pattern**, split the accent phrase into its own inline element with class `.ink-text-gradient` styled in `home.css` (`background:linear-gradient(...);-webkit-background-clip:text;background-clip:text;color:transparent`), wrapped in `@supports` with a solid `primary` fallback. (FSE can't apply a block style to part of a heading's rich text, so this is pattern markup + CSS, not an editor toggle — acceptable because the hero is locked.)

### 0.4 Plus-pattern background texture
**Target (`HeroSpotlight.tsx:48`):** absolutely-positioned layer at **opacity 0.03**, `background-image` = an inline SVG data-URI of a 60×60px plus/cross tile, black fill. (Reproduce the data-URI verbatim from source.)
**AC:**
- [ ] Store the SVG data-URI as `settings.custom` (`--wp--custom--ink--pattern--plus`) or inline in `home.css`; apply via `.ink-hero-texture::before` (`position:absolute; inset:0; opacity:.03; z-index:0; pointer-events:none`), content above at `z-index:1`.
- [ ] The texture layer is **decorative** — `aria-hidden` / not exposed to AT; must never intercept clicks.

### 0.5 Card hover-lift + shared card recipe
**Current gap:** `is-style-card` has border+shadow but **no hover-lift**.
**Target (`.card-hover`):** `transition: all .3s ease`; hover → `translateY(-4px)` + `shadow.lg`.
**AC:** [ ] Extend/ add `is-style-ink-card` block style with hover-lift + `shadow.lg` on hover (hero challenge card, featured story cards). **Reduced-motion (a11y):** disable the transform under `prefers-reduced-motion` — see §0.6.

### 0.6 Animations + motion/focus safety
**Target:** `.animate-fade-up` (`fadeUp .6s ease-out`, opacity 0→1 + translateY 20→0, staggered `animation-delay`); `.underline-slide` nav underline (2px terracotta, width 0→100% on hover, `.3s`). CSS-only, fire once on mount; no Framer/scroll-triggers.
**AC:**
- [ ] Add `fade-up` keyframes + `underline-slide` to `home.css`; apply underline-slide to nav links + "Sien alle werke".
- [ ] **`prefers-reduced-motion` covers ALL motion, not just fade** (was scoped too narrowly): disable fade-up, the §0.5 hover-lift translate, `hover:scale-105` (§7 chips), and underline-slide transitions.

### 0.7 Tier & gold colour tokens `[NEW in v2 — owner decision]`
**Current gap:** DESIGN.md declares `brons`/`silwer`/`goud` (`[NEW]` C9) in frontmatter, but **theme.json has none of them** (only `gold-muted` + `warning`). The winner card's two-stop bright-gold→muted-gold gradient can't be built from the muted token alone. *(v1 wrongly stated `goud` already existed — corrected.)*
**Decision (Cobus):** add a bright gold token for the gradient AND register the full tier set. **Naming discipline:** `goud` is the **Goud *tier* colour** = muted #C9B88A (DESIGN.md C9 — must stay muted, it's a Gradering rank cue); the winner-card **gradient top stop** is a *distinct* bright token to avoid overloading the tier colour.
**AC:**
- [ ] Add to theme.json palette: **tier tokens** `brons` #A6754C, `silwer` #9AA3AD, `goud` #C9B88A (= `gold-muted`, the tier value — unchanged from DESIGN.md); **plus a bright** `gold` #E8B130 (Lovable `--gold`) for the winner-card gradient top stop only. `brons` #A6754C (warm bronze) / `silwer` #9AA3AD (cool silver-grey) are **working defaults (owner-approved 2026-07-19)** — build with them; may be refined after the first demo (not a build blocker).
- [ ] Winner-card gradient = `color-mix(gold 10%, transparent) → color-mix(gold-muted 20%, transparent)` (bright → muted; §5).
- [ ] Gradering indicator + winner rank labels use `brons`/`silwer`/`goud` (muted) + Meester=`primary`, always paired with tier text + icon (never colour-alone — DESIGN.md a11y rule). `gold` (bright) is a decorative gradient/tint token, **not** a rank colour.

### 0.8 Home CSS layer (mechanism)
**Current gap:** no enqueued stylesheet exists (`grep` finds zero `wp_enqueue_style`); the theme.json `css` string targets only `ink-core` widgets. §0.3–0.6 all depend on a real stylesheet.
**AC:** [ ] Add `wp_enqueue_style('ink-foundation-home', get_theme_file_uri('assets/css/home.css'), [], $theme->get('Version'))`, gated to the front page, mirroring the existing `wp_enqueue_script` pattern. *(Note: block-style `inline_style` CAN hold `:hover`/`@keyframes` — the existing theme.json `css` string already ships an `:hover` — but the enqueued file is the right home for the volume of home CSS.)*

### 0.9 Icon system (shared)
**Current gap:** every button/card carries a Lucide icon (BookOpen, ArrowRight, PenLine, Sparkles, Crown, Trophy, Calendar, Users, Clock, Heart, MessageCircle) but the theme ships **no icon system** (zero inline SVG in patterns; `core/button` has no icon support).
**AC:** [ ] Establish an inline-SVG convention in 19-1: icons in **locked static patterns** = hand-placed inline `<svg>` (`fill:currentColor`, 16px `size-4`, `aria-hidden`) inside the button/card flex row; icons in **dynamic sections** (winner/challenge/featured counts) = emitted by the `ink-core` block PHP. Icons carrying meaning (reaction counts) get an accessible text label.

---

## §1 — Header / Nav bar
**Current:** all 7 nav links + logo present; `Begin skryf` is a plain nav-link, not a CTA button.
**Target (`Header.tsx`):** sticky `top-0` z-50; `surface`/95 + `backdrop-blur` 4px; 1px bottom border; row height **64px**; 16px gutter. Logo: feather 24×24 terracotta + wordmark **Lora 20px/600** (gap 8px). Nav links `muted-text`→`text`, gap **32px**, underline-slide, hidden < 768px → hamburger. Actions gap 12px: `My profiel` = ghost (36px), `Begin skryf` = literary CTA (36px, `px-3`).
**AC:**
- [ ] `Begin skryf` renders as `is-style-ink-primary` (sm), not a bare link.
- [ ] Sticky header with translucent `surface`/95 + backdrop-blur + 1px bottom border; row height 64px.
- [ ] Add the **feather glyph** (terracotta, 24px, `aria-hidden`) beside the wordmark; confirm wordmark weight **600** (current `site-title` is 700).
- [ ] Nav links `muted-text`→`text` with underline-slide + **visible focus ring**; collapse to hamburger < 768px.

## §2 — Hero (HeroSpotlight)
**Current:** single-column inline stack; no badge, no split, no side card, no texture, heading too small, buttons over-rounded. **Biggest gap.**
**Target (`HeroSpotlight.tsx`):** section `py-12 md:py-20` (48/80px) + plus-pattern (§0.4); layout single-column < 1024px, **50/50 two-column ≥ 1024px** (gap 32→48px), `items-center`. Left: badge pill (`px-4 py-1.5`, `primary`/10 tint, `primary` text, `rounded-full`, 14px/500, sentence case, 24px below) → motto; h1 hero-display (30→48px fluid/600/1.25) with gradient accent phrase → *"Stories wat verdien om gelees en gekoester te word"*; body `muted-text` 16px/1.625; two `lg` buttons (BookOpen "Begin lees" primary + arrow "Deel jou werk" outline).
**AC:**
- [ ] Two-column hero ≥1024px (content left, challenge card right §3), single column below — via **`core/group` Grid layout + `home.css` `@media (max-width:1023px)`**, NOT `core/columns` (its ~782px fixed stack won't hit 1024px).
- [ ] Badge pill present, `primary`/10 via `color-mix`, 14px/500, sentence case.
- [ ] Heading fluid 30→48px/600/1.25 with the inline `.ink-text-gradient` accent phrase (§0.3).
- [ ] Two `lg` buttons per §0.1 (44px, 6px radius, icons 16px).
- [ ] Plus-pattern texture behind the section (§0.4); single visible `h1` on the page (heading order).
- [ ] **Badge contrast (a11y):** darken pill text toward `text` (terracotta-on-`primary`/10 is ~3.6:1) so the small/uppercase badge (§3) clears the readability floor.

## §3 — Weekly-challenge card (hero right column)
**Current:** rendered as a separate full-width static stack (`huidige-uitdaging.php`, "geen per-uitdaging-logika").
**Target (`HeroSpotlight.tsx:84`):** card `surface-alt`, `rounded-xl` 12px, 1px border, `p-6 md:p-8` (24→32px), `shadow.sm` (Lovable `shadow-card`), hover-lift; 96×96 `primary`/5 `rounded-bl-[4rem]` (64px) corner behind content; type badge (`primary`/10, 12px/600 UPPERCASE `tracking-wide`, Sparkles 14px) → "Uitdaging"; Calendar 12px + deadline meta; h2 Lora 20→24px/600; body `muted-text` 14→16px; CTA literary (40px, full-width < 640px).
**AC:**
- [ ] Challenge card in the hero **right column** ≥1024px, stacks below on mobile; `rounded-xl` 12px, 1px border, `surface-alt`, `shadow.sm`, hover-lift (reduced-motion safe).
- [ ] Decorative 64px `rounded-bl` corner tint (`aria-hidden`), behind content.
- [ ] UPPERCASE 12px/600 type badge (Sparkles) + Calendar deadline meta; `h2` (correct heading order under the hero `h1`).
- [ ] **Data (ink-core, blocker):** wire to the live current `uitdaging` (title, prompt excerpt, deadline) via a **new `ink/huidige-uitdaging` dynamic block** (thin `render` + pure `toHtml` + data seam + graceful collapse, house style per `FeaturedWinners`/`HomepageStrip`). Theme embeds + styles the block; the open-`uitdaging` query lives in `ink-core`. Replaces the static teaser.

## §4 — ~~Stats row~~ **REMOVED** `[owner decision, v2]`
**Decision (Cobus):** the 12K+/48K+/150K+ vanity-number row is **dropped** — it conflicts with INK's binding **"weerklank bo bereik"** principle (EXPERIENCE.md rejects vanity-reach framing). Do **not** build it.
**AC:**
- [ ] No stats row on the Tuisblad. If a quiet editorial beat is wanted in its place, use a single calm line (e.g. the motto) — **no counts, no "+N" vanity metrics**. This also resolves the review finding that the stats row lived in only one of three contract files.

## §5 — Feature cards (Uitdaging + Wenner) — the winner banner
**Current:** absent. `ink/wenner-kollig` is wired but renders empty (no `ink_home_featured_winner` provider); and its `toHtml()` emits a flat `<h2>+<ul>/<li>`, so the rich card **cannot be styled onto it from the theme**.
**Target (`ChallengeSection.tsx`):** section `py-16`, `secondary`/30 bg; `grid lg:grid-cols-2 gap-8` (stack < 1024px).
- **Card A — Uitdaging:** `surface-alt`, `rounded-2xl` 16px, `p-8`, `shadow.md` (`shadow-soft`), 1px border; 128×128 `primary`/5 `rounded-bl-full` corner; icon tile (`primary`/10, `rounded-lg`) + Trophy 20px; eyebrow 14px/500 `primary`; title Lora 24→30px/600; meta (Calendar + Users 16px, 14px `muted-text`); literary button.
- **Card B — Wenner (winner banner, C9):** gradient `gold`/10 → `gold-muted`/20 (135°, bright→muted), `rounded-2xl` 16px, `p-8`, 1px `gold-muted`/30 border, **no shadow**; Crown watermark 96×96 `gold`/20 offset `-top-4 -right-4` (`aria-hidden`); icon tile (`gold`/20) + Crown 20px; **eyebrow "[Maand] wenner" set in `text`/`muted-text` — NOT gold-on-gold** (a11y critical: gold text on gold ground ≈1.8:1); italic Lora quote; author block: avatar 48×48 `rounded-full` 2px `gold-muted`/30 (with **alt text**) + name 16px/500 + "Nde uitdaging-wen" 14px.
**AC:**
- [ ] Two-column feature section below the hero band ≥1024px; Uitdaging card per target (16px radius, `shadow.md`, `rounded-bl-full` corner, Trophy eyebrow).
- [ ] **Winner card (cross-layer, Epic 12A blocker):** upgrade the `ink/wenner-kollig` block markup to emit the card DOM — avatar, `<blockquote>` quote, rank label (text + icon), gradient via `goud`/`gold-muted` token classes — behind its existing `ink_home_featured_winner` seam. Theme supplies token styling only. **Do not attempt in the theme alone.**
- [ ] Rank colour always paired with **text + icon** (Crown), never colour-alone; the eyebrow/rank words are legible (`text`/`muted-text`, not gold-on-gold).
- [ ] Per-rank variants: **"[Maand] algehele wenner"** (1st, more prominent) vs **"[Maand] wenner"** (2nd/3rd), ordered algehele-first (EXPERIENCE.md).
- [ ] No live winner → section **collapses gracefully** (no placeholder).

## §6 — Featured bydraes ("Die redakteur se keuse")
**Current:** 3 identical hardcoded placeholder cards ("Titel van die werk / deur [skrywer] / href=#"), symmetric, no hover.
**Target (`FeaturedWorks.tsx`):** section `py-16`; header (`items-end justify-between mb-10`): eyebrow 14px/500 `primary` UPPERCASE `tracking-wider` + title Lora 30→36px/600 + "Sien alle werke" link (`primary`, underline-slide, hidden < 768px). Grid `md:grid-cols-2 gap-6` (1 col < 768px), **asymmetric**: first (featured) card `md:col-span-2` horizontal flex; rest fill the 2-col grid. Card: `surface-alt`, `rounded-xl` 12px, `p-6`, 1px border, hover-lift; category pill (`secondary`, 12px/500, `rounded-full`); read-time (Clock 12px + "N min"); title Lora 600 (featured 24px / standard 20px), hover → `primary`; excerpt (`muted-text`, featured 16px / standard 14px `line-clamp-2`); footer: avatar 32×32 (alt text) + name 14px/500, Heart 16px + count / MessageCircle 16px + count.
**AC:**
- [ ] Section header with UPPERCASE terracotta eyebrow + 30/36px title + "Sien alle werke" (underline-slide, focusable).
- [ ] **Asymmetric grid** via `core/group` Grid layout with per-child `columnSpan` (featured spans 2) — NOT `core/columns`.
- [ ] Cards `rounded-xl` 12px + hover-lift (reduced-motion safe); title hover → terracotta; card titles `h3` (correct heading order).
- [ ] Category pill, read-time, avatar (alt), author, and reaksie counts (Heart/MessageCircle) — **counts without verbs**, `_n()` af plurals, icons given accessible labels.
- [ ] **Data (ink-core, blocker):** real featured storie/gedig/artikel via `core/query` loop or an `ink-core` featured-stream block. **Read-time computed in ink-core from word count** (owner decision) + engagement counts are `ink-core`-owned values — **never computed in the theme**. No "Titel van die werk"/`href="#"` in output; **empty feed → the section hides entirely** (owner decision — no empty-state copy; brownfield migration carries ample data, so an empty feed should never surface).

## §7 — Borg (sponsor) strip
**Current:** `ink/borg-strook` present, collapses to empty (correct).
**Target (`SponsorsSection.tsx`):** section `py-16`, `secondary`/20; centered **sage** eyebrow + serif h2 (24→30px) + intro paragraph; per-tier chips (`px-6 py-3 rounded-lg`, `text-lg`/`text-base`): **goud** = `goud`/10 bg + `goud`/20 border; **silwer** = `secondary` bg + border; **brons** = `muted`/50 bg; `hover:scale-105`; `sage-outline` "Word 'n borg" button.
**AC:**
- [ ] ≥1 active `borg` → render strip (per-tier chips + sage eyebrow + intro + "Word 'n borg" sage-outline CTA); multiple → rotate/wrap; no active sponsor → collapse (current behaviour is correct).
- [ ] Alt text on borg logos; chip `hover:scale-105` is reduced-motion safe.

## §8 — CTA band
**Current:** present but flat `secondary`, no gradient.
**Target (`CallToAction.tsx`):** section `py-20`; band gradient `primary` → `primary-light` (135°), `rounded-3xl` 24px, `p-10 md:p-16` (40→64px), centered, `surface-alt` text; two `surface-alt`/5 decorative circles (256px TL, 192px BR). Heading Lora 30→48px/600; body 18px `max-w-2xl` (672px); buttons `xl` (48px, `rounded-lg` 8px, `px-10`): primary = `surface-alt` fill / `primary` text + PenLine → "Begin vandag skryf"; secondary = `surface-alt`/30 border + `surface-alt` text + BookOpen → "Ontdek stories".
**AC:**
- [ ] Terracotta gradient band, `rounded-3xl` 24px, 40/64px padding, two faint decorative circles (`aria-hidden`).
- [ ] Heading `surface-alt` Lora up to 48px (`h2`); **body at full `surface-alt` (not /80)** so it clears the readability floor over the terracotta gradient (white/80 ≈3.4:1); max 672px, centred.
- [ ] Two `xl` buttons (48px, 8px radius, focus ring): `surface-alt`-fill/`primary`-text primary + `surface-alt`-outline secondary, icons 16px.

## §9 — Buttons (shared primitive reference)
| Size | Min-height | Padding | Text | Radius |
|---|---|---|---|---|
| `sm` | 36px | `px-3` 12px | 14px/500 | 6px |
| default | 40px | `px-4 py-2` 16/8px | 14px/500 | 6px |
| `lg` | 44px | `px-8` 32px | 16px | 6px |
| `xl` | 48px | `px-10` 40px | 18px | **8px** |

| Variant (block style) | Resting | Hover |
|---|---|---|
| `is-style-ink-primary` (literary) | `primary` fill, `surface-alt` text, Lora, **`shadow.md`** | `primary-light` fill |
| `is-style-ink-outline` | 2px `primary` border, `primary` text, Lora | fill `primary` + `surface-alt` text |
| `is-style-ink-sage` / `-sage-outline` | `accent` fill / 2px `accent` border | `accent-light` / fill `accent` |

**AC:**
- [ ] Variants registered as `core/button` block styles (color/border/radius/font + `:hover` via `inline_style`); **size baked per-instance** in locked patterns (not a block style — §0.1); radius per table (never pill).
- [ ] Every button has a visible `:focus-visible` ring (2px `primary` + offset); icons 16px `aria-hidden`, 8px gap.

## §10 — Footer
**Current:** present, solid (3 columns + social + copyright), plain.
**Target (`Footer.tsx`):** `secondary`/30 bg, 1px top border, `py-12` (48px), `mt-20` (80px); **4-column** ≥768px (brand+blurb / Ontdek / Gemeenskap / Ondersteun ons); brand = feather 20px + Lora 18px/600 wordmark + `muted-text` 14px blurb; link cols = serif h-heading + `space-y-2` 14px `muted-text` links (hover → `text`); bottom bar (1px top border) copyright 14px + "Gemaak met ♥…" (filled terracotta Heart 16px).
**AC:**
- [ ] **4-column** layout ≥768px (brand/blurb column + the 3 link groups — current staging shows 3).
- [ ] `secondary`/30 bg, top border, 48/80px spacing; filled-terracotta heart.
- [ ] Org copy = Afrikaans placeholders ("Niewinsgerigte gemeenskapsorganisasie", `[stigtingsjaar]`) — never US "501(c)(3)" (the Lovable footer's is placeholder to discard).

---

## §11 — Dynamic-data gaps (ink-core dependencies — block full fidelity)
| Home element | Needs | Current | Owner / epic |
|---|---|---|---|
| Weekly-challenge card (§3) | current open `uitdaging` via new `ink/huidige-uitdaging` block | static teaser | ink-core (Epic 12/12A-adjacent) |
| Winner card (§5) | latest `wenneraankondiging` via `ink_home_featured_winner` **+ block markup upgrade** | wired seam, flat DOM, empty | **Epic 12A (cross-epic blocker)** |
| Featured bydraes (§6) | featured stream + read-time (from word count) + engagement counts | hardcoded placeholders | ink-core |
| Borg strip (§7) | active `borg` | wired, collapses correctly | done |
| ~~Stats row (§4)~~ | — | removed (owner decision) | n/a |

**AC:** [ ] Each dynamic section renders real data or **collapses gracefully** — no placeholder ("Titel van die werk"/"[skrywer]"/`href="#"`) ever reaches production. No theme-side computation.

## Token-mapping notes (v2)
1. **Tier/gold tokens** — `brons`/`silwer`/`goud`(bright)/`gold-muted` to be **added to theme.json** in 19-1 (§0.7). *(v1 wrongly said `goud` existed.)* `brons`/`silwer` hex = pre-build confirmation gate.
2. **Alpha tints** — `color-mix(in srgb, var(--token) N%, transparent)` with an opaque fallback declared first. **Do not** use `hsl(var/a)` (opaque hex tokens can't be channel-split).
3. **"White"** = `surface-alt` token (never `#fff`).
4. **Shadows** — `shadow.sm` = Lovable `shadow-card` (cards); `shadow.md` = Lovable `shadow-soft` (primary button + Uitdaging card); `shadow.lg` = `shadow-elevated` (hover-lift).

## Open decisions — resolved in v2
- ✅ **Gold token** → add bright `goud` + register tier tokens (§0.7).
- ✅ **Stats row** → OMIT (vanity-reach conflict) (§4).
- ✅ **Read-time** → computed in `ink-core` from word count (§6).
- ✅ **brons/silwer hex** → working defaults #A6754C / #9AA3AD (owner-approved); may be refined after the first demo, not a build blocker (§0.7).
- ✅ **Empty featured-feed** (§6) → section hides entirely; no empty-state copy (brownfield data means it's never seen).

*All open decisions resolved — no pre-build gates remain.*

## Suggested story slicing (Epic 19)
- **19-1 Foundations:** `elements.button` + button variant block styles + per-instance sizing convention; type ramp (`4xl`/`5xl` + fluid `hero`); **tier/gold tokens (§0.7)**; **icon convention (§0.9)**; **`color-mix` tint convention**; **enqueued `home.css` (§0.8)**; focus-ring + reduced-motion base. *(Everything downstream depends on this.)*
- **19-2 Hero:** Grid-layout two-column split + badge + gradient heading + plus-pattern (§0.3, 0.4, §2). *(Stats row removed — not built.)*
- **19-3 Cards:** hero challenge card + Uitdaging + Winner feature cards + hover-lift (§0.5, §3, §5). **Split presentation (build against collapsing block) from data-wiring;** §5 winner-card markup is an **Epic 12A blocker**.
- **19-4 Featured bydraes:** asymmetric Grid + real-data wiring (§6). **Split presentation from data-wiring;** read-time/counts are ink-core.
- **19-5 Polish:** CTA gradient + header CTA/feather + footer 4-col + borg per-tier chips + animations (§8, §1, §10, §7, §0.6).
- **Data (ink-core prereqs, sequence ahead of the card stories):** `ink/huidige-uitdaging` block (§3); `ink_home_featured_winner` provider **+ `ink/wenner-kollig` markup upgrade** (§5, Epic 12A); featured-stream + read-time + counts (§6). These are **hard blockers**, not parallel work — a card story run before its provider ships graceful-collapse (no live data) silently.
