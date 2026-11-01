# Build spec 02: reading-page winner aside (N7)

**Status:** planned, not ratified. **Date:** 2026-11-01. **Map:** `../maps/lees-wenner.csv` (5 ids). **Gap:** `GAP-02-01`. **Depends on:** spec 01 (link target).
This spec holds no style values (R4, R5). Recipes are joins for the capture to check.

## 1. Files read

**Lovable:** `src/pages/ReadStory.tsx:1-209`, `src/data/awards.ts`.
**INK:** `patterns/reading-storie.php`, `reading-gedig.php` (:60-119), `reading-artikel.php`; `functions.php:296-314` (reading.css gate) and `:2130-2155`; `Challenges/{ModeratorFeedback,WinnerBanner,Placements,Pools,SinglePage}.php`; `Content/ChallengeResultTitle.php`.
**Not read in full:** `reading.css` and `reading-gedig.php:1-59` and `:120` on. No claim here rests on them.

## 2. Section spine

| Lovable | INK |
|---|---|
| `reading-header-inner`: badge, title, byline, **`reading-award`**, hint (`ReadStory.tsx:144-201`) | Same group in all three patterns: `.ink-lees-intro` (storie, artikel), `.ink-gedig-intro` (gedig) |

Insert `<!-- wp:ink/lees-wenner /-->` after the `ink-lees-byline` group and before the `is_user_logged_in()` hint branch:
- `patterns/reading-storie.php` after line 94
- `patterns/reading-gedig.php` after line 105
- `patterns/reading-artikel.php` after line 85

**Renderer:** new `Challenges/ReadingAward.php`, block `ink/lees-wenner`, thin `render()` over pure `toHtml()`. It returns `''` for a work with no placement.

**Not reused:** `WinnerBanner::forPost()` and `ink_foundation_wenner_banier()`. README N7 names them, but their markup (a star and a label in a span) isn't this design. This aside uses the same `Placements` reads and gives `ModeratorFeedback::feedbackFor()` its production consumer. Leave `WinnerBanner` alone.

**Audit ids:** the same five on all three post types; Lovable's `ReadStory.tsx` is one shared component. `reading-artikel.php` has no `reading-header` ids today, so its aside can be measured but its neighbours can't. That file's ids are outside this package.

## 3. Element table

| Audit id | Recipe | Data source | Copy |
|---|---|---|---|
| `reading-award` | **RECIPE-GAP GAP-02-01** | Present only when `Placements::placementFor( post )` is valid | |
| `reading-award-link` | eyebrow card: `ink-eyebrow ink-eyebrow--card ink-eyebrow--gold` on `<a>`; icon gap and hover in `reading.css` | Round's `/uitslae/` URL (spec 01) | `LEES-WENNER-ETIKET` |
| `reading-award-icon` | Icon, size from capture | | |
| `reading-award-citation` | Page text in `reading.css` | First item of `ModeratorFeedback::feedbackFor( post )`; absent if empty (D6) | Moderator's text |
| `reading-award-citation-by` | `ink-stat-label`; absent with no citation | | `UITSLAE-KRITIEK-DEUR` (spec 01) |

## 4. Data

| Field | Source | Status |
|---|---|---|
| Position | `ink_entry_placement` | **Importer**: 0 rows on dev |
| Grade (gedig) | `ink_entry_gradering`; Meester shows as Goud (D11) | **Importer + Gradering fix** |
| Round | The post's `uitdagingsrondte` term, slug `ChallengeRound::slugFor()` | Exists: e.g. `uitdaging-68791`. Needs a new reverse lookup from term to round id. Use the importer's record if one exists |
| Round month | `ChallengeResultTitle::monthLabel( round post_date )` | Derived. `Cadence` needs a deadline; no round has one |
| Citation, opt-in | `ink_moderator_terugvoer`, `ink_wys_moderator_terugvoer` | Exists; 0 comments and 0 opt-ins on dev |

**Page caching and privacy:** `feedbackFor()` is the only read. Do not call `commentsFor()`, which is ungated.

**A win with no resolvable round** (old data): render the label without a link? Lovable has no such case. Decision: no aside until the round resolves, because the aside's purpose is the link.

## 5. Copy

`LEES-WENNER-ETIKET` = `{Placements::placementLabel}` · `{type}` · `{Maand jaar}-uitdaging`

| Part | Text | Source |
|---|---|---|
| Label | `algehele wenner` / `wenner`, verbatim | Terms, D11 |
| Type | Gedig: `Goud gedigte` (Terms grade + lower-case Terms plural). Others: Terms `storie_plural`, `artikel_plural` | README N7 "Goud gedigte" |
| Period | `%s-uitdaging` | `CurrentChallenge.php:255` |

No copy debt in this package.

## 6. URL and slug check

No new URL. Link target: spec 01 §7. Existing URLs on dev, e.g. `/gedig/grou-gisters-goud/`, `/storie/ontdekking/`, `/artikel/pandora-jou-indringer-brons/`.

## 7. Load scope

Layout in the existing `assets/css/reading.css`, gate `is_singular( array( 'gedig', 'storie', 'artikel' ) )` (`functions.php:301`). That covers every consumer. No new sheet. Biblioteek and opleiding reading patterns aren't consumers.

## 8. Accepted divergences

| Divergence | Handoff list |
|---|---|
| Label wording, not "1st Place" | Item 2 (D11) |
| Afrikaans copy | Item 3 |
| Citation block absent without opt-in | D6 |

**Noticed outside this package, Lovable wins, for whoever re-measures the reading pages:**
- Lovable shows the hint to everyone; INK only when logged in.
- Lovable's byline shows read time; INK's shows the date.

## 9. Test fixture for dev

Uses spec 01 §11 (round 68679). Create it once.

| Lovable capture | INK URL | Post |
|---|---|---|
| `read-s2-gedig` | `/gedig/` permalink of 68070 | Goud, position 1 |
| `read-s3-storie` | `/storie/die-laaste-koevert/` (confirm permalink) | 68211, position 1 |
| Artikel (no Lovable-specific capture; same component) | Permalink of 68214 | Position 1 |

Each author: `ink_wys_moderator_terugvoer` = 1, plus one comment from post 68266. **D6 check:** turn opt-in off on one and confirm the citation and attribution go while the link stays. **No-placement check:** a post with none shows no aside, e.g. 68206 until it's placed. Add production-path unit tests (README §6, "capability without a consumer").

Measure at 390, 768, 1440, logged out, then logged in as `fidelity-capture` so the hint appears. Provisional until the importer lands.

## Questions for owner

None.
