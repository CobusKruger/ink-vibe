<?php
/**
 * Submission-entitlement gate — `can_submit()` (Story 4.3, FR-6/FR-13/FR-19, AD-2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

use Ink\Kernel\Sast;

defined( 'ABSPATH' ) || exit;

/**
 * The runtime submission-entitlement gate (AD-2/AD-6) — "may this user plaas right
 * now?". The reusable evaluation {@see Api::can_submit()} facades and that the
 * publish point (Story 6.8, `Ink\Submission`) and AD-3 challenge entry consume.
 *
 * THE DATE IS THE TIME-AUTHORITY; STATUS GOVERNS ONLY ADMINISTRATIVE REVOCATION (AD-2 /
 * AC-3). The gate reads the user's memberships ACROSS ALL STATUSES (not WooCommerce's
 * active-only convenience reader) and decides each INK membership with a two-part test;
 * a membership is GRANTED iff BOTH hold:
 *
 *  - (a) its status is NOT an administrative-revocation status — see
 *    {@see REVOKED_STATUSES} (`cancelled`, `paused`, `pending`, `pending_cancellation`,
 *    WooCommerce Memberships' OWN status slugs). These are deliberate admin / lifecycle
 *    revocations and ALWAYS deny, regardless of the end date (AC-3 suspension /
 *    cancellation). Every OTHER status — `active`, `complimentary`, `free`,
 *    `free_trial`, AND crucially `expired` — passes this check and proceeds to (b).
 *  - (b) its end date is still valid THROUGH end of day SAST per the single Kernel SAST
 *    helper ({@see Sast::isThroughEndOfDay()}) — valid through 23:59:59 SAST = 21:59:59
 *    UTC on the expiry day — or the membership is genuinely unlimited (absent end date).
 *
 * The crux (AD-2 cron-lag protection): WooCommerce Memberships flips a membership's
 * stored status to `expired` via a scheduled action (Action Scheduler / wp-cron) that
 * LAGS the true expiry instant. Because `expired` is treated as a TIME state (NOT an
 * administrative revocation), a membership WC has already flipped to `expired` is STILL
 * granted when its end date is within end-of-day SAST — the date, not the lagging flag,
 * is the time-authority. This is deterministic and cron-independent: no scheduled job
 * needs to have run for the gate to return the correct answer. Conversely a `paused` /
 * `cancelled` membership is denied regardless of date (AC-3). A malformed object missing
 * `get_status()` is treated conservatively (deny, fail-safe).
 *
 * AUTO-REVOKE IS EMERGENT, NOT A STORED FLAG OR A ROUTINE. Because the gate evaluates
 * the end date LIVE, a lapsed lidmaatskap simply makes `canSubmit()` return `false` —
 * the betaalde lid silently reverts to a gratis lid (no submission entitlement). There
 * is NO separate revoke action, and crucially this class performs NO WRITE of any kind:
 * it never deletes the account (`wp_delete_user`), never touches `ink_writer_tier`
 * (`update_user_meta`), and never unpublishes or status-changes a bydrae
 * (`wp_update_post` / `wp_trash_post`). Account, Gradering, and published work persist
 * (epics.md#Story-4.3 AC; project-context.md edge case). `canSubmit()` is a PURE READ.
 *
 * FAIL-SAFE DENY. A null / logged-out / id-0 user, a member with no membership, a
 * non-INK membership, or WooCommerce Memberships being inactive/absent all yield
 * `false` — never a fatal, never an over-permissive `true`. AD-2's "benign failure
 * direction" note concerns the residual over-permissive window for a member who was
 * paying moments earlier; a NON-member is always denied.
 *
 * USE, DON'T REIMPLEMENT. The membership is read through WooCommerce Memberships' own
 * API (`wc_memberships_get_user_memberships()` — the ACROSS-STATUSES reader, so the gate
 * itself is the status/date authority rather than a status pre-filter), behind a
 * `function_exists` availability guard and `method_exists` getter guards, so the seam
 * degrades gracefully when the platform plugin is absent. This class never reimplements
 * membership storage. An "INK lidmaatskap" membership is one whose plan grants a
 * Story-4.1 lidmaatskap PRODUCT (the configured `term-months => product_id` map),
 * resolved through {@see MembershipPlans} — a non-INK WooCommerce membership grants no
 * INK submission entitlement.
 *
 * THE conflation rule (AD-1, FR-13): entitlement is computed ONLY from membership /
 * lidmaatskap state — ZERO reference to `Ink\Tiers` / `ink_writer_tier` / writer
 * Gradering. A paid Brons subscriber is NOT a Brons writer with an expired
 * subscription. Deptrac enforces `Entitlement ⟂ Tiers`.
 *
 * Non-`final` for a deliberate testability seam (the 4.1 {@see MembershipPlans} /
 * 4.2 {@see PurchaseActivation} precedent): the WC-availability check, the across-
 * statuses membership read, the "now" source, and the INK product-id resolution are
 * `protected` so the unit suite can override them deterministically without mocking
 * PHP internals (Brain Monkey-defined function symbols persist within a process).
 *
 * Scope (Story 4.3): the reusable evaluation ONLY. The actual `transition_post_status`
 * / pre-publish WIRING into a submission flow is Story 6.8 (`Ink\Submission` — that
 * module + the front-end form do not exist yet; AD-6 decision 2 / FR-19 → 6.8). The
 * full Afrikaans "jou lidmaatskap het verval" status copy is Story 4.7.
 *
 * @package Ink\Core
 */
