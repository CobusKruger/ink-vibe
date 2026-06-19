---
name: INK
description: Visual identity for INK — a warm, literary community publishing platform for Afrikaans writers, poets, and readers. Editorial-first; reading is the hero.
status: final
sources:
  - {planning_artifacts}/prds/prd-ink-vibe-2026-06-14/prd.md
  - {project_knowledge}/specs/ink-consolidated-spec.md
  - {project_knowledge}/design-handoff/tokens/theme-tokens.json
  - {project_knowledge}/design-handoff/tokens/token-map.md
  - {project_knowledge}/design-handoff/lovable-repo-analysis.md
  - {project_knowledge}/design-handoff/agent-brief.md
  - {project_knowledge}/mockup-readiness-assessment.md
updated: 2026-06-15
colors:
  # Light mode (base). Hex from design-handoff/tokens/theme-tokens.json.
  # Slug names: primary/secondary/accent/surface/surface-alt/text/muted-text are
  # declared canonical in token-map.md. The rest follow kebab convention — [ASSUMPTION] on the slug.
  primary: '#EA4015'              # terracotta
  primary-light: '#EF6842'        # [ASSUMPTION] slug
  secondary: '#EDE9E0'            # cream / warm neutral
  accent: '#4D8066'               # sage
  accent-light: '#5C9979'         # [ASSUMPTION] slug
  surface: '#F8F6F2'              # warm off-white page base
  surface-alt: '#FDFCFA'          # raised surface
  text: '#1A1D21'                 # near-black
  muted-text: '#6B7280'           # secondary text
  success: '#4D8066'              # = accent (sage)  [ASSUMPTION] slug
  warning: '#D4A418'              # amber/gold       [ASSUMPTION] slug
  danger: '#EF4444'               # error red        [ASSUMPTION] slug
  border: '#E8E4DC'               # warm light grey  [ASSUMPTION] slug
  highlight: '#FFE066'            # gold text-highlight (line resonance)
  highlight-foreground: '#1A1D21' # text on highlight
  gold-muted: '#C9B88A'           # muted gold accent
  # Gradering (tier) colour tokens — [NEW, Sprint Change 2026-06-20 / C9].
  # Always paired with the tier name + icon; never used as the sole rank cue (a11y).
  brons: '#A6754C'                # [NEW] [ASSUMPTION value] warm bronze — confirm against Lovable
  silwer: '#9AA3AD'               # [NEW] [ASSUMPTION value] cool silver-grey — confirm against Lovable
  goud: '#C9B88A'                 # [NEW] = gold-muted (muted gold, reuses existing token value)
  # Meester reuses {colors.primary} #EA4015 (brand red-orange) — NOT a new token, NOT danger.
  # Dark mode overrides (only 6 tokens redefined in source; the rest inherit — [ASSUMPTION]).
  surface-dark: '#171C1F'
  surface-alt-dark: '#1A1D21'
  text-dark: '#EAE7DF'
  primary-dark: '#EE5830'
  accent-dark: '#6AA88A'
  border-dark: '#2A3035'
typography:
  # Families are from source. Role composition (which size+weight+line-height per role)
  # is [ASSUMPTION] — sources supply independent scales, not pre-composed roles. No letterSpacing token exists.
  display:
    fontFamily: 'Lora, Georgia, serif'
    fontSize: '2rem'      # 3xl / 32px
    fontWeight: '700'
    lineHeight: '1.2'     # tight
  h1:
    fontFamily: 'Lora, Georgia, serif'
    fontSize: '2rem'      # 3xl / 32px
    fontWeight: '600'
    lineHeight: '1.2'
  h2:
    fontFamily: 'Lora, Georgia, serif'
    fontSize: '1.5rem'    # 2xl / 24px
    fontWeight: '600'
    lineHeight: '1.2'
  h3:
    fontFamily: 'Lora, Georgia, serif'
    fontSize: '1.25rem'   # xl / 20px
    fontWeight: '600'
    lineHeight: '1.2'
  body-prose:             # the reading column — legibility-first
    fontFamily: 'Inter, system-ui, sans-serif'
    fontSize: '1.125rem'  # lg / 18px
    fontWeight: '400'
    lineHeight: '1.7'     # relaxed
  body:
    fontFamily: 'Inter, system-ui, sans-serif'
    fontSize: '1rem'      # md / 16px
    fontWeight: '400'
    lineHeight: '1.5'     # normal
  ui-label:
    fontFamily: 'Inter, system-ui, sans-serif'
    fontSize: '0.875rem'  # sm / 14px
    fontWeight: '500'
    lineHeight: '1.5'
  caption:
    fontFamily: 'Inter, system-ui, sans-serif'
    fontSize: '0.75rem'   # xs / 12px
    fontWeight: '400'
    lineHeight: '1.5'
