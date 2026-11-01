# Handoff: winner cleanup

**Written:** 2026-10-31. **For:** an agent with no knowledge of earlier sessions.

**The job:** finish a small, bounded list of winner rows for writers who are still active, then stop. **This runs before the homepage round** (`homepage-dev-round.md`), not beside it.

**The follow-on job:** §7, the Gradering fix and winner importer, is separate work that follows the cleanup. Start it only when the owner asks.

## 1. The rules that frame everything (owner, 2026-10-31)

- **The hand-curated files are the source of truth.** Add to them. **Do not write another way of building them**; the owner is tired of mechanisms being reinvented. If the matcher misses something, fix `build-winner-lists.php` or edit the CSV.
- **Don't obsess over old wins.** If a member has posted nothing in a year, their Gradering and win counts are best effort. If the winning post can be identified, mark it; if not, move on. Nobody reviews those rows.
- **People can't type Afrikaans reliably, so comparisons must not care.** Accents get dropped or swapped, even in people's own names, and the article 'n arrives as ŉ, ñ, ń, 'n, ’n, n, N and more. Writers also add their grade, class, word count and project to titles, in every form. All comparison goes through `Ink\Migration\TextKey`, which removes all of that. Never compare titles or names any other way.

## 2. The process

```powershell
$env:Path = [Environment]::GetEnvironmentVariable('Path','Machine') + ';' + [Environment]::GetEnvironmentVariable('Path','User')
node tools/local.mjs wp eval-file docs/migration-tools/build-winner-lists.php > tmp/gen.txt
```

Running it is always safe. Each run:
- reads result posts not yet in `result-posts.csv` and appends their wins;
- fills a blank post or account if one can now be found;
- refreshes round IDs, activity and counts, and rewrites the review lists;
- **never changes a filled post, account, category, grade or position, and never changes a status you set to `ok`**;
- keeps the previous lists in `../migration-data/backup/`.

There is no rebuild. It refuses to run if the lists are missing. Tested 2026-10-31: a second run left all three lists byte-identical, hand edits survived, and a removed result post came back identical. `WL_OUT=<folder>` runs it against a copy.

To record a decision, edit the data:

| Decision | Where |
|---|---|
| A win's post, account, grade, position or status | That row in `wenners.csv` |
| By-line → account | `writer-accounts.csv` |
| Spelling variant → by-line | `writer-aliases.csv`. Check here before asking about a name |
| Deleted account | `writer-deleted.csv` |
| "Same title, not the same writer" | `review/accounts-confirmed-*.csv`; those aren't asked again |
| Result post's round, or `not-results`, `outside` | `result-posts.csv` |
| Round has no results | `challenges.csv` |

The columns `activity` (`active`, `inactive`, `no-account`) and `source_category` are maintained by the run. **`source_category` identifies a round;** `challenge_id` changes with every migration and is refreshed from it.

**Status in `wenners.csv`:** `ok`; `review` (a person should look: posted in the last year, or no account and a result from the last year); `best-effort` (inactive, nobody looks). All three **count as wins**.

## 3. What's left

Generated 2026-10-31. 1,611 wins: 1,482 ok, 87 best-effort, 42 review, across 12 writers.

1. **`review/wins-without-a-post.csv`: 29 rows** (16 poems). 24 have suggested posts. Several are obvious from the suggestions, for example Nix1984's "LAAT EK JOU VERTEL" (three candidates, pick one) and MCHorak's. Others are probably generic titles: Hakieshart's "haikoe (silwer)", where the win names a poem inside the post. Read the post; if you can't tell, leave it and say so.
2. **`review/july-list-mismatches.csv`: the 14 rows with status `review`,** sorted first: 7 the parser missed, 5 with differing details, 2 the parser read that aren't on the list. Default: the July list wins. The other 50 are inactive writers; leave them.
3. **`result-posts.csv`: 2 rows.** 9375 Lente 2016 (weak evidence, looks right) and 15518 "Julie FAK" (its round is mistitled "Junie 2016 - FAK projek", dated 2017-06).
4. **January 2021 (post 45211):** not on the July list, and positions are assumed from list order. Check only the rows for active writers.
5. **Grade changes:** Morné du Preez (exactly 20 wins → Goud) and Helena Mellet (exactly 5 → Silwer) sit on a threshold, so one rejected win changes their grade. Check both if they're active. `Pat` (#434) has no posts and drops to none; ignore it. They may be the same person as Patricia.C (#515, "Pat Viljoen", 35 posts).
6. **Not built:** merging #69 into #168, including its 3 posts (`WL_SAME_PERSON` handles the matching until then).

Then give the owner a short list of anything you couldn't settle, and stop. Don't go looking for more.

## 4. Owner's rulings: settled, don't reopen

**Counting**
- Gradering is Gedigte-only. All wins are recorded; only poetry wins promote a poet.
- A win counts even with no post, and even if the old post gives no grade: "if it's a win, it counts".
- **Competitions are not challenges**: they work differently, have different prizes, and are never counted.
- Results posted from July 2026 on are new, so absence from the July list means nothing.

