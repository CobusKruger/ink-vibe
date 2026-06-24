<?php
/**
 * Lidmaatskap status-message resolver — state → Afrikaans message (Story 4.7, FR-9).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

defined( 'ABSPATH' ) || exit;

/**
 * The state → Afrikaans-message RESOLVER for lidmaatskap access states (FR-9 /
 * Story 4.7).
 *
 * A thin read-model (the {@see PlanPresenter} shape): it introduces NO new business
 * rule — the entitlement AUTHORITY remains {@see SubmissionGate::canSubmit()} (the
 * 4.3 bool gate). This class only MAPS a known state to the right lid-family Afrikaans
 * message, so a consumer never re-derives the mapping or inlines a literal:
 *  - {@see messageFor()} — a {@see MembershipStatus} → its registry message (the
 *    primary surface; the consumer already holds the typed state);
 *  - {@see fromWcStatus()} — a WooCommerce Memberships STATUS STRING → the matching
 *    {@see MembershipStatus}, for a consumer (Story 9.4 status surface) that has only
 *    the platform's raw status string in hand;
 *  - {@see messageForWcStatus()} — the convenience of the two combined.
 *
 * WC-status mapping (the SAME vocabulary the 4.3 gate decides on, kept consistent):
 *  - `active` / `complimentary` / `free` / `free_trial`        → {@see MembershipStatus::Active}
 *  - `expired`                                                 → {@see MembershipStatus::Expired}
 *  - `cancelled` / `paused` / `pending` / `pending_cancellation`
 *    (and any unrecognised status)                             → {@see MembershipStatus::AccessDenied}
 *
 * `payment-failed` is DELIBERATELY NOT reachable from {@see fromWcStatus()}: it is a
 * PayFast RETURN/CANCEL state (the off-site gateway came back failed/cancelled), NOT
 * a WooCommerce Memberships status. Its consumer (the 4.2 purchase-return context)
 * resolves {@see MembershipStatus::PaymentFailed} DIRECTLY and calls
 * {@see messageFor()} — never via a membership status string.
 *
 * Scope (Story 4.7): the messages + the resolver ONLY. The actual RENDER consumers do
 * not exist yet — the publish-DENIAL enforcement point is Story 6.8 (`Ink\Submission`)
 * and the lidmaatskap / My Profiel status SURFACE is Story 9.4; both consume this
 * resolver through {@see Api} later. This class registers no hook (a pure on-demand
 * read, like the gate) and renders nothing (escaping happens at the consumer's render
 * point).
 *
 * THE conflation rule (AD-1, FR-13): lidmaatskap-state only — zero reference to
 * `Ink\Tiers` / writer Gradering.
 *
 * @package Ink\Core
 */
final class StatusMessages {

	/**
	 * The WooCommerce Memberships status strings that map to {@see MembershipStatus::Active}.
	 *
	 * The platform's OWN status slugs (no `ink_` prefix — they are WooCommerce's
	 * vocabulary). Kept as a single-source internal set, mirroring the way the 4.3
	 * {@see SubmissionGate} treats these as the entitled / time states.
	 *
	 * @var list<string>
	 */
	private const WC_ACTIVE_STATUSES = array( 'active', 'complimentary', 'free', 'free_trial' );

	/**
	 * The WooCommerce Memberships "expired" status string (a TIME state, AD-2).
	 */
	private const WC_EXPIRED_STATUS = 'expired';

	/**
	 * The Afrikaans status message for a typed access state.
	 *
	 * The primary surface: the consumer holds the typed {@see MembershipStatus} and
	 * gets back the registry-resolved Afrikaans message (never an inline literal).
	 *
	 * @param MembershipStatus $status The access state.
	 * @return string The lid-family Afrikaans status message.
	 */
	public function messageFor( MembershipStatus $status ): string {
		return $status->message();
	}

	/**
	 * Map a WooCommerce Memberships status string to the matching access state.
	 *
	 * For a consumer (Story 9.4) that has only the platform's raw status string. Maps
	 * the entitled / time / administrative-revocation vocabulary to the closed enum;
	 * any UNRECOGNISED status fails safe to {@see MembershipStatus::AccessDenied} (the
	 * conservative "no access" state, never an over-permissive Active). NEVER returns
	 * {@see MembershipStatus::PaymentFailed} — that is a PayFast-return state, not a
	 * membership status (resolve it directly from the payment-return context).
	 *
	 * The input is normalised with `strtolower( trim( … ) )` before matching, so a
	 * stray-cased / whitespace-padded status (`'Active'`, `' active '`, `'EXPIRED'`)
	 * still maps to the correct enum case rather than fail-safe-denying an entitled
	 * member (a false denial) — mirroring the fail-safe-deny hardening of the 4.3
	 * `SubmissionGate` / `StorefrontSuppression`. The fail-safe DIRECTION is unchanged:
	 * a genuinely unknown status still resolves to {@see MembershipStatus::AccessDenied}.
	 *
	 * @param string $wc_status The WooCommerce Memberships status slug.
	 * @return MembershipStatus The matching access state (fail-safe AccessDenied).
	 */
	public function fromWcStatus( string $wc_status ): MembershipStatus {
		$wc_status = strtolower( trim( $wc_status ) );

		if ( in_array( $wc_status, self::WC_ACTIVE_STATUSES, true ) ) {
			return MembershipStatus::Active;
		}

		if ( self::WC_EXPIRED_STATUS === $wc_status ) {
			return MembershipStatus::Expired;
		}

		// `cancelled` / `paused` / `pending` / `pending_cancellation` and any
		// unrecognised status → conservative no-access (fail-safe).
		return MembershipStatus::AccessDenied;
	}

	/**
	 * The Afrikaans status message for a WooCommerce Memberships status string.
	 *
	 * Convenience: {@see fromWcStatus()} then {@see messageFor()}.
	 *
	 * @param string $wc_status The WooCommerce Memberships status slug.
	 * @return string The lid-family Afrikaans status message.
	 */
	public function messageForWcStatus( string $wc_status ): string {
		return $this->messageFor( $this->fromWcStatus( $wc_status ) );
	}
}
