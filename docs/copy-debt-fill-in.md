# Copy debt: fill-in sheet

> **Filled and applied 2026-11-06.** All strings are in the code; answers are in `ui-copy-translations.md` › "Kopieskuld ingevul (2026-11-06)". This sheet is now a record.

Generated 2026-11-06 from the live code. **101 strings** still show a `[NEEDS HUMAN AFRIKAANS]` placeholder on the dev site.

## How to fill it in

- Write your Afrikaans on the `AF:` line under each item. That's all.
- **Reads as:** shows the text around your blank (`▢`). The words around it are already Afrikaans in the code. Write only what goes in the blank.
- **English hint:** a sample from the earlier sheet, to show the meaning. Your Afrikaans replaces it; don't translate word for word.
- Leave an `AF:` empty if you're unsure. That string stays a tracked gap and keeps its placeholder.
- Keep tokens in braces, like `{skrywer}` or `{datum}`, exactly as written.
- House rules: sentence case, "jy"-voice, no savings or %-off framing, no auto-renew framing.
- Sections A, B, C, D, E are what visitors and members see. Section F is admin only: lower priority.

## What I do with it afterwards

1. Add each filled line to its `ui-copy-translations.md` row (**Goes to**).
2. Replace the placeholder at the **Code** location with the approved string, in the existing `ink-foundation` / `ink-core` text domain.
3. Update the matching ID in `afrikaans-translation-sheet.md` and `afrikaans-copy-worklist.md`.
4. Run `composer copy:scan`, then lower the baseline (`--update-baseline`). Launch target: empty.

IDs are for matching only. Ignore them.

---

## A. Public: Uitslae page and Tuis (home) winners

Uitslae = the results page of a monthly round. Tuis = the home page. `▢` marks the blank you are filling; the text around it is already Afrikaans in the code.

### 1. UITSLAE-H1: Results page title

- **Reads as:** “{tema}” — ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITSLAE-H1`
- **English hint:** Results page title, after the theme: "“Theme” — the winners"
- **Note:** Comes after the challenge theme. English sample: "the winners".
- **Code:** `wp-content/plugins/ink-core/src/Challenges/ResultsPage.php:191`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Uitslae

### 2. UITSLAE-META-KATEGORIE: Results hero meta line, last item

- **Reads as:** {n} inskrywings · ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITSLAE-META-KATEGORIE`
- **English hint:** Results hero, end of the meta line: "Poetry, stories and articles"
- **Note:** Lists the categories of the round (poetry, stories, articles).
- **Code:** `wp-content/plugins/ink-core/src/Challenges/ResultsPage.php:183`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Gedigte, stories en artikels

### 3. UITSLAE-BRIEF-H2: Heading above the judges' letter

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITSLAE-BRIEF-H2`
- **English hint:** Heading: "A note from the judges"
- **Code:** `wp-content/plugins/ink-core/src/Challenges/ResultsPage.php:218`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: 'n Nota van die moderator

### 4. UITSLAE-BRIEF-NOTA: Small note under the judges' letter

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITSLAE-BRIEF-NOTA`
- **English hint:** Below the letter: "Entries are judged anonymously. The panel does not see writers' names until results are final."
- **Note:** About anonymous judging.
- **Code:** `wp-content/plugins/ink-core/src/Challenges/ResultsPage.php:220`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Alle inskrywings word anoniem beoordeel. Die moderator sien nie skrywers se name nie.

### 5. UITSLAE-PODIUM-H2: Heading above the podium tabs

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITSLAE-PODIUM-H2`
- **English hint:** Heading: "The podium"
- **Code:** `wp-content/plugins/ink-core/src/Challenges/ResultsPage.php:254`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Die podium

### 6. UITSLAE-AFDELING: Heading of each grade section

- **Reads as:** {Goud / Silwer / Brons} ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITSLAE-AFDELING`
- **English hint:** After the grade: "Gold section"
- **Note:** Follows the grade word, e.g. "Goud ▢". English sample: "Gold section".
- **Code:** `wp-content/plugins/ink-core/src/Challenges/ResultsPage.php:279`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: < No text. Remove this. >

