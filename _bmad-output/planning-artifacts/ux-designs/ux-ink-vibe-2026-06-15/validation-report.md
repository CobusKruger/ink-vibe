# Validation Report — INK Theme Visual-Fidelity Spec

- **DESIGN.md:** `_bmad-output/planning-artifacts/ux-designs/ux-ink-vibe-2026-06-15/DESIGN.md`
- **EXPERIENCE.md:** `_bmad-output/planning-artifacts/ux-designs/ux-ink-vibe-2026-06-15/EXPERIENCE.md`
- **Spec under review:** `theme-fidelity-spec.md` (same folder, `status: draft`)
- **Run at:** 2026-07-19T20:56:49+02:00
- **Lenses run:** rubric walker · fidelity & traceability · WP/FSE buildability · accessibility

## Overall verdict

**Accept with fixes — the spec is sound; the gaps cluster in three fixable seams, not in the pixel work.** The fidelity lens spot-checked ~70 discrete values against the `ink-lovable` source and found them overwhelmingly correct, every "current gap" claim matched the actual theme code, and the dynamic-data honesty (§11) holds up against `ink-core`. The rubric confirms the contract is source-extractable and internally disciplined (0 critical there). So the spec is a trustworthy build contract — **but not yet buildable as written** in three places:

1. **Token layer** — the winner card depends on `goud`/`brons`/`silwer` tokens that exist in DESIGN.md frontmatter but were **never added to `theme.json`**, and the spec wrongly states `goud` is present. (Convergent: rubric + fidelity, both HIGH.)
2. **Cross-layer coupling** — the rich winner card, the current-uitdaging card, the featured stream, and the stats row need `ink-core` data/markup that the theme cannot supply; the winner card in particular needs an `ink/wenner-kollig` **markup upgrade (Epic 12A)**, not just theme CSS. (Buildability, HIGH + MEDIUM.)
3. **Mechanism friction** — button *size* can't be a block style, buttons need an icon system the theme doesn't have, and `core/columns` won't hit the 1024/768 splits. All buildable, but the naive path walls. (Buildability, HIGH + MEDIUM.)

The accessibility lens adds one **critical** legibility breach (gold-on-gold winner eyebrow ≈1.8:1) and a cluster of floor-completeness gaps (focus states, reduced-motion scope, alt-text scope, several sub-4.5:1 contrast pairs) that the spec should close before it becomes the NFR-5 contract. None of this invalidates the spec — it's a strong draft that needs one revision pass before promotion to `final` and Epic 19 build.

## Category verdicts (rubric walker)

- Flow coverage — **strong**
- Token completeness — **adequate**
- Component coverage — **adequate**
- State coverage — **strong**
- Visual reference coverage — **adequate**
- Bloat & overspecification — **strong**
- Inheritance discipline — **strong**
- Shape fit — **strong**

Extra-lens verdicts: Fidelity & traceability — **accept with fixes**; WP/FSE buildability — **buildable with friction**; Accessibility — **conditional pass** (against the NFR-5 readability floor; WCAG flagged as info only).

## Findings by severity

### Critical (1)

**[Accessibility]** — Gold eyebrow text on the gold-tinted winner card ≈ 1.8:1 (§5)
Gold text on a pale-gold ground is genuinely illegible — a readability-floor breach, not just a WCAG note.
Fix: don't colour the "December-wenner"/rank words gold on a light ground; let the Crown icon + gold tint carry the "gold moment," and set the eyebrow text in `text`/`muted-text`.

### High (8)

