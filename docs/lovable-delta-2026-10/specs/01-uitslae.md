# Build spec 01: Uitslae (N1 + E8)

**Status:** planned, not ratified. **Date:** 2026-11-01. **Map:** `../maps/uitslae.csv` (263 ids). **Gaps:** `recipe-gaps.csv`, `GAP-01-01` to `GAP-01-08`.
**Rule for this spec:** it holds no style values (R4, R5). A recipe below is a join to check, chosen because the Lovable element has the same role and classes as the element the recipe was measured on. The capture diff decides. If a recipe drifts, fix it in `primitives.css` or raise a gap; do not restyle on the page (R8).

## 1. Files read

**Lovable:** `src/pages/ChallengeResults.tsx`, `src/data/awards.ts`, `src/components/ui/button.tsx`, `src/App.tsx` (routes).
**INK:** `Challenges/{Placements,WinnersPost,ModeratorFeedback,ResultPostLinker,Pools,Ingestion,ResultsParser,SinglePage,CurrentChallenge,Deadline}.php`, `Migration/LegacyResultsParser.php`, `Content/{FieldSets (uitdaging fields),ChallengeResultTitle}.php`, `templates/single-uitdaging.html`, `patterns/reading-uitdaging.php`, `functions.php` (enqueue pattern), `primitives.css` (classes cited).
**Dev (read-only, 2026-11-01):** rewrite rules, round and result-post data, `wenners.csv` headers and counts.

## 2. Section spine

| # | Lovable section | INK counterpart (all new unless noted) |
|---|---|---|
| Route | `/challenges/:slug/results` | Endpoint `uitslae` on `uitdaging`; `templates/single-uitdaging-uitslae.html` through `single_template_hierarchy`; pattern `patterns/uitslae.php` |
| 1 | `results-hero` :78 | `ink/uitslae {"deel":"kop"}` |
| 2 | `results-letter` :90 | `ink/uitslae {"deel":"brief"}`; renders nothing with no letter |
| 3 | `results-podium` :103 | `ink/uitslae {"deel":"podium"}`; tabs through `assets/js/uitslae-tabs.js` |
| 4 | `results-mentions` :141 | `ink/uitslae {"deel":"vermeldings"}` |
| E8 | none (Lovable has no link) | Link in `patterns/reading-uitdaging.php`; see Questions Q3 |

Header and footer are the existing template parts. Renderer: `Challenges/ResultsPage.php`, thin `render()` over pure `toHtml()`, like `SinglePage`. Read model: `Challenges/RoundResults.php`.

**Tabs.** Lovable swaps panels on the client. Render all three panels on the server, with `hidden` on the inactive two and `role=tab` / `aria-selected` as in Lovable. Stories and Artikels ids are captured with `--click [data-audit-id=results-tab-short-story] --state stories` and the Artikels equivalent. **Not verified:** how the harness treats nodes under `hidden` in the default capture. The default capture must not report them as `EXTRA`; test this first.

**Lovable's empty cases, followed as coded:** all three tabs always show; a tier heading shows even if its podium is empty (`:128-131`). Do not hide them.

## 3. Element table

Repeater slots: `{slot}` = `gold`, `silver`, `bronze`, `short-story`, `article`; `{n}` = 1 to 3. Ids stay as Lovable assigned them, in English (R3).

