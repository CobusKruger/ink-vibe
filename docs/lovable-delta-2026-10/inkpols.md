# WP4 InkPols: specification

**Status:** fully specified by the owner, 2026-10-26. No open decisions. The migration step (§3) is built (2026-10-29); the archive, single and home section (N2, N3, N6) are not.
**Lovable reference:**
- `src/pages/Magazine.tsx` (archive)
- `src/pages/MagazineIssue.tsx` (issue)
- `src/components/home/MagazineSection.tsx` (home)
- `src/data/magazine.ts`

**Where this sits:** this is WP4 of `README.md` §3.3. It resolves README §4 D9.

## 1. Prod today (read from the prepared prod dump of 2026-10-25)

- **`/inkpols/` is one Page** (ID 65591). It holds the latest issue's PDF block, followed by a hand-built "Vorige Uitgawes" grid of cover images that link to the issue pages.
- **Seven issues.** Each is a root-level Page holding a PDF Embedder block:

  | Page | Old URL |
  |---|---|
  | 65687 | `/inkpols-2025-11/` |
  | 65914 | `/inkpols-2025-12/` |
  | 66734 | `/inkpols-2026-01/` |
  | 66718 | `/inkpols-2026-02/` |
  | 67245 | `/inkpols-2026-03/` |
  | 67687 | `/inkpols-2026-05/` |
  | 68227 | `/inkpols-2026-07/` |

- **Real3D Flipbook takes over the PDF Embedder blocks**, so every one of these pages renders a flipbook. Counting only `[real3dflipbook]` shortcodes makes Real3D look unused, but it is in use.
- **Cadence:** monthly from November 2025 to February 2026, then every two months.
- **No structured metadata.**
  - A cover is linked to its issue only by the grid on the index page and by the Yoast OG image.
  - The February PDF was uploaded twice.
  - No nav menu item points at InkPols.
- **The INK migration step `migrate-inkpols` is a no-op on this data.** After a refresh, `/inkpols/` is empty. This is also why the InkPols page was never measured in the fidelity rework (`fidelity-remediation/stage-4-owner-questions.md`, 17-cov).
- **`docs/migration-plan.md` (InkPols section, ~:210-216) is stale.** It assumes prod has an InkPols CPT, but prod uses Pages. Correct it when building.

## 2. Target

**Post type and URLs**
- Use the CPT `inkpols_uitgawe`, which already exists from Epic 13 (`InkPols/Issue.php`, `Content/FieldSets.php:47-51`); adapt its fields.
- The archive is `/inkpols/` (= Lovable `/magazine`); its template `archive-inkpols_uitgawe.html` already exists.
- The single is `/inkpols/<slug>/` (= Lovable `/magazine/:slug`); its template `single-inkpols_uitgawe.html` already exists.

**Required to publish (every issue, imported ones included):**
- **PDF**
- **cover (voorblad):** A4. It is also the social sharing image. A separate social image is undecided; the team may want one later.
- **naam:** the months plus the year, e.g. `Julie/Augustus 2026`. It is the eyebrow wherever the design shows "Issue 14 · Winter 2026", and it **replaces the issue number everywhere**. There is no issue number and no season.
- **titel:** the H2. For imported issues the owner will probably use the cover story.
- **beskrywing**
- **three featured items, exactly one each of Gedig, Storie and Artikel:** a title and a writer each. They are **plain text describing something in the PDF**. They are not linked and not loaded on the site.

**Where issues appear**
- **The latest *published* issue** goes in the home section (`MagazineSection.tsx`) and is the current-issue feature at the top of `/inkpols/`. Past issues follow below it.
- **InkPols is reachable only from its home section.** It is not in the header (README §4.1 D1), and the footer is deferred.

**Reading and download**
- The Real3D flipbook appears **on the single reading page only**. Home and archive show the cover.
- **No download buttons anywhere.** This drops the design's "Download PDF" and "PDF" buttons.

**Layout details**
- **Covers keep the A4 ratio.** The design uses 3:4. Record this as `DIVERGENCE (accepted)` with an in-code disclosure (fidelity R6).
- **Year filter chips** on past issues, defaulting to **"Alles"**. The current `InkPols/Archive.php` renders year *sections* instead.
- **Empty states:**
  - with no issues, the home section is hidden;
  - with only one issue, "past issues" is hidden.

**Slugs and redirects**
- The slug comes from the naam, e.g. `/inkpols/julie-augustus-2026/`. Set it explicitly; do not let WordPress derive it from the title.
- The old root Pages 301 to the new URLs.
- The old `/inkpols/` Page is retired, because the CPT archive owns that URL.

**Afrikaans copy (owner-supplied, final; do not translate further):**