### 7. UITSLAE-GEEN-SKAKEL: Label when a win has no post on the site

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITSLAE-GEEN-SKAKEL`
- **English hint:** A win with no post on the site: "Coming to Inkwell soon"
- **Note:** Replaces the "Lees" button.
- **Code:** `wp-content/plugins/ink-core/src/Challenges/ResultsPage.php:371`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: < No text. If the win is not linked to a post, it's not a win. >

### 8. UITSLAE-VERMELDING-REEL: Connecting word in an honourable mention

- **Reads as:** “{titel}” ▢ {skrywer}
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITSLAE-VERMELDING-REEL`
- **English hint:** Joins title and writer in a mention: "“Title” by Writer"
- **Note:** Probably just "deur". Confirm.
- **Code:** `wp-content/plugins/ink-core/src/Challenges/ResultsPage.php:405`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: deur

### 9. UITSLAE-DANKIE: Closing thanks under the mentions

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITSLAE-DANKIE`
- **English hint:** Closing thanks: "And to all {n} writers who entered — thank you. Every piece was read in full."
- **Note:** The code has no writer count here. Write the sentence without a number, or tell me to add `{n}`.
- **Code:** `wp-content/plugins/ink-core/src/Challenges/ResultsPage.php:418`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Baie dankie aan almal wat aan hierdie uitdaging deelgeneem het.

### 10. TUIS-UITSLAE-TITEL: Tuis results banner title

- **Reads as:** “{tema}” — ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] TUIS-UITSLAE-TITEL`
- **English hint:** Results banner, after the theme: "“Theme” — meet the winners in poetry, stories and articles"
- **Note:** Comes after the theme.
- **Code:** `wp-content/plugins/ink-core/src/Challenges/ResultsBanner.php:128`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: sien die wenners in gedigte, stories en artikels

### 11. TUIS-UITSLAE-CTA: Tuis results banner link text

- **Reads as:** ▢ →
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] TUIS-UITSLAE-CTA`
- **English hint:** Results banner link: "Read the announcement"
- **Note:** Link to the Uitslae page.
- **Code:** `wp-content/plugins/ink-core/src/Challenges/ResultsBanner.php:130`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Lees die aankondiging

### 12. TUIS-WENNERS-H2: Tuis winners section heading

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] TUIS-WENNERS-H2`
- **English hint:** Tuis winners heading: "Last month's winners"
- **Note:** Above the winner cards.
- **Code:** `wp-content/plugins/ink-core/src/Challenges/FeaturedWinners.php:266`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Onlangse wenners

