<?php
/**
 * Lidmaatskap lifecycle expiry-warning emails (Story 4.8, FR-9a / R5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

use Ink\Kernel\Sast;
use Ink\Notifications\Api as Notifications;
use Ink\Notifications\Template;

defined( 'ABSPATH' ) || exit;

/**
 * The lidmaatskap EXPIRY-WARNING half of the R5 lifecycle emails (Story 4.8, FR-9a).
 *
 * Story 4.2's {@see PurchaseActivation} already owns the ONE activation thank-you
 * email (`ink_membership_activated_email`, fired on `→ active`); this collaborator
 * owns the NEW work — the two pre-expiry warning emails:
 *
 *  - a **1-week-prior** warning on EVERY term (1 / 6 / 12 months);
 *  - a **1-month-prior** warning on LONGER terms ONLY (6 & 12) — a 1-month term has
 *    no 1-month-prior warning (the instant would fall on/before activation).
 *
 * Both are TIME-BASED sends computed off the **membership expiry anchor** — the
 * end-of-day-SAST instant of the membership end date ({@see Sast::endOfDay()}), the
 * SAME anchor the 4.3 entitlement gate and the FR-44 expiry reminder (Story 9.9)
 * share (AD-2). The warning is scheduled at `anchor − lead` via **Action Scheduler**
 * (bundled with WooCommerce; AD-6/AD-9).
 *
 * NO FALSE WARNING TO A RENEWED MEMBER — three mechanisms, together (not a single
 * send-time check, which the original notes overclaimed):
 *
 *  - **RESCHEDULE ON END-DATE CHANGE** (FIX 1) — a launch "renew" EXTENDS the end date of
 *    the still-`active` membership, usually with NO status transition, so this also hooks
 *    `wc_memberships_user_membership_saved` ({@see HOOK_MEMBERSHIP_SAVED}) and reschedules
 *    off the LIVE end date, clearing the stale OLD-anchor actions;
 *  - **INVARIANT-IDENTITY UNSCHEDULE** (FIX 2) — the scheduled-action args + the unschedule
 *    match key are the term-INDEPENDENT identity `[membership_id, user_id, base_key]`
 *    ({@see actionIdentity()}), so a term-change renewal still clears the OLD term's action;
 *  - **ROBUST SEND-TIME RE-CHECK** (FIX 3) — ON SEND {@see shouldStillWarn()} recomputes the
 *    warning's expected fire instant off the LIVE anchor and only sends if it is still due
 *    within ~a day of now, AND the status is not revoked (reusing the 4.3 single source
 *    {@see SubmissionGate::REVOKED_STATUSES}), AND not expired. So a renewed-FORWARD member
 *    (live anchor far in the future) is NOT warned even if a reschedule was somehow missed.
 *
 * THE PER-TERM TOGGLE MATRIX (AC-4). Each email TYPE is independently toggleable
 * on/off PER TERM LENGTH (1/6/12) by staff. The matrix is modelled as a distinct
 * per-term TOGGLE KEY — `"{base_key}_{months}"` ({@see toggleKeyFor()}) — consulted
 * through the 1.12 {@see \Ink\Notifications\TemplateStore::isEnabled()} mechanism,
 * which is **fail-safe OFF** (an unset/unconfigured pair never sends). The matrix:
 *
 *   | type          | 1mo | 6mo | 12mo |
 *   |---------------|-----|-----|------|
 *   | thank-you     |  ✓  |  ✓  |  ✓   |  (owned by PurchaseActivation, AC-2)
 *   | 1-week warn   |  ✓  |  ✓  |  ✓   |
 *   | 1-month warn  |  —  |  ✓  |  ✓   |  (1-month-term cell is structurally absent)
 *
 * Only the TOGGLE is per term; the body/subject stay ONE per type (no per-term body
 * duplication) — so a single base {@see Template} per warning type carries the copy,
 * and the per-term cell is a toggle-key read only. A send fires iff BOTH the per-term
 * toggle key AND the base template toggle are ON (the base toggle is the master
 * copy-ready gate — it stays OFF until Story 4.8's human Afrikaans copy lands), so
 * NOTHING dispatches until human copy lands AND staff enable a (type, term) pair
 * (AC-5).
 *
 * AFRIKAANS-SOURCE + NFR-1 (AC-5). The default subject/body are Afrikaans-source
 * LITERAL `__( …, 'ink-core' )` strings (decision 5a; `wp i18n make-pot` extracts
 * them; no English `.mo` ships). Because the curated email PROSE is not yet authored
 * (ui-copy-translations.md marks all three 4.8 rows `[NEEDS HUMAN AFRIKAANS]`), the
 * bodies are clearly-marked `[WAG OP MENSLIKE KOPIE]` placeholders built only from
 * approved glossary terms — never AI-translated. Any later admin override stored in
 * the Notifications options store is a new NFR-1 leak vector in scope for the standing
 * leak scan (Story 17.4 / Epic 18) — not built here.
 *
 * THE conflation rule (AD-1, FR-13): lifecycle emails are a LIDMAATSKAP/entitlement
 * concept, strictly independent of writer Gradering — this class carries ZERO
 * reference to `Ink\Tiers` and never writes `ink_writer_tier`. It performs NO
 * membership-status WRITE (WC Memberships owns the status; this only READS it) and is
 * PCI-clean (reads no card / gateway field). Deptrac enforces `Entitlement ⟂ Tiers`;
 * `Entitlement → Notifications` is the already-allowed edge.
 *
 * Non-`final` for deliberate testability seams (the 4.1/4.2/4.3 precedent): the
 * Action-Scheduler / WC-Memberships availability checks, the membership read, and the
 * `now()` clock are `protected` so the unit suite drives the WC/Action-Scheduler-absent
 * branches deterministically without mocking PHP internals (Brain Monkey-defined
 * function symbols persist within a process).
 *
 * Scope (Story 4.8): the expiry-warning scheduling + send + the per-term toggle model.
 * NOT built here: recurring/auto-renew + the recurring-renewal warning variant +
 * recurring discount (Stories 4.9–4.11, post-launch); the BuddyPress in-app expiry
 * kennisgewing (Story 9.9 — shares the anchor, separate surface); an admin settings
 * screen for the templates/toggles (deferred); the NFR-1 leak-scan tool (17.4/18.x).
 *
 * @package Ink\Core
 */
