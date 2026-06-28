<?php
/**
 * Sponsors module public facade — Story 14.1 (FR-58).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Sponsors;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Sponsors module facade — the sole public cross-module surface (AD-1).
 *
 * Other modules reach Sponsors through this facade, never into its internals. It
 * exposes the sponsor read-model ({@see sponsorFor()}), the canonical set of
 * sponsor meta keys ({@see metaKeys()}, delegating to the {@see FieldSets} single
 * source), and — from 14.2 — the campaign-window surfaces ({@see activeSponsors()}
 * / {@see featuredSponsor()}, delegating to {@see Campaign}). Conflation-clean — no
 * Tiers/Entitlement.
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * The {@see Sponsor} read-model for a post, or null when it is not a real
	 * `borg` sponsor. Type-guards before reading so a wrong-CPT or non-existent
	 * id never yields a phantom sponsor.
	 *
	 * @param int|WP_Post $post The sponsor post or its id.
	 * @return Sponsor|null
	 */
	public static function sponsorFor( int|WP_Post $post ): ?Sponsor {
		$id = $post instanceof WP_Post ? (int) $post->ID : (int) $post;

		if ( $id <= 0 ) {
			return null;
		}

		if ( PostTypes::BORG !== get_post_type( $id ) ) {
			return null;
		}

		return Sponsor::forPost( $id );
	}

	/**
	 * The five borg sponsor meta keys (the FR-58 fields), in registration order.
	 * Delegates to {@see FieldSets} so the keys stay single-sourced.
	 *
	 * @return list<string>
	 */
	public static function metaKeys(): array {
		return array(
			FieldSets::BORG_LINK,
			FieldSets::BORG_TIER,
			FieldSets::BORG_START_DATE,
			FieldSets::BORG_END_DATE,
			FieldSets::BORG_PLACEMENT,
		);
	}

	/**
	 * All sponsors currently within their campaign window (Story 14.2). Delegates to
	 * {@see Campaign}. The recognition section (14.4) consumes this.
	 *
	 * @param \DateTimeImmutable|null $now The instant to test; defaults to SAST now.
	 * @return list<Sponsor>
	 */
	public static function activeSponsors( ?\DateTimeImmutable $now = null ): array {
		return Campaign::activeSponsors( $now );
	}

	/**
	 * The single sponsor to feature right now — the daily-rotated pick from the active
	 * set, or null when none is active (Story 14.2). Delegates to {@see Campaign}. The
	 * homepage strip (14.3) consumes this.
	 *
	 * @param \DateTimeImmutable|null $now The instant; defaults to SAST now.
	 * @return Sponsor|null
	 */
	public static function featuredSponsor( ?\DateTimeImmutable $now = null ): ?Sponsor {
		return Campaign::featured( $now );
	}
}
