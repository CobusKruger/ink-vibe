# 16 · lidmaatskap — primitive register (Phase 4b)

**Page:** `https://nuwe-ink.local/lidmaatskap/` · **Treatment:** C · **Date:** 2026-10-13 · **Stage 1**
**Register:** `maps/_primitive-observations.lidmaatskap.csv` (21 `ink` rows, 5 `source` rows)
**Captures:** `tmp/fidelity-captures/ink/lidmaatskap@{390,768,1440}.ndjson` — determinism gate 3/3 identical and
`verify --sample 30` 0 mismatches at every width. No duplicate audit-ids.

Header and footer were verified on row 00 (`--exclude-chrome`) and are not re-reported.

## Files read end to end

- `templates/page-lidmaatskap.html`, `patterns/lidmaatskap.php`
- `functions.php` (all of it, including `ink_foundation_membership_plans()` and the block styles at 1424-1906)
- `assets/css/profiel.css:855-920` (the `#lidmaatskap` copies of this page's recipes; the rest of that file belongs to row 02)
- `theme.json` — `settings` and `styles.elements` read; `styles.css` parsed rule by rule (#560-#574 are this page's rules)
- `patterns/lidmaatskap-hernu.php:100-140`, read only to confirm it emits the same `is-style-card` / `is-style-pill` markup (it renders on row 20, which is out of scope)

## How the raw output was resolved

| Raw tool row | Resolution |
|---|---|
| generic `button` ×3 (`.wp-block-button__link…`, note "wrapper has no registered variant") | `button-primary`. Wrapper `is-style-pill` sets only the radius; the fill comes from the `has-primary-background-color` preset class |
| `card` `.wp-block-group.is-style-card` ×3 | plan cards, kept as `card` |
| `card` `.wp-block-details.is-style-card` ×3 | FAQ items, kept as `card` |
| `section-band` ×5 | hero, plans, benefits, FAQ, CTA. These are pattern bands, so all are kept |

No eyebrow on this page. The FAQ `summary` row (flex, 500 weight, chevron) and the benefits `<ul>` are not
vocabulary kinds and are not registered. Their list markers are `::marker`, which is not captured.

## Declaration comparison

- **card — two definitions that differ only in padding.** `functions.php:1431` (`.wp-block-group.is-style-card`:
  surface-alt, 1px border, radius lg, shadow sm, padding **s-24**) vs `functions.php:1451`
  (`.wp-block-details.is-style-card`: the same four declarations, **no padding**). This page supplies padding
  **s-16** inline on each `<details>` (`lidmaatskap.php:186`ff). Rendered: plan card 24px, FAQ card 16px; radius,
  border, background and shadow are identical. On My Profiel, `profiel.css:867` restyles the same plan card under
  `#lidmaatskap` (border **2px**, radius lg, hover tint).
- **button-primary — the same plan button, two recipes.** Here: `theme.json` #562 (`padding:0 s-32; height:44px;
  font-weight:500; inline-flex`) + `functions.php:1466` (radius full) → 44px pill. On My Profiel the identical markup
  from `lidmaatskap-hernu.php` is re-sized by `profiel.css:899` (`padding:s-12 s-8; min-height:44px; font-size:sm;
  line-height:1.3; white-space:normal`) and `profiel.css:912` (radius **lg** instead of full). The comment at
  `profiel.css:878-886` says this is a copy made because #562 is keyed on a class only this page emits. The same
  44px / 0 32px / 500 layer is also restated in `kontak.css:115` and `theme.json` #577 (oor-ink).
- **heading — four `!important` size pins** (#560 h1 32px, #564/#565 h2 24px, #574 CTA h2 32px). They override the
  fluid `xxl` / `xxxl` presets with flat sizes, and are copied for kontak (#578) and oor-ink (#575/#576). The plan
  `h2` (xl, 20px) has no pin. The price paragraph pin (#561 `p.has-xxl-font-size{24px}`) is restated for My Profiel
  at `profiel.css:887`.

## Load-scope cross-check

| Definition | load_scope | used_in | Result |
|---|---|---|---|
| `theme.json` #562 `.ink-lidmaatskap-plans … .wp-block-button__link` | site-wide (by load) | `lidmaatskap.php` (the only emitter of `.ink-lidmaatskap-plans`). The same button markup is in `lidmaatskap-hernu.php` | **scope-by-selector gap.** The rule loads everywhere but matches only under this page's class, so `lidmaatskap-hernu.php` on My Profiel did not get it. That was a live bug, now patched by a *copy* (`profiel.css:899`) with different values. `PRIMITIVE`: record it, do not fix it |
| `functions.php:1431/1451/1466` block styles | site-wide | lidmaatskap.php, lidmaatskap-hernu.php and others | consistent |

## OFF-TOKEN

0 off-token values (`findings/16-lidmaatskap-conformance.md`).