class LifecycleEmails {

	/**
	 * The Notifications base template/event key for the 1-WEEK-prior expiry warning.
	 *
	 * One base template per warning TYPE (the body/subject); the per-term gate is a
	 * toggle-key read ({@see toggleKeyFor()}), so the body is never duplicated per term.
	 * The `_email` suffix keeps the key in the Notifications keyspace, mirroring
	 * {@see PurchaseActivation::ACTIVATED_TEMPLATE_KEY}.
	 */
	public const WARN_1WEEK_TEMPLATE_KEY = 'ink_membership_expiry_1week_email';

	/**
	 * The Notifications base template/event key for the 1-MONTH-prior expiry warning.
	 *
	 * Applies to LONGER terms only (6 & 12 months) — see {@see warningTypes()}.
	 */
	public const WARN_1MONTH_TEMPLATE_KEY = 'ink_membership_expiry_1month_email';

	/**
	 * The Action Scheduler hook the scheduled warning sends fire on.
	 *
	 * Single-source so the hook name is never an inline literal. The callback is
	 * {@see sendWarning()}, wired in {@see register()}; the action carries the
	 * INVARIANT IDENTITY `[ membership_id, user_id, base_key ]` as its args (NOT the
	 * mutable `term_months` — see {@see scheduleWarnings()}), so a reschedule/cancel
	 * reliably matches any prior warning of that type for that membership regardless
	 * of a term change on renewal.
	 */
	public const HOOK_SEND_WARNING = 'ink_entitlement_send_expiry_warning';

	/**
	 * The WC Memberships hook that fires when a user-membership is SAVED — including a
	 * renewal / end-date extension WHILE ALREADY ACTIVE (no status transition).
	 *
	 * The crux of the renewal model (Story 4.5): a re-buy EXTENDS the end date of the
	 * still-`active` membership — typically with NO `status_changed` transition. The
	 * 4.2 status-changed listener therefore never reschedules an in-place renewal, so
	 * the OLD anchor's warning would survive and fire a false "expires soon" to a member
	 * who renewed months out. This hook fires on the save that carries the new end date,
	 * so we recompute the anchor from the LIVE end date and RESCHEDULE off it. Guarded
	 * with `function_exists`/availability — absent on a WC-less install. Single source
	 * so the hook name is never an inline literal.
	 */
	public const HOOK_MEMBERSHIP_SAVED = 'wc_memberships_user_membership_saved';

	/**
	 * The Action Scheduler group all INK scheduled actions live under.
	 */
	public const AS_GROUP = 'ink';

	/**
	 * The lead time (a strtotime-relative modifier) for the 1-week-prior warning.
	 */
	private const LEAD_1WEEK = '-1 week';

	/**
	 * The lead time (a strtotime-relative modifier) for the 1-month-prior warning.
	 *
	 * FIX 5 — accepted imprecision: `DateTimeImmutable::modify('-1 month')` overflows on a
	 * long→short month boundary (e.g. an anchor on the 31st minus one month lands on the
	 * 3rd of two months back, not the 28th/30th of the prior month). For a "~1 month
	 * prior" courtesy warning this is cosmetic — a day or two of drift on a single
	 * reminder — and the past-instant skip ({@see scheduleWarnings()}) plus the send-time
	 * window recompute (which uses this SAME modifier, so schedule and re-check agree)
	 * keep it safe. Documented and accepted rather than switched to `sub(P1M)` (which has
	 * the same calendar-overflow behaviour) — no clean-month-prior maths is warranted for
	 * a cosmetic lead on an unauthored-copy email.
	 */
	private const LEAD_1MONTH = '-1 month';

