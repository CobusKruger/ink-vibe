# Troubleshooting: WordPress Memory & Performance Outages

Diagnosing memory exhaustion, CPU/RAM saturation, and "site spins forever / hangs
then recovers" incidents on the **live INK WordPress site (`ink.org.za`)**.

**Environment this guide assumes**

- Host: **NameHero** shared/cloud, **cPanel + CloudLinux (LVE) + LiteSpeed**.
- Access available: **cPanel**, **phpMyAdmin**, **File Manager**, **WP Toolkit**,
  **Advanced Cron Manager** plugin. **No SSH** (times out — firewall) and **no
  cPanel Terminal / WP-CLI**. All steps below therefore use the web UI only.
- DB table prefix: **`wpjj_`** (adjust the SQL if the prefix ever changes).

> This guide was written from a real incident (2026-07-07) where the site
> repeatedly hung and recovered for hours. Root cause: an **orphaned MailPoet
> recurring background job** churning inside Action Scheduler. See
> [Case study](#case-study-the-2026-07-07-outage) at the end.

---

## 1. Triage — classify the symptom first

The symptom routes everything:

| Symptom | Most likely area |
|---|---|
| White screen (WSOD) | PHP fatal (memory or bad code) |
| HTTP 500 | PHP fatal or `.htaccess`/server misconfig |
| HTTP 502 / 504 | PHP-FPM/LiteSpeed down or timing out |
| "Error establishing a database connection" | MySQL down / wrong creds |
| **Spins forever / hangs, then recovers** | **Resource exhaustion (CPU/RAM/connections)** |
| Loads intermittently | Hitting the CloudLinux LVE resource cap under load |

**Key diagnostic insight:** access logs are written *when a request completes*.
If the site "spins forever" **and the access log stops updating**, requests are
arriving but never finishing — the backend is wedged (resource exhaustion), not
a fast-failing error. A site that throws 500s (which complete) but later *hangs*
(which don't) has degraded from erroring into exhaustion.

---

## 2. Check server resources (CloudLinux)

Storage on this plan is effectively unlimited, so **disk-full is usually NOT the
cause here** — the real ceiling is **CPU and RAM (the CloudLinux LVE cap, ~2 GB
RAM)**.

- cPanel → **Metrics → Resource Usage** → open the **Snapshot / faults** view.
  This shows *which* limit you are hitting over time:
  - **PMEM** (physical memory) → memory-bound (matches 512 MB per-request fatals).
  - **CPU** → CPU-bound (a runaway loop or heavy processing).
  - **EP** (entry processes) / **NPROC** → too many concurrent requests (a flood).
- Being pinned at **100% RAM and ~99% CPU** is not normal idle behaviour — that
  *is* the outage mechanism. A healthy site idles well under the cap.

---

## 3. Read the logs (this is where the answer usually is)

Look at **error** logs, not access logs.

- **`wp-content/debug.log`** — if `WP_DEBUG_LOG` is on. Open via File Manager.
  - ⚠️ This file can grow to **gigabytes**. Do not open a multi-GB file in a
    browser editor — it will hang. Delete/rotate it and turn logging off (below).
  - Benign noise to ignore: `_load_textdomain_just_in_time ... triggered too
    early`, `ltrim(): Passing null ... deprecated`, `Undefined array key`,
    `WP_Error could not be converted to int`. These are cosmetic; they are not
    what crashes the site (but their *volume* can bloat the log).
- **Account error log** — cPanel → **Metrics → Errors** (last ~300 lines only).
- **Per-directory `error_log` files** — PHP writes a file literally named
  `error_log` in the directory where the error occurred. Check (enable "Show
  Hidden Files" in File Manager):
  - `public_html/error_log`
  - `public_html/wp-content/error_log`
  - `public_html/wp-content/plugins/<plugin>/error_log`
- **Raw access logs** — cPanel → **Metrics → Raw Access Logs** (full, gzipped;
  the panel's "latest visitors" view is truncated).

**The signature of this class of bug** — a per-request memory fatal in the hook
dispatcher:

```
PHP Fatal error: Allowed memory size of 536870912 bytes exhausted
(tried to allocate ...) in wp-includes/class-wp-hook.php on line 388
```

512 MB is the per-request limit; a *single* request exhausting it means something
allocates without bound in one request — often a scheduled/background task run via
a hook.

**Correlate a fatal to a request** — find what URL was being served at the fatal's
timestamp in the raw access log (search for e.g. `07/Jul/2026:19:41:4`).

---

## 4. Rule out self-inflicted debug instrumentation

Before chasing plugins, check for leftover debugging code that is *itself* the
load. In this site's history someone left a **must-use plugin** that did real harm:

- Look in **`wp-content/mu-plugins/`** (auto-loaded, invisible in the normal
  plugin list and in WP Toolkit).
- Also check bootstrap/drop-ins: top of `wp-config.php`, and
  `wp-content/{advanced-cache,object-cache,db,sunrise}.php`.

Red flags to remove on sight:

- `add_action('all', ...)` — runs the callback on **every** hook fire (thousands
  per request) = constant CPU tax.
- `remove_all_actions()` / `remove_all_filters()` used as a "circuit breaker" —
  destructively strips plugin callbacks mid-request; causes a cascade of
  downstream `WP_Error` / null / "too early" warnings that look like separate bugs.
- Code writing custom trace logs (e.g. `cron-tracker.log`) on every request.

To disable: rename the file to `<name>.php.off` in File Manager, then recycle PHP
(cPanel → **Select PHP Version** → re-save) if changes don't take effect
(OPcache / LiteSpeed `lsphp` workers can serve stale bytecode until recycled).

---

## 5. Investigate WP-Cron and Action Scheduler

WordPress has no real scheduler. By default it runs **WP-Cron on every front-end
visit**. On a busy site (hit ~once/second) this puts scheduled/background tasks on
the hot path — so a single heavy or looping task runs constantly and can pin
CPU/RAM or blow the 512 MB memory limit.

**Check whether WP-Cron fires on every visit** (File Manager → view `wp-config.php`):

```
grep for: DISABLE_WP_CRON
```

If it is **not** defined (or is `false`), WP-Cron is firing on every request. This
is often paired with a **system cron** that also hits `wp-cron.php`:

```
/usr/bin/wget -q -O - https://www.ink.org.za/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

That system cron should run every few minutes (`*/5 * * * *`). If it is set to
midnight-daily, scheduled tasks barely run and then batch up.

**Action Scheduler** is a background-job queue bundled in WooCommerce, MailPoet,
and many other plugins. It reschedules `action_scheduler_run_queue` every minute.
A log line like:

```
Cron reschedule event error for hook: action_scheduler_run_queue,
Error code: could_not_set, Error message: The cron event list could not be saved.
```

is a **symptom of overload** (WordPress can't write the `cron` option under load),
not necessarily corruption. Investigate the Action Scheduler tables directly.

> **Important gotcha:** Action Scheduler stores its jobs in its **own tables**
> (`wpjj_actionscheduler_*`), *separate* from WP-Cron's `cron` option. So a
> runaway/orphaned Action Scheduler job **will not appear in Advanced Cron
> Manager** (which only reads the `cron` option). You must inspect the AS tables
> in phpMyAdmin.

### Diagnostic SQL (phpMyAdmin → SQL tab)

These are the exact statements used to identify the root cause. Run in order.

```sql
-- 1. Is the WP-Cron `cron` option itself bloated? (healthy = a few KB, e.g. ~6 KB)
SELECT LENGTH(option_value) AS bytes, autoload
FROM wpjj_options
WHERE option_name = 'cron';
```

```sql
-- 2. Do the Action Scheduler tables exist, and how bloated are they by status?
SHOW TABLES LIKE '%actionscheduler%';

SELECT status, COUNT(*) AS cnt
FROM wpjj_actionscheduler_actions
GROUP BY status;
```

```sql
-- 3. Which plugin/group is flooding the queue?
SELECT g.slug, COUNT(*) AS cnt
FROM wpjj_actionscheduler_actions a
JOIN wpjj_actionscheduler_groups g ON a.group_id = g.group_id
GROUP BY g.slug
ORDER BY cnt DESC;
```

```sql
-- 4. How bloated is the logs table? (thousands of failed actions => huge; slows every AS query)
SELECT COUNT(*) FROM wpjj_actionscheduler_logs;
```

```sql
-- 5. WHICH task is failing — names the exact broken hook/job
SELECT hook, COUNT(*) AS cnt
FROM wpjj_actionscheduler_actions
WHERE status = 'failed'
GROUP BY hook
ORDER BY cnt DESC
LIMIT 10;
```

```sql
-- 6. Confirm what is actually active, and whether a suspect plugin is even installed
SELECT option_value FROM wpjj_options WHERE option_name = 'active_plugins';
```

```sql
-- 7. Inspect a specific suspect hook's current state (pending recurrence vs failures)
SELECT status, COUNT(*) FROM wpjj_actionscheduler_actions
WHERE hook = 'mailpoet/cron/daemon-trigger'   -- replace with your suspect hook
GROUP BY status;
```

**Interpreting the results**

- A **large `bytes` with `autoload = yes`** on the `cron` option would mean it is
  loaded into memory on every request (a memory suspect). In this incident it was
  healthy (~6 KB), which *ruled out* that mechanism.
- A **huge `failed` count** in the AS tables, dominated by one `group` slug and one
  `hook`, names the culprit.
- If the offending plugin is **absent from `active_plugins` and its folder is
  gone** but its actions persist, you have an **orphaned recurring action** that
  reschedules itself every minute forever. Nothing in any plugin UI can stop it —
  you must delete the rows (below).

---

## 6. Remediation

### Immediate — clear a runaway / orphaned queue

**Preferred:** WooCommerce → **Status → Scheduled Actions** → filter by *Failed*
→ bulk delete. If that UI times out on tens of thousands of rows, use SQL (do it
once load is down, e.g. after deactivating the offending plugin):

```sql
-- Delete a specific orphaned plugin's actions (adjust the LIKE pattern)
DELETE FROM wpjj_actionscheduler_actions WHERE hook LIKE 'mailpoet/%';

-- Delete the failed backlog generally
DELETE FROM wpjj_actionscheduler_actions WHERE status = 'failed';

-- Clear orphaned log rows left behind
DELETE l FROM wpjj_actionscheduler_logs l
LEFT JOIN wpjj_actionscheduler_actions a ON l.action_id = a.action_id
WHERE a.action_id IS NULL;
```

> In the 2026-07-07 incident, deleting these rows **reclaimed ~1.8 GB RAM and cut
> CPU roughly in half** immediately.

If the offender **is** an installed/active plugin, deactivate it first (WP Toolkit
→ Plugins, or wp-admin → Plugins) before purging, so it stops scheduling new jobs.

### Structural — stop WP-Cron running on every visit

This is the durable fix that prevents any *future* misbehaving task from getting
every-second amplification.

1. In `wp-config.php` (File Manager → Edit), **above** the
   `require_once ... wp-settings.php` line:
   ```php
   define('DISABLE_WP_CRON', true);
   ```
   Do **not** disturb the DB credentials or the `wp-settings.php` require line.
2. Set the **system cron** to run every 5 minutes: cPanel → **Cron Jobs** →
   `*/5 * * * *`.

### Hygiene

- Turn off debug logging in production: `define('WP_DEBUG_LOG', false);`
  (or `WP_DEBUG` false). Delete/rotate any multi-GB `debug.log`.
- Configure Action Scheduler retention so backlogs auto-purge (default is 30
  days; shorten if needed).
- Deactivate integrations you don't use (e.g. Pinterest for WooCommerce) if they
  generate recurring failures.

---

## 7. Monitoring queries — re-run these in future

Run these periodically (and especially after any incident) to confirm the site
stays healthy. Adjust the `wpjj_` prefix if it changes.

```sql
-- A. Overall Action Scheduler health. `failed` should stay at/near 0.
--    If it climbs into the hundreds/thousands again, an ACTIVE plugin is
--    misbehaving — use query C to name it.
SELECT status, COUNT(*) FROM wpjj_actionscheduler_actions GROUP BY status;
```

```sql
-- B. Logs table size — should stay small (was reduced from 80,031 -> ~1,599
--    after cleanup). Growth back into the tens of thousands = a task failing
--    in a loop and logging heavily.
SELECT COUNT(*) FROM wpjj_actionscheduler_logs;
```

```sql
-- C. Which task is failing (empty result = nothing failing = good).
SELECT hook, COUNT(*) AS cnt
FROM wpjj_actionscheduler_actions
WHERE status = 'failed'
GROUP BY hook
ORDER BY cnt DESC
LIMIT 10;
```

```sql
-- D. Check a known-suspect recurring hook's recent runs (do the newest ones
--    complete, or still fail?). Example: WooCommerce unpaid-order cleanup.
SELECT hook, status, scheduled_date_gmt
FROM wpjj_actionscheduler_actions
WHERE hook = 'woocommerce_cancel_unpaid_orders'
ORDER BY scheduled_date_gmt DESC
LIMIT 5;
```

```sql
-- E. Sanity-check the WP-Cron option stays small (~few KB).
SELECT LENGTH(option_value) AS bytes, autoload
FROM wpjj_options WHERE option_name = 'cron';
```

Also glance at cPanel → **Metrics → Resource Usage → faults** after any incident
to confirm you are no longer hitting the PMEM/CPU/EP caps.

---

## 8. Runaway hook / filter recursion (memory exhausted in the hook dispatcher)

A distinct failure mode from a backlog: a **single request** exhausts the full
512 MB, and the fatal lands in **`wp-includes/class-wp-hook.php`** (line ~341 or
~388 — the `apply_filters`/`do_action` callback loop). That signature means a
**hooked callback re-enters its own hook**, recursing until memory runs out. It is
NOT about data volume — it can fire with an empty database.

**How to recognise it:**

- The fatal is in `class-wp-hook.php` **plus** a plugin/theme file that calls a
  WordPress function which itself fires the hook that plugin/theme is filtering.
  Example seen here (all three frames are one recursion loop):
  ```
  joinup-core/inc/plugins/checkout/checkout.php   ← filter callback calls get_items()
  woocommerce/includes/abstracts/abstract-wc-order.php ← get_items() fires the filter
  wp-includes/class-wp-hook.php:341               ← filter dispatches back to the callback → repeat
  ```
- Spikes are **triggered by an action, not a schedule** — it detonates only when
  the offending code path runs (an order read, a checkout, a page view), so it can
  lie dormant for hours/days and look like a random spike.
- `tried to allocate <small> bytes` after already using the full limit — the
  classic "one more stack frame tipped it over" recursion tell.

**The anti-pattern to look for** — a filter callback that calls the very function
whose filter it is hooked to, without an unconditional break:

```php
// Hooked to 'woocommerce_order_get_items', which get_items() fires:
add_filter( 'woocommerce_order_get_items', array( $this, 'extend' ), 10, 3 );

public function extend( $items, $order, $types ) {
    if ( sizeof( $types ) == 1 && in_array( 'line_item', $types ) ) {
        $extra = $this->supported_post_types();   // returns [] when nothing registers types
        $extra[] = 'line_item';
        $items = $order->get_items( $extra );      // re-fires the SAME filter...
        // when $extra === ['line_item'], the guard above is TRUE again → infinite recursion
    }
    return $items;
}
```

The guard (`sizeof==1 && line_item`) was *meant* to break the loop by appending
extra types — but when the "extra types" list is **empty**, the re-call passes the
same `['line_item']` and re-enters forever.

**Fix:** never re-enter unconditionally. Only re-call when the state genuinely
changes the arguments:

```php
$extra = $this->supported_post_types();
if ( ! empty( $extra ) ) {          // no empty-list re-entry
    $extra[] = 'line_item';
    $items = $order->get_items( $extra );
}
```

**How to confirm the trigger:** find what state makes the "extra" list empty
(here, `supported_post_types()` returns `apply_filters('...', array())` and
**nothing in the codebase hooks that filter** — so it is *always* empty; verify
with a repo-wide grep for the filter name). Then find what makes the offending
path run — here, a **single stale `wc-pending` order from 2016** that
`woocommerce_cancel_unpaid_orders` kept trying to cancel, calling `get_items()`
on it each run. On this memberships-only site (no products, so `get_unpaid_orders`
should be irrelevant) that one junk order was the entire trigger.

**Editing plugin/theme code:** back up the file first (`file.php` → `file.php.bak`)
and note the change is overwritten on plugin/theme update — re-apply until the
underlying code is replaced. Test on the local **ink-staging** site before copying
the file up to production, then recycle PHP.

---

## Case studies

### Day 1 (2026-07-07): orphaned Action Scheduler job

- **Symptom:** site "spun forever" and recovered repeatedly for hours; earlier
  500s; access logs frozen during hangs; account pinned at 100% RAM / 99% CPU.
- **Red herrings ruled out:** disk (unlimited), the `cron` option (healthy 6 KB),
  the flood of benign `debug.log` notices.
- **Aggravating factor:** a leftover `mu-plugins` "circuit breaker" using
  `add_action('all')` + `remove_all_actions()` — removed (see §4).
- **Root cause:** **MailPoet had been uninstalled** (folder gone, not in
  `active_plugins`) but left an **orphaned every-minute recurring action**
  `mailpoet/cron/daemon-trigger` inside Action Scheduler's own tables. It
  self-rescheduled and failed forever (no callback), producing **17,869 failed
  actions** (of 26,142 total), an 80,031-row logs table, and — amplified by
  WP-Cron firing on every visit — the CPU/RAM saturation and 512 MB memory fatals.
  It did **not** show in Advanced Cron Manager because AS jobs live outside the
  `cron` option.
- **Fix:** deleted the orphaned + failed AS rows (reclaimed ~1.8 GB RAM, halved
  CPU); applied `DISABLE_WP_CRON` + `*/5` system cron.
- **Lesson:** when uninstalling a plugin that uses Action Scheduler, confirm its
  recurring actions are gone from `wpjj_actionscheduler_actions`, not just that
  the plugin folder was removed.

### Day 2 (2026-07-08): latent recursion, surfaced by a stale order

- **Symptom:** ~24 h calm at a healthy ~80 MB baseline, then a sudden spike back to
  2 GB / 100% CPU; phpMyAdmin threw `Access denied for user 'cpses_...'` (a
  starved-MySQL session-user symptom, cleared by re-logging into cPanel). Fatals
  in `abstract-wc-order.php`, `joinup-core/.../checkout.php`, and
  `class-wp-hook.php`.
- **Red herrings ruled out:** an order backlog (HPOS table empty; legacy table had
  exactly **1** order), and `woocommerce_cancel_unpaid_orders` itself (its
  "failed after 300 seconds / unknown error" was the AS watchdog reacting to the
  OOM-killed worker — a *bystander*, not the cause).
- **Root cause:** an **infinite recursion** in the theme plugin `joinup-core`
  (`order_item_types_global_extend()` on the `woocommerce_order_get_items` filter),
  live whenever `supported_post_types()` is empty — which, per grep, it *always*
  is here. See §8. It had been latent for years; it only fires when order line
  items are read.
- **Trigger:** a single stale **`wc-pending` order (ID 932) from 2016** that
  `woocommerce_cancel_unpaid_orders` kept picking up and calling `get_items()` on.
  (Timing note: nothing changed in config 24 h prior; the most likely reason for
  the calm window is that Day 1's AS cleanup had removed the scheduled
  `cancel_unpaid_orders` action, which WooCommerce later re-created — this part is
  inference, not confirmed.)
- **Fix (belt-and-suspenders, any one suffices):** (1) added an `! empty()`
  recursion guard to the theme method; (2) cancelled the stale order
  (`UPDATE wpjj_posts SET post_status='wc-cancelled' WHERE ID=932;` — SQL avoids
  triggering `get_items()`); (3) memberships-only site, so disabled Hold-stock so
  `cancel_unpaid_orders` stops being scheduled at all.
- **Lesson:** a `class-wp-hook.php` memory fatal with an empty database means
  **recursion, not volume**. And a task failing on a trivial workload is usually a
  *bystander* to an OOM elsewhere in the same worker — read the actual fatals in
  `error_log`, don't trust the failing task's name.