rounded:
  sm: '4px'
  md: '6px'
  lg: '8px'
  xl: '12px'
  2xl: '16px'
  full: '9999px'
  DEFAULT: '6px'          # [ASSUMPTION] — no DEFAULT declared in source; md chosen
spacing:
  # theme.json slugs s-4..s-96 (token-map.md). Labels are px; values rem.
  '4': '0.25rem'
  '8': '0.5rem'
  '12': '0.75rem'
  '16': '1rem'
  '24': '1.5rem'
  '32': '2rem'
  '48': '3rem'
  '64': '4rem'
  '80': '5rem'
  '96': '6rem'
  content: '768px'        # reading-column / content width (layout.contentSize)
  wide: '1400px'          # feature-row / wide width (layout.wideSize)
components:
  # [ASSUMPTION] — sources carry NO per-component token objects. Component visual
  # specifics derive from the Lovable .tsx source + WP block styles. Only token
  # references that are directly grounded in source are stated here.
  button-primary:
    background: '{colors.primary}'
    color: '{colors.surface-alt}'
    radius: '{rounded.md}'
  button-secondary:
    background: '{colors.secondary}'
    color: '{colors.text}'
    border: '{colors.border}'
    radius: '{rounded.md}'
  line-highlight:
    background: '{colors.highlight}'
    color: '{colors.highlight-foreground}'
  card:
    background: '{colors.surface-alt}'
    border: '{colors.border}'
    radius: '{rounded.lg}'
    shadow: 'hover-only'   # cards lift on hover (Lovable intent)
  tier-indicator:
    # Brons/Silwer/Goud/Meester Gradering (ster-gradering) shown on profiles. UI never uses the word "badge".
    # Rank/tier is ALWAYS paired with text + icon — colour alone never encodes rank (a11y).
    brons: '{colors.brons}'       # [NEW] — see Colors; warm bronze
    silwer: '{colors.silwer}'     # [NEW] — cool silver-grey
    goud: '{colors.goud}'         # [NEW] — = gold-muted
    meester: '{colors.primary}'   # [NEW — R3] Meester = brand red-orange #EA4015 (NOT danger #EF4444)
shadow:
  sm: '0 2px 12px -2px hsl(220 20% 12% / 0.06)'
  md: '0 4px 20px -4px hsl(220 20% 12% / 0.08)'
  lg: '0 12px 40px -8px hsl(220 20% 12% / 0.12)'
---

# INK — Design Spine

> The canonical visual identity for INK. This spine wins over any Lovable mock, screenshot, or import on conflict. The Lovable mockup (React + Tailwind + shadcn/ui) is **design intent, not code** — never ported. `theme.json` token names are the production source of truth even where Lovable names differ (`token-map.md`). Paired with `EXPERIENCE.md` (how it works).

## Brand & Style

INK is a literary **tuiste** — a home for Afrikaans writers, poets, and readers — not a marketplace and not a social feed. The aesthetic posture is **warm editorial**: print-inspired, calm, and text-forward. Reading is the hero; the interface gets out of the way of the words.

The palette is organic and named for warmth — terracotta, cream, sage, gold — over the cool greys of generic SaaS. Type pairs a literary serif (Lora) for voice with a quiet sans (Inter) for function. Every surface should feel closer to a thoughtfully set print page than to a dashboard. Quiet over loud; *"'n deurdagte leser tel meer as 'n virale oomblik."*

