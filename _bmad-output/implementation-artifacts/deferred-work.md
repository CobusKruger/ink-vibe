# Deferred Work

Consolidated `defer` findings from code reviews. Each item is real but not actionable in its originating story (pre-existing, by-design, or owned by a later story/epic).

## Deferred from: code review of Epic 5 Group D (5.6 guardrails) (2026-06-26)

- **Conflation behavioural test covers only `PurchaseActivation`, not `LifecycleEmails`** [`tests/Unit/Tiers/ConflationGuardrailTest.php`] — `LifecycleEmails` (second handler on `wc_memberships_user_membership_status_changed`) has zero `update_user_meta` today (no live leak); add a transition drive through it when convenient.
- **Guardrail globs are single-level + skip top-level `src/*.php`** [`ConflationGuardrailTest.php`] — `Tiers`/`Entitlement` are flat dirs today; the scan degrades the moment a nested subdir appears. Recurse (or assert the flat assumption) when a module grows subdirectories.
- **Structural scan retains string literals (substring `toContain`)** [`ConflationGuardrailTest.php`] — a future `__()` label containing "Tiers"/"Entitlement" would false-FAIL (safe failure, not false confidence). Tighten to a symbol/namespaced match if it bites.

## Deferred from: code review of Epic 5 Group C (5.4+5.5+5.9+5.10) (2026-06-26)

- **`winnerLabel('')` emits a leading space** [`Tiers/Api.php`] — `winnerLabel(Tier::Goud, '')` → `" Goud-wenner"`. The Epic-12 winner surface (not built) supplies the period; primitive contract is "non-empty period". Trivial `trim()` if it matters.
- **`wenner` registry key vs glossary slug `winner`** [`Tiers/I18n Terms.php`, `afrikaans-terms.md:102`] — registry keys on the Afrikaans label `'wenner'` while the glossary's machine slug column reads `winner`. Functionally correct; verify the slug-vs-label keying convention with the glossary author.
- **`winsNeededSubtext()` at/above-threshold clamp masks "promotion pending"** [`Tiers/PromotionEngine.php` progressFor] — `max(1, wins-count)` shows "1 nodig" for a writer already at/over threshold (only reachable via a non-reset counter: manual DB edit, legacy value, or a future direct `recordWin` path). `promote()` resets on every promotion, so the normal path can't reach it. Revisit if a non-reset accumulation path appears.
- **Blank congratulation email if toggle-override ON but template unregistered** [`Tiers/PromotionEmails.php` + Notifications 1.12] — load-order edge ("first wiring wins") + a stored `enabled=true` override could let `Notifier::send` proceed with empty subject/body. Fix belongs in the 1.12 Notifier (suppress when unregistered even under an override), not Tiers. Speculative; toggle is OFF by default.
- **`PromotionEmails::onTierPromoted()` has no idempotency guard** [`Tiers/PromotionEmails.php`] — a double-fired `ink/tier_promoted` would send two emails. Synchronous single-fire today; revisit if the event becomes async/retried (12A.3 Action-Scheduler ingestion).

## Deferred from: code review of Epic 5 Group B (5.2+5.8) (2026-06-26)

- **`PromotionEngine::award()` large-batch overshoot discards surplus wins** [`PromotionEngine.php`] — `award($id, 100)` on a Brons writer promotes one step and resets to 0, losing the surplus past the first threshold. "One step per call" is the documented spec intent (no grade-skip); whether a backlog should promote multiple steps is owned by the not-yet-built R2 ingestion caller (Story 12A.3).
- **`awardWins()` is not idempotent on `$challenge_id` re-runs** [`PromotionEngine.php`] — no dedupe on the challenge link, so replaying the same challenge result double-counts wins. Idempotency is Story 12A.3's concern by spec design (the engine's contract is "caller supplies the win count").
- **`Api::promote()` does not verify `$user_id` exists** [`Api.php`] — `forUser()` returns Brons for an unknown user, so a non-no-op `promote()` could write orphan usermeta + an audit row for a phantom user. Low-impact (both callers supply real ids); revisit if a programmatic caller can pass unvalidated ids.

