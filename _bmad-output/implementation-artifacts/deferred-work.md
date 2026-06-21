# Deferred Work

Consolidated `defer` findings from code reviews. Each item is real but not actionable in its originating story (pre-existing, by-design, or owned by a later story/epic).

## Deferred from: code review of Epic 1 stories (2026-06-21)

### Story 1.2 — typography-system
- **templateParts scope drift** [`theme.json`] — diff retitles header/footer parts to Afrikaans (Kopstuk/Voetstuk) and adds a new `section`/Seksie-omhulsel template part. Not typography; belongs to Story 1.4. Resolves on commit reconciliation (shared uncommitted working tree).

### Story 1.4 — global-templates-template-parts
- **Stale "git diff --stat empty" verification claims** [story file] — Tasks 5/6 + Debug Log assert front-page.html / header-main.php / footer-main.php are unchanged, but git shows diffs (the `lock`/`templateLock` additions owned by Story 1.6, applied later in the same working tree). Documentation-accuracy only.

### Story 1.6 — block-locking-strategy
- **Lock-object miscount in Dev Agent Record** [story file:54,181] — narrative says "34 lock objects"; actual count is 35 (the per-file breakdown sums to 35). Cosmetic off-by-one in prose; implementation is correct.

### Story 1.8 — comment-disable-filters
- **`removeCommentSupport()` misses post types registered after `init` @ PHP_INT_MAX** [`Comments.php`] — by-design; the `comments_open` filter is the runtime guard (AC-1).
- **`ink-core.php` registers `Notifications\Module()` not in 1.8 File List** — pre-existing Story 1.12 working-tree edit bleeding into shared bootstrap; untracked dir prevents git isolation.

### Story 1.10 — locale-af-i18n-scaffolding
- **`is_admin()` guard excludes REST-delivered admin surfaces** [`I18n.php`] (MEDIUM) — block-editor/REST admin strings resolve via site locale `af` rather than forced `en_US` for staff. Matches the spec's explicit §14.14/AC-2 `is_admin()` scoping; no ink-core admin REST surfaces exist yet. Revisit when admin REST surfaces ship (Epic 2+).

### Story 1.11 — test-harness-scaffold (all LOW, by-design or pre-disclosed)
- **`.wp-env.json` core ref `WordPress/WordPress#7.0`** may not resolve at first `wp-env start` — execution deferred per AC-6; AC targets "WP 7.0+".
- **Integration suite runs on host**, not via `wp-env run tests-cli`; real-WP loading lands in Story 18.8 (`->skip()` placeholder today).
- **`qossmic/deptrac-shim ^1.0` possible deprecation** — swap pre-disclosed; ruleset is runner-agnostic.
- **PHPStan level 5 may need a baseline** on first run — pre-disclosed in config + notes.
- **`.wp-env.json` omits vetted platform plugins** named in AC-2 — AD-4/Epic-16 + 18.8 buildout concern.

### Story 1.12 — form-letter-notification-capability-foundation
- **Mail-header injection on merged subject via `wp_mail`** [`Notifier.php:57-60`] — PHPMailer encodes subjects and no untrusted merge values are reachable yet; revisit when consumer epics feed user-controlled merge data.
- **AC-3 event/Action-Scheduler seam is documentation-only** [`Module.php:46-54`] — the `Api::send` facade is the seam; concrete subscriptions correctly scoped to consumer epics.
