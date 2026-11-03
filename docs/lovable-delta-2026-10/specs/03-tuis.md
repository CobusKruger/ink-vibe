# Build spec 03: Tuis (E1, E2, N5, N6 home section)

**Built (step F, 2026-11-02).** `winner-gedig-*` and `winner-*` pair by type. `FeaturedWinners::order()` and `orderFeed()` are gone with the old feed.

**Accepted (2026-11-02).** Gate passes at 390, 768 and 1440 (`maps/tuis.csv`). Hero, CTA band and sponsor strip are now emitted in Lovable's node structure (raw HTML in the patterns, `HomepageStrip`), as the header was; the featured cards keep `<article>` with a stretched title link (hover and click verified against Lovable). Accepted with evidence, in the map: link-for-`<button>` on four buttons (c), BTN-FONT (a), NR-4 meta gap (a), per-type pill tone (a), CTA padding and text colour (a), and sizes that follow Afrikaans copy and data (b). `featured-lead-author-name` was missing because fixture post 68862 (the F1 entry, now the newest) had author 1, which does not exist; it now belongs to user 3799. The hero's closed-round state was measured by unpublishing round 68854 for one capture: no MISSING or STRUCTURE, only height deltas from the longer Oktober description.

**Closed-round state:** was measured by drafting a fixture round; that gate and the fixtures were removed (2026-11-03). It needs a real way to close the newest round before it can be gated again.

**Status:** planned, not ratified. **Date:** 2026-11-01. **Map:** `../maps/tuis.csv` (152 ids). **Gaps:** `GAP-03-01` to `GAP-03-09`. **Depends on:** spec 01 (banner target); the InkPols current-issue renderer (N2).
This spec holds no style values (R4, R5). Recipes are joins for the capture to check. `header-nav-competitions` is WP6, and the footer is deferred.

## 1. Files read

**Lovable:** `src/pages/Index.tsx`; `components/home/{HeroSpotlight,ResultsBanner,MagazineSection,ChallengeSection,FeaturedWorks,SponsorsSection,CallToAction}.tsx`; `components/ui/button.tsx`.
**INK:** `templates/front-page.html`; `patterns/{hero,featured-grid,borg-strook,cta-band,qa-bloks (references)}.php`; `Challenges/{CurrentChallenge,FeaturedWinners,WinnersPost,Placements,Pools,Deadline,Migration (:1-240)}.php`; `Discovery/FeaturedStream.php`; `Sponsors/HomepageStrip.php` (from :60), `Campaign.php` (located, not read whole); `InkPols/{Issue,Archive}.php`; `functions.php:1125-1361`; `assets/css/home.css`.

## 2. Hero challenge card: why it doesn't render

**Cause: a code defect. A monthly round's period is implied by its month (owner, repeatedly: October runs 1 to 31 October, November 1 to 30 November), but `resolveCurrent()` requires a stored deadline, and no round has one.**

| Step | Evidence |
|---|---|
| Rendered page | `hero-aside` present and empty; 0 matches for `hero-spotlight` and `ink-huidige` in the fresh HTML (200, cache-busted) |
| Block | `CurrentChallenge::toHtml()` returns `''` with no title (`:309`) |
| Payload | `CurrentChallenge::current()` is `NULL` in `wp eval-file`; `ink_home_current_challenge` filter gives `NULL` |
| Query | `resolveCurrent()` skips a round when `Deadline::parse()` is null (`:202-208`) |
| Data | **140 published rounds, 0 with `ink_uitdaging_deadline`.** All 10 scanned rounds (68777 to 68715) print `deadline=''` |
| Origin | `Challenges/Migration.php:31`: "no deadline is fabricated (legacy categories carry none)". Nothing else writes it |

