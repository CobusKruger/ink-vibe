# 05 Kompetisies: build spec (N4)

**Status:** planned, awaiting recipe-gap ratification. **Treatment:** A. No values here: every style value comes from captures (R4/R5). **Map:** `../maps/kompetisies.csv`. **Rulings:** D2, D3, D4.

**Scope:** web pages only. No entry or judging pipeline, no payment. **Competitions are not challenges:** README N4's "annual round = `uitdaging` with cadence `jaarliks`" is stale (handoff §8, `CLAUDE.md`). Nothing here reads `uitdaging` or `CadenceType`.

## 1. Section spine

Nothing exists yet for this page.

| Lovable | INK block (new) | Files |
|---|---|---|
| `Competitions.tsx:73` dark hero | `ink/kompetisies-hero` | `Competitions/Hero.php` |
| `:115` categories | `ink/kompetisies-kategoriee` | `Competitions/Categories.php` |
| `:167` gala + Evenings past | `ink/kompetisies-gala` | `Competitions/Gala.php` |
| `:280` directory + submit box | `ink/kompetisies-gids` | `Competitions/Directory.php` |

**Chain:** `templates/page-kompetisies.html` (new; same frame as `page-kontak.html`) → `patterns/kompetisies.php` (new; the four blocks, no wrapper padding) → the blocks. **Registration:** `SystemPages::PAGES['kompetisies'] => 'Kompetisies'`. New module `Ink\Competitions` (`Module.php`, `Data.php`, `Migration.php`); add it to the deptrac rules. It reads only `Ink\Kernel` and core.

## 2. Page adoption (D2)

