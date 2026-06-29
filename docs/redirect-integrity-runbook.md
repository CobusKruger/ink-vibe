# Redirect-integrity runbook (Story 18.2, NFR-4)

Old URLs must survive migration: every changed URL issues a **single** 301 to the
live target, and dead URLs are **tracked**. The redirect map is *built* by Story
16.7 (`wp ink generate-redirects`); this story *verifies* it. Two halves — the
static map audit (code, runs anywhere) and the live crawl + 404 log (need a
running staging site).

> Source of record: `_bmad-output/planning-artifacts/epics.md` Story 18.2;
> Epic-17 retro carry-forward ("18.2 owns the 301-verify crawl"); project-context
> (Redirection = platform plugin; redirects mandatory on every URL-changing
> reassignment).

## 1. Static map audit (code — `Ink\Migration\RedirectIntegrity`)

Run on staging after `wp ink generate-redirects`:

```
wp ink verify-redirects          # audit only: counts chains / loops / empty targets
wp ink verify-redirects --fix    # flatten chains so each old URL → ONE 301 to the final target
```

- **Chain** — old URL A → B where B is itself a redirect (a 301-to-301 hop). This
  happens when post B moved into the slot post A vacated. `--fix` collapses A
  straight to B's final destination. Search engines penalise redirect chains.
- **Loop** — a target that points back to its own key. Reported, never followed
  (the 16.7 serve handler also guards loops at runtime).
- **Empty target** — a key that would redirect nowhere; `--fix` drops it.

Re-run `wp ink verify-redirects` after `--fix` and confirm `Aanstuurkaart is heel`.

## 2. Live 301-verify crawl (staging — needs a running site)

Before DNS cutover, crawl **every recorded old URL** and assert the response chain:

1. Export the old-URL list — the keys of the `ink_migration_redirects` option
   (`wp option get ink_migration_redirects --format=json`).
2. For each old URL, request it (no-follow first) and assert:
   - status `301` (not 302, not 200-direct, not 404),
   - the `Location` resolves to a `200` in **one** hop (no chain — should already be
     guaranteed by step 1's `--fix`).
3. Record any old URL that 404s or chains; fix the source (re-run 16.7 for that
   post, or add a manual Redirection rule) and re-crawl until zero failures.

A simple harness: `wget --spider -r` over the old-URL list, or a small script that
curls each and checks `http_code` + `redirect_url`. Wire this into the 18.8 CI/cron
staging job alongside the live English-leak crawl (they share the page list).

## 3. 404 tracking (Redirection plugin — configured, not reimplemented)

"404s are tracked" is the **Redirection** plugin's built-in 404 logger:

- Enable Redirection's **Log 404 errors** on staging and production.
- After cutover, review the 404 log weekly for the first month: any recurring old
  URL that escaped the migration map gets a manual Redirection rule (or a 16.7
  re-run if it's a CPT-reassignment miss).
- Do **not** reimplement a 404 logger in ink-core — Redirection owns this
  (project-context platform plugin). ink-core's `RedirectGenerator::maybeRedirect()`
  only *rescues* a 404 that matches the migration map; everything else falls through
  to Redirection's log.

## Cutover sequence (where this fits)

… → posts + 301 redirects (`wp ink generate-redirects`, 16.7) →
**`wp ink verify-redirects --fix` (18.2)** → InkPols → sponsors → rebuild nav →
**live 301-verify crawl + enable Redirection 404 log (18.2)** → smoke-test →
DNS cutover → **monitor Redirection 404 log (18.2)**.
