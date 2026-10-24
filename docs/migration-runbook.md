# INK migration runbook

Rebuild the new site from a fresh copy of production. The database is disposable and every human decision is a committed file, so a rehearsal and the real cutover are the same procedure.

## Before you start

- A fresh prod copy imported into a staging site (Local or the staging host). Orphan-reassignment clean-up on prod is done first (owner task).
- `wp-config.php` on staging: `WP_ENVIRONMENT_TYPE` is not `production`, and `INK_MAIL_SINK` is set to an inbox you control. Until then no mail leaves the site (the sink drops it).
- `docs/data-cleanup/winners/tier-import.csv` is current.
- `ink-core` from the branch to be released is active; nothing else needs activating.

## Run

```
wp ink migrate-all --tiers=docs/data-cleanup/winners/tier-import.csv
```

It runs, in order: sanitise, users, tiers, verify-subscriptions, library-training, posts, challenges, challenge-entries, inkpols, clean-shortcodes, rebuild-navigation, follows, options, term recount, generate-redirects, verify-redirects --fix, verify-media.

- It stops at the first failing step. Fix the cause and run the same command: steps that succeeded are skipped.
- `--from=<step>` re-runs from that step on. `--reset` forgets all stored outcomes.
- Read the summary. A step flagged `silent` or `all-zero` did nothing; decide whether that is right (for example no InkPols on a test copy) before going on. The last attempt hid 0 library items and 0 challenges this way.
- Note the total run time. It decides the cutover window (below).

## Check after a run

1. `wp ink report-result-posts --file=tmp/result-posts.csv`: about 12 a year, all by Anze or Inkmoderator. Review the list; the confirmed list is what gradering relies on.
2. Spot-check counts: posts still in `skryfwerk` should be small (staging copy: about 150, mostly result posts and posts filed only under a challenge category).
3. `wp ink audit-production`, `wp ink audit-translations`, `wp ink verify-redirects` (crawl), `wp ink verify-media`.
4. Open a few of each: a Gedigte shelf poem, a collection (Flipvis), a Woordeboeke item, an Opleiding module, an old challenge and its entries, `/ontdek/`.
5. Old URLs: `/kategorie/lede-bydraes/...` and `/biblioteek/projek-wenners/...` redirect to Ontdek; the dropped `plagiaat-letterdiefstal` duplicate redirects to the Opleiding copy.
6. Challenge flow: pick an old challenge, generate the judge emails, confirm they arrive only in the sink inbox.

## Cutover

1. Rehearse at least once on a fresh copy and once more on a second copy (the second run proves it is repeatable). Time both.
2. If the timed run is short: announce a short freeze (no new posts or signups), take the final prod copy, run, verify, flip DNS.
3. If it is too long: use the planned incremental pass for posts and redirects only (a `--since` watermark; not built yet, build it only if the timing demands it). Memberships and orders are never merged incrementally.
4. DNS flip only after the checks above pass.

## Rollback

Production is never modified by any step: the pipeline works on a copy. Rolling back is leaving DNS where it is. After the flip, rollback is pointing DNS back to the old host (keep it untouched for the agreed period).

## Where the decisions live

| Decision | File |
| --- | --- |
| Category slug to content type, genre markers | `ink-core/src/Migration/PostReclassifier.php` |
| Legacy Biblioteek/Opleiding roots to shelf/collection/skill, skips | `ink-core/src/Migration/LegacyContentMap.php` |
| Section redirects, dropped duplicates | `ink-core/src/Migration/StaticRedirects.php` |
| Challenge rounds (descendants of `projekte` / `kompetisie`) | `ink-core/src/Challenges/Migration.php` |
| Writer tiers | `docs/data-cleanup/winners/tier-import.csv` |