class SubmissionGate {

	/**
	 * The WooCommerce Memberships reader this gate uses (use, don't reimplement).
	 *
	 * The ACROSS-STATUSES reader (NOT the active-only convenience function): the gate —
	 * not WooCommerce's status pre-filter — is the authority over which memberships grant
	 * entitlement, so it must see `expired`-flagged (cron-lag) AND `paused`/`cancelled`
	 * (admin-revoked) memberships and decide them itself. Single source so the platform
	 * function name is never an inline bare literal and the availability guard + the read
	 * reference the same symbol.
	 */
	public const WC_MEMBERSHIPS_FN = 'wc_memberships_get_user_memberships';

	/**
	 * The membership statuses that ALWAYS deny entitlement — administrative / lifecycle
	 * revocations (AC-3 suspension & cancellation), regardless of the end date.
	 *
	 * These are WooCommerce Memberships' OWN status slugs (no `ink_` prefix — they are
	 * the platform's vocabulary, not ours), kept as a single-source internal set:
	 *  - `cancelled`            — the lid (or an admin) cancelled the membership.
	 *  - `paused`               — the membership is suspended (AC-3 suspension revocation).
	 *  - `pending`              — never activated (awaiting payment) — not yet entitled.
	 *  - `pending_cancellation` — scheduled to cancel; treated as already revoked.
	 *
	 * NOTABLY ABSENT: `expired`. `expired` is a TIME state (the cron flipped the flag),
	 * NOT an administrative revocation, so it is NOT in this set — it passes the status
	 * check and is evaluated by the END DATE (the AD-2 cron-lag protection). `active`,
	 * `complimentary`, `free`, and `free_trial` likewise pass the status check.
	 *
	 * @var list<string>
	 */
	private const REVOKED_STATUSES = array( 'cancelled', 'paused', 'pending', 'pending_cancellation' );

	/**
	 * Whether the user may plaas (submit/publish) right now.
	 *
	 * Returns `true` iff the user has at least one INK lidmaatskap membership that both
	 * (a) is NOT in an administrative-revocation status ({@see REVOKED_STATUSES}) and
	 * (b) has an end date still valid THROUGH end of day SAST (or is genuinely unlimited /
	 * has no end date). Evaluated at the moment it is called — the publish point (6.8)
	 * calls it at the *plaas* moment, so a draft saved while entitled but published
	 * after lapse is denied. Fail-safe deny in every other case.
	 *
	 * @param int|\WP_User|null $user The user id, WP_User, or null/logged-out.
	 * @return bool True when the user may submit; false otherwise (fail-safe).
	 */
	public function canSubmit( int|\WP_User|null $user ): bool {
		$user_id = $this->resolveUserId( $user );

		if ( $user_id <= 0 ) {
			return false; // Null / logged-out / invalid → fail-safe deny.
		}

		if ( ! $this->isMembershipsAvailable() ) {
			return false; // WooCommerce Memberships inactive/absent → fail-safe deny.
		}

		$ink_product_ids = $this->inkProductIds();

		if ( array() === $ink_product_ids ) {
			return false; // No INK lidmaatskap product configured → nothing to honour.
		}

		$now = $this->now();

		foreach ( $this->userMemberships( $user_id ) as $membership ) {
			if ( ! $this->isInkMembership( $membership, $ink_product_ids ) ) {
				continue; // A non-INK WooCommerce membership grants no INK entitlement.
			}

			if ( $this->isAdministrativelyRevoked( $membership ) ) {
				continue; // STATUS governs admin revocation (AC-3): always deny, any date.
			}

			if ( $this->isWithinWindow( $membership, $now ) ) {
				return true; // The end DATE is the time-authority (AD-2 cron-lag).
			}
		}

		return false;
	}

