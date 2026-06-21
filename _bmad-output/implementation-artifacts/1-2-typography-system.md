---
baseline_commit: c5821d806c1700331ae3a352f097d8a0646c6fe8
---

# Story 1.2: Typography system

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a reader,
I want legible, Afrikaans-first typography,
so that reading is comfortable and on-brand.

## Acceptance Criteria

1. **Fonts are self-hosted and registered (fontFace)** — Lora (display + heading) and Inter (body + UI) are bundled as `woff2` files under `wp-content/themes/ink-foundation/assets/fonts/` and declared via `settings.typography.fontFamilies[].fontFace` in `theme.json`. Each `src` uses the relative form `file:./assets/fonts/…`; `font-display` is `swap`; weights **400–700** are covered (plus italic where the face provides it). **No Google Fonts CDN or any remote font request** is emitted at runtime — the theme loads only its bundled files. _[Source: architecture.md#`ink-foundation` FSE theme tree line 1008 "assets/ … Lora + Inter fonts"; project-context.md "no hardcoded asset URLs"; spec §9.7 self-host design intent]_
2. **Glyph coverage for Afrikaans** — the bundled subsets include **Latin + Latin-Extended** so every Afrikaans diacritic renders from the bundled face (not a fallback): `ê ë î ï ô ö û ü á é í ó ú à è`, the `'n` apostrophe form, and standard punctuation. No diacritic falls back to Georgia/system-ui. _[Source: project-context.md Afrikaans-first / Gate D; afrikaans-terms.md]_
3. **Families assigned to roles** — in `theme.json` `styles`, **Lora** resolves for the display role and **all heading levels h1–h6**, and **Inter** resolves for body copy and UI surfaces (navigation, buttons, captions/eyebrows). All assignments reference the font-family **slugs** (`var:preset|font-family|display|heading|body|ui`) — never a raw family string in markup. _[Source: theme-tokens.json#typography.fontFamily; token-map.md; epics.md#Story-1.2 "Lora for display/headings and Inter for body/UI"]_
4. **Named scale, fluid where appropriate** — every type size in use resolves to a named scale slug (`xs`…`3xl`); **no unnamed/raw type sizes**. The larger display/heading sizes (`2xl`, `3xl`) are **fluid** (clamp), while small UI/body sizes stay stable. Heading levels h1–h6 have sensible default sizes set in `styles.elements` so hierarchy is correct without an editor hand-picking a size per heading. _[Source: epics.md#Story-1.2 "named scale (xs–3xl), fluid where appropriate"; theme-tokens.json#typography.fontSize; project-context.md Gate A]_
5. **Sentence-case headings (convention, not transform)** — heading copy is authored in Afrikaans **sentence case** ("Waar woorde lesers vind", "Begin skryf"). **No CSS `text-transform` forcing capitalize/uppercase is applied to headings.** Existing patterns/templates are audited and compliant; the convention is documented as a standing Gate D rule. _[Source: spec line 359 + project-context.md "Heading casing: sentence case"; epics.md#Story-1.2 "headings render in sentence case"]_
6. **Line-height / weight roles + validity** — headings use the `tight` line-height and body uses a comfortable reading line-height (`normal`/`relaxed`) via the existing `var:custom|line-height|*` tokens; weights map to the named `var:custom|font-weight|*` tokens. `theme.json` remains valid `version: 3` JSON, loads cleanly in the WP 7.0 Site Editor, and the Lora/Inter families appear and are selectable in Global Styles → Typography. **No Gate A regression** (still token-only; the existing colour/spacing/shadow/radius tokens are untouched). _[Source: theme.json#settings.custom.lineHeight/fontWeight (added in 1.1); project-context.md Gate A + WP 7.0+; architecture.md#Starter-Template NFR-2]_

## Tasks / Subtasks

> **Current state (read before starting):** `theme.json` already declares the four font **families** by name (`display`/`heading` = `Lora, Georgia, serif`; `body`/`ui` = `Inter, system-ui, sans-serif`), the 7-size named scale (`xs`…`3xl`) with global `"fluid": true`, and the `custom.lineHeight` (`tight`/`normal`/`relaxed`) + `custom.fontWeight` (`regular`/`medium`/`semibold`/`bold`) tokens that Story 1.1 added. **What is missing is the actual typography *system*:** the real webfonts are NOT bundled (no `assets/` dir, no `fontFace`), heading levels have no per-level sizing, and fluid is on globally but not tuned per size. This story makes the type *work*, not just *declared*. **Do not regress the 1.1 token set.**

- [x] **Task 1 — Acquire & bundle the Lora + Inter font files (AC: 1, 2)** ✅ **DONE (pre-staged 2026-06-20)**
  - [x] `wp-content/themes/ink-foundation/assets/fonts/` created.
  - [x] Bundled **variable `woff2`** for both families from `@fontsource-variable` v5.2.8 (SIL OFL 1.1): Lora (weight axis **400–700**) + Inter (axis **100–900**), **normal + italic**, **latin + latin-ext** subsets = **8 files** + both `*-LICENSE.txt`. Verified `wOF2` signatures.
  - [x] Italic faces bundled (normal + italic per family).
  - [x] `woff2` only; provenance + version + license + the exact subset `unicode-range`s recorded in `assets/fonts/README.md`.
  - **Files:** `lora-{latin,latin-ext}-wght-{normal,italic}.woff2`, `inter-{latin,latin-ext}-wght-{normal,italic}.woff2`. **Afrikaans diacritics live in the `latin` subset** (Latin-1 `U+00C0–00FF`); `latin-ext` is bundled for completeness. See `assets/fonts/README.md`.

- [x] **Task 2 — Register the fonts via `fontFamilies[].fontFace` in theme.json (AC: 1, 2)**
  - [x] Added the Lora `fontFace` block (4 entries: normal+italic × latin+latin-ext, `fontWeight:"400 700"`) under **both** the `display` and `heading` families, and the Inter block (`fontWeight:"100 900"`) under **both** `body` and `ui` — 16 `src` refs total. Each entry has `fontDisplay:"swap"`, `src:["file:./assets/fonts/…"]`, and per-subset `unicodeRange`. Pasted from `assets/fonts/README.md`.
  - [x] Existing `"fontFamily"` CSS stacks kept intact (`"Lora, Georgia, serif"` / `"Inter, system-ui, sans-serif"`); the four `slug`s and `name`s unchanged.
  - [x] `fontFace.fontFamily` is the raw face name (`"Lora"`/`"Inter"`); duplicate Lora/Inter entries across the two families left as-is (WP de-duplicates emitted `@font-face`).

- [x] **Task 3 — Assign families to roles in `styles` (AC: 3, 5)**
  - [x] Verified `styles.typography.fontFamily = var:preset|font-family|body` (Inter) and `styles.elements.heading.typography.fontFamily = var:preset|font-family|heading` (Lora) — both present from 1.1, not duplicated.
  - [x] Added `core/button` → `var:preset|font-family|ui` (Inter). `core/navigation-link` (ui) and `core/site-title` (display = Lora wordmark) left as-is.
  - [x] No raw family string introduced anywhere in `styles` — slugs only (verified: 0 `Lora…`/`Inter…` literals inside the `styles` object).

- [x] **Task 4 — Per-level heading sizes + tune fluid scale (AC: 4)**
  - [x] Added per-level sizes in `styles.elements`: `h1→3xl, h2→2xl, h3→xl, h4→lg, h5→md, h6→sm` (each `var:preset|font-size|*`). Shared `elements.heading` (family + tight line-height) kept as the common default.
  - [x] Fluid tuning: `xs`–`xl` set `"fluid": false` (stable UI/body); `2xl` → `{min:1.25rem, max:1.5rem}`, `3xl` → `{min:1.5rem, max:2rem}`. `max` = the existing named value, so desktop rendering is unchanged; only mobile scales down.
  - [x] All `size` values kept verbatim from the token file; fluid `min`/`max` are additive.

- [x] **Task 5 — Line-height & weight roles (AC: 6)**
  - [x] Headings inherit `lineHeight = var:custom|line-height|tight` via `elements.heading` — applies to all levels (verified).
  - [x] Body global `styles.typography.lineHeight = var:custom|line-height|normal` (1.5) kept. The `relaxed` (1.7) reading line-height is **deliberately not applied globally** — it belongs on the Epic 7 reading templates.
  - [x] No element-level weight override added: Lora headings render at the regular (400) weight — an intentional, on-brand literary/editorial look, and the only weight not invented from the tokens. Inline `fontWeight:"600"/"700"` in block markup (`site-title`, eyebrow) left as-is; the bundled variable faces carry the full 400–700 axis, so those weights are real, not browser-synthesised.

- [x] **Task 6 — Sentence-case heading audit + convention (AC: 5)**
  - [x] Audited `patterns/`/`templates/`/`template-parts/` + `theme.json styles`: **no `text-transform` on any heading**. The only `text-transform:uppercase` is on the front-page eyebrow **paragraph** "Tuisblad" (a label treatment) — left intact.
  - [x] Existing heading copy is sentence case ("Waar woorde lesers vind" ✓); curated Afrikaans unchanged.
  - [x] Convention documented below (Dev Notes): headings authored sentence-case; never enforced via CSS transform — a Gate D authoring rule.

- [x] **Task 7 — Validate & verify (AC: 1, 6)**
  - [x] `theme.json` parses as valid JSON.
  - [x] All 16 `fontFace.src` paths resolve to existing files in `assets/fonts/` (0 missing).
  - [x] Zero remote font URLs in the theme (only `$schema` points at `schemas.wp.org`); all `src` are `file:./…`.
  - [x] **Live Site-Editor check deferred to Story 1.11** (no running WP env in repo yet), same precedent as Story 1.1's AC-4. Verified by JSON validity + path existence + subset glyph coverage (Afrikaans diacritics in the `latin` subset's `U+0000-00FF`).
  - [x] Gate A re-grep over `patterns/`/`templates/`/`template-parts/`/`theme.json styles`: no hex/rgb/hsl, no untokenised spacing/sizes introduced.

## Dev Notes

### What this story is (and is not)
- **Is:** make the declared type system *real* — self-host & register Lora + Inter (`fontFace`), assign families to display/heading/body/UI roles, set per-level heading sizes, tune fluid sizing for large sizes, apply line-height/weight roles via the 1.1 tokens, and lock the sentence-case authoring convention.
- **Is not:** dark-mode palette wiring (**Story 1.3** — `theme-tokens.json#modes.dark` exists but is out of scope; light mode only at v1), new templates/section shells (**1.4**), the pattern library (**1.5**), block locking (**1.6**), `functions.php`/`ink-core` (**1.7**), or the long-form reading-comfort line-height (`relaxed` 1.7) which is applied on reading templates in **Epic 7**. _[Source: epics.md Stories 1.3–1.7, 7.1–7.2; architecture.md "light mode only at v1"]_

### Why fontFace is the heart of this story
- `theme.json` currently lists `"fontFamily": "Lora, Georgia, serif"` etc. **by name only**. Without `fontFace` declarations and bundled files, WordPress emits **no `@font-face`** — the site silently falls back to **Georgia / system-ui**, so it is *not actually on-brand* and AC "Lora is used for display/headings" fails in the browser. Bundling + `fontFace` is what makes Lora/Inter render. _[Source: theme.json#settings.typography.fontFamilies; WP theme.json v3 fontFace]_

### theme.json `fontFace` mechanics (WP 7.0 / schema v3)
- `fontFace` is an **array on each `fontFamilies[]` entry**. WordPress generates the `@font-face` CSS and (6.5+) registers the font in the Site Editor automatically — **no `functions.php` enqueue needed**, which keeps the theme presentation-only.
- `src` accepts the theme-relative form `"file:./assets/fonts/lora-variable.woff2"` — WordPress resolves it against the theme root. Do **not** hardcode a full URL.
- **Variable fonts:** one file with `"fontWeight": "400 700"` (a range) covers all weights — fewer files, smaller payload. Static fonts need one `fontFace` entry per weight.
- `"fontDisplay": "swap"` avoids invisible text during load (FOIT); acceptable for a content/reading site.
- Both Lora families (`display`, `heading`) need the Lora `fontFace`; both Inter families (`body`, `ui`) need the Inter `fontFace`. WP de-duplicates the emitted `@font-face` by family+weight+style, so repeating entries is fine and expected.

### ⚠️ Guardrails (prevent disasters)
- **Self-host only — no Google Fonts CDN.** Privacy (POPIA/GDPR), performance, and the Cloudflare-locked origin all argue against runtime calls to `fonts.gstatic.com`. Bundle the `woff2` and reference `file:./…`. _[project-context.md "no hardcoded asset URLs"; security: Cloudflare-locked origin]_
- **Latin-Extended is non-negotiable** — a Latin-only subset drops Afrikaans diacritics (`ê ë î ô û` …) to a fallback face, which is a visible Gate D failure. Verify the subset before bundling. _[project-context.md Afrikaans-first / Gate D]_
- **Never enforce heading casing with CSS.** `text-transform:capitalize` mangles Afrikaans (proper nouns, `'n`); `uppercase` is wrong for headings. Sentence case is an **authoring** rule. _[spec line 359; project-context.md]_
- **Do not touch the 1.1 token set** (16 colours, spacing, layout, shadow, radius, line-height, font-weight) or rename the 4 font-family slugs — downstream stories and migration depend on them. Type **sizes** keep their verbatim token-file values; fluid `min`/`max` are additive. _[1-1 story Completion Notes; token-map.md]_
- **No tier/Gradering fonts or colours** — typography is Foundation; Gradering display styling is Epic 5. _[project-context.md THE conflation rule]_

### Source tree (files this story touches)
```
wp-content/themes/ink-foundation/
├── theme.json              # UPDATE — add fontFace to the 4 fontFamilies; per-level heading sizes;
│                           #   fluid tuning on 2xl/3xl; button → ui family; verify line-height/weight roles
├── assets/fonts/           # NEW — bundled Lora + Inter woff2 (Latin + Latin-Extended, weights 400–700)
├── patterns/               # AUDIT only (header-main.php, footer-main.php) — sentence-case + no raw families
├── templates/              # AUDIT only (front-page.html — h1 "Waar woorde lesers vind" is sentence-case ✓)
└── template-parts/         # AUDIT only (header.html, footer.html embed patterns)
```
_[Source: architecture.md#`ink-foundation` FSE theme tree lines 999–1009 — `assets/` holds "Lora + Inter fonts"]_

### Current markup facts (verified by inspection)
- `front-page.html`: `h1` uses `fontSize:"3xl"`, intro paragraph `lg`; eyebrow paragraph "Tuisblad" uses `fontWeight:600 + uppercase + letterSpacing:0.08em` (a **label**, not a heading — leave it). Heading copy is sentence case. ✅
- `header-main.php`: `site-title` has inline `fontWeight:"700"`; navigation uses `core/navigation-link`. Both already family-assigned in `theme.json styles.blocks`.
- These inline numeric weights (`600`/`700`) are why Task 1 must bundle those weights — otherwise the browser synthesises a faux-bold.

### Project constraints that apply
- **Presentation only:** zero business logic in the theme / `functions.php` (none is created here). _[project-context.md three-layer rule]_
- **Afrikaans-first, sentence-case headings** — do not introduce English UI strings or alter curated Afrikaans copy. _[project-context.md Gate D; memory: Afrikaans is source of truth]_
- **Gate A still holds** — everything stays token-referenced; no hex/raw spacing/unnamed sizes introduced. _[project-context.md Quality Gate A]_

### Testing standards summary
- No unit-test harness exists at repo root yet (arrives in **Story 1.11**); the theme is covered by the **Gate A/D audits + (future) E2E/visual checks**, not PHP unit tests. _[project-context.md "cover the block theme via E2E/visual checks instead of unit tests"; 1-1 Completion Notes]_
- Verification for this story = JSON validity + `fontFace` path existence + no-remote-URL grep + glyph-coverage check of the chosen subset, with the live Site-Editor render confirmation deferred to 1.11 (same precedent as 1.1's AC-4).

### Project Structure Notes
- `assets/fonts/` is the architecture-sanctioned home for fonts (tree line 1008) — creating it here is the first appearance of `assets/`, which is expected at the typography story. No structural deviation.
- `functions.php`, `styles/` block-style variations, and the fuller scaffold remain **not created** here — they arrive with 1.4/1.5/1.7. This story stays within `theme.json` + `assets/fonts/` + an audit of existing patterns/templates.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.2 — Typography system (Lora display/headings, Inter body/UI, named scale xs–3xl fluid, sentence-case headings)]
- [Source: docs/design-handoff/tokens/theme-tokens.json#typography — fontFamily (display/heading=Lora, body/ui=Inter), fontSize xs–3xl, lineHeight tight/normal/relaxed, fontWeight 400–700]
- [Source: docs/design-handoff/tokens/token-map.md — fontFamily/fontSize → settings.typography slugs; "never use raw Lovable names in markup"]
- [Source: _bmad-output/implementation-artifacts/1-1-themejson-design-token-system.md — established theme.json patterns; custom.lineHeight/fontWeight tokens; deferred Site-Editor verification precedent]
- [Source: _bmad-output/planning-artifacts/architecture.md#`ink-foundation` FSE theme tree (lines 999–1009, "assets/ … Lora + Inter fonts"); #Starter-Template-Evaluation (Create Block Theme; NFR-2 tokens canonical, light mode only at v1)]
- [Source: docs/specs/ink-consolidated-spec.md line 120 (Lora display/heading + Inter body/UI), line 359 (sentence-case headings), §9.7 (extract design intent, no JSX/Tailwind/CDN)]
- [Source: _bmad-output/project-context.md — Quality Gate A (tokens only), Gate D (Afrikaans-first, sentence-case), three-layer separation, WP 7.0+/PHP 8.3+, no hardcoded asset URLs, Cloudflare-locked origin]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m] (Opus 4.8, 1M context)

### Debug Log References

- JSON validity: `python3 -c "import json; json.load(open('theme.json'))"` → VALID.
- fontFace path resolution: programmatic walk of `settings.typography.fontFamilies[].fontFace[].src` → **16/16** refs resolve to existing files in `assets/fonts/`, 0 missing.
- No-remote-URL grep on `theme.json`: only match is `$schema` (`schemas.wp.org`); no `fonts.googleapis.com` / `fonts.gstatic.com` / `http` font src.
- Gate A grep (`#hex|rgb(|hsl(`) over `patterns/`/`templates/`/`template-parts/` → no hits; hex inside `theme.json` only in the `color.palette` + `shadow` token registries (expected). `styles` object contains **0** hex colours (all `var:preset|*`).
- woff2 signature check on all 8 bundled files → `wOF2` (`0x774f4632`) on every file.
- Face inventory: display/heading = 4 faces each (normal+italic, weight `400 700`); body/ui = 4 faces each (normal+italic, weight `100 900`).

### Completion Notes List

- **Task 1 was pre-staged** before dev-story (fonts fetched + bundled on owner request): 8 variable `woff2` from `@fontsource-variable` v5.2.8 (SIL OFL 1.1) — Lora (400–700) + Inter (100–900), normal + italic, latin + latin-ext — plus both `*-LICENSE.txt` and a provenance `README.md` under `assets/fonts/`.
- **fontFace registration (Task 2):** added the Lora block under `display` **and** `heading`, the Inter block under `body` **and** `ui` (16 `src` refs, each with per-subset `unicodeRange`, `fontDisplay:swap`). This is the load-bearing change — before it WP emitted no `@font-face` and silently fell back to Georgia/system-ui. The four family `slug`s/`name`s and CSS stacks are untouched (fallback preserved).
- **Role assignment (Task 3):** added `core/button → ui` (Inter); body/heading/nav/site-title roles already correct from 1.1. No raw family literals in `styles`.
- **Heading scale + fluid (Task 4):** per-level `h1..h6 → 3xl..sm`; `2xl`/`3xl` made fluid (clamp) with `max` = existing named value (desktop unchanged, mobile scales down); `xs..xl` pinned `fluid:false` so the global `fluid:true` cannot distort small UI/body sizes. Token `size` values kept verbatim.
- **Line-height/weight (Task 5):** headings `tight`, body `normal` (both via 1.1 custom tokens). Deliberately did **not** add an element-level heading weight — Lora headings render at regular (400), an intentional editorial look; inventing `semibold`/`bold` for headings would be unsourced design intent. The `relaxed` (1.7) reading line-height is left for Epic 7 reading templates, not forced site-wide.
- **Sentence-case (Task 6):** audit confirms no `text-transform` on headings; the sole `uppercase` is the eyebrow paragraph label (acceptable). Convention documented as a standing Gate D rule.
- **AC-4 live check deferred to Story 1.11** (no running WP env in repo), consistent with Story 1.1's precedent. All static verification (JSON, paths, no-CDN, glyph coverage, Gate A) passed.
- **No test/lint harness at repo root yet** (Story 1.11) — the theme is covered by the Gate A/D audits + future E2E/visual checks per project-context, not PHP unit tests. No existing tests to run or regress.
- **No 1.1 token regression:** the 16 colours, spacing, layout, shadow, radius, line-height, and font-weight tokens are byte-for-byte unchanged; only additive typography (`fontFace`, per-size `fluid`, per-level heading sizes, `core/button` family) was introduced.

### Review Findings

Code review (2026-06-21): 0 decision-needed, 0 patch, 1 defer, 2 dismissed. No failed layers (Blind Hunter, Edge Case Hunter, Acceptance Auditor all completed).

- [x] [Review][Defer] templateParts scope drift in theme.json [wp-content/themes/ink-foundation/theme.json:231-247] — deferred, pre-existing. The reviewed working-tree diff retitles the header/footer template parts (Header→Kopstuk, Footer→Voetstuk) and adds a new `section` (Seksie-omhulsel, area `uncategorized`) template part. These are NOT typography changes, are NOT in this story's File List, and the Dev Notes explicitly scope "new templates/section shells" to Story 1.4. Baseline commit c5821d8 had only Header/Footer (English). Not caused by this story; belongs to the 1.4 section-shell work (companion new file `template-parts/section.html` is also untracked and out of scope here).

_Dismissed (noise/handled): (1) The 16 fontFace entries are byte-duplicated (Lora under display+heading, Inter under body+ui) — intentional and documented; WP de-duplicates emitted `@font-face` by family+weight+style. (2) Task 6's audit note ("only one uppercase paragraph") is now stale because many new pattern files (hero.php, featured-grid.php, archetype-b/c, archive-intro) added uppercase eyebrow PARAGRAPHS after the audit — but those files are out of this story's scope (Story 1.5) and all instances are paragraphs, not headings, so AC-5 ("no text-transform on headings") holds._

### File List

- `wp-content/themes/ink-foundation/theme.json` (modified) — added `fontFace` to all 4 font families; per-level heading sizes (h1–h6); fluid `min`/`max` on `2xl`/`3xl` + `fluid:false` on `xs`–`xl`; `core/button` → `ui` family.
- `wp-content/themes/ink-foundation/assets/fonts/*.woff2` (new, 8 files) — bundled Lora + Inter variable woff2 (normal + italic, latin + latin-ext).
- `wp-content/themes/ink-foundation/assets/fonts/Lora-LICENSE.txt`, `Inter-LICENSE.txt` (new) — SIL OFL 1.1.
- `wp-content/themes/ink-foundation/assets/fonts/README.md` (new) — provenance, version, license, and the exact theme.json fontFace wiring.

## Change Log

| Date | Change |
|---|---|
| 2026-06-20 | Story created (context-engineered) — typography system: self-host/register Lora + Inter via fontFace, role assignment, per-level heading sizes, fluid tuning, sentence-case convention. Status → ready-for-dev. |
| 2026-06-20 | Task 1 pre-staged: bundled 8 variable woff2 (Lora 400–700 + Inter 100–900, normal + italic, latin + latin-ext) from @fontsource-variable v5.2.8 (OFL 1.1) into `assets/fonts/`, with LICENSEs + README (provenance + exact fontFace wiring). Tasks 2–7 remain for dev-story. |
| 2026-06-20 | Implemented typography system (Tasks 2–7): registered all 4 families via `fontFace` (16 src, normal+italic, latin+latin-ext); `core/button` → Inter; per-level heading sizes h1–h6; fluid `2xl`/`3xl` + pinned `xs`–`xl`; verified line-height/weight roles; sentence-case audit clean. JSON valid, 0 missing font paths, no CDN, no Gate A regression. Status → review. |