### 13. TUIS-WENNER-LEES-ARTIKEL: Winner card button for an article

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] TUIS-WENNER-LEES-ARTIKEL`
- **English hint:** Tuis Artikel card button; the others are "Lees storie" and "Lees gedig"
- **Note:** The story and poem cards already read "Lees storie" and "Lees gedig". Match that pattern.
- **Code:** `wp-content/plugins/ink-core/src/Challenges/FeaturedWinners.php:138`, `wp-content/plugins/ink-core/src/Challenges/FeaturedWinners.php:223`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Lees artikel


---

## B. Public: Kompetisies page

Hero, categories, gala, and the directory of outside competitions.

### 14. KOMP-HERO-BADGE: Hero badge

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-HERO-BADGE`
- **English hint:** Our flagship competition
- **Note:** Small badge above the hero title.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Hero.php:55`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Ons flagskip-kompetisie

### 15. KOMP-HERO-SLUIT: Hero pill: closing date

- **Reads as:** ▢ {datum}
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-HERO-SLUIT`
- **English hint:** Entries close
- **Note:** Date follows your text. English sample: "Closes".
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Hero.php:65`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Sluit

### 16. KOMP-HERO-INSKRYF: Hero primary button

- **Reads as:** ▢ →
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-HERO-INSKRYF`
- **English hint:** Enter the Competition
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Hero.php:85`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Skryf nou in

### 17. KOMP-HERO-RIGLYNE: Hero secondary button

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-HERO-RIGLYNE`
- **English hint:** Read the Guidelines
- **Note:** Opens the guidelines.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Hero.php:89`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Lees die reëls

### 18. KOMP-KAT-H2: Categories section heading

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-KAT-H2`
- **English hint:** Three categories, one annual prize
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Categories.php:64`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Gedigte en kortverhale, vir volwassenes en skoliere

### 19. KOMP-KAT-INTRO: Categories intro sentence

- **Reads as:** ▢ {naaswenner-nota}
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-KAT-INTRO`
- **English hint:** Judged blind, in every category.
- **Note:** The runner-up note (editor-entered) is appended after your text.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Categories.php:54`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Ons kompetisie word anoniem beoordeel.

### 20. KOMP-KAT-INSKRYF: Button on each category card

- **Reads as:** ▢ {kategorienaam}
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-KAT-INSKRYF`
- **English hint:** Enter (followed by the category name)
- **Note:** Category name follows, e.g. "Skryf in vir ▢ Gedigte". Write only the verb part.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Categories.php:103`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Skryf in < Remove the rest. All three buttons should say the same. >

### 21. KOMP-GALA-KICKER: Gala eyebrow

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GALA-KICKER`
- **English hint:** One evening a year
- **Note:** Small label above the gala block.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Gala.php:123`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Een spesiale aand per jaar

### 22. KOMP-GALA-DEURE: Gala info: doors time

- **Reads as:** · ▢ {tyd}
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GALA-DEURE`
- **English hint:** doors from
- **Note:** Time follows. English sample: "Doors".
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Gala.php:159`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: deure open

### 23. KOMP-GALA-PROGRAM: Gala programme label

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GALA-PROGRAM`
- **English hint:** Evening programme
- **Note:** Small caps label above the programme list.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Gala.php:129`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Die Program

### 24. KOMP-GALA-BESPREEK: Gala reserve button

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GALA-BESPREEK`
- **English hint:** Reserve Your Seat
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Gala.php:135`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Bespreek nou

### 25. KOMP-GALA-VERLEDE-H3: Past galas card heading

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GALA-VERLEDE-H3`
- **English hint:** Evenings Past
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Gala.php:221`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Vorige Gala-aande

### 26. KOMP-GALA-VERLEDE-TEKS: Past galas card text

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GALA-VERLEDE-TEKS`
- **English hint:** A look back at the galas that came before.
- **Note:** Sits above the row of year chips.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Gala.php:222`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: 'n Blik terug na vorige gala-aande

### 27. KOMP-GALA-ALLES-H1: Heading of the all-gala-photos page

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GALA-ALLES-H1`
- **English hint:** (no design source) heading of /kompetisies/gala/<jaar>/, every photo of one gala, e.g. "Photos of the gala"
- **Note:** Owner-approved page with no design source.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/GalaPage.php:328`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Ink se Presige Gala-aand {year} < We must have the year in this title >

### 28. KOMP-GIDS-KICKER: Directory eyebrow

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GIDS-KICKER`
- **English hint:** Beyond Inkwell
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:121`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Skryf in Afrikaans

### 29. KOMP-GIDS-H2: Directory heading

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GIDS-H2`
- **English hint:** Other writing competitions
- **Note:** Directory of competitions run by others.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:123`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Ander Kompetisies

### 30. KOMP-GIDS-TEKS: Directory intro text

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GIDS-TEKS`
- **English hint:** A vetted list of contests from further afield. We check each listing before it appears here — always confirm details on the organiser's own site before entering.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:124`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Ink onderhou hierdie lys van ander skryfkompetisies, maar is nie self met hierdie kompetisies geassosieer nie. Kontak the instansies wat die onderskeie kompetisies aanbied direk vir die reëls en enige ander navrae.

### 31. KOMP-GIDS-SOEK: Directory search box placeholder

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GIDS-SOEK`
- **English hint:** Search competitions, organisers, regions…
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:140`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Soek vir kompetisies

### 32. KOMP-FOOI-ALL: Fee filter chip: all

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-FOOI-ALL`
- **English hint:** Any entry fee
- **Note:** Three chips: all / free / paid.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:154`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: < Remove. We're not showing entry fees of external competitions on the website. >

### 33. KOMP-FOOI-FREE: Fee filter chip: free (also the fee shown on a free card)

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-FOOI-FREE`
- **English hint:** Free to enter
- **Note:** Used twice.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:155`, `wp-content/plugins/ink-core/src/Competitions/Directory.php:205`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: < Remove. We're not showing entry fees of external competitions on the website. >

### 34. KOMP-FOOI-PAID: Fee filter chip: paid

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-FOOI-PAID`
- **English hint:** Paid entry
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:156`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: < Remove. We're not showing entry fees of external competitions on the website. >

### 35. KOMP-TELLING: Result count noun

- **Reads as:** {getal} ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-TELLING`
- **English hint:** competition / competitions (needs singular and plural; the number stands before it)
- **Note:** Shown as "7 ▢" and updates as filters change. Plural form.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:114`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: kompetisies

### 36. KOMP-EKS-SLUIT: Closing date on a directory card

- **Reads as:** ▢ {datum}
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-EKS-SLUIT`
- **English hint:** Closes
- **Note:** English sample: "Closes".
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:219`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Sluit

### 37. KOMP-EKS-SKAKEL: Link text on a directory card

- **Reads as:** ▢ ↗
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-EKS-SKAKEL`
- **English hint:** Visit organiser
- **Note:** Opens the organiser's site in a new tab.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:223`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: 

