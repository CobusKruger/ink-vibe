# 05 · uitdaging-enkel — primitive register (Phase 4b)

**Register:** `maps/_primitive-observations.uitdaging-enkel.csv` — 106 rows: 64 `lovable`, 22 `ink`, 20 `source`.
All three source columns are filled on every `ink` and `source` row (0 unattributed). Header and footer are
excluded (row 00 scope).

Record, do not judge: this document lists variants and the two checks. It chooses no canonical recipe
(Stage 2) and changes no declaration (R8).

## Method

1. **Body-only captures.** The six Phase-4 captures (`tmp/fidelity-captures/<side>/uitdaging-enkel@{390,768,1440}.ndjson`) were filtered to drop the header and footer subtrees, identified by the path prefix of the `header` / `footer` audit-id nodes and the `<header>` / `<footer>` template-part tags. `run.sh primitives` was then run on the filtered copies. 207 raw rows by the tool's classification.
2. **Resolution.** Every tool row was resolved to a vocabulary kind, and instances whose computed values are identical across widths were collapsed into one row (`390|768|1440`):
   - The INK `.wp-block-button` **wrapper** rows (tool kinds `button-primary` / `button-outline`) were folded into the `<a>` row the tool calls generic `button`. That `<a>` row now carries the variant, with the wrapper classes recorded in `note`.
   - Lovable `img.rounded-full` (tool kind `pill`) → `avatar`.
   - Lovable hero tag (tool `pill`) → `badge`, to pair with the INK `.ink-lees-tipe` badge.
   - Lovable rules-list dots (tool `pill`) → **`list-bullet`** (new kind, README §4.1 "Added during row 05").
   - Dropped one Lovable `<section>` with no class or id: the Sonner toast live region, not page content.
3. **Tool gaps — rows added from the capture by audit-id** (noted per row):
   - Lovable `literary` / `literary-outline` buttons: Tailwind `<a>` via `Button asChild`, which no tool rule matches.
   - INK `.ink-uitdaging__inskrywing-skakel` (card) and `.ink-uitdaging__inskrywing-tipe` (pill): their class names miss the tool's `-card` / `-pil` patterns.
   - The `meta-item` instances on both sides, and the Lovable resource `icon-tile`.

   These are harness gaps worth fixing in `extract-primitives.mjs`; this audit did not touch the tool.
4. **Source attribution** followed README Phase 4b. `theme.json:162` is the `styles.css` key; the selector in brackets locates the rule inside that ~100 KB string.

## Variant counts (distinct computed declarations per side; `source` = separate definitions)

| kind | lovable variants / instances | ink variants / instances | source definitions |
|---|---|---|---|
| heading | 7 / 19 | 3 / 6 | 4 (element layer + 3 page rules) |
| section-band | 4 / 5 | 5 / 5 | 2 classes + 2 bare inline-style sections |
| card | 3 / 8 | 1 / 1 | 1 |
| pill | 3 / 11 | 2 / 2 | 2 |
| meta-item | 2 / 5 | 1 / 2 | **2 (identical)** |
| button-outline | 1 / 2 | **2 / 2** | 1 (+ size rule) |
| button-primary | 1 / 2 | 1 / 2 | 1 (+ size rule) |
| badge | 1 / 1 | 1 / 1 | 2 (layered) |
| button-size | — | — | **2 (differing)** |
| button-icon-label | — | — | **2 (identical)** |
| avatar | 1 / 3 | 1 / 1 | 1 |
| eyebrow, icon-tile, list-bullet | 2/3, 1/1, 1/4 | 0 | 0 (no INK counterpart) |

## Check 1 — declaration comparison

- **button-size (PRIMITIVE).** This page's buttons are sized by a page-scoped `theme.json` rule (`.ink-uitdaging__hero .wp-block-buttons .wp-block-button__link, .ink-uitdaging-cta …`: `padding:0 s-32; height:44px; font-weight:medium`), not by the `.ink-btn-lg` size class (`home.css:196,218`).
  - Same: height and horizontal padding.
  - The page rule lacks `justify-content:center` and `white-space:nowrap`, and adds `font-weight:medium`. `.ink-btn-lg` also sets `font-size:md`.
  - Measured consequence (drift report): `white-space normal` vs Lovable `nowrap`, and `justify-content normal` vs `center` on all four page buttons.
