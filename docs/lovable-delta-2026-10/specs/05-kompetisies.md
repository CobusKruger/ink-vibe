# 05 Kompetisies: build spec (N4)

**Status:** planned, awaiting recipe-gap ratification. **Treatment:** A. No values here: every style value comes from captures (R4/R5). **Map:** `../maps/kompetisies.csv`. **Rulings:** `owner-rulings.md`, D2, D3, D4, and THE FIRST RULE in `CLAUDE.md`.

**Scope:** web pages and their admin screens. No entry or judging pipeline, no payment (D3, D4).

**Competitions are not challenges (owner).** They get their own post type. `uitdaging` isn't reused, cadence `jaarliks` isn't used, and nothing here reads `CadenceType`. README N4's old wording, Epic 12B and the `kadens` row in `afrikaans-terms.md` are stale on this.

**How this page runs:** everything on it is added and edited on the site, by an editor, in Afrikaans admin screens. There is no options-from-CSV layer, no seed file and no `--force`. `migrate-all` reads one CSV, once, to import old competitions from before the cutover (§3.6).

## 1. Section spine

Nothing exists yet for this page.

| Lovable | INK block (new) | Files |
|---|---|---|
| `Competitions.tsx:73` dark hero | `ink/kompetisies-hero` | `Competitions/Hero.php` |
| `:115` categories | `ink/kompetisies-kategoriee` | `Competitions/Categories.php` |
| `:167` gala + Evenings past | `ink/kompetisies-gala` | `Competitions/Gala.php` |
| `:280` directory + submit box | `ink/kompetisies-gids` | `Competitions/Directory.php` |

**Chain:** `templates/page-kompetisies.html` (new; same frame as `page-kontak.html`) → `patterns/kompetisies.php` (new; the four blocks, no wrapper padding) → the blocks. **Registration:** `SystemPages::PAGES['kompetisies'] => 'Kompetisies'`. New module `Ink\Competitions` (`Module.php`, `Data.php`, `Migration.php`); add it to the deptrac rules. It reads `Ink\Kernel`, `Ink\Content` (the `PostTypes` and `FieldSets` constants, as `Ink\InkPols` does) and core.

## 2. Page adoption (D2)

