<?php
/**
 * INK user-meta registration.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

use Ink\Kernel\Capabilities;
use Ink\Kernel\Tier;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the writer-tier user meta (Story 2.3).
 *
 * The meta keys are declared once here as class constants — the single source for
 * the keys (mirroring {@see PostTypes}/{@see Taxonomies}). This story registers
 * only the STORAGE SUBSTRATE; the sole write path `Tiers::promote()`, the
 * win-count threshold engine and `ink_tier_win_count` (Story 5.7) remain Epic 5
 * (the Tiers module).
 *
 * THE conflation rule (AD-1): writer tier (Gradering → competition pools) is kept
 * strictly independent of lidmaatskap entitlement (→ submission). This registrar
 * carries NO reference to `Ink\Entitlement`. The controlled value set + default
 * come from the Kernel-owned {@see Tier} enum, so a junk write can never persist
 * an invalid grade. Writes are staff-gated on {@see Capabilities::MANAGE_TIERS}.
 *
 * Registered inside the `Ink\Content` module on `init` ({@see Module::register()},
 * after the CPTs/taxonomies). No registration leaks into the theme.
 *
 * @package Ink\Core
 */
final class UserMeta {

	// Migration-/ID-stable meta keys — aliases of the Kernel-owned single source
	// ({@see Tier::META_KEY}). The Kernel ownership lets `Ink\Tiers` read the same
	// key without a forbidden `Tiers → Content` edge (Story 5.1); the literal
	// values are unchanged, so no migration and no consumer breakage.
	public const WRITER_TIER      = Tier::META_KEY;
	public const TIER_PROMOTED_AT = Tier::PROMOTED_AT_META_KEY;

	/**
	 * Every INK writer-tier user-meta key, registration order preserved.
	 *
	 * `ink_tier_win_count` is intentionally absent — it is registered in Story 5.7.
	 *
	 * @return list<string>
	 */
	public static function keys(): array {
		return array(
			self::WRITER_TIER,
			self::TIER_PROMOTED_AT,
		);
	}

	/**
	 * Register the writer-tier user meta. Invoked on `init` from
	 * {@see Module::register()} after the CPT/taxonomy registrars.
	 */
	public function register(): void {
		$gate = static fn (): bool => current_user_can( Capabilities::MANAGE_TIERS );

		register_meta(
			'user',
			self::WRITER_TIER,
			array(
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => Tier::Brons->value, // Controlled default — never a 'brons' literal.
				'sanitize_callback' => array( self::class, 'sanitizeTier' ),
				'auth_callback'     => $gate, // Staff-set only; tier is never self-set by the member.
			)
		);

		register_meta(
			'user',
			self::TIER_PROMOTED_AT,
			array(
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => $gate,
			)
		);
	}

	/**
	 * Coerce any value to a valid writer-tier backing string.
	 *
	 * An unrecognised value (typo, malicious REST payload) falls back to the
	 * `brons` default via the Kernel {@see Tier} enum — an invalid grade can never
	 * persist.
	 *
	 * @param mixed $value Incoming meta value.
	 * @return string A valid {@see Tier} backing value.
	 */
	public static function sanitizeTier( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return Tier::Brons->value;
		}

		return ( Tier::tryFrom( (string) $value ) ?? Tier::Brons )->value;
	}
}
