# 04 InkPols: build spec (N2, N3; contract for N6)

**Status:** planned, awaiting recipe-gap ratification. **Treatment:** A. No values here: every style value comes from captures (R4/R5). **Map:** `../maps/inkpols.csv`. **Requirements:** `../inkpols.md` (final).

## 1. What exists today (read, not grepped)

| Item | Finding |
|---|---|
| CSS for `ink-inkpols*` | **The fidelity log is out of date.** 20 rules exist in `theme.json` `styles.css` (parsed in full; none in `assets/css/*`, `style.css` or `functions.php`). They style the old year-section layout. Delete them all at build; nothing in them is reusable. |
| `InkPols/Archive.php` | Year sections with card list, date, volume. Replaced. |
| `InkPols/SingleIssue.php` | `ink/inkpols-besonderhede`: cover, date, volume, teaser. Replaced by bar, contents and nav. |
| `InkPols/Viewer.php` | `ink/inkpols-leser`: Real3D via shortcode; a direct-PDF fallback link when the plugin is off. Real3D Lite 4.19.2 is active on dev and its assets load on the issue page. Keep the mechanism, wrap in the frame. |
| `InkPols/Issue.php` | Reads date, volume, cover, PDF, teaser only. Titel and the six featured keys exist in `FieldSets` but no reader. |
| `patterns/inkpols.php`, `patterns/reading-inkpols.php` | Rewritten. Lovable has no `post-content` section, so the single's body section goes. |
| Migration | Built, no change. Seven dummy issues are published on dev (`Toetstitel: …`), which is expected. |

## 2. Section spine

| Lovable | INK | Files |
|---|---|---|
| `Magazine.tsx:31` archive hero + current issue | `ink/inkpols-argief` hero, calling the shared renderer | `templates/archive-inkpols_uitgawe.html` (no change) → `patterns/inkpols.php` (full-width, no padding, the block emits its own bands) → `InkPols/Archive.php` → `InkPols/CurrentIssue.php` (new) |
| `Magazine.tsx:60` past issues, chips, grid | same block | `InkPols/Archive.php` + `assets/js/inkpols-argief.js` (new) |
| `MagazineIssue.tsx:22` top bar | `ink/inkpols-besonderhede` (bar) | `templates/single-inkpols_uitgawe.html` (no change) → `patterns/reading-inkpols.php` → `InkPols/SingleIssue.php` |
| `MagazineIssue.tsx:37` reader | `ink/inkpols-leser` | `InkPols/Viewer.php` |
| `MagazineIssue.tsx:54` contents + nav | new block `ink/inkpols-inhoud` | `InkPols/SingleIssue.php` (second render method) |
| `MagazineSection.tsx` home | N6, specced in `03-tuis` | shared renderer, §3 |

Sections render in that order; the header and footer come from the template unchanged.

## 3. Shared renderer contract (N2 and N6)

**Class:** `Ink\InkPols\CurrentIssue`. **Facade:** other modules and the theme reach it only through `Ink\InkPols\Api`.

```
Api::latestPublishedIssue(): ?Issue     // newest `datum` (ink_inkpols_issue_date), status publish; null when none
Api::publishedIssues(): list<Issue>     // newest `datum` first; cap Archive::MAX_ISSUES
CurrentIssue::toHtml( Issue $issue, string $context ): string   // $context: 'archive' | 'home'. Pure: Terms + escaping
```

**`Issue` read-model additions** (from `FieldSets` constants; `volume` is dropped, no consumer remains):

| Property / method | Source |
|---|---|
| `title` (the naam) | post title |
| `titel` | `INKPOLS_TITEL` |
| `teaser` (the beskrywing) | `INKPOLS_TEASER` |
| `items(): list<array{kind:string, title:string, writer:string}>` | nine glans keys (`ink_inkpols_glans_{1,2,3}_{titel,skrywer,tipe}`); `tipe` = the editor's free text, shown as the kind label (no Terms label, nothing hardcoded). Order is glans-1, 2, 3 (owner ruling 2026-11-03, replacing Storie, Gedig, Artikel). A blank-titel item is left out |
| `permalink()` | `get_permalink` |