- **button-outline renders two INK variants from one recipe.** `uitdaging-btn-outline` sits in a wrapper carrying `.ink-btn-icon`, so it computes `display:inline-flex`. `uitdaging-cta-btn-secondary` has no icon class, so it computes `display:inline-block`. Lovable renders both as one variant (`display:flex`).
- **button-icon-label: identical duplicate.** `.wp-block-button.ink-btn-icon .wp-block-button__link{display:inline-flex;align-items:center;gap:s-8}` is defined in `theme.json:162` and again in `assets/css/home.css:303`, with byte-equivalent declarations. On this page only the theme.json copy loads.
- **meta-item: identical duplicate.** `.ink-uitdaging__sluitingsdatum-ry` and `.ink-uitdaging__deelnemers-ry` are two separate rules with identical declarations (inline-flex, centre, gap s-8, muted-text, sm, 1.25rem), each with an identical `.ink-icon { color: primary }` child rule. Computed identical. Against Lovable the gap differs (8px vs 6px) and so does the colour.
- **pill: two INK definitions, differing in padding and fill.** `.ink-uitdaging__toestand-pil` (`2px s-12`, no base background — only via the `.is-oop` / `.is-gesluit` modifiers) vs `.ink-uitdaging__inskrywing-tipe` (`s-4 0.625rem`, `secondary` background, `ink-text` colour, `line-height:1rem`). Both are `radius-full`, `xs` and `medium`.
  - The badge `.ink-lees-tipe` (`4px s-12`, `currentColor 10%` mix) is a third rounded-full chip recipe on the page.
  - Lovable's chips also vary: `px-2 py-0.5` (resource tag), `px-2.5 py-1` (entry type), `px-3 py-1` + border (device chip), `px-3 py-1` (badge).
- **card: replica.** `.ink-uitdaging__inskrywing-skakel` re-declares the `is-style-ink-card` recipe (`functions.php:1564`: surface-alt, 1px border, `radius-xl`, `shadow-sm`, padding s-24, −4px lift on hover) as a separate rule. The same duplication shape as the homepage's PRIM-4. Against Lovable's card: Lovable has no resting shadow, INK has `shadow-sm`; both radii are 12px.
- **heading.** Three INK page rules on top of the element layer:
  - `.ink-uitdaging__hero h1.has-hero-font-size` uses two `!important` rules to override the preset class. That makes four size layers for one h1.
  - `.ink-uitdaging__inskrywings-titel` and `.ink-uitdaging-cta__titel` declare the same size steps (hero-sm → xxxxl at 768) separately; only the former sets `font-family:display`. Computed line-height is 1.2× (43.2px at 36px) on both, vs Lovable's 40px.

## Check 2 — load-scope cross-check

Every class on the rendered page body was traced to its definitions (theme.json `styles.css`, `functions.php`
block styles, `assets/css/*.css`). Comment-only hits were excluded after reading them; for example
`.ink-lees-tipe` appears in `home.css` only inside comments (lines 357 and 949).

- **No live load-scope bug on this page.** Every primitive here is defined in a site-wide layer (`theme.json` or a `functions.php` block style).
- **Latent duplication:** `.ink-btn-icon`, and also `.ink-icon` (theme.json + `home.css` + `skryf.css`), are defined both site-wide and in page-gated sheets. Harmless here, but they diverge the moment one copy is edited.
- **Not a primitive, but unstyled:** `.ink-uitdaging`, `.ink-uitdaging__inskrywings-seksie`, `.ink-uitdaging__sluitingsdatum` and `.ink-uitdaging__sluitingsdatum-etiket` match no rule anywhere (hooks only).
- **Cross-page reach:** `.ink-uitdaging__toestand-pil` and `.ink-uitdaging__sluitingsdatum-ry` are also emitted by `ink-core/src/Challenges/Archive.php` (row 14, `/uitdagings`). They load site-wide, so they are not inert there either.

## Vocabulary

Added `list-bullet` to README §4.1 under "Added during row 05". It was checked against the existing list first:
`count-chip`, `pill` and `step-badge` all carry text; this carries none. All other kinds used here
(`meta-item`, `icon-tile`, `button-size`, `button-icon-label`) already existed.