So the markup isn't at fault. **Fix:** derive a monthly round's close from its month, in one place in `Challenges/Cadence.php`, and have every reader of `UITDAGING_DEADLINE` (`CurrentChallenge`, `Archive`, `WinnersPost`, `SinglePage`, `ChallengeLinking`, `KennisgewingsSurface`) use it. Nothing is stored or entered per round, and no migration input is needed. A round with cadence `jaarliks` keeps its stored deadline, as the code does today; competitions don't use it (spec 05). The month comes from the title, verified against the post date (owner: created in the first days of the month, normally the 1st, seldom after the 3rd, a few outliers shortly before month-end).

**No new round yet (owner, 2026-11-01): keep showing the last one.** `resolveCurrent()` falls back to the newest round instead of returning null, so the hero isn't empty on dev today: it shows Oktober 2026. A round already past its close shows no "Sluit" date, because a past closing date is not a call to act.

## 3. Section spine

Required order: owner, 2026-10-30.

| # | Lovable | INK | Change |
|---|---|---|---|
| 1 | `HeroSpotlight` | `patterns/hero.php`; `ink/huidige-uitdaging {"variant":"kompak"}` | Keep; §5 |
| 2 | `ResultsBanner` | **New** block `ink/uitslae-banier`, `Challenges/ResultsBanner.php` | Add |
| 3 | `MagazineSection` | **New** block `ink/inkpols-tuis`, `InkPols/HomeSection.php` | Add |
| 4 | `ChallengeSection` | `ink/wenner-kollig`, `Challenges/FeaturedWinners.php`, DOM rewritten | Replace |
| 5 | `FeaturedWorks` | `patterns/featured-grid.php`, `Discovery/FeaturedStream.php` | Keep; add mobile link |
| 6 | `SponsorsSection` | `patterns/borg-strook.php`, `Sponsors/HomepageStrip.php` | Keep; hide empty band |
| 7 | `CallToAction` | `patterns/cta-band.php` | Keep; disclosed divergence stays |

**`templates/front-page.html`:** order as above. **Remove** the `ink-feature-band` group, lines 8-19.

**Remove with it:**
- `.ink-feature-band` and `.ink-feature-grid` rules in `home.css`.
- `ink_foundation_homepage_demo_winner()` and its `add_filter` (`functions.php:1289-1361`), as the handoff orders.

**Keep:** `VARIANT_FEATURE` and its `--kenmerk` CSS. `patterns/qa-bloks.php:71` still embeds it. Its `challenge-card*` ids no longer pair with Lovable on Tuis.

**Update on build:** `fidelity-remediation/maps/tuisblad.csv` is partly orphaned: `challenge-card*`, `winner`, `winner-cards`, `winner-eyebrow-text`, `winner-byline`, `hero-stat*` have no current Lovable node.

## 4. Winner pairing (E1, D10)

| Position | INK card | Join |
|---|---|---|
| Pair 1, left | Goud Gedig | `winner-gedig-*` |
| Pair 1, right | Storie | `winner-*` |
| Pair 2, left | Silwer Gedig | No ids: repeats the recipe, no Lovable reference |
| Pair 2, right | Artikel | No ids: same |

- Lovable's `winner-card` is the prose card and sits left; `winner-gedig-card` is the poem and sits right. **Pair by type.** INK's left/right order comes from D10, so horizontal position drift on these ids is expected.
- Duplicate ids make `run.sh diff` exit 2, so pair 2 carries none. It is checked by class list and, if wanted, a `--scope` capture.
- Silwer Gedig uses Lovable's poem shape (`poem: true`) with the Feather icon. Artikel uses the prose shape with BookOpen.
- Disclose D10 in a code comment (R6).
- A slot with no winner renders no card, no placeholder.

## 5. Element table

**Hero** (exists; ids and files in the map). Recipes stand; re-measure.