	/**
	 * Whether the membership is in an administrative-revocation status (always deny).
	 *
	 * STATUS governs ONLY administrative / lifecycle revocation (AC-3 suspension &
	 * cancellation): a `cancelled` / `paused` / `pending` / `pending_cancellation`
	 * membership is denied regardless of its end date. Every other status (including
	 * `expired`, a TIME state) passes and proceeds to the end-date check (AD-2). A
	 * malformed object missing `get_status()` is treated conservatively as revoked
	 * (deny, fail-safe).
	 *
	 * @param object $membership The WC Memberships user-membership object.
	 * @return bool True when the membership is administratively revoked (deny).
	 */
	private function isAdministrativelyRevoked( object $membership ): bool {
		if ( ! method_exists( $membership, 'get_status' ) ) {
			return true; // Cannot read the status → conservative fail-safe deny.
		}

		return in_array( (string) $membership->get_status(), self::REVOKED_STATUSES, true );
	}

	/**
	 * Resolve a user id from an id / WP_User / null, fail-safe to 0.
	 *
	 * @param int|\WP_User|null $user The user.
	 * @return int The user id, or 0 when unresolvable.
	 */
	private function resolveUserId( int|\WP_User|null $user ): int {
		if ( null === $user ) {
			return 0;
		}

		if ( $user instanceof \WP_User ) {
			return (int) $user->ID;
		}

		return $user > 0 ? $user : 0;
	}

	/**
	 * Whether the membership's end date is still valid through end of day SAST.
	 *
	 * Reads `get_end_date( 'timestamp' )` behind a `method_exists` guard, then
	 * distinguishes THREE cases — never conflating "perpetual membership" with "couldn't
	 * read the date":
	 *  - a genuinely ABSENT end date (`null` / `''` / `0` — WooCommerce's "unlimited
	 *    membership" signal) → GRANT (unlimited access is legitimate WC semantics, though
	 *    NOT a launch product; the launch lidmaatskaps are all fixed-term, Story 4.1);
	 *  - a PRESENT-but-non-numeric / unparseable end date → DENY (an anomaly, fail-safe —
	 *    a garbage value must never read as "never expires");
	 *  - a present numeric timestamp → reinterpret as an instant and compare via the
	 *    single Kernel SAST helper. The END DATE is the time-authority, never the status.
	 *
	 * @param object             $membership The WC Memberships user-membership object.
	 * @param \DateTimeImmutable $now        The pinned "now" instant.
	 * @return bool True when within the end-of-day-SAST window.
	 */
	private function isWithinWindow( object $membership, \DateTimeImmutable $now ): bool {
		if ( ! method_exists( $membership, 'get_end_date' ) ) {
			return false; // Malformed object → fail-safe deny.
		}

		$timestamp = $membership->get_end_date( 'timestamp' );

		// Genuinely ABSENT end date (WC's unlimited-membership signal) → never expires.
		if ( null === $timestamp || '' === $timestamp || 0 === $timestamp || '0' === $timestamp ) {
			return true;
		}

		// PRESENT-but-non-numeric / unparseable end date → anomaly → fail-safe DENY
		// (distinct from "unlimited": a garbage value must not read as never-expires).
		if ( ! is_numeric( $timestamp ) ) {
			return false;
		}

		$end_date = ( new \DateTimeImmutable( '@' . (int) $timestamp ) )
			->setTimezone( new \DateTimeZone( Sast::TIMEZONE ) );

		return Sast::isThroughEndOfDay( $end_date, $now );
	}