	/**
	 * The send-time "still due" tolerance in seconds (FIX 3) — ~1 day.
	 *
	 * At fire time {@see shouldStillWarn()} recomputes the warning's expected fire instant
	 * off the LIVE anchor and only sends if it is within this tolerance of "now". A
	 * comfortably-wide one-day window absorbs Action Scheduler dispatch lag and the
	 * `-1 month` calendar imprecision while still being far tighter than any renewal
	 * (which moves the anchor by whole months), so a renewed-forward member is suppressed.
	 * A literal `86400` (one day in seconds) rather than WP's `DAY_IN_SECONDS` — that
	 * constant is undefined when WordPress is not loaded (the mocked unit suite), and this
	 * is a self-contained tolerance, not a WP-dependent value.
	 */
	private const WINDOW_TOLERANCE = 86400;

	/**
	 * Register this collaborator's hooks + warning templates. Invoked once from
	 * {@see Module::register()} (dispatched by the Kernel on `init`).
	 *
	 * Wires (a) the warning-template registration (Afrikaans-source, toggles OFF);
	 * (b) the WC Memberships status-change listener at priority 20 — AFTER 4.2's
	 * priority-10 thank-you trigger — so a single transition first fires the thank-you
	 * (4.2) and then (re)schedules/cancels the warnings (this class); (c) the Action
	 * Scheduler send callback. The behaviour is self-gated (the `→ active` check, the
	 * availability seams, the fail-safe-OFF toggles), so wiring it unconditionally is
	 * safe on a WC / Action-Scheduler-absent install.
	 */
	public function register(): void {
		$this->registerTemplates();

		add_action( PurchaseActivation::HOOK_STATUS_CHANGED, array( $this, 'onMembershipStatusChanged' ), 20, 3 );

		// Renewal / end-date extension WHILE active (no status transition) — reschedule
		// off the LIVE end date (FIX 1: the in-place renewal gap). Fires on every save,
		// so onMembershipSaved is itself idempotent (unschedule-before-schedule on the
		// invariant identity, and a no-change save recomputes the SAME anchor ⇒ no stack).
		add_action( self::HOOK_MEMBERSHIP_SAVED, array( $this, 'onMembershipSaved' ), 20, 1 );

		add_action( self::HOOK_SEND_WARNING, array( $this, 'sendWarning' ), 10, 3 );
	}

	/**
	 * The warning types: base template key, lead time, and the terms each applies to.
	 *
	 * The SINGLE SOURCE for the "1-week → every term; 1-month → longer terms only"
	 * rule (AC-1). The 1-month warning's term set is the 6/12-month subset — the
	 * 1-month term is deliberately ABSENT (a 1-month-prior warning on a 1-month term
	 * would fall on/before activation). The term sets derive from the closed
	 * {@see LidmaatskapTerm} enum — the 1/6/12 literals are never inlined.
	 *
	 * @return list<array{key:string, lead:string, terms:list<LidmaatskapTerm>}>
	 */
	public function warningTypes(): array {
		return array(
			array(
				'key'   => self::WARN_1WEEK_TEMPLATE_KEY,
				'lead'  => self::LEAD_1WEEK,
				'terms' => LidmaatskapTerm::cases(), // Every term (1/6/12).
			),
			array(
				'key'   => self::WARN_1MONTH_TEMPLATE_KEY,
				'lead'  => self::LEAD_1MONTH,
				'terms' => array( LidmaatskapTerm::SixMonths, LidmaatskapTerm::TwelveMonths ), // Longer terms only.
			),
		);
	}

	/**
	 * The per-term TOGGLE KEY for a (base template key, term) pair — the single source
	 * for the {type}×{term} toggle matrix (AC-4).
	 *
	 * `"{base_key}_{months}"`, e.g. `ink_membership_expiry_1week_email_6`. Used for BOTH
	 * the activation thank-you ({@see PurchaseActivation}) and the warnings, so the
	 * derivation lives once. The toggle is read through the 1.12
	 * {@see \Ink\Notifications\TemplateStore::isEnabled()} (fail-safe OFF). `public
	 * static` so {@see PurchaseActivation} shares the one derivation.
	 *
	 * @param string          $baseKey The base template key (a type).
	 * @param LidmaatskapTerm $term    The term length.
	 * @return string The per-term toggle key.
	 */
	public static function toggleKeyFor( string $baseKey, LidmaatskapTerm $term ): string {
		return $baseKey . '_' . $term->months();
	}