`[ASSUMPTION]` There is no single verbatim brand statement in the sources; this posture is distilled from the palette naming, the serif display type, and the "literary prose styling" intent in `mockup-readiness-assessment.md`. Confirm or sharpen.

## Colors

Terracotta `{colors.primary}` is the brand signature — used for primary actions, key links, and brand moments; never as a body-text color and never as a large flood (it is an accent of warmth, not a background). Cream `{colors.secondary}` and the warm off-white `{colors.surface}` carry the bulk of the canvas, giving the "print page" feel. Sage `{colors.accent}` is the calm secondary accent (and doubles as `{colors.success}`). Gold `{colors.highlight}` is reserved for the **line-resonance highlight** on reading surfaces — its single most important job — with muted gold `{colors.gold-muted}` for quieter accents such as tier moments. `{colors.warning}` (amber) and `{colors.danger}` (red) are state-only and used sparingly.

**Gradering (tier) colours** `[NEW — Sprint Change 2026-06-20 / C9].` The four Gradering levels carry distinct tokens: **Brons** `{colors.brons}` (warm bronze), **Silwer** `{colors.silwer}` (cool silver-grey), **Goud** `{colors.goud}` (= `{colors.gold-muted}`), and **Meester** `{colors.primary}` — the existing **brand red-orange `#EA4015`** already used on primary buttons. Meester deliberately reuses the brand signature; it is **not** the `danger` red `{colors.danger}` `#EF4444` (a common mistake — they must not be conflated). Because the palette is warm and several of these read as analogous hues, **rank is never conveyed by colour alone**: every Gradering and winner cue pairs the colour with the **tier name (text) and an icon** (a11y, consistent with the NFR-5 readability floor). Note Meester's red-orange is otherwise an action colour, so its tier use must always be visibly a Gradering label (text + icon), never a bare swatch that could be read as a button.

**Dark mode is deferred — light mode only at launch (v1)** (decided 2026-06-15). The six dark-override tokens above (`surface-dark`, `surface-alt-dark`, `text-dark`, `primary-dark`, `accent-dark`, `border-dark`) are kept in this spine as **future-ready scaffolding only**; they are not wired up for v1 and full dark coverage is intentionally out of scope. When dark mode is taken up post-launch, complete the remaining ten tokens and decide the toggle mechanism then.

## Typography

**Lora** (serif) is the editorial voice — display and all headings. **Inter** (sans) is the functional counterpoint — body, UI labels, captions. The reading column uses `body-prose` at 18px / 1.7 line-height for sustained legibility; this is deliberately more generous than UI `body` (16px / 1.5).

Production type ramp (theme.json `fontSizes`, slugs `xs`–`3xl`): 12 · 14 · 16 · 18 · 20 · 24 · 32 px. Line-heights: `tight` 1.2 · `normal` 1.5 · `relaxed` 1.7. Weights: 400 / 500 / 600 / 700.

Heading case is **sentence case** ("Begin skryf", not "Begin Skryf") — Afrikaans uses fewer capitals than English. Legibility of Afrikaans prose always wins over decorative type.

`[ASSUMPTION]` The mapping of which size/weight/line-height belongs to which role (the composed role objects in frontmatter) is inferred — sources give independent scales only. No `letterSpacing` token exists in source. Confirm role composition.

## Layout & Spacing

A token-driven spacing rhythm (`s-4`…`s-96`); **no one-off values** — every margin, gap, and padding maps to the scale (`agent-brief.md`). Two content widths govern layout: `content` 768px (the reading column, tuned for readability) and `wide` 1400px (feature rows, grids, hero bands). Reserve generous vertical space between major sections to let the reader pause — the editorial gap.

`[ASSUMPTION]` No numeric breakpoints, column counts, or gutter tokens exist in source — only qualitative per-page responsive notes (tab reflow, section stacking, single column, image-crop parity). Breakpoint values are specified behaviorally in EXPERIENCE.md → Responsive & Platform and should be confirmed against the Lovable source.

## Elevation & Depth

