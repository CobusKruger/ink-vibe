# 13 · oor-ink — Spine and structural findings

| | |
|---|---|
| Page slug | `oor-ink` |
| INK | https://nuwe-ink.local/oor-ink/ (`templates/page-oor-ink.html`) |
| Lovable | **no page.** The referenced region is `SponsorsSection.tsx`, rendered on the Lovable homepage `/` (`src/pages/Index.tsx:17`), captured from http://localhost:4193/ and scoped to `[data-audit-id="sponsors"]` |
| Date | 2026-10-13 |
| Phases | 0–4b (Stage 1) |
| Treatment | **B (hybrid).** There are two regions, and the boundary between them is marked in every table below |

**A/C boundary.**
- **Treatment A**, diffed against Lovable: the sponsor recognition section. Its root is the
  `<section data-audit-id="sponsors">` of `patterns/borg-erkenning.php:20`, which wraps the
  `ink/borg-erkenning` block (`RecognitionSection`).
- **Treatment C**, checked for conformance and with no spine: everything else in `<main>`.
  That is the mission hero, *Ons organisasie*, *Kontak* and *Meer oor INK*
  (`patterns/oor-ink.php:32-94, 98-116`). Its OFF-TOKEN result is in
  `13-oor-ink-conformance.md`.
- Header and footer belong to row 00 and were verified there. They are not re-reported.

---

## 1. Files read end to end

**Lovable**
- `src/pages/Index.tsx` — confirms `SponsorsSection` renders on `/` (line 17)
- `src/components/home/SponsorsSection.tsx` (65 lines)
- `src/components/ui/button.tsx` (`variant="sage-outline"`, `size="lg"`)

**INK — resolution chain (README §3)**
1. `wp-content/themes/ink-foundation/templates/page-oor-ink.html`
2. Template parts `header` / `footer`: row 00 scope, not re-read for this row
3. `patterns/oor-ink.php` → `patterns/borg-erkenning.php`
4. Dynamic block `ink/borg-erkenning` → `wp-content/plugins/ink-core/src/Sponsors/RecognitionSection.php`, which uses
   `Sponsors/SponsorLink.php` and `Sponsors/HomepageStrip.php` (for `tierSlug()`, and as the
   homepage counterpart that reuses the same ids)
- Style sources, for Phase 4b attribution only and not used as evidence of values (R4):
  `theme.json` (settings plus `styles.css`, extracted in full) and `assets/css/home.css:1088-1280`
  (the homepage `.ink-borg-strook*` definitions)
- The live HTML of `/oor-ink/` (curl, cache-busted), which confirms the rendered chain

## 2. Section spine (Treatment A region only)

| # | Lovable (`SponsorsSection.tsx`) | INK | Status |
|---|---|---|---|
| A1 | `<section>` band `py-16 bg-secondary/20` (:15) | `<section>` from `borg-erkenning.php:20` | present |
| A2 | `div.container.text-center` (:16) | `div.alignwide` (`borg-erkenning.php:22`) + `section.ink-borg-erkenning` (`RecognitionSection.php:141`) | present (+1 wrapper) |
| A3 | eyebrow `<span>` (:17) | `p.__etiket` (:142) | present |
| A4 | `<h2>` (:20) | `h2.__titel` (:143) | present |
| A5 | intro `<p>` (:23) | `p.__beskrywing` (:144) | present |
| A6 | chip grid, flex `<div>` (:29) | `ul.__rooster` (:147) | present |
| A7 | chips × 6, gold/silver/bronze (:34) | `li.__item--{goud,silwer,brons}` × 6 (:173) | present (live data: 2 goud, 3 silwer, 1 brons) |
| A8 | sage-outline lg `<Button>` + Heart (:56) | `a.__cta` + inline Heart svg (:182) | present |

The Treatment C remainder has no spine. In page order it is: `oor-ink-hero`, then
`oor-ink-org`, then `oor-ink-kontak`, then **[A region]**, then `oor-ink-meer`.

## 3. Structural findings — Treatment A region