Dev has **Page 59192**: slug `kompetisies`, title `Kompetisies` (README's "Eksterne Kompetisies" is the prod menu label), status publish, template `default`. `ensure()` finds it by path, so it is adopted, not created.

Empty it in code:
- **Body:** add a slug-keyed rule next to `LEGACY_SHORTCODE` in `SystemPages::ensure()`, e.g. `EMPTY_BODY = array( 'kompetisies' )`, so the ~1 KB body becomes `''` on every run. Idempotent; add to `SystemPagesTest`.
- **`_simple_css` meta:** delete it in the same rule. Simple CSS is active, and on dev this meta's Afrikaans text prints inside the `simple-css-output` style tag of `/kompetisies/` (verified in the page source). Emptying the body alone leaves it there.

The guard test at `SystemPagesTest.php:41-53` passes once the Page is in the list.

## 3. Data: seed files

**Conflict flagged:** README Principle 4 and D4 say "committed files"; `CLAUDE.md` says all migration data lives only in `../migration-data/` and may never be copied into the repo. `CLAUDE.md` is newer, so **all seed CSVs go in `../migration-data/`**, read through `Ink\Kernel\MigrationData`. Update Principle 4 and D4's wording at build.

| File | One row per | Columns |
|---|---|---|
| `kompetisies-jaarliks.csv` | (one row) | `uitgawe`, `naam`, `beskrywing`, `sluitingsdatum`, `inskrywing_nota`, `naaswenner_nota`, `indien_epos` |
| `kompetisies-kategorie.csv` | category (3) | `volgorde`, `ikoon` (`feather`/`book`/`pen`), `naam`, `tema`, `beskrywing`, `woordlimiet`, `riglyn_1`, `riglyn_2`, `riglyn_3` |
| `gala.csv` | (one row) | `naam`, `beskrywing`, `datum`, `deure`, `plek`, `drag` |
| `gala-program.csv` | line | `volgorde`, `teks` |
| `gala-kaartjies.csv` | ticket | `volgorde`, `etiket`, `prys`, `prys_is_plekhouer` |
| `gala-jare.csv` | year, newest first | `jaar`, `hoogtepunt` |
| `gala-fotos.csv` | photo | `jaar`, `volgorde`, `pad`, `onderskrif` |
| `eksterne-kompetisies.csv` | listing | `naam`, `organiseerder`, `streek`, `sluitingsdatum`, `prys`, `fooi_soort` (`gratis`/`betaal`), `fooi`, `genre` (`gedigte`/`fiksie`/`niefiksie`/`gemeng`), `url`, `nota` |

**Rules**
- **Ticket prices are fictional (D4):** `prys_is_plekhouer` must be `ja` on every row until payments exist; the step warns on any other value. The marker is in the seed only and nothing shows on the page.
- **Photos:** `pad` is a path under `wp-content/uploads/`, matched to an existing attachment by `_wp_attached_file` as `InkPols\Migration` does. `gala-*` from Lovable never ship (permitted #4). A row with no matching attachment is skipped and reported.
- **Step:** `wp ink migrate-kompetisies` in `migrate-all`, once-off with `--force`, same shape as `migrate-inkpols`. Stores one non-autoloaded option per dataset (`ink_kompetisies_{jaarliks,kategorie,gala,jare,eksterne}`). Rendering reads the options, never the CSVs.
- **Missing file:** stops the step, like the other migration inputs. Add the eight names to the check at `tools/rehearsal-prep.mjs:79`.
- **Blank owner cells:** the row imports and the step lists them, as with InkPols.
- **Docs:** `docs/migration-data.md`, `../migration-data/README.md`, `docs/migration-runbook.md` step list and Verify table.

**Lovable's data is not a source.** No string from `competitions.ts` goes in a seed file: its text is English, and its prices are the only values D4 lets through as fictional.

## 4. Behaviour

| Element | Behaviour |
|---|---|
| Gala year chips | Client-side. First year active. Every year's photos are in the HTML; only the active year's carry audit-ids (JS moves nothing else). Highlight and photos swap on click |
| Directory search | Client-side over name, organiser, region, note, as `Competitions.tsx:50-64` |
| Genre, fee chips | Client-side; AND with search. Count line updates |
| Empty state | Shown when nothing matches; clear button resets all three |
| No JS | All listings shown, active gala year only |
| Reserve (D4) | **Target none.** `<button type="button">` with no handler, as Lovable's `:220`. D4 lets the spec choose none or contact; none adds nothing the design doesn't show |
| Enter, Guidelines | Lovable: `/write`, and no target. Followed pending Q1 |
| Submit a listing | `mailto:` from `indien_epos`, Q3 |
| External links | `target="_blank" rel="noopener noreferrer"` |

**Script:** `assets/js/kompetisies.js`, one file.

**Empty data** (not in the design; follows the `inkpols.md` precedent of hiding): no gala year row hides `competitions-gala-past`; no listing shows the empty state with no clear button; no `gala.csv` values or no annual row is a failed step, not a hidden section.

## 5. Element tables

### Hero

| audit-id | Lovable | recipe | data source | copy | status / note |
|---|---|---|---|---|---|
| `competitions-hero` | Competitions.tsx:73 | `ink-band ink-band--dark ink-band--flush` + RECIPE-GAP RG-KO-01 | — | — | planned |
| `competitions-hero-texture` | Competitions.tsx:74 | RECIPE-GAP RG-KO-03 | — | — | planned |
| `competitions-hero-container` | Competitions.tsx:77 | `ink-container` + RECIPE-GAP RG-KO-02 | — | — | planned |
| `competitions-hero-content` | Competitions.tsx:78 | layout (page sheet) | — | — | planned |
| `competitions-hero-badge` | Competitions.tsx:79 | RECIPE-GAP RG-KO-05 | seed `kompetisies-jaarliks.csv`: `uitgawe` | seed + copy-debt KOMP-HERO-BADGE | planned |
| `competitions-hero-badge-icon` | Competitions.tsx:80 | svg in parent recipe | — | — | planned |
| `competitions-hero-h1` | Competitions.tsx:83 | `ink-heading-h1-index` + RECIPE-GAP RG-KO-04 | seed `kompetisies-jaarliks.csv`: `naam` | seed | planned |
| `competitions-hero-text` | Competitions.tsx:86 | text (page sheet, tokens) | seed `kompetisies-jaarliks.csv`: `beskrywing` | seed | planned |
| `competitions-hero-meta` | Competitions.tsx:89 | layout (page sheet) | — | — | planned |
| `competitions-hero-meta-close` | Competitions.tsx:90 | RECIPE-GAP RG-KO-06 | seed `kompetisies-jaarliks.csv`: `sluitingsdatum` | seed + copy-debt KOMP-HERO-SLUIT | planned |
| `competitions-hero-meta-close-icon` | Competitions.tsx:91 | svg in parent recipe | — | — | planned |
| `competitions-hero-meta-entry` | Competitions.tsx:94 | RECIPE-GAP RG-KO-06 | seed `kompetisies-jaarliks.csv`: `inskrywing_nota` | seed | planned |
| `competitions-hero-meta-entry-icon` | Competitions.tsx:95 | svg in parent recipe | — | — | planned |
| `competitions-hero-actions` | Competitions.tsx:99 | layout (page sheet) | — | — | planned |
| `competitions-hero-btn-primary` | Competitions.tsx:100 | `ink-btn ink-btn--lg ink-btn--primary` | link `/skryf/` (Lovable `/write`); see Q1 | copy-debt KOMP-HERO-INSKRYF | planned |
| `competitions-hero-btn-primary-icon` | Competitions.tsx:103 | svg in parent recipe | — | — | planned |
| `competitions-hero-btn-guidelines` | Competitions.tsx:106 | `ink-btn ink-btn--lg` + RECIPE-GAP RG-KO-07 | no target, as Lovable; see Q1 | copy-debt KOMP-HERO-RIGLYNE | planned |

### Categories

| audit-id | Lovable | recipe | data source | copy | status / note |
|---|---|---|---|---|---|
| `competitions-categories` | Competitions.tsx:115 | `ink-container ink-band--poem` | — | — | planned |
| `competitions-categories-head` | Competitions.tsx:116 | layout (page sheet) | — | — | planned |
| `competitions-categories-head-text` | Competitions.tsx:117 | layout (page sheet) | — | — | planned |
| `competitions-categories-h2` | Competitions.tsx:118 | `ink-heading-h2-section` | static | copy-debt KOMP-KAT-H2 | planned |
| `competitions-categories-intro` | Competitions.tsx:121 | text (page sheet, tokens) | seed `kompetisies-jaarliks.csv`: `naaswenner_nota` | copy-debt KOMP-KAT-INTRO + seed | planned |
| `competitions-categories-grid` | Competitions.tsx:126 | layout (page sheet) | — | — | planned |
| `competitions-category` | Competitions.tsx:132 | `ink-card ink-card--lg` + RECIPE-GAP RG-KO-08 | seed row 1 | — | planned |
| `competitions-category-{i}` | Competitions.tsx:132 | `ink-card ink-card--lg` + RECIPE-GAP RG-KO-08 | seed row | — | planned |
| `competitions-category-{i}-body` | Competitions.tsx:135 | layout (page sheet) | — | — | planned: Lovable CardContent; INK nests it so the padding box matches |
| `competitions-category-{i}-icon-wrap` | Competitions.tsx:136 | layout (page sheet) | — | — | planned |
| `competitions-category-{i}-icon-tile` | Competitions.tsx:137 | RECIPE-GAP RG-KO-09 | — | — | planned |
| `competitions-category-{i}-icon` | Competitions.tsx:138 | svg in parent recipe | seed `kompetisies-kategorie.csv`: `ikoon` | — | planned |
| `competitions-category-{i}-title` | Competitions.tsx:141 | `ink-heading-h2-block` | seed `kompetisies-kategorie.csv`: `naam` | seed | planned |
| `competitions-category-{i}-theme` | Competitions.tsx:142 | RECIPE-GAP RG-KO-10 | seed `kompetisies-kategorie.csv`: `tema` | seed | planned |
| `competitions-category-{i}-desc` | Competitions.tsx:143 | text (page sheet, tokens) | seed `kompetisies-kategorie.csv`: `beskrywing` | seed | planned |
| `competitions-category-{i}-limit` | Competitions.tsx:144 | RECIPE-GAP RG-KO-11 | seed `kompetisies-kategorie.csv`: `woordlimiet` | seed | planned |
| `competitions-category-{i}-limit-icon` | Competitions.tsx:145 | svg in parent recipe | — | — | planned |
| `competitions-category-{i}-guidelines` | Competitions.tsx:148 | layout (page sheet) | — | — | planned |
| `competitions-category-{i}-guideline-{g}` | Competitions.tsx:150 | text (page sheet, tokens) | seed `kompetisies-kategorie.csv`: `riglyn_1..3` | seed | planned |
| `competitions-category-{i}-guideline-{g}-dot` | Competitions.tsx:151 | text (page sheet, tokens) | — | — | planned |
| `competitions-category-{i}-btn` | Competitions.tsx:156 | `ink-btn ink-btn--outline ink-btn--block` | link `/skryf/`; see Q1 | copy-debt KOMP-KAT-INSKRYF + seed naam | planned |

### Gala

| audit-id | Lovable | recipe | data source | copy | status / note |
|---|---|---|---|---|---|
| `competitions-gala` | Competitions.tsx:167 | `ink-band ink-band--strip ink-band--flush` | — | — | planned |
| `competitions-gala-container` | Competitions.tsx:168 | `ink-container ink-band--poem` | — | — | planned |
| `competitions-gala-grid` | Competitions.tsx:169 | layout (page sheet) | — | — | planned |
| `competitions-gala-details` | Competitions.tsx:171 | layout (page sheet) | — | — | planned |
| `competitions-gala-eyebrow` | Competitions.tsx:172 | `ink-eyebrow ink-eyebrow--kicker` | — | — | planned |
| `competitions-gala-eyebrow-icon` | Competitions.tsx:173 | svg in parent recipe | — | — | planned |
| `competitions-gala-eyebrow-text` | Competitions.tsx:174 | kicker `> span` | static | copy-debt KOMP-GALA-KICKER | planned |
| `competitions-gala-h2` | Competitions.tsx:176 | `ink-heading-h2-section` | seed `gala.csv`: `naam` | seed | planned |
| `competitions-gala-text` | Competitions.tsx:179 | text (page sheet, tokens) | seed `gala.csv`: `beskrywing` | seed | planned |
| `competitions-gala-info` | Competitions.tsx:181 | layout (page sheet) | — | — | planned |
| `competitions-gala-info-date` | Competitions.tsx:182 | RECIPE-GAP RG-KO-11 | — | — | planned |
| `competitions-gala-info-date-icon` | Competitions.tsx:183 | svg in parent recipe | — | — | planned |
| `competitions-gala-info-date-text` | Competitions.tsx:184 | text (page sheet, tokens) | seed `gala.csv`: `datum` | seed | planned |
| `competitions-gala-info-doors` | Competitions.tsx:185 | text (page sheet, tokens) | seed `gala.csv`: `deure` | copy-debt KOMP-GALA-DEURE + seed | planned |
| `competitions-gala-info-venue` | Competitions.tsx:187 | RECIPE-GAP RG-KO-11 | — | — | planned |
| `competitions-gala-info-venue-icon` | Competitions.tsx:188 | svg in parent recipe | — | — | planned |
| `competitions-gala-info-venue-text` | Competitions.tsx:189 | text (page sheet, tokens) | seed `gala.csv`: `plek` | seed | planned |
| `competitions-gala-info-dress` | Competitions.tsx:191 | RECIPE-GAP RG-KO-11 | — | — | planned |
| `competitions-gala-info-dress-icon` | Competitions.tsx:192 | svg in parent recipe | — | — | planned |
| `competitions-gala-info-dress-text` | Competitions.tsx:193 | text (page sheet, tokens) | seed `gala.csv`: `drag` | seed | planned |
| `competitions-gala-programme` | Competitions.tsx:197 | `ink-card--feature` + RECIPE-GAP RG-KO-17 | — | — | planned |
| `competitions-gala-programme-label` | Competitions.tsx:198 | RECIPE-GAP RG-IP-09 | static | copy-debt KOMP-GALA-PROGRAM | planned |
| `competitions-gala-programme-list` | Competitions.tsx:201 | layout (page sheet) | — | — | planned |
| `competitions-gala-programme-item-{n}` | Competitions.tsx:203 | layout (page sheet) | seed `gala-program.csv`: `volgorde` | — | planned |
| `competitions-gala-programme-item-{n}-num` | Competitions.tsx:204 | text (page sheet, tokens) | derived | — | planned |
| `competitions-gala-programme-item-{n}-text` | Competitions.tsx:205 | text (page sheet, tokens) | seed `gala-program.csv`: `teks` | seed | planned |
| `competitions-gala-tickets` | Competitions.tsx:211 | layout (page sheet) | — | — | planned |
| `competitions-gala-ticket-{n}` | Competitions.tsx:213 | RECIPE-GAP RG-KO-12 | seed `gala-kaartjies.csv`: `etiket` | seed | planned |
| `competitions-gala-ticket-{n}-icon` | Competitions.tsx:214 | svg in parent recipe | — | — | planned |
| `competitions-gala-ticket-{n}-price` | Competitions.tsx:215 | text (page sheet, tokens) | seed `gala-kaartjies.csv`: `prys` + `prys_is_plekhouer` | seed; fictional (D4) | planned |
| `competitions-gala-btn-reserve` | Competitions.tsx:220 | `ink-btn ink-btn--lg ink-btn--primary` | target none (D4), `<button type="button">` | copy-debt KOMP-GALA-BESPREEK | planned |
| `competitions-gala-btn-reserve-icon` | Competitions.tsx:221 | svg in parent recipe | — | — | planned |
| `competitions-gala-past` | Competitions.tsx:227 | layout (page sheet) | hidden with no gala year (see §6) | — | planned |
| `competitions-gala-past-card` | Competitions.tsx:228 | `ink-card--feature-hero` + RECIPE-GAP RG-KO-17 | — | — | planned |
| `competitions-gala-past-h3` | Competitions.tsx:229 | `ink-heading-h2-block` | static | copy-debt KOMP-GALA-VERLEDE-H3 | planned |
| `competitions-gala-past-text` | Competitions.tsx:230 | text (page sheet, tokens) | static | copy-debt KOMP-GALA-VERLEDE-TEKS | planned |
| `competitions-gala-past-years` | Competitions.tsx:235 | layout (page sheet) | — | — | planned |
| `competitions-gala-past-year-2025` | Competitions.tsx:237 | RECIPE-GAP RG-KO-14 | seed `gala-jare.csv`: `jaar` | year | planned |
| `competitions-gala-past-year-2024` | Competitions.tsx:237 | RECIPE-GAP RG-KO-14 | seed `gala-jare.csv`: `jaar` | year | planned |
| `competitions-gala-past-year-2023` | Competitions.tsx:237 | RECIPE-GAP RG-KO-14 | seed `gala-jare.csv`: `jaar` | year | planned |
| `competitions-gala-past-highlight` | Competitions.tsx:251 | text (page sheet, tokens) | seed `gala-jare.csv`: `hoogtepunt` | seed | planned |
| `competitions-gala-past-grid` | Competitions.tsx:255 | layout (page sheet) | — | — | planned |
| `competitions-gala-past-photo-{n}` | Competitions.tsx:257 | layout (page sheet) | seed `gala-fotos.csv`: `volgorde` | — | planned: ids stamped on the active year only, so they stay unique |
| `competitions-gala-past-photo-{n}-img` | Competitions.tsx:261 | RECIPE-GAP RG-KO-15 | seed `gala-fotos.csv`: `pad` (attachment match) | alt = caption | planned: real images only: permitted #4 |
| `competitions-gala-past-photo-{n}-caption` | Competitions.tsx:269 | text (page sheet, tokens) | seed `gala-fotos.csv`: `onderskrif` | seed | planned |

### Directory and submit box

| audit-id | Lovable | recipe | data source | copy | status / note |
|---|---|---|---|---|---|
| `competitions-directory` | Competitions.tsx:280 | `ink-container ink-band--poem` | — | — | planned |
| `competitions-directory-head` | Competitions.tsx:281 | layout (page sheet) | — | — | planned |
| `competitions-directory-eyebrow` | Competitions.tsx:282 | `ink-eyebrow ink-eyebrow--kicker` | — | — | planned |
| `competitions-directory-eyebrow-icon` | Competitions.tsx:283 | svg in parent recipe | — | — | planned |
| `competitions-directory-eyebrow-text` | Competitions.tsx:284 | kicker `> span` | static | copy-debt KOMP-GIDS-KICKER | planned |
| `competitions-directory-h2` | Competitions.tsx:286 | `ink-heading-h2-section` | static | copy-debt KOMP-GIDS-H2 | planned |
| `competitions-directory-text` | Competitions.tsx:289 | text (page sheet, tokens) | static | copy-debt KOMP-GIDS-TEKS | planned |
| `competitions-directory-controls` | Competitions.tsx:296 | layout (page sheet) | — | — | planned |
| `competitions-directory-search` | Competitions.tsx:297 | layout (page sheet) | — | — | planned |
| `competitions-directory-search-icon` | Competitions.tsx:298 | svg in parent recipe | — | — | planned |
| `competitions-directory-search-input` | Competitions.tsx:299 | `ink-search-field` + RECIPE-GAP RG-KO-16 | client-side | copy-debt KOMP-GIDS-SOEK (placeholder) | planned |
| `competitions-directory-filters-genre` | Competitions.tsx:306 | layout (page sheet) | — | — | planned |
| `competitions-directory-genre-all` | Competitions.tsx:308 | RECIPE-GAP RG-KO-13 | static | Alles (`Hub.php:667`, `inkpols.md`) | planned |
| `competitions-directory-genre-poetry` | Competitions.tsx:308 | RECIPE-GAP RG-KO-13 | seed `eksterne-kompetisies.csv`: `genre` code | copy-debt KOMP-GENRE-POETRY | planned |
| `competitions-directory-genre-fiction` | Competitions.tsx:308 | RECIPE-GAP RG-KO-13 | seed `eksterne-kompetisies.csv`: `genre` code | copy-debt KOMP-GENRE-FICTION | planned |
| `competitions-directory-genre-non-fiction` | Competitions.tsx:308 | RECIPE-GAP RG-KO-13 | seed `eksterne-kompetisies.csv`: `genre` code | copy-debt KOMP-GENRE-NON-FICTION | planned |
| `competitions-directory-genre-mixed` | Competitions.tsx:308 | RECIPE-GAP RG-KO-13 | seed `eksterne-kompetisies.csv`: `genre` code | copy-debt KOMP-GENRE-MIXED | planned |
| `competitions-directory-filters-fee` | Competitions.tsx:321 | layout (page sheet) | — | — | planned |
| `competitions-directory-fee-all` | Competitions.tsx:323 | RECIPE-GAP RG-IP-10 | client-side | copy-debt KOMP-FOOI-ALL | planned |
| `competitions-directory-fee-free` | Competitions.tsx:323 | RECIPE-GAP RG-IP-10 | client-side | copy-debt KOMP-FOOI-FREE | planned |
| `competitions-directory-fee-paid` | Competitions.tsx:323 | RECIPE-GAP RG-IP-10 | client-side | copy-debt KOMP-FOOI-PAID | planned |
| `competitions-external-count` | Competitions.tsx:356 | text (page sheet, tokens) | derived | copy-debt KOMP-TELLING (en/mv) | planned |
| `competitions-external-grid` | Competitions.tsx:361 | layout (page sheet) | — | — | planned |
| `competitions-external` | Competitions.tsx:365 | `ink-card` + RECIPE-GAP RG-KO-18 | seed row 1 | — | planned: first of 0..8; padding box on the -body child as Lovable |
| `competitions-external-{i}` | Competitions.tsx:365 | `ink-card` + RECIPE-GAP RG-KO-18 | seed row | — | planned |
| `competitions-external-{i}-body` | Competitions.tsx:368 | layout (page sheet) | — | — | planned |
| `competitions-external-{i}-head` | Competitions.tsx:369 | layout (page sheet) | — | — | planned |
| `competitions-external-{i}-genre` | Competitions.tsx:370 | `ink-pill ink-pill--outline` | seed `eksterne-kompetisies.csv`: `genre` | copy-debt KOMP-GENRE-* | planned |
| `competitions-external-{i}-fee` | Competitions.tsx:373 | RECIPE-GAP RG-IP-08 + `ink-eyebrow--accent` | seed `eksterne-kompetisies.csv`: `fooi` | seed / copy-debt KOMP-FOOI-FREE | planned |
| `competitions-external-{i}-title` | Competitions.tsx:377 | `ink-heading-h3-card` | seed `eksterne-kompetisies.csv`: `naam` | seed | planned |
| `competitions-external-{i}-org` | Competitions.tsx:380 | text (page sheet, tokens) | seed `eksterne-kompetisies.csv`: `organiseerder` · `streek` | seed | planned |
| `competitions-external-{i}-note` | Competitions.tsx:384 | text (page sheet, tokens) | seed `eksterne-kompetisies.csv`: `nota` (optional) | seed | planned: Lovable rows 6 and 8 have no note; fixture mirrors that |
| `competitions-external-{i}-meta` | Competitions.tsx:386 | layout (page sheet) | — | — | planned |
| `competitions-external-{i}-deadline` | Competitions.tsx:387 | RECIPE-GAP RG-KO-19 | seed `eksterne-kompetisies.csv`: `sluitingsdatum` | copy-debt KOMP-EKS-SLUIT + seed | planned |
| `competitions-external-{i}-deadline-icon` | Competitions.tsx:388 | svg in parent recipe | — | — | planned |
| `competitions-external-{i}-prize` | Competitions.tsx:391 | RECIPE-GAP RG-KO-19 | seed `eksterne-kompetisies.csv`: `prys` | seed | planned |
| `competitions-external-{i}-prize-icon` | Competitions.tsx:392 | svg in parent recipe | — | — | planned |
| `competitions-external-{i}-link` | Competitions.tsx:396 | RECIPE-GAP RG-KO-20 | seed `eksterne-kompetisies.csv`: `url` | copy-debt KOMP-EKS-SKAKEL | planned |
| `competitions-external-{i}-link-icon` | Competitions.tsx:402 | svg in parent recipe | — | — | planned |
| `competitions-external-empty` | Competitions.tsx:339 | `ink-card--empty ink-card--empty-lg` | client-side | — | planned |
| `competitions-external-empty-title` | Competitions.tsx:340 | text (page sheet, tokens) | static | copy-debt KOMP-LEEG-TITEL | planned |
| `competitions-external-empty-text` | Competitions.tsx:341 | text (page sheet, tokens) | static | copy-debt KOMP-LEEG-TEKS | planned |
| `competitions-external-empty-btn` | Competitions.tsx:342 | `ink-btn ink-btn--outline` | client-side | Vee filters uit (`Library/Archive.php:598`) | planned |
| `competitions-submit` | Competitions.tsx:412 | RECIPE-GAP RG-KO-21 | — | — | planned |
| `competitions-submit-h3` | Competitions.tsx:413 | `ink-heading-h2-block` | static | copy-debt KOMP-INDIEN-H3 | planned |
| `competitions-submit-text` | Competitions.tsx:414 | text (page sheet, tokens) | static | copy-debt KOMP-INDIEN-TEKS | planned |
| `competitions-submit-btn` | Competitions.tsx:418 | `ink-btn ink-btn--primary` | seed `kompetisies-jaarliks.csv`: `indien_epos` (mailto); see Q3 | copy-debt KOMP-INDIEN-KNOP | planned |
| `competitions-submit-btn-icon` | Competitions.tsx:420 | svg in parent recipe | — | — | planned |

## 6. Page sheet

| Asset | Gate | Consumers |
|---|---|---|
| `assets/css/kompetisies.css` (new, layout + text only) | `is_page('kompetisies')` | the four blocks, only used in this pattern |
| `assets/js/kompetisies.js` (new) | `is_page('kompetisies')` | same |

`home.css` loads on the front page only, so RG-KO-03 goes in `primitives.css`. The home hero keeps its accepted `::before` (`03-tuis`); only the shared pattern token is reused.

## 7. Recipe gaps

Rows are in `recipe-gaps.csv`. RG-IP-08, -09 and -10 from `04-inkpols` are also used here.

| gap | element | proposed class |
|---|---|---|
| RG-KO-01 | dark band without top border | `ink-band--dark ink-band--no-border` |
| RG-KO-02 | container padding pair | `ink-band--feature` |
| RG-KO-03 | plus-pattern layer on dark, as a real element | `ink-texture (+ --on-ink)` |
| RG-KO-04 | on-ink colour for h1 role and lede | `ink-heading--on-ink (generalised)` |
| RG-KO-05 | gold caps hero badge with icon | `ink-pill--gold-caps` |
| RG-KO-06 | meta pill on ink | `ink-pill--on-ink` |
| RG-KO-07 | lg ghost button with border on ink | `ink-btn--ghost-on-ink` |
| RG-KO-08 | base card with feature hover lift | `ink-card--lift` |
| RG-KO-09 | circular icon tile, size between circle and circle-lg | `ink-icon-tile--circle-md` |
| RG-KO-10 | gold uppercase xs theme line | `ink-eyebrow--theme` |
| RG-KO-11 | meta item in foreground with terracotta icon | `ink-meta--ink` |
| RG-KO-12 | ticket pill | `ink-pill--ticket` |
| RG-KO-13 | compact chip with primary active state | `ink-chip--sm` |
| RG-KO-14 | filter chip at medium weight | `ink-chip--medium` |
| RG-KO-15 | gallery photo with hover scale | `ink-photo` |
| RG-KO-16 | search field at this height on card fill, no Soek button | `ink-search-field--card` |
| RG-KO-17 | feature card without hover lift | `ink-card--static` |
| RG-KO-18 | base card with soft hover | `ink-card--lift-soft` |
| RG-KO-19 | meta item, small icon, wider gap | `ink-meta--sm-wide` |
| RG-KO-20 | medium-weight primary link with icon | `ink-link--medium` |
| RG-KO-21 | dashed filled callout box | `ink-card--callout` |

## 8. URL and slug check (dev, read-only, 2026-11-01)

| Check | Result |
|---|---|
| `/kompetisies/` | 200, Page 59192; `wp rewrite list` matches only the generic page rule |
| Other `kompetisies` post in any status | none; 59192 is the only row with that slug |
| Near names | `ink-dig-en-skryfkompetisie-2022` (Page 49428), `atkv-tak-loerie-poesiekompetisie` (Page 66669), several `artikel` posts: different slugs, no clash |
| Gala Pages | `ink-se-gala-aande` (28477), ten gala Pages and ten Fotos Pages are published; no clash, disposition is Q2 |

## 9. Test data

Create on dev for build and measurement, keep out of the theme and plugin, and list what was created. Rows go in the seed CSVs with a `Toets` prefix, as `inkpols-uitgawes.csv` does, so they survive step H. **Never Lovable's English.**

| Seed | Needs | Why |
|---|---|---|
| `kompetisies-jaarliks.csv` | 1 row | hero; `indien_epos` a test address |
| `kompetisies-kategorie.csv` | 3 rows, icons feather/book/pen, 3 guidelines each | ids 0..2, 0..2 |
| `gala.csv`, `gala-program.csv`, `gala-kaartjies.csv` | 1, 5, 3 rows; `prys_is_plekhouer=ja` | programme 0..4, tickets 0..2 |
| `gala-jare.csv` | 2025, 2024, 2023 | ids carry the year, and Lovable uses these three |
| `gala-fotos.csv` | 2025: 4, 2024: 3, 2023: 2 photos | same counts as Lovable. Real attachments are on dev under Pages 64688 (796), 61228 (850) and 57753 (582) |
| `eksterne-kompetisies.csv` | 9 rows; same genre and fee pattern as Lovable's rows (Fiction Free, Mixed Free, Fiction Free, Mixed paid, Poetry paid, Poetry paid, Fiction paid, Mixed Free, Fiction paid); no `nota` on rows 6 and 8 | ids 0..8; rows 6 and 8 render no note |

**Capture states**
- Default; `--click competitions-gala-past-year-2024 --state gala-2024`.
- **Empty:** `--click competitions-directory-genre-poetry --click competitions-directory-fee-free --state empty`. No row is Poetry + Free on either side. `run.sh` has `--click` but no `--fill`, which is what `tmp/audit-ids/verify.mjs` used. Text height will differ wherever copy-debt markers stand in for text; record such rows, don't tune them.

## 10. Accepted divergences (handoff §3)

| Divergence | Permitted item |
|---|---|
| Real gala photos, not `gala-*` | #4 |
| Afrikaans copy and owner seed text | #3 |

Nothing else diverges. Fictional prices are D4 and match the design's structure.

## 11. Copy

**Available:** Kompetisies (D1), Alles (`Training/Hub.php:667`), Vee filters uit (`Library/Archive.php:598`). Everything data-shaped comes from seed files, which the owner supplies.

**Copy debt** (add to the translation sheet and worklist with the English as reference; render as `[NEEDS HUMAN AFRIKAANS] <KEY>`; add `kompetisie`, `gala` to `afrikaans-terms.md`, whose only row now reserves "kompetisie" away from the monthly uitdaging):

| Key | Stands for (`Competitions.tsx`) |
|---|---|
| `KOMP-HERO-BADGE` | :81 "Our flagship competition" |
| `KOMP-HERO-SLUIT` | :92 "Entries close" (glossary has *sluitingsdatum*) |
| `KOMP-HERO-INSKRYF` | :102 "Enter the Competition" |
| `KOMP-HERO-RIGLYNE` | :107 "Read the Guidelines" |
| `KOMP-KAT-H2`, `KOMP-KAT-INTRO`, `KOMP-KAT-INSKRYF` | :119, :122, :157 |
| `KOMP-GALA-KICKER`, `KOMP-GALA-DEURE`, `KOMP-GALA-PROGRAM`, `KOMP-GALA-BESPREEK` | :174, :185, :199, :222 |
| `KOMP-GALA-VERLEDE-H3`, `KOMP-GALA-VERLEDE-TEKS` | :229, :231 |
| `KOMP-GIDS-KICKER`, `KOMP-GIDS-H2`, `KOMP-GIDS-TEKS`, `KOMP-GIDS-SOEK` | :284, :287, :290, :302 |
| `KOMP-GENRE-POETRY`, `-FICTION`, `-NON-FICTION`, `-MIXED` | :41 |
| `KOMP-FOOI-ALL`, `-FREE`, `-PAID` | :332 |
| `KOMP-TELLING` | :357 singular and plural |
| `KOMP-EKS-SLUIT`, `KOMP-EKS-SKAKEL` | :389, :402 |
| `KOMP-LEEG-TITEL`, `KOMP-LEEG-TEKS` | :340, :341 |
| `KOMP-INDIEN-H3`, `KOMP-INDIEN-TEKS`, `KOMP-INDIEN-KNOP` | :413, :415, :421 |

**For `KOMP-GIDS-TEKS`:** Page 59192's body and `_simple_css` hold INK's own Afrikaans disclaimer ("LET WEL: INK Skryf in Afrikaans het NIKS met die aanbied…"). Emptying the body removes it from the site. It's outside the permitted copy sources, so it isn't used; the owner may want it here.

## 12. Questions for owner

**Q1. Enter and Guidelines buttons.** D3 rules out an entry pipeline, but Lovable's four Enter buttons go to the writing page (`/skryf/` here), and that page files nothing into a competition. A visitor could believe they have entered. Guidelines has no target in Lovable.
- Spec default: follow Lovable until ruled.
- Options: keep; no target, like Reserve; or a link you supply, e.g. the competition's announcement.

**Q2. The old gala Pages.** `/ink-se-gala-aande/`, ten gala Pages and ten Fotos Pages stay published on dev. `prod-menu-mapping.md` says "new gala pages", D3 says the gala section on this page. Do they stay reachable, or 301 to `/kompetisies/`? This spec does not touch them.

**Q3. Which address does "Submit a listing" use?** No INK address exists in the repo. It's a seed cell, so the answer needs no code. Alternative: link to `/kontak/`.

**Q4. How do editors change this data later?** Seeds and options mean CSV + `--force`, with no admin screen. D3 defers the entries admin, not this. Fine for launch, or should the directory be an editable post type?

**Q5. How many gala years show?** Lovable has three and its chip row doesn't wrap; prod has ten galas. At ten the row overflows at 390. Name the years, or rule that the row wraps (a new divergence).