	/**
	 * React to a WC Memberships status transition — (re)schedule or cancel the warnings.
	 *
	 * On a genuine transition INTO `active` (the same gate 4.2 uses: `new === active &&
	 * old !== active` — first activation AND renewal/reactivation), (re)schedule the
	 * warnings off the live end date; on any transition OUT of active (to a non-active
	 * status), CANCEL any scheduled warnings for that membership so a lapsed/cancelled
	 * membership does not still fire one. A malformed object or an unresolvable term is a
	 * graceful no-op. Performs NO status write (WC owns the status).
	 *
	 * @param mixed  $user_membership The WC_Memberships_User_Membership (platform object).
	 * @param string $old_status      The previous status.
	 * @param string $new_status      The new status.
	 */
	public function onMembershipStatusChanged( mixed $user_membership, string $old_status, string $new_status ): void {
		if ( ! is_object( $user_membership ) ) {
			return; // Malformed ⇒ graceful no-op.
		}

		if ( PurchaseActivation::STATUS_ACTIVE === $new_status && PurchaseActivation::STATUS_ACTIVE !== $old_status ) {
			$this->scheduleWarnings( $user_membership );
			return;
		}

		// Any transition OUT of active (new status is not active) clears stale schedules.
		if ( PurchaseActivation::STATUS_ACTIVE !== $new_status ) {
			$this->cancelWarnings( $user_membership );
		}
	}

	/**
	 * React to a WC Memberships SAVE — reschedule the warnings off the LIVE end date
	 * (FIX 1: the in-place renewal gap).
	 *
	 * A re-buy at launch (Story 4.5) EXTENDS the end date of the still-`active`
	 * membership, typically WITHOUT a `status_changed` transition — so
	 * {@see onMembershipStatusChanged()} never fires and the OLD anchor's warning would
	 * survive and fire a false "expires soon" to a member who renewed months out. This
	 * `wc_memberships_user_membership_saved` listener recomputes the anchor from the live
	 * end date and reschedules (the unschedule-before-schedule in {@see scheduleWarnings()}
	 * clears the stale OLD-anchor actions first). For a STILL-active membership only — a
	 * save that left it in a non-active status is handled by the status-changed cancel
	 * path; saving a non-active membership here just cancels (no future warnings).
	 *
	 * Idempotent: rescheduling repeatedly for the SAME end date recomputes the SAME anchor
	 * and the unschedule-before-schedule matches on the invariant identity (FIX 2), so no
	 * duplicate actions stack. A malformed object is a graceful no-op.
	 *
	 * @param mixed $user_membership The WC_Memberships_User_Membership (platform object).
	 */
	public function onMembershipSaved( mixed $user_membership ): void {
		if ( ! is_object( $user_membership ) ) {
			return; // Malformed ⇒ graceful no-op.
		}

		if ( ! method_exists( $user_membership, 'get_status' ) ) {
			return;
		}

		// Only an active membership has live warnings to (re)schedule; a save that left it
		// non-active clears any stale schedule (mirrors the status-changed OUT-of-active path).
		if ( PurchaseActivation::STATUS_ACTIVE === (string) $user_membership->get_status() ) {
			$this->scheduleWarnings( $user_membership );
			return;
		}

		$this->cancelWarnings( $user_membership );
	}