### 38. KOMP-LEEG-TITEL: Empty state: title

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-LEEG-TITEL`
- **English hint:** No competitions match that search.
- **Note:** No competition matches the search.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:185`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Geen kompetisies gevind nie.

### 39. KOMP-LEEG-TEKS: Empty state: text

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-LEEG-TEKS`
- **English hint:** Try clearing the filters or searching for something broader.
- **Note:** Below the title. A "Vee filters uit" button follows (already Afrikaans).
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:186`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Vee die filters uit om vir iets meer algemeen te soek.

### 40. KOMP-INDIEN-H3: Submit-a-listing card heading

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-INDIEN-H3`
- **English hint:** Run a writing competition?
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:234`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: < Remove this. We're not going to take unsolicited submissions. >

### 41. KOMP-INDIEN-TEKS: Submit-a-listing card text

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-INDIEN-TEKS`
- **English hint:** Ons lys kompetisies wat skrywers regverdig behandel. Stuur ons die inligting om jou kompetisies te oorweeg.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:235`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: < Remove this. We're not going to take unsolicited submissions. >

### 42. KOMP-INDIEN-KNOP: Submit-a-listing button

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-INDIEN-KNOP`
- **English hint:** Submit a listing
- **Note:** Opens an email.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:238`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: < Remove this. We're not going to take unsolicited submissions. >

### 43. KOMP-GENRE-POETRY: Genre name: poetry

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GENRE-POETRY`
- **English hint:** Poetry
- **Note:** Filter chip and card label. Also an admin dropdown option.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:92`, `wp-content/plugins/ink-core/src/Content/FieldSets.php:777`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Poësie

### 44. KOMP-GENRE-FICTION: Genre name: fiction

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GENRE-FICTION`
- **English hint:** Fiction
- **Note:** Same.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:93`, `wp-content/plugins/ink-core/src/Content/FieldSets.php:778`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Fiksie

### 45. KOMP-GENRE-NON-FICTION: Genre name: non-fiction

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GENRE-NON-FICTION`
- **English hint:** Non-fiction
- **Note:** Same.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:94`, `wp-content/plugins/ink-core/src/Content/FieldSets.php:779`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Nie-fiksie

### 46. KOMP-GENRE-MIXED: Genre name: mixed

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-GENRE-MIXED`
- **English hint:** Mixed
- **Note:** Same.
- **Code:** `wp-content/plugins/ink-core/src/Competitions/Directory.php:95`, `wp-content/plugins/ink-core/src/Content/FieldSets.php:780`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Gemeng

---

## C. Public: InkPols and Uitdagings

### 47. INKPOLS-HUIDIGE: Eyebrow on the current-issue block (not on Tuis)

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] INKPOLS-HUIDIGE`
- **English hint:** Current issue
- **Note:** On Tuis the eyebrow is the existing "InkPols" label.
- **Code:** `wp-content/plugins/ink-core/src/InkPols/CurrentIssue.php:58`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Nuutste uitgawe

### 48. INKPOLS-TERUG: Back link on a single issue page

- **Reads as:** ← ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] INKPOLS-TERUG`
- **English hint:** Back to Archive
- **Note:** Goes back to the InkPols archive.
- **Code:** `wp-content/plugins/ink-core/src/InkPols/SingleIssue.php:88`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Terug na die argief

### 49. UITDAGINGS-H1: Uitdagings page title

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITDAGINGS-H1`
- **English hint:** One prompt, every month
- **Note:** The eyebrow above it already reads "Maandelikse uitdagings".
- **Code:** `wp-content/plugins/ink-core/src/Challenges/Archive.php:481`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: 'n Nuwe uitdaging, elke maand

### 50. UITDAGINGS-INTRO: Uitdagings page intro

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITDAGINGS-INTRO`
- **English hint:** Each month we set a single theme for stories, poems and articles. Entries are judged anonymously and the top three in each category are announced early the following month.
- **Note:** English sample covers: one theme a month, anonymous judging, top three per category announced early next month.
- **Code:** `wp-content/plugins/ink-core/src/Challenges/Archive.php:482`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Elke maand kies ons 'n opdrag vir gedigte, stories en artikels. Alle inskrywings word anoniem beoordeel en die top drie in elke kategorie word spoedig aangekondig.

### 51. UITDAGINGS-VORIGE-H2: Heading above past challenges

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITDAGINGS-VORIGE-H2`
- **English hint:** Past challenges
- **Code:** `wp-content/plugins/ink-core/src/Challenges/Archive.php:518`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Vorige uitdaging