| id | class | finding | Lovable | INK |
|---|---|---|---|---|
| S-1 | DRIFT | The band has no background. Lovable tints the section `secondary/20`. The INK pattern section carries no colour attribute and no band class, so it is transparent (measured). The homepage equivalent has `.ink-borg-band` (`borg-strook.php:24`, `home.css:1090`), but it is not used here | `SponsorsSection.tsx:15` | `borg-erkenning.php:19-20` |
| S-2 | DRIFT | The content is capped at `contentSize` 768px. The block's `<section>` sits inside an `alignwide` constrained group, so the grid is 768px wide where Lovable's is 1368px @1440. The homepage strip had the same bug and it was fixed there; the reasoning is in `home.css:1095-1103` ("Released to the full wide width here"). The fix was never ported to `.ink-borg-erkenning` | `SponsorsSection.tsx:16,29` | `borg-erkenning.php:21-23`, `RecognitionSection.php:141` |
| S-3 | DRIFT | The vertical rhythm comes from a flat 16px flex `gap` (`.ink-borg-erkenning`) with `margin:0` on every child. Lovable uses `mt-2 mb-4` on the h2, `mb-10` on the intro and `mb-10` on the grid. The homepage strip already replaced the flat gap with Lovable's per-element margins (`home.css:1100-1103`) | `:20,23,29` | `RecognitionSection.php:141-147` |
| S-4 | STRUCTURE | There is one extra wrapper level: the pattern's `div.alignwide` and then the block-root `section.ink-borg-erkenning` (`sponsors-inner`), which nests a `<section>` inside a `<section>` | `:16` | `borg-erkenning.php:22`, `RecognitionSection.php:141` |
| S-5 | MECHANISM | The grid is a `<ul>`/`<li>`, where Lovable uses flex `<div>`s. This is the same mechanism as row 00's F11 | `:29,34` | `RecognitionSection.php:147,173` |
| S-6 | SCOPE | Each chip's content is a link (`a.__skakel` via `SponsorLink`, to the sponsor's URL or permalink) wrapping the name. Lovable's chip is an inert `<div><span>`. A logo `<img>` replaces the name when the sponsor has one, and in that case `-name` is absent | `:34-50` | `SponsorLink.php:41-59`, `RecognitionSection.php:163-174` |
| S-7 | SCOPE | The data is live. INK renders **every** active sponsor and always renders the section; only the grid is omitted when there are no sponsors. Lovable renders a static six. This is disclosed in the class docblock | `:4-11` | `RecognitionSection.php:22-29` |
| S-8 | MECHANISM | The CTA is an `<a href=/kontak>`, where Lovable uses a `<button>`. This is the same as row 00 | `:56` | `RecognitionSection.php:182` |
| S-9 | PRIMITIVE | The whole section recipe is defined twice: `.ink-borg-strook*` in `home.css` (front page only) and `.ink-borg-erkenning*` in `theme.json` styles.css (site-wide). The two copies have already diverged; S-2 and S-3 were fixed in one and not the other. Detail in `13-oor-ink-primitives.md` | — | `home.css:1104-1280`; `theme.json` `.ink-borg-erkenning*` |

**Not findings.** The copy differs ("Ons borge" / "Moontlik gemaak deur" / "Word 'n borg" against
the English mockup). That follows R2: Afrikaans is the source of truth. The sponsor names are
live data.

**R6 check.** `oor-ink.php:18-27` discloses the page-scoped `!important` font-size and button
pins, and says the page's fidelity target is "internal design-system consistency". It discloses
nothing for the sponsor region. `RecognitionSection.php:17-29` discloses only the data scope
(S-7). None of S-1 to S-3 is disclosed.

### Treatment C remainder — structural notes (no Lovable counterpart, recorded for Stage 2/4)

| id | class | note | INK |
|---|---|---|---|
| S-C1 | PRIMITIVE | There are two eyebrow recipes on one page. The hero eyebrow is primary, xs, 500, 0.2em (from inline block attributes). The sponsor eyebrow is accent, sm, 500, 0.08em | `oor-ink.php:36-37` vs `RecognitionSection.php:142` |
| S-C2 | PRIMITIVE | The page's headings render at weight **700**, the UA bold. `theme.json` `styles.elements.heading` sets no weight. Every Lovable heading measured so far is 600, and `.ink-borg-erkenning__titel` on this same page is 600 | `oor-ink.php:41,61,77,103` |
| S-C3 | PRIMITIVE | *Kontak ons* is a core/button with colour block attributes and **no** `is-style-ink-primary`. Its height and padding come from a page-scoped rule in theme.json, `.ink-oor-ink-kontak .wp-block-buttons .wp-block-button__link{height:44px…}` (disclosed `oor-ink.php:18-27`) | `oor-ink.php:84-89` |
| S-C4 | PRIMITIVE | The h1/h2 sizes are pinned with page-scoped `!important` (32px and 24px) to work around the fluid-clamp bug for `xxl`/`xxxl`. This is disclosed in `oor-ink.php:18-22` | theme.json styles.css `.ink-oor-ink-*` |
| S-C5 | STRUCTURE | Every C section declares `alignwide` (1368px), but its blocks are constrained to the 768px `contentSize`. The measurement shows 300px auto-centring margins @1440 (conformance doc) | `oor-ink.php:34-35` etc. |
| S-C6 | **live bug** | *Meer oor INK* links "Maandelikse uitdagings" to `/uitdagings`, which 301s to an unrelated post, `/skryfwerk/uitdagings-stil-geen-hongerte/`. The challenge archive is at `/uitdaging/`. See row 14, S-U1 | `oor-ink.php:109` |