| Audit id | Recipe | Data source | Copy |
|---|---|---|---|
| `results-hero` | section-band: `ink-band ink-band--poem ink-band--intro` | | |
| `-hero-inner`, `-letter-inner`, `-podium-inner`, `-mentions-inner` | `ink-container`; widths in `uitslae.css` | | |
| `results-hero-eyebrow`, `-icon` | eyebrow card: `ink-eyebrow ink-eyebrow--card ink-eyebrow--gold` | Derived: month, §4 | `UITSLAE-EYEBROW` |
| `results-hero-h1` | **RECIPE-GAP GAP-01-01** | Derived: theme, §4 | `UITSLAE-H1` (debt) |
| `results-hero-meta` | Page text | Derived: announced date, entry count, §4 | `UITSLAE-META` |
| `results-letter` | **GAP-01-02** | New field `ink_uitdaging_brief` | |
| `results-letter-icon` | Icon, page layout | | |
| `results-letter-h2` | heading h2-block | | `UITSLAE-BRIEF-H2` (debt) |
| `results-letter-p-{0,1}` | Page text | One node per paragraph of the field | Owner's text |
| `results-letter-note` | Page text | | `UITSLAE-BRIEF-NOTA` (debt) |
| `results-podium` | **GAP-01-02** + `ink-band--border-t` | | |
| `results-podium-head` | Layout | | |
| `results-podium-h2` | heading h2-section | | `UITSLAE-PODIUM-H2` (debt) |
| `results-tabs`, `results-tab-*` | **GAP-01-03** | Fixed three tabs | Terms `gedig_plural`, `storie_plural`, `artikel_plural` |
| `results-tiers`, `results-tier-{gold,silver,bronze}` | Layout | Fixed order: Goud, Silwer, Brons | |
| `results-tier-*-h3` | heading h2-block on `<h3>` | | `UITSLAE-AFDELING` (debt; Terms `goud` etc. as token) |
| `results-tier-*-dot` | **GAP-01-08** | | |
| `results-podium-{slot}`, `-rest` | Layout in `uitslae.css` | First row by position is the lead; the rest follow | |
| `results-award-{slot}-{n}` | **GAP-01-04**; lead **GAP-01-05** | `RoundResults` row | |
| `…-pill`, `-pill-icon` | **GAP-01-06** | Stored position selects the tone; D11 label | `Placements::placementLabel()` |
| `…-grid`, `-main`, `-action` | Layout (lead grid in `uitslae.css`) | | |
| `…-title` | Lead: heading h2-sub. Others: heading h3 | Win title, §5 | Member's text |
| `…-author`, `-avatar` | Page text; avatar `ink-avatar ink-avatar--28` | Display name or by-line; `get_avatar_url()` | |
| `…-excerpt` | Page text; gedig italic with line breaks | Derived, §4; blank with no post | Member's text |
| `…-citation`, `-citation-text` | **GAP-01-07**; page text | `ModeratorFeedback::feedbackFor()`; block absent if empty (D6) | Moderator's text |
| `…-citation-by` | `ink-stat-label` | | `UITSLAE-KRITIEK-DEUR` |
| `…-btn`, `-btn-icon` | button-outline neutral: `ink-btn ink-btn--sm ink-btn--neutral` | Post permalink; only with a post | `Lees` (`ui-copy-translations.md:200`) |
| `…-soon` | Page text | Shown when the win has no post | `UITSLAE-GEEN-SKAKEL` (debt) |
| `results-mentions` | **GAP-01-02** + `ink-band--cream ink-band--border-t` | | |
| `results-mentions-h2` | heading h2-block; absent with no mentions | | `UITSLAE-VERMELDINGS-H2` |
| `results-mentions-list` | Page text | New field `ink_uitdaging_vermeldings` | `UITSLAE-VERMELDING-REEL` (debt) |
| `results-mentions-thanks` | Page text | `SinglePage::entryCount()` | `UITSLAE-DANKIE` (debt) |
| `results-mentions-btn` | button-primary: `ink-btn ink-btn--primary` | `CurrentChallenge::current()['url']`; absent if no round is open | `Skryf in` (`afrikaans-terms.md` Deel 2) |

**Tier mapping:** a Meester row shows under Goud (D11). A Gedig win with no grade is possible on old rounds (D13): show it under Gedigte with the non-tier `Podium` shape, below the three tiers.

## 4. Derived values

