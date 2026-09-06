<?php
/**
 * Lidmaatskap status-card dates — "Lid sedert" / "Hernieu" (Story 9.x, My Profiel).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

defined( 'ABSPATH' ) || exit;

/**
 * The read-model behind the My Profiel Lidmaatskap-tab status card's two date
 * rows: "Lid sedert: [datum]" (the membership start date) and "Hernieu: [datum]"
 * (the current term's end/renewal date). Facaded ONLY through {@see Api}
 * ({@see Api::memberSinceFor()} / {@see Api::renewalDateFor()}) — AD-1, the same
 * "no internals reach" rule {@see MembershipPlans} and {@see PurchaseActivation}
 * already follow.
 *
 * USE, DON'T REIMPLEMENT. The membership is read through WooCommerce Memberships'
 * own across-statuses reader ({@see SubmissionGate::WC_MEMBERSHIPS_FN} — the same
 * single source the 4.3 gate uses, never a re-declared literal), behind a
 * `function_exists` availability guard, so the seam degrades gracefully (null,
 * never a fatal) when the platform plugin is absent. An "INK lidmaatskap"
 * membership is one whose plan grants a Story-4.1 product (resolved through
 * {@see MembershipPlans}, mirroring {@see SubmissionGate::isInkMembership()}).
 *
 * SCOPE: this card is the ACTIVE-membership status surface (the ratified copy is
 * "Aktiewe lidmaatskap" / "Status: Aktief") — so both getters resolve the
 * member's current membership whose WooCommerce status is literally
 * {@see PurchaseActivation::STATUS_ACTIVE} (not the 4.3 gate's broader
 * time-authority window, which deliberately also grants a cron-lagged
 * `expired` membership FOR SUBMISSION purposes; this is a display surface, not
 * an entitlement gate, so it shows dates only for a genuinely-active
 * membership). No INK membership, no active one, or WooCommerce Memberships
 * being unavailable all degrade to null — the consumer omits the date row,
 * never a fatal.
 *
 * THE conflation rule (AD-1): a lidmaatskap-only concept — zero reference to
 * `Ink\Tiers` / writer Gradering.
 *
 * Non-`final` for the deliberate testability seam (the 4.1/4.2/4.3 precedent):
 * the WC-Memberships-availability check and the membership read are `protected`
 * so the unit suite can drive the "absent"/"present" branches deterministically
 * without mocking PHP internals (Brain Monkey-defined function symbols persist
 * within a process).
 *
 * @package Ink\Core
 */
class MembershipDates {

	/**
	 * The member's "Lid sedert" date — the active INK membership's start date.
	 *
	 * Formatted with the site's configured `date_format` via `date_i18n()` (never
	 * a hardcoded date format) — the same locale-aware rendering convention
	 * {@see \Ink\Social\SkrywerProfiel} already uses for member-facing dates.
	 *
	 * @param int $user_id The member.
	 * @return string|null The formatted start date, or null (graceful degrade).
	 */
	public function memberSinceFor( int $user_id ): ?string {
		$membership = $this->activeInkMembership( $user_id );

		if ( null === $membership ) {
			return null;
		}

		return $this->formattedDate( $membership, 'get_start_date' );
	}

	/**
	 * The member's "Hernieu" date — the active INK membership's end date.
	 *
	 * @param int $user_id The member.
	 * @return string|null The formatted end/renewal date, or null (graceful degrade).
	 */
	public function renewalDateFor( int $user_id ): ?string {
		$membership = $this->activeInkMembership( $user_id );

		if ( null === $membership ) {
			return null;
		}

		return $this->formattedDate( $membership, 'get_end_date' );
	}

