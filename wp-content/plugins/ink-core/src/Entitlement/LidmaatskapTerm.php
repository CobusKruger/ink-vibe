<?php
/**
 * Fixed lidmaatskap term-length enum (Story 4.1, FR-4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * The fixed value set of lidmaatskap term lengths (FR-4 / Story 4.1).
 *
 * The backing `int` is the term length IN MONTHS — the single source for the
 * three launch terms the acceptance criteria say "stay 1/6/12". Modelled as an
 * enum per the project rule "Model fixed value sets as enums; the int is the
 * persisted value; never duplicate literals."
 *
 * Scope boundary (the anti-hardcode rule, AC-2): this enum holds ONLY the term
 * LENGTH. The PRICE (R60 / R300 / R600 at launch) is owned by the WooCommerce
 * product and is admin-editable — it is resolved at runtime by
 * {@see MembershipPlans::priceFor()}, NEVER a literal here. A redakteur changing
 * a price/term in WooCommerce admin needs no `ink-core` code change.
 *
 * No recurring / discount concept is attached — auto-renew and a genuine
 * recurring discount are post-launch (Stories 4.9–4.11). This is a value type
 * only; lives in the Entitlement module (domain-specific) rather than the Kernel
 * (which carries only cross-module enums like {@see \Ink\Kernel\Tier}).
 *
 * THE conflation rule (AD-1): lidmaatskap entitlement is strictly independent of
 * writer Gradering — this enum carries no reference to `Ink\Tiers`.
 *
 * @package Ink\Core
 */
enum LidmaatskapTerm: int {

	case OneMonth     = 1;
	case SixMonths    = 6;
	case TwelveMonths = 12;

	/**
	 * The term length in months (the backing value, named for readability).
	 */
	public function months(): int {
		return $this->value;
	}

	/**
	 * The Afrikaans display label, resolved through the terminology registry.
	 *
	 * Never an inline bare literal (AD-10 / Gate D): the label lives once in
	 * {@see Terms} (projected from `docs/afrikaans-terms.md`), so re-deciding the
	 * wording is a one-file edit there.
	 */
	public function label(): string {
		return Terms::label( $this->termKey() );
	}

	/**
	 * The terminology-registry key for this term's display label.
	 */
	private function termKey(): string {
		return match ( $this ) {
			self::OneMonth     => 'term_1_month',
			self::SixMonths    => 'term_6_months',
			self::TwelveMonths => 'term_12_months',
		};
	}
}
