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
 * Other modules reach Sponsors through this facade, never into its internals. At
 * 14.1 it exposes the sponsor read-model ({@see sponsorFor()}) and the canonical
 * set of sponsor meta keys ({@see metaKeys()}, delegating to the {@see FieldSets}
 * single source). Conflation-clean — no Tiers/Entitlement.
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
}