| Value | Source | Why |
|---|---|---|
| Month | `ChallengeResultTitle::monthLabel( uitdaging post_date )` | `Cadence::periodLabelFor()` needs a deadline, and all 140 rounds on dev have none |
| Theme | `ink_uitdaging_theme` if set, else the round's title, entities decoded | The field exists and is empty on all 140 rounds. Do not strip month or "projek" from the title; that would be new parsing |
| Announced | Linked post's `post_date` through `wp_date( 'j F Y' )`; newest post if several | Round 68708 has two |
| Entries | `SinglePage::entryCount()`, `_n( '%d inskrywing', '%d inskrywings' )` | The existing single source and string |
| Excerpt | `has_excerpt()`, else the `SinglePage::excerptFor()` rule. Gedig: first content lines through `Engagement\GedigBody::tokenize()`, keeping breaks | Lovable uses `whitespace-pre-line` on poems |
| "Results exist" | The round has a linked post (`WinnersPost::UITDAGING_META`) or at least one result row | 101 of 140 rounds on dev have a link. The rest return 404 |

## 5. Data

| Field | Source | Status |
|---|---|---|
| Position | `ink_entry_placement` | **Importer**: 0 rows on dev |
| Grade | `ink_entry_gradering` | **Importer + Gradering fix**: 0 rows. `Pools::group()` drops entries with no snapshot, so today Storie and Artikel need one. Do not build on that; the fix changes the pool key |
| Win without a post (156 rows in `wenners.csv`) | Importer's store, not chosen yet | **Importer**. Drives the `-soon` state |
| Win title and by-line | Importer's row (`title`, `byline`), else post title and author | **Importer**. Post titles carry noise, e.g. "Geld praat die hardste (Goud) (1382 woorde)". Do not clean titles here; prefer the listed title |
| Category tab | Post type where a post exists; importer's category otherwise | **Importer**; see Q4 |
| Citation | Comment type `ink_moderator_terugvoer`, opt-in `ink_wys_moderator_terugvoer` | Exists. 0 comments and 0 opt-ins on dev, so no citation shows on real data today |
| Letter, mentions | New fields, §6 | This package |

**Read seam.** `RoundResults::forRound( int ): array` is the page's only data path. Rows: `category`, `tier`, `position`, `post_id` (0 = none), `title`, `author`, `user_id`, `excerpt`. It reads `Placements::forRound()` now; the importer's store plugs in behind it. It applies filter `ink_uitslae_rows`, house style like `CurrentChallenge::DATA_FILTER`. Do not read pool keys in the page.

## 6. D8: judges' letter and honourable mentions

**Storage (both on the `uitdaging`, registered in `FieldSets`, in its admin box and REST):**

| Field | Key | Shape |
|---|---|---|
| Judges' letter | `ink_uitdaging_brief` | Text; blank line separates paragraphs |
| Honourable mentions | `ink_uitdaging_vermeldings` | JSON list of `{titel, skrywer, post_id}`. Plain text as printed; `post_id` optional. Lovable does not link them |

**Not touched:** `LegacyResultsParser.php`, `build-winner-lists.php`, `wenners.csv`, `result-posts.csv`, `challenges.csv`. Mentions are not wins and never enter that data.

**New results (from now on):**
- `Challenges/ResultsParser.php` (the paste parser, not the legacy one) stops discarding at `:147-151` and returns `mentions` next to `winners` and `commentary`. Lines under the heading stop being dropped; they still never become winners.
- `IngestionPage::analyse()` resolves the EntryIDs. `Ingestion::commit()` writes the field as a new seam, inside the existing commit-done guard.
- The letter is typed in the round's admin box.

**Old rounds:**
- **Letter:** none exists. Of 102 result posts, 97 have text above the winners, mostly "Baie geluk aan hierdie 10 lede!" and 10 run past 120 characters, some of them repeating the assignment. Do not extract it. `results-letter` hides.
- **Mentions:** 7 of 102 posts have a real heading, read with the legacy parser's own pattern and the public `LegacyResultsParser::lines()`: 57598, 56179, 52098, 45938, 45306, 30902, 16861. Layouts vary, so do not parse them. New optional input `../migration-data/eervolle-vermeldings.csv` (`source_category,titel,skrywer`), hand-filled by reading those posts, read by a new `migrate-all` step that writes only `ink_uitdaging_vermeldings`. Keying on `source_category` survives ID changes (`winner-cleanup.md` §7). An empty file is valid: those rounds show no mentions.

