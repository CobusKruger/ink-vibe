<?php
/**
 * InkPols module public facade — Story 13.1 (FR-57).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\InkPols;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * InkPols module facade — the sole public cross-module surface (AD-1).
 *
 * Other modules reach InkPols through this facade, never into its internals. At
 * 13.1 it exposes the issue read-model ({@see issueFor()}) and the canonical set
 * of issue meta keys ({@see metaKeys()}, delegating to the {@see FieldSets}
 * single source). Conflation-clean — no Tiers/Entitlement.
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * The {@see Issue} read-model for a post, or null when it is not a real
	 * `inkpols_uitgawe` issue. Type-guards before reading so a wrong-CPT or
	 * non-existent id never yields a phantom issue.
	 *
	 * @param int|WP_Post $post The issue post or its id.
	 * @return Issue|null
	 */
	public static function issueFor( int|WP_Post $post ): ?Issue {
		$id = $post instanceof WP_Post ? (int) $post->ID : (int) $post;

		if ( $id <= 0 ) {
			return null;
		}

		if ( PostTypes::INKPOLS_UITGAWE !== get_post_type( $id ) ) {
			return null;
		}

		return Issue::forPost( $id );
	}

	/**
	 * The five InkPols issue meta keys (the FR-57 fields), in registration order.
	 * Delegates to {@see FieldSets} so the keys stay single-sourced.
	 *
	 * @return list<string>
	 */
	public static function metaKeys(): array {
		return array(
			FieldSets::INKPOLS_ISSUE_DATE,
			FieldSets::INKPOLS_VOLUME,
			FieldSets::INKPOLS_COVER_ID,
			FieldSets::INKPOLS_PDF_ID,
			FieldSets::INKPOLS_TEASER,
		);
	}
}
