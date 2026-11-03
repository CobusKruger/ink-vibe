# Handoff: Gradering fix and winner importer (built)

**Rewritten 2026-11-03.** The earlier version described a hand-curated winner-list workflow (`wenners.csv` and friends, `build-winner-lists.php`). The owner dropped it: those files and that script are gone, and the old lists sit in `../backup-data/`, which nothing reads.

**For:** an agent with no knowledge of earlier sessions. **Status (2026-11-03): built and run on the dev copy; not yet proved on a real refresh.** What exists, and what is still open, is in section 2.

## 1. Rules (owner)

- **Wins come from the old challenge result posts**, read by `LegacyResultsParser`. No hand-curated list is an input. The migration reads the prod database and three files in `../migration-data/` (`writer-accounts.csv`, `writer-aliases.csv`, `inkpols-uitgawes.csv`), nothing else; `MigrationDataTest` enforces it. See `docs/migration-data.md`.
- **Best effort for people who have posted nothing in a year.** A win count slightly off for them is fine. If the winning post can be identified, mark it; if not, move on. The win still counts.
- **Compare typed titles and names only through `Ink\Migration\TextKey`** (dropped accents, every spelling of 'n, grades added to titles).
- **Competitions are not challenges** and never count as challenge wins.
- **Gradering is Gedigte-only.** Every win in every category is recorded; only poetry wins promote a poet.
- A win counts even with no post. A win with no account stays recorded and promotes nobody.
- Results posted from July 2026 on are new; the older ones are history.

## 2. What was built, and what is open

**Built (2026-11-03), with unit tests:**
- **Gradering fix (D7, D13).** `Challenges/Pools.php`: Gedigte keep per-Gradering pools; Storie and Artikel are one pool each, keyed `|<category>`. `Placements::arrange`, `Coverage` ties, `WinnerBanner` and `Ingestion::awardPromotions` follow: a Storie or Artikel win is recorded and placed but promotes nobody.
- **Competitions are not rounds.** The round list never applied `ChallengeRound::isCompetition()`; `Challenges\Migration::isChallengeCategory()` does now (the category name holds `kompetisie`). That is 8 rounds: **128 expected**, not 136. INK-Slypskool is a challenge. A competition's result post is never read for wins (`TierDeriver`) or linked to a round (`ResultPostLinker`).
- **The winner importer**, `Migration\WinnerImporter` (`wp ink import-winners`, the `winners` step of `migrate-all`, after `result-posts`), with the pure rules in `Migration\WinnerMatcher`. No new input file. For every win the parser reads, in every category: the round is the result post's stored link or, with none, the round that holds most of the winners' posts (that link is then stored); the winning post is the writer's own post with that title through `TextKey` (exact, one holds the other, or a near match filed under the round); the post gets its placement and, for a Gedig under a grade heading, its entry-Gradering snapshot; a record is stored on the round whether or not a post or an account was found.
- **The store is `WinRecords`** (meta `ink_uitdaging_wen` on the round, one JSON row per win). It holds a win with no post and no account, and live results commits write to it too. The lifetime count is a writer's rows over all rounds. `RoundResults` shows records the placements do not (a win with no post, or a post that is not an entry of the round), so the Uitslae page and the Tuis cards read real data.
- `TierDeriver` lists every win (`win_rows`); `derive-tiers` is unchanged for Gradering.

**Result on the 2026-11-02 dev copy (run 2026-11-03):** 1,573 wins from 123 result posts; 1,571 recorded (two have position 4); about 1,420 winning posts found; 14 wins with no account; none without a round; 23 result posts got their round from their winners. Against the old hand-curated list (now in `../backup-data/`): 1,556 of 1,571 wins identical, and where that list had a winning post, the importer found the same post in 1,401 of 1,413 cases. About 27 of 142 writers have a different lifetime count, mostly by 1 or 2. The two writers printed as "Francois Meyer" (`#46`, `#66`) were mixed up by 7 wins; see the rulings below for the fix.

**Open:**
- Prove it on a real refresh (`docs/refresh-worklist.md`). The dev copy it ran on still had the 8 competition rounds and the old test data.
- Known gaps, accepted as best effort: result posts the parser reads only partly (the first Amakeia round, lines with two winners).
- `Ingestion::commit` and the live results flow were only changed for the Gedigte-only rule; they are covered by unit tests, not exercised on a real round.
- The two `not-results` apology posts (January and February 2019) still link to their rounds; they hold no winners.

## 3. Rulings about people (kept; their by-lines live in the writer maps)

- Palet = Suzette Strydom: `#168`. Viooltjie = Carma Shaw.
- Francois Meyer (`#46`, 13 posts up to 2019-09-10) and GEDAGTES (`#66`) are different writers, both printed as "Francois Meyer". **Owner's solution (2026-11-03):** "Francois Meyer (Gedagtes)" and the plain "Francois Meyer" stay `#66`. `#46` goes by Francois Terblanche, so on production the owner edits the two result posts that carry his wins (`/wenners-november-2017-oop-projek/`, `/uitslae-september-2019-verplasings-projek/`) to print "Francois Terblanche"; `writer-accounts.csv` maps that name to `FrancoisMeyer`.
- There are two Wilmas: `#668` has posted; the other uses the username "Meisie".
- Loret Zwarts is `#2411`; her posts are trashed on deleted account `#921`, so those wins count with no post.
- Anzé Bezuidenhout (`#36`) is the site owner and removed her own posts on purpose.
- `anonymous` (`#1853`) earns no wins. Deleted accounts (Agie, Charne Harding and others the owner named) have their wins recorded and promote nobody.

## 4. Environment and traps

- **Windows 11.** `node tools/local.mjs wp …`; plain `wp` can't connect. Git Bash has no node or PHP on PATH, so use PowerShell for those. Table prefix on dev: `wpjj_`.
- **Dev sees plugin code only after `node tools/deploy-to-local.mjs --apply`.**
- **Tests:** `composer test:unit`. Four integration tests fail on this machine with or without any change (`CommentInsertionTest`, `SubmissionGateTest`, two in `TierWriteTest`). PHPCS has about 45 errors, an old baseline: do not chase it.
- **Git:** nothing is pushed; run `git status` first. Commit only when the owner asks, with the message in `tmp/commit-msg.txt` and `git commit -F`.
- **Docs:** update the docs alongside any rule or process change, and correct anything stale you find.
- **Owner:** short answers, bullets or tables. Verify before claiming. Assume the matcher is at fault before the post.
