<?php
/**
 * Sponsor (borg) read-model value object — Story 14.1 (FR-58).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Sponsors;

use Ink\Content\FieldSets;
use Ink\Kernel\Scalar;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * The single read-model for a `borg` (sponsor) — Story 14.1 (FR-58).
 *
 * The `borg` CPT ({@see \Ink\Content\PostTypes::BORG}) and its five editorial
 * fields (link, sponsor tier, campaign start/end, placement) already exist from
 * Epic 2 ({@see \Ink\Content\PostTypes} 2.1, {@see FieldSets} 2.4). This value
 * object is the typed, default-safe surface the Epic-14 reader surfaces (14.2
 * scheduling, 14.3 homepage strip, 14.4 recognition section) consume — it reads
 * the meta off the authoritative post via the {@see FieldSets} meta-key constants
 * (the single source) and never re-types the `ink_borg_*` literals.
 *
 * The sponsor **name** is the post title; the **logo** is the featured image
 * (`borg` supports `thumbnail`, Story 2.1) — there is no separate logo meta.
 * The WP-touching reads ({@see forPost()}, {@see logoUrl()}) use guarded core
 * calls so the unit suite can stub them, mirroring {@see \Ink\InkPols\Issue}.
 *
 * The campaign-window math (active/rotation) is Story 14.2 — this VO holds only
 * the raw `startDate`/`endDate` strings; it does NOT compute the window.
 * Conflation-clean: reads only `Ink\Content` + `Ink\Kernel` + WP core — zero
 * Tiers/Entitlement (managing/viewing a sponsor is editorial, never gated).
 *
 * @package Ink\Core
 */
final class Sponsor {

	/**
	 * Build a sponsor read-model from its already-resolved field values.
	 *
	 * @param int    $postId    The authoritative `borg` post id.
	 * @param string $name      The sponsor name (the post title), or '' when unset.
	 * @param string $link      The sponsor's outbound URL, or '' when unset.
	 * @param string $tier      The sponsor tier (borgvlak) label, or '' when unset.
	 * @param string $startDate The campaign start date (`Y-m-d`), or '' when unset.
	 * @param string $endDate   The campaign end date (`Y-m-d`), or '' when unset.
	 * @param string $placement The placement preference, or '' when unset.
	 */
	public function __construct(
		public readonly int $postId,
		public readonly string $name,
		public readonly string $link,
		public readonly string $tier,
		public readonly string $startDate,
		public readonly string $endDate,
		public readonly string $placement,
	) {
	}

	/**
	 * Build the read-model from a post (id or object). Reads each field via the
	 * {@see FieldSets} meta-key constants; a missing/non-scalar meta degrades to the
	 * typed empty default (never a fatal or a malformed value).
	 *
	 * @param int|WP_Post $post The sponsor post or its id.
	 * @return self
	 */
	public static function forPost( int|WP_Post $post ): self {
		$id = $post instanceof WP_Post ? (int) $post->ID : (int) $post;

		return new self(
			$id,
			Scalar::asString( get_the_title( $id ) ),
			Scalar::asString( get_post_meta( $id, FieldSets::BORG_LINK, true ) ),
			Scalar::asString( get_post_meta( $id, FieldSets::BORG_TIER, true ) ),
			Scalar::asString( get_post_meta( $id, FieldSets::BORG_START_DATE, true ) ),
			Scalar::asString( get_post_meta( $id, FieldSets::BORG_END_DATE, true ) ),
			Scalar::asString( get_post_meta( $id, FieldSets::BORG_PLACEMENT, true ) ),
		);
	}

	/**
	 * The sponsor logo (featured image) URL at the given registered size, or ''
	 * when none resolves.
	 *
	 * Resolves the featured-image attachment id then its URL, both guarded so the
	 * unit suite stubs them and a missing image fails to '' (never a fatal).
	 * Mirrors {@see \Ink\InkPols\Issue::coverUrl()}.
	 *
	 * @param string $size A registered image size (default `medium`).
	 * @return string
	 */
	public function logoUrl( string $size = 'medium' ): string {
		if ( $this->postId <= 0 || ! function_exists( 'get_post_thumbnail_id' ) || ! function_exists( 'wp_get_attachment_image_url' ) ) {
			return '';
		}

		$thumbnail_id = (int) get_post_thumbnail_id( $this->postId );

		if ( $thumbnail_id <= 0 ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $thumbnail_id, $size );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Whether the sponsor has a usable logo — a featured image that resolves to a URL.
	 *
	 * @return bool
	 */
	public function hasLogo(): bool {
		return '' !== $this->logoUrl();
	}
}