| Lovable | INK |
|---|---|
| "Inkwell Magazine" (eyebrow on `/inkpols/`) | **InkPols** |
| "The Archive" | **Die Argief** |
| "Every issue, published bimonthly. Read online or download the PDF." | **Al ons uitgawes, gratis beskikbaar om aanlyn te lees** |
| "Read Online" | **Lees aanlyn** |
| "Past issues" (home link and archive heading) | **Alle uitgawes** |
| "In this issue" / "Inside this issue" | **In hierdie uitgawe** |
| older/newer issue links | **Vorige** / **Volgende** (as elsewhere on the site) |
| "All" (year filter) | **Alles** |

## 3. Migration

### The input file

The owner fills in `wp-content/plugins/ink-core/migration-data/inkpols-uitgawes.csv`. Its columns are described in `migration-data/README.md`.
- **Prefilled per row:** `slug`, `naam`, `datum`, `pdf`, `voorblad`, `ou_url`.
- **Blank, for the owner:** `titel`, `beskrywing`, and the six featured-item cells.

**This CSV is committed** through a `.gitignore` exception (no personal data), unlike the writer CSVs, which live outside the repo (`INK_MIGRATION_DATA_DIR`).

### The new migration step

A new step replaces the no-op `migrate-inkpols` and creates one issue per CSV row:
- It matches the attachments by upload path.
- It sets the publish date to `datum` (the first month of the issue), so that published order and issue order agree.
- It writes the 301s from `ou_url`.
- It retires the prod `/inkpols/` Page and the seven issue Pages.

**Every issue follows the same required-fields rule**, because every field comes from the committed CSV. There is no exemption for imported issues, and nothing is entered by hand on nuwe-ink, which a refresh would wipe.

### As built (2026-10-29)

`Ink\InkPols\Migration`, still `wp ink migrate-inkpols` and still the `inkpols` step of `migrate-all`. The CSV is found through `Ink\Kernel\MigrationData` (the plugin's `migration-data/` unless `INK_MIGRATION_DATA_DIR` holds a copy).

| CSV | Stored as |
|---|---|
| `naam` | post title (the eyebrow) |
| `slug` | post slug, set explicitly; also `ink_inkpols_source_slug`, the get-or-create marker |
| `datum` | publish date (midnight, site time) and `ink_inkpols_issue_date` |
| `pdf` | `ink_inkpols_pdf_id`: the attachment whose `_wp_attached_file` equals the path (lowest ID if stored twice) |
| `voorblad` | `ink_inkpols_cover_id` and the featured image (the social image), matched the same way |
| `titel` | `ink_inkpols_titel` (the H2) |
| `beskrywing` | `ink_inkpols_teaser` |
| six featured cells | `ink_inkpols_{gedig,storie,artikel}_{titel,skrywer}` |
| `ou_url` | `ink_migration_source_url`, the key `generate-redirects` reads |

The titel and featured-item keys are new `FieldSets` fields (admin box and REST); `Issue`/`Api` do not read them yet: that is N2/N3. `ink_inkpols_volume` is no longer written (there is no issue number).

**Blanks.** A row missing any required field (or whose `pdf`/`voorblad` path matches no attachment) is still created, as a **draft**, and the step prints one warning per draft listing the missing fields. The step still succeeds, so owner-pending blanks never stop `migrate-all`; its summary line counts the drafts. With today's CSV all seven issues are drafts, so `/inkpols/` and the home section stay empty until the owner fills the CSV and the migration runs again. Rows that cannot become an issue at all (invalid slug or `datum`, a repeated slug, the wrong number of cells) are skipped and reported; a missing CSV or missing column fails the step.

**Redirects.** `generate-redirects` (after this step in `migrate-all`) writes `ou_url` → issue permalink for **published** issues only, like every other 301. The redirect serves only on a 404.

**Retirement.** Pages are moved to the trash, never deleted. An old issue Page is trashed once its issue is published, so the 301 takes over at once; while the issue is a draft the old Page stays live and the step counts it as kept. The `/inkpols/` index Page is trashed on every run: the CPT archive owns the URL.

**Re-running.** A completed run is a no-op (`ink_inkpols_csv_migration_done`, a new flag, so the old no-op step's flag does not block it). `--force` reconciles the issues in place by slug marker: title, slug, date, status and meta follow the CSV; no duplicates. On a site that is not refreshed, follow it with `wp ink generate-redirects --force` and `wp ink verify-redirects --fix`.

### Before measuring

None of the four Lovable magazine files has a `data-audit-id` yet. Add them Lovable-first (README §3.2 step B) before anything can be measured. The acceptance target must be a **migrated** issue URL, so that it survives a refresh.