Without a row, `results-mentions-h2` and `-list` hide; the thank-you and CTA stay.

## 7. URL and slug check (dev, read-only)

| Check | Result |
|---|---|
| Post or page named `uitslae` | None. No published post named `uitdaging` either |
| `/uitdaging/september-2026-my-opinie-projek/uitslae/` today | **301 to `/uitslae-en-kommentaar-augustus-2020-oop-projek/`.** The path falls to the attachment rule, 404s, and core's 404 guess finds an old result post |
| Rule order | The `wc-api` endpoint already generates `uitdaging/([^/]+)/wc-api(/(.*))?/?$` ahead of `uitdaging/[^/]+/([^/]+)/?$`. An `EP_PERMALINK` endpoint lands the same way |
| Round slugs | 140 published rounds. Permalinks `/%postname%/`; `uitdaging` rewrite `ep_mask` = 1 |
| Result posts | 102 linked, 101 rounds. Example `/september-2026-my-opinie-projek-kommentaar-en-uitslae/` (200 today) |

**Required behaviour:**
- `add_rewrite_endpoint( 'uitslae', EP_PERMALINK )`, version-gated flush. The mask also matches posts and other CPTs, so `/gedig/x/uitslae/` must 404. `/uitslae/` gives an empty query var; test presence, not truthiness.
- 404 with no results, and suppress the guess (`do_redirect_guess_404_permalink`), or the stray 301 above returns.
- Register in the endpoint list for the `SystemPagesTest` guard (README §3.4).
- **301 (D5):** `template_redirect`, singular `post` with `UITDAGING_META` goes to `/uitdaging/<slug>/uitslae/`. Both posts of round 68708 redirect. The post stays stored: it holds the announced date. The two `not-results` posts lose their link in the importer (`winner-cleanup.md` §7), so they 301 wrongly until then.
- Check `tools/fidelity/targets.json` gets the new row with a migrated round; another agent owns that folder.

## 8. Load scope

| Asset | Holds | Gate | Consumers |
|---|---|---|---|
| `assets/css/uitslae.css` (new) | Layout and page text only | `is_singular( 'uitdaging' )` and endpoint present | `patterns/uitslae.php` |
| `assets/js/uitslae-tabs.js` (new) | Tab switching | Same | Same |
| `primitives.css` | Ratified gaps | Site-wide | Everywhere |

E8 needs no sheet.

## 9. Copy

| Key | Text | Source |
|---|---|---|
| `UITSLAE-EYEBROW` | `Uitslae · {Maand jaar}-uitdaging` | "Uitslae" README N5; `%s-uitdaging` `CurrentChallenge.php:255` |
| `UITSLAE-META` | `Aangekondig {datum} · {n} inskrywings · ` + debt `UITSLAE-META-KATEGORIE` | "aangekondig" README N5; `_n` string `CurrentChallenge.php:415` |
| `UITSLAE-KRITIEK-DEUR` | `— Terugvoer van die moderator` | `ui-copy-translations.md:444` |
| `UITSLAE-VERMELDINGS-H2` | `Eervolle vermeldings` | Prod's heading; `LegacyResultsParser.php:120` |

Copy-debt keys, each a `[NEEDS HUMAN AFRIKAANS]` marker, added to `afrikaans-translation-sheet.md` and `afrikaans-copy-worklist.md` at build: `UITSLAE-H1`, `UITSLAE-META-KATEGORIE`, `UITSLAE-BRIEF-H2`, `UITSLAE-BRIEF-NOTA`, `UITSLAE-PODIUM-H2`, `UITSLAE-AFDELING`, `UITSLAE-GEEN-SKAKEL`, `UITSLAE-VERMELDING-REEL`, `UITSLAE-DANKIE`. New Terms need the glossary first: `afrikaans-terms.md` (Onderhoud). `Terms::label( 'uitslae' )` returns the raw key today; it is not a registered term.

