# Migration data

Inputs for the once-off migration. Names and WordPress usernames only (no email addresses), so they are safe to commit.

- `writer-accounts.csv`: `name,user_login`. A by-line as printed on the old result posts (pen names, maiden names, spelling variants) and the account it belongs to. Hand-verified in the July 2026 cleanup. Used by `wp ink derive-tiers`.
- `writer-aliases.csv`: `variant,canonical`. Misspellings and variants of one by-line, mapped to its canonical form (the canonical form is then looked up in `writer-accounts.csv` or among the site's users).

Add a row here when `derive-tiers` reports an unresolved writer that you identify.
- `inkpols-uitgawes.csv`: one row per InkPols uitgawe, owner-maintained. `slug` and `naam` (the months, e.g. `Julie/Augustus 2026`, shown as the eyebrow), `titel` and `beskrywing` (filled in by hand), `datum` (the first month of the issue, used as the publish date, so the newest row is the current issue), `pdf` and `voorblad` (paths under `wp-content/uploads/`, matching the prod attachments; the cover is also the social image), `ou_url` (the old prod Page, redirected to the new URL), and one title + writer each for the featured Gedig, Storie and Artikel. On prod, the February issue's PDF was uploaded twice; the file listed is the one its page used.
- `result-post-authors.csv`: `login`. The moderator accounts that publish the challenge result posts ("... Kommentaar en uitslae"). Their posts are kept as winners announcements instead of being filed as submitted writing, and are read for gradering. Users who can `edit_others_posts` also count, but `Inkmoderator` is only a subscriber after the user-reclassification step, so the list is what matters.