| Audit id | Finding (Lovable wins) | Data | Copy |
|---|---|---|---|
| `hero-spotlight-badge` | Text is the month tag, not the word "Uitdaging" | Payload `eyebrow` | `Januarie-uitdaging` (`ui-copy-translations.md:76`) |
| `hero-spotlight-date` | Lovable carries deadline and entries; compact variant has only the deadline | `deadline` + per-type counts (new in payload) | `Sluit 31 Jan` and `18 gedigte · 12 stories · 4 artikels`, zero counts dropped (`ui-copy` same table). Today `Deadline::format()` gives the long form |
| `hero-spotlight-title` | Lovable wraps the theme in quotes | Round title | |
| `hero-spotlight-btn` | None | Round URL | `Skryf in` |
| `hero-texture` | `MECHANISM`, F10: `::before`, no node | | |

Other `hero*` ids: unchanged; re-measure. Data for the card: the current round (§2); fixture F1 for the open state.

**Results banner (N5).** Band and the whole panel link.

| Audit id | Recipe | Data | Copy |
|---|---|---|---|
| `results-banner` | **RECIPE-GAP GAP-03-01** | Absent if no announcement | |
| `results-banner-container` | `ink-container` | | |
| `results-banner-link` | **GAP-03-02** | Round's `/uitslae/` URL | |
| `results-banner-icon`, `-icon-svg` | **GAP-03-03** | | |
| `results-banner-body` | Layout inside GAP-03-02, because the banner also shows on the round page (spec 01 §12) | | |
| `results-banner-eyebrow` | **GAP-03-04** gold | The round's month, from its title (§2) | `Uitslae aangekondig · {Maand jaar}` (README N5) |
| `results-banner-title` | **GAP-03-05** | Theme: spec 01 §4 | `TUIS-UITSLAE-TITEL` (debt) |
| `results-banner-cta`, `-cta-icon` | **GAP-03-06** | | `TUIS-UITSLAE-CTA` (debt) |

Round: the latest announcement. Make `WinnersPost::latestAnnouncement()` reachable through a public read, then `UITDAGING_META`. No placements are needed, so the banner works on real data today: round 68791, post 68532.

**InkPols home section (N6, `inkpols.md`).** Hidden with no published issue. Shares N2's current-issue renderer, taking an audit prefix: `magazine-*` here, `magazine-current-*` on the archive. Build N2 first.

| Audit id | Recipe | Data | Copy |
|---|---|---|---|
| `magazine` | `ink-band ink-band--poem ink-band--cream` | Latest published `inkpols_uitgawe` | |
| `magazine-container` | `ink-container` | | |
| `magazine-grid`, `-cover-col`, `-body`, `-actions` | Layout | | |
| `magazine-cover` | **GAP-03-07** | `ink_inkpols_cover_id`; alt = naam | |
| `magazine-eyebrow` | eyebrow card, primary | Post title (naam) | `InkPols · {naam}` (`inkpols.md`) |
| `magazine-h2` | heading h2-section | `ink_inkpols_titel` (`Issue` must gain it) | Owner's |
| `magazine-text` | Page text | `ink_inkpols_teaser` | Owner's |
| `magazine-inside-label` | **GAP-03-04** muted | | `In hierdie uitgawe` |
| `magazine-inside-list`, `-item-{0,1,2}` and `-kind`, `-title`, `-author` | Layout and page text | `ink_inkpols_{storie,gedig,artikel}_{titel,skrywer}`, plain text, Lovable's order: prose, poem, essay | Kind: Terms `storie`, `gedig`, `artikel` |
| `magazine-btn-read`, `-icon` | button-primary lg: `ink-btn ink-btn--lg ink-btn--primary` | Permalink | `Lees aanlyn` |
| `magazine-btn-pdf`, `-icon` | Not built | | |
| `magazine-past-link`, `-icon` | **GAP-03-06** | `/inkpols/` | `Alle uitgawes` |

**Winners (E1).** Both id families below; `{g}` = empty or `gedig-`.