	/**
	 * The member's current ACTIVE INK lidmaatskap membership, or null.
	 *
	 * Fail-safe null for: an invalid user id, WooCommerce Memberships absent, no
	 * INK lidmaatskap product configured, or no membership matching BOTH "is an
	 * INK lidmaatskap" (mirrors {@see SubmissionGate::isInkMembership()}) AND
	 * "WooCommerce status is literally {@see PurchaseActivation::STATUS_ACTIVE}".
	 *
	 * @param int $user_id The member.
	 * @return object|null The active INK membership object, or null.
	 */
	private function activeInkMembership( int $user_id ): ?object {
		if ( $user_id <= 0 ) {
			return null;
		}

		if ( ! $this->isMembershipsAvailable() ) {
			return null;
		}

		$ink_product_ids = $this->inkProductIds();

		if ( array() === $ink_product_ids ) {
			return null;
		}

		foreach ( $this->userMemberships( $user_id ) as $membership ) {
			if ( ! $this->isInkMembership( $membership, $ink_product_ids ) ) {
				continue;
			}

			if ( ! method_exists( $membership, 'get_status' ) ) {
				continue; // Malformed object → conservatively skip, never guess "active".
			}

			if ( PurchaseActivation::STATUS_ACTIVE === (string) $membership->get_status() ) {
				return $membership;
			}
		}

		return null;
	}

	/**
	 * Whether a membership is an INK lidmaatskap (its plan grants a 4.1 product).
	 *
	 * Mirrors {@see SubmissionGate::isInkMembership()} exactly (kept local rather
	 * than shared — the 4.8 {@see LifecycleEmails} precedent of a per-class
	 * duplicate for a small, stable check rather than a cross-class dependency).
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
	 * A membership date, formatted with the site's `date_format` option via
	 * `date_i18n()` — never a hardcoded date format.
	 *
	 * Reads the raw UNIX timestamp via the given getter (`get_start_date`/
	 * `get_end_date`, both support the `'timestamp'` format argument per the 4.3/
	 * 4.8 precedent), behind a `method_exists` guard. A genuinely absent value
	 * (`null`/`''`/`0` — WooCommerce's "unlimited"/"not set" signal) or a
	 * non-numeric value degrades to null rather than inventing a date.
	 *
	 * @param object $membership The WC Memberships user-membership object.
	 * @param string $getter     Either `'get_start_date'` or `'get_end_date'`.
	 * @return string|null The locale-formatted date, or null.
	 */
	private function formattedDate( object $membership, string $getter ): ?string {
		if ( ! method_exists( $membership, $getter ) ) {
			return null;
		}

		$timestamp = $membership->{$getter}( 'timestamp' );

		if ( null === $timestamp || '' === $timestamp || 0 === $timestamp || '0' === $timestamp ) {
			return null;
		}

		if ( ! is_numeric( $timestamp ) ) {
			return null; // Present-but-unparseable → anomaly, never invent a date.
		}

		return date_i18n( (string) get_option( 'date_format' ), (int) $timestamp );
	}

	/**
	 * The configured Story-4.1 INK lidmaatskap product ids (the `term-months =>
	 * product_id` map's values).
	 *
	 * Resolved through the 4.1 {@see MembershipPlans} registry (never
	 * reimplemented) — mirrors {@see SubmissionGate::inkProductIds()}.
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
	 * Mirrors {@see SubmissionGate::userMemberships()} exactly — same reader
	 * function ({@see SubmissionGate::WC_MEMBERSHIPS_FN}), same fail-safe-empty
	 * discipline. A `protected` seam so the unit suite can supply mock
	 * memberships deterministically.
	 *
	 * @param int $user_id The member.
	 * @return list<object> The membership objects across all statuses.
	 */
	protected function userMemberships( int $user_id ): array {
		if ( ! function_exists( SubmissionGate::WC_MEMBERSHIPS_FN ) ) {
			return array();
		}

		$memberships = wc_memberships_get_user_memberships( $user_id );

		return is_array( $memberships ) ? array_values( $memberships ) : array();
	}

	/**
	 * Whether WooCommerce Memberships' reader API is available in this request.
	 *
	 * The "do not reimplement" boundary (project-context.md): when the plugin is
	 * inactive this seam degrades gracefully (null dates, never a fatal) rather
	 * than fatalling on the missing function. A `protected` seam (not an inline
	 * `function_exists()`) so the "absent" branch is deterministically
	 * unit-testable via a test subclass — the 4.1 {@see MembershipPlans}/4.3
	 * {@see SubmissionGate} precedent (Brain Monkey-defined function symbols
	 * persist within a process).
	 *
	 * @return bool True when the WC Memberships reader can be called.
	 */
	protected function isMembershipsAvailable(): bool {
		return function_exists( SubmissionGate::WC_MEMBERSHIPS_FN );
	}
}