Dev has **Page 59192**: slug `kompetisies`, title `Kompetisies` (README's "Eksterne Kompetisies" is the prod menu label), status publish, template `default`. `ensure()` finds it by path, so it is adopted, not created.

Empty it in code:
- **Body:** add a slug-keyed rule next to `LEGACY_SHORTCODE` in `SystemPages::ensure()`, e.g. `EMPTY_BODY = array( 'kompetisies' )`, so the ~1 KB body becomes `''` on every run. Idempotent; add to `SystemPagesTest`.
- **`_simple_css` meta:** delete it in the same rule. Simple CSS is active, and on dev this meta's Afrikaans text prints inside the `simple-css-output` style tag of `/kompetisies/` (verified in the page source). Emptying the body alone leaves it there.

The body holds only the LET WEL text and a link to `2026/01/SKRYFKOMPETISIES-van-2026-op-verskeie-platvorms.pdf` (read from the prod dump). The PDF stays in the media library; an editor can use it as the source when entering the first directory listings (§3.3).

The guard test at `SystemPagesTest.php:41-53` passes once the Page is in the list.

## 3. Data model

### 3.1 Per element

| Page element | Mechanism | Who changes it, where |
|---|---|---|
| Annual competition (hero) | CPT `kompetisie`: one post per yearly edition. Title + fields | Editor: Kompetisies → add or edit |
| Three categories, guidelines | Fields on that post, three slots; guidelines are a textarea, one per line | Same screen |
| Gala details | Fields on that post | Same screen |
| Programme | Textarea on that post, one item per line; line order is the order, numerals derived | Same screen |
| Ticket prices (fictional, D4) | Fields on that post, three label + price slots | Same screen |
| "Evenings past": year chips | Derived: every published `kompetisie` with a gala highlight or photos, newest `jaar` first | Appears when the post has them |
| "Evenings past": highlight | Field on that post | Same screen |
| "Evenings past": photos | Media library + a gallery field (ordered attachment ids) on that post. Caption is the attachment's own caption, and it is also the alt | Same screen: pick, reorder, caption in the media modal |
| External directory | CPT `eksterne_kompetisie`: one post per listing. Title + fields. Genre is a select, a blank fee means free | Editor: Eksterne Kompetisies → add, edit, unpublish |
| Submit a listing | Fixed `mailto:` in code (ruling) | Not editable; §4 |
| Headings and other static text | Copy-debt keys, §11 | Translation sheet |

**Why two post types:** the fields of a listing and of an edition barely overlap, and `FieldSets` gives one box per post type. **Why slots and line-per-item textareas:** `FieldSets` saves scalars only, the design has three categories and three tickets, and a blank slot simply doesn't render. A repeater would be more machinery for no visible gain.

### 3.2 Post type `kompetisie`

Register in `Content/PostTypes.php` and `Content/FieldSets.php`, the same declarative way as `inkpols_uitgawe` and `uitdaging`: `PostTypes::KOMPETISIE`, added to `all()` and `definitions()`.

| Setting | Value | Why |
|---|---|---|
| `public` | `false`, with `show_ui` and `show_in_rest` true | The page is `/kompetisies/`. No singles or archive, so the slug can't clash with Page 59192 or category `kompetisie` |
| `has_archive`, `rewrite` | `false` | Same |
| `supports` | `title`, `editor` | Editor holds an imported gala's old text (§3.6) for reference. Nothing renders it |
| Editor | Classic screen: `use_block_editor_for_post_type` returns false for this type | One save request carries title, status and box |
| Capability | Shared `ink_contents` capability type; `FieldSets` cap `edit_ink_contents` | Administrator and editor already hold it; no new capability, so no re-activation |
| Terms | New keys `kompetisie` (Kompetisie), `kompetisie_plural` (Kompetisies, D1) | `assertTermKey` needs them. Glossary first |

**`PostTypes::args()` must change for this:** it passes `(bool) $def['public']` and always a rewrite slug. Add optional `show_ui` (defaults to `public`) and accept `rewrite => false`. No existing type changes.

**Fields, all `ink_kompetisie_*`, in this order.** The post title is the naam (`competitions-hero-h1`). Labels: an existing string where one exists, otherwise a copy-debt key (admin screens are Afrikaans; §11).

| Key suffix | Input | Label | Feeds |
|---|---|---|---|
| `jaar` | number | `KOMP-ADMIN-JAAR` | ordering, year chip |
| `uitgawe` | text | `KOMP-ADMIN-UITGAWE` | hero badge |
| `beskrywing` | textarea | `KOMP-ADMIN-BESKRYWING` | hero text; **also marks a post as a current edition**, §3.5 |
| `sluitingsdatum` | date | Sluitingsdatum (`FieldSets`) | hero pill, `wp_date` |
| `inskrywing_nota` | text | `KOMP-ADMIN-INSKRYWING` | hero pill |
| `naaswenner_nota` | text | `KOMP-ADMIN-NAASWENNER` | categories intro |
| `kat{1,2,3}_naam` | text | `KOMP-ADMIN-KAT` + `: naam` pattern as "Gedig: titel" | card title |
| `kat{1,2,3}_ikoon` | select: feather, book, pen | `KOMP-ADMIN-IKOON` (options `KOMP-ADMIN-IKOON-*`) | card icon |
| `kat{1,2,3}_tema` | text | Tema (`FieldSets`) | card theme |
| `kat{1,2,3}_beskrywing` | textarea | `KOMP-ADMIN-BESKRYWING` | card text |
| `kat{1,2,3}_woordlimiet` | text | `KOMP-ADMIN-WOORDLIMIET` | card limit |
| `kat{1,2,3}_riglyne` | textarea, one per line | `KOMP-ADMIN-RIGLYNE` | guideline list |
| `gala_naam` | text | `KOMP-ADMIN-GALA-NAAM` | gala h2 |
| `gala_beskrywing` | textarea | `KOMP-ADMIN-BESKRYWING` | gala text |
| `gala_datum` | date | `KOMP-ADMIN-GALA-DATUM` | date row, `wp_date` |
| `gala_deure` | text | `KOMP-ADMIN-GALA-DEURE` | doors |
| `gala_plek` | text | `KOMP-ADMIN-GALA-PLEK` | venue |
| `gala_drag` | text | `KOMP-ADMIN-GALA-DRAG` | dress |
| `gala_program` | textarea, one per line | `KOMP-ADMIN-GALA-PROGRAM` | programme |
| `kaartjie{1,2,3}_etiket` | text | `KOMP-ADMIN-KAARTJIE` | ticket label |
| `kaartjie{1,2,3}_prys` | text | `KOMP-ADMIN-PRYS` (says the price is fictional until payments exist, D4) | ticket price |
| `gala_hoogtepunt` | text | `KOMP-ADMIN-HOOGTEPUNT` | past highlight |
| `gala_fotos` | gallery | `KOMP-ADMIN-FOTOS` | past photos |

The placeholder marker from D4 lives in that label. Nothing about it shows on the page.

### 3.3 Post type `eksterne_kompetisie`

Same registration settings, `supports` = `title` only. Terms: `eksterne_kompetisie`, `eksterne_kompetisie_plural` (Eksterne Kompetisies, the prod menu label). No editor support, so WordPress gives the classic screen by itself. Newest publish date first; an editor unpublishes a listing to remove it. `Data::listings()` caps at 200.

| Key `ink_eksterne_*` | Input | Label | Feeds |
|---|---|---|---|
| `organiseerder` | text | `KOMP-ADMIN-ORGANISEERDER` | org line |
| `streek` | text | `KOMP-ADMIN-STREEK` | org line, search |
| `sluitingsdatum` | text (`1 November`: recurring, no year) | Sluitingsdatum (`FieldSets`) | deadline |
| `prys` | text | `KOMP-ADMIN-PRYS-EKSTERN` | prize |
| `fooi` | text; **blank = free** | `KOMP-ADMIN-FOOI` | fee, fee filter |
| `genre` | select: gedigte, fiksie, niefiksie, gemeng | `KOMP-ADMIN-GENRE`, options `KOMP-GENRE-*` | genre pill, filter |
| `url` | url, `esc_url_raw` | Skakel (`FieldSets`) | link |
| `nota` | textarea, optional | `KOMP-ADMIN-NOTA` | note |

### 3.4 `FieldSets` gains media inputs

The box's only media input today is a number ("heg-ID"), which makes an editor look up ids. Add two inputs to `renderBox()`:
- `media`: one attachment, stored as the integer. Choose and Remove buttons with a thumbnail, through `wp.media`.
- `gallery`: many, stored as a comma-separated id list in media-modal order, sanitised to `absint` ids. Captions are edited in the modal, on the attachment.

`assets/js/admin-media-field.js` loads on the edit screens that use them, with `wp_enqueue_media()`. Without JS the real input stays visible and still saves. Update the class docblock ("JS-free"). `04-inkpols` uses `media` too.

### 3.5 Reading the data

`Competitions\Data`, read directly at render, with no options and no CSV:

| Method | Rule |
|---|---|
| `current(): ?Edition` | Published `kompetisie` with `beskrywing` set, highest `jaar`. Imported old competitions have no `beskrywing`, so one never becomes current |
| `pastGalas(): list<GalaYear>` | Published `kompetisie` with `gala_hoogtepunt` or `gala_fotos`, highest `jaar` first. A later editor adding photos to this year's post makes this year's chip appear; nothing else is needed |
| `listings(): list<Listing>` | Published `eksterne_kompetisie` |

**After a save:** the page is cached and neither type is public, so on `save_post` of either type purge Page 59192 through the same call the site uses elsewhere for LiteSpeed. Otherwise an edit doesn't show.

### 3.6 Importing old competitions: no file

**Discovery, not a list (owner, 2026-11-02):** `wp ink migrate-kompetisies`, the `kompetisies` step of `migrate-all`, ahead of `clean-shortcodes` and `generate-redirects`, finds the galas and the 2022 competition itself in the old page tree, **by meta first** (owner, 2026-11-02): the owner runs `docs/migration-tools/tag-old-competitions.sql` on production before the snapshot (runbook step 2a), which sets `ink_old_gala` = year on each gala Page, `ink_old_gala_fotos` = year on its Fotos Page and `ink_old_competition` = year on the competition Page (49428). **Fallback, with a warning "found by title; tag it":** a top-level Page whose title holds "gala" with a child Page whose title starts "Foto" (the Fotos Page); a tagged gala lacking a tagged Fotos Page takes that child; a tagged Fotos Page under an untagged gala is used. An untagged competition is not found. Name, year (the tag, else the first four-digit year in the title) and body come from the gala or competition Page; the photos from the Fotos Page; the key is `gala-<jaar>` (`-2` for a second in a year). `INK se Gala-Aande` (28477) is the fixed index. A gala added to production before the cutover imports with no file edit; a gala Page with no Fotos Page is reported, not guessed. Same shape as `migrate-inkpols`: once-off (`ink_kompetisies_csv_migration_done`), get-or-create on marker `ink_kompetisie_source_key`. **It runs before `clean-shortcodes`** because the photos are the ids in the Fotos Pages' gallery shortcodes; where those are already gone it reads none, reports it, links nothing and keeps the Pages.

**No file is read** (2026-11-03): `kompetisies-oud.csv` is gone. The galas and the 2022 competition come from the tags on the old Pages; the highlight and captions are edited on the site.

The ten galas and how their Fotos Pages hold the photos (checked: the parser reads all forms; per-year counts are 2025: 656, 2024: 441, 2023: 464, 2022: 571, 2021: 431, 2020: 386, 2019: 166, 2018: 101, 2017: 103, 2016: 60, 3,379 in all). Kept in page order, repeats dropped:

| `jaar` | `bladsy` | `fotos_bladsy` | Photos on the Fotos Page, and how they're held |
|---|---|---|---|
| 2025 | 64611 | 64688 | `vc_media_grid include` |
| 2024 | 61225 | 61228 | `vc_media_grid include` |
| 2023 | 57691 | 57753 | `vc_gallery images` |
| 2022 | 52672 | 52674 | `vc_media_grid include` |
| 2021 | 47913 | 47916 | `vc_media_grid include` |
| 2020 | 43438 | 43440 | `vc_media_grid include` |
| 2019 | 37975 | 37984 | `vc_media_grid include` |
| 2018 | 28946 | 29087 | `vc_media_grid include` |
| 2017 | 17991 | 17993 | `vc_gallery` |
| 2016 | 7802 | 9949 | `vc_single_image` |
| 2022 | 49428 | | None. "INK dig- en Skryfkompetisie 2022": its rules and dates are kept in `post_content`. No gala, so no chip |

**Never in this file:** the current competition, categories, guidelines, gala details, programme, tickets or external listings. Those are entered on the site. Nor test data (§9).

**Not imported:** the `kompetisie`-named prod categories (`ChallengeRound::isCompetition()`; 12 on the projekte tree, e.g. `GrysKrag`, `Pendoring/INK`). They hold no content beyond a category; their writing migrates as ordinary posts, and the two `outside` rows in `result-posts.csv` stay ordinary posts. The Pendoring winners are in the 2018 gala body, kept above.

**After go-live:** the file and the step are finished. A wrong caption or a new photo is fixed on the site. `--force` exists for rehearsals only: it reconciles rows by marker and never touches a post without one, but it would overwrite site edits to imported posts, so it never runs on the live site. Say so in `docs/migration-runbook.md`.

### 3.7 Redirects

`/ink-se-gala-aande/`, the ten gala Pages and the ten Fotos Pages 301 to `/kompetisies/`: 21 exact paths in `MigrationStaticRedirects::EXACT`, committed code with no CSV involved. **A gala found later (a 2026 gala) is not in that fixed list:** the step stores the old path of every Page it retires in the option `ink_gala_old_paths` (taken before trashing, from the Page's URI), and `RedirectGenerator::maybeRedirect()` serves those through `StaticRedirects::storedGalaTarget()` beside `EXACT`. A redirect serves only on a 404, so the step **trashes** the Pages (28477 plus each gala's Page and Fotos Page, once its photos are linked), never deleting them. Attachments stay. Check them with `wp ink verify-redirects`.

Page 49428 isn't one of the ruled redirects: it stays published.

## 4. Behaviour

| Element | Behaviour |
|---|---|
| Gala year chips | **All years show, in one row that slides sideways and never wraps** (owner; §10). Layout only, in the page sheet. Client-side. First year active. Every year's photos are in the HTML, `loading="lazy"`; only the active year's carry audit-ids (JS moves nothing else). Highlight and photos swap on click |
| Directory search | Client-side over name, organiser, region, note, as `Competitions.tsx:50-64` |
| Genre, fee chips | Client-side; AND with search. Count line updates |
| Empty state | Shown when nothing matches; clear button resets all three |
| No JS | All listings shown, active gala year only |
| Reserve (D3, D4) | **No target.** `<button type="button">` with no handler, as Lovable's `:220`. No payment |
| Ticket prices | Fictional, from the fields (D4) |
| Enter buttons (hero + each category) | **Link to `/skryf/`** (ruled). The writer chooses there what to write |
| Guidelines | `<button type="button">`, no target, as Lovable's `:106` |
| Submit a listing | `mailto:skryfafrikaans@ink.org.za?subject=Nuwe%20kompetisie%20kennisgewing` (ruled). A class constant in `Directory.php`; build the subject with `rawurlencode()` |
| External links | `target="_blank" rel="noopener noreferrer"` |

**Script:** `assets/js/kompetisies.js`, one file.

**Empty data** (not in the design; follows the `inkpols.md` precedent of hiding). Nothing here fails a step, because an editor can publish in any order:

| State | Result |
|---|---|
| No current edition (the state straight after `migrate-all`) | Hero and categories hide; the gala details column hides |
| Current edition without `gala_naam` | Gala details column hides |
| Blank category or ticket slot; blank `riglyne`, `gala_program`, `nota` | That item doesn't render |
| No past gala | `competitions-gala-past` hides. Both gala columns empty: `competitions-gala` hides |
| Past gala without highlight, or without photos | That element hides; the chip stays |
| No listing | Empty state with no clear button |

## 5. Element tables

### Hero

| audit-id | Lovable | recipe | data source | copy | status / note |
|---|---|---|---|---|---|
| `competitions-hero` | Competitions.tsx:73 | `ink-band ink-band--dark ink-band--flush` + RECIPE-GAP RG-KO-01 | — | — | planned |
| `competitions-hero-texture` | Competitions.tsx:74 | RECIPE-GAP RG-KO-03 | — | — | planned |
| `competitions-hero-container` | Competitions.tsx:77 | `ink-container` + `ink-band--step` | — | — | planned |
| `competitions-hero-content` | Competitions.tsx:78 | layout (page sheet) | — | — | planned |
| `competitions-hero-badge` | Competitions.tsx:79 | RECIPE-GAP RG-KO-05 | kompetisie: ink_kompetisie_uitgawe | editor's text + copy-debt KOMP-HERO-BADGE | planned |
| `competitions-hero-badge-icon` | Competitions.tsx:80 | svg in parent recipe | — | — | planned |
| `competitions-hero-h1` | Competitions.tsx:83 | `ink-heading-h1-index` + RECIPE-GAP RG-KO-04 | kompetisie: post title | editor's text | planned |
| `competitions-hero-text` | Competitions.tsx:86 | text (page sheet, tokens) | kompetisie: ink_kompetisie_beskrywing | editor's text | planned |
| `competitions-hero-meta` | Competitions.tsx:89 | layout (page sheet) | — | — | planned |
| `competitions-hero-meta-close` | Competitions.tsx:90 | RECIPE-GAP RG-KO-06 | kompetisie: ink_kompetisie_sluitingsdatum | editor's text + copy-debt KOMP-HERO-SLUIT | planned |
| `competitions-hero-meta-close-icon` | Competitions.tsx:91 | svg in parent recipe | — | — | planned |
| `competitions-hero-meta-entry` | Competitions.tsx:94 | RECIPE-GAP RG-KO-06 | kompetisie: ink_kompetisie_inskrywing_nota | editor's text | planned |
| `competitions-hero-meta-entry-icon` | Competitions.tsx:95 | svg in parent recipe | — | — | planned |
| `competitions-hero-actions` | Competitions.tsx:99 | layout (page sheet) | — | — | planned |
| `competitions-hero-btn-primary` | Competitions.tsx:100 | `ink-btn ink-btn--lg ink-btn--primary` | link `/skryf/` (ruled; Lovable `/write`) | copy-debt KOMP-HERO-INSKRYF | planned |
| `competitions-hero-btn-primary-icon` | Competitions.tsx:103 | svg in parent recipe | — | — | planned |
| `competitions-hero-btn-guidelines` | Competitions.tsx:106 | `ink-btn ink-btn--lg` + RECIPE-GAP RG-KO-07 | no target, as Lovable | copy-debt KOMP-HERO-RIGLYNE | planned |

### Categories

| audit-id | Lovable | recipe | data source | copy | status / note |
|---|---|---|---|---|---|
| `competitions-categories` | Competitions.tsx:115 | `ink-container ink-band--poem` | — | — | planned |
| `competitions-categories-head` | Competitions.tsx:116 | layout (page sheet) | — | — | planned |
| `competitions-categories-head-text` | Competitions.tsx:117 | layout (page sheet) | — | — | planned |
| `competitions-categories-h2` | Competitions.tsx:118 | `ink-heading-h2-section` | static | copy-debt KOMP-KAT-H2 | planned |
| `competitions-categories-intro` | Competitions.tsx:121 | text (page sheet, tokens) | kompetisie: ink_kompetisie_naaswenner_nota | copy-debt KOMP-KAT-INTRO + editor's text | planned |
| `competitions-categories-grid` | Competitions.tsx:126 | layout (page sheet) | — | — | planned |
| `competitions-category` | Competitions.tsx:132 | `ink-card ink-card--lg` + RECIPE-GAP RG-KO-08 | current edition, category 1 | — | planned |
| `competitions-category-{i}` | Competitions.tsx:132 | `ink-card ink-card--lg` + RECIPE-GAP RG-KO-08 | current edition, category slot | — | planned |
| `competitions-category-{i}-body` | Competitions.tsx:135 | layout (page sheet) | — | — | planned: Lovable CardContent; INK nests it so the padding box matches |
| `competitions-category-{i}-icon-wrap` | Competitions.tsx:136 | layout (page sheet) | — | — | planned |
| `competitions-category-{i}-icon-tile` | Competitions.tsx:137 | RECIPE-GAP GAP-03-03 | — | — | planned |
| `competitions-category-{i}-icon` | Competitions.tsx:138 | svg in parent recipe | kompetisie: ink_kompetisie_kat{n}_ikoon | — | planned |
| `competitions-category-{i}-title` | Competitions.tsx:141 | `ink-heading-h2-block` | kompetisie: ink_kompetisie_kat{n}_naam | editor's text | planned |
| `competitions-category-{i}-theme` | Competitions.tsx:142 | RECIPE-GAP RG-KO-10 | kompetisie: ink_kompetisie_kat{n}_tema | editor's text | planned |
| `competitions-category-{i}-desc` | Competitions.tsx:143 | text (page sheet, tokens) | kompetisie: ink_kompetisie_kat{n}_beskrywing | editor's text | planned |
| `competitions-category-{i}-limit` | Competitions.tsx:144 | RECIPE-GAP RG-KO-11 | kompetisie: ink_kompetisie_kat{n}_woordlimiet | editor's text | planned |
| `competitions-category-{i}-limit-icon` | Competitions.tsx:145 | svg in parent recipe | — | — | planned |
| `competitions-category-{i}-guidelines` | Competitions.tsx:148 | layout (page sheet) | — | — | planned |
| `competitions-category-{i}-guideline-{g}` | Competitions.tsx:150 | text (page sheet, tokens) | kompetisie: ink_kompetisie_kat{n}_riglyne, one line | editor's text | planned |
| `competitions-category-{i}-guideline-{g}-dot` | Competitions.tsx:151 | text (page sheet, tokens) | — | — | planned |
| `competitions-category-{i}-btn` | Competitions.tsx:156 | `ink-btn ink-btn--outline ink-btn--block` | link `/skryf/` (ruled) | copy-debt KOMP-KAT-INSKRYF + category naam | planned |

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
| `competitions-gala-h2` | Competitions.tsx:176 | `ink-heading-h2-section` | kompetisie: ink_kompetisie_gala_naam | editor's text | planned |
| `competitions-gala-text` | Competitions.tsx:179 | text (page sheet, tokens) | kompetisie: ink_kompetisie_gala_beskrywing | editor's text | planned |
| `competitions-gala-info` | Competitions.tsx:181 | layout (page sheet) | — | — | planned |
| `competitions-gala-info-date` | Competitions.tsx:182 | RECIPE-GAP RG-KO-11 | — | — | planned |
| `competitions-gala-info-date-icon` | Competitions.tsx:183 | svg in parent recipe | — | — | planned |
| `competitions-gala-info-date-text` | Competitions.tsx:184 | text (page sheet, tokens) | kompetisie: ink_kompetisie_gala_datum | editor's text | planned |
| `competitions-gala-info-doors` | Competitions.tsx:185 | text (page sheet, tokens) | kompetisie: ink_kompetisie_gala_deure | copy-debt KOMP-GALA-DEURE + editor's text | planned |
| `competitions-gala-info-venue` | Competitions.tsx:187 | RECIPE-GAP RG-KO-11 | — | — | planned |
| `competitions-gala-info-venue-icon` | Competitions.tsx:188 | svg in parent recipe | — | — | planned |
| `competitions-gala-info-venue-text` | Competitions.tsx:189 | text (page sheet, tokens) | kompetisie: ink_kompetisie_gala_plek | editor's text | planned |
| `competitions-gala-info-dress` | Competitions.tsx:191 | RECIPE-GAP RG-KO-11 | — | — | planned |
| `competitions-gala-info-dress-icon` | Competitions.tsx:192 | svg in parent recipe | — | — | planned |
| `competitions-gala-info-dress-text` | Competitions.tsx:193 | text (page sheet, tokens) | kompetisie: ink_kompetisie_gala_drag | editor's text | planned |
| `competitions-gala-programme` | Competitions.tsx:197 | `ink-card--feature` + RECIPE-GAP RG-KO-17 | — | — | planned |
| `competitions-gala-programme-label` | Competitions.tsx:198 | RECIPE-GAP GAP-03-04 muted | static | copy-debt KOMP-GALA-PROGRAM | planned |
| `competitions-gala-programme-list` | Competitions.tsx:201 | layout (page sheet) | — | — | planned |
| `competitions-gala-programme-item-{n}` | Competitions.tsx:203 | layout (page sheet) | kompetisie: ink_kompetisie_gala_program, line order | — | planned |
| `competitions-gala-programme-item-{n}-num` | Competitions.tsx:204 | text (page sheet, tokens) | derived | — | planned |
| `competitions-gala-programme-item-{n}-text` | Competitions.tsx:205 | text (page sheet, tokens) | kompetisie: ink_kompetisie_gala_program, one line | editor's text | planned |
| `competitions-gala-tickets` | Competitions.tsx:211 | layout (page sheet) | — | — | planned |
| `competitions-gala-ticket-{n}` | Competitions.tsx:213 | RECIPE-GAP RG-KO-12 | kompetisie: ink_kompetisie_kaartjie{n}_etiket | editor's text | planned |
| `competitions-gala-ticket-{n}-icon` | Competitions.tsx:214 | svg in parent recipe | — | — | planned |
| `competitions-gala-ticket-{n}-price` | Competitions.tsx:215 | text (page sheet, tokens) | kompetisie: ink_kompetisie_kaartjie{n}_prys | editor's text; fictional (D4) | planned |
| `competitions-gala-btn-reserve` | Competitions.tsx:220 | `ink-btn ink-btn--lg ink-btn--primary` | no target (D3, D4), `<button type="button">` | copy-debt KOMP-GALA-BESPREEK | planned |
| `competitions-gala-btn-reserve-icon` | Competitions.tsx:221 | svg in parent recipe | — | — | planned |
| `competitions-gala-past` | Competitions.tsx:227 | layout (page sheet) | hidden with no gala year (§4) | — | planned |
| `competitions-gala-past-card` | Competitions.tsx:228 | `ink-card--feature-hero` + RECIPE-GAP RG-KO-17 | — | — | planned |
| `competitions-gala-past-h3` | Competitions.tsx:229 | `ink-heading-h2-block` | static | copy-debt KOMP-GALA-VERLEDE-H3 | planned |
| `competitions-gala-past-text` | Competitions.tsx:230 | text (page sheet, tokens) | static | copy-debt KOMP-GALA-VERLEDE-TEKS | planned |
| `competitions-gala-past-years` | Competitions.tsx:235 | layout (page sheet) | — | — | divergence: all years; slides sideways, never wraps (§10) |
| `competitions-gala-past-year-2025` | Competitions.tsx:237 | `ink-chip` | published kompetisie with past gala: ink_kompetisie_jaar | year | planned |
| `competitions-gala-past-year-2024` | Competitions.tsx:237 | `ink-chip` | published kompetisie with past gala: ink_kompetisie_jaar | year | planned |
| `competitions-gala-past-year-2023` | Competitions.tsx:237 | `ink-chip` | published kompetisie with past gala: ink_kompetisie_jaar | year | planned |
| `competitions-gala-past-year-{2022..2016}` | — | `ink-chip` | same | year | scope: real data, Lovable has three years |
| `competitions-gala-past-highlight` | Competitions.tsx:251 | text (page sheet, tokens) | active year kompetisie: ink_kompetisie_gala_hoogtepunt | editor's text | planned |
| `competitions-gala-past-grid` | Competitions.tsx:255 | layout (page sheet) | — | — | planned |
| `competitions-gala-past-photo-{n}` | Competitions.tsx:257 | layout (page sheet) | active year kompetisie: ink_kompetisie_gala_fotos, order | — | planned: ids stamped on the active year only, so they stay unique |
| `competitions-gala-past-photo-{n}-img` | Competitions.tsx:261 | RECIPE-GAP RG-KO-15 | active year kompetisie: ink_kompetisie_gala_fotos (media library) | alt = caption | planned: real images only: permitted #4 |
| `competitions-gala-past-photo-{n}-caption` | Competitions.tsx:269 | text (page sheet, tokens) | attachment caption (media library) | editor's text | planned |

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
| `competitions-directory-genre-all` | Competitions.tsx:308 | `ink-chip` | static | Alles (`Hub.php:667`, `inkpols.md`) | planned |
| `competitions-directory-genre-poetry` | Competitions.tsx:308 | `ink-chip` | eksterne_kompetisie: ink_eksterne_genre select | copy-debt KOMP-GENRE-POETRY | planned |
| `competitions-directory-genre-fiction` | Competitions.tsx:308 | `ink-chip` | eksterne_kompetisie: ink_eksterne_genre select | copy-debt KOMP-GENRE-FICTION | planned |
| `competitions-directory-genre-non-fiction` | Competitions.tsx:308 | `ink-chip` | eksterne_kompetisie: ink_eksterne_genre select | copy-debt KOMP-GENRE-NON-FICTION | planned |
| `competitions-directory-genre-mixed` | Competitions.tsx:308 | `ink-chip` | eksterne_kompetisie: ink_eksterne_genre select | copy-debt KOMP-GENRE-MIXED | planned |
| `competitions-directory-filters-fee` | Competitions.tsx:321 | layout (page sheet) | — | — | planned |
| `competitions-directory-fee-all` | Competitions.tsx:323 | `ink-chip` | client-side | copy-debt KOMP-FOOI-ALL | planned |
| `competitions-directory-fee-free` | Competitions.tsx:323 | `ink-chip` | client-side | copy-debt KOMP-FOOI-FREE | planned |
| `competitions-directory-fee-paid` | Competitions.tsx:323 | `ink-chip` | client-side | copy-debt KOMP-FOOI-PAID | planned |
| `competitions-external-count` | Competitions.tsx:356 | text (page sheet, tokens) | derived | copy-debt KOMP-TELLING (en/mv) | planned |
| `competitions-external-grid` | Competitions.tsx:361 | layout (page sheet) | — | — | planned |
| `competitions-external` | Competitions.tsx:365 | `ink-card` + RECIPE-GAP RG-KO-08 | first `eksterne_kompetisie` | — | planned: first of 0..8; padding box on the -body child as Lovable |
| `competitions-external-{i}` | Competitions.tsx:365 | `ink-card` + RECIPE-GAP RG-KO-08 | one `eksterne_kompetisie` | — | planned |
| `competitions-external-{i}-body` | Competitions.tsx:368 | layout (page sheet) | — | — | planned |
| `competitions-external-{i}-head` | Competitions.tsx:369 | layout (page sheet) | — | — | planned |
| `competitions-external-{i}-genre` | Competitions.tsx:370 | `ink-pill ink-pill--outline` | eksterne_kompetisie: ink_eksterne_genre | copy-debt KOMP-GENRE-* | planned |
| `competitions-external-{i}-fee` | Competitions.tsx:373 | RECIPE-GAP RG-IP-08 + `ink-eyebrow--accent` | eksterne_kompetisie: ink_eksterne_fooi (blank = free) | editor's text / copy-debt KOMP-FOOI-FREE | planned |
| `competitions-external-{i}-title` | Competitions.tsx:377 | `ink-heading-h3-card` | eksterne_kompetisie: post title | editor's text | planned |
| `competitions-external-{i}-org` | Competitions.tsx:380 | text (page sheet, tokens) | eksterne_kompetisie: ink_eksterne_organiseerder + ink_eksterne_streek | editor's text | planned |
| `competitions-external-{i}-note` | Competitions.tsx:384 | text (page sheet, tokens) | eksterne_kompetisie: ink_eksterne_nota (optional) | editor's text | planned: Lovable rows 6 and 8 have no note; fixture mirrors that |
| `competitions-external-{i}-meta` | Competitions.tsx:386 | layout (page sheet) | — | — | planned |
| `competitions-external-{i}-deadline` | Competitions.tsx:387 | RECIPE-GAP RG-KO-19 | eksterne_kompetisie: ink_eksterne_sluitingsdatum | copy-debt KOMP-EKS-SLUIT + editor's text | planned |
| `competitions-external-{i}-deadline-icon` | Competitions.tsx:388 | svg in parent recipe | — | — | planned |
| `competitions-external-{i}-prize` | Competitions.tsx:391 | RECIPE-GAP RG-KO-19 | eksterne_kompetisie: ink_eksterne_prys | editor's text | planned |
| `competitions-external-{i}-prize-icon` | Competitions.tsx:392 | svg in parent recipe | — | — | planned |
| `competitions-external-{i}-link` | Competitions.tsx:396 | RECIPE-GAP RG-KO-20 | eksterne_kompetisie: ink_eksterne_url | copy-debt KOMP-EKS-SKAKEL | planned |
| `competitions-external-{i}-link-icon` | Competitions.tsx:402 | svg in parent recipe | — | — | planned |
| `competitions-external-empty` | Competitions.tsx:339 | `ink-card--empty ink-card--empty-lg` | client-side | — | planned |
| `competitions-external-empty-title` | Competitions.tsx:340 | text (page sheet, tokens) | static | copy-debt KOMP-LEEG-TITEL | planned |
| `competitions-external-empty-text` | Competitions.tsx:341 | text (page sheet, tokens) | static | copy-debt KOMP-LEEG-TEKS | planned |
| `competitions-external-empty-btn` | Competitions.tsx:342 | `ink-btn ink-btn--outline` | client-side | Vee filters uit (`Library/Archive.php:598`) | planned |
| `competitions-submit` | Competitions.tsx:412 | RECIPE-GAP RG-KO-21 | — | — | planned |
| `competitions-submit-h3` | Competitions.tsx:413 | `ink-heading-h2-block` | static | copy-debt KOMP-INDIEN-H3 | planned |
| `competitions-submit-text` | Competitions.tsx:414 | text (page sheet, tokens) | static | copy-debt KOMP-INDIEN-TEKS | planned |
| `competitions-submit-btn` | Competitions.tsx:418 | `ink-btn ink-btn--primary` | fixed mailto constant (ruled; §4) | copy-debt KOMP-INDIEN-KNOP | planned |
| `competitions-submit-btn-icon` | Competitions.tsx:420 | svg in parent recipe | — | — | planned |

## 6. Page sheet

| Asset | Gate | Consumers |
|---|---|---|
| `assets/css/kompetisies.css` (new, layout + text only) | `is_page('kompetisies')` | the four blocks, only used in this pattern |
| `assets/js/kompetisies.js` (new) | `is_page('kompetisies')` | same |
| `assets/js/admin-media-field.js` (new) | edit screens using `media` or `gallery` | §3.4 |

`home.css` loads on the front page only, so RG-KO-03 goes in `primitives.css`. The home hero keeps its accepted `::before` (`03-tuis`); only the shared pattern token is reused.

## 7. Recipe gaps

Rows are in `recipe-gaps.csv`. RG-IP-08 and -10 from `04-inkpols`, GAP-03-03 and GAP-03-04 from `03-tuis` are also used here. RG-KO-09 merged into GAP-03-03. This rewrite adds no gap: the sliding chip row is layout, and admin screens are core WordPress.

| gap | element | proposed class |
|---|---|---|
| RG-KO-01 | dark band without top border | `ink-band--dark ink-band--no-border` |
| RG-KO-03 | plus-pattern layer on dark, as a real element | `ink-texture (+ --on-ink)` |
| RG-KO-04 | on-ink colour for h1 role and lede | `ink-heading--on-ink (generalised)` |
| RG-KO-05 | gold caps hero badge with icon | `ink-pill--gold-caps` |
| RG-KO-06 | meta pill on ink | `ink-pill--on-ink` |
| RG-KO-07 | lg ghost button with border on ink | `ink-btn--ghost-on-ink` |
| RG-KO-08 | base card with feature hover lift | `ink-card--lift` |
| RG-KO-10 | gold uppercase xs theme line | `ink-eyebrow--theme` |
| RG-KO-11 | meta item in foreground with terracotta icon | `ink-meta--ink` |
| RG-KO-12 | ticket pill | `ink-pill--ticket` |
| RG-KO-15 | gallery photo with hover scale | `ink-photo` |
| RG-KO-16 | search field at this height on card fill, no Soek button | `ink-search-field--card` |
| RG-KO-17 | feature card without hover lift | `ink-card--static` |
| RG-KO-19 | meta item, small icon, wider gap | `ink-meta--sm-wide` |
| RG-KO-20 | medium-weight primary link with icon | `ink-link--medium` |
| RG-KO-21 | dashed filled callout box | `ink-card--callout` |

## 8. URL and slug check (dev, read-only, 2026-11-01)

| Check | Result |
|---|---|
| `/kompetisies/` | 200, Page 59192; `wp rewrite list` matches only the generic page rule |
| Other `kompetisies` post in any status | none; 59192 is the only row with that slug |
| Near names | `ink-dig-en-skryfkompetisie-2022` (Page 49428), `atkv-tak-loerie-poesiekompetisie` (Page 66669), several `artikel` posts: different slugs, no clash |
| New post types | `kompetisie` and `eksterne_kompetisie` have no rewrite or archive: no URL, no clash with category `kompetisie` |
| Gala Pages | Page IDs and shortcodes in §3.6 re-read from `tmp/nuwe-ink-imported.sql` (2026-11-01; the Local site was stopped). Redirects §3.7 |

Page 66669 (ATKV-Tak Loerie) is an external competition's notice. It stays as it is; an editor may add a listing for it.

## 9. Test data

There is no test data (the fixture scripts were removed 2026-11-03). Competitions and directory listings are added on the site through the post types. **Never Lovable's English, and never in `kompetisies-oud.csv`.**

| Creates | Needs | Why |
|---|---|---|
| 1 `kompetisie`, `jaar` 2026 | All fields; 3 categories, icons feather/book/pen, 3 guidelines each; 5 programme lines; 3 tickets | hero, ids 0..2, 0..2, programme 0..4, tickets 0..2 |
| Imported 2025, 2024, 2023 posts | `gala_hoogtepunt` set; test captions on the first 4, 3, 2 photos. Galleries keep every photo (owner, 2026-11-02) | `/kompetisies/` shows four a year, so 2024 shows one photo more than Lovable (`EXTRA` in the gala-2024 state) |
| 9 `eksterne_kompetisie` | same genre and fee pattern as Lovable's rows (Fiction Free, Mixed Free, Fiction Free, Mixed paid, Poetry paid, Poetry paid, Fiction paid, Mixed Free, Fiction paid); no `nota` on rows 6 and 8 | ids 0..8; rows 6 and 8 render no note |

**Capture states**
- Default; `--click competitions-gala-past-year-2024 --state gala-2024`.
- **Empty:** `--click competitions-directory-genre-poetry --click competitions-directory-fee-free --state empty`. No row is Poetry + Free on either side. `run.sh` has `--click` but no `--fill`, which is what `tmp/audit-ids/verify.mjs` used. Text height will differ wherever copy-debt markers stand in for text; record such rows, don't tune them.
- **Chip row:** at 390, confirm by capture that the ten chips stay on one line and the page itself doesn't scroll sideways.

**Unit and admin tests:** `Data::current()` ignores imported posts; `pastGalas()` order; blank slots; the `mailto` string; both post types' save paths through `FieldSets`; `PostTypesTest` (nine CPTs becomes eleven) and `FieldSetsTest` (`metaKeys` count). Add one and edit one of each type through the admin screen on dev, and see the page change with no CLI step: that is the acceptance test for THE FIRST RULE here.

## 10. Accepted divergences (handoff §3)

| Divergence | Permitted item |
|---|---|
| Real gala photos, not `gala-*` | #4 |
| Afrikaans copy and editors' text | #3 |
| **Gala year chips:** all years (ten today, not Lovable's three) in a row that slides sideways and never wraps | Owner, 2026-11-01 (`owner-rulings.md`). Map: `competitions-gala-past-years` `divergence`, extra chips `scope`. Disclose in a code comment (R6) |

Fictional prices are D4 and match the design's structure. The LET WEL disclaimer is not in the design, and isn't in this build: the owner adds it back later.

## 11. Copy

**Available:** Kompetisies (D1), Alles (`Training/Hub.php:667`), Vee filters uit (`Library/Archive.php:598`); admin: Tema, Sluitingsdatum, Skakel (`FieldSets`), Eksterne Kompetisies (prod menu). Everything data-shaped is what the editor types.

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
| `KOMP-GENRE-POETRY`, `-FICTION`, `-NON-FICTION`, `-MIXED` | :41 (also the admin select's options) |
| `KOMP-FOOI-ALL`, `-FREE`, `-PAID` | :332 |
| `KOMP-TELLING` | :357 singular and plural |
| `KOMP-EKS-SLUIT`, `KOMP-EKS-SKAKEL` | :389, :402 |
| `KOMP-LEEG-TITEL`, `KOMP-LEEG-TEKS` | :340, :341 |
| `KOMP-INDIEN-H3`, `KOMP-INDIEN-TEKS`, `KOMP-INDIEN-KNOP` | :413, :415, :421 |

**Copy debt, admin screens** (no English in the design; the key names the meaning):

| Key | Meaning |
|---|---|
| `KOMP-ADMIN-JAAR`, `-UITGAWE`, `-BESKRYWING`, `-INSKRYWING`, `-NAASWENNER` | year; edition; description; entry note; runner-up note |
| `KOMP-ADMIN-KAT`, `-IKOON`, `-IKOON-FEATHER`, `-IKOON-BOOK`, `-IKOON-PEN`, `-WOORDLIMIET`, `-RIGLYNE` | category; icon and its three options; word limit; guidelines, one per line |
| `KOMP-ADMIN-GALA-NAAM`, `-GALA-DATUM`, `-GALA-DEURE`, `-GALA-PLEK`, `-GALA-DRAG`, `-GALA-PROGRAM` | gala name, date, doors, venue, dress, programme (one item per line) |
| `KOMP-ADMIN-KAARTJIE`, `-PRYS` | ticket label; ticket price, fictional until payments exist |
| `KOMP-ADMIN-HOOGTEPUNT`, `-FOTOS` | gala highlight; gala photos |
| `KOMP-ADMIN-ORGANISEERDER`, `-STREEK`, `-PRYS-EKSTERN`, `-FOOI`, `-GENRE`, `-NOTA` | organiser; region; prize; entry fee (blank means free); genre; note |
| `KOMP-ADMIN-MEDIA-KIES`, `-MEDIA-VERWYDER` | Choose and Remove on the media inputs (shared with InkPols) |

**`KOMP-GIDS-TEKS` isn't the LET WEL text.** Page 59192's disclaimer is removed with the body and is not used in this build (ruled).

## 12. Docs to update at build

`docs/migration-data.md`, `docs/migration-runbook.md` (step list, Verify table, never `--force` on live), `docs/prod-menu-mapping.md` rows 18 and 21, `docs/afrikaans-terms.md` (new terms; correct the `kadens` row), `_bmad-output/planning-artifacts/epics.md` Epic 12B (competitions don't reuse the monthly machinery).

## 12a. Every photo of a gala year (owner, 2026-11-02)

`/kompetisies/` shows four photos a year, then **Sien alles** (`ink-link--ink`, audit-id `competitions-gala-past-all`, map `divergence`) to `/kompetisies/gala/<jaar>/`. That page is code: rewrite rule and route in `Competitions/GalaPage.php` (the `kompetisies` Page with `ink_gala_jaar`; template `kompetisies-gala.html`, registered in `SystemPages::ENDPOINT_TEMPLATES`), pattern `kompetisies-gala.php`, block `ink/kompetisies-gala-fotos`. Recipes only: band, container, eyebrow, `ink-heading-h1-index` (copy debt `KOMP-GALA-ALLES-H1`), back link `ink-nav-link--footer`, `ink-photo`, `ink-pager`. 24 per `?bladsy=N`; real pager links without JS; `kompetisies.js` loads the next batch near the end. Lazy images in the fixed 3/2 box, so nothing moves. A year without photos, or a page past the last, is a 404. The 2025 Fotos Page also holds two video clips; they render as `<video>` in the same box.

## 13. Questions for owner

**Q1 (resolved 2026-11-02: every photo; see §12a). How many photos per gala year?** The ruling says the old Fotos Pages feed the gallery. They hold up to 656 photos a year; the design shows two to four. Searched `docs/`, `_bmad-output/`, the code and `owner-rulings.md`: no number.
- **Resolved:** all of them, read from the Fotos Pages by the migration; no number in any file.
- **Does not block the build.**
