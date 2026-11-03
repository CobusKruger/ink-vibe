# Owner rulings on the build specs

Rulings of 2026-11-01. Where a spec's questions or defaults disagree, this file wins.

| Spec | Question | Ruling | Consequence |
|---|---|---|---|
| 03 Tuis | Q1 hero deadline | A monthly round runs from the first to the last day of its month | Withdrawn; see `03-tuis.md` §2 |
| 01 Uitslae | Q3 link from round to results | Add it, as a banner similar to the homepage results banner | **Approved divergence:** the design has no link. Shown on the round page only when results exist. Reuses the home banner recipes (GAP-03-01, -02, -03, -05, -06), not the outline button the spec proposed |
| 05 Kompetisies | Q1 Enter buttons | Keep `/skryf/`, as in the design. The writer chooses there what to write and whether to enter it for the challenge | No change to the spec default |
| 05 Kompetisies | Q2 old gala Pages | Redirect | `/ink-se-gala-aande/`, the ten gala Pages and the ten Fotos Pages 301 to `/kompetisies/`. Their photos feed the "Evenings past" gallery |
| 05 Kompetisies | Q5 gala years | Slide, don't wrap | **Approved divergence:** all years show, and the chip row scrolls sideways |
| 06 Chrome | Q1 closing CTA | The contribution CTA was removed on purpose | Stays absent on Opleiding and Biblioteek, recorded as `scope` in the map |
| 05 Kompetisies | Seed data | Seed CSVs live in `../migration-data/` | **Superseded by Q4 below:** there are no seed files. The one CSV, for old competitions only, lives there |
| 04 InkPols | Featured items | Lovable's order: Storie, Gedig, Artikel (superseded 2026-11-03: glans-1, 2, 3, see below) | As the spec has it |
| 03 Tuis | No round yet for the current month | Keep showing the last one | The hero never goes empty because a new round is late |
| 03 Tuis | Which field gives a round's month | The title; recent rounds all carry the month. The post date verifies it: normally the 1st, seldom after the 3rd, a few outliers shortly before month-end | |
| 01 Uitslae | Q2 old result posts | Each challenge has its own uitslae page, and the old results redirect there | D5 confirmed: one 301 per post to its own round's page |
| 01 Uitslae | Q4 categories to tabs | Already ruled, `docs/migration-plan.md` §"Current state": `artikels`, `algemeengorrel`, `rubrieke`, `prosa`, `prosa-lede-bydraes` give `artikel` | Verhale to Stories; Rubrieke, Meningstukke, Prosa and Algemeen to Artikels |
| 05 Kompetisies | Q3 "Submit a listing" | Opens an email to `skryfafrikaans@ink.org.za` with the subject "Nuwe kompetisie kennisgewing" | |
| 05 Kompetisies | Q4 editing | **New competitions are added through a competitions CPT.** `migrate-all` uses a CSV only to import old competitions from before the cutover. See THE FIRST RULE in `CLAUDE.md` | Spec 05 is rewritten around real site features |
| 05 Kompetisies | "LET WEL" disclaimer | Added back later, once the new competitions pages are up | Not in this build |
| 06 Chrome | Audit-ids for the mobile menu | Add them | Ids only |

## Still open


## Design update (verse-haven `e870ac0`, 2026-11-01)

Reference is now `e870ac0`, which contains the audit-ids (`c65d970`). Lovable normalised headings, filter chips and card hovers, so GAP-01-01, RG-IP-04, RG-KO-13, RG-KO-14 and RG-KO-18 are closed and RG-IP-10 now covers only the fee chips. 44 gaps remain. Section spacing and the fee chips were not normalised.

## Design update (verse-haven `0589bf6`, 2026-11-01)

Reference is now `0589bf6`. Lovable moved section spacing to the Library step (existing `ink-band--step`) and the fee chips to the standard pill, so GAP-01-02, RG-IP-01, -02, -03, -10 and RG-KO-02 are closed. 38 gaps remain.

## Recipe gaps ratified (owner, 2026-11-01)

