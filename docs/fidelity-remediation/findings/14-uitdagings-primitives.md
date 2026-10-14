# 14 · uitdagings — Primitive observations (Phase 4b)

The register is `maps/_primitive-observations.uitdagings.csv`: 27 rows, made up of 7 `lovable`,
10 `ink` and 10 `source`.

**Inputs.** The Lovable captures are `uitdagings@{390,768,1440}-scope-challenge` and the INK
captures are the full-page `uitdagings@{390,768,1440}` from `/uitdaging/`. All passed
determinism and verify. `run.sh primitives --exclude-chrome` was run over all six in one pass,
then post-processed by `tmp/r1314/register.py`.

**Hand resolution** (README §4.1):
- The tool missed these rows, which were added from the captures:
  - the INK `<li>` card (`card`);
  - the INK card title `<a>` (`heading`, row 03 convention);
  - the meta items on both sides (`meta-item`);
  - the INK "Lees meer" link (`link-underline`; no underline, opacity 0 at rest);
  - Lovable's class-less, non-uppercase month eyebrow (`eyebrow`, 08a convention).
- The block-root `section.ink-uitdagings.alignwide` that the tool tags as `section-band` was
  dropped.
- `source` rows give each theme.json rule behind a selector on this page.

**Attribution.** Every card class is defined in `theme.json` styles.css (**site-wide**). `used_in`
is `Archive.php` for all of them. The pill and date-row classes are also emitted by
`SinglePage.php:314,327` (row 05): one definition with two consumers, which is deliberate per
`Archive.php:265-270`.

---

## Declaration comparison

### card — another card variant

| | Lovable feature card (`ChallengeSection.tsx:10`) | INK `.ink-uitdagings__item` (theme.json) |
|---|---|---|
| layout | block, per-element margins | flex column, `gap: s-12` |
| padding | 32px (`p-8`) | 24px (`s-24`) |
| radius | 16px (`rounded-2xl`, `radius.2xl`) | 8px (`radius.lg`) |
| shadow | `shadow-soft` = **`shadow.md`** (0 4px 20px -4px /.08) | **`shadow.sm`** (0 2px 12px -2px /.06) |
| fill / border | `bg-card` (253,253,252) / `border` (231,225,218) | `surface-alt` (253,252,250) / `border` (232,228,220) |
| extras | `relative overflow-hidden` + corner tint | hover `translateY(-2px)` + `shadow.lg` |

Every INK value is a token, and every one differs from the reference. `Archive.php:260-263`
says this recipe deliberately mirrors the Opleiding/Biblioteek archive cards (rows 08a/08b).
Those classes were **not** compared here; Stage 2 groups them. If they share the declarations,
the same card recipe lives under at least three class names.

### pill — two paddings inside one card row

| | padding | line-height | fill | type |
|---|---|---|---|---|
| `.ink-uitdagings__tema-pil` | 4px / **10px** (`0.625rem` literal) | 1rem (ratio 1.333) | `secondary` | xs 500 ink-text |
| `.ink-uitdaging__toestand-pil.is-oop` | **2px** (literal) / 12px | inherited | `success` 12% | xs 500 success |

The two pills sit side by side in `challenge-card-head` and do not share a height recipe. The
10px and 2px paddings are the only off-token spacing literals on the page; both are in the A
region (informational, conformance doc). Lovable's card has no pill to compare against.

### heading

- **Card title.** INK has `.ink-uitdagings__titel`, an **`<a>`** at 20px, weight 600,
  line-height **1.4** (an off-token literal). Lovable has an `<h2>` at 24px → 30px, weight 600,
  line-height 32px/36px (`text-2xl md:text-3xl`).
- **Page h1** (C region). `.ink-uitdagings__heading` sets 36px → 48px → 60px from the tokens
  `xxxxl`, `xxxxxl` and `xxxxxxl`, at weight 600 and `line-height.tight`.

### meta-item

- **INK** `.ink-uitdaging__sluitingsdatum-ry`: muted-text, 14px, line-height `1.25rem` (ratio
  1.429), gap `s-8`, with the icon in `primary`.
- **Lovable** `challenge-card-meta-item`: muted-foreground (103,111,126), 14px, line-height
  20px, gap 6px (`gap-1.5`), with the icon in the current colour.
- The colour token differs by palette value only: Lovable's muted-foreground is (103,111,126)
  and INK's `muted-text` is (107,114,128).

### button-primary vs link-underline

Lovable's CTA is `literary`, which renders as `button-primary` in the register: terracotta
fill, white text, Lora 14px weight 500, padding 8px/16px, radius 6px, `shadow.md`. INK's paired
element is a **text link**, primary-coloured, Inter 14px weight 500, padding 0, opacity 0 at
rest. These are different kinds, and the difference is recorded rather than resolved (spine
S-U11, owner call).

### Lovable-only

These have no INK counterpart: `icon-tile` (`p-2 bg-terracotta/10 rounded-lg`, 8px padding,
8px radius) and `eyebrow` (terracotta 14px 500, not uppercase).

## Load-scope cross-check

Every selector is in theme.json, so it applies **site-wide**. The consumers are
`Archive.php` (`/uitdaging/` and `/qa-bloks/`) and `SinglePage.php` (single `uitdaging`). There
is **no inert class** and no live load-scope bug on row 14.

## Vocabulary

No new `component_kind` (see `13-oor-ink-primitives.md`).