---

## D. Public: header, 404, reading pages, Skryf form, notices

### 52. CHROME-MENU: Mobile menu button (screen-reader label)

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] CHROME-MENU`
- **English hint:** Label of the button that opens and closes the mobile menu: "Menu"
- **Note:** Every phone visitor; read aloud by screen readers. English sample: "Menu".
- **Code:** `wp-content/themes/ink-foundation/patterns/header-main.php:127`
- **Goes to:** `ui-copy-translations.md` › new row (404-bladsy / Kopbalk)

AF: < It doesn't show any text right now, only the burger menu icon. Let's keep it that way. >

### 53. 404-BOODSKAP: 404 page message

- **Reads as:** 404 / ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] — 404 message ("page not found") not yet authored in ui-copy-translations.md.`
- **English hint:** Oops! Page not found *(muted one-line message under the "404" heading)*
- **Note:** One muted line under the big "404".
- **Code:** `wp-content/themes/ink-foundation/patterns/vier-nul-vier.php:32`
- **Goes to:** `ui-copy-translations.md` › new row (404-bladsy / Kopbalk)

AF: Gits! Daardie bladsy bestaan nie.

### 54. 404-TUIS-SKAKEL: 404 page link home

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] — 404 link-home label not yet authored in ui-copy-translations.md.`
- **English hint:** Return to Home *(underlined link to the home page under the message)*
- **Note:** Underlined link under the message.
- **Code:** `wp-content/themes/ink-foundation/patterns/vier-nul-vier.php:36`
- **Goes to:** `ui-copy-translations.md` › new row (404-bladsy / Kopbalk)

AF: Begin gerus op ons tuisblad en vind iets om te lees.

### 55. REAKSIE-OPSTEM: Upvote button name on a response

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] REAKSIE-OPSTEM`
- **English hint:** (accessible name of the upvote button on a response; the button shows a chevron and a count, so this is read by screen readers only)
- **Note:** Screen readers only; the button shows a chevron and a count.
- **Code:** `wp-content/plugins/ink-core/src/Engagement/ResponsesList.php:223`, `wp-content/plugins/ink-core/src/Engagement/ResponseUpvoteController.php:149`
- **Goes to:** `ui-copy-translations.md` › Lees-bladsy / Skryf (new row)

AF: < Where is this? Reading page? >

### 56. REAKSIE-OPSTEM-FOUT: Upvote error message

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] REAKSIE-OPSTEM-FOUT`
- **English hint:** (error text when an upvote is refused: not signed in, not a response, or your own response; the page never offers a control that triggers it)
- **Note:** Shown only if an upvote is refused (not signed in, own response). The page never offers the control in those cases.
- **Code:** `wp-content/plugins/ink-core/src/Engagement/ResponseUpvoteController.php:149`
- **Goes to:** `ui-copy-translations.md` › Lees-bladsy / Skryf (new row)

AF: Jammer, dit het nie gewerk nie

### 57. GEDIG-TOEWYDING-LABEL: Skryf form: dedication field label (poems only)

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] GEDIG-TOEWYDING-LABEL`
- **English hint:** (label of the optional dedication field on the Skryf form, shown for a gedig only; the dedication shows above the poem as an em-dash-framed line)
- **Note:** Optional field. The dedication shows above the poem as a dash-framed line.
- **Code:** `wp-content/themes/ink-foundation/patterns/skryf.php:213`
- **Goes to:** `ui-copy-translations.md` › Lees-bladsy / Skryf (new row)

AF: Opgedra aan

### 58. LEESLYS-LEEG-BODY: My Profiel › Leeslys empty state, body line

- **Reads as:** Nog niks gestoor nie. / ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] — Leeslys empty-state body copy not yet authored in ui-copy-translations.md.`
- **English hint:** Tap the bookmark on any story or poem and it will land here for later.
- **Note:** Heading is already ratified. Only the line below it is open.
- **Code:** `wp-content/plugins/ink-core/src/Engagement/ReadingList.php:122`
- **Goes to:** `ui-copy-translations.md` › My Profiel-bladsy (row per ID; new rows for the NEW ones)

AF: Klik die boekmerk op enige gedig, storie of artikel om 'n leeslys uit te bou.

### 59. KENNIS-LEEG: My Profiel › Kennisgewings empty state

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] — Kennisgewings empty-state copy not yet authored in ui-copy-translations.md.`
- **Note:** NEW ID (not in the old sheet). No ratified line exists yet.
- **Code:** `wp-content/plugins/ink-core/src/Notifications/KennisgewingsSurface.php:306`
- **Goes to:** `ui-copy-translations.md` › My Profiel-bladsy (row per ID; new rows for the NEW ones)