	/**
	 * Whether a membership is an INK lidmaatskap (its plan grants a 4.1 product).
	 *
	 * Resolves the membership's plan products (`get_plan()->get_product_ids()`, behind
	 * `method_exists` guards) and intersects them with the configured INK product ids.
	 * A non-INK WooCommerce membership (no shared product) is not an INK lidmaatskap.
	 *
	 * @param object    $membership      The WC Memberships user-membership object.
	 * @param list<int> $ink_product_ids The configured INK lidmaatskap product ids.
	 * @return bool True when the membership grants an INK lidmaatskap product.
	 */
	private function isInkMembership( object $membership, array $ink_product_ids ): bool {
		if ( ! method_exists( $membership, 'get_plan' ) ) {
			return false;
		}

		$plan = $membership->get_plan();

		if ( ! is_object( $plan ) || ! method_exists( $plan, 'get_product_ids' ) ) {
			return false;
		}

		$plan_product_ids = array_map( 'intval', (array) $plan->get_product_ids() );

		return array() !== array_intersect( $plan_product_ids, $ink_product_ids );
	}

	/**
	 * The configured Story-4.1 INK lidmaatskap product ids (the `term-months =>
	 * product_id` map's values).
	 *
	 * Resolved through the 4.1 {@see MembershipPlans} registry (never reimplemented):
	 * the product id mapped to each fixed term. A `protected` seam so the unit suite
	 * can pin the set without building the option/filter chain.
	 *
	 * @return list<int> The configured INK lidmaatskap product ids (possibly empty).
	 */
	protected function inkProductIds(): array {
		$plans       = new MembershipPlans();
		$product_ids = array();

		foreach ( MembershipPlans::terms() as $term ) {
			$product_id = $plans->productIdFor( $term );

			if ( null !== $product_id ) {
				$product_ids[] = $product_id;
			}
		}

		return array_values( array_unique( $product_ids ) );
	}

	/**
	 * The user's WooCommerce Memberships ACROSS ALL STATUSES (use, don't reimplement).
	 *
	 * Reads via `wc_memberships_get_user_memberships()` (NOT the active-only convenience
	 * reader) so the gate itself sees — and decides — `expired`-flagged (cron-lag) and
	 * `paused`/`cancelled` (admin-revoked) memberships rather than trusting WooCommerce's
	 * status pre-filter. A `protected` seam (the 4.1/4.2 precedent) so the unit suite can
	 * supply mock memberships deterministically. Returns an array of membership objects;
	 * fail-safe empty.
	 *
	 * @param int $user_id The member.
	 * @return list<object> The membership objects across all statuses.
	 */
	protected function userMemberships( int $user_id ): array {
		// Inline `function_exists` narrowing at the call site: WooCommerce Memberships
		// is a premium plugin with no PHPStan stub, so the reader symbol is unknown to
		// static analysis. Guarding it here (in addition to the `isMembershipsAvailable()`
		// seam the canSubmit() flow already gates on) keeps the analysis honest without a
		// baseline entry, and is a second fail-safe for any direct call of this seam.
		if ( ! function_exists( self::WC_MEMBERSHIPS_FN ) ) {
			return array();
		}

		$memberships = wc_memberships_get_user_memberships( $user_id );

		return is_array( $memberships ) ? array_values( $memberships ) : array();
	}

	/**
	 * The current instant (via the single Kernel SAST clock).
	 *
	 * A `protected` seam so the unit suite can pin "now" and make the SAST boundary
	 * maths deterministic; production reads the timezone-aware WordPress clock.
	 *
	 * @return \DateTimeImmutable The current instant.
	 */
	protected function now(): \DateTimeImmutable {
		return Sast::now();
	}

	/**
	 * Whether WooCommerce Memberships' reader API is available in this request.
	 *
	 * The "do not reimplement" boundary (project-context.md): when the plugin is
	 * inactive the gate degrades gracefully (fail-safe deny) rather than fatalling on
	 * the missing function. A `protected` seam (not an inline `function_exists()`) so
	 * the "absent" branch is deterministically unit-testable via a test subclass — the
	 * 4.1 {@see MembershipPlans::isWooCommerceAvailable()} / 4.2 precedent (Brain
	 * Monkey-defined symbols persist within a process).
	 *
	 * @return bool True when the WC Memberships reader can be called.
	 */
	protected function isMembershipsAvailable(): bool {
		return function_exists( self::WC_MEMBERSHIPS_FN );
	}
}