All 38 rows in `recipe-gaps.csv` are ratified. Values come from captures. Category 7 (RG-KO-10, GAP-03-09, RG-IP-05) is ratified as it stands, unless Lovable normalises it first.

**Competitions dark hero:** kept, because the annual competition is a momentous occasion on the INK calendar. Dark is the only difference: font sizes, padding and everything else match the standard elements.

## Design update (verse-haven `3e75fb8`, 2026-11-01)

Reference is now `3e75fb8`. It adds a new page, `src/pages/Challenges.tsx` at `/challenges` (INK: `/uitdaging/`, `archive-uitdaging.html`), with the header link pointing to it. Specified in `07-uitdagings.md`.

**Uitslae winner display (owner, 2026-11-01):** ratified as designed. Stories and Artikels sit behind their own tabs.

**Challenges page (owner, 2026-11-01):** keep the design's heading and featured current-round card. Past rounds keep INK's standard cards, not the design's rows. Accepted divergence, recorded as `scope` in `maps/uitdagings.csv`. See `07-uitdagings.md`.

**Biblioteek shelf (2026-11-01):** the page, its menu link and the Gedigte-first pills stay (owner). The design shows the "Editor's shelf" only under an "All" category, which INK doesn't have, so the shelf stays out, recorded as `scope`. This is our conclusion from that; the owner did not object.

## Rulings of 2026-11-02

Reference is now verse-haven `cbd9d3a` (includes the Challenges ids). **Dark hero:** recommendations accepted; the "only difference" condition means padding, spacing and font sizes. The Guidelines border is surface/30 and the pattern 0.03, as the standard. **Countdown:** kept for now on the Challenges page; the owner may reassess later.

**RG-UI-01, -02, -03:** ratified (owner, 2026-11-02).

**Header (owner, 2026-11-02):** "My profiel" comes off the desktop row, since the user menu already has it. The header switches to the mobile menu below 1024 instead of the design's 768, for one clean row at every width. Accepted divergence, disclosed in code.

**Gala photos (owner, 2026-11-02):** the migration imports every photo from each old Fotos page, in order, not the first four. A gala year on `/kompetisies/` shows four photos, then a "Sien alles" link to a page with all that year's images in an infinite scroll. The design has no such page or link: approved addition, built from the ratified `ink-photo` and link recipes, disclosed in code. The photos and the galas themselves are read from the old pages by the migration, not listed in `kompetisies-oud.csv` (owner, 2026-11-02: a new gala before cutover must import with no file edit); the CSV only overrides the highlight and captions. Order can change later in admin.

**"Nth wen" ordinals (owner, 2026-11-02):** 1ste, 8ste, 20ste and up, otherwise -de.

**Challenges past cards (orchestrator decision, 2026-11-02):** they sit in the standard wide container, not the design's 896px one, so they keep the owner's preferred standard card display. Accepted divergence.

## Lees decisions (owner, 2026-11-02)

All four recommendations approved: **build the response upvote**; **keep the "Select text" hint and per-line heart members-only**; **add the poem dedication field**; **keep poem lines member-only and persisted**.

**Poems, stacked blank lines (owner, 2026-11-02):** two or more consecutive blank lines collapse to a single stanza break (40px at 390, 48px at 1440). Overrides the "blank-line spacing verbatim" part of FR-25; leading whitespace and line breaks stay verbatim. Blank lines at the very start or end of a poem are dropped too. Implemented in `GedigBody::toHtml()` (one separator owed before the next stanza; none leading or trailing).

## InkPols featured items (owner, 2026-11-03)

The three featured items are no longer exactly one Gedig, one Storie and one Artikel: they are a variety of items from the magazine, called **glans-1, glans-2, glans-3**. Each takes `titel`, `skrywer` and a new free-text **`tipe`** that replaces the hardcoded Gedig/Storie/Artikel labels. Display order is glans-1, 2, 3. Still plain text, not linked; all nine fields required to publish. Supersedes the "Storie, Gedig, Artikel" order and the item rules in `inkpols.md`. (The owner typed "gland-3": read as glans-3.)