**[Token / convergent: rubric + fidelity]** — `goud`/`brons`/`silwer` tier tokens absent from `theme.json`; spec mis-states them as present (§5 AC, Open Decision #1, token-note #1)
`theme.json` has only `gold-muted` (#C9B88A) + `warning` (#D4A418). The `[NEW]` C9 tier tokens live only in DESIGN.md frontmatter. The Lovable winner card is a two-stop bright-gold→muted-gold gradient, so with only the muted token the gradient collapses. No AC adds the tokens.
Fix: correct the note to say only `gold-muted` exists today; add a §0 foundations AC to register `brons`/`silwer`/`goud` (+ a bright-gold top-stop, e.g. from `warning`) as theme.json presets before §5 builds; confirm the `[ASSUMPTION value]` brons/silwer hex against Lovable.

**[Buildability]** — Winner card visual is coupled to `ink-core` block output, not theme-stylable (§5)
`ink/wenner-kollig` (`FeaturedWinners::toHtml()`) emits a flat `<h2>`+`<ul>/<li>`; theme CSS can't invent the avatar/quote/crown/card DOM.
Fix: treat §5 as an `ink-core` change (Epic 12A) — upgrade the block to emit the card DOM (avatar, `<blockquote>`, rank label with text+icon, gold token classes) behind its existing seam; the theme supplies only token styling. Do not try to satisfy §5 in the theme alone.

**[Buildability]** — Buttons need Lucide icons; `core/button` has no icon support and the theme ships no icon system (§2/§3/§5/§8/§9)
Zero inline `<svg>` in patterns; no icon block.
Fix: establish an inline-SVG icon convention in 19-1 — hand-placed `<svg>` (`currentColor`, 16px) in locked patterns; icons in dynamic sections emitted by `ink-core` PHP.

**[Buildability]** — Button *size* (sm/default/lg/xl) is not expressible as a block style (§0.1/§9)
`register_block_style` is a single radio axis; size × variant can't combine. "Height" isn't a button property.
Fix: keep variant as the block style; bake size per-instance in the locked patterns via spacing/typography supports (padding→`s-*`, font-size preset, `min-height`). Optional `ink-btn-*` utility classes in `home.css` for reuse.

**[Accessibility]** — Pale badge pill contrast ≈ 3.6:1 (§2 hero badge, §3 12px UPPERCASE type badge)
Terracotta on `primary/10` fails normal-text 4.5:1.
Fix: darken the pill text (toward `text`) or deepen the pill background; the 12px uppercase badge is small text and most at risk.

**[Accessibility + fidelity, convergent]** — No focus-state ACs anywhere (§0/§1/§9)
Lovable uses `focus-visible:ring-2`; EXPERIENCE.md's floor requires visible focus. Spec specs hover but not focus.
Fix: add an AC for a visible focus ring (2px `primary` + offset) on buttons and nav/card links.

**[Accessibility]** — `prefers-reduced-motion` scoped only to the "optional" §0.6 animations
Doesn't cover the mandated hover-lift (§0.5/§3/§6) or `hover:scale-105` (§7).
Fix: widen the reduced-motion AC to disable all transforms (lift, scale, slide, fade) site-wide.

**[Accessibility]** — Alt-text required only for borg logos (§7)
Author avatars (§5/§6) and featured images uncovered.
Fix: require alt text on featured images + avatars; mark decorative shapes/watermarks/plus-pattern `aria-hidden`.

### Medium (18)

- **[Fidelity]** Primary-button shadow mis-mapped: Lovable `literary` uses `shadow-soft` = theme.json preset **`md`**, but §0.1 AC + §9 table say `shadow-sm` (the flatter card shadow); §0.1 prose already says "shadow-soft" — internally inconsistent. Fix: use `shadow-md`.
- **[Fidelity]** Spec hardcodes white (`#fff`) button text/fill (§0.1/§8/§9), contradicting its own no-hardcode-hex rule; theme.json has no white token. Fix: route "white" to `surface-alt` (existing convention) or a named `on-primary` preset.
- **[Buildability]** `core/columns` stacks at a fixed ~782px, not the spec's 1024px hero / 768px featured splits (§2/§6). Fix: use `core/group` Grid layout + `home.css` media queries.
- **[Buildability]** Asymmetric featured grid (featured card spanning 2 cols) not achievable with `core/columns` (§6). Fix: `core/group` Grid layout with per-child `columnSpan` (WP 6.3+).
- **[Buildability]** `background-clip:text` on a heading sub-phrase can't be an editor block style (§0.3). Fix: split the accent phrase into an inline `.ink-text-gradient` element in the locked pattern; `@supports` + solid fallback.
- **[Buildability + fidelity]** `hsl(var/a)` alpha tints not viable (opaque hex tokens); standardise on `color-mix()` + opaque fallback (token-note #2). Fix: drop the `hsl(var/a)` alternative.
- **[Buildability]** No home CSS layer exists (no `wp_enqueue_style` anywhere); §0.3–0.6 all depend on it (§0 note). Fix: enqueue versioned `assets/css/home.css` gated to front page (correctly slotted first in 19-1). Minor: block-style `inline_style` *can* hold `:hover`/`@keyframes`, so "block styles can't do hover" is imprecise — enqueued CSS still preferred for volume.
- **[Buildability]** Current-uitdaging card (§3) needs a live data surface that doesn't exist (`huidige-uitdaging.php` is static). Fix: add an `ink/huidige-uitdaging` dynamic block (thin render + pure toHtml + seam + graceful collapse) in `ink-core`.
- **[Buildability]** Featured bydraes (§6) + stats (§4): read-time and counts must **not** be computed in the theme (three-layer rule). Fix: `core/query` loop or `ink-core` featured-stream block; counts/read-time are `ink-core`-owned values; make "no theme-side computation" explicit in ACs.
- **[Accessibility]** Contrast cluster: white on terracotta buttons ≈ 4.0:1 (~3.1:1 on `primary-light` hover); `muted-text` on cream ≈ 4.0:1 (§5/§7/§10); white/80 body on CTA gradient ≈ 3.4:1 (§8); sage-as-text ≈ 4.2:1. Fix: nudge these pairs to ≥4.5:1 (darken muted-text on cream; raise CTA body to white/100; avoid sage/gold as body text).
- **[Accessibility]** Heading levels unassigned for §5/§6/§8 section + card titles (skip/duplicate-h1 risk); decorative shapes/watermarks/icons not marked `aria-hidden`; reaction-count icons carry meaning with no accessible label. Fix: assign explicit heading order; aria-hide decorative; label counts (e.g. "342 hartjies").
- **[Rubric]** Stats row exists only in the fidelity spec — no DESIGN.md.Components or EXPERIENCE.md Component Patterns entry, data source undecided. Fix: add a one-line stats entry (behavior: static vs `ink-core` provider) or scope it explicitly as a fidelity-only static primitive.
- **[Rubric]** Orphan `mockups/` reference (EXPERIENCE.md:74) — dir doesn't exist, no mocks rendered (contradicts the decision log). Fix: replace with the real reference basis (Lovable `@5618f39` + the two PNGs + theme-fidelity-spec.md).
- **[Rubric]** `status: final` spines cite a `status: draft` spec — a consumer may treat draft values as ratified. Fix: promote the spec to `final` on sign-off, or annotate spine refs as "(draft — provisional until Epic 19 sign-off)".
- **[Rubric]** No "add tier-colour presets" AC anywhere (overlaps the token HIGH). Fix: add to §0 foundations AC list alongside the fontSize additions.
- **[Accessibility]** 36–40px primary CTAs sit under the 44px comfortable mobile touch target (clears WCAG 2.5.8's 24px min). Fix: ensure primary mobile CTAs meet ~44px via padding.

### Low (~20 — full detail in the per-lens files)

Representative: UJ-2 carries no inline payment-failed branch (rubric); loading-state remains `[ASSUMPTION]`; "full-pill" root-cause phrasing imprecise (fidelity — confirm the pill's actual source); type-ramp 2px low-end drift (32 vs 30px for section titles); §4 AC still lists placeholder stat numbers (tie to data decision + copy-debt); §7 sponsor chips under-specified vs `SponsorsSection.tsx` per-tier chip styling; logo feather icon not required by any §1 AC (+ wordmark weight 600 vs current 700); spec `sources` use absolute machine paths not `{placeholder}` convention; recommend `templateLock:"contentOnly"` on assembled home sections; 19-1 should also land the icon + `color-mix` conventions.

## Reviewer files
- `review-rubric.md` (0 crit · 1 high · 4 med · 8 low)
- `review-fidelity.md` (0 crit · 1 high · 3 med · 5 low)
- `review-buildability.md` (0 crit · 3 high · 8 med · 5 low)
- `review-accessibility.md` (1 crit · 4 high · 6 med · 3 low)

## Recommended disposition
Roll these into a spec revision (an Update) before promoting `theme-fidelity-spec.md` to `final`:
1. **Token fixes** (HIGH): add brons/silwer/goud + bright-gold presets to theme.json via a §0 AC; correct the "goud exists" mis-statement; fix the shadow (`md`) and white-token mappings.
2. **A11y fixes** (CRITICAL+HIGH): recolour the winner eyebrow; add focus-state ACs; widen reduced-motion; extend alt-text; nudge the contrast cluster ≥4.5:1.
3. **Buildability re-slice** (HIGH): 19-1 lands icon convention + `color-mix` + home.css; split each dynamic section into presentation vs data-wiring; make §3 (`ink/huidige-uitdaging`) and §5 (`ink/wenner-kollig` markup upgrade, Epic 12A) explicit blockers, not parallel work; switch column mechanisms to Grid layout.
4. **Housekeeping** (MEDIUM): fix the orphan `mockups/` ref; reconcile the final-cites-draft status; add the stats-row behavioral entry.