**Matching**
- A result comes after its round closes and almost certainly within three months.
- Winning posts are filed under their round, which confirms both post and round.

**People**
- Palet = Suzette Strydom: #168. Viooltjie = Carma Shaw.
- Francois Meyer (#46, 13 posts up to 2019-09-10) and GEDAGTES (#66) are different writers, both printed as "Francois Meyer".
- There are two Wilmas: #668 has posted; the other uses the username "Meisie".
- Loret Zwarts is #2411; the posts are trashed on deleted account #921, so those wins count with no post.
- Anzé Bezuidenhout (#36) is the site owner. She removed her own posts on purpose. Not reviewed.
- `anonymous` (#1853, "anoniem") earns no wins.
- `writer-deleted.csv` is partly the owner's best guess ("I think"); reverse a row if evidence appears.

## 5. How to work with the owner

- Short answers: bullets or tables. No over-explaining.
- **Verify before claiming.** Earlier claims here were wrong and the owner caught them, including "over 97%" (it scored only rows the parser read) and asking about competitions already ruled on.
- **Assume the matcher is at fault before the post.** The owner keeps finding "unfindable" posts. Fix general gaps once, in one place, with a test where possible.
- After any matcher change, **copy `wenners.csv` first, then read every changed row** before reporting a number. `tmp/diff-wins.php` compares against `tmp/wenners-before.csv`; `tmp/` is scratch, so recreate it if missing.
- When you need decisions, give a short file with a `y/n` column, display names, known aliases and links. Preserve the answers.

## 6. Environment and traps

- **Windows 11.** `node tools/local.mjs wp …`; plain `wp` can't connect. Git Bash has no node or PHP on PATH, so use PowerShell for those. Table prefix on dev: `wpjj_`.
- **Dev is a migrated copy of prod** at `https://nuwe-ink.local`. It sees plugin code only after `node tools/deploy-to-local.mjs --apply`, and the generator uses deployed classes such as `TextKey`.
- **Dev grades don't change from this.** Nothing reads the lists until the importer (§7) is built.
- **`../migration-data/` stays out of the repo.** After changing `docs/migration-data.md`, copy it there as `README.md`.
- **Line endings:** `writer-aliases.csv` is CRLF, `writer-accounts.csv` and `writer-deleted.csv` are LF. Git Bash `sed -i` can change them.
- **Write generator output to a file.** `Select-Object -First` kills the pipe and reports exit 255.
- **Tests:** `composer test:unit`. `MigrateAllTest` line 118 already fails on Windows (quoting). PHPCS has one old error in `LegacyResultsParser.php`.
- **Git:** nothing is pushed; run `git status` first. Commit only when the owner asks, with the message in `tmp/commit-msg.txt` and `git commit -F`, and **no `Co-Authored-By` trailers**.
- **Docs:** update `docs/migration-data.md` alongside any rule or process change.

## 7. After the cleanup: Gradering fix and winner importer

Moved here from `homepage-dev-round.md` (owner, 2026-10-31). The homepage winner cards and the Uitslae page read this work's output: on dev today there are **0 placements and 0 entry-Gradering snapshots**, and 102 result posts linked to rounds.

**Fix Gradering (D7, D13).**
- Gradering is **Gedigte-only**. Every win in every category is recorded; only poetry wins promote a poet.
- `Challenges/Pools.php` splits every category by Gradering: wrong. Gedigte keep per-Gradering pools; Storie and Artikel get one pool each. Consumers: `Placements::arrange`, `Coverage`, the ingestion and parser, and their tests.
- `Ingestion::awardPromotions` counts all categories: wrong. Count Gedigte only.
- A Meester is handled as Goud on the podium. There are none today.

**Make the migration read the reviewed winner lists** instead of parsing, in `wp ink migrate-all`. Inputs: `wenners.csv`, `result-posts.csv`, `challenges.csv` (see `docs/migration-data.md`). They are hand-curated and are the source of truth. **Do not build another way to match wins**, and compare any typed title or name through `Ink\Migration\TextKey`, which handles dropped accents and every spelling of 'n. Rows with status `best-effort` and `review` count as wins, the same as `ok`. Requirements:
- Write placements (`ink_entry_placement`) and the entry-Gradering snapshot (`ink_entry_gradering`) on matched posts.
- **A win counts even with no post** (over 150 rows have none, and many never will, because writers who stopped posting are best effort), so win records can't live only in post meta. Choose a store and record why.
- A row with no account stays recorded and promotes nobody.
- Replace the source `derive-tiers` counts from. Thresholds are 5 wins, then 15 more (`PromotionEngine`). Check the result against `review/grade-changes.csv`.
- Set round links from `result-posts.csv`; the two `not-results` posts lose theirs.
- **Trap: `challenge_id` is not stable.** Rounds are created by the migration with fresh IDs (68657–68796 on dev today), and those IDs shift between runs, more so now that 12 competitions no longer migrate. Match rounds on the `source_category` column, which is `ink_uitdaging_source_category`, the prod category term ID. `post_id` and `user_id` come from prod and are stable.
- The card's "Nth challenge win" is a **lifetime** count derived from the records, because `ink_tier_win_count` resets on promotion.