Three soft, diffuse shadow steps (`shadow.sm/md/lg`), all using a single cool-dark shadow color at low opacity (0.06 → 0.08 → 0.12) with large blur radii — a gentle glow against warm surfaces, not hard drop shadows. Prefer tonal layering (`surface` vs. `surface-alt`) and 1px `border` outlines over heavy shadow. Cards lift on hover rather than sitting raised at rest.

`[ASSUMPTION]` No z-index/layering scale and no inner/focus-ring shadow tokens in source.

## Shapes

A soft, progressive corner-radius scale: `sm` 4 · `md` 6 · `lg` 8 · `xl` 12 · `2xl` 16 px, plus `full` (9999px) for pills and circular elements. Subtle rounding throughout — never sharp (too aggressive for a literary calm), never fully pill-ed on containers (too "tech"). Cards and larger containers use `lg`; small controls use `sm`/`md`. `[ASSUMPTION]` `DEFAULT` = `md` (none declared in source).

## Components

`[ASSUMPTION]` The sources contain **no per-component visual token objects** — the component frontmatter above states only token references that are directly grounded (primary button on terracotta, highlight on gold, etc.). Concrete per-component visual specs (padding, state colors, focus rings, hover treatments) must be lifted from the Lovable `.tsx` source during theme build and re-expressed as WP block styles. The component **inventory** (what exists and how each behaves) lives in `EXPERIENCE.md` → Component Patterns; this section owns only their *appearance*.

Grounded visual notes:
- **Buttons** — primary = solid terracotta fill, light text; secondary = cream fill or bordered, dark text. `{rounded.md}` corners, generous horizontal padding.
- **Cards** (work cards, writer cards) — `surface-alt` on `border`, `{rounded.lg}`, shadow on hover only; large imagery with serif title + caption subline.
- **Line-highlight** — gold `{colors.highlight}` background with `{colors.highlight-foreground}` text; the signature reading affordance.
- **Gradering indicator** (Brons/Silwer/Goud/**Meester** ster-gradering) — shown on the public **Skrywerprofiel** and in discovery filters, using `{colors.brons}` / `{colors.silwer}` / `{colors.goud}` / `{colors.primary}` (Meester). The word "badge" never appears in UI copy and "tier" never appears in UI (use **Gradering**); the Gradering is *shown* with a colour **plus its name + icon** — never colour-only (a11y). Meester uses the brand red-orange, never `{colors.danger}`.
- **Winner banner** `[NEW — C9]` — the base design already exists on the home page (*The Last Light of Winter* marked "December Winner"; Afrikaans copy "Desember-wenner"). Remaining visual work is the **per-rank variants**: **"[Maand] algehele wenner"** (1st place, more prominent) vs **"[Maand] wenner"** (2nd/3rd), each carrying the relevant Brons/Silwer/Goud colour token **paired with rank text + icon** (no colour-only rank encoding). Behaviour and featured-feed ordering live in `EXPERIENCE.md`.

## Do's and Don'ts

**Do**
- Map every color, spacing, type size, and radius to a `theme.json` token. Generate `theme.json` values from normalized tokens only.
- Treat the Lovable mockup as layout + visual-system *intent*; re-express in WordPress block primitives.
- Keep terracotta as a warm accent; let cream/off-white carry the canvas.
- Use sentence-case headings; prioritize Afrikaans prose legibility.
- Reserve gold `{colors.highlight}` for the line-resonance highlight.

**Don't**
- ❌ Introduce one-off colors, spacing values, or unnamed type sizes.
- ❌ Use raw Lovable token names in markup — normalize to `theme.json` slugs first.
- ❌ Port JSX, Tailwind classes, shadcn components, react-router, or mock data/`localStorage`.
- ❌ Lift any English placeholder copy from the mockup — copy comes from `ui-copy-translations.md` / `afrikaans-terms.md`; real content from the migrated DB.
- ❌ Flood large areas with terracotta or use it for body text.
- ❌ Use the word "badge" in UI for Gradering indicators, or the word "tier" in UI (use **Gradering**).
- ❌ Colour **Meester** with `{colors.danger}` `#EF4444` — Meester uses the brand `{colors.primary}` `#EA4015`.
- ❌ Convey Gradering or winner rank by colour alone — always pair colour with the tier/rank name + icon (a11y).
