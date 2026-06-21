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

## Deferred from: code review of Story 2.0 — terminology-label-registry (2026-06-21)
- **Block Bindings `resolve()` renders blank/raw key to front-end HTML** [`I18n/Bindings.php:69-71`] — missing/empty/typo'd `args.key` coerces to `''` → returns `''` with no production signal. Matches the documented "returns the key if unregistered" intent; track for a future visible-fallback / key-normalisation decision.
- **No "called before `init`" guard on `Terms::label()` / `ink_term()`** [`I18n/Terms.php:125`] — a too-early caller triggers `_doing_it_wrong` on WP 6.7+; harmless under the no-`.mo` policy (literal still returned). Pre-existing pattern.

## Deferred from: code review of Story 2.1 — register-cpts (2026-06-21)
- **`map_meta_cap => true` without `capability_type`/`capabilities`** [`Content/PostTypes.php:200`] — all nine CPTs inherit default `post` caps; the flag gives a false impression of isolation. Custom capability mapping explicitly deferred by the story; revisit when caps land.
- **No `Terms::has()` assertion in the CPT registrar** [`Content/PostTypes.php` labels()] — a missing/typo'd Terms key silently registers a raw machine key as the CPT label in production. All keys exist today; pre-existing registry fail-soft design.
- **CPT archive/rewrite slug collision + activation-only flush** [`Content/PostTypes.php`] — `/biblioteek/`, `/opleiding/`, `/inkpols/` have no page/rewrite collision guard; post-activation slug changes 404 until permalinks re-saved. Owned by Epic 16 (redirects).
- **`/inkpols/` archive URL lacks a cited migration-plan source** [`Content/PostTypes.php:173`] — only biblioteek/opleiding are documented; record the inkpols archive URL mapping in the migration plan.

## Deferred from: code review of Story 2.2 — register-taxonomies (2026-06-21)
- **`ster_gradering` rewrite slug hand-typed `'ster-gradering'` (hyphen)** [`Content/Taxonomies.php:550`] — breaks the `rewrite => self::CONST` single-source the other three taxonomies use; constant edits won't propagate to the URL, and the four URLs are formatted inconsistently. Standardise when term-archive templates land (Epic 8).
- **Taxonomies use default term caps (`manage_categories`), no `capabilities` arg** [`Content/Taxonomies.php`] — controlled-vocabulary integrity depends on future member roles lacking the term-add cap; unenforced at this layer. Owned by the capability/roles work (Epic 3/5).
- **`uitdagingsrondte`/`ster_gradering` deliberately not attached to `uitdaging` CPT** [`Content/Taxonomies.php:546-551`] — intentional (entry record authoritative, AD-5) but undocumented/untested as a deliberate non-attachment. Document/verify in Epic 12.
- **No `Terms::has()` assertion in the taxonomy registrar** [`Content/Taxonomies.php:586-626`] — a renamed/removed registry key silently ships a raw machine key into admin/term-archive titles in production. Pre-existing registry fail-soft design.

## Deferred from: code review of Story 2.3 — user-meta (2026-06-21)
- **Tier-write gate denies for all users until role-mapping lands** [`Content/UserMeta.php:62`, `Kernel/Capabilities.php:23`] — `MANAGE_TIERS` is granted to no role (stub); gated writes are impossible (incl. admins) until Epic 5. Latent/by-design; no writer exists yet.
- **`auth_callback` ignores `$object_id` (no per-target scope)** [`Content/UserMeta.php:62-86`] — once `MANAGE_TIERS` is granted, a holder can write any user's tier. Confirm authorization scope when the promotion UI lands (Epic 5).
- **`ink_tier_promoted_at` has no datetime-format validation** [`Content/UserMeta.php:83-86`] — `sanitize_text_field` only; a malformed timestamp would persist/serve over REST. Validate in the `Tiers::promote()` writer (Epic 5).
- **`brons` default only resolves on the WP registered-default read path** [`Content/UserMeta.php:71`] — Epic-5 consumers should read tier via a typed accessor, not raw `get_user_meta`. Provide with the consumer.

## Deferred from: code review of Story 2.4 — cpt-admin-field-sets (2026-06-21)
- **REST vs meta-box capability divergence on `uitdaging`/`borg` meta** [`Content/FieldSets.php:207, 276, 296`] — field `auth_callback` gates on `MANAGE_CHALLENGES`/`MANAGE_SPONSORS` (granted to no role yet), while meta-box `save()` gates on `edit_post`. REST/block-editor writes blocked for all users (incl. admins) until role-mapping; meta-box round-trip works. Reconcile when caps are role-mapped (Epic 5).

## Deferred from: code review of Story 2.5 — term-images-native (2026-06-21)
- **No attachment-validity check on term-image save** [`Content/TermImages.php` save()] — `absint` accepts any positive integer, so a non-existent / non-image / deleted attachment ID persists and `Api::termImageId()` returns it as valid. Front-end rendering is out of scope here; validate (`wp_attachment_is_image`) where the image is consumed (Epics 8/11) or add a save-time check then.