| Audit id | Recipe | Data | Copy |
|---|---|---|---|
| `challenge` | section-band base: `ink-band`, no tint (dev's cream is the older design) | Absent with no winner | |
| `challenge-container` | `ink-container` | | |
| `challenge-head`, `-head-icon` | Layout; icon | | |
| `challenge-eyebrow` | eyebrow card + gold | Month and theme | `{Maand}-uitdaging · "{tema}"` |
| `challenge-h2` | heading h2-section | | `TUIS-WENNERS-H2` (debt) |
| `challenge-grid` | Layout in `home.css` | | |
| `winner-{g}card` | card winner: `ink-card--winner`, flex column in `home.css` | Slot winner, §6 | |
| `winner-{g}watermark` | Layout (existing watermark rule) | | |
| `winner-{g}eyebrow`, `-eyebrow-icon` | **GAP-03-08** | Slot | `Goud Gedig`, `Storie`, `Silwer Gedig`, `Artikel` (D10; Terms) |
| `winner-{g}title` | heading h2-block on `<h3>`, in quotes per Lovable | Post title or win title | Member's |
| `winner-quote` | Page text in `home.css`: italic, quoted | Excerpt rule, spec 01 §4 | Member's |
| `winner-gedig-quote` | **GAP-03-09** | First four lines, breaks kept (Lovable shows four) | Member's |
| `winner-{g}footer`, `-author`, `-author-details` | Layout | | |
| `winner-{g}avatar` | `ink-avatar ink-avatar--gold-ring` | `get_avatar_url()` | |
| `winner-{g}author-name` | Page text | Display name | |
| `winner-{g}author-wins` | Page text | Lifetime count, §6 | `3de wen` (`ui-copy`) |
| `winner-{g}btn`, `-btn-icon` | button-outline gold: `ink-btn ink-btn--gold` | Permalink | `Lees storie`, `Lees gedig` (README E1); `TUIS-WENNER-LEES-ARTIKEL` (debt) |

**Featured, sponsors, CTA** exist; recipes stand; re-measure.

| Audit id | Change |
|---|---|
| `featured-all-link-mobile` | **Add** (F2). `link-underline` base. Hidden from `md`. `Sien alle skrywes →` from Terms `sien_alle_werke` |
| `featured-lead`, `featured-card` | `MECHANISM` F5: `<article>` vs `<Link>`. Per-type pill tone is disclosed in `FeaturedStream::metaTopHtml()`: respect it (R6) |
| `sponsors` | **Dev has 0 `borg` posts:** the block returns `''` but the `<aside>` keeps its padding, an empty band. Add `.ink-borg-band:not(:has(.ink-borg-strook)) { display: none }`, the same device `home.css:205` used |
| `sponsors-*` | Copy from Terms, unchanged |
| `cta*` | Unchanged. Bottom padding: `DIVERGENCE (accepted)`, `cta-band.php:21` |

## 6. Data

| Field | Source | Status |
|---|---|---|
| Current round | `CurrentChallenge::current()`: the open round, else the newest (§2) | Code fix; fixture F1 only to measure the open state |
| Round for banner and winners | Latest announcement | Exists: 102 linked posts |
| Winner slots | `WinnersPost::featured()` today returns only id, rank, title, URL, flattening every pool | **Rewrite:** return four slots, rank 1 in each, ties by lowest id as in `orderFeed()` |
| Position | `ink_entry_placement` | **Importer**: 0 rows, which is why the winner section prints nothing |
| Goud/Silwer on Gedig | `ink_entry_gradering`; Meester as Goud (D11) | **Importer + Gradering fix** |
| Storie, Artikel pool | Single pool after the fix. Read through `RoundResults` (spec 01), never pool keys | **Gradering fix** |
| "Nth wen" | Lifetime count from the win records (D10; `ink_tier_win_count` resets) | **Importer** for old wins; **`Ingestion::commit()` must add a record for every new win**, or the count freezes at go-live. Until then count `ink_entry_placement` rows by author behind one function, so the importer replaces one call. Ordinal form: `FeaturedWinners.php:358` assigned it to 12A; `ui-copy` gives only `3de wen` |
| InkPols fields | Old issues: CSV migration, built. New issues: the admin screen (`inkpols.md` §4) | Dev: 7 published with "Toetstitel" dummy data; expected per the handoff |
| Featured | Newest bydraes | Exists |
| Sponsors | Hand-made `borg` posts | 0 on dev |

## 7. URLs and slugs

| URL | Dev |
|---|---|
| `/` | Static front page, ID 37 |
| `/inkpols/` | 200, CPT archive. Latest: `/inkpols/julie-augustus-2026/` |
| `/uitdaging/<slug>/uitslae/` | Spec 01 §7 |

No new slug. Block names `ink/uitslae-banier`, `ink/inkpols-tuis` are new; check no block is registered under them when building.

## 8. Load scope

| Sheet | Holds | Gate | Consumers |
|---|---|---|---|
| `home.css` (existing) | Layout for banner and winners | `is_front_page()` or QA gallery (`functions.php:1132-1138`) | Front page, `qa-bloks` |
| `inkpols.css` (new, owned with N2) | Current-issue layout | `is_front_page()`, `is_post_type_archive( 'inkpols_uitgawe' )`, `is_singular( 'inkpols_uitgawe' )` | Home section, archive, single |

The InkPols spec must use the same sheet and gate; **not verified**, because that spec was being written alongside this one. `load_scope ⊇ used_in` is an acceptance gate.

## 9. Test fixture for dev

Outside theme and plugin; a refresh wipes it; list what was created. No fixture for featured writing or InkPols.

**F1, open round.** A published test `uitdaging` for the current month (November 2026), with body text for `hero-spotlight-desc`. No deadline meta: its close comes from its month (§2). It's needed only to measure the open state, with its "Sluit" date; after a refresh the hero shows the newest round either way.

**F2, four winners, round 68791 (the latest announcement), real results from post 68532:**

| Card | Post | `ink_entry_placement` | `ink_entry_gradering` |
|---|---|---|---|
| Goud Gedig | 68301, Al wat sy kon red | 1 | `goud` |
| Storie | 68398, Geagte Dominee Vark | 1 | `goud` (only for today's `Pools`; drop after the fix) |
| Silwer Gedig | 68446, Die museum van verlore mense | 1 | `silwer` |
| Artikel | 68420, Geld praat die hardste | 1 | `goud` (same) |

Winner cards measure on Lovable ids only for Goud Gedig and Storie.

**F3, sponsors.** Six published `borg` posts matching Lovable's mix: two `goud`, three `silwer`, one `brons` in `ink_borg_tier`; titles "Toetsborg 1" to "Toetsborg 6"; `ink_borg_start_date` 2026-01-01, `ink_borg_end_date` 2027-12-31; no logo, so the `-name` ids stamp. Don't use the `QA FIXTURE — ` prefix: `HomepageStrip` excludes it.

**Removes:** the demo filter must be gone before measuring, or the fixture isn't what shows.

Measure at 390, 768, 1440, and the mobile menu state for the `mobile-menu` capture. Provisional until step 7 passes.

## 10. Accepted divergences

| Divergence | Handoff list |
|---|---|
| Four winner cards, two pairs, Gedig left | Item 1 (D10) |
| Afrikaans copy | Item 3 |
| Real images only: no `magazine-cover.jpg` | Item 4 |
| CTA bottom padding; featured pill tones | Item 5 |
| A4 cover; no PDF button; naam for issue and season | Item 6 |

Item 2 applies only to labels, and home cards show category labels from D10. Everything else is `DRIFT`, and Lovable wins.

## 11. Copy-debt keys

`TUIS-UITSLAE-TITEL`, `TUIS-UITSLAE-CTA`, `TUIS-WENNERS-H2`, `TUIS-WENNER-LEES-ARTIKEL`. Each is a `[NEEDS HUMAN AFRIKAANS]` marker, added to the sheet and worklist at build.

Existing Afrikaans rejected on purpose: `Lees die volledige storie` is ratified for the older "Read Full Story"; the current design says "Read Story", and README E1 gives `Lees storie`. If you'd rather keep the ratified string, it's a one-line change.

## Questions for owner

None. Q1 was withdrawn: a monthly round's period is implied by its month (§2).
