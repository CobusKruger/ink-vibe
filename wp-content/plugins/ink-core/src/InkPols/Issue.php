<?php
/**
 * InkPols issue read-model value object — Story 13.1 (FR-57).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\InkPols;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Kernel\Scalar;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * The single read-model for an InkPols `uitgawe` (issue) — Story 13.1 (FR-57).
 *
 * The `inkpols_uitgawe` CPT ({@see PostTypes::INKPOLS_UITGAWE}) and its five
 * editorial fields (issue date, volume, cover image, PDF, teaser) already exist
 * from Epic 2 ({@see PostTypes} 2.1, {@see FieldSets} 2.4). This value object is
 * the typed, default-safe surface the Epic-13 reader surfaces (13.2 by-year
 * archive, 13.3 PDF viewing) consume — it reads the meta off the authoritative
 * post via the {@see FieldSets} meta-key constants (the single source) and never
 * re-types the `ink_inkpols_*` literals.
 *
 * Pure derived accessors ({@see year()}) stay WordPress-free so they unit-test
 * without mocks; the WP-touching reads ({@see forPost()}, {@see displayDate()},
 * the attachment resolvers) use guarded core calls so the unit suite can stub
 * them. Conflation-clean: reads only `Ink\Content` + `Ink\Kernel` + WP core —
 * zero Tiers/Entitlement (modelling an issue is open, never gated).
 *
 * @package Ink\Core
 */
final class Issue {

	/**
	 * Build an issue read-model from its already-resolved field values.
	 *
	 * @param int    $postId    The authoritative `inkpols_uitgawe` post id.
	 * @param string $title     The issue title.
	 * @param string $issueDate The issue date (`Y-m-d`), or '' when unset.
	 * @param string $volume    The volume / jaargang label, or '' when unset.
	 * @param int    $coverId   The cover-image attachment id, or 0 when unset.
	 * @param int    $pdfId     The PDF attachment id, or 0 when unset.
	 * @param string $teaser    The teaser / voorskou text, or '' when unset.
	 */
	public function __construct(
		public readonly int $postId,
		public readonly string $title,
		public readonly string $issueDate,
		public readonly string $volume,
		public readonly int $coverId,
		public readonly int $pdfId,
		public readonly string $teaser,
	) {
	}

	/**
	 * Build the read-model from a post (id or object). Reads each field via the
	 * {@see FieldSets} meta-key constants; a missing/non-scalar meta degrades to the
	 * typed empty default (never a fatal or a malformed value).
	 *
	 * @param int|WP_Post $post The issue post or its id.
	 * @return self
	 */
	public static function forPost( int|WP_Post $post ): self {
		$id = $post instanceof WP_Post ? (int) $post->ID : (int) $post;

		return new self(
			$id,
			(string) get_the_title( $id ),
			Scalar::asString( get_post_meta( $id, FieldSets::INKPOLS_ISSUE_DATE, true ) ),
			Scalar::asString( get_post_meta( $id, FieldSets::INKPOLS_VOLUME, true ) ),
			Scalar::asNonNegativeInt( get_post_meta( $id, FieldSets::INKPOLS_COVER_ID, true ) ),
			Scalar::asNonNegativeInt( get_post_meta( $id, FieldSets::INKPOLS_PDF_ID, true ) ),
			Scalar::asString( get_post_meta( $id, FieldSets::INKPOLS_TEASER, true ) ),
		);
	}

	/**
	 * The 4-digit publication year parsed from {@see $issueDate} — the by-year
	 * archive grouping key (13.2). Pure: returns '' for an absent/malformed date.
	 *
	 * @return string
	 */
	public function year(): string {
		if ( 1 === preg_match( '/^(\d{4})-\d{2}-\d{2}/', $this->issueDate, $m ) ) {
			return $m[1];
		}

		return '';
	}

	/**
	 * The issue date localised for display via `wp_date`, or '' when no date.
	 *
	 * Mirrors {@see \Ink\Challenges\SinglePage::formatDeadline()}: localised via
	 * `wp_date()` when available, with a deterministic `Y-m-d` `gmdate` fallback so
	 * the unit suite (no `wp_date`) stays green.
	 *
	 * @return string
	 */
	public function displayDate(): string {
		if ( '' === $this->issueDate ) {
			return '';
		}

		$timestamp = strtotime( $this->issueDate );

		if ( false === $timestamp ) {
			return '';
		}

		if ( function_exists( 'wp_date' ) ) {
			$format    = function_exists( 'get_option' ) ? (string) get_option( 'date_format', 'j F Y' ) : 'j F Y';
			$formatted = wp_date( '' !== $format ? $format : 'j F Y', $timestamp );

			if ( is_string( $formatted ) && '' !== $formatted ) {
				return $formatted;
			}
		}

		return gmdate( 'Y-m-d', $timestamp );
	}

	/**
	 * The cover-image URL at the given registered size, or '' when none resolves.
	 *
	 * @param string $size A registered image size (default `large`).
	 * @return string
	 */
	public function coverUrl( string $size = 'large' ): string {
		if ( $this->coverId <= 0 || ! function_exists( 'wp_get_attachment_image_url' ) ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $this->coverId, $size );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * The issue PDF URL, or '' when none resolves.
	 *
	 * @return string
	 */
	public function pdfUrl(): string {
		if ( $this->pdfId <= 0 || ! function_exists( 'wp_get_attachment_url' ) ) {
			return '';
		}

		$url = wp_get_attachment_url( $this->pdfId );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Whether the issue has a viewable PDF — a positive id that resolves to a URL.
	 *
	 * @return bool
	 */
	public function hasPdf(): bool {
		return '' !== $this->pdfUrl();
	}
}
