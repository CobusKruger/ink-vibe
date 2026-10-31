# Handoff: winner cleanup

**Written:** 2026-10-31. **For:** an agent with no knowledge of earlier sessions.

**The job:** finish the reviewed lists of challenge wins, so they can replace parsing as the single source for winner marks, grades and lifetime win counts. You work on data and the list generator. Building the migration that reads these lists belongs to the separate handoff `homepage-dev-round.md`.

## 1. Background

The old site, ink.org.za, announced challenge results as free-form moderator posts. A parser reads them, and a hand-verified list from July 2026 covers results up to May 2026. Neither is complete, so the generator combines them, matches each win to an account and a post, and writes lists for the owner to review.

Read first: `CLAUDE.md`, `../CLAUDE.md`, `docs/migration-data.md`, and `docs/migration-tools/build-winner-lists.php` (all of it, including the constants at the top).

## 2. Files

| Path | What it is |
|---|---|
| `../migration-data/wenners.csv` | One row per win |
| `../migration-data/result-posts.csv` | Each result post and its round |
| `../migration-data/challenges.csv` | Each round |
| `../migration-data/review/wins-without-a-post.csv` | Wins with no matched post; Anzé's sorted last |
| `../migration-data/review/july-list-mismatches.csv` | Where parser and July list disagree, before July 2026 only |
| `../migration-data/review/grade-changes.csv` | Grades that change if every draft win stands |
| `../migration-data/review/accounts-to-confirm.csv` | Same title on another account. Empty now |
| `../migration-data/review/accounts-confirmed-2026-10-31.csv` | The owner's y/n answers, already applied |
| `../migration-data/writer-accounts.csv`, `writer-aliases.csv` | By-line → account, and spelling variant → by-line |
| `../data-cleanup/winners/winner-list.csv` | The July list. Read-only |
| `../live-import/` | Prod SQL, plugins, uploads. Never commit |

`../migration-data/` is the owner's working folder: edit it directly, and keep it out of the repo. After changing `docs/migration-data.md`, copy it to `../migration-data/README.md`.

**Regenerate (PowerShell):**

```powershell
$env:Path = [Environment]::GetEnvironmentVariable('Path','Machine') + ';' + [Environment]::GetEnvironmentVariable('Path','User')
$env:FORCE='1'; node tools/local.mjs wp eval-file docs/migration-tools/build-winner-lists.php > tmp/gen.txt; Remove-Item Env:FORCE
```

**`FORCE=1` overwrites `wenners.csv` and every review file, including anything the owner typed.** Before each regenerate, compare timestamps or diff against a saved copy. If the owner has edited a file, carry their answers into the generator or a data file first, as was done with `accounts-confirmed-2026-10-31.csv`.

## 3. Numbers now

Generated 2026-10-31 from the dev database.

| | |
|---|---:|
| Wins | 1,611 |
| To review | 139 (102 no post, 37 have a post but a source disagreement) |
| Wins without a post | 166: 112 others, 54 Anzé's |
| The 112, by category | Gedigte 55, Rubrieke 21, Verhale 17, Artikels 7, Algemeen 6, Meningstukke 4, Prosa 2 |
| Of the 112 | 18 on deleted accounts, 61 with suggested posts |
| July mismatches | 64: 24 parser missed, 16 January 2021 post not on the list, 15 parser read but not on the list, 9 details differ |
| Result posts | 126: 122 ok, 2 review, 2 not-results |
| Rounds | 128, none left to review |
| Grade changes | Morné du Preez → Goud (exactly 20 wins), Palet #168 → Silwer, GEDAGTES #66 → Silwer, Helena Mellet → Silwer (exactly 5), Francois Meyer #46 → Brons, Suzette Strydom #69 → none |

Morné and Helena sit exactly on a threshold, so a single rejected win changes their grade. Tell the owner.

## 4. Owner's rulings: settled, don't reopen

**Counting**
- Gradering is Gedigte-only. All wins are recorded; only poetry wins promote a poet.
- A win counts even if its post can't be found, and even if the old post gives no grade: "if it's a win, it counts".
- **Competitions are not challenges**: they work differently, have different prizes, and are never counted or listed. The two outside competition result posts (14391, 14557) are excluded.
- Results posted from July 2026 on are new, so absence from the July list means nothing.

**Matching**
- A result comes after its round closes and almost certainly within three months (the generator uses a 150-day look-back).
- Winning posts are filed under their round, which confirms both post and round.
- Posts 31881 and 32763 are apologies for late results, holding no winners.
- Februarie 2019 and Herfs published no results.