## 10. Accepted divergences

| Divergence | Handoff list |
|---|---|
| "algehele wenner" / "wenner" on the pill, not "1st Place"; stored position still selects the tone. Verbatim from Terms, so lower case | Item 2 (D11) |
| Afrikaans copy | Item 3 |
| Citation block absent without opt-in | D6 |

Anything else is `DRIFT`, and Lovable wins.

## 11. Test fixture for dev

Create on dev, outside theme and plugin; a refresh wipes it. List what was created. **Round: 68679, `/uitdaging/augustus-2026-aanhalingsprojek/uitslae/`**, not the latest round, because Tuis needs the latest round all on posts (spec 03 §9).

**Set `ink_entry_placement` and `ink_entry_gradering`:**

| Slot | Post | Position | Grade |
|---|---|---|---|
| Gold | 68070, 68144, 68201 | 1, 2, 3 | `goud` |
| Short story | 68211, 68151 | 1, 2 | `goud` (only because of today's `Pools`; drop after the fix) |
| Article | 68214 | 1 | `goud` (same) |

**Rows without a post, through a dev-only `wp-content/mu-plugins/ink-toets-uitslae.php` hooking `ink_uitslae_rows`** (they are the `-soon` state; the store does not exist yet):

| Slot, position | Title | By-line |
|---|---|---|
| Silver 1, 2, 3 | … Waar storms gaan lê; Waar niks ontbreek nie; Que vadis | Pierce van Tonder; Nicola Brown; Hennie du Toit |
| Bronze 1, 2, 3 | Geloof, Hoop en Liefde; As verbeelding belangriker as kennis is; Toetstitel brons 3 | Audrey Bierman; Anja Nel; Toetsskrywer |
| Short story 3 | Een dollar per jaar | Rika Gerryts |
| Article 2, 3 | Waar siel en liggaam ontmoet; Wees die verandering | Pieter Mostert; Hester Steenkamp |

**Also:**
- Citations: one `ink_moderator_terugvoer` comment per posted row, using that piece's paragraph from post 68266. `ink_wys_moderator_terugvoer` = 1 on the authors. Rows without a post carry a `citation` key from the same post. Add `citation` to the row shape for this.
- `ink_uitdaging_brief` on 68679: two paragraphs starting "Toetsdata:".
- `ink_uitdaging_vermeldings` on 68679: three rows, "Toetstitel" / "Toetsskrywer".
- Open round for `results-mentions-btn`: fixture F1 in spec 03 §9.
- **D6 check:** turn opt-in off for one author and confirm the block goes. Add a unit test too.

Measure at 390, 768, 1440, for the default state and both tab states. Acceptance here is provisional until the importer lands (handoff step 7).

## Questions for owner

1. **Q2. Old commentary disappears.** D5 301s all 102 result posts, and D6 hides citations without opt-in. Today's posts publish every entry's commentary, for winners and non-winners. After this, none of it is reachable for any old round. There are 0 stored citations and no import planned. Is that intended, or should the old body stay reachable somewhere?
2. **Q3. E8 has no design.** README E8 wants a link from the round to its results, but `Challenge.tsx` has none and it isn't on the permitted list. Proposal: one `ink-btn ink-btn--lg ink-btn--outline` beside "Lees inskrywings" in `uitdaging-actions`, labelled "Uitslae", only when results exist. Approve, or say where it goes?
3. **Q4. Seven old categories, three tabs.** `wenners.csv` has Gedigte 952, Verhale 252, Rubrieke 133, Artikels 84, Meningstukke 67, Algemeen 63, Prosa 60. Sept 2026 uses Prosa for what are articles. Which tab do Rubrieke, Meningstukke, Prosa and Algemeen (Gorrel) go under? This applies where a win has no post; otherwise the post type decides.

(Q1 is about the hero and sits in spec 03.)