## 4. Join-key coverage

The full table is in `maps/oor-ink.csv`.
- **A region: 13 ids, 12 paired** at all three viewports: `sponsors`, `-eyebrow`, `-h2`, `-intro`,
  `-grid`, `-btn`, and `-chip-{gold,silver,bronze}` with their `-name`s. The 13th,
  `sponsors-inner`, is INK-only.
- These ids are **reused from row 00** (`maps/tuisblad.csv`). On this page they are emitted by a
  different renderer, `RecognitionSection`, plus `borg-erkenning.php`. The homepage emits them
  from `HomepageStrip` and `borg-strook.php`. The two never render on the same page, except the
  QA gallery `/qa-bloks/`, which embeds both blocks (`qa-bloks.php:109,197`). That page is out
  of the inventory, and `--scope` is the established way to capture it. The homepage was
  re-checked after deploy and has no duplicate ids.
- **Lovable:** no edits. Row 00 already instrumented every element of the region.
- **C region:** 18 page-prefixed ids (`oor-ink-*`), static, in `oor-ink.php`.
- **Coverage gaps.** The chip name ids exist only for name-only sponsors, so a logo chip has no
  `-name` id; all six live sponsors are name-only. The Heart icon carries no id on either side.
  Hover (`scale(1.05)`) was not captured.

## 5. Conclusion and limits

The sponsor region is structurally complete. Every Lovable element has an INK counterpart, and
the differences are a wrapper level, list semantics, linked chips and live data. The visible
divergence is almost entirely **layout and recipe drift**, and its cause is visible in the
source. This block is a second copy of the homepage strip. It never received the two fixes the
homepage copy got (full wide width, Lovable's per-element margins), and it never received the
band tint. That makes it a PRIMITIVE (S-9) at heart: the fix belongs in Stage 3's single
definition, not on this page.

This Phase 2 document makes no claim about computed values on its own. S-1 to S-3 are
classified DRIFT because Phase 4 measured them, as summarised in §6. The C remainder is held
to the token set only; OFF-RECIPE waits for Stage 4.

## 6. Phase 4 / 4b summary (measured)

The gates all passed. Determinism: three byte-identical runs for each of the 9 captures behind
this row (Lovable scoped ×3, INK full ×3, INK scoped ×3). `verify --sample 30` passed on every
one. For a clean region diff, the **INK capture is scoped** to `[data-audit-id="sponsors"]`
(`--scope-label sponsors`) as well as the Lovable one. The INK full-page captures feed the
conformance check and the register.

Drift (`13-oor-ink-drift.md`, plus `@768` and `@390`): 12 paired ids and 114, 114 and 112 deltas.
The top items:
1. **Width and rhythm (S-2, S-3).** The grid is 768px wide where Lovable's is 1368px @1440.
   The gap is 24px where Lovable has 48px (32px @390). The h2 margins are 0/0 against 8/16, and
   the intro and grid margin-bottom is 0 against 40px. The band height is 512px against 534px.
2. **Band colour (S-1).** Transparent, where Lovable has `rgba(240,235,230,.2)`.
3. **Type recipe.** The CTA and chip boxes are Lora 500, where Lovable uses Inter 400/500. The
   gold chip is tinted with `goud` #C9B88A, where Lovable uses `gold` #E8B130; both are palette
   tokens and the wrong one is chosen. The bronze chip has a 1px transparent border that
   Lovable lacks. The eyebrow tracking is 1.12px against 0.7px. The h2 is 32px against 30px
   @≥768.
4. Chip height differences (24px against 48px) are a **content-box vs border-box artefact**
   (`box-sizing`): the padding adds up. They are not real drift. The CTA width differs because
   the copy differs.

The OFF-TOKEN check on the C remainder found **0** off-token values across 1,451 tested
values; see the conformance doc.

The primitives, `13-oor-ink-primitives.md`, show `tier-chip`, `eyebrow`, `button-sage-outline`
and the sponsor heading each duplicated between `home.css` and `theme.json`. No class is inert
on this page: every definition is site-wide.