AF: Daar is tans geen kennisgewings nie.


---

## E. Public: My Profiel

Profile page of a signed-in member.

### 60. PROFIEL-REDIGEER-LEUSE-LABEL: "Wysig profiel" dialog: tagline field label

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] — tagline field label not yet authored in ui-copy-translations.md.`
- **English hint:** Tagline *(short field label, under the field for the writer's own one-line tagline shown in the identity strip)*
- **Note:** New field: one short line shown in the identity strip. Naam and Oor my labels already exist.
- **Code:** `wp-content/plugins/ink-core/src/Social/ProfileEditor.php:152`
- **Goes to:** `ui-copy-translations.md` › My Profiel-bladsy (row per ID; new rows for the NEW ones)

AF: Leuse

### 61. PROFIEL-REDIGEER-STOOR: "Wysig profiel" dialog: save button

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] — save button label not yet authored in ui-copy-translations.md.`
- **English hint:** Save *(button)*
- **Code:** `wp-content/plugins/ink-core/src/Social/ProfileEditor.php:163`
- **Goes to:** `ui-copy-translations.md` › My Profiel-bladsy (row per ID; new rows for the NEW ones)

AF: Stoor

### 62. PROFIEL-REDIGEER-KANSELLEER: "Wysig profiel" dialog: cancel button

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] — cancel button label not yet authored in ui-copy-translations.md.`
- **English hint:** Cancel *(button)*
- **Code:** `wp-content/plugins/ink-core/src/Social/ProfileEditor.php:162`
- **Goes to:** `ui-copy-translations.md` › My Profiel-bladsy (row per ID; new rows for the NEW ones)

AF: Kanselleer

### 63. PROFIEL-REDIGEER-BESIG: Status text while saving

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] — saving-status text not yet authored in ui-copy-translations.md.`
- **English hint:** Saving… *(transient status text shown while the save request is in flight)*
- **Note:** Transient, shown next to the buttons. English sample: "Saving…".
- **Code:** `wp-content/themes/ink-foundation/functions.php:1090`
- **Goes to:** `ui-copy-translations.md` › My Profiel-bladsy (row per ID; new rows for the NEW ones)

AF: Aan die werk...

### 64. PROFIEL-REDIGEER-FOUT: Status text if saving fails

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] — save-error status text not yet authored in ui-copy-translations.md.`
- **English hint:** Couldn't save. Please try again. *(transient status text shown if the save request fails)*
- **Note:** Transient. English sample: "Couldn't save. Please try again."
- **Code:** `wp-content/themes/ink-foundation/functions.php:1091`
- **Goes to:** `ui-copy-translations.md` › My Profiel-bladsy (row per ID; new rows for the NEW ones)

AF: Dit het nie gewerk nie. Probeer weer.

### 65. OORSIG-BIO-LEEG: Oorsig › "Oor my" card, no bio yet

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] — "no bio yet" fallback copy not yet authored in ui-copy-translations.md.`
- **English hint:** (No Lovable equivalent — a fallback line shown in the "Oor my" card when the member hasn't written a bio yet, in place of the bio text itself.)
- **Note:** Replaces the bio text when the member has written none.
- **Code:** `wp-content/themes/ink-foundation/patterns/my-profiel.php:257`
- **Goes to:** `ui-copy-translations.md` › My Profiel-bladsy (row per ID; new rows for the NEW ones)

AF: Oor my

### 66. BYDRAES-LEEG: Bydraes tab, no published works

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] — Bydraes-tab empty-state copy not yet authored in ui-copy-translations.md.`
- **English hint:** (No Lovable equivalent — shown in the Bydraes tab when the member has no published works yet, in place of the post list.)
- **Note:** Replaces the list of works.
- **Code:** `wp-content/plugins/ink-core/src/Social/BydraesSurface.php:196`
- **Goes to:** `ui-copy-translations.md` › My Profiel-bladsy (row per ID; new rows for the NEW ones)

AF: Jy het nog geen bydraes geplaas nie. Klik "Begin skryf" regs bo.

### 67. VOLG-VOER-AKSIE: Aktiwiteit tab: connecting phrase

- **Reads as:** {skrywer} ▢ {tipe} · {n}d gelede
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] — Aktiwiteitsvoer se "published a new [type]" aksiefrase nog nie outeur in ui-copy-translations.md nie.`
- **English hint:** published a new {type} *(connecting phrase in the activity-feed row, between the author name and the type label + relative "Nd ago" timing, e.g. "Anja published a new gedig · 3d ago" — the {type}/timing either side are already ratified/established sources, only this phrase is new)*
- **Note:** Example: "Anja ▢ gedig · 3d gelede". Only the phrase is open; type and timing already exist.
- **Code:** `wp-content/plugins/ink-core/src/Social/FollowingFeed.php:291`
- **Goes to:** `ui-copy-translations.md` › My Profiel-bladsy (row per ID; new rows for the NEW ones)

