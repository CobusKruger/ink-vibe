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
 * editorial fields (issue date, cover image, PDF, teaser, titel, featured items) exist
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
	 * @param int                                                         $postId    The authoritative `inkpols_uitgawe` post id.
	 * @param string                                                      $title     The issue title.
	 * @param string                                                      $issueDate The issue date (`Y-m-d`), or '' when unset.
	 * @param int                                                         $coverId   The cover-image attachment id, or 0 when unset.
	 * @param int                                                         $pdfId     The PDF attachment id, or 0 when unset.
	 * @param string                                                      $teaser    The teaser / voorskou text, or '' when unset.
	 * @param string                                                      $titel     The issue's heading (`inkpols.md` §2), or '' when unset.
	 * @param array<int, array{title:string, writer:string, tipe:string}> $featured The featured items, glans-1 to glans-3, keyed 1 to 3.
	 */
	public function __construct(
		public readonly int $postId,
		public readonly string $title,
		public readonly string $issueDate,
		public readonly int $coverId,
		public readonly int $pdfId,
		public readonly string $teaser,
		public readonly string $titel = '',
		public readonly array $featured = array(),
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
			Scalar::asNonNegativeInt( get_post_meta( $id, FieldSets::INKPOLS_COVER_ID, true ) ),
			Scalar::asNonNegativeInt( get_post_meta( $id, FieldSets::INKPOLS_PDF_ID, true ) ),
			Scalar::asString( get_post_meta( $id, FieldSets::INKPOLS_TEASER, true ) ),
			Scalar::asString( get_post_meta( $id, FieldSets::INKPOLS_TITEL, true ) ),
			self::readFeatured( $id )
		);
	}

	/**
	 * The three featured items, `glans-1` to `glans-3`, read from the issue's meta.
	 *
	 * @param int $id The issue post id.
	 * @return array<int, array{title:string, writer:string, tipe:string}>
	 */
	private static function readFeatured( int $id ): array {
		$featured = array();

		foreach ( FieldSets::glansKeys() as $n => $keys ) {
			$featured[ $n ] = array(
				'title'  => Scalar::asString( get_post_meta( $id, $keys['titel'], true ) ),
				'writer' => Scalar::asString( get_post_meta( $id, $keys['skrywer'], true ) ),
				'tipe'   => Scalar::asString( get_post_meta( $id, $keys['tipe'], true ) ),
			);
		}

		return $featured;
	}

	/**
	 * The three featured items in order, glans-1 to glans-3 (owner, 2026-11-03). `tipe` is the
	 * editor's own free-text description of the item (a poem, a story, an interview), shown as
	 * the kind label; nothing about the kind is hardcoded. A blank item is left out.
	 *
	 * @return list<array{tipe:string, title:string, writer:string}>
	 */
	public function items(): array {
		$items = array();

		$featured = $this->featured;

		ksort( $featured );

		foreach ( $featured as $item ) {
			if ( '' !== trim( $item['title'] ) ) {
				$items[] = array(
					'tipe'   => $item['tipe'],
					'title'  => $item['title'],
					'writer' => $item['writer'],
				);
			}
		}

		return $items;
	}

	/**
	 * The issue's URL.
	 *
	 * @return string
	 */
	public function permalink(): string {
		return (string) get_permalink( $this->postId );
	}

	/**
	 * The 4-digit publication year parsed from {@see $issueDate} — the by-year
	 * archive grouping key (13.2). Pure: returns '' for an absent/malformed date.
	 *
	 * @return string
	 */
	public function year(): string {
		$date = $this->normalisedDate();

		return '' !== $date ? substr( $date, 0, 4 ) : '';
	}

	/**
	 * The issue date localised for display via `wp_date`, or '' when no date.
	 *
	 * Mirrors {@see \Ink\Challenges\SinglePage::formatDeadline()}: localised via
	 * `wp_date()` when available, with a deterministic `Y-m-d` `gmdate` fallback so
	 * the unit suite (no `wp_date`) stays green. The timestamp is anchored at noon
	 * UTC so a site timezone offset can never shift the rendered calendar day
	 * across a month/year boundary (R13 review).
	 *
	 * @return string
	 */
	public function displayDate(): string {
		$date = $this->normalisedDate();

		if ( '' === $date ) {
			return '';
		}

		$timestamp = strtotime( $date . ' 12:00:00 UTC' );

		if ( false === $timestamp ) {
			return '';
		}

		if ( function_exists( 'wp_date' ) ) {
			$format    = function_exists( 'get_option' ) ? Scalar::asString( get_option( 'date_format', 'j F Y' ), 'j F Y' ) : 'j F Y';
			$formatted = wp_date( '' !== $format ? $format : 'j F Y', $timestamp );

			if ( is_string( $formatted ) && '' !== $formatted ) {
				return $formatted;
			}
		}

		return gmdate( 'Y-m-d', $timestamp );
	}

	/**
	 * The issue date as a REAL `Y-m-d` calendar date, or '' when absent/malformed.
	 *
	 * The single trust point for the date: it requires the `Y-m-d` shape AND a real
	 * calendar date (`checkdate`), so a well-shaped-but-invalid value (`2026-02-30`,
	 * `2026-13-01` — both pass {@see \Ink\Content\FieldSets::sanitizeDate}, which
	 * only checks the shape) is treated as undated CONSISTENTLY by both
	 * {@see year()} and {@see displayDate()} rather than one grouping it and the
	 * other shifting/blanking it (R13 review).
	 *
	 * @return string
	 */
	private function normalisedDate(): string {
		if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', $this->issueDate, $m ) ) {
			return '';
		}

		if ( ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
			return '';
		}

		return $m[1] . '-' . $m[2] . '-' . $m[3];
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
