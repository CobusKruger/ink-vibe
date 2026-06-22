<?php
/**
 * Lidmaatskap plan-slot value object (Story 4.1, FR-4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

defined( 'ABSPATH' ) || exit;

/**
 * One launch lidmaatskap plan slot (FR-4 / Story 4.1) — an immutable description
 * of a sellable lidmaatskap, NOT the sellable product itself.
 *
 * A slot binds a fixed {@see LidmaatskapTerm} (1/6/12 months) to its WooCommerce
 * product id (nullable when unmapped). It deliberately carries:
 *  - the term LENGTH (the INK-held fixed value);
 *  - the WooCommerce product id (the mapping, admin/config-driven);
 *  - NO price field — the price is owned by the WooCommerce product and resolved
 *    at runtime by {@see MembershipPlans::priceFor()} (AC-2: no hardcoded value);
 *  - NO `isRecurring` data and NO discount/savings field — auto-renew and a
 *    genuine recurring discount are post-launch (Stories 4.9–4.11). {@see
 *    isRecurring()} is a hard `false` at launch (AC-1), and there is no discount
 *    surface for any "%-off"/"save R…"/"best value" vanity framing (AC-3).
 *
 * THE conflation rule (AD-1): a plan describes lidmaatskap access only — zero
 * reference to writer Gradering (`Ink\Tiers`).
 *
 * @package Ink\Core
 */
final class MembershipPlan {

	/**
	 * Describe one launch plan slot (a fixed term + its WooCommerce product id).
	 *
	 * @param LidmaatskapTerm $term      The fixed term length (1/6/12 months).
	 * @param int|null        $productId The mapped WooCommerce product id, or null when unmapped.
	 */
	public function __construct(
		public readonly LidmaatskapTerm $term,
		public readonly ?int $productId = null,
	) {}

	/**
	 * Whether this plan auto-renews. Always FALSE at launch (AC-1: "no auto-renew
	 * at launch"); recurring is post-launch (Story 4.9). Exposed as a method (not
	 * mutable data) so the launch invariant cannot be configured ON by accident.
	 */
	public function isRecurring(): bool {
		return false;
	}
}
