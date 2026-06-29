# Caching runbook (Story 18.5, NFR-3 / §14.9)

Two cache layers: **LiteSpeed Cache** (origin page + object cache) and **Cloudflare**
(edge). Both are configured platform layers — INK does not reimplement caching. The
code's only job is *cache correctness*: INK's dynamic/private surfaces opt out so the
cache can be aggressive everywhere else without leaking personalised content or
serving stale form results.

> Source of record: `_bmad-output/planning-artifacts/epics.md` Story 18.5;
> `_bmad-output/project-context.md` (LiteSpeed Cache + Cloudflare are platform layers).

## What the code does (`Ink\Cache\CacheControl`)

- Bypasses the page cache for any INK `admin-post.php?action=ink_*` round-trip
  (contact, report, …) — detected generically via the `ink_` prefix.
- Exposes `add_filter( 'ink_cache_bypass', '__return_true' )` so any personalised
  surface can opt its own request out.
- On bypass it signals **both** layers: `nocache_headers()` + `DONOTCACHEPAGE`
  (generic) and `do_action( 'litespeed_control_set_nocache', … )` (LiteSpeed API;
  inert when LiteSpeed is absent).

## LiteSpeed Cache configuration

1. **Enable** the cache (public cache on). Turn on **Object Cache** (Redis/Memcached
   if the host provides it) and **Browser Cache**.
2. **Logged-in users:** prefer **ESI** (Edge Side Includes) so the public shell is
   cached and only the per-user fragments (login menu, personalised blocks) are
   private — OR exclude logged-in users from the page cache entirely. Decision:
   start with **exclude-logged-in** (simplest, correct) and revisit ESI if logged-in
   traffic is heavy.
3. **Do-not-cache list** — exclude these INK surfaces (the code already signals the
   first; list them here too so LiteSpeed's own rules agree):
   - `admin-post.php` (the `ink_*` actions: `ink_kontak`, `ink_rapporteer`).
   - The personalised INK blocks' host pages / URIs: read counts (`ink/leesgetalle`,
     My Profiel), following feed (`ink/volg-voer`), discovery personalised surfaces
     (`ink/ontdek-vlakke`), pinned-works manager (`ink/vasgespel-bestuur`), the
     rating/report forms. Cache these pages **private** (per-user) or exclude.
   - Any REST/AJAX INK endpoint that returns per-user data.
4. **Purge model:** LiteSpeed auto-purges a post's cache on save/update (covers new
   works, edits, reactions that update denormalised meta). Add a **full purge on
   deploy** to the release runbook (18.7) so template/asset changes take effect.

## Cloudflare edge caching

5. Cache **static assets** aggressively (CSS/JS/images/fonts) at the edge.
6. **Bypass the edge cache** for: `/wp-admin/*`, `admin-post.php`, `wp-login.php`,
   and any request carrying a WordPress logged-in cookie (`wordpress_logged_in_*`).
   A Cache Rule with "Bypass cache" on those matches keeps the edge from serving a
   logged-in member another member's cached page.
7. Set a sane **Edge Cache TTL** for HTML (or "Respect existing headers" so the
   origin's `Cache-Control`/`DONOTCACHEPAGE` signalling wins). Honour the origin's
   no-cache headers for the dynamic surfaces above.
8. Purge the Cloudflare cache on deploy (alongside the LiteSpeed full purge).

## Verify

- Logged out: a public work page returns a cache HIT (LiteSpeed `x-litespeed-cache`
  / Cloudflare `cf-cache-status: HIT`).
- Logged in: My Profiel / following feed return a cache BYPASS/MISS (never another
  user's data).
- Submit the contact/report form: the round-trip is never cached (the notice always
  reflects the latest submission).