## Deferred from: code review of Epic 5 Group A (5.1+5.3+5.7) (2026-06-26)

- **`PromotionLogEntry::createdAt` exposes a raw GMT string with no timezone marker** [`PromotionLog.php`/`PromotionLogEntry.php:866`] — storage is correctly GMT (`current_time('mysql', true)`), but any consumer rendering the value directly shows GMT as if local (SAST is UTC+2, a 2-hour skew). Display-boundary concern; owned by the downstream Graderingsgeskiedenis display consumer (Story 5.4+ / profile templates).
- **`reason` column is unbounded `text` and unsanitised** [`PromotionLog.php:727,733`] — an over-length staff `reason` fails `$wpdb->insert` under MySQL strict mode (→ `record()` returns false → silent audit-row loss, see the related decision item). The `reason` source is the staff admin UI (Story 5.2), where length/validation belongs.
- **5.7 spec baseline-count documentation drift** [`5-7-…md`] — AC-3 cites "baseline 305", the 5.3 hand-off ends at 294, the Change Log reaches 310. Plausibly consistent if 5.2 landed between, but unverifiable from the diff. Documentation-accuracy only; the suite passes.
- **Epic-5 story commits carry `Co-Authored-By` / `Claude-Session` trailers** [commits `6b7222d`…`3c0429c`] — CLAUDE.md now prohibits these (`attribution.commit: ""`), but the prohibition landed in the final branch commit `62b694f`, so all 10 story commits predate enforcement. Commit metadata, not in the diff body; not worth a history rewrite. Note only — keep new commits trailer-free.

## Deferred from: code review of 3-5-social-login-r6 (2026-06-22)

- **`/meld-aan` hardcoded footer link** [`auth-register.php`] — root-relative path breaks in subdirectory/multisite installs (same class as the 3.5 privacy link, which WAS fixed). PRE-EXISTING — predates Story 3.5 (the "Reeds 'n rekening? Meld aan" footer). Fold into a future auth-pattern hardening pass; use `wp_login_url()` / `home_url()`.
- **DRY: duplicated social-section markup** [`auth-login.php` + `auth-register.php`] — ~25 lines of identical separator/divider/buttons/consent markup live in both patterns; the review patches had to be applied twice. Extract to a single `ink_foundation_render_social_section()` bridge. Deferred (not a bug) because block-pattern render behaviour of an extracted helper is verified only by E2E (Story 18.8), not the unit suite.
- **No real `/privaatheidsbeleid` privacy page** — the POPIA consent link currently 404s; authoring the Afrikaans privacy page is a pre-launch content gate.
- **Rendered social buttons + real OAuth round-trip** — requires the live vetted social-login plugin + OAuth-app credentials (Composer-assembled, git-ignored, deploy-time). E2E (Story 18.8).

## Deferred from: code review of 3-6-optional-manual-approval-backstop-r6 (2026-06-22)

- **REST / application-password / XML-RPC auth bypass** [`Approval.php:929`] — the login gate filters `wp_authenticate_user`, which does NOT fire for application-password / REST / XML-RPC auth, so a pending account could in principle authenticate off-form while the backstop is ON. Belongs to the Story-18.10 hardening surface (which hardens *around* the 3.6 pending state); a freshly-pending self-registration has no application password yet, so live exposure is minimal.
- **No executed test for approve→login-passes or the forged-POST nonce/cap gate** [`tests/Unit/Accounts/ApprovalTest.php`] — unit tests prove the flag writes + the gate's keying off `isBlocked`, but not the end-to-end "approve ⇒ user can now log in" composition, nor that `guardWrite` rejects a forged/nonce-less POST (the handler ends in `wp_safe_redirect` + `exit`). Deferred to E2E (Story 18.8), consistent with the `Onboarding::completeViaPost` precedent.

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

## Deferred from: Epic 4 — Membership, access & payment (2026-06-24)