	/**
	 * (Re)schedule the applicable warnings for a membership off its expiry anchor.
	 *
	 * Reads the membership id, owner id, the live end date and the term; computes the
	 * anchor = {@see Sast::endOfDay()} of the end date; for each applicable warning type
	 * (per {@see warningTypes()}), clears any prior schedule for that (membership, type)
	 * and schedules a single action at `anchor − lead` — but ONLY when that instant is in
	 * the FUTURE (a past instant is skipped, never fired immediately). All Action
	 * Scheduler calls are availability-guarded ⇒ graceful no-op when absent.
	 *
	 * @param object $membership The WC Memberships user-membership object.
	 */
	public function scheduleWarnings( object $membership ): void {
		if ( ! $this->isActionSchedulerAvailable() ) {
			return; // Action Scheduler absent (no WooCommerce) ⇒ graceful no-op.
		}

		$membership_id = $this->membershipId( $membership );
		$user_id       = $this->membershipOwnerId( $membership );
		$end_date      = $this->endDate( $membership );
		$term          = $this->resolveTerm( $membership );

		if ( $membership_id <= 0 || $user_id <= 0 || ! $end_date instanceof \DateTimeImmutable || ! $term instanceof LidmaatskapTerm ) {
			return; // Cannot resolve the schedule inputs ⇒ fail-safe no-op.
		}

		$anchor = Sast::endOfDay( $end_date );
		$now    = $this->now();

		foreach ( $this->warningTypes() as $type ) {
			// Clear any prior schedule for this (membership, type) FIRST — even for a type
			// that does not apply to the CURRENT term — so a term-change renewal (e.g.
			// 6mo→1mo) reliably clears the old term's 1-month warning. The match is on the
			// INVARIANT IDENTITY only (FIX 2), so a prior warning of this type for this
			// membership is cleared regardless of the term it was scheduled under.
			$identity = $this->actionIdentity( $membership_id, $user_id, $type['key'] );

			// Inline `function_exists` narrowing (in addition to the isActionSchedulerAvailable()
			// gate above) keeps static analysis honest — Action Scheduler ships with
			// WooCommerce and has no PHPStan stub, so its symbols are unknown to analysis
			// (the 4.3 SubmissionGate::userMemberships() precedent).
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( self::HOOK_SEND_WARNING, $identity, self::AS_GROUP );
			}

			if ( ! in_array( $term, $type['terms'], true ) ) {
				continue; // This warning type does not apply to this term (e.g. 1-month on a 1-month term) — cleared above, never re-scheduled.
			}

			$when = $anchor->modify( $type['lead'] );

			// Only schedule a FUTURE instant — a past instant (e.g. a reschedule already
			// inside the warning window) is skipped, never fired immediately.
			if ( $when <= $now ) {
				continue;
			}

			if ( function_exists( 'as_schedule_single_action' ) ) {
				as_schedule_single_action( $when->getTimestamp(), self::HOOK_SEND_WARNING, $identity, self::AS_GROUP );
			}
		}
	}

	/**
	 * The INVARIANT IDENTITY of a scheduled warning action — `[ membership_id, user_id,
	 * base_key ]`, term-INDEPENDENT (FIX 2).
	 *
	 * This is BOTH the scheduled action's args (its send payload) AND the unschedule
	 * match key. Keeping it term-independent is the crux: a term-change renewal (6mo→12mo)
	 * reschedules with the NEW term, but the unschedule must still match the OLD action,
	 * which was scheduled under the OLD term. Were `term_months` part of the args, the
	 * NEW-term unschedule would not match the OLD-term action and a stale warning would
	 * survive. The send handler ({@see sendWarning()}) re-resolves the term from the LIVE
	 * membership, so it needs no term in the payload.
	 *
	 * @param int    $membership_id The membership.
	 * @param int    $user_id       The membership owner (recipient).
	 * @param string $base_key      The warning base template key (the type).
	 * @return array{0:int,1:int,2:string} The invariant identity args.
	 */
	protected function actionIdentity( int $membership_id, int $user_id, string $base_key ): array {
		return array( $membership_id, $user_id, $base_key );
	}

	/**
	 * Cancel any scheduled warnings for a membership (a lapse/cancellation/deactivation).
	 *
	 * Availability-guarded; clears every warning type's scheduled action for the
	 * membership so a no-longer-active membership never fires a stale warning.
	 *
	 * @param object $membership The WC Memberships user-membership object.
	 */
	public function cancelWarnings( object $membership ): void {
		if ( ! $this->isActionSchedulerAvailable() ) {
			return;
		}

		$membership_id = $this->membershipId( $membership );
		$user_id       = $this->membershipOwnerId( $membership );

		if ( $membership_id <= 0 || $user_id <= 0 ) {
			return;
		}

		// Cancel by the INVARIANT IDENTITY only (FIX 2) — no term needed. A cancellation
		// must clear EVERY warning type regardless of the term it was scheduled under, so
		// term resolution (which can fail on a cancelled/garbled membership) is not a
		// precondition to clearing the schedule.
		foreach ( $this->warningTypes() as $type ) {
			$identity = $this->actionIdentity( $membership_id, $user_id, $type['key'] );
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( self::HOOK_SEND_WARNING, $identity, self::AS_GROUP );
			}
		}
	}

	/**
	 * The Action Scheduler callback — send ONE expiry warning, re-checked LIVE.
	 *
	 * The action payload is the INVARIANT IDENTITY only (`[ membership_id, user_id,
	 * base_key ]`, FIX 2) — NO `term_months`: the term is re-resolved from the LIVE
	 * membership so the per-term toggle and the window re-check both reflect the
	 * membership AS IT IS NOW, not a snapshot taken at schedule time.
	 *
	 * Gating order (AC-3/AC-4/AC-5): (1) the live membership is re-read and its LIVE term
	 * resolved; (2) the per-term toggle key for this (type, LIVE term) must be ON
	 * (fail-safe OFF) — else no send; (3) the membership must still be eligible to be
	 * warned on a robust LIVE re-check (FIX 3): NOT administratively revoked, NOT expired,
	 * AND the warning is STILL DUE — its expected fire instant recomputed from the LIVE
	 * end date (`Sast::endOfDay(liveEndDate) − lead`) must be within a ~1-day tolerance of
	 * "now". A renewed-FORWARD member (live anchor far in the future) therefore has a fire
	 * instant far in the future, so this catches a stale warning even if a reschedule was
	 * somehow missed; (4) the recipient + `{skrywer}` greeting are resolved from the
	 * membership owner; (5) the dispatch goes through {@see Notifications::send()} on the
	 * BASE key — itself toggle-gated, so the base (copy-ready) toggle must ALSO be ON. So a
	 * send fires iff the per-term toggle AND the base toggle are both ON (and copy landed).
	 *
	 * @param int    $membership_id The membership the warning is for (re-read live).
	 * @param int    $user_id       The membership owner (recipient).
	 * @param string $base_key      The warning base template key (the type).
	 */
	public function sendWarning( int $membership_id, int $user_id, string $base_key ): void {
		// (1) Re-read the membership LIVE and resolve its CURRENT term — the renewed lid's
		// live membership reflects the new term, not the term at schedule time.
		$membership = $this->liveMembership( $membership_id );

		if ( ! is_object( $membership ) ) {
			return; // Cannot re-read ⇒ fail-safe no-op.
		}

		$term = $this->resolveTerm( $membership );

		if ( ! $term instanceof LidmaatskapTerm ) {
			return; // Not (any longer) an INK lidmaatskap / unresolvable term ⇒ fail-safe no-op.
		}

		// (2) Per-term toggle (the staff matrix gate) — fail-safe OFF.
		if ( ! $this->isEnabledForTerm( $base_key, $term ) ) {
			return;
		}

		// (3) Robust live re-check (FIX 3): not revoked, not expired, AND still due off the
		// LIVE anchor — a renewed-forward member's fire instant has moved out of "now".
		if ( ! $this->shouldStillWarn( $membership, $base_key ) ) {
			return;
		}

		// (4) Resolve the recipient + greeting (the PurchaseActivation precedent).
		$user = get_userdata( $user_id );

		if ( ! ( $user instanceof \WP_User ) || '' === (string) $user->user_email ) {
			return; // Missing user / empty email ⇒ graceful no-op.
		}

		$skrywer = (string) $user->display_name;
		if ( '' === $skrywer ) {
			$skrywer = (string) $user->user_login;
		}

		// (4) Dispatch on the BASE key — Notifications gates on the base (copy-ready) toggle.
		Notifications::send(
			$base_key,
			(string) $user->user_email,
			array( 'skrywer' => $skrywer )
		);
	}

	/**
	 * Whether a (type, term) pair is enabled — the per-term toggle (fail-safe OFF).
	 *
	 * Reads the per-term toggle key ({@see toggleKeyFor()}) through the 1.12
	 * {@see \Ink\Notifications\TemplateStore::isEnabled()} via a small read against the
	 * Notifications options row, so an unset/unconfigured pair never sends. Done as a
	 * direct option read (the store's per-key toggle) so the matrix needs no new store.
	 *
	 * @param string          $baseKey The base template key (a type).
	 * @param LidmaatskapTerm $term    The term length.
	 * @return bool True when the (type, term) toggle is explicitly ON.
	 */
	protected function isEnabledForTerm( string $baseKey, LidmaatskapTerm $term ): bool {
		$toggle_key = self::toggleKeyFor( $baseKey, $term );

		$all = get_option( \Ink\Notifications\TemplateStore::OPTION, array() );

		if ( ! is_array( $all ) ) {
			return false;
		}

		$row = $all[ $toggle_key ] ?? array();

		if ( ! is_array( $row ) || ! array_key_exists( 'enabled', $row ) ) {
			return false; // Unconfigured pair ⇒ fail-safe OFF.
		}

		return (bool) $row['enabled'];
	}

	/**
	 * Whether the membership should STILL be warned — a robust LIVE re-check (FIX 3).
	 *
	 * Defence-in-depth so a stale warning cannot fire if the live membership no longer
	 * matches the warning's premise — even if a reschedule (FIX 1) was somehow missed.
	 * Three independent conditions, all against the LIVE membership:
	 *
	 *  - NOT administratively revoked — reuses the 4.3 single source
	 *    {@see SubmissionGate::REVOKED_STATUSES} (FIX 4 — no duplicated hardcoded set);
	 *  - NOT already expired — still valid through end of day SAST
	 *    ({@see Sast::isThroughEndOfDay()} against the live end date, the 4.3 logic);
	 *  - STILL DUE — the warning's expected fire instant recomputed from the LIVE end date
	 *    (`Sast::endOfDay(liveEndDate) − lead`, per this warning TYPE's lead) must be within
	 *    {@see WINDOW_TOLERANCE} of "now". This is the renewed-FORWARD guard: a member who
	 *    renewed pushes the live anchor far into the future, so the recomputed fire instant
	 *    is far in the future (well outside the tolerance) and the warning is suppressed —
	 *    independently of whether the stale action was unscheduled.
	 *
	 * @param object $membership The LIVE WC Memberships user-membership object.
	 * @param string $base_key   The warning base template key (the type — selects the lead).
	 * @return bool True when the membership is still in a state worth warning.
	 */
	protected function shouldStillWarn( object $membership, string $base_key ): bool {
		// Administrative revocation (cancelled/paused/pending) ⇒ do not warn. The status
		// set is the 4.3 single source (FIX 4), not a re-derived copy.
		if ( ! method_exists( $membership, 'get_status' ) ) {
			return false;
		}

		if ( in_array( (string) $membership->get_status(), SubmissionGate::REVOKED_STATUSES, true ) ) {
			return false;
		}

		$end_date = $this->endDate( $membership );

		if ( ! $end_date instanceof \DateTimeImmutable ) {
			return false; // No resolvable end date ⇒ fail-safe no-warn (a warning needs an expiry).
		}

		$now    = $this->now();
		$anchor = Sast::endOfDay( $end_date );

		// Already expired (live end date past end of day SAST) ⇒ do not warn.
		if ( ! Sast::isThroughEndOfDay( $end_date, $now ) ) {
			return false;
		}

		// Still DUE off the LIVE anchor (FIX 3): the recomputed fire instant must be within
		// the tolerance of "now". A renewed-forward member's anchor (and so its fire instant)
		// is far in the future ⇒ suppressed even if the stale action was not unscheduled.
		$lead = $this->leadFor( $base_key );

		if ( null === $lead ) {
			return false; // Unknown type ⇒ no lead to recompute ⇒ fail-safe no-warn.
		}

		$expected_fire = $anchor->modify( $lead );
		$delta_seconds = abs( $expected_fire->getTimestamp() - $now->getTimestamp() );

		return $delta_seconds <= self::WINDOW_TOLERANCE;
	}

	/**
	 * The lead-time modifier for a warning TYPE (base key), or null when unknown.
	 *
	 * Drives the send-time window recompute ({@see shouldStillWarn()}) off the SAME
	 * single-source {@see warningTypes()} list the scheduler uses — so the "due" instant
	 * recomputed at send time matches the instant the warning was scheduled at.
	 *
	 * @param string $base_key The warning base template key (the type).
	 * @return string|null The strtotime-relative lead modifier, or null when unknown.
	 */
	protected function leadFor( string $base_key ): ?string {
		foreach ( $this->warningTypes() as $type ) {
			if ( $type['key'] === $base_key ) {
				return $type['lead'];
			}
		}

		return null;
	}

	/**
	 * Register the two warning base templates (Afrikaans-source, send toggle OFF).
	 *
	 * One base {@see Template} per warning TYPE — Afrikaans-source LITERAL `__()`
	 * subject/body (decision 5a; `wp i18n make-pot` extracts them; no English `.mo`),
	 * the `{skrywer}` greeting, the send toggle OFF. The body is a clearly-marked
	 * `[WAG OP MENSLIKE KOPIE]` placeholder built only from approved glossary terms —
	 * Story 4.8's curated copy is `[NEEDS HUMAN AFRIKAANS]` (ui-copy ~603–604), so NO
	 * `wp_mail` fires today. Mirrors {@see PurchaseActivation::registerEmailTemplate()}.
	 *
	 * The PER-TERM toggles are NOT registered as separate templates (that would
	 * duplicate the body): one base template per type carries the copy + the master
	 * copy-ready toggle (OFF); the per-term cells are toggle-key reads only
	 * ({@see isEnabledForTerm()}), fail-safe OFF.
	 */
	public function registerTemplates(): void {
		Notifications::registerTemplate(
			new Template(
				self::WARN_1WEEK_TEMPLATE_KEY,
				// Subject — glossary-only placeholder, sentence case.
				__( 'Jou lidmaatskap verval binnekort [WAG OP MENSLIKE KOPIE]', 'ink-core' ),
				// Body — PLACEHOLDER. [NEEDS HUMAN AFRIKAANS] — toggle stays OFF until
				// Story 4.8's curated copy lands; glossary terms only, no invented prose.
				__( 'Hallo {skrywer}, jou lidmaatskap verval oor een week. [WAG OP MENSLIKE KOPIE]', 'ink-core' ),
				// Send toggle OFF by default — no wp_mail until human copy is approved.
				false
			)
		);

		Notifications::registerTemplate(
			new Template(
				self::WARN_1MONTH_TEMPLATE_KEY,
				__( 'Jou lidmaatskap verval binnekort [WAG OP MENSLIKE KOPIE]', 'ink-core' ),
				__( 'Hallo {skrywer}, jou lidmaatskap verval oor een maand. [WAG OP MENSLIKE KOPIE]', 'ink-core' ),
				false
			)
		);
	}

	/**
	 * Resolve the membership's fixed term from its plan products (the 4.1 mapping inverse).
	 *
	 * A membership is an INK lidmaatskap when its plan grants a configured Story-4.1
	 * product; the term is the {@see LidmaatskapTerm} whose mapped product id is among
	 * the membership's plan products. Resolved through {@see MembershipPlans} (never
	 * reimplemented). Returns null when the membership is not an INK lidmaatskap or the
	 * term cannot be resolved (graceful no-op upstream).
	 *
	 * @param object $membership The WC Memberships user-membership object.
	 * @return LidmaatskapTerm|null The resolved fixed term, or null.
	 */
	protected function resolveTerm( object $membership ): ?LidmaatskapTerm {
		if ( ! method_exists( $membership, 'get_plan' ) ) {
			return null;
		}

		$plan = $membership->get_plan();

		if ( ! is_object( $plan ) || ! method_exists( $plan, 'get_product_ids' ) ) {
			return null;
		}

		$plan_product_ids = array_map( 'intval', (array) $plan->get_product_ids() );
		$plans            = new MembershipPlans();

		foreach ( MembershipPlans::terms() as $term ) {
			$product_id = $plans->productIdFor( $term );

			if ( null !== $product_id && in_array( $product_id, $plan_product_ids, true ) ) {
				return $term;
			}
		}

		return null;
	}

	/**
	 * The membership's id, or 0 when unreadable (PCI-clean — reads no card/gateway field).
	 *
	 * @param object $membership The WC Memberships user-membership object.
	 * @return int The membership id, or 0.
	 */
	protected function membershipId( object $membership ): int {
		if ( ! method_exists( $membership, 'get_id' ) ) {
			return 0;
		}

		return (int) $membership->get_id();
	}

	/**
	 * The membership owner's user id, or 0 when unreadable.
	 *
	 * @param object $membership The WC Memberships user-membership object.
	 * @return int The owner user id, or 0.
	 */
	protected function membershipOwnerId( object $membership ): int {
		if ( ! method_exists( $membership, 'get_user_id' ) ) {
			return 0;
		}

		return (int) $membership->get_user_id();
	}

	/**
	 * The membership's end date as a SAST-typed instant, or null.
	 *
	 * Reads `get_end_date( 'timestamp' )` (the 4.3 {@see SubmissionGate} read), behind a
	 * `method_exists` guard. A genuinely absent end date (unlimited membership) or a
	 * non-numeric value yields null (a warning needs a concrete expiry to anchor off);
	 * a numeric timestamp becomes a SAST-typed immutable instant.
	 *
	 * @param object $membership The WC Memberships user-membership object.
	 * @return \DateTimeImmutable|null The end-date instant, or null.
	 */
	protected function endDate( object $membership ): ?\DateTimeImmutable {
		if ( ! method_exists( $membership, 'get_end_date' ) ) {
			return null;
		}

		$timestamp = $membership->get_end_date( 'timestamp' );

		if ( null === $timestamp || '' === $timestamp || 0 === $timestamp || '0' === $timestamp || ! is_numeric( $timestamp ) ) {
			return null; // Unlimited or unparseable ⇒ no expiry to anchor a warning off.
		}

		return ( new \DateTimeImmutable( '@' . (int) $timestamp ) )
			->setTimezone( new \DateTimeZone( Sast::TIMEZONE ) );
	}

	/**
	 * Re-read a membership LIVE by id (use, don't reimplement).
	 *
	 * Reads via `wc_memberships_get_user_membership()` behind a `function_exists` guard,
	 * so the live re-check (AC-3) sees the membership's CURRENT status + end date at send
	 * time, not a stale snapshot. A `protected` seam so the unit suite supplies the live
	 * membership deterministically.
	 *
	 * @param int $membership_id The membership id.
	 * @return object|null The live membership object, or null.
	 */
	protected function liveMembership( int $membership_id ): ?object {
		if ( ! function_exists( 'wc_memberships_get_user_membership' ) ) {
			return null;
		}

		$membership = wc_memberships_get_user_membership( $membership_id );

		return is_object( $membership ) ? $membership : null;
	}

	/**
	 * Whether Action Scheduler's scheduling API is available in this request.
	 *
	 * Action Scheduler ships with WooCommerce; when WooCommerce is inactive the
	 * scheduler functions are absent and the lifecycle seam degrades gracefully (no
	 * schedule, no fatal). A `protected` seam (not an inline `function_exists()`) so the
	 * absent branch is deterministically unit-testable — the 4.1/4.2/4.3 precedent.
	 *
	 * @return bool True when `as_schedule_single_action()` can be called.
	 */
	protected function isActionSchedulerAvailable(): bool {
		return function_exists( 'as_schedule_single_action' )
			&& function_exists( 'as_unschedule_all_actions' );
	}

	/**
	 * The current instant (via the single Kernel SAST clock).
	 *
	 * A `protected` seam so the unit suite can pin "now" and make the anchor maths
	 * deterministic; production reads the timezone-aware WordPress clock.
	 *
	 * @return \DateTimeImmutable The current instant.
	 */
	protected function now(): \DateTimeImmutable {
		return Sast::now();
	}
}