**The renderer returns only the grid** (`magazine-current` / `magazine-grid`) and never a section, container or empty shell. The caller owns the band. **The home block returns `''` when `latestPublishedIssue()` is null**, which hides the section (`inkpols.md`).

| Per context | `archive` | `home` |
|---|---|---|
| Id prefix: grid / body / eyebrow / h2 / text | `magazine-current`, `-body`, `-eyebrow`, `-h2`, `-text` | `magazine-grid`, `magazine-body`, `magazine-eyebrow`, `magazine-h2`, `magazine-text` |
| Cover wrapper | `<a>` to permalink, `magazine-current-cover-link` | `<div>` not linked, `magazine-cover-col` |
| Cover id, recipe | `magazine-current-cover`, `ink-cover ink-cover--tilt` | `magazine-cover`, same + `ink-cover--home` |
| Eyebrow copy | copy-debt `INKPOLS-HUIDIGE` · naam | `InkPols` · naam |
| Label above list | none | `magazine-inside-label`, In hierdie uitgawe |
| List ids | wrap `magazine-current-highlights-wrap`; `magazine-current-highlights`, `-{n}`, `-{n}-kind|title|author` | `magazine-inside-list`, `magazine-inside-item-{n}`, `-{n}-kind|title|author` |
| List modifier | none | `ink-inkpols__lys--home` (Lovable's home list differs in spacing and writer size) |
| Actions | `magazine-current-actions` + read | `magazine-actions` + read + `magazine-past-link` (Alle uitgawes → `/inkpols/`) |
| Read button | `magazine-current-btn-read` | `magazine-btn-read`, same recipe, Lees aanlyn |

`magazine-btn-pdf` and `magazine-current-btn-pdf` are never emitted. The past grid uses the third list variant, `ink-inkpols__lys--compact`, from the same list method (`CurrentIssue::listHtml( Issue, string $idPrefix, string $variant )`).

**Styles** live in `assets/css/inkpols.css`, layout only. **Its gate must include the front page**, §7.

**Test:** `CurrentIssue::toHtml` unit tests for both contexts (ids, no PDF markup, three items in order), null-issue hide, and one production-path test through `Api`.

## 4. Behaviour

- **Past issues:** all published issues except the newest, newest first. **Hidden when there is one issue** (`magazine-past` not emitted). With no issue at all the archive shows the hero text only; the current `Geen Uitgawes gevind nie` empty state stays as `ink-card--empty` because `inkpols.md` does not cover this page's empty case.
- **Year chips:** Alles first and active, then each year among past issues, newest first. Client-side as Lovable: every past card is in the HTML with `data-jaar`; `inkpols-argief.js` toggles `hidden` and `aria-pressed`. Without JS all stay visible.
- **Year source:** `Issue::year()` (from `datum`).
- **Prev/next:** Vorige is the next-older published issue, left; Volgende the next-newer, right. On the oldest, `magazine-issue-nav-spacer` holds the left slot. Label is the word only, per `inkpols.md`.
- **Viewer:** `[real3dflipbook]` inside `ink-reader-frame`. The PDF-link fallback stays for plugin-off (FR-57 a11y); it is a read link, not a download button.
- **Slugs and redirects:** old issues: done by the migration step. New issues: WordPress derives the slug from the naam (§4a). No routing change.

## 4a. Adding and editing issues on the site

**After go-live an editor adds every issue in admin; the CSV is finished.** Read against `FieldSets` and `InkPols/Migration.php`: every value the page needs has a field (post title = naam; `datum`, cover, PDF, teaser, titel, six featured cells), and `register_post_meta` and the save path work. Build these gaps; `inkpols.md` §4 has the rules:

| Gap found | Fix at build |
|---|---|
| Cover and PDF are number inputs: the editor has to look up an attachment id | Change both to the shared `media` input (`05-kompetisies` §3.4) |
| The migration calls `set_post_thumbnail()`; the admin path doesn't, so a new issue has no social image | On save, set the featured image from the cover |
| "Required to publish" is enforced only by the migration | New `InkPols/PublishGuard.php`, `save_post` after `FieldSets::save()`: a publish with a required field blank goes back to draft with an admin notice listing what's missing, in Afrikaans (`INKPOLS-ADMIN-ONVOLLEDIG`, debt) |
| The block editor saves the box in a second request, and the single has no body section | Remove `editor` and `excerpt` from this type's `supports`: WordPress then gives the classic screen, one request, and no misleading body |
| Order follows publish date, so publishing an older issue late would make it current | §3 now orders by `datum`. Add `datum` to the required fields |
| `ink_inkpols_volume` is still in the box; there is no issue number | Remove the field from `FieldSets` and `Issue`. Update `FieldSetsTest`'s count |

**No gap:** a title of `Julie/Augustus 2026` gives slug `julie-augustus-2026` (`sanitize_title_with_dashes()` turns `/` into `-` on save, `formatting.php:2305`). The flipbook takes the PDF's URL at render (`Viewer::shortcodeFor()`), so a new issue needs no plugin set-up.

**Found in the browser, 2026-11-02 (fixed):** `PublishGuard` was on `save_post_{type}`, which core fires before the generic `save_post` where `FieldSets` writes the box. The guard read the previous save's meta, so a new issue needed three saves to publish. It is now on `save_post` at priority 20. Browser run as a temporary administrator: a publish with titel, storie and artikel blank stays a draft and names exactly those; the full publish is current on `/inkpols/`, home and its single in one save; an edit shows at once; the media picker fills cover and PDF and sets the featured image.

**Test:** add one issue through the admin screen on dev, on a real browser session; confirm it's the current issue on `/inkpols/` and home with no CLI step. Publishing with a blank field stays a draft.

## 5. Element table: archive

| audit-id | Lovable | recipe | data source | copy | status / note |
|---|---|---|---|---|---|
| `magazine-archive-hero` | Magazine.tsx:31 | `ink-band ink-band--border-b` + `ink-band--step` | — | — | planned |
| `magazine-archive-container` | Magazine.tsx:32 | `ink-container` | — | — | planned |
| `magazine-archive-eyebrow` | Magazine.tsx:33 | `ink-eyebrow ink-eyebrow--card` | Terms `inkpols` | InkPols (`inkpols.md`) | planned |
| `magazine-archive-h1` | Magazine.tsx:34 | `ink-heading-h1-index` | static | Die Argief (`inkpols.md`) | planned |
| `magazine-archive-text` | Magazine.tsx:35 | text (page sheet, tokens) | static | Al ons uitgawes, gratis beskikbaar om aanlyn te lees (`inkpols.md`) | planned |
| `magazine-current` | Magazine.tsx:37 | layout (page sheet) | derived: newest published | — | planned |
| `magazine-current-cover-link` | Magazine.tsx:38 | layout (page sheet) | derived: permalink | — | planned |
| `magazine-current-cover` | Magazine.tsx:39 | RECIPE-GAP GAP-03-07 | meta `ink_inkpols_cover_id` | alt = naam | planned: A4 ratio: accepted, permitted #6; real image: permitted #4 |
| `magazine-current-body` | Magazine.tsx:42 | layout (page sheet) | — | — | planned |
| `magazine-current-eyebrow` | Magazine.tsx:43 | `ink-eyebrow ink-eyebrow--card` | post title (naam) | copy-debt INKPOLS-HUIDIGE + naam | planned: issue number and season replaced by naam: permitted #6 |
| `magazine-current-h2` | Magazine.tsx:44 | `ink-heading-h2-section` | meta `ink_inkpols_titel` | editor's text | planned |
| `magazine-current-text` | Magazine.tsx:45 | text (page sheet, tokens) | meta `ink_inkpols_teaser` | editor's text | planned |
| `magazine-current-highlights-wrap` | Magazine.tsx:46 | layout (page sheet) | — | — | planned |
| `magazine-current-highlights` | Magazine.tsx:10 | layout (page sheet) | — | — | planned |
| `magazine-current-highlights-{n}` | Magazine.tsx:12 | layout (page sheet) | derived | — | planned |
| `magazine-current-highlights-{n}-kind` | Magazine.tsx:13 | RECIPE-GAP RG-IP-08 | derived | meta `ink_inkpols_glans_{1,2,3}_tipe` | planned |
| `magazine-current-highlights-{n}-title` | Magazine.tsx:14 | text (page sheet, tokens) | meta `ink_inkpols_glans_{1,2,3}_titel` | editor's text | planned |
| `magazine-current-highlights-{n}-author` | Magazine.tsx:15 | text (page sheet, tokens) | meta `ink_inkpols_glans_{1,2,3}_skrywer` | editor's text | planned |
| `magazine-current-actions` | Magazine.tsx:47 | layout (page sheet) | — | — | planned |
| `magazine-current-btn-read` | Magazine.tsx:48 | `ink-btn ink-btn--lg ink-btn--primary` | derived: permalink | Lees aanlyn (`inkpols.md`) | planned |
| `magazine-current-btn-read-icon` | Magazine.tsx:49 | svg in parent recipe | — | — | planned |
| `magazine-current-btn-pdf` | Magazine.tsx:51 | none | — | — | divergence: accepted: permitted #6, no download buttons (`inkpols.md` §2). Not built |
| `magazine-current-btn-pdf-icon` | Magazine.tsx:52 | none | — | — | divergence: accepted: permitted #6, no download buttons (`inkpols.md` §2). Not built |
| `magazine-past` | Magazine.tsx:60 | `ink-band` + `ink-band--step` | hidden with one issue (`inkpols.md`) | — | planned |
| `magazine-past-container` | Magazine.tsx:61 | `ink-container` | — | — | planned |
| `magazine-past-head` | Magazine.tsx:62 | layout (page sheet) | — | — | planned |
| `magazine-past-h2` | Magazine.tsx:63 | `ink-heading-h2-section` | static | Alle uitgawes (`inkpols.md`) | planned |
| `magazine-past-filters` | Magazine.tsx:64 | layout (page sheet) | — | — | planned |
| `magazine-past-filter-all` | Magazine.tsx:66 | `ink-chip` | static | Alles (`inkpols.md`) | planned |
| `magazine-past-filter-2025` | Magazine.tsx:66 | `ink-chip` | derived: newest year among past issues | year | planned: positional join key: stamped on the first year chip whatever its year |
| `magazine-past-filter-2024` | Magazine.tsx:66 | `ink-chip` | derived: next year among past issues | year | planned: positional join key: stamped on the second year chip; later chips get no id |
| `magazine-past-grid` | Magazine.tsx:74 | layout (page sheet) | — | — | planned |
| `magazine-past-{p}` | Magazine.tsx:76 | layout (page sheet) | derived: published, newest first, minus current | — | planned |
| `magazine-past-{p}-cover-link` | Magazine.tsx:77 | layout (page sheet) | derived: permalink | — | planned |
| `magazine-past-{p}-cover` | Magazine.tsx:78 | RECIPE-GAP RG-IP-06 | meta `ink_inkpols_cover_id` | alt = naam | planned: A4 ratio: accepted, permitted #6 |
| `magazine-past-{p}-eyebrow` | Magazine.tsx:81 | RECIPE-GAP RG-IP-08 | post title (naam) | editor's text | planned: permitted #6 |
| `magazine-past-{p}-title` | Magazine.tsx:82 | `ink-heading-h3` | meta `ink_inkpols_titel` | editor's text | planned |
| `magazine-past-{p}-title-link` | Magazine.tsx:83 | layout (page sheet) | derived: permalink | — | planned |
| `magazine-past-{p}-highlights-wrap` | Magazine.tsx:85 | layout (page sheet) | — | — | planned |
| `magazine-past-{p}-highlights` | Magazine.tsx:10 | layout (page sheet) | — | — | planned |
| `magazine-past-{p}-highlights-{n}` | Magazine.tsx:12 | layout (page sheet) | derived | — | planned |
| `magazine-past-{p}-highlights-{n}-kind` | Magazine.tsx:13 | RECIPE-GAP RG-IP-08 | derived | Terms | planned |
| `magazine-past-{p}-highlights-{n}-title` | Magazine.tsx:14 | text (page sheet, tokens) | meta (as current) | editor's text | planned |
| `magazine-past-{p}-highlights-{n}-author` | Magazine.tsx:15 | text (page sheet, tokens) | meta (as current) | editor's text | planned |
| `magazine-past-{p}-actions` | Magazine.tsx:86 | layout (page sheet) | — | — | planned |
| `magazine-past-{p}-btn-read` | Magazine.tsx:87 | `ink-btn ink-btn--sm ink-btn--primary` | derived: permalink | Lees aanlyn (`inkpols.md`) | planned |
| `magazine-past-{p}-btn-read-icon` | Magazine.tsx:88 | svg in parent recipe | — | — | planned |
| `magazine-past-{p}-btn-pdf` | Magazine.tsx:90 | none | — | — | divergence: accepted: permitted #6, no download buttons (`inkpols.md` §2). Not built |
| `magazine-past-{p}-btn-pdf-icon` | Magazine.tsx:91 | none | — | — | divergence: accepted: permitted #6, no download buttons (`inkpols.md` §2). Not built |
| `magazine-past-5 (and -5-*)` | — | same as magazine-past-{p} | published data | editor's text | scope: real-data repeater (taxonomy SCOPE): dev has six past issues, Lovable five |

## 6. Element table: issue

Lovable pairing for capture: `/magazine/issue-14` ↔ `/inkpols/julie-augustus-2026/` (newest), `issue-13` ↔ `/inkpols/mei-junie-2026/` (middle), `issue-9` ↔ `/inkpols/november-2025/` (oldest).

| audit-id | Lovable | recipe | data source | copy | status / note |
|---|---|---|---|---|---|
| `magazine-issue-bar` | MagazineIssue.tsx:22 | `ink-band ink-band--flush ink-band--border-b` | — | — | planned |
| `magazine-issue-bar-inner` | MagazineIssue.tsx:23 | `ink-container` + layout (page sheet) | — | — | planned |
| `magazine-issue-back` | MagazineIssue.tsx:24 | `ink-nav-link--footer` | archive URL | copy-debt INKPOLS-TERUG | planned |
| `magazine-issue-back-icon` | MagazineIssue.tsx:25 | svg in parent recipe | — | — | planned |
| `magazine-issue-title-col` | MagazineIssue.tsx:27 | layout (page sheet) | — | — | planned |
| `magazine-issue-eyebrow` | MagazineIssue.tsx:28 | RECIPE-GAP RG-IP-08 | post title (naam) | editor's text | planned: permitted #6 |
| `magazine-issue-h1` | MagazineIssue.tsx:29 | RECIPE-GAP RG-IP-05 | meta `ink_inkpols_titel` | editor's text | planned |
| `magazine-issue-btn-pdf` | MagazineIssue.tsx:31 | none | — | — | divergence: accepted: permitted #6, no download buttons (`inkpols.md` §2). Not built |
| `magazine-issue-btn-pdf-icon` | MagazineIssue.tsx:32 | none | — | — | divergence: accepted: permitted #6, no download buttons (`inkpols.md` §2). Not built |
| `magazine-issue-reader` | MagazineIssue.tsx:37 | `ink-band ink-band--cream ink-band--border-b` + `ink-band--step` | — | — | planned |
| `magazine-issue-reader-container` | MagazineIssue.tsx:38 | `ink-container` | — | — | planned |
| `magazine-issue-reader-frame` | MagazineIssue.tsx:39 | RECIPE-GAP RG-IP-11 | — | — | planned |
| `magazine-issue-reader-embed` | MagazineIssue.tsx:41 | plugin output, not measured | meta `ink_inkpols_pdf_id` | plugin strings | planned: Lovable branch not rendered in its capture; Real3D subtree excluded from the diff |
| `magazine-issue-reader-placeholder` | MagazineIssue.tsx:43 | none | — | — | divergence: accepted: permitted #6 (`inkpols.md`: Real3D on the single). Lovable shows this only while no flipbook is connected |
| `magazine-issue-reader-cover` | MagazineIssue.tsx:44 | none | — | — | divergence: accepted: permitted #6 (`inkpols.md`: Real3D on the single). Lovable shows this only while no flipbook is connected |
| `magazine-issue-reader-text` | MagazineIssue.tsx:45 | none | — | — | divergence: accepted: permitted #6 (`inkpols.md`: Real3D on the single). Lovable shows this only while no flipbook is connected |
| `magazine-issue-reader-text-icon` | MagazineIssue.tsx:46 | none | — | — | divergence: accepted: permitted #6 (`inkpols.md`: Real3D on the single). Lovable shows this only while no flipbook is connected |
| `magazine-issue-contents` | MagazineIssue.tsx:54 | `ink-band` + `ink-band--step` | — | — | planned |
| `magazine-issue-contents-container` | MagazineIssue.tsx:55 | `ink-container` + layout (page sheet) | — | — | planned |
| `magazine-issue-contents-label` | MagazineIssue.tsx:56 | RECIPE-GAP GAP-03-04 muted | static | In hierdie uitgawe (`inkpols.md`) | planned |
| `magazine-issue-contents-grid` | MagazineIssue.tsx:57 | layout (page sheet) | — | — | planned |
| `magazine-issue-item-{n}` | MagazineIssue.tsx:59 | RECIPE-GAP RG-IP-12 | derived | — | planned |
| `magazine-issue-item-{n}-kind` | MagazineIssue.tsx:60 | RECIPE-GAP RG-IP-08 | derived | Terms | planned |
| `magazine-issue-item-{n}-title` | MagazineIssue.tsx:61 | `ink-heading-h4 ink-heading--regular` | meta | editor's text | planned |
| `magazine-issue-item-{n}-author` | MagazineIssue.tsx:62 | text (page sheet, tokens) | meta | editor's text | planned |
| `magazine-issue-nav` | MagazineIssue.tsx:67 | layout (page sheet) | — | — | planned |
| `magazine-issue-nav-older` | MagazineIssue.tsx:69 | `ink-nav-link--footer` | derived: next-older published | Vorige (`inkpols.md`) | planned |
| `magazine-issue-nav-older-icon` | MagazineIssue.tsx:70 | svg in parent recipe | — | — | planned |
| `magazine-issue-nav-spacer` | MagazineIssue.tsx:72 | layout (page sheet) | — | — | planned |
| `magazine-issue-nav-newer` | MagazineIssue.tsx:74 | `ink-nav-link--footer` | derived: next-newer published | Volgende (`inkpols.md`) | planned |
| `magazine-issue-nav-newer-icon` | MagazineIssue.tsx:75 | svg in parent recipe | — | — | planned |

## 7. Page sheet and scripts

| Asset | Gate | Consumers |
|---|---|---|
| `assets/css/inkpols.css` (new, layout + text only) | `is_post_type_archive('inkpols_uitgawe') \|\| is_singular('inkpols_uitgawe') \|\| is_front_page()` | archive, single, home section (N6) |
| `assets/js/inkpols-argief.js` (new) | `is_post_type_archive('inkpols_uitgawe')` | year chips |
| `theme.json` `styles.css` | delete all 20 `.ink-inkpols*` rules | none left |

## 8. Recipe gaps

Rows are in `recipe-gaps.csv`; values come from captures after ratification. **Merged:** the tilted cover is `GAP-03-07` and the xs label is the muted use of `GAP-03-04` (`03-tuis`); this page adds its archive size to `GAP-03-07`.

| gap | element | proposed class |
|---|---|---|
| RG-IP-05 | h1, compact bar title (truncates) | `ink-heading-h1-bar` |
| RG-IP-06 | magazine cover, grid size with hover lift | `ink-cover` |
| RG-IP-08 | xs sentence-case eyebrow | `ink-eyebrow--card-xs` |
| RG-IP-11 | flipbook frame with responsive ratio | `ink-reader-frame` |
| RG-IP-12 | item with coloured top rule | `ink-accent-block--top` |

## 9. URL and slug check (dev, read-only, 2026-11-01)

| URL | Result |
|---|---|
| `/inkpols/` | rewrite `inkpols/?$` → CPT archive; 200. Page 65591 is in the trash as `inkpols__trashed` |
| `/inkpols/julie-augustus-2026/` | 200; the seven issue slugs are published |
| Old `/inkpols-2025-11/` … | seven Pages in the trash; each issue carries `ink_migration_source_url` |

No clash. Covers checked on dev are all A4 ratio (7 of 7, height/width 1.415 to 1.416).

## 10. Test data

**No new fixture.** The seven dummy issues from `inkpols-uitgawes.csv` cover the archive, all three issue states and two year chips.

- **Year chips:** dev has past years 2026 and 2025; Lovable has 2025 and 2024, and its ids carry the year. The INK chips take Lovable's ids by position (see the table), or the gate reports `MISSING` for a difference that is only data.
- **Sixth past issue:** rows `magazine-past-5*` have no Lovable pair; recorded as `scope`.
- **States not on dev** (zero issues, one issue): cover by unit test. Do not unpublish issues to measure them. Checked in Chromium on 2026-11-02 with a temporary request-only dev mu-plugin (`?ink_toets_uitgawes=0|1` limited the issue query; no data changed; removed after): zero shows the hero and the empty card and hides the home section; one shows the current issue without past issues; no sideways scroll at 390 or 1440.

## 11. Accepted divergences (handoff §3)

| Divergence | Permitted item |
|---|---|
| A4 covers instead of 3:4; disclose in code | #6 |
| No download or PDF buttons | #6 |
| Naam instead of issue number and season | #6 |
| Flipbook instead of Lovable's unconnected placeholder | #6, `inkpols.md` §2 |
| Real covers, not `magazine-cover.jpg` | #4 |
| Afrikaans copy | #3 |

## 12. Copy

- **Owner-supplied (`inkpols.md`):** InkPols, Die Argief, Al ons uitgawes…, Lees aanlyn, Alle uitgawes, In hierdie uitgawe, Vorige, Volgende, Alles. Add those not in `Terms` to the registry.
- **Copy debt** (add to `afrikaans-translation-sheet.md` and `afrikaans-copy-worklist.md`, render as `[NEEDS HUMAN AFRIKAANS] <KEY>`):

| Key | Lovable string it stands for | Where |
|---|---|---|
| `INKPOLS-HUIDIGE` | "Current issue" | `Magazine.tsx:43` |
| `INKPOLS-TERUG` | "Back to Archive" | `MagazineIssue.tsx:25` |

## 13. Questions for owner

None. Item order is glans-1, 2, 3 (owner ruling 2026-11-03, `owner-rulings.md`); it replaces the earlier Storie, Gedig, Artikel order that followed Lovable.

## 14. Docs to update at build

`docs/migration-runbook.md` (never `migrate-inkpols --force` on the live site), `docs/migration-plan.md` InkPols section (`inkpols.md` §1), `findings` or log line that says no CSS exists, `fidelity-remediation/maps/inkpols.csv` (superseded by this map).