### Stories 4.9–4.11 — POST-LAUNCH (not built; blocked on PayFast recurring verification)
The three recurring/auto-renew stories are explicitly post-launch per epics.md §14.8 / OQ-9 and are **intentionally not implemented** in this epic. They stay `backlog` in sprint-status (not a gap):
- **4.9 Auto-renew (recurring)** [FR-63] — requires PayFast recurring support + WooCommerce-Memberships extension compatibility to be **verified first**. Until then, renewal IS the manual fixed-term flow shipped in Story 4.5. No code.
- **4.10 Recurring-renewal warning variant** [FR-63] — depends on 4.9; the launch lifecycle emails (Story 4.8) cover the manual-term thank-you + 1-week/1-month expiry warnings only.
- **4.11 Recurring-renewal discount** [FR-63, §14.5] — depends on 4.9; a genuine recurring discount with no vanity "%-off" framing. (Launch carries no discount/savings framing at all — see 4.1 AC-3.)

### Cross-story deferrals surfaced by Epic 4 code reviews
- **Story 9.4 (My Profiel) must embed the renewal section** — Story 4.5 ships the `ink-foundation/lidmaatskap-hernu` section + an interim `page-my-profiel-lidmaatskap` host. 9.4 must embed the section into the real My Profiel → Lidmaatskap tab and retire/redirect the interim host. FR-8 only fully reaches users once 9.4 lands (4.5 AC is PARTIAL by design).
- **Story 6.8 wires the entitlement gate at the publish point** — Story 4.3 delivers `Ink\Entitlement\Api::can_submit()` (the evaluation) but no enforcement point exists yet (`Ink\Submission` is Epic 6). 6.8 calls `can_submit()` at *plaas* and surfaces the 4.7 denial copy. FR-6/FR-19 fully close at 6.8.
- **Story 4.7 status copy + 4.8 email copy are `[NEEDS HUMAN AFRIKAANS]` / `[WAG OP MENSLIKE KOPIE]` placeholders** where not yet curated — pre-launch content gate. 4.8 lifecycle emails stay toggle-OFF until human copy lands AND staff enable a (type, term) pair. In scope for the NFR-1 leak gate (tooling: Story 17.4 / 18.x).
- **4.8 lifecycle-email tone for non-payment activations** — the activation thank-you fires on *any* `→ active` transition (incl. complimentary/admin grants). 4.8's copy author should keep the wording tone-appropriate or add a paid-only filter if desired.
- **Store-UI "reads as a community" residue → Epic 15/18** — Story 4.6 suppresses the literal cart/catalog/checkout surfaces; WooCommerce nav-menu items / breadcrumbs are nav config (Epic 15 nav) / Rank Math (Epic 18), not suppressed here.
- **Pre-existing `patterns/onboarding.php` phpcs errors** (Epic 3, unmodified by Epic 4) — repo-hygiene debt for a separate cleanup pass; not an Epic-4 regression.
- **Live-WooCommerce / PayFast-sandbox E2E verification → Story 18.8** — the entire Epic-4 WC/PayFast/Memberships/Action-Scheduler integration is unit-tested against mocks (Brain Monkey); real-WP + PayFast-sandbox round-trips (purchase → activate → entitlement → renewal → expiry-warning scheduling) are the E2E layer's job.

## Deferred from: code review of story 3-1 — authentication-pages (2026-06-22)
- **No `is_user_logged_in()` guard on the auth surfaces** [`ink-foundation/patterns/auth-register.php`, `auth-forgot-password.php`] — a logged-in member visiting the page sees a stale "Skep jou rekening" / "Wagwoord-herstel" screen with live links; Meld aan degrades gracefully (the `core/loginout` block swaps to a logout link). Static FSE patterns make an in-pattern conditional awkward; revisit when the auth pages get their real page-binding/routing.
- **Hardcoded root-relative slug cross-links + unbound page objects** [`ink-foundation/patterns/auth-*.php`, `theme.json`] — patterns link to literal `/meld-aan` `/registreer` `/wagwoord-herstel` and `customTemplates` are registered, but this story creates no `page` objects bound to those slugs, so the cross-links are dead until an editor creates the pages at exactly those slugs. Page creation is editorial/content; the slug coupling is inherent to static patterns. Bind/verify when the auth pages are created.
