<?php
/**
 * Lidmaatskap access-state enum + its Afrikaans status message (Story 4.7, FR-9).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * The closed value set of lidmaatskap ACCESS STATES a lid can be in, each carrying
 * its lid-family Afrikaans status message (FR-9 / Story 4.7).
 *
 * The four states are the acceptance-criteria set verbatim (epics.md#Story-4.7):
 *  - {@see Active}        — the lid has an active lidmaatskap and may plaas.
 *  - {@see Expired}       — the lidmaatskap's term has lapsed; renew to plaas again.
 *  - {@see AccessDenied}  — access is denied because a betaalde lidmaatskap is needed
 *                           (the publish-denial case — Story 6.8 surfaces it).
 *  - {@see PaymentFailed} — a PayFast payment failed or was cancelled on return.
 *
 * Modelled as a closed `enum` per the project rule "model fixed value sets as enums
 * in ink-core; the string is the persisted/stable value; never duplicate these
 * literals" — and mirroring the in-module {@see LidmaatskapTerm} precedent (an enum
 * whose display string defers to the terminology registry, never an inline literal).
 * The backing `string` is the STABLE STATE ID (kebab-case, matching the AC wording),
 * suitable for a consumer to pass around / persist as a state marker.
 *
 * MESSAGE SOURCE (AD-10 / Gate D): the message TEXT is NOT held here — each case maps
 * to a terminology-registry KEY ({@see messageKey()}) and the message is resolved
 * through {@see Terms::label()}. The four registry literals are the machine projection
 * of `docs/afrikaans-terms.md` Deel 3 (human-authored, approved — never AI-translated).
 * So re-deciding a message's wording is a one-file edit in {@see Terms}, and this enum
 * inlines no Afrikaans literal.
 *
 * THE conflation rule (AD-1, FR-13): an access state is a LIDMAATSKAP-STATE concept,
 * strictly independent of writer Gradering — this enum references neither the Tiers
 * module nor the writer-tier user meta. Deptrac enforces `Entitlement ⟂ Tiers`.
 *
 * @package Ink\Core
 */
enum MembershipStatus: string {

	case Active        = 'active';
	case Expired       = 'expired';
	case AccessDenied  = 'access-denied';
	case PaymentFailed = 'payment-failed';

	/**
	 * The Afrikaans status message for this state, resolved through the registry.
	 *
	 * Never an inline bare literal (AD-10 / Gate D): the message lives once in
	 * {@see Terms} (projected from `docs/afrikaans-terms.md` Deel 3), so re-deciding
	 * the wording is a one-file edit there.
	 */
	public function message(): string {
		return Terms::label( $this->messageKey() );
	}

	/**
	 * The terminology-registry key for this state's status message.
	 */
	public function messageKey(): string {
		return match ( $this ) {
			self::Active        => 'status_active',
			self::Expired       => 'status_expired',
			self::AccessDenied  => 'status_access_denied',
			self::PaymentFailed => 'status_payment_failed',
		};
	}
}