AF: < That's not how Afrikaans works. You will need to change it to accommodate this. > {skrywer} het 'n {tipe} geplaas · {n}d gelede

---

## F. Admin screens (editors only)

Visitors never see these. Field labels in the WordPress admin, one notice, and the media buttons.

### 68. INKPOLS-ADMIN-ONVOLLEDIG: Notice when an issue is sent back to draft

- **Reads as:** ▢: {velde}
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] INKPOLS-ADMIN-ONVOLLEDIG`
- **English hint:** Notice when an issue goes back to draft: "This issue was not published. These fields are still blank"
- **Note:** Followed by a list of the blank fields. English sample: "This issue was not published. These fields are still blank".
- **Code:** `wp-content/plugins/ink-core/src/InkPols/PublishGuard.php:176`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Hierdie uitgawe is nie geplaas nie. Van die velde is steeds leeg.

### 69. UITSLAE-ADMIN-BRIEF: Round field: judges' letter

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITSLAE-ADMIN-BRIEF`
- **English hint:** Round: judges' letter (a blank line starts a new paragraph)
- **Note:** A blank line in the text starts a new paragraph. Say so in the label if you like.
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:520`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Skei paragrawe met 'n blanko reël.

### 70. UITSLAE-ADMIN-VERMELDINGS: Round field: honourable mentions

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] UITSLAE-ADMIN-VERMELDINGS`
- **English hint:** Round: honourable mentions, one per line, Title | Writer
- **Note:** One per line, written Title | Writer. Say so in the label if you like.
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:527`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Eervolle vermelding

### 71. GEDIG-TOEWYDING-ADMIN: Poem field: dedication

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] GEDIG-TOEWYDING-ADMIN`
- **English hint:** (label of the dedication field in the gedig's admin box)
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:581`
- **Goes to:** `ui-copy-translations.md` › Lees-bladsy / Skryf (new row)

AF: Opgedra aan

### 72. KOMP-ADMIN-JAAR: Competition field: year

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-JAAR`
- **English hint:** Year
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:699`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: jaar

### 73. KOMP-ADMIN-UITGAWE: Competition field: edition

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-UITGAWE`
- **English hint:** Edition
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:704`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Edisie

### 74. KOMP-ADMIN-BESKRYWING: Description (used for the edition, each category and the gala)

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-BESKRYWING`
- **English hint:** Description
- **Note:** One label, used in three places. For a category it reads "Kategorie 1: ▢".
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:705`, `wp-content/plugins/ink-core/src/Content/FieldSets.php:727`, `wp-content/plugins/ink-core/src/Content/FieldSets.php:733`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Beskrywing

### 75. KOMP-ADMIN-INSKRYWING: Competition field: entry note

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-INSKRYWING`
- **English hint:** Entry note
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:707`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Inskrywing

### 76. KOMP-ADMIN-NAASWENNER: Competition field: runner-up note

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-NAASWENNER`
- **English hint:** Runner-up note
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:708`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Naaswenner

### 77. KOMP-ADMIN-KAT: Word "Category" in category field labels

- **Reads as:** ▢ 1: …
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-KAT`
- **English hint:** Category
- **Note:** Reads "▢ 1: Ikoon", "▢ 2: Tema" and so on.
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:693`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Kategorie

### 78. KOMP-ADMIN-IKOON: Category field: icon

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-IKOON`
- **English hint:** Icon
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:715`, `wp-content/plugins/ink-core/src/Content/FieldSets.php:719`, `wp-content/plugins/ink-core/src/Content/FieldSets.php:720`, `wp-content/plugins/ink-core/src/Content/FieldSets.php:721`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Ikoon

### 79. KOMP-ADMIN-IKOON-FEATHER: Icon option: feather

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-IKOON-FEATHER`
- **English hint:** Icon option: feather
- **Note:** Dropdown option.
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:719`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Veertjie-ikoon

### 80. KOMP-ADMIN-IKOON-BOOK: Icon option: book

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-IKOON-BOOK`
- **English hint:** Icon option: book
- **Note:** Dropdown option.
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:720`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Boek-ikoon

### 81. KOMP-ADMIN-IKOON-PEN: Icon option: pen

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-IKOON-PEN`
- **English hint:** Icon option: pen
- **Note:** Dropdown option.
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:721`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Pen-ikoon

### 82. KOMP-ADMIN-WOORDLIMIET: Category field: word limit

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-WOORDLIMIET`
- **English hint:** Word limit
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:728`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Woordbeperking

### 83. KOMP-ADMIN-RIGLYNE: Category field: guidelines

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-RIGLYNE`
- **English hint:** Guidelines, one per line
- **Note:** One per line.
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:729`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Riglyne

### 84. KOMP-ADMIN-GALA-NAAM: Gala field: name

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-GALA-NAAM`
- **English hint:** Gala name
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:732`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Naam

### 85. KOMP-ADMIN-GALA-DATUM: Gala field: date

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-GALA-DATUM`
- **English hint:** Gala date
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:734`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Datum

### 86. KOMP-ADMIN-GALA-DEURE: Gala field: doors time

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-GALA-DEURE`
- **English hint:** Gala doors
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:735`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Deure open

### 87. KOMP-ADMIN-GALA-PLEK: Gala field: venue

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-GALA-PLEK`
- **English hint:** Gala venue
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:736`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Venue

### 88. KOMP-ADMIN-GALA-DRAG: Gala field: dress code

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-GALA-DRAG`
- **English hint:** Gala dress
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:737`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Drag

### 89. KOMP-ADMIN-GALA-PROGRAM: Gala field: programme

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-GALA-PROGRAM`
- **English hint:** Gala programme, one item per line
- **Note:** One item per line.
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:738`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Program

### 90. KOMP-ADMIN-KAARTJIE: Word "Ticket" in ticket field labels

- **Reads as:** ▢ 1: …
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-KAARTJIE`
- **English hint:** Ticket
- **Note:** Reads "▢ 1: naam" and "▢ 1: ▢ (prys)".
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:741`, `wp-content/plugins/ink-core/src/Content/FieldSets.php:743`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Kaartjie

### 91. KOMP-ADMIN-PRYS: Ticket field: price

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-PRYS`
- **English hint:** Ticket price (fictional until payments exist)
- **Note:** The price is fictional until payments exist. Say so if you want it in the label.
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:743`, `wp-content/plugins/ink-core/src/Content/FieldSets.php:769`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Kaartjieprys

### 92. KOMP-ADMIN-HOOGTEPUNT: Gala field: highlight

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-HOOGTEPUNT`
- **English hint:** Gala highlight
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:746`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Hoogtepunt

### 93. KOMP-ADMIN-FOTOS: Gala field: photos

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-FOTOS`
- **English hint:** Gala photos
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:749`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Foto's

### 94. KOMP-ADMIN-ORGANISEERDER: Directory field: organiser

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-ORGANISEERDER`
- **English hint:** Organiser
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:765`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Organiseerder

### 95. KOMP-ADMIN-STREEK: Directory field: region

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-STREEK`
- **English hint:** Region
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:766`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Streek

### 96. KOMP-ADMIN-PRYS-EKSTERN: Directory field: prize

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-PRYS-EKSTERN`
- **English hint:** Prize
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:769`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Prys

### 97. KOMP-ADMIN-FOOI: Directory field: entry fee

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-FOOI`
- **English hint:** Entry fee (blank means free)
- **Note:** Blank means free. Say so in the label if you like.
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:770`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Inskrywingsfooi

### 98. KOMP-ADMIN-GENRE: Directory field: genre

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-GENRE`
- **English hint:** Genre
- **Note:** Dropdown; options are the four KOMP-GENRE-* strings.
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:773`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Genre

### 99. KOMP-ADMIN-NOTA: Directory field: note

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-NOTA`
- **English hint:** Note
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:786`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Nota

### 100. KOMP-ADMIN-MEDIA-KIES: Media picker button: choose

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-MEDIA-KIES`
- **English hint:** Choose (media button)
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:346`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Kies

### 101. KOMP-ADMIN-MEDIA-VERWYDER: Media picker button: remove

- **Reads as:** ▢
- **Now shows:** `[NEEDS HUMAN AFRIKAANS] KOMP-ADMIN-MEDIA-VERWYDER`
- **English hint:** Remove (media button)
- **Code:** `wp-content/plugins/ink-core/src/Content/FieldSets.php:347`
- **Goes to:** `ui-copy-translations.md` › Ontwerp-delta (2026-11) (new row)

AF: Verwyder

