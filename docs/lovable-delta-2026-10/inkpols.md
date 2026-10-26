# WP4 InkPols: specification

**Status:** fully specified by the owner, 2026-10-26. No open decisions. Not built.
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

⚠ **This CSV is gitignored** (`.gitignore:28`, `*.csv`), as are the other migration-data CSVs. Add a `.gitignore` exception (it holds no personal data) or copy it by hand, or it will not reach another machine.

### The new migration step

A new step replaces the no-op `migrate-inkpols` and creates one issue per CSV row:
- It matches the attachments by upload path.
- It sets the publish date to `datum` (the first month of the issue), so that published order and issue order agree.
- It writes the 301s from `ou_url`.
- It retires the prod `/inkpols/` Page and the seven issue Pages.

**Every issue follows the same required-fields rule**, because every field comes from the committed CSV. There is no exemption for imported issues, and nothing is entered by hand on nuwe-ink, which a refresh would wipe.

### Before measuring

None of the four Lovable magazine files has a `data-audit-id` yet. Add them Lovable-first (README §3.2 step B) before anything can be measured. The acceptance target must be a **migrated** issue URL, so that it survives a refresh.