**People**
- Palet = Suzette Strydom: use #168. Merging #69 and its 3 posts isn't built.
- Viooltjie = Carma Shaw. These are in `writer-aliases.csv`; **check the aliases before asking the owner about a name.**
- Francois Meyer (#46) and GEDAGTES (#66) are different writers, both printed as "Francois Meyer". #46 posted only 13 posts up to 2019-09-10: 1038, 1555, 15391, 15394, 15445, 15629, 15722, 16944, 23043, 25950, 26261, 26299, 36860.
- "Maria Magdelena" is Francois Meyer's post 36860.
- "Jong oumens" is Patricia.C's (#515, post 26062).
- There are two Wilmas: #668 has posted; the other uses the username "Meisie".
- Loret Zwarts is #2411; the posts are trashed on deleted account #921, so those wins count with no post.
- Anzé Bezuidenhout (#36) is the site owner. She removed her own posts on purpose and no longer takes part, so her missing posts are expected. **Low priority.**
- `anonymous` (#1853, "anoniem") earns no wins. Charne Harding's account was deleted and her posts moved there, so her win is recorded with no account.
- Deleted accounts, wins recorded with nobody to promote: Agie, Charne Harding. The owner said "I think" for Arnold van Zyl, Yvonne de Kock, Jaco Geldenhuys, Lynelle Clark and Magret Cordier, so reverse those if evidence appears. Only Yvonne's and Magret's poems could matter.
- Not the same writer, despite the same title on another account: Caren Kearley/charleine "Gestroop", Estelle Rubow/Viooltjie "Haikoe 3", Marsofine/Palet "Uitgespreide siervere", Charne Harding/anonymous.

## 5. The work, in order

1. **Fix the unstable round IDs first.** `challenge_id` in the lists and `WL_NO_RESULTS` in the generator (68692, 68701) are IDs the migration creates, and they'll change at the next migration, more so now that 12 competitions no longer migrate. Use the prod category term in `ink_uitdaging_source_category` instead, add it as a column, and tell whoever is on the dev round. `post_id` and `user_id` come from prod and are stable.
2. **Cut the 112 others, starting with the 55 poems,** since only those affect grades. For each: use the row's suggestions, read the result post, read the writer's posts in that round. Fix general gaps in the matcher rather than single rows where you can. Recent examples of gaps: accents, and extra text after the title. **The owner keeps finding that "unfindable" posts are findable, so assume the matcher is at fault before the post.**
3. **Resolve "Liefde" (Pieter Mostert, 2024-11-18):** two candidates, #60979 (Italian sonnet) and #60989 (has a word count). Read the result post and use the row's category to decide.
4. **Work through the 64 mismatches.** Priorities: the **15 the parser read that aren't on the list**, since they may not be wins at all, including 7 from December 2017 at position 3 and ungraded rows from 2019-04-10 and 2019-06-04. Then the 9 detail differences; the July value wins by default, flagged for the owner. Then the 24 the parser missed.
5. **January 2021 (post 45211, 16 rows):** not on the July list, and positions are **assumed from list order**. "Oordenking (skrywer onbekend)" stays unparsed. Ask the owner to confirm.
6. **Check the two result posts under review:** 9375 Lente 2016 (weak evidence but looks right), and 15518 "Julie FAK", whose round is mistitled "Junie 2016 - FAK projek" and dated 2017-06.
7. **"Konform" (2017)** is assigned to #46 with no post. Check it against the list of 13 posts, and ask if unresolved.
8. **Hand the owner a short list of what's left,** then mark the lists reviewed once they agree.
9. **Build what's missing in the tooling:** merging new result posts into a reviewed `wenners.csv` without overwriting it. It's needed before cutover, because new results keep arriving. Also merging #69 into #168, including the 3 posts.

**Not yours:** making the migration read these lists, `Ingestion::awardPromotions`, `Pools`, and anything visual.

## 6. How to work with the owner

- Short answers: bullets or tables. No over-explaining.
- **Verify before claiming.** Earlier claims here were wrong and the owner caught them: "over 97%" (it scored only rows the parser read), 325 posts missing "mostly deleted" (inflated by a title bug, the deletion claim unchecked), and asking about competitions that had already been ruled on.
- After any matcher change, **diff the regenerated `wenners.csv` against a saved copy and read every changed row** before reporting a number. `tmp/diff-wins.php` does this but depends on `tmp/wenners-before.csv`; `tmp/` is scratch, so recreate it if missing.
- When you want a decision, give a short file with a `y/n` column and a link on each row, and show display names and known aliases, so that the owner doesn't have to look things up. Preserve the answers.
- Check the rulings above and the aliases before asking anything.

## 7. Environment and traps

- **Windows 11.** Use `node tools/local.mjs wp …`; plain `wp` can't connect. Git Bash has no node or PHP on PATH, so use PowerShell for those. Table prefix on dev: `wpjj_`.
- **Dev is a migrated copy of prod** at `https://nuwe-ink.local`. It sees plugin code only after `node tools/deploy-to-local.mjs --apply`. The generator is run from the repo but uses deployed classes, such as `ChallengeRound::isCompetition`.
- **Dev grades don't change from any of this.** Nothing reads the lists yet.
- **Line endings:** `writer-aliases.csv` is CRLF, `writer-accounts.csv` is now LF (a harmless earlier side effect). Git Bash `sed -i` can change them.
- **Don't cut output with `Select-Object -First` on the generator:** it kills the pipe and reports exit 255. Write to a file.
- **Avoid stray `cd` in Bash:** it changes the working folder for later calls.
- **Tests:** `composer test:unit`. `MigrateAllTest` line 118 already fails on Windows (quoting). PHPStan is clean; PHPCS has one old error in `LegacyResultsParser.php`.
- **Git:** nothing is pushed; run `git status` first. Commit only when the owner asks, with the message in `tmp/commit-msg.txt` and `git commit -F`, and **no `Co-Authored-By` trailers**.
- **Docs:** update `docs/migration-data.md` alongside any rule or process change.
- **This job runs before the homepage round, not beside it** (owner, 2026-10-31). Don't touch the theme or the challenge code.
